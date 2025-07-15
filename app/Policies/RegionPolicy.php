<?php

namespace App\Policies;

use App\Models\Region;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RegionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view regions
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Region $region): bool
    {
        return true; // All authenticated users can view regions
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Region $region): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Region $region): bool
    {
        // Only admins can delete regions
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Region $region): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Region $region): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can manage regions (admin panel access).
     */
    public function manageRegions(User $user): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can manage featured entries.
     */
    public function manageFeaturedEntries(User $user, Region $region): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can upload cover images.
     */
    public function uploadCoverImage(User $user, Region $region): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }
}