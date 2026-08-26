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
                    <a href="{{ route('meetings.create') }}" class="hidden sm:block px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Meeting
                    </a>
                    
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
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Hosted Meetings</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['hosted_meetings'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Participated</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['participated_meetings'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Now</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['active_meetings'] }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="h-12 w-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Upcoming</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['upcoming_meetings'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Meetings -->
        <div class="mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Upcoming Meetings</h2>
                <a href="{{ route('meetings.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
            </div>
            
            @if ($upcomingMeetings->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No upcoming meetings</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new meeting.</p>
                    <a href="{{ route('meetings.create') }}" class="mt-4 inline-block px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Create Meeting</a>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Meeting</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($upcomingMeetings as $meeting)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $meeting->title }}</div>
                                            <div class="text-sm text-gray-500">{{ $meeting->meeting_code }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $meeting->scheduled_at ? $meeting->scheduled_at->format('M d, Y g:i A') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                @if($meeting->status === 'active') bg-green-100 text-green-800
                                                @elseif($meeting->status === 'scheduled') bg-blue-100 text-blue-800
                                                @elseif($meeting->status === 'ended') bg-gray-100 text-gray-800
                                                @else bg-yellow-100 text-yellow-800 @endif">
                                                {{ ucfirst($meeting->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                @if ($meeting->host_id === auth()->id())
                                                    @if ($meeting->status === 'scheduled')
                                                        <a href="{{ route('meetings.pre-join', $meeting) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Join</a>
                                                    @elseif ($meeting->status === 'active')
                                                        <a href="{{ route('meetings.room', $meeting) }}" class="text-sm text-green-600 hover:text-green-900">Enter</a>
                                                    @endif
                                                @else
                                                    <a href="{{ route('meetings.pre-join', $meeting) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Join</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- Recent Meetings -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Recent Meetings</h2>
                <a href="{{ route('meetings.index') }}" class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
            </div>
            
            @if ($recentMeetings->isEmpty())
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No recent meetings</h3>
                    <p class="mt-1 text-sm text-gray-500">Your meeting history will appear here.</p>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Meeting</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participants</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($recentMeetings as $meeting)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $meeting->title }}</div>
                                            <div class="text-sm text-gray-500">{{ $meeting->meeting_code }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $meeting->ended_at ? $meeting->ended_at->format('M d, Y') : ($meeting->scheduled_at ? $meeting->scheduled_at->format('M d, Y') : 'N/A') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            @if ($meeting->activeSession && $meeting->activeSession->duration)
                                                {{ gmdate('H:i:s', $meeting->activeSession->duration) }}
                                            @else
                                                --
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ $meeting->activeSession->participant_count ?? 0 }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="{{ route('meetings.show', $meeting) }}" class="text-sm text-indigo-600 hover:text-indigo-900">View Details</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </main>
</div>
@endsection
