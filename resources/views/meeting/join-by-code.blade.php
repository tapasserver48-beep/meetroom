@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 bg-indigo-600 rounded-xl flex items-center justify-center">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">Join Meeting</h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Enter your name to join <span class="font-mono font-semibold">{{ $meeting->meeting_code }}</span>
            </p>
        </div>
        
        <form class="mt-8 space-y-6" method="POST" action="{{ route('meetings.join-by-code.post', $meeting->meeting_code) }}">
            @csrf
            
            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <div class="space-y-4">
                <div>
                    <label for="name" class="sr-only">Your Name</label>
                    <input id="name" name="name" type="text" autocomplete="name" required
                        class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                        placeholder="Your name" value="{{ old('name') }}">
                </div>
                
                @if ($meeting->password)
                    <div>
                        <label for="password" class="sr-only">Meeting Password</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Meeting password">
                    </div>
                @endif
            </div>
            
            <div>
                <button type="submit"
                    class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Join Meeting
                </button>
            </div>
        </form>
        
        <div class="text-center">
            <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:text-indigo-500">Already have an account? Sign in</a>
        </div>
    </div>
</div>
@endsection