<?php

namespace App\Models;

use App\Traits\Likeable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes, Likeable;

    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'content',
        'parent_id',
        'depth',
        'reply_to_user_id',
        'mentions',
    ];

    protected $casts = [
        'mentions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Removed $withCount to prevent circular reference
    // Counts should be loaded explicitly when needed

    /**
     * Get the owning commentable model (post, list, place).
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that created the comment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment if this is a reply.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->latest();
    }

    /**
     * Scope to get root comments (no parent).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get replies only.
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Check if comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Get mentioned users.
     */
    public function getMentionedUsers()
    {
        if (empty($this->mentions)) {
            return collect();
        }

        return User::whereIn('username', $this->mentions)->get();
    }

    /**
     * Get the user this comment is replying to.
     */
    public function replyToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reply_to_user_id');
    }

    /**
     * Calculate depth for a new reply.
     */
    public static function calculateDepth($parentId): int
    {
        if (!$parentId) {
            return 0; // Root comment
        }

        $parent = self::find($parentId);
        if (!$parent) {
            return 0;
        }

        // Only allow one level of visual indentation
        // depth 0 = root, depth 1 = direct reply (indented), depth 2+ = nested reply (not indented)
        return min($parent->depth + 1, 2);
    }

    /**
     * Scope to get comments for display (ordered by thread).
     */
    public function scopeThreaded($query)
    {
        return $query->orderBy('created_at', 'desc')
                    ->orderBy('depth', 'asc');
    }
}
