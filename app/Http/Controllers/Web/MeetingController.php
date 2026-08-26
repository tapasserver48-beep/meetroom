<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ResolvesParticipant;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Services\MeetingService;
use App\Services\TurnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MeetingController extends Controller
{
    use ResolvesParticipant;

    public function __construct(
        private MeetingService $meetingService,
        private TurnService $turnService
    ) {}

    /* -----------------------------------------------------------------
     |  Meeting management (host / auth users)
     | ----------------------------------------------------------------- */

    public function index(Request $request)
    {
        $meetings = $this->meetingService->getUserMeetings($request->user());

        return view('meetings.index', compact('meetings'));
    }

    public function create()
    {
        return view('meetings.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'password' => 'nullable|string|max:50',
            'scheduled_at' => 'nullable|date|after:now',
            'max_participants' => 'nullable|integer|min:2|max:100',
            'waiting_room_enabled' => 'boolean',
            'allow_participants_before_host' => 'boolean',
            'recording_enabled' => 'boolean',
        ]);

        $meeting = $this->meetingService->createMeeting($request->user(), $validated);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting created successfully!');
    }

    public function show(Meeting $meeting)
    {
        $this->authorize('view', $meeting);

        $meeting->load(['host', 'participants.user', 'activeSession']);

        return view('meetings.show', compact('meeting'));
    }

    public function edit(Meeting $meeting)
    {
        $this->authorize('manage', $meeting);

        return view('meetings.edit', compact('meeting'));
    }

    public function update(Request $request, Meeting $meeting)
    {
        $this->authorize('manage', $meeting);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'password' => 'nullable|string|max:50',
            'scheduled_at' => 'nullable|date',
            'max_participants' => 'nullable|integer|min:2|max:100',
            'waiting_room_enabled' => 'boolean',
            'allow_participants_before_host' => 'boolean',
            'recording_enabled' => 'boolean',
        ]);

        $this->meetingService->updateMeeting($meeting, $validated);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting updated successfully!');
    }

    public function destroy(Meeting $meeting)
    {
        $this->authorize('manage', $meeting);

        $this->meetingService->deleteMeeting($meeting);

        return redirect()->route('meetings.index')
            ->with('success', 'Meeting deleted successfully!');
    }

    public function join(Request $request, Meeting $meeting)
    {
        $this->authorize('join', $meeting);

        $participant = $this->meetingService->joinMeeting($meeting, $request->user());

        if ($participant->status === 'waiting') {
            return redirect()->route('meetings.waiting', $meeting);
        }

        return redirect()->route('meetings.pre-join', $meeting);
    }

    public function start(Request $request, Meeting $meeting)
    {
        $this->authorize('manage', $meeting);

        $this->meetingService->startMeeting($meeting);

        return redirect()->route('meetings.room', $meeting)
            ->with('success', 'Meeting started!');
    }

    public function end(Request $request, Meeting $meeting)
    {
        $this->authorize('manage', $meeting);

        $this->meetingService->endMeeting($meeting);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting ended.');
    }

    /* -----------------------------------------------------------------
     |  Host controls: admit / remove participants
     | ----------------------------------------------------------------- */

    public function admitParticipant(Request $request, Meeting $meeting, MeetingParticipant $participant)
    {
        $this->authorize('manage', $meeting);

        abort_unless($participant->meeting_id === $meeting->id, 404);

        $this->meetingService->admitParticipant($meeting, $participant);

        return back()->with('success', "{$participant->display_name} was admitted.");
    }

    public function removeParticipant(Request $request, Meeting $meeting, MeetingParticipant $participant)
    {
        $this->authorize('manage', $meeting);

        abort_unless($participant->meeting_id === $meeting->id, 404);

        $this->meetingService->removeParticipant($meeting, $participant);

        return back()->with('success', "{$participant->display_name} was removed.");
    }

    /* -----------------------------------------------------------------
     |  Join flow — works for BOTH logged-in users and guests
     | ----------------------------------------------------------------- */

    public function joinByCode($meetingCode)
    {
        $meeting = Meeting::where('meeting_code', $meetingCode)->firstOrFail();

        // Already joined in this browser? Skip the form.
        $existingParticipant = $this->currentParticipant($meeting);
        if ($existingParticipant && $existingParticipant->status === 'joined' && !$existingParticipant->isHost()) {
            return redirect()->route('meetings.pre-join', $meeting);
        }
        if ($existingParticipant && $existingParticipant->status === 'waiting') {
            return redirect()->route('meetings.waiting', $meeting);
        }

        return view('meeting.join-by-code', compact('meeting'));
    }

    public function joinByCodePost(Request $request, $meetingCode)
    {
        $meeting = Meeting::where('meeting_code', $meetingCode)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string',
        ]);

        // Meeting must be joinable
        if (!in_array($meeting->status, ['scheduled', 'active'])) {
            return back()->withErrors(['name' => 'This meeting has ended or is no longer available.']);
        }

        // Capacity check
        if ($this->meetingService->getOccupiedSeats($meeting) >= $meeting->max_participants) {
            return back()->withErrors(['name' => 'This meeting is full. Please contact the host.']);
        }

        // Check password if meeting has one
        if ($meeting->password && $meeting->password !== $request->password) {
            return back()->withErrors(['password' => 'Invalid meeting password.']);
        }

        // Logged-in user: join as authenticated user
        if (Auth::check()) {
            $participant = $this->meetingService->joinMeeting($meeting, Auth::user(), $request->name);

            if ($participant->status === 'waiting') {
                return redirect()->route('meetings.waiting', $meeting);
            }

            return redirect()->route('meetings.pre-join', $meeting);
        }

        // GUEST: join with just a name — no account needed.
        $existingGuest = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('id', $this->getGuestParticipantId($meeting) ?? 0)
            ->first();

        $participant = $this->meetingService->joinAsGuest($meeting, $request->name, $existingGuest);

        $this->rememberGuestParticipant($meeting, $participant);

        if ($participant->status === 'waiting') {
            return redirect()->route('meetings.waiting', $meeting);
        }

        return redirect()->route('meetings.pre-join', $meeting);
    }

    public function preJoin(Meeting $meeting)
    {
        $participant = $this->currentParticipant($meeting);

        if (!$participant) {
            return redirect()->route('meetings.join-by-code', $meeting->meeting_code);
        }

        if ($participant->status === 'waiting') {
            return redirect()->route('meetings.waiting', $meeting);
        }

        return view('meeting.pre-join', compact('meeting', 'participant'));
    }

    public function room(Meeting $meeting)
    {
        $participant = $this->currentParticipant($meeting);

        if (!$participant) {
            return redirect()->route('meetings.join-by-code', $meeting->meeting_code);
        }

        if ($participant->status === 'waiting') {
            return redirect()->route('meetings.waiting', $meeting);
        }

        if ($participant->status === 'removed') {
            return redirect()->route('meetings.join-by-code', $meeting->meeting_code)
                ->withErrors(['name' => 'You were removed from this meeting by the host.']);
        }

        // Keep participant marked as joined
        if ($participant->status !== 'joined') {
            $this->meetingService->admitParticipant($meeting, $participant);
        }

        $iceServers = $this->turnService->getIceServers();

        return view('meeting.room', compact('meeting', 'participant', 'iceServers'));
    }

    public function waiting(Meeting $meeting)
    {
        $participant = $this->currentParticipant($meeting);

        if (!$participant) {
            return redirect()->route('meetings.join-by-code', $meeting->meeting_code);
        }

        if ($participant->status === 'joined') {
            return redirect()->route('meetings.pre-join', $meeting);
        }

        if ($participant->status === 'removed') {
            return redirect()->route('meetings.join-by-code', $meeting->meeting_code)
                ->withErrors(['name' => 'You were not admitted to this meeting.']);
        }

        return view('meeting.waiting', compact('meeting', 'participant'));
    }

    /**
     * JSON endpoint for the waiting screen to poll admission status.
     */
    public function participantStatus(Meeting $meeting)
    {
        $participant = $this->currentParticipant($meeting);

        if (!$participant) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Waiting participants are alive — keep their heartbeat fresh so the
        // stale-participant reaper never touches them during/after admission.
        $data = $participant->connection_data ?? [];
        $data['last_seen'] = now()->timestamp;
        $participant->update(['connection_data' => $data]);

        return response()->json([
            'status' => $participant->status,
            'role' => $participant->role,
            'name' => $participant->display_name,
            'meeting_status' => $meeting->status,
        ]);
    }

    public function leave(Meeting $meeting)
    {
        $participant = $this->currentParticipant($meeting);

        if ($participant) {
            $participant->update([
                'status' => 'left',
                'left_at' => now(),
                'screen_sharing' => false,
            ]);

            $this->forgetGuestParticipant($meeting);
        }

        return redirect()->route('meetings.join-by-code', $meeting->meeting_code)
            ->with('info', 'You have left the meeting. You can rejoin anytime.');
    }
}
