<?php
// Check old notifications
require_once 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Notification;
use App\Models\AppNotification;

echo "=== Old Notifications (Follow System) ===\n\n";

$oldNotifications = Notification::with('user')->latest()->limit(5)->get();
echo "Sample old notifications:\n";
foreach ($oldNotifications as $notif) {
    echo "- [{$notif->type}] {$notif->message} (User: {$notif->user->email}, Created: {$notif->created_at})\n";
}

echo "\nTotal old notifications: " . Notification::count() . "\n";
echo "Unread old notifications: " . Notification::unread()->count() . "\n";

echo "\n=== New Notifications (App System) ===\n\n";
$newNotifications = AppNotification::with('recipient')->latest()->limit(5)->get();
echo "Sample new notifications:\n";
foreach ($newNotifications as $notif) {
    echo "- [{$notif->type}] {$notif->title}: {$notif->message} (User: {$notif->recipient->email}, Created: {$notif->created_at})\n";
}

echo "\nTotal new notifications: " . AppNotification::count() . "\n";
echo "Unread new notifications: " . AppNotification::unread()->count() . "\n";