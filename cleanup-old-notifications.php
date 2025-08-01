<?php
// Optional cleanup script - only run after verifying migration
require_once 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

echo "=== Old Notification System Cleanup ===\n\n";
echo "⚠️  WARNING: This will delete the old notifications table!\n";
echo "Make sure you've verified all notifications have been migrated.\n\n";

$oldCount = Notification::count();
echo "Old notifications table has $oldCount records.\n\n";

echo "Do you want to proceed? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim($line);

if ($answer !== 'yes') {
    echo "\nCleanup cancelled.\n";
    exit;
}

echo "\nAre you REALLY sure? Type 'DELETE' to confirm: ";
$line = fgets($handle);
$confirm = trim($line);
fclose($handle);

if ($confirm !== 'DELETE') {
    echo "\nCleanup cancelled.\n";
    exit;
}

try {
    // Option 1: Just truncate the table (keeps structure)
    DB::table('notifications')->truncate();
    echo "\n✅ Old notifications table has been truncated.\n";
    
    // Option 2: Drop the table completely (uncomment if desired)
    // Schema::dropIfExists('notifications');
    // echo "\n✅ Old notifications table has been dropped.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}