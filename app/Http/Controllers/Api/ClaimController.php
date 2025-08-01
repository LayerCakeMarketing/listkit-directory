<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\Place;
use App\Models\VerificationCode;
use App\Models\ClaimDocument;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use App\Notifications\ClaimVerificationCode;

class ClaimController extends Controller
{
    /**
     * Check if a place can be claimed by the current user
     */
    public function checkClaimability(Place $place)
    {
        $user = auth()->user();
        
        return response()->json([
            'can_claim' => $place->canBeClaimedBy($user),
            'is_claimed' => $place->is_claimed,
            'has_pending_claim' => $place->hasActiveClaim(),
            'user_has_pending_claim' => $place->claims()
                ->where('user_id', $user->id)
                ->pending()
                ->exists(),
        ]);
    }

    /**
     * Initiate a claim for a place
     */
    public function initiateClaim(Request $request, Place $place, StripeService $stripeService)
    {
        $user = auth()->user();
        
        if (!$place->canBeClaimedBy($user)) {
            return response()->json([
                'message' => 'This place cannot be claimed at this time.'
            ], 422);
        }

        $validated = $request->validate([
            'tier' => ['required', Rule::in([Claim::TIER_FREE, Claim::TIER_1, Claim::TIER_2])],
            'verification_method' => ['required', Rule::in(['email', 'phone', 'document'])],
            'business_email' => 'required_if:verification_method,email|email',
            'business_phone' => 'required_if:verification_method,phone|string',
            'verification_notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $claim = Claim::create([
                'place_id' => $place->id,
                'user_id' => $user->id,
                'status' => Claim::STATUS_PENDING,
                'tier' => $validated['tier'],
                'verification_method' => $validated['verification_method'],
                'business_email' => $validated['business_email'] ?? null,
                'business_phone' => $validated['business_phone'] ?? null,
                'verification_notes' => $validated['verification_notes'] ?? null,
                'expires_at' => now()->addDays(7),
            ]);

            // Generate verification code if using email or phone
            if (in_array($validated['verification_method'], ['email', 'phone'])) {
                $destination = $validated['verification_method'] === 'email' 
                    ? $validated['business_email'] 
                    : $validated['business_phone'];
                
                $verificationCode = VerificationCode::generate($claim, $validated['verification_method'], $destination);
                
                // Send verification code
                if ($validated['verification_method'] === 'email') {
                    if (!config('services.claiming.test_mode')) {
                        // Send real email
                        Notification::route('mail', $claim->business_email)
                            ->notify(new ClaimVerificationCode($verificationCode));
                    }
                } else {
                    // Phone verification
                    if (!config('services.claiming.test_mode')) {
                        // TODO: Implement SMS sending via Twilio
                    }
                }
                
                // Return code in development/test mode
                if (app()->environment('local', 'testing') || config('services.claiming.test_mode')) {
                    $claim->verification_code = $verificationCode->code;
                }
            }

            // Create payment intent for paid tiers
            $paymentIntent = null;
            $clientSecret = null;
            
            if ($validated['tier'] !== Claim::TIER_FREE) {
                $paymentIntent = $stripeService->createPaymentIntent($claim, $validated['tier']);
                $clientSecret = $paymentIntent ? $paymentIntent->client_secret : null;
            }

            DB::commit();

            $response = [
                'claim' => $claim->load('place', 'user'),
                'message' => 'Claim initiated successfully.',
                'next_step' => $this->getNextStep($claim),
            ];

            // Include payment details for paid tiers
            if ($clientSecret) {
                $response['payment'] = [
                    'required' => true,
                    'client_secret' => $clientSecret,
                    'amount' => Claim::TIER_PRICES[$validated['tier']] / 100, // Convert cents to dollars
                    'tier_name' => Claim::TIER_NAMES[$validated['tier']],
                ];
            } else {
                $response['payment'] = [
                    'required' => false,
                ];
            }

            return response()->json($response);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get the status of a claim
     */
    public function getClaimStatus(Claim $claim)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim or is admin
        if ($claim->user_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        return response()->json([
            'claim' => $claim->load(['place', 'documents', 'verificationCodes']),
            'next_step' => $this->getNextStep($claim),
        ]);
    }

    /**
     * Upload documents for a claim
     */
    public function uploadDocument(Request $request, Claim $claim)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        if (!$claim->isPending()) {
            return response()->json([
                'message' => 'Cannot upload documents for a claim that is not pending.'
            ], 422);
        }

        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
            'document_type' => ['required', Rule::in([
                'business_license',
                'tax_document',
                'utility_bill',
                'incorporation',
                'other'
            ])],
        ]);

        DB::beginTransaction();
        try {
            $file = $request->file('document');
            $path = $file->store('claim-documents/' . $claim->id, 'private');

            $document = ClaimDocument::create([
                'claim_id' => $claim->id,
                'document_type' => $validated['document_type'],
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'status' => ClaimDocument::STATUS_PENDING,
            ]);

            // Update claim if this is their first document upload
            if ($claim->verification_method === 'document' && !$claim->verified_at) {
                $claim->update(['verified_at' => now()]);
            }

            DB::commit();

            return response()->json([
                'document' => $document,
                'message' => 'Document uploaded successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get user's claims
     */
    public function getUserClaims()
    {
        $user = auth()->user();
        
        $claims = Claim::with(['place', 'documents'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($claims);
    }

    /**
     * Cancel a pending claim
     */
    public function cancelClaim(Claim $claim)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        if (!$claim->isPending()) {
            return response()->json([
                'message' => 'Only pending claims can be cancelled.'
            ], 422);
        }

        $claim->update([
            'status' => Claim::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejection_reason' => 'Cancelled by user',
        ]);

        return response()->json([
            'message' => 'Claim cancelled successfully.',
        ]);
    }

    /**
     * Debug endpoint to check verification code (test mode only)
     */
    public function debugCode(Claim $claim)
    {
        // Only allow in test mode
        if (!config('services.claiming.test_mode')) {
            abort(403, 'Test mode is not enabled');
        }

        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        $verificationCode = $claim->verificationCodes()
            ->latest()
            ->first();

        return response()->json([
            'claim_id' => $claim->id,
            'verification_code' => $verificationCode ? [
                'code' => $verificationCode->code,
                'type' => $verificationCode->type,
                'attempts' => $verificationCode->attempts,
                'expires_at' => $verificationCode->expires_at,
                'is_expired' => $verificationCode->expires_at->isPast(),
                'verified_at' => $verificationCode->verified_at,
            ] : null,
            'test_mode' => config('services.claiming.test_mode'),
            'expected_code' => config('services.claiming.test_otp'),
        ]);
    }

    /**
     * Test endpoint to quickly approve a claim (development only)
     */
    public function testApprove(Claim $claim)
    {
        // Only allow in test mode
        if (!config('services.claiming.test_mode')) {
            abort(403, 'Test mode is not enabled');
        }

        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        if (!$claim->isPending()) {
            return response()->json([
                'message' => 'Claim is not pending'
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Mark as verified if not already
            if (!$claim->verified_at) {
                $claim->update(['verified_at' => now()]);
            }

            // Auto-approve the claim
            $claim->approve($user);

            DB::commit();

            return response()->json([
                'message' => 'Claim approved successfully (test mode)',
                'claim' => $claim->fresh()->load('place'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update tier selection for a claim
     */
    public function updateTier(Request $request, Claim $claim)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'tier' => ['required', Rule::in([Claim::TIER_FREE, Claim::TIER_1, Claim::TIER_2])],
        ]);

        // Update the tier
        $claim->update([
            'tier' => $validated['tier']
        ]);

        return response()->json([
            'message' => 'Tier updated successfully.',
            'claim' => $claim->fresh()->load('place'),
        ]);
    }

    /**
     * Create payment intent for a claim
     */
    public function createPaymentIntent(Request $request, Claim $claim, StripeService $stripeService)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        // Validate tier if provided, otherwise use claim's current tier
        $tier = $request->input('tier', $claim->tier);
        
        if (!in_array($tier, [Claim::TIER_1, Claim::TIER_2])) {
            return response()->json([
                'message' => 'Invalid tier selected for payment.',
            ], 422);
        }

        try {
            // Update claim tier if different
            if ($claim->tier !== $tier) {
                $claim->update(['tier' => $tier]);
            }

            // Create payment intent
            $paymentIntent = $stripeService->createPaymentIntent($claim, $tier);

            if (!$paymentIntent) {
                return response()->json([
                    'message' => 'No payment required for free tier.',
                ], 422);
            }

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'amount' => $paymentIntent->amount / 100, // Convert cents to dollars
                'tier' => $tier,
                'tier_name' => Claim::TIER_NAMES[$tier],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create payment intent', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to initialize payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Confirm payment for a claim
     */
    public function confirmPayment(Request $request, Claim $claim)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        if ($claim->stripe_payment_status === 'succeeded') {
            return response()->json([
                'message' => 'Payment already confirmed.',
            ]);
        }

        // For free tier, mark as paid immediately
        if ($claim->tier === Claim::TIER_FREE) {
            $claim->update([
                'stripe_payment_status' => 'succeeded',
                'payment_completed_at' => now(),
            ]);

            // Auto-approve free claims if configured
            if (config('services.claiming.auto_approve_free', false)) {
                $claim->approve($user);
            }

            return response()->json([
                'message' => 'Free tier confirmed.',
                'claim' => $claim->fresh()->load('place'),
            ]);
        }

        // For paid tiers, check with Stripe
        if (!$claim->stripe_payment_intent_id) {
            return response()->json([
                'message' => 'No payment intent found for this claim.',
            ], 422);
        }

        // Payment confirmation will be handled by Stripe webhook
        // This endpoint just acknowledges the frontend completed the payment flow
        return response()->json([
            'message' => 'Payment submitted. Waiting for confirmation.',
            'claim' => $claim->fresh()->load('place'),
        ]);
    }

    /**
     * Get next step for a claim
     */
    private function getNextStep(Claim $claim): array
    {
        if ($claim->status !== Claim::STATUS_PENDING) {
            return [
                'action' => 'none',
                'message' => 'Claim is no longer pending.',
            ];
        }

        // Check if payment is required and not completed
        if ($claim->tier !== Claim::TIER_FREE && $claim->stripe_payment_status !== 'succeeded') {
            return [
                'action' => 'complete_payment',
                'message' => 'Please complete the payment process to continue.',
            ];
        }

        switch ($claim->verification_method) {
            case 'email':
            case 'phone':
                if (!$claim->verified_at) {
                    return [
                        'action' => 'verify_code',
                        'message' => 'Please enter the verification code sent to your ' . $claim->verification_method . '.',
                    ];
                }
                return [
                    'action' => 'wait_approval',
                    'message' => 'Your claim has been verified and is awaiting admin approval.',
                ];

            case 'document':
                if ($claim->documents()->count() === 0) {
                    return [
                        'action' => 'upload_documents',
                        'message' => 'Please upload business verification documents.',
                    ];
                }
                return [
                    'action' => 'wait_approval',
                    'message' => 'Your documents have been uploaded and are awaiting review.',
                ];

            default:
                return [
                    'action' => 'wait_approval',
                    'message' => 'Your claim is awaiting admin review.',
                ];
        }
    }

    /**
     * Create payment intent for verification fee
     */
    public function createVerificationFeePayment(Request $request, Claim $claim, StripeService $stripeService)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        // Validate request
        $request->validate([
            'keep_fee' => 'boolean',
        ]);

        // Check if fee already paid
        if ($claim->verification_fee_paid) {
            return response()->json([
                'message' => 'Verification fee already paid.',
            ], 422);
        }

        try {
            // Update whether to keep the fee
            $claim->update([
                'fee_kept' => $request->input('keep_fee', false),
            ]);

            // Create payment intent for verification fee
            $paymentIntent = $stripeService->createVerificationFeePaymentIntent($claim);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'amount' => Claim::VERIFICATION_FEE / 100, // Convert cents to dollars
                'fee_kept' => $claim->fee_kept,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create verification fee payment', ['error' => $e->getMessage()]);
            
            return response()->json([
                'message' => 'Failed to initialize payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Confirm verification fee payment
     */
    public function confirmVerificationFeePayment(Request $request, Claim $claim)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        // Mark fee as paid
        $claim->update([
            'verification_fee_paid' => true,
        ]);

        return response()->json([
            'message' => 'Verification fee confirmed.',
            'claim' => $claim->fresh()->load('place'),
        ]);
    }

    /**
     * Create combined payment intent for tier + verification fee
     */
    public function createCombinedPayment(Request $request, Claim $claim, StripeService $stripeService)
    {
        $user = auth()->user();
        
        // Ensure user owns this claim
        if ($claim->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'tier' => 'required|in:' . implode(',', [Claim::TIER_FREE, Claim::TIER_1, Claim::TIER_2]),
            'include_verification_fee' => 'boolean',
            'keep_fee' => 'boolean',
        ]);

        // Update claim with fee preferences
        if ($validated['include_verification_fee'] ?? false) {
            $claim->update([
                'fee_kept' => $validated['keep_fee'] ?? false,
            ]);
        }

        try {
            // Calculate total amount
            $tierAmount = 0;
            if ($validated['tier'] !== Claim::TIER_FREE) {
                $tierAmount = Claim::TIER_PRICES[$validated['tier']] ?? 0;
            }
            
            $feeAmount = ($validated['include_verification_fee'] ?? false) ? Claim::VERIFICATION_FEE : 0;
            $totalAmount = $tierAmount + $feeAmount;

            if ($totalAmount === 0) {
                return response()->json([
                    'message' => 'No payment required.',
                ], 422);
            }

            // Create Stripe customer if needed
            $customer = $stripeService->getOrCreateCustomer($user);

            // Create payment intent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $totalAmount,
                'currency' => 'usd',
                'customer' => $customer->id,
                'metadata' => [
                    'claim_id' => $claim->id,
                    'place_id' => $claim->place_id,
                    'user_id' => $user->id,
                    'tier' => $validated['tier'],
                    'tier_amount' => $tierAmount / 100,
                    'fee_amount' => $feeAmount / 100,
                    'fee_kept' => ($validated['keep_fee'] ?? false) ? 'yes' : 'no',
                    'type' => 'combined_payment',
                ],
                'description' => 'Business claim - ' . Claim::TIER_NAMES[$validated['tier']] . 
                                ($feeAmount > 0 ? ' + Verification Fee' : ''),
            ]);

            // Update claim with payment intent ID
            $claim->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
                'payment_amount' => $tierAmount / 100,
                'tier' => $validated['tier'],
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'total_amount' => $totalAmount / 100,
                'tier_amount' => $tierAmount / 100,
                'fee_amount' => $feeAmount / 100,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create combined payment intent', [
                'error' => $e->getMessage(),
                'claim_id' => $claim->id,
            ]);

            return response()->json([
                'message' => 'Failed to initialize payment. Please try again.',
            ], 500);
        }
    }
}