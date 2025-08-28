<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\UserList;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    /**
     * Get comments for a specific model.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:post,list,place',
            'id' => 'required|integer',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $comments = $model->rootComments()
            ->with([
                'user', // Load all user fields to get accessors
                'replyToUser:id,firstname,lastname,username', // Load who they're replying to
                'replies' => function ($query) {
                    $query->with([
                        'user', // Load all user fields to get accessors
                        'replyToUser:id,firstname,lastname,username'
                    ])
                    ->withCount('likes')
                    ->latest()
                    ->limit(3); // Load first 3 replies
                }
            ])
            ->withCount(['likes', 'replies'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        // Add current user's like status
        if (auth()->check()) {
            $comments->through(function ($comment) {
                $comment->is_liked = $comment->isLikedBy(auth()->user());
                if ($comment->replies) {
                    $comment->replies->each(function ($reply) {
                        $reply->is_liked = $reply->isLikedBy(auth()->user());
                    });
                }
                return $comment;
            });
        }

        return response()->json($comments);
    }

    /**
     * Store a new comment.
     */
    public function store(StoreCommentRequest $request): JsonResponse
    {
        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        // Calculate depth and get parent info
        $depth = Comment::calculateDepth($request->parent_id);
        $replyToUserId = null;

        if ($request->parent_id) {
            $parent = Comment::find($request->parent_id);
            if ($parent) {
                $replyToUserId = $parent->user_id;
            }
        }

        // Create the comment with depth and reply_to_user_id
        $comment = new Comment([
            'user_id' => auth()->id(),
            'commentable_type' => get_class($model),
            'commentable_id' => $model->id,
            'content' => $request->content,
            'parent_id' => $request->parent_id,
            'depth' => $depth,
            'reply_to_user_id' => $replyToUserId,
            'mentions' => $this->extractMentions($request->content),
        ]);

        $comment->save();

        $comment->load([
            'user', // Load all user fields to get accessors
            'replyToUser:id,firstname,lastname,username'
        ])->loadCount(['likes', 'replies']);

        $comment->is_liked = false; // New comment is not liked

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment,
        ], 201);
    }

    /**
     * Update a comment.
     */
    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->authorize('update', $comment);

        $comment->update([
            'content' => $request->content,
            'mentions' => $this->extractMentions($request->content),
        ]);

        $comment->load([
            'user:id,firstname,lastname,username,avatar_cloudflare_id'
        ])->loadCount(['likes', 'replies']);

        if (auth()->check()) {
            $comment->is_liked = $comment->isLikedBy(auth()->user());
        }

        return response()->json([
            'message' => 'Comment updated successfully',
            'comment' => $comment,
        ]);
    }

    /**
     * Delete a comment.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        $commentable = $comment->commentable;
        $comment->delete();

        // Update the comment count
        if ($commentable) {
            $commentable->decrement('comments_count');
        }

        return response()->json([
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Get replies for a comment.
     */
    public function replies(Comment $comment, Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'integer|min:1|max:100',
        ]);

        $replies = $comment->replies()
            ->with([
                'user', // Load all user fields to get accessors
                'replyToUser' // Load all fields for reply-to user as well
            ])
            ->withCount('likes')
            ->paginate($request->get('per_page', 20));

        // Add current user's like status
        if (auth()->check()) {
            $replies->through(function ($reply) {
                $reply->is_liked = $reply->isLikedBy(auth()->user());
                return $reply;
            });
        }

        return response()->json($replies);
    }

    /**
     * Get model instance by type and ID.
     */
    private function getModel(string $type, int $id)
    {
        $modelClass = $this->getModelClass($type);
        return $modelClass::find($id);
    }

    /**
     * Get model class by type string.
     */
    private function getModelClass(string $type): string
    {
        return match($type) {
            'post' => Post::class,
            'list' => UserList::class,
            'place' => Place::class,
        };
    }

    /**
     * Extract @mentions from content.
     */
    private function extractMentions(string $content): ?array
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        return $matches[1] ?? null;
    }
}