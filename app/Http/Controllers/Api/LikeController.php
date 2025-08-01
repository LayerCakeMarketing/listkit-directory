<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Models\UserList;
use App\Models\Place;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    /**
     * Get likes for a specific model.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:post,list,place,comment',
            'id' => 'required|integer',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $likes = $model->likes()
            ->with('user:id,firstname,lastname,username,avatar_cloudflare_id')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($likes);
    }

    /**
     * Toggle like status.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:post,list,place,comment',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $user = auth()->user();
        $wasLiked = $model->isLikedBy($user);
        $model->toggleLike($user);

        return response()->json([
            'liked' => !$wasLiked,
            'likes_count' => $model->fresh()->likes_count,
            'message' => !$wasLiked ? 'Liked successfully' : 'Unliked successfully',
        ]);
    }

    /**
     * Check if current user has liked a resource.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:post,list,place,comment',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        return response()->json([
            'liked' => $model->isLikedBy(auth()->user()),
            'likes_count' => $model->likes_count,
        ]);
    }

    /**
     * Get user's liked items.
     */
    public function userLikes(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'in:post,list,place,comment',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $query = auth()->user()->likes();

        if ($request->has('type')) {
            $modelClass = $this->getModelClass($request->type);
            $query->where('likeable_type', $modelClass);
        }

        $likes = $query->with('likeable')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($likes);
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
            'comment' => Comment::class,
        };
    }
}