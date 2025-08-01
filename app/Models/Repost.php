<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Repost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'repostable_type',
        'repostable_id',
        'comment',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the owning repostable model (post, list).
     */
    public function repostable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that created the repost.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include reposts by a specific user.
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope a query to only include reposts for a specific model type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('repostable_type', $type);
    }

    /**
     * Check if repost has a comment.
     */
    public function hasComment(): bool
    {
        return !empty($this->comment);
    }
}