<?php

// Test script for claim notifications
// Run with: php test-claim-notifications.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Place;
use App\Models\Claim;
use App\Notifications\ClaimStatusChanged;
use Illuminate\Support\Facades\DB;

echo "\n=== CLAIM NOTIFICATION TEST ===\n\n";

// Get test user
$testUser = User::where('email', 'test@example.com')->first();
if (!$testUser) {
    echo "Test user not found. Please create a user with email: test@example.com\n";
    exit(1);
}

echo "Test user: {$testUser->firstname} {$testUser->lastname} ({$testUser->email})\n\n";

// Find a pending claim for the test user
$pendingClaim = Claim::where('user_id', $testUser->id)
    ->where('status', 'pending')
    ->with('place')
    ->first();

if (!$pendingClaim) {
    echo "No pending claims found for test user.\n";
    echo "Creating a test claim...\n";
    
    // Find or create a test place
    $place = Place::where('created_by_user_id', '!=', $testUser->id)
        ->whereNull('owner_user_id')
        ->first();
    
    if (!$place) {
        echo "No unclaimed places available for testing.\n";
        exit(1);
    }
    
    $pendingClaim = Claim::create([
        'place_id' => $place->id,
        'user_id' => $testUser->id,
        'status' => 'pending',
        'verification_method' => 'email',
        'business_email' => 'test@business.com',
        'tier' => 'free',
    ]);
    
    echo "Created test claim ID: {$pendingClaim->id} for place: {$place->title}\n";
}

echo "\n=== TESTING NOTIFICATIONS ===\n";

// Test approval notification
echo "\n1. Testing APPROVAL notification...\n";
try {
    $testUser->notify(new ClaimStatusChanged($pendingClaim, 'approved'));
    echo "✓ Approval notification sent successfully\n";
} catch (\Exception $e) {
    echo "✗ Error sending approval notification: " . $e->getMessage() . "\n";
}

// Test rejection notification
echo "\n2. Testing REJECTION notification...\n";
try {
    $testUser->notify(new ClaimStatusChanged($pendingClaim, 'rejected', 'Insufficient verification provided'));
    echo "✓ Rejection notification sent successfully\n";
} catch (\Exception $e) {
    echo "✗ Error sending rejection notification: " . $e->getMessage() . "\n";
}

// Test unclaim notification
echo "\n3. Testing UNCLAIM notification...\n";
try {
    $testUser->notify(new ClaimStatusChanged($pendingClaim, 'unclaimed', 'Administrative action'));
    echo "✓ Unclaim notification sent successfully\n";
} catch (\Exception $e) {
    echo "✗ Error sending unclaim notification: " . $e->getMessage() . "\n";
}

// Check database notifications
echo "\n=== DATABASE NOTIFICATIONS ===\n";
$notifications = DB::table('notifications')
    ->where('notifiable_id', $testUser->id)
    ->where('notifiable_type', User::class)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Recent notifications for test user:\n";
foreach ($notifications as $notification) {
    $data = json_decode($notification->data, true);
    echo "  - Type: " . $notification->type . "\n";
    echo "    Action: " . ($data['action'] ?? 'N/A') . "\n";
    echo "    Place: " . ($data['place_name'] ?? 'N/A') . "\n";
    echo "    Created: " . $notification->created_at . "\n\n";
}

echo "\n=== TEST COMPLETE ===\n\n";
echo "Note: Email notifications are queued. Run 'php artisan queue:work' to process them.\n\n";