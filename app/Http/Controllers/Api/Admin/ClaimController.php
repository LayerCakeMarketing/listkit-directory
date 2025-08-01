<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ClaimDocument;
use App\Services\StripeService;
use App\Jobs\RefundVerificationFee;
use App\Notifications\ClaimStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClaimController extends Controller
{
    /**
     * Get all claims for admin review
     */
    public function index(Request $request)
    {
        $query = Claim::with(['place', 'user', 'documents']);
        
        // Add payment status filter
        if ($request->has('payment_status')) {
            if ($request->payment_status === 'paid') {
                $query->where(function ($q) {
                    $q->where('verification_fee_paid', true)
                      ->orWhere('tier', '!=', Claim::TIER_FREE)
                      ->orWhereNotNull('stripe_subscription_id');
                });
            } elseif ($request->payment_status === 'unpaid') {
                $query->where(function ($q) {
                    $q->where('verification_fee_paid', false)
                      ->where('tier', Claim::TIER_FREE)
                      ->whereNull('stripe_subscription_id');
                });
            }
            // If 'all' or not specified, show all claims
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by verification method
        if ($request->has('verification_method')) {
            $query->where('verification_method', $request->verification_method);
        }

        // Search by place name or user email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('place', function ($q2) use ($search) {
                    $q2->where('title', 'like', "%{$search}%");
                })
                ->orWhereHas('user', function ($q2) use ($search) {
                    $q2->where('email', 'like', "%{$search}%")
                       ->orWhere('firstname', 'like', "%{$search}%")
                       ->orWhere('lastname', 'like', "%{$search}%");
                });
            });
        }

        $claims = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($claims);
    }

    /**
     * Get a single claim for review
     */
    public function show(Claim $claim)
    {
        $claim->load(['place', 'user', 'documents', 'verificationCodes']);

        return response()->json($claim);
    }

    /**
     * Approve a claim
     */
    public function approve(Request $request, Claim $claim)
    {
        if (!$claim->isPending()) {
            return response()->json([
                'message' => 'Only pending claims can be approved.'
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $claim->approve(auth()->user());

            // Add any notes to metadata
            if (!empty($validated['notes'])) {
                $metadata = $claim->metadata ?? [];
                $metadata['approval_notes'] = $validated['notes'];
                $claim->update(['metadata' => $metadata]);
            }

            // Check if verification fee should be refunded
            if ($claim->shouldRefundFee()) {
                RefundVerificationFee::dispatch($claim);
            }

            // Send notification to user
            try {
                $claim->user->notify(new ClaimStatusChanged($claim, 'approved'));
            } catch (\Exception $e) {
                \Log::error('Failed to send claim approval notification', [
                    'claim_id' => $claim->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Claim approved successfully.',
                'claim' => $claim->fresh()->load(['place', 'user']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Reject a claim
     */
    public function reject(Request $request, Claim $claim)
    {
        if (!$claim->isPending()) {
            return response()->json([
                'message' => 'Only pending claims can be rejected.'
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $claim->reject(auth()->user(), $validated['reason']);

            // Check if verification fee should be refunded
            if ($claim->shouldRefundFee()) {
                RefundVerificationFee::dispatch($claim);
            }

            // Send notification to user
            try {
                $claim->user->notify(new ClaimStatusChanged($claim, 'rejected', $validated['reason']));
            } catch (\Exception $e) {
                \Log::error('Failed to send claim rejection notification', [
                    'claim_id' => $claim->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Claim rejected.',
                'claim' => $claim->fresh()->load(['place', 'user']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get statistics for claims dashboard
     */
    public function statistics()
    {
        $stats = [
            'total_claims' => Claim::count(),
            'pending_claims' => Claim::pending()->count(),
            'approved_claims' => Claim::where('status', Claim::STATUS_APPROVED)->count(),
            'rejected_claims' => Claim::where('status', Claim::STATUS_REJECTED)->count(),
            'expired_claims' => Claim::expired()->count(),
            'paid_claims' => Claim::where(function ($q) {
                $q->where('verification_fee_paid', true)
                  ->orWhere('tier', '!=', Claim::TIER_FREE)
                  ->orWhereNotNull('stripe_subscription_id');
            })->count(),
            'unpaid_claims' => Claim::where(function ($q) {
                $q->where('verification_fee_paid', false)
                  ->where('tier', Claim::TIER_FREE)
                  ->whereNull('stripe_subscription_id');
            })->count(),
            'claims_by_method' => Claim::selectRaw('verification_method, count(*) as count')
                ->groupBy('verification_method')
                ->pluck('count', 'verification_method'),
            'claims_by_tier' => Claim::selectRaw('tier, count(*) as count')
                ->groupBy('tier')
                ->pluck('count', 'tier'),
            'verification_fees' => [
                'total_paid' => Claim::where('verification_fee_paid', true)->count(),
                'fees_kept' => Claim::where('fee_kept', true)->count(),
                'fees_refunded' => Claim::whereNotNull('fee_refunded_at')->count(),
                'total_amount' => Claim::where('verification_fee_paid', true)->sum('verification_fee_amount'),
            ],
            'recent_claims' => Claim::with(['place', 'user'])
                ->pending()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Verify a document
     */
    public function verifyDocument(Request $request, ClaimDocument $document)
    {
        $validated = $request->validate([
            'status' => 'required|in:verified,rejected',
            'notes' => 'required_if:status,rejected|nullable|string|max:500',
        ]);

        if ($validated['status'] === 'verified') {
            $document->verify(auth()->user(), $validated['notes'] ?? null);
        } else {
            $document->reject(auth()->user(), $validated['notes']);
        }

        return response()->json([
            'message' => 'Document ' . $validated['status'] . ' successfully.',
            'document' => $document->fresh(),
        ]);
    }

    /**
     * Download a claim document
     */
    public function downloadDocument(ClaimDocument $document)
    {
        if (!Storage::exists($document->file_path)) {
            abort(404, 'Document not found');
        }

        return Storage::download($document->file_path, $document->file_name);
    }

    /**
     * Unclaim a place - removes claim without rejection
     */
    public function unclaim(Request $request, Claim $claim)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Store unclaim info in metadata
            $metadata = $claim->metadata ?? [];
            $metadata['unclaimed_by'] = auth()->id();
            $metadata['unclaimed_at'] = now()->toIso8601String();
            $metadata['unclaim_reason'] = $validated['reason'] ?? 'Admin removed claim';
            
            // Update claim status to expired (since 'cancelled' is not a valid status)
            $claim->update([
                'status' => 'expired',
                'metadata' => $metadata,
            ]);

            // Remove ownership from place
            $claim->place->update([
                'owner_user_id' => null,
                'is_claimed' => false,
                'claimed_at' => null,
            ]);

            // Check if verification fee should be refunded
            if ($claim->verification_fee_paid && !$claim->fee_kept && !$claim->fee_refunded_at) {
                RefundVerificationFee::dispatch($claim);
            }

            // Send notification to user
            try {
                $claim->user->notify(new ClaimStatusChanged($claim, 'unclaimed', $validated['reason']));
            } catch (\Exception $e) {
                \Log::error('Failed to send claim unclaim notification', [
                    'claim_id' => $claim->id,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Claim removed successfully.',
                'claim' => $claim->fresh()->load(['place', 'user']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get Stripe payment details for a claim
     */
    public function paymentDetails(Claim $claim, StripeService $stripeService)
    {
        $details = [
            'claim_id' => $claim->id,
            'tier' => $claim->tier,
            'verification_fee_paid' => $claim->verification_fee_paid,
            'fee_kept' => $claim->fee_kept,
            'fee_amount' => $claim->verification_fee_amount,
        ];

        // Initialize Stripe
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        // Get payment intent details if available
        if ($claim->fee_payment_intent_id) {
            try {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($claim->fee_payment_intent_id);
                $details['payment_intent'] = [
                    'id' => $paymentIntent->id,
                    'amount' => $paymentIntent->amount / 100,
                    'currency' => $paymentIntent->currency,
                    'status' => $paymentIntent->status,
                    'created' => date('Y-m-d H:i:s', $paymentIntent->created),
                    'payment_method_types' => $paymentIntent->payment_method_types,
                ];

                // Get charge details
                if ($paymentIntent->latest_charge) {
                    $charge = \Stripe\Charge::retrieve($paymentIntent->latest_charge);
                    $details['charge'] = [
                        'id' => $charge->id,
                        'amount' => $charge->amount / 100,
                        'amount_refunded' => $charge->amount_refunded / 100,
                        'paid' => $charge->paid,
                        'refunded' => $charge->refunded,
                        'receipt_url' => $charge->receipt_url,
                    ];
                }
            } catch (\Exception $e) {
                $details['payment_error'] = $e->getMessage();
            }
        }

        // Get subscription details if available
        if ($claim->stripe_subscription_id) {
            try {
                $subscription = \Stripe\Subscription::retrieve($claim->stripe_subscription_id);
                $details['subscription'] = [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'current_period_start' => date('Y-m-d H:i:s', $subscription->current_period_start),
                    'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
                    'cancel_at_period_end' => $subscription->cancel_at_period_end,
                ];
            } catch (\Exception $e) {
                $details['subscription_error'] = $e->getMessage();
            }
        }

        return response()->json($details);
    }
}