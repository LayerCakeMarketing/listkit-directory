<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Subscription;
use App\Models\Claim;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for a claim
     */
    public function createPaymentIntent(Claim $claim, string $tier): PaymentIntent
    {
        $amount = Claim::TIER_PRICES[$tier];
        
        // No payment needed for free tier
        if ($amount === 0) {
            return null;
        }

        // Create or get Stripe customer
        $customer = $this->getOrCreateCustomer($claim->user);

        // Create payment intent
        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'usd',
            'customer' => $customer->id,
            'metadata' => [
                'claim_id' => $claim->id,
                'place_id' => $claim->place_id,
                'tier' => $tier,
                'user_id' => $claim->user_id,
            ],
            'description' => 'Business claim - ' . Claim::TIER_NAMES[$tier] . ' tier',
        ]);

        // Update claim with payment intent ID
        $claim->update([
            'stripe_payment_intent_id' => $intent->id,
            'payment_amount' => $amount / 100, // Store in dollars
        ]);

        return $intent;
    }

    /**
     * Create a subscription for ongoing payments
     */
    public function createSubscription(Claim $claim, string $paymentMethodId): Subscription
    {
        $tier = $claim->tier;
        $priceId = $this->getPriceIdForTier($tier);
        
        if (!$priceId) {
            throw new \Exception('No price ID configured for tier: ' . $tier);
        }

        $customer = $this->getOrCreateCustomer($claim->user);

        // Attach payment method to customer
        $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
        $paymentMethod->attach(['customer' => $customer->id]);

        // Set as default payment method
        $customer->invoice_settings = ['default_payment_method' => $paymentMethodId];
        $customer->save();

        // Create subscription
        $subscription = Subscription::create([
            'customer' => $customer->id,
            'items' => [['price' => $priceId]],
            'metadata' => [
                'claim_id' => $claim->id,
                'place_id' => $claim->place_id,
                'user_id' => $claim->user_id,
            ],
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        // Update claim
        $claim->update([
            'stripe_subscription_id' => $subscription->id,
            'subscription_starts_at' => now(),
        ]);

        return $subscription;
    }

    /**
     * Get or create Stripe customer for user
     */
    public function getOrCreateCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve Stripe customer: ' . $e->getMessage());
            }
        }

        // Create new customer
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        // Save customer ID to user
        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Get Stripe price ID for tier
     */
    protected function getPriceIdForTier(string $tier): ?string
    {
        $priceIds = [
            Claim::TIER_1 => config('services.stripe.price_tier1'),
            Claim::TIER_2 => config('services.stripe.price_tier2'),
        ];

        return $priceIds[$tier] ?? null;
    }

    /**
     * Handle webhook for payment confirmation
     */
    public function handlePaymentIntentSucceeded(PaymentIntent $intent): void
    {
        $claimId = $intent->metadata->claim_id ?? null;
        
        if (!$claimId) {
            Log::warning('Payment intent succeeded but no claim_id in metadata', ['intent_id' => $intent->id]);
            return;
        }

        $claim = Claim::find($claimId);
        if (!$claim) {
            Log::warning('Payment intent succeeded but claim not found', ['claim_id' => $claimId]);
            return;
        }

        $claim->update([
            'stripe_payment_status' => 'succeeded',
            'payment_completed_at' => now(),
        ]);

        // If this was a one-time payment and claim is verified, approve it
        if ($claim->verified_at && !$claim->approved_at) {
            // In production, you might want manual approval
            // For now, auto-approve after payment
            if (config('services.claiming.auto_approve_after_payment', false)) {
                $claim->approve($claim->user);
            }
        }
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(string $subscriptionId): Subscription
    {
        $subscription = Subscription::retrieve($subscriptionId);
        return $subscription->cancel();
    }

    /**
     * Get subscription details
     */
    public function getSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve([
            'id' => $subscriptionId,
            'expand' => ['customer', 'latest_invoice'],
        ]);
    }

    /**
     * Create a payment intent for verification fee
     */
    public function createVerificationFeePaymentIntent(Claim $claim): PaymentIntent
    {
        // Create or get Stripe customer
        $customer = $this->getOrCreateCustomer($claim->user);

        // Create payment intent for verification fee
        $intent = PaymentIntent::create([
            'amount' => Claim::VERIFICATION_FEE,
            'currency' => 'usd',
            'customer' => $customer->id,
            'metadata' => [
                'claim_id' => $claim->id,
                'place_id' => $claim->place_id,
                'user_id' => $claim->user_id,
                'type' => 'verification_fee',
                'fee_kept' => $claim->fee_kept ? 'yes' : 'no',
            ],
            'description' => 'Verification fee for business claim' . ($claim->fee_kept ? ' (kept as tip)' : ' (refundable)'),
        ]);

        // Update claim with fee payment intent ID
        $claim->update([
            'fee_payment_intent_id' => $intent->id,
        ]);

        return $intent;
    }

    /**
     * Refund verification fee
     */
    public function refundVerificationFee(Claim $claim): \Stripe\Refund
    {
        if (!$claim->fee_payment_intent_id) {
            throw new \Exception('No payment intent found for verification fee');
        }

        if ($claim->fee_refunded_at) {
            throw new \Exception('Fee already refunded');
        }

        // Create refund
        $refund = \Stripe\Refund::create([
            'payment_intent' => $claim->fee_payment_intent_id,
            'reason' => 'requested_by_customer',
            'metadata' => [
                'claim_id' => $claim->id,
                'reason' => 'verification_completed',
            ],
        ]);

        // Mark as refunded
        $claim->markFeeRefunded($refund->id);

        return $refund;
    }
}