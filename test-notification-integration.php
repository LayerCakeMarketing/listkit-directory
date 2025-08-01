<?php
// Test the notification system integration
require_once 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AppNotification;
use App\Models\User;

echo "=== Testing Notification Integration ===\n\n";

// Count existing notifications
$totalNotifications = AppNotification::count();
$unreadCount = AppNotification::unread()->count();

echo "Current notifications:\n";
echo "- Total: $totalNotifications\n";
echo "- Unread: $unreadCount\n\n";

// Get user counts
$users = User::all();
echo "Users in system: " . $users->count() . "\n";
foreach ($users as $user) {
    $userUnread = AppNotification::where('recipient_id', $user->id)->unread()->count();
    echo "- {$user->email}: $userUnread unread\n";
}

echo "\nCreating a test notification...\n";
$testUser = User::first();
if ($testUser) {
    $notification = AppNotification::createSystemNotification(
        $testUser->id,
        'Test Integration',
        'This is a test to verify the notification bell is working correctly.',
        '/messages',
        'high'
    );
    echo "✓ Created notification ID: {$notification->id} for {$testUser->email}\n";
}

echo "\n✅ The notification bell should now show this new notification!\n";
echo "Check the app at /messages to see all notifications.\n";