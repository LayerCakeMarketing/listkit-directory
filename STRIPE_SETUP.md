# Stripe Setup for Business Claiming

## Environment Variables

Add these to your `.env` file:

```bash
# Stripe API Keys (get from https://dashboard.stripe.com/apikeys)
STRIPE_KEY=pk_test_... # Publishable key
STRIPE_SECRET=sk_test_... # Secret key

# Stripe Webhook (for payment confirmations)
STRIPE_WEBHOOK_SECRET=whsec_... # Get this after creating webhook

# Stripe Price IDs (create in Stripe Dashboard or via API)
STRIPE_PRICE_TIER1=price_... # $9.99/month plan
STRIPE_PRICE_TIER2=price_... # $19.99/month plan
```

## Frontend Environment

Add to `frontend/.env`:

```bash
VITE_STRIPE_PUBLIC_KEY=pk_test_... # Same as STRIPE_KEY
```

## Creating Stripe Products and Prices

### Option 1: Via Stripe Dashboard

1. Go to https://dashboard.stripe.com/products
2. Create a product called "Business Listing - Professional"
3. Add a recurring price of $9.99/month
4. Copy the price ID (starts with `price_`)
5. Repeat for "Business Listing - Premium" at $19.99/month

### Option 2: Via Stripe CLI

```bash
# Install Stripe CLI first
# https://stripe.com/docs/stripe-cli

# Create products and prices
stripe products create \
  --name="Business Listing - Professional" \
  --description="Professional tier for business listings"

stripe prices create \
  --product="prod_..." \
  --unit-amount=999 \
  --currency=usd \
  --recurring[interval]=month

# Repeat for Premium tier at 1999 ($19.99)
```

## Setting up Webhooks

1. In Stripe Dashboard, go to Developers > Webhooks
2. Add endpoint: `https://yourdomain.com/stripe/webhook`
3. Select events:
   - `payment_intent.succeeded`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
4. Copy the signing secret to `STRIPE_WEBHOOK_SECRET`

## Testing

Use Stripe test cards:
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`
- Requires auth: `4000 0025 0000 3155`

## Local Testing with Stripe CLI

```bash
# Forward webhooks to local development
stripe listen --forward-to localhost:8000/stripe/webhook

# This will show your webhook signing secret
# Copy it to STRIPE_WEBHOOK_SECRET in .env
```

## Verify Configuration

Run the test script to verify everything is set up:

```bash
php test-claim-flow.php
```

All Stripe configuration items should show as "SET".