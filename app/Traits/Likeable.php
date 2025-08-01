<?php

namespace App\Traits;

use App\Models\Like;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Likeable
{
    /**
     * Get all likes for the model.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Check if the model is liked by a specific user.
     */
    public function isLikedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->likes()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Like the model by a user.
     */
    public function like(User $user): bool
    {
        if ($this->isLikedBy($user)) {
            return false;
        }

        $this->likes()->create([
            'user_id' => $user->id,
        ]);

        $this->increment('likes_count');

        return true;
    }

    /**
     * Unlike the model by a user.
     */
    public function unlike(User $user): bool
    {
        $like = $this->likes()
            ->where('user_id', $user->id)
            ->first();

        if (!$like) {
            return false;
        }

        $like->delete();
        $this->decrement('likes_count');

        return true;
    }

    /**
     * Toggle like status.
     */
    public function toggleLike(User $user): bool
    {
        return $this->isLikedBy($user) 
            ? $this->unlike($user) 
            : $this->like($user);
    }

    /**
     * Get users who liked this model.
     */
    public function likers()
    {
        return $this->belongsToMany(User::class, 'likes', 'likeable_id', 'user_id')
            ->where('likeable_type', static::class)
            ->withTimestamps();
    }

    /**
     * Scope to order by likes count.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('likes_count', 'desc');
    }

    /**
     * Boot the trait.
     */
    public static function bootLikeable()
    {
        static::deleting(function ($model) {
            $model->likes()->delete();
        });
    }
}