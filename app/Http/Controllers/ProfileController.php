<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\CloudflareImageService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    private CloudflareImageService $imageService;

    public function __construct(CloudflareImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        
        // Get current images
        $avatarUrl = null;
        $coverUrl = null;
        $pageLogoUrl = null;
        
        if ($user->avatar_cloudflare_id) {
            $avatarUrl = $this->imageService->getImageUrl($user->avatar_cloudflare_id);
        }
        
        if ($user->cover_cloudflare_id) {
            $coverUrl = $this->imageService->getImageUrl($user->cover_cloudflare_id);
        }
        
        if ($user->page_logo_cloudflare_id) {
            $pageLogoUrl = $this->imageService->getImageUrl($user->page_logo_cloudflare_id);
        }
        
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'auth' => [
                'user' => array_merge($user->toArray(), [
                    'avatar_url' => $avatarUrl,
                    'cover_url' => $coverUrl,
                    'page_logo_url' => $pageLogoUrl,
                ])
            ]
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        
        // Handle basic profile fields
        $user->fill($request->only(['name', 'email', 'custom_url']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // Handle avatar image upload
        if (isset($validated['avatar_image']) && isset($validated['avatar_image']['cloudflare_id'])) {
            $user->avatar_cloudflare_id = $validated['avatar_image']['cloudflare_id'];
        }

        // Handle cover image upload
        if (isset($validated['cover_image']) && isset($validated['cover_image']['cloudflare_id'])) {
            $user->cover_cloudflare_id = $validated['cover_image']['cloudflare_id'];
        }

        // Handle page logo upload
        if (isset($validated['page_logo_image']) && isset($validated['page_logo_image']['cloudflare_id'])) {
            $user->page_logo_cloudflare_id = $validated['page_logo_image']['cloudflare_id'];
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'Profile updated successfully!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
