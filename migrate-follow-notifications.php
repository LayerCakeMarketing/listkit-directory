<?php
// Migrate old follow notifications to new app notifications
require_once 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Notification;
use App\Models\AppNotification;
use Illuminate\Support\Facades\DB;

echo "=== Migrating Follow Notifications ===\n\n";

// Start transaction
DB::beginTransaction();

try {
    $oldNotifications = Notification::all();
    $migrated = 0;
    $skipped = 0;

    foreach ($oldNotifications as $old) {
        // Check if already migrated
        $exists = AppNotification::where('recipient_id', $old->user_id)
            ->where('type', 'follow')
            ->where('created_at', $old->created_at)
            ->exists();

        if ($exists) {
            $skipped++;
            continue;
        }

        // Prepare the related entity data
        $relatedType = null;
        $relatedId = null;
        $actionUrl = null;
        $icon = 'user-plus';
        $color = 'blue';

        if ($old->type === 'user_follow' && isset($old->data['follower_id'])) {
            $relatedType = 'App\Models\User';
            $relatedId = $old->data['follower_id'];
            $actionUrl = '/up/@' . ($old->data['follower_custom_url'] ?? $old->data['follower_username']);
        } elseif ($old->type === 'channel_follow' && isset($old->data['channel_id'])) {
            $relatedType = 'App\Models\Channel';
            $relatedId = $old->data['channel_id'];
            $actionUrl = '/@' . $old->data['channel_slug'];
            $icon = 'tv';
            $color = 'purple';
        }

        // Create new app notification
        AppNotification::create([
            'type' => 'follow',
            'title' => $old->title,
            'message' => $old->message,
            'sender_id' => $old->data['follower_id'] ?? null,
            'recipient_id' => $old->user_id,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
            'action_url' => $actionUrl,
            'priority' => 'normal',
            'is_read' => $old->is_read,
            'read_at' => $old->read_at,
            'metadata' => [
                'icon' => $icon,
                'color' => $color,
                'original_type' => $old->type,
                'follower_data' => $old->data
            ],
            'created_at' => $old->created_at,
            'updated_at' => $old->updated_at
        ]);

        $migrated++;
        echo ".";
    }

    DB::commit();

    echo "\n\n✅ Migration complete!\n";
    echo "- Migrated: $migrated notifications\n";
    echo "- Skipped: $skipped (already migrated)\n";
    echo "\nAll follow notifications are now available in the Messages view!\n";

} catch (Exception $e) {
    DB::rollback();
    echo "\n\n❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Transaction rolled back.\n";
}