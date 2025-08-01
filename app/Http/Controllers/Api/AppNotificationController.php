<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppNotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $query = auth()->user()
            ->appNotifications()
            ->with('sender:id,firstname,lastname,avatar')
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by read status
        if ($request->has('unread')) {
            $query->unread();
        }

        $notifications = $query->paginate(20);

        return response()->json($notifications);
    }

    /**
     * Get unread notifications count and recent items
     */
    public function unread(): JsonResponse
    {
        $user = auth()->user();
        
        $unreadCount = $user->appNotifications()->unread()->count();
        
        $recentUnread = $user->appNotifications()
            ->unread()
            ->with('sender:id,firstname,lastname,avatar')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'action_url' => $notification->action_url,
                    'icon' => $notification->getIcon(),
                    'color' => $notification->getColor(),
                    'created_at' => $notification->created_at->diffForHumans(),
                    'sender' => $notification->sender,
                ];
            });

        return response()->json([
            'count' => $unreadCount,
            'notifications' => $recentUnread,
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(AppNotification $notification): JsonResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->recipient_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()
            ->appNotifications()
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(AppNotification $notification): JsonResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->recipient_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Get notification statistics
     */
    public function statistics(): JsonResponse
    {
        $user = auth()->user();
        
        $stats = [
            'total' => $user->appNotifications()->count(),
            'unread' => $user->appNotifications()->unread()->count(),
            'by_type' => $user->appNotifications()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_priority' => $user->appNotifications()
                ->unread()
                ->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
        ];

        return response()->json($stats);
    }
}