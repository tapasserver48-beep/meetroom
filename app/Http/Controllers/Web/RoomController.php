<?php

namespace App\Http\Controllers\Web;

use App\Events\SignalRelay;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Concerns\ResolvesParticipant;
use App\Models\Meeting;
use App\Models\MeetingMessage;
use App\Models\MeetingParticipant;
use App\Services\MeetingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    use ResolvesParticipant;

    public function __construct(
        private MeetingService $meetingService
    ) {}

    /**
     * Full room state for the requesting participant.
     */
    public function state(Meeting $meeting): JsonResponse
    {
        $me = $this->joinedParticipant($meeting);

        if (!$me) {
            return response()->json(['error' => 'not_in_room'], 403);
        }

        $this->touch($me);
        $this->reapStaleParticipants($meeting);

        $participants = $meeting->participants()
            ->whereIn('status', ['joined'])
            ->get()
            ->map(fn (MeetingParticipant $p) => $this->participantPayload($p))
            ->values();

        return response()->json([
            'me' => $this->participantPayload($me),
            'participants' => $participants,
            'meeting_status' => $meeting->status,
        ]);
    }

    /**
     * Announce presence: marks participant joined and notifies the room.
     * Existing peers respond by sending WebRTC offers to the newcomer.
     */
    public function hello(Meeting $meeting): JsonResponse
    {
        $me = $this->joinedParticipant($meeting);

        if (!$me) {
            return response()->json(['error' => 'not_in_room'], 403);
        }

        $this->touch($me);
        $this->reapStaleParticipants($meeting);

        if (!$me->joined_at) {
            $this->meetingService->admitParticipant($meeting, $me);
        }

        $this->relay($meeting, $me, [
            'type' => 'hello',
            'participant' => $this->participantPayload($me),
        ], null, persist: true);

        $participants = $meeting->participants()
            ->where('status', 'joined')
            ->where('id', '!=', $me->id)
            ->get()
            ->map(fn (MeetingParticipant $p) => $this->participantPayload($p))
            ->values();

        return response()->json(['participants' => $participants]);
    }

    /**
     * Relay WebRTC signaling data (offer / answer / ice) between participants.
     */
    public function signal(Request $request, Meeting $meeting): JsonResponse
    {
        $me = $this->joinedParticipant($meeting);

        if (!$me) {
            return response()->json(['error' => 'not_in_room'], 403);
        }

        $validated = $request->validate([
            'to' => 'required|integer',
            'type' => 'required|in:offer,answer,ice',
            'data' => 'required|array',
        ]);

        $target = $meeting->participants()
            ->where('id', $validated['to'])
            ->where('status', 'joined')
            ->first();

        if (!$target) {
            return response()->json(['error' => 'target_not_found'], 404);
        }

        $this->relay($meeting, $me, [
            'type' => $validated['type'],
            'data' => $validated['data'],
        ], $target->id, persist: true);

        return response()->json(['ok' => true]);
    }

    /**
     * Broadcast microphone / camera / screen-share status.
     */
    public function media(Request $request, Meeting $meeting): JsonResponse
    {
        $me = $this->joinedParticipant($meeting);

        if (!$me) {
            return response()->json(['error' => 'not_in_room'], 403);
        }

        $validated = $request->validate([
            'microphone_enabled' => 'boolean',
            'camera_enabled' => 'boolean',
            'screen_sharing' => 'boolean',
        ]);

        $me->update($validated);

        $this->relay($meeting, $me, [
            'type' => 'media-status',
            'data' => [
                'microphone_enabled' => (bool) $me->microphone_enabled,
                'camera_enabled' => (bool) $me->camera_enabled,
                'screen_sharing' => (bool) $me->screen_sharing,
            ],
        ], null, persist: true);

        return response()->json(['ok' => true]);
    }

    /**
     * Persist and broadcast a chat message.
     */
    public function chat(Request $request, Meeting $meeting): JsonResponse
    {
        $me = $this->joinedParticipant($meeting);

        if (!$me) {
            return response()->json(['error' => 'not_in_room'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $message = MeetingMessage::create([
            'meeting_id' => $meeting->id,
            'user_id' => $me->user_id,
            'sender_name' => $me->display_name,
            'message' => $validated['message'],
            'type' => 'text',
        ]);

        $this->relay($meeting, $me, [
            'type' => 'chat',
            'data' => [
                'id' => $message->id,
                'sender_name' => $message->sender_name,
                'message' => $message->message,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ], null, persist: true);

        return response()->json([
            'ok' => true,
            'id' => $message->id,
            'created_at' => $message->created_at->toIso8601String(),
        ]);
    }

    /**
     * Reliable signal delivery fallback: returns (and clears) signals queued
     * for this participant. Used by clients to survive WebSocket failures.
     */
    public function poll(Meeting $meeting): JsonResponse
    {
        $me = $this->joinedParticipant($meeting);

        if (!$me) {
            return response()->json(['error' => 'not_in_room'], 403);
        }

        $this->touch($me);
        $this->reapStaleParticipants($meeting);

        $rows = DB::table('meeting_signals')
            ->where('meeting_id', $meeting->id)
            ->where(function ($q) use ($me) {
                $q->where('target_participant_id', $me->id)->orWhereNull('target_participant_id');
            })
            ->orderBy('id')
            ->limit(100)
            ->get();

        // Only delete signals targeted specifically at this participant.
        // Broadcast (target_participant_id = NULL) signals must remain readable
        // by EVERY participant, otherwise only the first poller would receive
        // them (e.g. one-way chat). They are pruned by the stale-cleanup below.
        $deleteIds = $rows
            ->where('target_participant_id', $me->id)
            ->pluck('id');

        if ($deleteIds->isNotEmpty()) {
            DB::table('meeting_signals')->whereIn('id', $deleteIds)->delete();
        }

        // Opportunistic cleanup of undelivered stale rows
        DB::table('meeting_signals')->where('created_at', '<', now()->subMinutes(2))->delete();

        return response()->json([
            'signals' => $rows->map(fn ($r) => json_decode($r->payload, true))->values(),
        ]);
    }

    /**
     * Explicit leave: marks left and notifies the room (used on unload too).
     */
    public function bye(Meeting $meeting): JsonResponse
    {
        $me = $this->currentParticipant($meeting);

        if (!$me) {
            return response()->json(['ok' => true]);
        }

        $wasJoined = $me->status === 'joined';

        $me->update(['status' => 'left', 'left_at' => now(), 'screen_sharing' => false]);
        $this->forgetGuestParticipant($meeting);

        if ($wasJoined) {
            $this->relay($meeting, $me, [
                'type' => 'bye',
                'participant_id' => $me->id,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * HOST ONLY: participants currently waiting in the waiting room.
     */
    public function waitingList(Meeting $meeting): JsonResponse
    {
        $me = $this->currentParticipant($meeting);

        if (!$me || !$this->isHost($meeting, $me)) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $waiting = $meeting->participants()
            ->where('status', 'waiting')
            ->orderBy('created_at')
            ->get()
            ->map(fn (MeetingParticipant $p) => $this->participantPayload($p))
            ->values();

        return response()->json(['waiting' => $waiting]);
    }

    /* ----------------------------------------------------------------- */

    private function isHost(Meeting $meeting, MeetingParticipant $participant): bool
    {
        return $participant->role === 'host'
            || $participant->role === 'cohost'
            || (auth()->check() && auth()->id() === $meeting->host_id);
    }

    /**
     * Heartbeat: record that this participant is still connected.
     */
    private function touch(MeetingParticipant $participant): void
    {
        $data = $participant->connection_data ?? [];
        $data['last_seen'] = now()->timestamp;
        $participant->update(['connection_data' => $data]);
    }

    /**
     * Participants whose heartbeat is older than 120s are ghosts (crashed
     * tabs, lost connections) — mark them left so tiles stop appearing.
     * 120s tolerates background-tab timer throttling (~60s intervals).
     */
    private function reapStaleParticipants(Meeting $meeting): void
    {
        $meeting->participants()
            ->where('status', 'joined')
            ->get()
            ->each(function (MeetingParticipant $p) {
                $lastSeen = $p->connection_data['last_seen'] ?? null;

                if ($lastSeen === null || (now()->timestamp - $lastSeen) > 120) {
                    $p->update(['status' => 'left', 'left_at' => now(), 'screen_sharing' => false]);
                }
            });
    }

    private function participantPayload(MeetingParticipant $p): array
    {
        return [
            'participant_id' => $p->id,
            'name' => $p->display_name,
            'role' => $p->role,
            'status' => $p->status,
            'microphone_enabled' => (bool) $p->microphone_enabled,
            'camera_enabled' => (bool) $p->camera_enabled,
            'screen_sharing' => (bool) $p->screen_sharing,
        ];
    }

    private function relay(Meeting $meeting, MeetingParticipant $from, array $payload, ?int $to = null, bool $persist = false): void
    {
        $full = array_merge([
            'from' => $from->id,
            'from_name' => $from->display_name,
            'to' => $to,
        ], $payload);

        if ($persist) {
            $full['sid'] = (string) Str::uuid();

            DB::table('meeting_signals')->insert([
                'meeting_id' => $meeting->id,
                'target_participant_id' => $to,
                'payload' => json_encode($full),
                'created_at' => now(),
            ]);
        }

        try {
            broadcast(new SignalRelay($meeting->id, $full));
        } catch (\Throwable $e) {
            \Log::warning('Broadcast failed: ' . $e->getMessage());
        }
    }
}
