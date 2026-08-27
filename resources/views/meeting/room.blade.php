@extends('layouts.app')

@php
    $publicHost = trim((string) config('meetroom.reverb.public_host'));
    if ($publicHost) {
        $echoConfig = [
            'key' => config('meetroom.reverb.key'),
            'wsHost' => $publicHost,
            'wsPort' => 80,
            'wssPort' => 443,
            'forceTLS' => true,
        ];
    } else {
        $echoConfig = [
            'key' => config('meetroom.reverb.key'),
            'wsHost' => request()->getHost(),
            'wsPort' => (int) config('meetroom.reverb.port'),
            'wssPort' => (int) config('meetroom.reverb.port'),
            'forceTLS' => false,
        ];
    }
    $isHostUser = $participant->role === 'host' || $participant->role === 'cohost'
        || (auth()->check() && auth()->id() === $meeting->host_id);
    $roomConfig = [
        'meetingId' => $meeting->id,
        'meetingCode' => $meeting->meeting_code,
        'participantId' => $participant->id,
        'participantName' => $participant->display_name,
        'isHost' => $isHostUser,
        'echo' => $echoConfig,
        'iceServers' => json_encode($iceServers ?? []),
        'urls' => [
            'state' => route('rooms.state', $meeting),
            'hello' => route('rooms.hello', $meeting),
            'signal' => route('rooms.signal', $meeting),
            'media' => route('rooms.media', $meeting),
            'chat' => route('rooms.chat', $meeting),
            'bye' => route('rooms.bye', $meeting),
            'poll' => route('rooms.poll', $meeting),
            'waitingList' => route('rooms.waiting-list', $meeting),
            'admit' => route('meetings.participants.admit', [$meeting, ':pid:']),
            'remove' => route('meetings.participants.remove', [$meeting, ':pid:']),
            'invite' => route('meetings.join-by-code', $meeting->meeting_code),
            'exit' => auth()->check() ? route('meetings.show', $meeting) : route('meetings.join-by-code', $meeting->meeting_code),
        ],
    ];
@endphp

