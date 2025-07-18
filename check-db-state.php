<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Check if columns exist
$connection = DB::connection();
$tableName = 'lists';

echo "Checking columns in lists table:\n";
$columns = $connection->getSchemaBuilder()->getColumnListing($tableName);
echo "Columns: " . implode(', ', $columns) . "\n\n";

// Check specifically for owner columns
$hasOwnerType = in_array('owner_type', $columns);
$hasOwnerId = in_array('owner_id', $columns);

echo "Has owner_type column: " . ($hasOwnerType ? 'YES' : 'NO') . "\n";
echo "Has owner_id column: " . ($hasOwnerId ? 'YES' : 'NO') . "\n\n";

// Check migration status
echo "Migration status:\n";
$migrations = DB::table('migrations')->orderBy('batch', 'desc')->limit(10)->get();
foreach ($migrations as $migration) {
    echo "- {$migration->migration} (batch: {$migration->batch})\n";
}

// Count lists with null owner_type
echo "\nLists analysis:\n";
$totalLists = DB::table('lists')->count();
echo "Total lists: $totalLists\n";

if ($hasOwnerType) {
    $nullOwnerType = DB::table('lists')->whereNull('owner_type')->count();
    echo "Lists with NULL owner_type: $nullOwnerType\n";
    
    $userOwned = DB::table('lists')->where('owner_type', 'App\Models\User')->count();
    $channelOwned = DB::table('lists')->where('owner_type', 'App\Models\Channel')->count();
    echo "User-owned lists: $userOwned\n";
    echo "Channel-owned lists: $channelOwned\n";
}