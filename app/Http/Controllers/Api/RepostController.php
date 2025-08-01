<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repost;
use App\Models\Post;
use App\Models\UserList;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RepostController extends Controller
{
    /**
     * Get reposts for a specific model.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:post,list',
            'id' => 'required|integer',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $reposts = $model->reposts()
            ->with('user:id,firstname,lastname,username,avatar_cloudflare_id')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($reposts);
    }

    /**
     * Create a repost.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:post,list',
            'id' => 'required|integer',
            'comment' => 'nullable|string|max:500',
        ]);

        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        // Check if already reposted
        if ($model->isRepostedBy(auth()->user())) {
            return response()->json([
                'message' => 'You have already reposted this item',
            ], 422);
        }

        $repost = $model->repost(auth()->user(), $request->comment);

        $repost->load('user:id,firstname,lastname,username,avatar_cloudflare_id');

        return response()->json([
            'message' => 'Reposted successfully',
            'repost' => $repost,
            'reposts_count' => $model->fresh()->reposts_count,
        ], 201);
    }

    /**
     * Remove a repost.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:post,list',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        if (!$model->isRepostedBy(auth()->user())) {
            return response()->json([
                'message' => 'You have not reposted this item',
            ], 422);
        }

        $model->unrepost(auth()->user());

        return response()->json([
            'message' => 'Repost removed successfully',
            'reposts_count' => $model->fresh()->reposts_count,
        ]);
    }

    /**
     * Check if current user has reposted a resource.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:post,list',
            'id' => 'required|integer',
        ]);

        $model = $this->getModel($request->type, $request->id);
        
        if (!$model) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $repost = null;
        if (auth()->check()) {
            $repost = $model->reposts()
                ->where('user_id', auth()->id())
                ->first();
        }

        return response()->json([
            'reposted' => $repost !== null,
            'repost' => $repost,
            'reposts_count' => $model->reposts_count,
        ]);
    }

    /**
     * Get user's reposts.
     */
    public function userReposts(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'in:post,list',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $query = auth()->user()->reposts();

        if ($request->has('type')) {
            $modelClass = $this->getModelClass($request->type);
            $query->where('repostable_type', $modelClass);
        }

        $reposts = $query->with(['repostable', 'repostable.user'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($reposts);
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
        };
    }
}