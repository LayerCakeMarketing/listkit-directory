<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $type = $request->input('type'); // Filter by type if provided
        
        $query = auth()->user()->notifications()
            ->with('notifiable')
            ->latest();
            
        if ($type) {
            $query->where('type', $type);
        }
        
        $notifications = $query->paginate($perPage);
        
        return response()->json($notifications);
    }
    
    /**
     * Get unread notifications count
     */
    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->notifications()
            ->unread()
            ->count();
            
        return response()->json(['count' => $count]);
    }
    
    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $notification->markAsRead();
        
        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification
        ]);
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->notifications()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);
            
        return response()->json(['message' => 'All notifications marked as read']);
    }
    
    /**
     * Delete a notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        // Ensure the notification belongs to the authenticated user
        if ($notification->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $notification->delete();
        
        return response()->json(['message' => 'Notification deleted']);
    }
    
    /**
     * Delete all read notifications
     */
    public function clearRead(): JsonResponse
    {
        auth()->user()->notifications()
            ->read()
            ->delete();
            
        return response()->json(['message' => 'Read notifications cleared']);
    }
}