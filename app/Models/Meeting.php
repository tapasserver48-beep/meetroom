<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'meeting_code',
        'host_id',
        'title',
        'description',
        'password',
        'scheduled_at',
        'started_at',
        'ended_at',
        'status',
        'max_participants',
        'waiting_room_enabled',
        'allow_participants_before_host',
        'recording_enabled',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'waiting_room_enabled' => 'boolean',
        'allow_participants_before_host' => 'boolean',
        'recording_enabled' => 'boolean',
        'max_participants' => 'integer',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MeetingMessage::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MeetingSession::class);
    }

    public function settings(): HasMany
    {
        return $this->hasMany(MeetingSetting::class);
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(MeetingSession::class)->whereNull('ended_at')->latest();
    }

    public function getCurrentParticipantsCountAttribute(): int
    {
        return $this->participants()->where('status', 'joined')->count();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function canJoin(User $user): bool
    {
        if (!in_array($this->status, ['scheduled', 'active'])) {
            return false;
        }

        if ($this->password && !request()->hasValidPassword()) {
            return false;
        }

        if ($this->current_participants_count >= $this->max_participants) {
            return false;
        }

        if ($this->waiting_room_enabled && !$this->allow_participants_before_host) {
            return $this->host_id === $user->id || $user->isCohost($this);
        }

        return true;
    }
}