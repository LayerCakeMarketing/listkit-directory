<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\VerificationCode;
use App\Notifications\ClaimVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class VerificationController extends Controller
{
    /**
     * Verify a code for a claim
     */
    public function verifyCode(Request $request, Claim $claim)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        // Get the latest valid verification code
        $verificationCode = $claim->verificationCodes()
            ->valid()
            ->where('type', $claim->verification_method)
            ->latest()
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'message' => 'No valid verification code found. Please request a new one.'
            ], 422);
        }

        // Debug logging in test mode
        if (config('services.claiming.test_mode')) {
            \Log::info('Verification attempt', [
                'submitted_code' => $validated['code'],
                'stored_code' => $verificationCode->code,
                'verified_at' => $verificationCode->verified_at,
                'expires_at' => $verificationCode->expires_at,
                'attempts' => $verificationCode->attempts,
            ]);
        }

        if ($verificationCode->verify($validated['code'])) {
            // Mark claim as verified
            $claim->update(['verified_at' => now()]);

            return response()->json([
                'message' => 'Verification successful!',
                'claim' => $claim->fresh()->load('place'),
            ]);
        }

        return response()->json([
            'message' => 'Invalid verification code. Please try again.',
            'attempts_remaining' => max(0, 5 - $verificationCode->attempts),
            'debug' => config('services.claiming.test_mode') ? [
                'expected' => $verificationCode->code,
                'received' => $validated['code'],
                'attempts' => $verificationCode->attempts,
                'expired' => $verificationCode->expires_at->isPast(),
            ] : null,
        ], 422);
    }

    /**
     * Resend verification code
     */
    public function resendCode(Claim $claim)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        if (!in_array($claim->verification_method, ['email', 'phone'])) {
            return response()->json([
                'message' => 'Verification codes are only available for email and phone verification.'
            ], 422);
        }

        if ($claim->verified_at) {
            return response()->json([
                'message' => 'This claim has already been verified.'
            ], 422);
        }

        // Check rate limiting - max 3 codes per hour
        $recentCodes = $claim->verificationCodes()
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($recentCodes >= 3) {
            return response()->json([
                'message' => 'Too many verification codes requested. Please try again later.'
            ], 429);
        }

        // Generate new code
        $destination = $claim->verification_method === 'email' 
            ? $claim->business_email 
            : $claim->business_phone;
        
        $verificationCode = VerificationCode::generate($claim, $claim->verification_method, $destination);

        // Send the code
        if ($claim->verification_method === 'email') {
            Notification::route('mail', $claim->business_email)
                ->notify(new ClaimVerificationCode($verificationCode));
        } else {
            // TODO: Implement SMS sending via Twilio
            // For now, return code in development
            if (app()->environment('local', 'testing')) {
                return response()->json([
                    'message' => 'Verification code sent successfully.',
                    'code' => $verificationCode->code, // Remove in production
                ]);
            }
        }

        return response()->json([
            'message' => 'Verification code sent successfully.',
        ]);
    }
}