@section('content')
<div class="min-h-screen bg-black">
    <!-- Meeting Room Header -->
    <header class="fixed top-0 left-0 right-0 z-40 bg-gray-900/95 backdrop-blur-sm border-b border-gray-800">
        <div class="max-w-full mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-4">
                    <button id="back-btn" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-lg font-semibold text-white">{{ $meeting->title }}</h1>
                        <p class="text-xs text-gray-400 font-mono">{{ $meeting->meeting_code }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <!-- Connection Status -->
                    <div id="connection-status" class="flex items-center space-x-1 px-3 py-1 rounded-full bg-gray-800">
                        <span id="status-indicator" class="h-2 w-2 rounded-full bg-yellow-500"></span>
                        <span id="status-text" class="text-xs text-gray-300">Connecting...</span>
                    </div>

                    @if ($isHostUser)
                        <!-- Waiting Room (host only) -->
                        <button id="waiting-btn" class="relative flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors">
                            <svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span class="text-sm font-medium text-white hidden sm:inline">Waiting</span>
                            <span id="waiting-badge" class="hidden absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center">0</span>
                        </button>
                    @endif

                    <!-- Participants Count -->
                    <button id="participants-btn" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors">
                        <svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span id="participant-count" class="text-sm font-medium text-white">1</span>
                    </button>

                    <!-- Chat Toggle -->
                    <button id="chat-btn" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors">
                        <svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span class="text-sm font-medium text-white hidden sm:inline">Chat</span>
                    </button>

                    <!-- Share Link -->
                    <button id="share-btn" class="p-2 rounded-lg bg-gray-800 hover:bg-gray-700 transition-colors" title="Copy invite link">
                        <svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 13l4 4 4-4M7 6a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zm14 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </button>

                    <!-- Leave Button -->
                    <form action="{{ route('meetings.leave', $meeting) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 transition-colors">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            <span class="text-sm font-medium text-white hidden sm:inline">Leave</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Meeting Area -->
    <main class="pt-16 h-screen flex flex-col">
        <!-- Video Grid -->
        <div id="video-grid" class="flex-1 relative overflow-hidden pb-24">
            <!-- Local Video (Picture in Picture) -->
            <div id="local-video-container" class="absolute bottom-4 right-4 z-20 w-48 h-36 md:w-64 md:h-48 rounded-lg overflow-hidden border-2 border-indigo-500 bg-gray-900">
                <video id="local-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
                <div id="local-video-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-gray-500 bg-gray-900">
                    <svg class="h-8 w-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-xs">Camera off</span>
                </div>
                <div class="absolute bottom-2 left-2 right-2 flex justify-between px-2">
                    <span class="text-xs font-medium text-white bg-gray-900/80 px-2 py-1 rounded">{{ $participant->display_name }} (You)</span>
                    <span id="local-share-indicator" class="hidden text-xs font-medium text-white bg-green-600/90 px-2 py-1 rounded">Sharing</span>
                </div>
            </div>

            <!-- Remote Videos Grid -->
            <div id="remote-videos" class="w-full h-full grid grid-cols-1 gap-2 p-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div id="empty-state" class="col-span-full row-span-full flex flex-col items-center justify-center text-gray-500">
                    <svg class="h-24 w-24 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-lg">You're the only one here</p>
                    <p class="text-sm">Share the meeting link — others join with just their name</p>
                </div>
            </div>
        </div>

        <!-- Control Bar -->
        <div class="fixed bottom-0 left-0 right-0 z-50 bg-gray-900/95 backdrop-blur-sm border-t border-gray-800">
            <div class="max-w-full mx-auto px-4 py-4">
                <div class="flex items-center justify-center space-x-6">
                    <!-- Mic Toggle -->
                    <button id="mic-btn" class="flex flex-col items-center p-3 rounded-xl bg-gray-800 hover:bg-gray-700 transition-colors" title="Mute/Unmute (M)">
                        <svg id="mic-icon" class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                        </svg>
                        <span id="mic-label" class="text-xs text-gray-400 mt-1">Mute</span>
                    </button>

                    <!-- Camera Toggle -->
                    <button id="camera-btn" class="flex flex-col items-center p-3 rounded-xl bg-gray-800 hover:bg-gray-700 transition-colors" title="Start/Stop Video (V)">
                        <svg id="camera-icon" class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span id="camera-label" class="text-xs text-gray-400 mt-1">Video</span>
                    </button>

                    <!-- Screen Share -->
                    <button id="screen-btn" class="flex flex-col items-center p-3 rounded-xl bg-gray-800 hover:bg-gray-700 transition-colors" title="Share Screen (S)">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span id="screen-label" class="text-xs text-gray-400 mt-1">Share</span>
                    </button>

                    <!-- Participants Panel Toggle -->
                    <button id="participants-panel-btn" class="flex flex-col items-center p-3 rounded-xl bg-gray-800 hover:bg-gray-700 transition-colors" title="Participants (P)">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="text-xs text-gray-400 mt-1">People</span>
                    </button>

                    <!-- Chat Panel Toggle -->
                    <button id="chat-panel-btn" class="flex flex-col items-center p-3 rounded-xl bg-gray-800 hover:bg-gray-700 transition-colors" title="Chat (C)">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <span class="text-xs text-gray-400 mt-1">Chat</span>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Participants Sidebar -->
    <aside id="participants-sidebar" class="fixed top-16 right-0 bottom-0 w-72 bg-gray-900 border-l border-gray-800 transform translate-x-full transition-transform duration-300 z-40 flex flex-col">
        <div class="p-4 border-b border-gray-800 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">Participants</h2>
            <button id="close-participants" class="p-1 rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3" id="participants-list"></div>
        <div class="p-4 border-t border-gray-800">
            <button id="invite-btn" class="w-full px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-100 rounded-lg hover:bg-indigo-200 transition-colors">
                <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Copy Invite Link
            </button>
        </div>
    </aside>

    <!-- Waiting Room Sidebar (host only) -->
    @if ($isHostUser)
    <aside id="waiting-sidebar" class="fixed top-16 right-0 bottom-0 w-72 bg-gray-900 border-l border-gray-800 transform translate-x-full transition-transform duration-300 z-40 flex flex-col">
        <div class="p-4 border-b border-gray-800 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">Waiting Room</h2>
            <button id="close-waiting" class="p-1 rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3" id="waiting-list">
            <p class="text-sm text-gray-500" id="waiting-empty">Nobody is waiting.</p>
        </div>
    </aside>
    @endif

    <!-- Chat Sidebar -->
    <aside id="chat-sidebar" class="fixed top-16 right-0 bottom-0 w-80 bg-gray-900 border-l border-gray-800 transform translate-x-full transition-transform duration-300 z-40 flex flex-col">
        <div class="p-4 border-b border-gray-800 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">Chat</h2>
            <button id="close-chat" class="p-1 rounded-lg hover:bg-gray-800 transition-colors">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chat-messages"></div>
        <div class="p-4 border-t border-gray-800">
            <form id="chat-form" class="flex space-x-2">
                <input type="text" id="chat-input" placeholder="Type a message..." maxlength="2000" class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-white font-medium transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </button>
            </form>
        </div>
    </aside>
</div>

@push('scripts')
<script>
    // ---- Injected config -------------------------------------------------
    const roomConfig = @json($roomConfig);

    // ---- State -----------------------------------------------------------
    let localStream = null;
    let screenStream = null;
    let isScreenSharing = false;
    let isMicEnabled = sessionStorage.getItem('meeting_mic_enabled') !== 'false';
    let isCameraEnabled = sessionStorage.getItem('meeting_camera_enabled') !== 'false';

    const peers = new Map();          // participantId -> RTCPeerConnection
    const pendingIce = new Map();     // participantId -> [candidates]
    const roster = new Map();         // participantId -> participant payload
    const chatSeen = new Set();
    const seenSignals = new Set();    // WS + poll dedupe
    const offeredTo = new Set();      // peers we already sent an offer to
    let channel = null;

    // ---- DOM ---------------------------------------------------------------
    const localVideo = document.getElementById('local-video');
    const localVideoPlaceholder = document.getElementById('local-video-placeholder');
    const remoteVideos = document.getElementById('remote-videos');
    const emptyState = document.getElementById('empty-state');
    const participantCount = document.getElementById('participant-count');
    const statusIndicator = document.getElementById('status-indicator');
    const statusText = document.getElementById('status-text');
    const participantsList = document.getElementById('participants-list');
    const chatMessages = document.getElementById('chat-messages');

    const urls = roomConfig.urls;
    const me = { id: roomConfig.participantId, name: roomConfig.participantName, isHost: roomConfig.isHost };

    // ---- Init ---------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', async () => {
        console.log('[room] wsHost:', roomConfig.echo.wsHost, '| channel: meeting.' + roomConfig.meetingId);
        await initLocalMedia();
        setupUI();
        renderRosterSelf();
        await syncRoster();   // REST-first: roster correct even if WS is slow/down
        announceHello();      // presence broadcast (subscribed peers offer to us)
        await connectSignaling();
        setupUnload();
        setInterval(() => syncRoster(), 10000);  // roster re-sync + offer reconcile
        setInterval(pollSignals, 2000);          // reliable signal delivery fallback
    });
    async function initLocalMedia() {
        try {
            const constraints = {
                audio: true,
                video: { width: { ideal: 1280 }, height: { ideal: 720 } }
            };
            localStream = await navigator.mediaDevices.getUserMedia(constraints);
            localStream.getAudioTracks().forEach(t => t.enabled = isMicEnabled);
            localStream.getVideoTracks().forEach(t => t.enabled = isCameraEnabled);
            localVideo.srcObject = localStream;
            updateLocalMediaUI();
        } catch (err) {
            console.warn('Media unavailable, joining without devices:', err);
            isMicEnabled = false;
            isCameraEnabled = false;
            updateLocalMediaUI();
        }
    }

    function updateLocalMediaUI() {
        localVideo.style.display = (isCameraEnabled && localStream) ? 'block' : 'none';
        localVideoPlaceholder.style.display = (isCameraEnabled && localStream) ? 'none' : 'flex';
        document.getElementById('mic-label').textContent = isMicEnabled ? 'Mute' : 'Unmute';
        document.getElementById('camera-label').textContent = isCameraEnabled ? 'Video' : 'Video Off';
        document.getElementById('mic-btn').classList.toggle('bg-red-600', !isMicEnabled);
        document.getElementById('camera-btn').classList.toggle('bg-red-600', !isCameraEnabled);
    }

    // ---- Signaling (Laravel Echo + REST relay) ------------------------------
    async function connectSignaling() {
        try {
            window.echo = await window.initEcho(roomConfig.echo);
            channel = window.echo.join(`meeting.${roomConfig.meetingId}`);

            bindConnectionState();
            bindRoomEvents();

            channel.subscribed(async () => {
                console.log('[room] subscribed — announcing presence');
                setConnectionStatus('connected');
                await announceHello();
            });
        } catch (err) {
            console.error('Signaling init failed:', err);
            setConnectionStatus('failed');
        }
    }

    function bindConnectionState() {
        const conn = window.echo.connector.pusher.connection;
        const map = { connected: 'connected', connecting: 'reconnecting', uninitialized: 'reconnecting', unavailable: 'failed', failed: 'failed', disconnected: 'disconnected' };
        conn.bind('state_change', (states) => {
            console.log('[room] WS state:', states.current);
            setConnectionStatus(map[states.current] || 'reconnecting');
        });
    }

    function bindRoomEvents() {
        channel.listen('.room.signal', (msg) => processSignal(msg));
    }

    /**
     * Single entry point for all signaling messages (WebSocket OR REST poll).
     * Deduplicated by server-assigned signal id.
     */
    function processSignal(msg) {
        if (msg.sid) {
            if (seenSignals.has(msg.sid)) return;
            seenSignals.add(msg.sid);
            if (seenSignals.size > 800) seenSignals.clear();
        }
        if (msg.from === me.id) return; // ignore own broadcasts

        switch (msg.type) {
            case 'hello':           handlePeerHello(msg); break;
            case 'offer':           handleOffer(msg); break;
            case 'answer':          handleAnswer(msg); break;
            case 'ice':             handleIce(msg); break;
            case 'bye':             handlePeerBye(msg); break;
            case 'media-status':    handleMediaStatus(msg); break;
            case 'chat':            handleChat(msg); break;
        }
    }

    /**
     * REST fallback poll: guarantees signal delivery even if WebSocket dies.
     */
    async function pollSignals() {
        if (document.hidden) return;
        try {
            const { data } = await window.axios.get(urls.poll);
            (data.signals || []).forEach(processSignal);
        } catch (e) {
            // 403 = we were reaped as stale (e.g., laptop sleep) — re-enter room
            if (e?.response?.status === 403) {
                const last = Number(sessionStorage.getItem('room_rejoin_at') || 0);
                if (Date.now() - last > 30000) {
                    sessionStorage.setItem('room_rejoin_at', String(Date.now()));
                    window.location.reload();
                }
            }
        }
    }

    async function syncRoster() {
        try {
            const { data } = await window.axios.get(urls.state);
            (data.participants || []).forEach(p => roster.set(p.participant_id, p));
            renderRoster();
            reconcilePeers();
        } catch (err) {
            console.warn('[room] roster sync failed:', err?.response?.status);
        }
    }

    async function announceHello() {
        try {
            const { data } = await window.axios.post(urls.hello);
            (data.participants || []).forEach(p => roster.set(p.participant_id, p));
            renderRoster();
            reconcilePeers();
        } catch (err) {
            console.warn('[room] hello failed:', err?.response?.status);
        }
    }

    /**
     * Deterministic mesh rule: for every other joined participant, exactly ONE
     * side creates the offer — the participant with the LOWER id (older peer).
     * This prevents glare (both offering at once) and deadlocks (offers sent
     * before the other side subscribed are retried here every 10s).
     */
    function reconcilePeers() {
        roster.forEach((p, id) => {
            if (id === me.id || p.status !== 'joined') return;
            if (me.id < id) maybeOffer(id);
        });
    }

    async function maybeOffer(peerId) {
        const pc = createPeer(peerId);
        if (offeredTo.has(peerId) && pc.signalingState === 'stable') return;
        if (pc.signalingState !== 'stable' && pc.signalingState !== 'have-local-offer') return;

        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);
        offeredTo.add(peerId);
        postSignal(peerId, 'offer', { sdp: pc.localDescription.sdp, type: 'offer' });
    }

    // ---- WebRTC mesh ----------------------------------------------------------
    function createPeer(peerId) {
        const existing = peers.get(peerId);
        if (existing && existing.connectionState !== 'closed') return existing;
        if (existing) peers.delete(peerId);

        const iceServers = roomConfig.iceServers || [
            { urls: ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302'] },
            { urls: 'stun:global.stun.twilio.com:3478' },
            { urls: 'stun:stun.nextcloud.com:443' },
        ];

        const pc = new RTCPeerConnection({
            iceServers,
            iceCandidatePoolSize: 4,
        });

        pc.onicecandidate = (e) => {
            if (e.candidate) {
                postSignal(peerId, 'ice', {
                    candidate: e.candidate.candidate,
                    sdpMid: e.candidate.sdpMid,
                    sdpMLineIndex: e.candidate.sdpMLineIndex,
                });
            }
        };

        pc.ontrack = (e) => attachRemoteStream(peerId, e.streams[0]);

        pc.onconnectionstatechange = () => {
            const tile = document.getElementById(`remote-video-${peerId}`);
            if (tile) {
                let badge = tile.querySelector('.conn-status');
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'conn-status absolute top-2 left-2 text-xs font-medium text-white bg-gray-900/80 px-2 py-1 rounded';
                    tile.appendChild(badge);
                }
                if (pc.connectionState === 'connected') {
                    badge.textContent = '';
                    badge.classList.add('hidden');
                } else if (['connecting', 'new'].includes(pc.connectionState)) {
                    badge.textContent = 'Connecting...';
                    badge.classList.remove('hidden');
                } else if (pc.connectionState === 'failed') {
                    badge.textContent = 'Reconnecting...';
                    badge.classList.remove('hidden');
                    // Self-heal: restart ICE
                    try { pc.restartIce(); } catch (e) {}
                    setTimeout(() => { if (peers.has(peerId) && pc.connectionState === 'failed') maybeOffer(peerId); }, 3000);
                }
            }
            console.log(`[room] peer ${peerId}: ${pc.connectionState}`);
        };

        if (localStream) {
            localStream.getTracks().forEach(track => pc.addTrack(track, localStream));
        } else {
            pc.addTransceiver('audio', { direction: 'recvonly' });
            pc.addTransceiver('video', { direction: 'recvonly' });
        }

        peers.set(peerId, pc);
        ensureRemoteTile(peerId);
        return pc;
    }

    async function handlePeerHello(msg) {
        roster.set(msg.participant.participant_id, msg.participant);
        renderRoster();

        // Older peer initiates the offer to the newcomer.
        if (me.id < msg.from) maybeOffer(msg.from);
    }

    async function handleOffer(msg) {
        const pc = createPeer(msg.from);
        await pc.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: msg.data.sdp }));
        flushPendingIce(msg.from);
        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);
        postSignal(msg.from, 'answer', { sdp: pc.localDescription.sdp, type: 'answer' });
    }

    async function handleAnswer(msg) {
        const pc = peers.get(msg.from);
        if (!pc) return;
        await pc.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: msg.data.sdp }));
        flushPendingIce(msg.from);
    }

    async function handleIce(msg) {
        const pc = peers.get(msg.from);
        const candidate = new RTCIceCandidate({
            candidate: msg.data.candidate,
            sdpMid: msg.data.sdpMid,
            sdpMLineIndex: msg.data.sdpMLineIndex,
        });

        if (pc && pc.remoteDescription && pc.remoteDescription.type) {
            try { await pc.addIceCandidate(candidate); } catch (e) { console.warn('ICE error:', e); }
        } else {
            if (!pendingIce.has(msg.from)) pendingIce.set(msg.from, []);
            pendingIce.get(msg.from).push(candidate);
        }
    }

    function flushPendingIce(peerId) {
        const list = pendingIce.get(peerId);
        const pc = peers.get(peerId);
        if (list && pc) {
            list.forEach(c => pc.addIceCandidate(c).catch(() => {}));
            pendingIce.delete(peerId);
        }
    }

    function handlePeerBye(msg) {
        const pc = peers.get(msg.participant_id);
        if (pc) { pc.close(); peers.delete(msg.participant_id); }
        pendingIce.delete(msg.participant_id);
        offeredTo.delete(msg.participant_id);
        roster.delete(msg.participant_id);
        const tile = document.getElementById(`remote-video-${msg.participant_id}`);
        if (tile) tile.remove();
        renderRoster();
    }

    function handleMediaStatus(msg) {
        if (roster.has(msg.from)) {
            roster.set(msg.from, { ...roster.get(msg.from), ...msg.data });
            renderRoster();
        }
        const tile = document.getElementById(`remote-video-${msg.from}`);
        if (tile) {
            const placeholder = tile.querySelector('.video-placeholder');
            if (placeholder && !msg.data.screen_sharing) {
                placeholder.style.display = msg.data.camera_enabled ? 'none' : 'flex';
            }
            const share = tile.querySelector('.share-indicator');
            if (share) share.classList.toggle('hidden', !msg.data.screen_sharing);
        }
    }

    function postSignal(to, type, data) {
        return window.axios.post(urls.signal, { to, type, data }).catch(err => {
            console.warn(`signal ${type} to ${to} failed:`, err?.response?.status);
        });
    }

    // ---- Remote tiles / roster -------------------------------------------------
    function ensureRemoteTile(peerId) {
        let tile = document.getElementById(`remote-video-${peerId}`);
        if (tile) return tile;

        const info = roster.get(peerId) || { name: 'Guest' };
        tile = document.createElement('div');
        tile.id = `remote-video-${peerId}`;
        tile.className = 'relative aspect-video bg-gray-800 rounded-lg overflow-hidden';
        tile.innerHTML = `
            <video autoplay playsinline class="w-full h-full object-cover"></video>
            <div class="video-placeholder absolute inset-0 flex flex-col items-center justify-center text-gray-500 bg-gray-800">
                <svg class="h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <span class="text-sm">Camera off</span>
            </div>
            <span class="share-indicator hidden absolute top-2 right-2 text-xs font-medium text-white bg-green-600/90 px-2 py-1 rounded">Sharing</span>
            <div class="absolute bottom-2 left-2 right-2 flex items-center justify-between px-2">
                <span class="text-xs font-medium text-white bg-gray-900/80 px-2 py-1 rounded"></span>
                <div class="flex space-x-1">
                    <span class="mic-status h-4 w-4 text-green-400" title="Mic"></span>
                    <span class="cam-status h-4 w-4 text-green-400" title="Camera"></span>
                </div>
            </div>
        `;
        tile.querySelector('.absolute.bottom-2 span').textContent = info.name;

        const video = tile.querySelector('video');
        video.addEventListener('loadedmetadata', () => {
            tile.querySelector('.video-placeholder').style.display = 'none';
        });

        emptyState.style.display = 'none';
        remoteVideos.appendChild(tile);
        updateMediaIcons(tile, roster.get(peerId));
        return tile;
    }

    function attachRemoteStream(peerId, stream) {
        const tile = ensureRemoteTile(peerId);
        const video = tile.querySelector('video');
        video.srcObject = stream;
        video.play().catch(() => {
            // Autoplay policy: retry muted, let user unmute via click
            console.warn('[room] autoplay blocked — retrying muted for peer', peerId);
            video.muted = true;
            video.play().catch(() => {});
        });
    }

    function updateMediaIcons(tile, p) {
        if (!p) return;
        const mic = tile.querySelector('.mic-status');
        const cam = tile.querySelector('.cam-status');
        mic.innerHTML = p.microphone_enabled ? MIC_ON : MIC_OFF;
        mic.className = `mic-status h-4 w-4 ${p.microphone_enabled ? 'text-green-400' : 'text-red-400'}`;
        cam.innerHTML = p.camera_enabled ? CAM_ON : CAM_OFF;
        cam.className = `cam-status h-4 w-4 ${p.camera_enabled ? 'text-green-400' : 'text-red-400'}`;
    }

    const MIC_ON = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>';
    const MIC_OFF = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2 2m0-2l-2-2"></path></svg>';
    const CAM_ON = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>';
    const CAM_OFF = '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path></svg>';

    function renderRosterSelf() {
        roster.set(me.id, {
            participant_id: me.id,
            name: me.name,
            role: me.isHost ? 'host' : 'participant',
            status: 'joined',
            microphone_enabled: isMicEnabled,
            camera_enabled: isCameraEnabled,
            screen_sharing: false,
        });
        renderRoster();
    }

    function renderRoster() {
        participantsList.innerHTML = '';
        [...roster.values()].forEach(p => {
            const el = document.createElement('div');
            el.className = 'flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-800';
            el.innerHTML = `
                <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-medium text-sm"></div>
                <div class="flex-1 min-w-0">
                    <p class="text-white font-medium truncate"></p>
                    <p class="text-xs text-gray-400"></p>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="r-mic w-4"></span>
                    <span class="r-cam w-4"></span>
                </div>
            `;
            el.querySelector('.h-8').textContent = (p.name || 'G').charAt(0).toUpperCase();
            el.querySelector('p').textContent = p.name;
            el.querySelector('.text-xs').textContent =
                p.role + (p.participant_id === me.id ? ' (You)' : '');
            el.querySelector('.r-mic').innerHTML = p.microphone_enabled ? MIC_ON : MIC_OFF;
            el.querySelector('.r-mic').className += p.microphone_enabled ? ' text-green-400' : ' text-red-400';
            el.querySelector('.r-cam').innerHTML = p.camera_enabled ? CAM_ON : CAM_OFF;
            el.querySelector('.r-cam').className += p.camera_enabled ? ' text-green-400' : ' text-red-400';
            participantsList.appendChild(el);

            const tile = document.getElementById(`remote-video-${p.participant_id}`);
            if (tile) {
                tile.querySelector('.absolute.bottom-2 span').textContent = p.name;
                updateMediaIcons(tile, p);
            }
        });
        participantCount.textContent = roster.size;
        emptyState.style.display = roster.size > 1 ? 'none' : 'flex';
    }

    // ---- Media toggles ----------------------------------------------------------
    async function broadcastMediaStatus() {
        updateLocalMediaUI();
        try {
            await window.axios.post(urls.media, {
                microphone_enabled: isMicEnabled,
                camera_enabled: isCameraEnabled,
                screen_sharing: isScreenSharing,
            });
        } catch (e) { console.warn('media status sync failed'); }
    }

    function toggleMic() {
        isMicEnabled = !isMicEnabled;
        if (localStream) localStream.getAudioTracks().forEach(t => t.enabled = isMicEnabled);
        broadcastMediaStatus();
    }

    function toggleCamera() {
        isCameraEnabled = !isCameraEnabled;
        if (localStream) localStream.getVideoTracks().forEach(t => t.enabled = isCameraEnabled);
        broadcastMediaStatus();
    }

    async function toggleScreenShare() {
        try {
            if (isScreenSharing) {
                if (screenStream) { screenStream.getTracks().forEach(t => t.stop()); screenStream = null; }
                isScreenSharing = false;
                document.getElementById('local-share-indicator').classList.add('hidden');
                if (localStream && isCameraEnabled) {
                    replaceVideoTrackForAll(localStream.getVideoTracks()[0] || null);
                } else {
                    replaceVideoTrackForAll(null);
                }
                localVideo.srcObject = localStream;
            } else {
                screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: true });
                const track = screenStream.getVideoTracks()[0];
                isScreenSharing = true;
                replaceVideoTrackForAll(track);
                localVideo.srcObject = screenStream;
                document.getElementById('local-share-indicator').classList.remove('hidden');
                track.onended = () => { if (isScreenSharing) toggleScreenShare(); };
            }
            updateLocalMediaUI();
            broadcastMediaStatus();
        } catch (err) {
            console.warn('Screen share cancelled/failed:', err);
        }
    }

    function replaceVideoTrackForAll(track) {
        peers.forEach(pc => {
            const sender = pc.getSenders().find(s => s.track?.kind === 'video' || s.trackKind === 'video');
            if (sender) sender.replaceTrack(track).catch(() => {});
        });
    }

    // ---- Chat ---------------------------------------------------------------------
    async function handleChat(msg) {
        if (chatSeen.has(msg.data.id)) return;
        chatSeen.add(msg.data.id);
        appendChat(msg.data.sender_name, msg.data.message, msg.data.created_at);
    }

    function appendChat(sender, message, createdAt) {
        const el = document.createElement('div');
        el.className = 'flex space-x-2';
        const time = createdAt ? new Date(createdAt).toLocaleTimeString() : new Date().toLocaleTimeString();
        el.innerHTML = `
            <div class="flex-1">
                <div class="flex items-center space-x-2">
                    <span class="font-medium text-white text-sm"></span>
                    <span class="text-xs text-gray-500">${time}</span>
                </div>
                <p class="text-gray-300 break-words"></p>
            </div>`;
        el.querySelector('.font-medium').textContent = sender;
        el.querySelector('p').textContent = message;
        chatMessages.appendChild(el);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // ---- Host waiting room ----------------------------------------------------------
    let waitingTimer = null;

    function startWaitingPoll() {
        if (!me.isHost) return;
        const poll = async () => {
            try {
                const { data } = await window.axios.get(urls.waitingList);
                renderWaiting(data.waiting || []);
            } catch (e) { /* host only; ignore */ }
        };
        poll();
        waitingTimer = setInterval(poll, 5000);
    }

    function renderWaiting(list) {
        const badge = document.getElementById('waiting-badge');
        const container = document.getElementById('waiting-list');
        const empty = document.getElementById('waiting-empty');
        if (!badge || !container) return;

        badge.textContent = list.length;
        badge.classList.toggle('hidden', list.length === 0);

        container.innerHTML = '';
        if (list.length === 0) {
            empty.classList.remove('hidden');
            container.appendChild(empty);
            return;
        }

        list.forEach(p => {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between p-3 rounded-lg bg-gray-800';
            row.innerHTML = `
                <div class="flex items-center space-x-2 min-w-0">
                    <div class="h-8 w-8 rounded-full bg-yellow-600 flex items-center justify-center text-white font-medium text-sm flex-shrink-0"></div>
                    <span class="text-white text-sm truncate"></span>
                </div>
                <div class="flex space-x-2 flex-shrink-0">
                    <button class="admit px-3 py-1 text-xs font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">Admit</button>
                    <button class="deny px-3 py-1 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Deny</button>
                </div>`;
            row.querySelector('.h-8').textContent = (p.name || 'G').charAt(0).toUpperCase();
            row.querySelector('span.truncate').textContent = p.name;
            row.querySelector('.admit').onclick = () => participantAction(urls.admit.replace(':pid:', p.participant_id));
            row.querySelector('.deny').onclick = () => participantAction(urls.remove.replace(':pid:', p.participant_id));
            container.appendChild(row);
        });
    }

    async function participantAction(url) {
        try {
            await window.axios.post(url);
            const { data } = await window.axios.get(urls.waitingList);
            renderWaiting(data.waiting || []);
        } catch (e) {
            console.warn('participant action failed', e);
        }
    }

    // ---- UI wiring --------------------------------------------------------------------
    function setConnectionStatus(status) {
        const map = {
            connected:    ['bg-green-500', 'Connected'],
            reconnecting: ['bg-yellow-500 animate-pulse', 'Reconnecting...'],
            disconnected: ['bg-red-500', 'Disconnected'],
            failed:       ['bg-red-500', 'Connection failed'],
        };
        const [cls, label] = map[status] || map.reconnecting;
        statusIndicator.className = `h-2 w-2 rounded-full ${cls}`;
        statusText.textContent = label;
    }

    function showCopyFeedback(message, isError = false) {
        let toast = document.getElementById('copy-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'copy-toast';
            toast.className = 'fixed bottom-24 left-1/2 transform -translate-x-1/2 z-50 px-4 py-2 rounded-lg shadow-xl text-sm font-medium transition-all duration-300 pointer-events-none';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        toast.className = 'fixed bottom-24 left-1/2 transform -translate-x-1/2 z-50 px-4 py-2 rounded-lg shadow-xl text-sm font-medium transition-all duration-300 pointer-events-none ' +
            (isError ? 'bg-red-600 text-white' : 'bg-gray-800 text-white');
    }

    function toggleSidebar(id) {
        ['participants-sidebar', 'chat-sidebar', 'waiting-sidebar'].forEach(sid => {
            const el = document.getElementById(sid);
            if (el && sid !== id) el.classList.add('translate-x-full');
        });
        document.getElementById(id)?.classList.toggle('translate-x-full');
    }

    function setupUI() {
        document.getElementById('mic-btn').addEventListener('click', toggleMic);
        document.getElementById('camera-btn').addEventListener('click', toggleCamera);
        document.getElementById('screen-btn').addEventListener('click', toggleScreenShare);

        document.getElementById('back-btn').addEventListener('click', () => {
            window.location.href = urls.exit;
        });

        const invite = async () => {
            const link = `${window.location.origin}/join/${roomConfig.meetingCode}`;
            try {
                await navigator.clipboard.writeText(link);
                showCopyFeedback('Invite link copied! Anyone can join with just their name.');
            } catch (e) {
                showCopyFeedback(link);
            }
        };
        document.getElementById('share-btn').addEventListener('click', invite);
        document.getElementById('invite-btn').addEventListener('click', invite);

        document.getElementById('participants-btn').addEventListener('click', () => toggleSidebar('participants-sidebar'));
        document.getElementById('participants-panel-btn').addEventListener('click', () => toggleSidebar('participants-sidebar'));
        document.getElementById('close-participants').addEventListener('click', () => document.getElementById('participants-sidebar').classList.add('translate-x-full'));

        document.getElementById('chat-btn').addEventListener('click', () => toggleSidebar('chat-sidebar'));
        document.getElementById('chat-panel-btn').addEventListener('click', () => toggleSidebar('chat-sidebar'));
        document.getElementById('close-chat').addEventListener('click', () => document.getElementById('chat-sidebar').classList.add('translate-x-full'));

        if (me.isHost) {
            document.getElementById('waiting-btn').addEventListener('click', () => toggleSidebar('waiting-sidebar'));
            document.getElementById('close-waiting').addEventListener('click', () => document.getElementById('waiting-sidebar').classList.add('translate-x-full'));
            startWaitingPoll();
        }

        document.getElementById('chat-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const input = document.getElementById('chat-input');
            const message = input.value.trim();
            if (!message) return;
            input.value = '';
            try {
                const { data } = await window.axios.post(urls.chat, { message });
                // Show own message immediately; incoming broadcast/poll is deduped by id
                if (data.id && !chatSeen.has(data.id)) {
                    chatSeen.add(data.id);
                    appendChat(me.name, message, data.created_at);
                }
            } catch (err) {
                showCopyFeedback('Message failed to send', true);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
            switch (e.key.toLowerCase()) {
                case 'm': e.preventDefault(); toggleMic(); break;
                case 'v': e.preventDefault(); toggleCamera(); break;
                case 's': e.preventDefault(); toggleScreenShare(); break;
                case 'p': e.preventDefault(); toggleSidebar('participants-sidebar'); break;
                case 'c': e.preventDefault(); toggleSidebar('chat-sidebar'); break;
                case 'escape':
                    ['participants-sidebar', 'chat-sidebar', 'waiting-sidebar'].forEach(id => {
                        document.getElementById(id)?.classList.add('translate-x-full');
                    });
                    break;
            }
        });
    }

    // ---- Unload cleanup -----------------------------------------------------------------
    function setupUnload() {
        const bye = () => {
            try {
                navigator.sendBeacon(urls.bye);
            } catch (e) {
                window.axios.post(urls.bye, null, { keepalive: true }).catch(() => {});
            }
        };
        window.addEventListener('pagehide', bye);
        window.addEventListener('beforeunload', () => {
            bye();
            if (localStream) localStream.getTracks().forEach(t => t.stop());
            if (screenStream) screenStream.getTracks().forEach(t => t.stop());
            peers.forEach(pc => pc.close());
        });
    }
</script>
@endpush
