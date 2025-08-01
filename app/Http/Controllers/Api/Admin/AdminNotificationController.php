<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\User;
use App\Notifications\System\SystemMessage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    /**
     * Get user filters for sending notifications
     */
    public function getFilters(): JsonResponse
    {
        return response()->json([
            'user_types' => [
                ['value' => 'all', 'label' => 'All Users'],
                ['value' => 'regular', 'label' => 'Regular Users'],
                ['value' => 'business_owner', 'label' => 'Business Owners'],
                ['value' => 'channel_owner', 'label' => 'Channel Owners'],
                ['value' => 'has_claim', 'label' => 'Users with Claims'],
                ['value' => 'has_lists', 'label' => 'Users with Lists'],
                ['value' => 'active_30days', 'label' => 'Active in last 30 days'],
            ],
            'claim_statuses' => [
                ['value' => 'pending', 'label' => 'Pending Claims'],
                ['value' => 'approved', 'label' => 'Approved Claims'],
                ['value' => 'rejected', 'label' => 'Rejected Claims'],
            ],
            'tags' => \App\Models\Tag::select('id', 'name')->get()->map(function ($tag) {
                return ['value' => $tag->id, 'label' => $tag->name];
            }),
        ]);
    }

    /**
     * Send notification to users
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'action_url' => 'nullable|string|max:255',
            'priority' => 'required|in:low,normal,high',
            'user_type' => 'required|string',
            'filters' => 'nullable|array',
            'send_email' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Build user query based on filters
            $query = User::query();
            
            // Apply user type filter
            switch ($validated['user_type']) {
                case 'business_owner':
                    $query->whereHas('ownedPlaces');
                    break;
                case 'channel_owner':
                    $query->whereHas('channels');
                    break;
                case 'has_claim':
                    $query->whereHas('claims');
                    break;
                case 'has_lists':
                    $query->whereHas('lists');
                    break;
                case 'active_30days':
                    $query->where('last_active_at', '>=', now()->subDays(30));
                    break;
                case 'regular':
                    $query->whereDoesntHave('ownedPlaces')
                          ->whereDoesntHave('channels');
                    break;
            }

            // Apply additional filters
            if (!empty($validated['filters'])) {
                if (isset($validated['filters']['claim_status'])) {
                    $query->whereHas('claims', function ($q) use ($validated) {
                        $q->where('status', $validated['filters']['claim_status']);
                    });
                }
                
                if (isset($validated['filters']['tags']) && !empty($validated['filters']['tags'])) {
                    $query->whereHas('tags', function ($q) use ($validated) {
                        $q->whereIn('tag_id', $validated['filters']['tags']);
                    });
                }
                
                if (isset($validated['filters']['min_followers'])) {
                    $query->has('followers', '>=', $validated['filters']['min_followers']);
                }
            }

            $users = $query->get();
            $notificationCount = 0;

            // Create notifications for each user
            foreach ($users as $user) {
                // Create in-app notification
                AppNotification::createSystemNotification(
                    $user->id,
                    $validated['title'],
                    $validated['message'],
                    $validated['action_url'] ?? null,
                    $validated['priority']
                );

                // Send email notification if requested
                if ($validated['send_email'] ?? false) {
                    $user->notify(new SystemMessage(
                        $validated['title'],
                        $validated['message'],
                        $validated['action_url'] ?? null,
                        $validated['priority']
                    ));
                }

                $notificationCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Notification sent successfully to {$notificationCount} users.",
                'count' => $notificationCount,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_sent' => AppNotification::count(),
            'total_sent_today' => AppNotification::whereDate('created_at', today())->count(),
            'total_unread' => AppNotification::unread()->count(),
            'by_type' => AppNotification::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'by_priority' => AppNotification::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'recent_announcements' => AppNotification::where('type', AppNotification::TYPE_ANNOUNCEMENT)
                ->with('sender:id,firstname,lastname')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'title', 'created_at', 'sender_id']),
        ];

        return response()->json($stats);
    }

    /**
     * Preview notification recipients
     */
    public function previewRecipients(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_type' => 'required|string',
            'filters' => 'nullable|array',
        ]);

        // Build the same query as send method
        $query = User::query();
        
        // Apply user type filter (same logic as send method)
        switch ($validated['user_type']) {
            case 'business_owner':
                $query->whereHas('ownedPlaces');
                break;
            case 'channel_owner':
                $query->whereHas('channels');
                break;
            case 'has_claim':
                $query->whereHas('claims');
                break;
            case 'has_lists':
                $query->whereHas('lists');
                break;
            case 'active_30days':
                $query->where('last_active_at', '>=', now()->subDays(30));
                break;
            case 'regular':
                $query->whereDoesntHave('ownedPlaces')
                      ->whereDoesntHave('channels');
                break;
        }

        // Apply additional filters (same as send method)
        if (!empty($validated['filters'])) {
            if (isset($validated['filters']['claim_status'])) {
                $query->whereHas('claims', function ($q) use ($validated) {
                    $q->where('status', $validated['filters']['claim_status']);
                });
            }
            
            if (isset($validated['filters']['tags']) && !empty($validated['filters']['tags'])) {
                $query->whereHas('tags', function ($q) use ($validated) {
                    $q->whereIn('tag_id', $validated['filters']['tags']);
                });
            }
            
            if (isset($validated['filters']['min_followers'])) {
                $query->has('followers', '>=', $validated['filters']['min_followers']);
            }
        }

        $count = $query->count();
        $sample = $query->limit(5)->get(['id', 'firstname', 'lastname', 'email']);

        return response()->json([
            'total_recipients' => $count,
            'sample_users' => $sample,
        ]);
    }
}