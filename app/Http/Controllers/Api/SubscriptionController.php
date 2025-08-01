<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    /**
     * Get available subscription plans
     */
    public function getPlans()
    {
        $plans = [
            [
                'id' => 'free',
                'name' => 'Free',
                'price' => 0,
                'features' => [
                    'Basic business information edits',
                    'Respond to reviews',
                    'Update business hours',
                ],
            ],
            [
                'id' => 'tier1',
                'name' => 'Professional',
                'price' => 999, // $9.99 in cents
                'stripe_price_id' => config('services.stripe.prices.tier1'),
                'features' => [
                    'Everything in Free',
                    'Business analytics dashboard',
                    'Verified badge',
                    'Priority customer support',
                    'Remove competitor ads',
                ],
            ],
            [
                'id' => 'tier2', 
                'name' => 'Premium',
                'price' => 1999, // $19.99 in cents
                'stripe_price_id' => config('services.stripe.prices.tier2'),
                'features' => [
                    'Everything in Professional',
                    'Multi-location management',
                    'Team member access',
                    'Custom branding options',
                    'API access',
                    'Dedicated account manager',
                ],
            ],
        ];

        return response()->json(['plans' => $plans]);
    }

    /**
     * Get subscription status for a place
     */
    public function getSubscriptionStatus(Place $place)
    {
        $user = auth()->user();
        
        // Check if user can manage this place
        if (!$place->canBeManaged($user)) {
            abort(403);
        }

        $subscription = null;
        if ($place->owner && $place->stripe_subscription_id) {
            $subscription = $place->owner->subscriptions()
                ->where('stripe_id', $place->stripe_subscription_id)
                ->first();
        }

        return response()->json([
            'current_tier' => $place->subscription_tier,
            'has_active_subscription' => $place->hasActiveSubscription(),
            'expires_at' => $place->subscription_expires_at,
            'subscription' => $subscription,
            'features' => $this->getFeaturesForTier($place->subscription_tier),
        ]);
    }

    /**
     * Create or update subscription
     */
    public function subscribe(Request $request, Place $place)
    {
        $user = auth()->user();
        
        // Check if user owns this place
        if ($place->owner_user_id !== $user->id) {
            abort(403, 'Only the owner can manage subscriptions');
        }

        $validated = $request->validate([
            'tier' => 'required|in:tier1,tier2',
            'payment_method' => 'required|string',
        ]);

        try {
            // Create or get Stripe customer
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }

            // Update default payment method
            $user->updateDefaultPaymentMethod($validated['payment_method']);

            // Get the Stripe price ID for the selected tier
            $priceId = config('services.stripe.prices.' . $validated['tier']);
            
            if (!$priceId) {
                return response()->json([
                    'message' => 'Invalid subscription tier'
                ], 422);
            }

            // Create or swap subscription
            if ($place->stripe_subscription_id && $user->subscribed($place->stripe_subscription_id)) {
                // Swap existing subscription
                $subscription = $user->subscription($place->stripe_subscription_id)
                    ->swap($priceId);
            } else {
                // Create new subscription
                $subscription = $user->newSubscription('place_' . $place->id, $priceId)
                    ->create($validated['payment_method']);
            }

            // Update place with subscription details
            $place->update([
                'subscription_tier' => $validated['tier'],
                'stripe_customer_id' => $user->stripe_id,
                'stripe_subscription_id' => $subscription->stripe_id,
                'subscription_expires_at' => $subscription->ends_at ?? null,
            ]);

            return response()->json([
                'message' => 'Subscription created successfully',
                'subscription' => $subscription,
                'place' => $place->fresh(),
            ]);

        } catch (IncompletePayment $exception) {
            return response()->json([
                'message' => 'Payment requires additional confirmation',
                'payment_intent' => $exception->payment->id,
                'client_secret' => $exception->payment->client_secret,
            ], 402);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Place $place)
    {
        $user = auth()->user();
        
        // Check if user owns this place
        if ($place->owner_user_id !== $user->id) {
            abort(403, 'Only the owner can manage subscriptions');
        }

        if (!$place->stripe_subscription_id) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 422);
        }

        try {
            $subscription = $user->subscription($place->stripe_subscription_id);
            
            if ($subscription) {
                $subscription->cancel();
                
                // Update place subscription details
                $place->update([
                    'subscription_expires_at' => $subscription->ends_at,
                ]);
            }

            return response()->json([
                'message' => 'Subscription cancelled successfully',
                'expires_at' => $subscription->ends_at,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resume a cancelled subscription
     */
    public function resume(Place $place)
    {
        $user = auth()->user();
        
        // Check if user owns this place
        if ($place->owner_user_id !== $user->id) {
            abort(403, 'Only the owner can manage subscriptions');
        }

        if (!$place->stripe_subscription_id) {
            return response()->json([
                'message' => 'No subscription found'
            ], 422);
        }

        try {
            $subscription = $user->subscription($place->stripe_subscription_id);
            
            if ($subscription && $subscription->cancelled()) {
                $subscription->resume();
                
                // Update place subscription details
                $place->update([
                    'subscription_expires_at' => null,
                ]);
            }

            return response()->json([
                'message' => 'Subscription resumed successfully',
                'subscription' => $subscription->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resume subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get features for a subscription tier
     */
    private function getFeaturesForTier($tier)
    {
        $features = [
            'free' => [
                'basic_edits' => true,
                'respond_reviews' => true,
                'update_hours' => true,
                'analytics' => false,
                'featured_badge' => false,
                'priority_support' => false,
                'multi_location' => false,
                'team_access' => false,
                'custom_branding' => false,
            ],
            'tier1' => [
                'basic_edits' => true,
                'respond_reviews' => true,
                'update_hours' => true,
                'analytics' => true,
                'featured_badge' => true,
                'priority_support' => true,
                'multi_location' => false,
                'team_access' => false,
                'custom_branding' => false,
            ],
            'tier2' => [
                'basic_edits' => true,
                'respond_reviews' => true,
                'update_hours' => true,
                'analytics' => true,
                'featured_badge' => true,
                'priority_support' => true,
                'multi_location' => true,
                'team_access' => true,
                'custom_branding' => true,
            ],
        ];

        return $features[$tier] ?? $features['free'];
    }
}