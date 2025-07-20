<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'notifiable_type',
        'notifiable_id',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The user who receives the notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the notifiable model (User, Channel, etc.)
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }

    /**
     * Mark the notification as unread
     */
    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null
        ]);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Create a follow notification
     */
    public static function createFollowNotification(User $follower, $followedEntity)
    {
        $data = [
            'follower_id' => $follower->id,
            'follower_name' => $follower->name,
            'follower_username' => $follower->username,
            'follower_custom_url' => $follower->custom_url,
            'follower_avatar' => $follower->avatar_url
        ];

        if ($followedEntity instanceof User) {
            // User following another user
            return self::create([
                'user_id' => $followedEntity->id,
                'type' => 'user_follow',
                'title' => 'New Follower',
                'message' => "@{$follower->username} followed you",
                'data' => $data,
                'notifiable_type' => User::class,
                'notifiable_id' => $follower->id
            ]);
        } elseif ($followedEntity instanceof Channel) {
            // User following a channel
            return self::create([
                'user_id' => $followedEntity->user_id, // Channel owner
                'type' => 'channel_follow',
                'title' => 'New Channel Follower',
                'message' => "@{$follower->username} followed your channel \"{$followedEntity->name}\"",
                'data' => array_merge($data, [
                    'channel_id' => $followedEntity->id,
                    'channel_name' => $followedEntity->name,
                    'channel_slug' => $followedEntity->slug
                ]),
                'notifiable_type' => User::class,
                'notifiable_id' => $follower->id
            ]);
        }
    }
}