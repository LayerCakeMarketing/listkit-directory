<?php
// Test script to check notifications
require_once 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AppNotification;
use App\Models\User;

echo "=== Checking Notifications ===\n\n";

// Check total notifications
$totalNotifications = AppNotification::count();
echo "Total notifications in database: {$totalNotifications}\n\n";

// Check recent notifications
$recentNotifications = AppNotification::orderBy('created_at', 'desc')->limit(5)->get();
echo "Recent notifications:\n";
foreach ($recentNotifications as $notification) {
    echo "- ID: {$notification->id}, Type: {$notification->type}, Title: {$notification->title}\n";
    echo "  Recipient: User #{$notification->recipient_id}, Created: {$notification->created_at}\n";
    echo "  Read: " . ($notification->read_at ? 'Yes' : 'No') . "\n\n";
}

// Check unread notifications for first user
$firstUser = User::first();
if ($firstUser) {
    $unreadCount = $firstUser->appNotifications()->unread()->count();
    echo "Unread notifications for {$firstUser->email}: {$unreadCount}\n";
}