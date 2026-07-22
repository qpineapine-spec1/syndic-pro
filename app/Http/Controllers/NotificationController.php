<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $notifications = Notification::forUser($user)
            ->orderByDesc('created_at')
            ->get();

        // Mark everything as read once the user opens the full list.
        Notification::forUser($user)->where('is_read', false)->update(['is_read' => true]);

        return view('notifications.index', ['notifications' => $notifications]);
    }

    public function markRead(Request $request)
    {
        $user = $request->user();

        Notification::forUser($user)->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['ok' => true]);
    }

    /**
     * JSON endpoint used by the header notification badge to stay in sync
     * with the database without requiring a full page reload.
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['count' => 0]);
        }

        $count = Notification::forUser($user)->where('is_read', false)->count();

        return response()->json(['count' => $count]);
    }
}