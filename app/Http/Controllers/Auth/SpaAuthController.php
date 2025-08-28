<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Verified;

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

        // Send email verification notification
        $user->sendEmailVerificationNotification();

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

        // Send the password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link has been sent to your email.'
            ]);
        }

        // Even if the email doesn't exist, return success to prevent email enumeration
        return response()->json([
            'message' => 'If an account exists for this email, a password reset link has been sent.'
        ]);
    }
    
    /**
     * Reset the password.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Your password has been reset successfully.'
            ]);
        }

        return response()->json([
            'message' => 'Invalid or expired reset token.'
        ], 422);
    }
    
    /**
     * Resend email verification notification.
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.'
            ]);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent.'
        ]);
    }

    /**
     * Verify email address.
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Invalid verification link.'
            ], 422);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified.'
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verified successfully.'
        ]);
    }
}