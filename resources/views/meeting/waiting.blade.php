@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full text-center">
        <div class="mx-auto h-24 w-24 bg-yellow-100 rounded-full flex items-center justify-center mb-6">
            <svg class="h-12 w-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 mb-2">Waiting for Host</h1>
        <p class="text-gray-600 mb-6">The meeting host will let you in shortly, <span class="font-semibold">{{ $participant->display_name }}</span>.</p>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-indigo-100 text-indigo-800 text-sm font-medium mb-4">
                <span class="font-mono mr-2">{{ $meeting->meeting_code }}</span>
            </div>
            <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ $meeting->title }}</h2>
            <p class="text-gray-500">Host: {{ $meeting->host->name }}</p>
            @if ($meeting->status === 'scheduled')
                <p class="mt-2 text-xs text-gray-400">The meeting hasn't started yet. You'll be admitted once the host begins.</p>
            @endif
        </div>

        <div id="waiting-status" class="space-y-3">
            <div class="flex items-center justify-center space-x-2">
                <div class="h-2 w-2 rounded-full bg-indigo-600 animate-bounce" style="animation-delay: 0ms"></div>
                <div class="h-2 w-2 rounded-full bg-indigo-600 animate-bounce" style="animation-delay: 150ms"></div>
                <div class="h-2 w-2 rounded-full bg-indigo-600 animate-bounce" style="animation-delay: 300ms"></div>
            </div>
            <p class="text-sm text-gray-500">Checking for host...</p>
        </div>

        <div id="waiting-error" class="hidden mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm"></div>

        <form action="{{ route('meetings.leave', $meeting) }}" method="POST" class="mt-6">
            @csrf
            <button type="submit" class="inline-block px-6 py-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Leave</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const statusUrl = '{{ route('meetings.participant-status', $meeting) }}';
    const preJoinUrl = '{{ route('meetings.pre-join', $meeting) }}';
    const joinUrl = '{{ route('meetings.join-by-code', $meeting->meeting_code) }}';

    async function checkStatus() {
        try {
            const response = await fetch(statusUrl, {
                headers: { 'Accept': 'application/json' },
                'X-Requested-With': 'XMLHttpRequest'
            });

            if (response.status === 404) {
                // Session lost or participant gone — send back to join form
                clearInterval(pollInterval);
                window.location.href = joinUrl;
                return;
            }

            const data = await response.json();

            if (data.status === 'joined') {
                clearInterval(pollInterval);
                window.location.href = preJoinUrl;
            } else if (data.status === 'removed' || data.status === 'left') {
                clearInterval(pollInterval);
                document.getElementById('waiting-status').classList.add('hidden');
                const errBox = document.getElementById('waiting-error');
                errBox.textContent = data.status === 'removed'
                    ? 'The host was unable to admit you into this meeting.'
                    : 'Your connection was interrupted. Please rejoin the meeting.';
                errBox.classList.remove('hidden');
            } else if (data.meeting_status === 'ended' || data.meeting_status === 'cancelled') {
                clearInterval(pollInterval);
                document.getElementById('waiting-status').classList.add('hidden');
                const errBox = document.getElementById('waiting-error');
                errBox.textContent = 'This meeting has ended.';
                errBox.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }

    const pollInterval = setInterval(checkStatus, 3000);
    checkStatus();

    // Stop polling after 30 minutes
    setTimeout(() => clearInterval(pollInterval), 30 * 60 * 1000);
</script>
@endpush
