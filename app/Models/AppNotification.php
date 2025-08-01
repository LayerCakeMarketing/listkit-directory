<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    use HasFactory;

    protected $table = 'app_notifications';

    protected $fillable = [
        'type',
        'title',
        'message',
        'sender_id',
        'recipient_id',
        'related_type',
        'related_id',
        'action_url',
        'read_at',
        'priority',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Notification types
    const TYPE_SYSTEM = 'system';
    const TYPE_CLAIM = 'claim';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_FOLLOW = 'follow';
    const TYPE_LIST = 'list';
    const TYPE_CHANNEL = 'channel';

    // Priority levels
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';

    /**
     * Get the sender of the notification
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the recipient of the notification
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the related model (polymorphic)
     */
    public function related()
    {
        if ($this->related_type && $this->related_id) {
            return $this->morphTo('related', 'related_type', 'related_id');
        }
        return null;
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for notifications by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for high priority notifications
     */
    public function scopeHighPriority($query)
    {
        return $query->where('priority', self::PRIORITY_HIGH);
    }

    /**
     * Scope for recent notifications
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead()
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
        return $this;
    }

    /**
     * Mark notification as unread
     */
    public function markAsUnread()
    {
        $this->update(['read_at' => null]);
        return $this;
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if notification is unread
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Get icon for notification type
     */
    public function getIcon(): string
    {
        return match($this->type) {
            self::TYPE_SYSTEM => 'system',
            self::TYPE_CLAIM => 'building-storefront',
            self::TYPE_ANNOUNCEMENT => 'megaphone',
            self::TYPE_FOLLOW => 'user-plus',
            self::TYPE_LIST => 'list-bullet',
            self::TYPE_CHANNEL => 'tv',
            default => 'bell'
        };
    }

    /**
     * Get color for notification type
     */
    public function getColor(): string
    {
        return match($this->type) {
            self::TYPE_SYSTEM => 'blue',
            self::TYPE_CLAIM => 'indigo',
            self::TYPE_ANNOUNCEMENT => 'purple',
            self::TYPE_FOLLOW => 'green',
            self::TYPE_LIST => 'yellow',
            self::TYPE_CHANNEL => 'pink',
            default => 'gray'
        };
    }

    /**
     * Create notification for claim status update
     */
    public static function createClaimNotification($claim, $status, $reason = null)
    {
        $messages = [
            'approved' => 'Your claim for %s has been approved!',
            'rejected' => 'Your claim for %s has been rejected.',
            'expired' => 'Your claim for %s has expired.',
            'pending_review' => 'Your claim for %s is under review.',
        ];

        $title = match($status) {
            'approved' => 'Claim Approved',
            'rejected' => 'Claim Rejected',
            'expired' => 'Claim Expired',
            'pending_review' => 'Claim Under Review',
            default => 'Claim Update'
        };

        $message = sprintf($messages[$status] ?? 'Your claim for %s has been updated.', $claim->place->title);
        
        if ($status === 'rejected' && $reason) {
            $message .= " Reason: {$reason}";
        }

        return self::create([
            'type' => self::TYPE_CLAIM,
            'title' => $title,
            'message' => $message,
            'recipient_id' => $claim->user_id,
            'related_type' => Claim::class,
            'related_id' => $claim->id,
            'action_url' => "/places/{$claim->place->slug}/claim",
            'priority' => $status === 'approved' ? self::PRIORITY_HIGH : self::PRIORITY_NORMAL,
            'metadata' => [
                'claim_id' => $claim->id,
                'place_id' => $claim->place_id,
                'status' => $status,
                'reason' => $reason
            ]
        ]);
    }

    /**
     * Create system notification
     */
    public static function createSystemNotification($recipientId, $title, $message, $actionUrl = null, $priority = self::PRIORITY_NORMAL)
    {
        return self::create([
            'type' => self::TYPE_SYSTEM,
            'title' => $title,
            'message' => $message,
            'recipient_id' => $recipientId,
            'action_url' => $actionUrl,
            'priority' => $priority,
        ]);
    }

    /**
     * Create announcement notification
     */
    public static function createAnnouncement($title, $message, $recipientIds = [], $filters = [])
    {
        $notifications = [];
        
        // If no specific recipients, apply filters to get users
        if (empty($recipientIds) && !empty($filters)) {
            $query = User::query();
            
            // Apply filters
            if (isset($filters['has_channel'])) {
                $query->whereHas('channels');
            }
            if (isset($filters['has_claim'])) {
                $query->whereHas('claims');
            }
            if (isset($filters['is_business_owner'])) {
                $query->whereHas('ownedPlaces');
            }
            if (isset($filters['tags'])) {
                // Assuming users have tags relationship
                $query->whereHas('tags', function($q) use ($filters) {
                    $q->whereIn('name', $filters['tags']);
                });
            }
            
            $recipientIds = $query->pluck('id')->toArray();
        }

        // Create notifications for all recipients
        foreach ($recipientIds as $recipientId) {
            $notifications[] = [
                'type' => self::TYPE_ANNOUNCEMENT,
                'title' => $title,
                'message' => $message,
                'recipient_id' => $recipientId,
                'priority' => self::PRIORITY_HIGH,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert for performance
        if (!empty($notifications)) {
            self::insert($notifications);
        }

        return count($notifications);
    }
}