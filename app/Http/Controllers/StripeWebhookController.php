<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\User;
use App\Models\Claim;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Stripe\Subscription as StripeSubscription;

class StripeWebhookController extends CashierController
{
    /**
     * Handle subscription created
     */
    public function handleCustomerSubscriptionCreated(array $payload)
    {
        $this->updatePlaceSubscription($payload);
        
        return parent::handleCustomerSubscriptionCreated($payload);
    }

    /**
     * Handle subscription updated
     */
    public function handleCustomerSubscriptionUpdated(array $payload)
    {
        $this->updatePlaceSubscription($payload);
        
        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    /**
     * Handle subscription deleted
     */
    public function handleCustomerSubscriptionDeleted(array $payload)
    {
        $subscription = $payload['data']['object'];
        
        // Find the place by Stripe subscription ID
        $place = Place::where('stripe_subscription_id', $subscription['id'])->first();
        
        if ($place) {
            $place->update([
                'subscription_tier' => 'free',
                'subscription_expires_at' => now(),
            ]);
        }
        
        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Handle payment intent succeeded
     */
    public function handlePaymentIntentSucceeded(array $payload)
    {
        $intent = $payload['data']['object'];
        $stripeService = app(StripeService::class);
        
        // Convert Stripe PaymentIntent array to object
        $paymentIntent = \Stripe\PaymentIntent::constructFrom($intent);
        
        // Handle claim payment
        $stripeService->handlePaymentIntentSucceeded($paymentIntent);
        
        return response('Webhook Handled', 200);
    }

    /**
     * Update place subscription details from webhook payload
     */
    protected function updatePlaceSubscription(array $payload)
    {
        $subscription = $payload['data']['object'];
        
        // Find the place by Stripe subscription ID
        $place = Place::where('stripe_subscription_id', $subscription['id'])->first();
        
        if (!$place) {
            return;
        }

        // Determine tier from price ID
        $tier = 'free';
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        
        if ($priceId === config('services.stripe.prices.tier1')) {
            $tier = 'tier1';
        } elseif ($priceId === config('services.stripe.prices.tier2')) {
            $tier = 'tier2';
        }

        // Update place subscription details
        $place->update([
            'subscription_tier' => $tier,
            'subscription_expires_at' => $subscription['cancel_at'] 
                ? \Carbon\Carbon::createFromTimestamp($subscription['cancel_at']) 
                : null,
        ]);
    }
}