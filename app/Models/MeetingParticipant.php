<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'guest_name',
        'role',
        'joined_at',
        'left_at',
        'microphone_enabled',
        'camera_enabled',
        'screen_sharing',
        'status',
        'connection_data',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'microphone_enabled' => 'boolean',
        'camera_enabled' => 'boolean',
        'screen_sharing' => 'boolean',
        'connection_data' => 'array',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Guest';
    }

    public function isHost(): bool
    {
        return $this->role === 'host';
    }

    public function isCohost(): bool
    {
        return $this->role === 'cohost';
    }

    public function isJoined(): bool
    {
        return $this->status === 'joined';
    }
}