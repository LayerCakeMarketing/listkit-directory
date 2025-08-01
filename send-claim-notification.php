<?php
// Script to send claim notifications
require_once 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Claim;
use App\Models\AppNotification;
use App\Notifications\System\ClaimStatusUpdate;

echo "=== Sending Claim Notifications ===\n\n";

// Get a claim
$claim = Claim::with(['user', 'place'])->first();

if ($claim) {
    echo "Found claim #{$claim->id} for {$claim->place->title} by {$claim->user->email}\n\n";
    
    // Method 1: Using the Notification class
    try {
        $claim->user->notify(new ClaimStatusUpdate($claim, 'approved'));
        echo "✓ Sent 'approved' notification using Laravel Notifications\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
    // Method 2: Direct database creation
    $notification = AppNotification::createClaimNotification($claim, 'pending_review');
    echo "✓ Created 'pending_review' notification directly (ID: {$notification->id})\n";
    
    // With rejection reason
    $notification2 = AppNotification::createClaimNotification($claim, 'rejected', 'Documents do not match business records');
    echo "✓ Created 'rejected' notification with reason (ID: {$notification2->id})\n";
} else {
    echo "No claims found. Create a claim first to test claim notifications.\n";
}

echo "\nNotifications created! Check the app.\n";