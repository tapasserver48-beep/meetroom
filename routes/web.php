<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\MeetingController;
use App\Http\Controllers\Web\RoomController;
use App\Http\Controllers\Web\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // Meetings
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->name('meetings.show');
    Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

    // Meeting actions (host)
    Route::post('/meetings/{meeting}/start', [MeetingController::class, 'start'])->name('meetings.start');
    Route::post('/meetings/{meeting}/end', [MeetingController::class, 'end'])->name('meetings.end');

    // Host controls for participants
    Route::post('/meetings/{meeting}/participants/{participant}/admit', [MeetingController::class, 'admitParticipant'])
        ->name('meetings.participants.admit');
    Route::post('/meetings/{meeting}/participants/{participant}/remove', [MeetingController::class, 'removeParticipant'])
        ->name('meetings.participants.remove');

    // Quick join shortcut for logged-in users
    Route::post('/meetings/{meeting}/join', [MeetingController::class, 'join'])->name('meetings.join');
});

// ---------------------------------------------------------------------
// Guest meeting flow (NO account required — name only)
// ---------------------------------------------------------------------
Route::get('/meetings/{meeting}/pre-join', [MeetingController::class, 'preJoin'])->name('meetings.pre-join');
Route::get('/meetings/{meeting}/room', [MeetingController::class, 'room'])->name('meetings.room');
Route::get('/meetings/{meeting}/waiting', [MeetingController::class, 'waiting'])->name('meetings.waiting');
Route::post('/meetings/{meeting}/leave', [MeetingController::class, 'leave'])->name('meetings.leave');
Route::get('/meetings/{meeting}/participant-status', [MeetingController::class, 'participantStatus'])
    ->name('meetings.participant-status');

// ---------------------------------------------------------------------
// Room real-time API (guest-compatible — session participant identity)
// ---------------------------------------------------------------------
Route::get('/meetings/{meeting}/room/state', [RoomController::class, 'state'])->name('rooms.state');
Route::post('/meetings/{meeting}/room/hello', [RoomController::class, 'hello'])->name('rooms.hello');
Route::post('/meetings/{meeting}/room/signal', [RoomController::class, 'signal'])->name('rooms.signal');
Route::post('/meetings/{meeting}/room/media', [RoomController::class, 'media'])->name('rooms.media');
Route::post('/meetings/{meeting}/room/chat', [RoomController::class, 'chat'])->name('rooms.chat');
Route::post('/meetings/{meeting}/room/bye', [RoomController::class, 'bye'])->name('rooms.bye');
Route::get('/meetings/{meeting}/room/poll', [RoomController::class, 'poll'])->name('rooms.poll');
Route::get('/meetings/{meeting}/room/waiting-list', [RoomController::class, 'waitingList'])->name('rooms.waiting-list');

// Join by meeting code (public — guests join with just a name)
Route::get('/join/{meetingCode}', [MeetingController::class, 'joinByCode'])->name('meetings.join-by-code');
Route::post('/join/{meetingCode}', [MeetingController::class, 'joinByCodePost'])->name('meetings.join-by-code.post');