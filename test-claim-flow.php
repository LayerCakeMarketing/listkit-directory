<?php

/**
 * Test script for verifying the claiming flow
 * Run with: php test-claim-flow.php
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Place;
use App\Models\User;
use App\Models\Claim;

echo "=== Testing Claim Flow ===\n\n";

// Check if test mode is enabled
$testMode = config('services.claiming.test_mode');
echo "Test Mode: " . ($testMode ? 'ENABLED' : 'DISABLED') . "\n";
echo "Test OTP: " . config('services.claiming.test_otp', 'NOT SET') . "\n\n";

// Check Stripe configuration
echo "Stripe Configuration:\n";
echo "- Public Key: " . (env('STRIPE_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "- Secret Key: " . (config('services.stripe.secret') ? 'SET' : 'NOT SET') . "\n";
echo "- Tier 1 Price ID: " . (config('services.stripe.prices.tier1') ?: 'NOT SET') . "\n";
echo "- Tier 2 Price ID: " . (config('services.stripe.prices.tier2') ?: 'NOT SET') . "\n\n";

// Check database tables
echo "Database Tables:\n";
try {
    $claimsCount = DB::table('claims')->count();
    echo "✓ claims table exists (Records: $claimsCount)\n";
    
    // Check claims table columns
    $claimsColumns = Schema::getColumnListing('claims');
    $requiredColumns = ['tier', 'stripe_payment_intent_id', 'payment_amount', 'stripe_payment_status'];
    foreach ($requiredColumns as $col) {
        if (in_array($col, $claimsColumns)) {
            echo "  ✓ Column '$col' exists\n";
        } else {
            echo "  ✗ Column '$col' MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "✗ Error checking claims table: " . $e->getMessage() . "\n";
}

// Check users table for stripe_customer_id
try {
    $usersColumns = Schema::getColumnListing('users');
    if (in_array('stripe_customer_id', $usersColumns)) {
        echo "✓ users.stripe_customer_id column exists\n";
    } else {
        echo "✗ users.stripe_customer_id column MISSING\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking users table: " . $e->getMessage() . "\n";
}

echo "\n";

// Check for test data
echo "Test Data:\n";
$testUser = User::where('email', 'test@example.com')->first();
echo "- Test User: " . ($testUser ? "EXISTS (ID: {$testUser->id})" : "NOT FOUND") . "\n";

$unclaimedPlace = Place::where('is_claimed', false)->where('status', 'published')->first();
echo "- Unclaimed Place: " . ($unclaimedPlace ? "EXISTS (ID: {$unclaimedPlace->id}, Name: {$unclaimedPlace->name})" : "NOT FOUND") . "\n";

$pendingClaims = Claim::where('status', 'pending')->count();
echo "- Pending Claims: $pendingClaims\n";

// Check API routes
echo "\nAPI Routes:\n";
$routes = [
    'POST /api/places/{place}/claim' => 'Initiate claim',
    'GET /api/claims/{claim}/status' => 'Check claim status',
    'POST /api/claims/{claim}/verify' => 'Verify OTP',
    'POST /api/claims/{claim}/confirm-payment' => 'Confirm payment',
    'GET /api/my-places' => 'List user places',
];

foreach ($routes as $route => $description) {
    echo "- $route => $description\n";
}

echo "\n=== End of Test ===\n";