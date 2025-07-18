<?php

namespace App\Traits;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Followable
{
    /**
     * Get all followers for this model.
     */
    public function followers(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    /**
     * Check if a user is following this model.
     */
    public function isFollowedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->followers()
            ->where('follower_id', $user->id)
            ->exists();
    }

    /**
     * Get the follower count.
     */
    public function getFollowerCountAttribute(): int
    {
        return $this->followers()->count();
    }

    /**
     * Follow this model.
     */
    public function follow(User $user): void
    {
        if (!$this->isFollowedBy($user)) {
            $this->followers()->create([
                'follower_id' => $user->id,
            ]);
        }
    }

    /**
     * Unfollow this model.
     */
    public function unfollow(User $user): void
    {
        $this->followers()
            ->where('follower_id', $user->id)
            ->delete();
    }
}