<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MeetingService
{
    public function __construct(
        private MeetingCodeGenerator $codeGenerator
    ) {}

    public function createMeeting(User $host, array $data): Meeting
    {
        return DB::transaction(function () use ($host, $data) {
            $meeting = Meeting::create([
                'uuid' => Str::uuid(),
                'meeting_code' => $this->codeGenerator->generate(),
                'host_id' => $host->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'password' => $data['password'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'max_participants' => $data['max_participants'] ?? 50,
                'waiting_room_enabled' => $data['waiting_room_enabled'] ?? true,
                'allow_participants_before_host' => $data['allow_participants_before_host'] ?? false,
                'recording_enabled' => $data['recording_enabled'] ?? false,
                'status' => isset($data['scheduled_at']) ? 'scheduled' : 'scheduled',
            ]);

            // Create host participant
            MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $host->id,
                'role' => 'host',
                'status' => 'joined',
                'joined_at' => now(),
                'microphone_enabled' => true,
                'camera_enabled' => true,
            ]);

            return $meeting->load('host');
        });
    }

    public function updateMeeting(Meeting $meeting, array $data): Meeting
    {
        $meeting->update($data);
        return $meeting->fresh();
    }

    public function deleteMeeting(Meeting $meeting): bool
    {
        return $meeting->delete();
    }

    public function joinMeeting(Meeting $meeting, User $user, ?string $guestName = null): MeetingParticipant
    {
        return DB::transaction(function () use ($meeting, $user, $guestName) {
            // Check if already a participant
            $existing = MeetingParticipant::where('meeting_id', $meeting->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existing) {
                $existing->update([
                    'status' => 'joined',
                    'joined_at' => now(),
                    'left_at' => null,
                    'connection_data' => array_merge($existing->connection_data ?? [], ['last_seen' => now()->timestamp]),
                ]);
                return $existing->fresh();
            }

            // Create new participant
            return MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => $user->id,
                'guest_name' => $guestName,
                'role' => 'participant',
                'status' => $this->getInitialParticipantStatus($meeting),
                'joined_at' => $this->getInitialParticipantStatus($meeting) === 'joined' ? now() : null,
                'microphone_enabled' => true,
                'camera_enabled' => true,
                'connection_data' => ['last_seen' => now()->timestamp],
            ]);
        });
    }

    public function joinAsGuest(Meeting $meeting, string $guestName, ?MeetingParticipant $existing = null): MeetingParticipant
    {
        return DB::transaction(function () use ($meeting, $guestName, $existing) {
            $status = $this->getInitialParticipantStatus($meeting);

            if ($existing) {
                // Re-joining guest: update name and reset status unless already admitted/joined
                if (!in_array($existing->status, ['joined', 'waiting'])) {
                    $existing->update([
                        'guest_name' => $guestName,
                        'status' => $status,
                        'joined_at' => $status === 'joined' ? now() : null,
                        'left_at' => null,
                    ]);
                } elseif ($existing->status === 'waiting') {
                    $existing->update(['guest_name' => $guestName]);
                }
                return $existing->fresh();
            }

            return MeetingParticipant::create([
                'meeting_id' => $meeting->id,
                'user_id' => null,
                'guest_name' => $guestName,
                'role' => 'participant',
                'status' => $status,
                'joined_at' => $status === 'joined' ? now() : null,
                'microphone_enabled' => true,
                'camera_enabled' => true,
                'connection_data' => ['last_seen' => now()->timestamp],
            ]);
        });
    }

    public function getInitialParticipantStatus(Meeting $meeting): string
    {
        return ($meeting->waiting_room_enabled && !$meeting->allow_participants_before_host) ? 'waiting' : 'joined';
    }

    public function getOccupiedSeats(Meeting $meeting): int
    {
        return $meeting->participants()
            ->whereIn('status', ['waiting', 'joined'])
            ->count();
    }

    public function leaveMeeting(Meeting $meeting, User $user): bool
    {
        $participant = MeetingParticipant::where('meeting_id', $meeting->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->update([
                'status' => 'left',
                'left_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    public function startMeeting(Meeting $meeting): Meeting
    {
        $meeting->update([
            'status' => 'active',
            'started_at' => now(),
        ]);

        // Create session
        $meeting->sessions()->create([
            'started_at' => now(),
            'participant_count' => $meeting->participants()->where('status', 'joined')->count(),
        ]);

        return $meeting->fresh();
    }

    public function endMeeting(Meeting $meeting): Meeting
    {
        $meeting->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);

        // End active session
        $activeSession = $meeting->activeSession;
        if ($activeSession) {
            $activeSession->update([
                'ended_at' => now(),
                'participant_count' => $meeting->participants()->where('status', 'joined')->count(),
            ]);
        }

        // Mark all participants as left
        $meeting->participants()->where('status', 'joined')->update([
            'status' => 'left',
            'left_at' => now(),
        ]);

        return $meeting->fresh();
    }

    public function admitParticipant(Meeting $meeting, MeetingParticipant $participant): MeetingParticipant
    {
        $data = $participant->connection_data ?? [];
        $data['last_seen'] = now()->timestamp;

        $participant->update([
            'status' => 'joined',
            'joined_at' => now(),
            'connection_data' => $data,
        ]);

        return $participant->fresh();
    }

    public function removeParticipant(Meeting $meeting, MeetingParticipant $participant): bool
    {
        $participant->update([
            'status' => 'removed',
            'left_at' => now(),
        ]);

        return true;
    }

    public function updateParticipantMedia(MeetingParticipant $participant, array $media): MeetingParticipant
    {
        $participant->update($media);
        return $participant->fresh();
    }

    public function getUserMeetings(User $user)
    {
        return Meeting::where('host_id', $user->id)
            ->orWhereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['host', 'participants.user'])
            ->latest()
            ->paginate(10);
    }

    public function getUpcomingMeetings(User $user)
    {
        return Meeting::where('host_id', $user->id)
            ->orWhereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereIn('status', ['scheduled', 'active'])
            ->where('scheduled_at', '>=', now())
            ->with(['host', 'participants.user'])
            ->orderBy('scheduled_at')
            ->get();
    }

    public function getRecentMeetings(User $user, int $limit = 5)
    {
        return Meeting::where('host_id', $user->id)
            ->orWhereHas('participants', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->whereIn('status', ['ended', 'cancelled'])
            ->with(['host', 'participants.user', 'activeSession'])
            ->latest('ended_at')
            ->limit($limit)
            ->get();
    }

    public function getDashboardStats(User $user): array
    {
        $hostedMeetings = Meeting::where('host_id', $user->id)->count();
        $participatedMeetings = Meeting::whereHas('participants', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->count();

        $activeMeetings = Meeting::where('host_id', $user->id)
            ->where('status', 'active')
            ->count();

        $upcomingMeetings = Meeting::where('host_id', $user->id)
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>=', now())
            ->count();

        return [
            'hosted_meetings' => $hostedMeetings,
            'participated_meetings' => $participatedMeetings,
            'active_meetings' => $activeMeetings,
            'upcoming_meetings' => $upcomingMeetings,
        ];
    }
}