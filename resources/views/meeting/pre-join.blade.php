@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ auth()->check() ? route('dashboard') : route('meetings.join-by-code', $meeting->meeting_code) }}" class="flex items-center space-x-2">
                        <div class="h-8 w-8 bg-indigo-600 rounded-xl flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">MeetRoom</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    @if (auth()->check())
                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">Dashboard</a>

                        <div class="flex items-center space-x-3">
                            <div class="h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-indigo-700">{{ strtoupper(auth()->user()->name[0]) }}</span>
                            </div>
                            <span class="hidden sm:block text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Logout</button>
                        </form>
                    @else
                        <span class="text-sm text-gray-500">Joining as guest:</span>
                        <div class="flex items-center space-x-2">
                            <div class="h-8 w-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium text-indigo-700">{{ strtoupper(substr($participant->display_name, 0, 1)) }}</span>
                            </div>
                            <span class="hidden sm:block text-sm font-medium text-gray-700">{{ $participant->display_name }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <!-- Meeting Info -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-indigo-100 text-indigo-800 text-sm font-medium mb-4">
                    <span class="font-mono mr-2">{{ $meeting->meeting_code }}</span>
                    <span>{{ ucfirst($meeting->status) }}</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $meeting->title }}</h1>
                @if ($meeting->description)
                    <p class="text-gray-600 mb-4">{{ $meeting->description }}</p>
                @endif
                <div class="flex items-center justify-center space-x-6 text-sm text-gray-500">
                    @if ($meeting->scheduled_at)
                        <span class="flex items-center">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{ $meeting->scheduled_at->format('M d, Y g:i A') }}
                        </span>
                    @endif
                    <span class="flex items-center">
                        <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        Host: {{ $meeting->host->name }}
                    </span>
                </div>
            </div>

            <!-- Media Preview -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Camera & Microphone Preview</h2>
                <div class="relative aspect-video bg-gray-100 rounded-lg overflow-hidden" id="video-preview">
                    <video id="local-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
                    <div id="video-placeholder" class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
                        <svg class="h-16 w-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <span>Click "Join Meeting" to start preview</span>
                    </div>
                </div>
                
                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Microphone</label>
                        <select id="mic-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Default</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Camera</label>
                        <select id="camera-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Default</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-4 flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="mic-toggle" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">Microphone</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" id="camera-toggle" checked class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <span class="ml-2 text-sm text-gray-700">Camera</span>
                    </label>
                </div>
            </div>

            <!-- Join Button -->
            <div class="flex justify-center space-x-4">
                <a href="{{ auth()->check() ? route('meetings.show', $meeting) : route('meetings.join-by-code', $meeting->meeting_code) }}" class="px-6 py-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                <button id="join-btn" class="px-8 py-3 text-base font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="inline-block h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h6v6"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14L21 3"></path>
                    </svg>
                    Join Meeting
                </button>
            </div>
        </div>
    </main>
</div>

@push('scripts')
<script>
    let localStream = null;
    const video = document.getElementById('local-video');
    const placeholder = document.getElementById('video-placeholder');
    const micSelect = document.getElementById('mic-select');
    const cameraSelect = document.getElementById('camera-select');
    const micToggle = document.getElementById('mic-toggle');
    const cameraToggle = document.getElementById('camera-toggle');
    const joinBtn = document.getElementById('join-btn');

    async function initMedia() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            
            // Populate microphone select
            const audioInputs = devices.filter(d => d.kind === 'audioinput');
            audioInputs.forEach(device => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Microphone ${micSelect.length + 1}`;
                micSelect.appendChild(option);
            });
            
            // Populate camera select
            const videoInputs = devices.filter(d => d.kind === 'videoinput');
            videoInputs.forEach(device => {
                const option = document.createElement('option');
                option.value = device.deviceId;
                option.text = device.label || `Camera ${cameraSelect.length + 1}`;
                cameraSelect.appendChild(option);
            });
            
            // Start preview
            await startPreview();
        } catch (error) {
            console.error('Error accessing media devices:', error);
            placeholder.innerHTML = `
                <svg class="h-16 w-16 mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>Unable to access camera/microphone</span>
                <p class="text-xs mt-1">Please check browser permissions</p>
            `;
        }
    }

    async function startPreview() {
        try {
            const constraints = {
                audio: micToggle.checked ? { deviceId: micSelect.value || undefined } : false,
                video: cameraToggle.checked ? { deviceId: cameraSelect.value || undefined } : false
            };
            
            if (localStream) {
                localStream.getTracks().forEach(track => track.stop());
            }
            
            localStream = await navigator.mediaDevices.getUserMedia(constraints);
            video.srcObject = localStream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
        } catch (error) {
            console.error('Error starting preview:', error);
            video.style.display = 'none';
            placeholder.style.display = 'flex';
            placeholder.innerHTML = `
                <svg class="h-16 w-16 mb-2 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span>Permission denied</span>
                <p class="text-xs mt-1">Please allow camera/microphone access</p>
            `;
        }
    }

    micSelect.addEventListener('change', startPreview);
    cameraSelect.addEventListener('change', startPreview);
    micToggle.addEventListener('change', startPreview);
    cameraToggle.addEventListener('change', startPreview);

    joinBtn.addEventListener('click', function() {
        // Store media preferences in sessionStorage for the meeting room
        sessionStorage.setItem('meeting_mic_enabled', micToggle.checked);
        sessionStorage.setItem('meeting_camera_enabled', cameraToggle.checked);
        sessionStorage.setItem('meeting_mic_device', micSelect.value);
        sessionStorage.setItem('meeting_camera_device', cameraSelect.value);
        
        // Navigate to meeting room
        window.location.href = '{{ route('meetings.room', $meeting) }}';
    });

    // Initialize on load
    initMedia();

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
    });
</script>
@endpush