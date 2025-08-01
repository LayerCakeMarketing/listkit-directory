<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChannelPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the channel.
     */
    public function view(?User $user, Channel $channel): bool
    {
        return $channel->canBeViewedBy($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create channels
    }

    /**
     * Determine whether the user can update the channel.
     */
    public function update(User $user, Channel $channel): bool
    {
        return $user->id === $channel->user_id;
    }

    /**
     * Determine whether the user can delete the channel.
     */
    public function delete(User $user, Channel $channel): bool
    {
        return $user->id === $channel->user_id;
    }

    /**
     * Determine whether the user can view the channel's chains.
     */
    public function viewChains(?User $user, Channel $channel): bool
    {
        return $channel->canBeViewedBy($user);
    }

    /**
     * Determine whether the user can create a chain for the channel.
     */
    public function createChain(User $user, Channel $channel): bool
    {
        return $user->id === $channel->user_id;
    }

    /**
     * Determine whether the user can view the channel's lists.
     */
    public function viewLists(?User $user, Channel $channel): bool
    {
        return $channel->canBeViewedBy($user);
    }

    /**
     * Determine whether the user can create a list for the channel.
     */
    public function createList(User $user, Channel $channel): bool
    {
        return $user->id === $channel->user_id;
    }
}