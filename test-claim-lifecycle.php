<?php

// Test script for claim lifecycle
// Run with: php test-claim-lifecycle.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Place;
use App\Models\Claim;
use Illuminate\Support\Facades\DB;

echo "\n=== CLAIM LIFECYCLE TEST ===\n\n";

// Get test user
$testUser = User::where('email', 'test@example.com')->first();
if (!$testUser) {
    echo "Creating test user...\n";
    $testUser = User::create([
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);
}

echo "Test user ID: {$testUser->id}\n\n";

// Check user's current claims
echo "=== CURRENT CLAIMS ===\n";
$userClaims = Claim::where('user_id', $testUser->id)->get();
echo "Total claims by user: " . $userClaims->count() . "\n";
echo "By status:\n";
foreach ($userClaims->groupBy('status') as $status => $claims) {
    echo "  - {$status}: " . $claims->count() . "\n";
}

// Check MyPlaces API result
echo "\n=== MYPLACES API RESULT ===\n";
$places = Place::with([
    'claims' => function($q) use ($testUser) {
        $q->where('user_id', $testUser->id)
          ->whereIn('status', ['pending', 'approved'])
          ->latest();
    }
])->where(function($q) use ($testUser) {
    $q->where('created_by_user_id', $testUser->id)
      ->orWhereHas('claims', function($subQ) use ($testUser) {
          $subQ->where('user_id', $testUser->id)
               ->whereIn('status', ['pending', 'approved']);
      });
})->get();

echo "Places visible in MyPlaces: " . $places->count() . "\n";
foreach ($places as $place) {
    $claim = $place->claims->first();
    echo "  - {$place->title} (ID: {$place->id})";
    if ($claim) {
        echo " [Claim: {$claim->status}]";
    }
    echo "\n";
}

// Check admin statistics
echo "\n=== ADMIN STATISTICS ===\n";
$stats = [
    'total_claims' => Claim::count(),
    'pending_claims' => Claim::pending()->count(),
    'approved_claims' => Claim::where('status', 'approved')->count(),
    'rejected_claims' => Claim::where('status', 'rejected')->count(),
    'expired_claims' => Claim::expired()->count(),
    'paid_claims' => Claim::where(function ($q) {
        $q->where('verification_fee_paid', true)
          ->orWhere('tier', '!=', 'free')
          ->orWhereNotNull('stripe_subscription_id');
    })->count(),
    'unpaid_claims' => Claim::where(function ($q) {
        $q->where('verification_fee_paid', false)
          ->where('tier', 'free')
          ->whereNull('stripe_subscription_id');
    })->count(),
];

foreach ($stats as $key => $value) {
    echo "  - " . str_replace('_', ' ', ucfirst($key)) . ": {$value}\n";
}

// Test unclaim process
echo "\n=== TEST UNCLAIM PROCESS ===\n";
$pendingClaim = Claim::where('user_id', $testUser->id)
    ->where('status', 'pending')
    ->first();

if ($pendingClaim) {
    echo "Found pending claim ID: {$pendingClaim->id} for place: {$pendingClaim->place->title}\n";
    echo "Setting to expired...\n";
    
    DB::beginTransaction();
    try {
        // Simulate unclaim
        $pendingClaim->update([
            'status' => 'expired',
            'metadata' => array_merge($pendingClaim->metadata ?? [], [
                'unclaimed_by' => 1,
                'unclaimed_at' => now()->toIso8601String(),
                'unclaim_reason' => 'Test unclaim',
            ]),
        ]);
        
        // Remove ownership
        $pendingClaim->place->update([
            'owner_user_id' => null,
            'is_claimed' => false,
            'claimed_at' => null,
        ]);
        
        DB::commit();
        echo "✓ Claim marked as expired\n";
        
        // Check if it still appears in MyPlaces
        $visibleAfterUnclaim = Place::whereHas('claims', function($q) use ($testUser, $pendingClaim) {
            $q->where('user_id', $testUser->id)
              ->where('id', $pendingClaim->id)
              ->whereIn('status', ['pending', 'approved']);
        })->exists();
        
        echo "Still visible in MyPlaces: " . ($visibleAfterUnclaim ? 'YES (ERROR!)' : 'NO (Correct)') . "\n";
        
    } catch (\Exception $e) {
        DB::rollback();
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No pending claims found for test user\n";
}

echo "\n=== TEST COMPLETE ===\n\n";