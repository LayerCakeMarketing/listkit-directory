# Business Claiming & Subscription Setup Guide

This guide explains how to set up the business claiming and subscription features.

## Prerequisites

### 1. Install Laravel Cashier for Stripe Integration

```bash
composer require laravel/cashier
```

### 2. Publish Cashier Migrations

```bash
php artisan vendor:publish --tag="cashier-migrations"
```

### 3. Run Migrations

```bash
php artisan migrate
```

This will create the necessary tables for claims, verification codes, documents, and add subscription fields to places.

### 4. Install Twilio SDK (Optional, for SMS verification)

```bash
composer require twilio/sdk
```

## Configuration

### 1. Environment Variables

Copy the example environment variables to your `.env` file:

```bash
# Stripe Configuration
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key  
STRIPE_WEBHOOK_SECRET=your_webhook_secret

# Stripe Price IDs
STRIPE_PRICE_TIER1=price_xxxxx  # Professional tier ($9.99/month)
STRIPE_PRICE_TIER2=price_xxxxx  # Premium tier ($19.99/month)

# Twilio (optional, for SMS)
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+1234567890
```

### 2. Create Stripe Products and Prices

1. Log into your Stripe Dashboard
2. Create a product called "Business Listing Subscription"
3. Add two prices:
   - Professional: $9.99/month (recurring)
   - Premium: $19.99/month (recurring)
4. Copy the price IDs to your `.env` file

### 3. Set Up Stripe Webhook

1. In Stripe Dashboard, go to Developers → Webhooks
2. Add endpoint: `https://yourdomain.com/stripe/webhook`
3. Select events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
4. Copy the signing secret to `STRIPE_WEBHOOK_SECRET`

### 4. Update User Model

Add the Billable trait to your User model if not already present:

```php
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use Billable;
    // ...
}
```

## Feature Overview

### Business Claiming Process

1. **Claim Initiation**
   - User clicks "Claim this business" on an unclaimed place
   - Selects verification method: Email, Phone, or Document

2. **Verification Methods**
   - **Email**: OTP sent to business email
   - **Phone**: OTP sent via SMS to business phone
   - **Document**: Upload business license, tax docs, utility bills

3. **Admin Review**
   - Admins can view all pending claims at `/admin/claims`
   - Can approve/reject claims with notes
   - Can verify uploaded documents

4. **Post-Approval**
   - Place ownership transferred to claiming user
   - User prompted to select subscription tier

### Subscription Tiers

1. **Free Tier**
   - Basic business information edits
   - Respond to reviews  
   - Update business hours

2. **Professional ($9.99/month)**
   - Everything in Free
   - Business analytics dashboard
   - Verified badge
   - Priority customer support
   - Remove competitor ads

3. **Premium ($19.99/month)**
   - Everything in Professional
   - Multi-location management
   - Team member access
   - Custom branding options
   - API access
   - Dedicated account manager

### API Endpoints

#### Public Endpoints
- `GET /api/claims/places/{place}/check` - Check if place can be claimed
- `POST /api/claims/places/{place}/initiate` - Start claim process

#### Authenticated Endpoints
- `GET /api/claims/{claim}/status` - Get claim status
- `POST /api/claims/{claim}/verify` - Verify OTP code
- `POST /api/claims/{claim}/documents` - Upload verification documents
- `POST /api/claims/{claim}/resend-code` - Resend verification code
- `DELETE /api/claims/{claim}` - Cancel claim
- `GET /api/claims/my-claims` - List user's claims

#### Subscription Endpoints
- `GET /api/subscriptions/plans` - Get available plans
- `GET /api/subscriptions/places/{place}/status` - Get subscription status
- `POST /api/subscriptions/places/{place}/subscribe` - Create/update subscription
- `POST /api/subscriptions/places/{place}/cancel` - Cancel subscription
- `POST /api/subscriptions/places/{place}/resume` - Resume cancelled subscription

#### Admin Endpoints
- `GET /api/admin/claims` - List all claims
- `GET /api/admin/claims/statistics` - Get claim statistics
- `POST /api/admin/claims/{claim}/approve` - Approve claim
- `POST /api/admin/claims/{claim}/reject` - Reject claim
- `POST /api/admin/claims/documents/{document}/verify` - Verify document

## Testing

### Test Claiming Process
1. Find an unclaimed place
2. Click "Claim this business"
3. Use test Stripe card: 4242 4242 4242 4242

### Test Webhook Locally
Use Stripe CLI:
```bash
stripe listen --forward-to localhost:8000/stripe/webhook
```

## Frontend Implementation

The frontend components need to be created in Vue.js to handle:
1. Claim button and modal on place pages
2. Verification code input form
3. Document upload interface
4. Subscription selection and payment form
5. Admin claim review dashboard

## Security Considerations

1. **Rate Limiting**: OTP requests limited to 3 per hour
2. **Code Expiry**: Verification codes expire after 30 minutes
3. **Attempt Limits**: Maximum 5 attempts per verification code
4. **Document Storage**: Documents stored in private storage
5. **Webhook Validation**: Stripe signature verification enabled