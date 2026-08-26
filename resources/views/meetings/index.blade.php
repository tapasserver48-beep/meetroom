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
                    <a href="{{ route('meetings.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                        <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Meeting
                    </a>
                    
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

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">My Meetings</h1>
            <a href="{{ route('meetings.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors">
                <svg class="inline-block h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Meeting
            </a>
        </div>

        @if ($meetings->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No meetings yet</h3>
                <p class="mt-2 text-gray-500">Get started by creating your first meeting.</p>
                <a href="{{ route('meetings.create') }}" class="mt-6 inline-block px-6 py-3 text-base font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Create Meeting</a>
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Meeting</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participants</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($meetings as $meeting)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $meeting->title }}</div>
                                        @if ($meeting->description)
                                            <div class="text-sm text-gray-500 truncate max-w-xs">{{ $meeting->description }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-gray-500">{{ $meeting->meeting_code }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        @if ($meeting->scheduled_at)
                                            {{ $meeting->scheduled_at->format('M d, Y g:i A') }}
                                        @else
                                            <span class="text-gray-400">Not scheduled</span>
                                        @endif
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
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $meeting->participants->where('status', 'joined')->count() }} / {{ $meeting->max_participants }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('meetings.show', $meeting) }}" class="text-sm text-indigo-600 hover:text-indigo-900">View</a>
                                            
                                            @if ($meeting->host_id === auth()->id())
                                                @if ($meeting->status === 'scheduled')
                                                    <form action="{{ route('meetings.start', $meeting) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-sm text-green-600 hover:text-green-900">Start</button>
                                                    </form>
                                                @elseif ($meeting->status === 'active')
                                                    <a href="{{ route('meetings.room', $meeting) }}" class="text-sm text-green-600 hover:text-green-900">Enter</a>
                                                    <form action="{{ route('meetings.end', $meeting) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-sm text-red-600 hover:text-red-900 ml-2">End</button>
                                                    </form>
                                                @endif
                                                
                                                <a href="{{ route('meetings.edit', $meeting) }}" class="text-sm text-gray-600 hover:text-gray-900">Edit</a>
                                                
                                                <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this meeting?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-sm text-red-600 hover:text-red-900 ml-2">Delete</button>
                                                </form>
                                            @else
                                                @if ($meeting->status === 'active' || $meeting->status === 'scheduled')
                                                    <a href="{{ route('meetings.pre-join', $meeting) }}" class="text-sm text-indigo-600 hover:text-indigo-900">Join</a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $meetings->links() }}
                </div>
            </div>
        @endif
    </main>
</div>
@endsection