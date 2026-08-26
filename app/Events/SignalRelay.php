<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Server-relayed WebRTC signaling / room presence event.
 *
 * Broadcast on a PUBLIC channel so guest (unauthenticated) participants
 * receive it. All payloads are scoped to a single meeting and validated
 * server-side before dispatch.
 */
class SignalRelay implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $meetingId,
        public array $payload
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('meeting.' . $this->meetingId)];
    }

    public function broadcastAs(): string
    {
        return 'room.signal';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
