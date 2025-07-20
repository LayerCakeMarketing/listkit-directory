<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class FollowController extends Controller
{
    /**
     * Follow a user.
     */
    public function followUser(User $user): JsonResponse
    {
        $currentUser = auth()->user();
        
        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }
        
        $currentUser->followUser($user);
        
        return response()->json([
            'message' => 'Successfully followed user',
            'is_following' => true,
            'follower_count' => $user->follower_count
        ]);
    }
    
    /**
     * Unfollow a user.
     */
    public function unfollowUser(User $user): JsonResponse
    {
        $currentUser = auth()->user();
        
        $currentUser->unfollowUser($user);
        
        return response()->json([
            'message' => 'Successfully unfollowed user',
            'is_following' => false,
            'follower_count' => $user->follower_count
        ]);
    }
    
    /**
     * Follow a place.
     */
    public function followPlace(Place $place): JsonResponse
    {
        $currentUser = auth()->user();
        
        $currentUser->followPlace($place);
        
        return response()->json([
            'message' => 'Successfully followed place',
            'is_following' => true,
            'follower_count' => $place->follower_count
        ]);
    }
    
    /**
     * Unfollow a place.
     */
    public function unfollowPlace(Place $place): JsonResponse
    {
        $currentUser = auth()->user();
        
        $currentUser->unfollowPlace($place);
        
        return response()->json([
            'message' => 'Successfully unfollowed place',
            'is_following' => false,
            'follower_count' => $place->follower_count
        ]);
    }
    
    /**
     * Get user's following list.
     */
    public function following(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $type = $request->get('type', 'all');
        $perPage = $request->get('per_page', 20);
        
        if ($type === 'users') {
            $following = $user->followingUsers()
                ->with(['tackedPost'])
                ->paginate($perPage);
        } elseif ($type === 'places') {
            $following = $user->followingPlaces()
                ->with(['location', 'category'])
                ->paginate($perPage);
        } else {
            // Return both users and places
            $users = $user->followingUsers()
                ->with(['tackedPost'])
                ->limit(10)
                ->get();
                
            $places = $user->followingPlaces()
                ->with(['location', 'category'])
                ->limit(10)
                ->get();
                
            return response()->json([
                'users' => $users,
                'places' => $places
            ]);
        }
        
        return response()->json($following);
    }
    
    /**
     * Get user's followers.
     */
    public function followers(Request $request, User $user): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        
        $followers = $user->followers()
            ->with('follower')
            ->paginate($perPage);
        
        return response()->json($followers);
    }
    
    /**
     * Check if following.
     */
    public function checkFollowing(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $type = $request->get('type');
        $id = $request->get('id');
        
        if ($type === 'user') {
            $followable = User::find($id);
            if (!$followable) {
                return response()->json(['is_following' => false]);
            }
            return response()->json([
                'is_following' => $user->isFollowing($followable)
            ]);
        } elseif ($type === 'place') {
            $followable = Place::find($id);
            if (!$followable) {
                return response()->json(['is_following' => false]);
            }
            return response()->json([
                'is_following' => $user->isFollowing($followable)
            ]);
        } elseif ($type === 'channel') {
            // For channels, check the channel_followers table
            $channel = \App\Models\Channel::where('slug', $id)->first();
            if (!$channel) {
                return response()->json(['is_following' => false]);
            }
            return response()->json([
                'is_following' => $channel->followers()->where('user_id', $user->id)->exists()
            ]);
        } else {
            return response()->json(['is_following' => false]);
        }
    }
}