<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MeetingPolicy
{
    /**
     * Determine whether the user can view any meetings.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the meeting.
     */
    public function view(User $user, Meeting $meeting): bool
    {
        // Host can always view
        if ($user->id === $meeting->host_id) {
            return true;
        }

        // Check if user is a participant
        $isParticipant = $meeting->participants()
            ->where('user_id', $user->id)
            ->exists();

        if ($isParticipant) {
            return true;
        }

        // Check if meeting is scheduled or active (not ended)
        return in_array($meeting->status, ['scheduled', 'active']);
    }

    /**
     * Determine whether the user can create meetings.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the meeting.
     */
    public function manage(User $user, Meeting $meeting): bool
    {
        // Only the host can manage the meeting
        return $user->id === $meeting->host_id;
    }

    /**
     * Determine whether the user can join the meeting.
     */
    public function join(User $user, Meeting $meeting): bool
    {
        // Host can always join
        if ($user->id === $meeting->host_id) {
            return true;
        }

        // Check if user is already a participant
        $participant = $meeting->participants()
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            // Check if meeting hasn't reached max participants
            $activeCount = $meeting->participants()
                ->where('status', 'joined')
                ->count();
            
            return $activeCount < $meeting->max_participants;
        }

        // User is not yet a participant - check if they can join
        if ($meeting->current_participants_count >= $meeting->max_participants) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can update the meeting.
     */
    public function update(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->host_id;
    }

    /**
     * Determine whether the user can delete the meeting.
     */
    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->id === $meeting->host_id;
    }
}