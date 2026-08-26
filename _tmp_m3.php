<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Services\MeetingService;

$m = app(MeetingService::class)->createMeeting(User::first(), [
    'title' => 'TEST-PollPath',
    'waiting_room_enabled' => false,
]);
$m->update(['status' => 'active', 'started_at' => now()]);
echo $m->meeting_code . '|' . $m->id;
