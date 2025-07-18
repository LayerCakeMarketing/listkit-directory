<?php

namespace App\Traits;

use App\Models\SavedItem;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Saveable
{
    /**
     * Get all saved items for this model.
     */
    public function savedItems(): MorphMany
    {
        return $this->morphMany(SavedItem::class, 'saveable');
    }

    /**
     * Check if the model is saved by a specific user.
     */
    public function isSavedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->savedItems()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Save this model for a specific user.
     */
    public function saveFor($user, $notes = null): SavedItem
    {
        return $this->savedItems()->firstOrCreate([
            'user_id' => $user->id,
        ], [
            'notes' => $notes,
        ]);
    }

    /**
     * Unsave this model for a specific user.
     */
    public function unsaveFor($user): bool
    {
        return $this->savedItems()
            ->where('user_id', $user->id)
            ->delete() > 0;
    }

    /**
     * Get the save count for this model.
     */
    public function getSaveCountAttribute(): int
    {
        return $this->savedItems()->count();
    }
}