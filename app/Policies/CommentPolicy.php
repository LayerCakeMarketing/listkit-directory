<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Comment $comment): bool
    {
        // If comment is soft deleted, only owner and admins can view
        if ($comment->trashed()) {
            return $user->id === $comment->user_id || in_array($user->role, ['admin', 'manager']);
        }
        
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Comment $comment): bool
    {
        // Only the comment owner can update
        return $user->id === $comment->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Comment $comment): bool
    {
        // Comment owner can delete their own comments
        if ($user->id === $comment->user_id) {
            return true;
        }

        // Admins and managers can delete any comment
        if (in_array($user->role, ['admin', 'manager'])) {
            return true;
        }

        // Owner of the parent content can delete comments on their content
        $commentable = $comment->commentable;
        if ($commentable) {
            // For posts
            if ($commentable instanceof \App\Models\Post) {
                return $commentable->user_id === $user->id;
            }
            
            // For lists
            if ($commentable instanceof \App\Models\UserList) {
                return $commentable->isOwnedBy($user);
            }
            
            // For places
            if ($commentable instanceof \App\Models\Place) {
                return $commentable->canManage($user);
            }
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Comment $comment): bool
    {
        return in_array($user->role, ['admin', 'manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Comment $comment): bool
    {
        return in_array($user->role, ['admin']);
    }
}