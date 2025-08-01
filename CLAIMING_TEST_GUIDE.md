# Business Claiming Test Guide

## Prerequisites

1. **Stripe Test Keys**: Update your `.env` file with your Stripe test keys:
   ```bash
   STRIPE_SECRET=sk_test_... # Your Stripe test secret key
   STRIPE_WEBHOOK_SECRET=whsec_... # Your webhook secret (optional for local testing)
   STRIPE_PRICE_TIER1=price_... # Create a $9.99/month price in Stripe
   STRIPE_PRICE_TIER2=price_... # Create a $19.99/month price in Stripe
   ```

2. **Test Mode Enabled**: Ensure test mode is enabled in `.env`:
   ```bash
   CLAIMING_TEST_MODE=true
   CLAIMING_TEST_OTP=123456
   CLAIMING_AUTO_APPROVE_FREE=true
   ```

## Testing the Complete Flow

### 1. Find an Unclaimed Business
- Navigate to http://localhost:5173/places
- Find a business that doesn't have a "Verified Business" badge
- Click on the business to view details

### 2. Start the Claiming Process
- Click the "Claim This Business" button
- You'll be redirected to `/places/{slug}/claim`

### 3. Step 1: Verification Method
- Choose one of three verification methods:
  - **Email Verification** (recommended for testing)
  - Phone Verification
  - Document Upload

### 4. Step 2: Verification
- For **Email Verification**:
  - Enter any email address (e.g., test@example.com)
  - Since test mode is enabled, use OTP: `123456`
  - The system will accept this code automatically

### 5. Step 3: Choose Your Plan (Soft-sell)
- You'll see three horizontal tier cards:
  - **Free** (default selected) - $0/month
  - **Professional** - $9.99/month
  - **Premium** - $19.99/month
- Free tier is pre-selected by default
- Click "Complete Claim" for free tier or "Continue to Payment" for paid tiers

### 6. Step 4: Payment (only for paid tiers)
- Use Stripe test cards:
  - **Success**: `4242 4242 4242 4242`
  - **Decline**: `4000 0000 0000 0002`
  - **3D Secure**: `4000 0025 0000 3155`
- Any future expiry date and any 3-digit CVC

### 7. View Your Claimed Business
- After successful claim, click "View My Places"
- Navigate to http://localhost:5173/my-places
- You should see your claimed business with status:
  - **"Claiming process underway"** - for pending claims
  - **"Claim Approved"** - for approved claims (free tier auto-approves if configured)

## Testing Different Scenarios

### Free Tier Claim
1. Select "Free" tier (default)
2. Complete verification
3. Click "Complete Claim"
4. Should auto-approve if `CLAIMING_AUTO_APPROVE_FREE=true`

### Paid Tier Claim
1. Select "Professional" or "Premium" tier
2. Complete verification
3. Enter payment details with test card `4242 4242 4242 4242`
4. Claim will be pending until admin approval

### Failed Payment
1. Select a paid tier
2. Use test card `4000 0000 0000 0002`
3. Should see error message
4. Can retry with valid card

### Multiple Claims Prevention
- Try claiming a business you've already claimed
- Should see message that you cannot claim it again

## Troubleshooting

### OTP Not Working
- Check `CLAIMING_TEST_MODE=true` in `.env`
- Test OTP should be `123456`
- Check browser console for errors

### Payment Form Not Loading
- Verify `VITE_STRIPE_PUBLIC_KEY` is set in `frontend/.env`
- Check browser console for Stripe errors
- Ensure you're using test keys (start with `pk_test_`)

### Claims Not Showing in My Places
- Check if claim was created: `php artisan tinker`
  ```php
  \App\Models\Claim::where('user_id', auth()->id())->get()
  ```
- Verify the API response at `/api/my-places`

### Stripe Price IDs
If you haven't created price IDs yet:
1. Go to https://dashboard.stripe.com/test/products
2. Create a product "Business Listing"
3. Add recurring prices:
   - Professional: $9.99/month
   - Premium: $19.99/month
4. Copy the price IDs (start with `price_`) to your `.env`

## Admin Testing

To approve pending claims:
1. Login as admin
2. Navigate to `/admin/claims` (if implemented)
3. Or use tinker:
   ```php
   $claim = \App\Models\Claim::find(1);
   $claim->approve($claim->user);
   ```

## Reset Test Data

To start fresh:
```bash
# Delete all claims
php artisan tinker
\App\Models\Claim::truncate();

# Reset a specific place to unclaimed
$place = \App\Models\Place::find(1);
$place->update(['is_claimed' => false, 'owner_user_id' => null]);
```
