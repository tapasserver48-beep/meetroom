<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'started_at',
        'ended_at',
        'participant_count',
        'peak_participants',
        'recording_url',
        'session_data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'participant_count' => 'integer',
        'peak_participants' => 'integer',
        'session_data' => 'array',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->ended_at) {
            return null;
        }
        return $this->started_at->diffInSeconds($this->ended_at);
    }
}