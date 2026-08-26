@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                        <div class="h-8 w-8 bg-indigo-600 rounded-xl flex items-center justify-center">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">MeetRoom</span>
                    </a>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="{{ route('meetings.index') }}" class="text-sm text-gray-500 hover:text-gray-700">All Meetings</a>
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
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        
        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- Meeting Header -->
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $meeting->title }}</h1>
                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                            <span class="font-mono">{{ $meeting->meeting_code }}</span>
                            <span>•</span>
                            <span>Host: {{ $meeting->host->name }}</span>
                        </div>
                    </div>
                    
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                        @if($meeting->status === 'active') bg-green-100 text-green-800
                        @elseif($meeting->status === 'scheduled') bg-blue-100 text-blue-800
                        @elseif($meeting->status === 'ended') bg-gray-100 text-gray-800
                        @else bg-yellow-100 text-yellow-800 @endif">
                        {{ ucfirst($meeting->status) }}
                    </span>
                </div>
            </div>
            
            <!-- Meeting Details -->
            <div class="p-6 border-b border-gray-200">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $meeting->description ?? 'No description' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Scheduled</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($meeting->scheduled_at)
                                {{ $meeting->scheduled_at->format('M d, Y g:i A') }}
                            @else
                                Not scheduled
                            @endif
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Started</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($meeting->started_at)
                                {{ $meeting->started_at->format('M d, Y g:i A') }}
                            @else
                                Not started
                            @endif
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Ended</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            @if ($meeting->ended_at)
                                {{ $meeting->ended_at->format('M d, Y g:i A') }}
                            @else
                                Not ended
                            @endif
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Max Participants</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $meeting->max_participants }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Waiting Room</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $meeting->waiting_room_enabled ? 'Enabled' : 'Disabled' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Join Before Host</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $meeting->allow_participants_before_host ? 'Allowed' : 'Not Allowed' }}</dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Recording</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $meeting->recording_enabled ? 'Enabled' : 'Disabled' }}</dd>
                    </div>
                </dl>
            </div>
            
            <!-- Participants -->
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Participants ({{ $meeting->participants->count() }})</h2>
                
                @if ($meeting->participants->isEmpty())
                    <p class="text-gray-500">No participants yet</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($meeting->participants as $participant)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $participant->display_name }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($participant->role === 'host') bg-purple-100 text-purple-800
                                                @elseif($participant->role === 'cohost') bg-blue-100 text-blue-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst($participant->role) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($participant->status === 'joined') bg-green-100 text-green-800
                                                @elseif($participant->status === 'waiting') bg-yellow-100 text-yellow-800
                                                @elseif($participant->status === 'left') bg-gray-100 text-gray-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst($participant->status) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            @if ($participant->joined_at)
                                                {{ $participant->joined_at->format('M d, Y g:i A') }}
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            @if ($meeting->host_id === auth()->id() && $participant->user_id !== auth()->id())
                                                @if ($participant->status === 'waiting')
                                                    <form action="{{ route('meetings.participants.admit', [$meeting, $participant]) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-indigo-600 hover:text-indigo-900 text-sm">Admit</button>
                                                    </form>
                                                @elseif ($participant->status === 'joined')
                                                    <form action="{{ route('meetings.participants.remove', [$meeting, $participant]) }}" method="POST" class="inline" onsubmit="return confirm('Remove this participant?')">
                                                        @csrf
                                                        <button type="submit" class="text-red-600 hover:text-red-900 text-sm">Remove</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            
            <!-- Actions -->
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <div class="flex flex-wrap gap-3">
                    @if ($meeting->host_id === auth()->id())
                        @if ($meeting->status === 'scheduled')
                            <form action="{{ route('meetings.start', $meeting) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700">
                                    <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Start Meeting
                                </button>
                            </form>
                        @elseif ($meeting->status === 'active')
                            <a href="{{ route('meetings.room', $meeting) }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 inline-flex items-center">
                                <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 3h6v6"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14L21 3"></path>
                                </svg>
                                Enter Room
                            </a>
                            <form action="{{ route('meetings.end', $meeting) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700" onclick="return confirm('End this meeting for everyone?')">
                                    End Meeting
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('meetings.edit', $meeting) }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Edit</a>
                        
                        <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this meeting?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">Delete</button>
                        </form>
                    @else
                        @if ($meeting->status === 'active' || $meeting->status === 'scheduled')
                            <a href="{{ route('meetings.pre-join', $meeting) }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                Join Meeting
                            </a>
                        @endif
                    @endif
                    
                    <a href="{{ route('meetings.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Back to List</a>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection