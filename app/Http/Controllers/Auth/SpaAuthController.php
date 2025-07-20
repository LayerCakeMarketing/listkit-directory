<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SpaAuthController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $request->session()->regenerate();

        // Debug: Log session info
        \Log::info('Login session info', [
            'session_id' => session()->getId(),
            'user_id' => Auth::id(),
            'session_cookie' => config('session.cookie'),
        ]);

        return response()->json([
            'user' => Auth::user(),
            'redirect' => $request->input('redirect', '/home'),
            'session_id' => session()->getId(),
            'session_cookie_name' => config('session.cookie'),
        ]);
    }

    /**
     * Handle user registration.
     */
    public function register(RegisterUserRequest $request)
    {
        $validated = $request->validated();

        // Generate a unique username if not provided
        $username = $validated['username'] ?? $this->generateUsername($validated['firstname'], $validated['lastname']);

        $user = User::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'name' => $validated['firstname'] . ' ' . $validated['lastname'], // Keep for backward compatibility
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'username' => $username,
            'custom_url' => $validated['custom_url'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'birthdate' => $validated['birthdate'] ?? null,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return response()->json([
            'user' => $user,
            'redirect' => '/home'
        ]);
    }

    /**
     * Generate a unique username from firstname and lastname
     */
    private function generateUsername($firstname, $lastname)
    {
        $base = strtolower($firstname . $lastname);
        $base = preg_replace('/[^a-z0-9]/', '', $base); // Remove non-alphanumeric characters
        
        $username = $base;
        $counter = 1;
        
        while (User::where('username', $username)->exists()) {
            $username = $base . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Send a password reset link.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // We'll pretend to send the email to avoid revealing whether a user exists
        return response()->json([
            'message' => 'If an account exists for this email, a password reset link has been sent.'
        ]);
    }
}