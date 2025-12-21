<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'content' => $notification->content,
                    'date' => $notification->date->format('d/m/Y H:i'),
                    'is_read' => $notification->is_read,
                ];
            });

        $unread = auth()->user()->notifications()->where('is_read', false)->count();

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
            'unread_count' => $unread,
        ], 200);
    }

    /**
     * Get unread notifications count
     */
    public function unreadCount()
    {
        $count = auth()->user()->notifications()
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count,
        ], 200);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Notification marked as read',
        ], 200);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        auth()->user()->notifications()->update(['is_read' => true]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ], 200);
    }

    /**
     * Get recent notifications (last 5)
     */
    public function recent()
    {
        $notifications = auth()->user()->notifications()
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'content' => $notification->content,
                    'date' => $notification->date->format('d/m/Y H:i'),
                    'is_read' => $notification->is_read,
                ];
            });

        return response()->json([
            'data' => $notifications,
            'count' => $notifications->count(),
        ], 200);
    }
}
