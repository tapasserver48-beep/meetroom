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
                    <a href="{{ route('meetings.show', $meeting) }}" class="text-sm text-gray-500 hover:text-gray-700">Back to Meeting</a>
                    
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
    <main class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
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

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Meeting</h1>
            
            <form method="POST" action="{{ route('meetings.update', $meeting) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Meeting Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="title" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Enter meeting title" value="{{ old('title', $meeting->title) }}">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Optional description">{{ old('description', $meeting->description) }}</textarea>
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Meeting Password (Optional)</label>
                    <input type="text" name="password" id="password"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Leave empty for no password" value="{{ old('password', $meeting->password) }}">
                    <p class="mt-1 text-sm text-gray-500">Leave empty to remove password</p>
                </div>
                
                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-1">Schedule for Later (Optional)</label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        value="{{ old('scheduled_at') ? \Carbon\Carbon::parse(old('scheduled_at'))->format('Y-m-d\TH:i') : ($meeting->scheduled_at ? $meeting->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                    <p class="mt-1 text-sm text-gray-500">Leave empty for instant meeting</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="max_participants" class="block text-sm font-medium text-gray-700 mb-1">Max Participants</label>
                        <input type="number" name="max_participants" id="max_participants" min="2" max="100" value="{{ old('max_participants', $meeting->max_participants) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                
                <div class="space-y-4 border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900">Meeting Options</h3>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="waiting_room_enabled" id="waiting_room_enabled" value="1" {{ old('waiting_room_enabled', $meeting->waiting_room_enabled) ? 'checked' : '' }}
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="waiting_room_enabled" class="ml-2 block text-sm text-gray-700">Enable Waiting Room</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="allow_participants_before_host" id="allow_participants_before_host" value="1" {{ old('allow_participants_before_host', $meeting->allow_participants_before_host) ? 'checked' : '' }}
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="allow_participants_before_host" class="ml-2 block text-sm text-gray-700">Allow participants to join before host</label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="recording_enabled" id="recording_enabled" value="1" {{ old('recording_enabled', $meeting->recording_enabled) ? 'checked' : '' }}
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="recording_enabled" class="ml-2 block text-sm text-gray-700">Enable Recording</label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-4 border-t border-gray-200 pt-6">
                    <a href="{{ route('meetings.show', $meeting) }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </main>
</div>
@endsection