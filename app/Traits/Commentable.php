<?php

namespace App\Traits;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Commentable
{
    /**
     * Get all comments for the model.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get root level comments (no parent).
     */
    public function rootComments(): MorphMany
    {
        return $this->comments()->whereNull('parent_id');
    }

    /**
     * Add a comment to the model.
     */
    public function comment(User $user, string $content, ?int $parentId = null): Comment
    {
        $comment = $this->comments()->create([
            'user_id' => $user->id,
            'content' => $content,
            'parent_id' => $parentId,
            'mentions' => $this->extractMentions($content),
        ]);

        // Increment count on parent comment if it's a reply
        if ($parentId) {
            Comment::where('id', $parentId)->increment('replies_count');
        }

        // Increment count on the commentable model
        $this->increment('comments_count');

        return $comment;
    }

    /**
     * Delete a comment.
     */
    public function deleteComment(Comment $comment): bool
    {
        if ($comment->commentable_id !== $this->id || 
            $comment->commentable_type !== static::class) {
            return false;
        }

        // If it's a reply, decrement parent's reply count
        if ($comment->parent_id) {
            Comment::where('id', $comment->parent_id)->decrement('replies_count');
        }

        $comment->delete();
        $this->decrement('comments_count');

        return true;
    }

    /**
     * Get comments ordered by creation date.
     */
    public function getCommentsAttribute()
    {
        return $this->rootComments()
            ->with(['user', 'replies.user'])
            ->withCount('likes')
            ->latest()
            ->get();
    }

    /**
     * Extract @mentions from content.
     */
    protected function extractMentions(string $content): ?array
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Boot the trait.
     */
    public static function bootCommentable()
    {
        static::deleting(function ($model) {
            $model->comments()->delete();
        });
    }
}