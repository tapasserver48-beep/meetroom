<?php

namespace App\Http\Controllers\Web\Concerns;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

trait ResolvesParticipant
{
    protected function getGuestParticipantId(Meeting $meeting): ?int
    {
        $map = Session::get('guest_participants', []);

        return $map[$meeting->id] ?? null;
    }

    protected function rememberGuestParticipant(Meeting $meeting, MeetingParticipant $participant): void
    {
        $map = Session::get('guest_participants', []);
        $map[$meeting->id] = $participant->id;
        Session::put('guest_participants', $map);
    }

    protected function forgetGuestParticipant(Meeting $meeting): void
    {
        $map = Session::get('guest_participants', []);
        unset($map[$meeting->id]);
        Session::put('guest_participants', $map);
    }

    /**
     * Resolve the current participant for this meeting: authenticated user OR session guest.
     */
    protected function currentParticipant(Meeting $meeting): ?MeetingParticipant
    {
        if (Auth::check()) {
            $participant = $meeting->participants()
                ->where('user_id', Auth::id())
                ->first();

            if ($participant) {
                return $participant;
            }
        }

        $guestId = $this->getGuestParticipantId($meeting);

        if ($guestId) {
            return MeetingParticipant::where('meeting_id', $meeting->id)
                ->where('id', $guestId)
                ->first();
        }

        return null;
    }

    /**
     * Participant that must exist and be joined, otherwise null.
     *
     * If the participant row exists but is no longer "joined" (e.g. it was
     * reaped to "left" after a stale heartbeat when the tab was backgrounded
     * and stopped polling), we auto re-admit them so the real-time session can
     * recover without a full page reload. Without this the client gets stuck in
     * a 403 loop: poll -> re-announce -> hello -> 403 -> poll ...
     * Explicitly removed participants are NOT recovered.
     */
    protected function joinedParticipant(Meeting $meeting): ?MeetingParticipant
    {
        $participant = $this->currentParticipant($meeting);

        if (!$participant) {
            return null;
        }

        if ($participant->status !== 'joined' && $participant->status !== 'removed') {
            $this->meetingService->admitParticipant($meeting, $participant);
        }

        return $participant;
    }
}
