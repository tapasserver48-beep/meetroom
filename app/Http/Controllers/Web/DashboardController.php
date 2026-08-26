<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\MeetingService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private MeetingService $meetingService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        
        $stats = $this->meetingService->getDashboardStats($user);
        $upcomingMeetings = $this->meetingService->getUpcomingMeetings($user);
        $recentMeetings = $this->meetingService->getRecentMeetings($user, 5);
        
        return view('dashboard', compact('stats', 'upcomingMeetings', 'recentMeetings'));
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        $stats = $this->meetingService->getDashboardStats($user);
        
        return response()->json($stats);
    }
}