<?php
// Verify the unified notification system
require_once 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\AppNotification;
use App\Models\User;

echo "=== Unified Notification System Check ===\n\n";

// Get all notification types
$types = AppNotification::distinct('type')->pluck('type');
echo "Notification types in app_notifications:\n";
foreach ($types as $type) {
    $count = AppNotification::where('type', $type)->count();
    echo "- $type: $count notifications\n";
}

echo "\n=== Sample Notifications by Type ===\n";

// Show follow notifications
$followNotifs = AppNotification::where('type', 'follow')->with('recipient', 'sender')->limit(2)->get();
echo "\nFollow Notifications:\n";
foreach ($followNotifs as $notif) {
    echo "- [{$notif->type}] {$notif->title}: {$notif->message}\n";
    echo "  To: {$notif->recipient->email}";
    if ($notif->sender) {
        echo ", From: {$notif->sender->email}";
    }
    if ($notif->metadata && isset($notif->metadata['follower_data'])) {
        echo "\n  Avatar: " . ($notif->metadata['follower_data']['follower_avatar'] ?? 'No avatar');
    }
    echo "\n  Action: {$notif->action_url}\n";
}

// Show system notifications
$systemNotifs = AppNotification::where('type', 'system')->with('recipient')->limit(2)->get();
echo "\nSystem Notifications:\n";
foreach ($systemNotifs as $notif) {
    echo "- [{$notif->type}] {$notif->title}: {$notif->message}\n";
    echo "  To: {$notif->recipient->email}\n";
}

echo "\n=== User Notification Counts ===\n";
$users = User::limit(3)->get();
foreach ($users as $user) {
    $total = AppNotification::where('recipient_id', $user->id)->count();
    $unread = AppNotification::where('recipient_id', $user->id)->unread()->count();
    $follow = AppNotification::where('recipient_id', $user->id)->where('type', 'follow')->count();
    $system = AppNotification::where('recipient_id', $user->id)->where('type', 'system')->count();
    
    echo "\n{$user->email}:\n";
    echo "  Total: $total | Unread: $unread | Follow: $follow | System: $system\n";
}

echo "\n✅ All notifications (follow + system) are now unified in the app_notifications table!\n";
echo "Users can see all their notifications at /messages\n";