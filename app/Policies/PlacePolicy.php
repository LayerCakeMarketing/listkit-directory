<?php

namespace App\Policies;

use App\Models\Place;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PlacePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view places
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Place $place): bool
    {
        // Published places are public
        if ($place->status === 'published') {
            return true;
        }

        // Non-published places require authentication
        if (!$user) {
            return false;
        }

        // Admins and managers can view any place
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        // Users can view their own places
        return $place->canBeManaged($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // All authenticated users can create places
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Place $place): bool
    {
        // Use the existing canBeEdited method
        return $place->canBeEdited();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Place $place): bool
    {
        // Admins and managers can delete any place
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        // Users can delete their own unpublished places
        if ($place->status !== 'published' && $place->canBeManaged($user)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Place $place): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Place $place): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can approve the place.
     */
    public function approve(User $user, Place $place): bool
    {
        // Only admins and managers can approve places
        if (!in_array($user->role, ['admin', 'manager'])) {
            return false;
        }

        // Can only approve pending places
        return $place->status === 'pending_review';
    }

    /**
     * Determine whether the user can reject the place.
     */
    public function reject(User $user, Place $place): bool
    {
        // Only admins and managers can reject places
        if (!in_array($user->role, ['admin', 'manager'])) {
            return false;
        }

        // Can only reject pending places
        return $place->status === 'pending_review';
    }

    /**
     * Determine whether the user can publish the place.
     */
    public function publish(User $user, Place $place): bool
    {
        // Check if user can publish content
        return $user->canPublishContent();
    }

    /**
     * Determine whether the user can manage places (admin actions).
     */
    public function manage(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can bulk update places.
     */
    public function bulkUpdate(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }
}