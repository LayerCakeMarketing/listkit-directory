<?php

namespace App\Traits;

use App\Models\Repost;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Repostable
{
    /**
     * Get all reposts for the model.
     */
    public function reposts(): MorphMany
    {
        return $this->morphMany(Repost::class, 'repostable');
    }

    /**
     * Check if the model is reposted by a specific user.
     */
    public function isRepostedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->reposts()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Repost the model by a user.
     */
    public function repost(User $user, ?string $comment = null): ?Repost
    {
        if ($this->isRepostedBy($user)) {
            return null;
        }

        $repost = $this->reposts()->create([
            'user_id' => $user->id,
            'comment' => $comment,
        ]);

        $this->increment('reposts_count');

        return $repost;
    }

    /**
     * Remove repost by a user.
     */
    public function unrepost(User $user): bool
    {
        $repost = $this->reposts()
            ->where('user_id', $user->id)
            ->first();

        if (!$repost) {
            return false;
        }

        $repost->delete();
        $this->decrement('reposts_count');

        return true;
    }

    /**
     * Toggle repost status.
     */
    public function toggleRepost(User $user, ?string $comment = null): bool
    {
        if ($this->isRepostedBy($user)) {
            return $this->unrepost($user);
        }

        return (bool) $this->repost($user, $comment);
    }

    /**
     * Get users who reposted this model.
     */
    public function reposters()
    {
        return $this->belongsToMany(User::class, 'reposts', 'repostable_id', 'user_id')
            ->where('repostable_type', static::class)
            ->withPivot('comment')
            ->withTimestamps();
    }

    /**
     * Scope to order by reposts count.
     */
    public function scopeMostReposted($query)
    {
        return $query->orderBy('reposts_count', 'desc');
    }

    /**
     * Boot the trait.
     */
    public static function bootRepostable()
    {
        static::deleting(function ($model) {
            $model->reposts()->delete();
        });
    }
}