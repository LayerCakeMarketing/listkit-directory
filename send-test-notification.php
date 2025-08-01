<?php
// Script to send a test notification
require_once 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AppNotification;
use App\Models\User;
use App\Notifications\System\SystemMessage;

echo "=== Sending Test Notifications ===\n\n";

// Method 1: Direct database creation (instant)
$user = User::where('email', 'test@example.com')->first();
if ($user) {
    $notification = AppNotification::createSystemNotification(
        $user->id,
        'Test Notification',
        'This is a test notification sent directly to the database.',
        '/dashboard',
        'high'
    );
    echo "✓ Direct notification created for {$user->email} (ID: {$notification->id})\n";
}

// Method 2: Using Laravel Notifications (supports email)
$admin = User::where('role', 'admin')->first();
if ($admin) {
    $admin->notify(new SystemMessage(
        'Admin Test',
        'This is a test notification using Laravel\'s notification system.',
        '/admin',
        'normal'
    ));
    echo "✓ Laravel notification sent to admin {$admin->email}\n";
}

// Method 3: Send to multiple users
$userIds = User::limit(3)->pluck('id');
foreach ($userIds as $userId) {
    AppNotification::createSystemNotification(
        $userId,
        'Bulk Test',
        'This is a bulk test notification.',
        '/messages'
    );
}
echo "✓ Sent bulk notifications to " . count($userIds) . " users\n";

echo "\nCheck your app to see the notifications!\n";