<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChannelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        // Anyone can view the list of public channels
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Channel $channel): bool
    {
        // Use the channel's built-in visibility check
        return $channel->canBeViewedBy($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create channels
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Channel $channel): bool
    {
        // Only the owner can update their channel
        return $user->id === $channel->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Channel $channel): bool
    {
        // Only the owner can delete their channel
        return $user->id === $channel->user_id;
    }

    /**
     * Determine whether the user can manage lists for the channel.
     */
    public function manageLists(User $user, Channel $channel): bool
    {
        // Only the owner can manage lists for their channel
        return $user->id === $channel->user_id;
    }

    /**
     * Determine whether the user can follow the channel.
     */
    public function follow(User $user, Channel $channel): bool
    {
        // Users cannot follow their own channels
        if ($user->id === $channel->user_id) {
            return false;
        }

        // Users can follow public channels or private channels they already follow
        return $channel->is_public || $channel->followers()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can view followers of the channel.
     */
    public function viewFollowers(?User $user, Channel $channel): bool
    {
        // Public channels: anyone can view followers
        // Private channels: only owner and followers can view
        if ($channel->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        return $user->id === $channel->user_id || 
               $channel->followers()->where('user_id', $user->id)->exists();
    }
}