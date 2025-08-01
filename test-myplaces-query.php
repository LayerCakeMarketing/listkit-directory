<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Place;
use App\Models\User;

// Set the user ID we're testing with
$userId = 2;
$user = User::find($userId);

echo "Testing myPlaces query for user ID: {$userId}\n";
echo "User name: {$user->firstname} {$user->lastname}\n\n";

// First, let's check what places exist for this user
echo "=== Direct query for created places ===\n";
$createdPlaces = Place::where('created_by_user_id', $userId)->count();
echo "Total places created by user: {$createdPlaces}\n";

// Let's get some details
$samplePlaces = Place::where('created_by_user_id', $userId)
    ->select('id', 'title', 'status', 'created_by_user_id')
    ->limit(5)
    ->get();

echo "\nSample places:\n";
foreach ($samplePlaces as $place) {
    echo "ID: {$place->id}, Title: {$place->title}, Status: {$place->status}, Created by: {$place->created_by_user_id}\n";
}

// Now let's replicate the exact query from myPlaces method
echo "\n=== Replicating myPlaces query ===\n";

// Start with base query (exactly as in myPlaces)
$query = Place::with([
    'category:id,name,slug',
    'region:id,name,slug,type',
    'stateRegion:id,name,slug',
    'cityRegion:id,name,slug',
    'claims' => function($q) use ($user) {
        $q->where('user_id', $user->id)
          ->whereIn('status', ['pending', 'approved'])
          ->latest();
    }
]);

// Apply the "all" filter (default)
$query->where(function($q) use ($user) {
    $q->where('created_by_user_id', $user->id)
      ->orWhereHas('claims', function($subQ) use ($user) {
          $subQ->where('user_id', $user->id)
               ->whereIn('status', ['pending', 'approved']);
      });
});

// Check the SQL being generated
echo "Generated SQL:\n";
echo $query->toSql() . "\n";
echo "Bindings: " . json_encode($query->getBindings()) . "\n\n";

// Execute the query
$results = $query->count();
echo "Results count: {$results}\n";

// Let's also check if there are any global scopes applied
echo "\n=== Checking for global scopes ===\n";
$model = new Place();
$globalScopes = $model->getGlobalScopes();
echo "Global scopes: " . json_encode(array_keys($globalScopes)) . "\n";

// Let's check if the published() scope is being applied somewhere
echo "\n=== Testing without any scopes ===\n";
$rawQuery = \DB::table('directory_entries')
    ->where('created_by_user_id', $userId)
    ->count();
echo "Raw query count: {$rawQuery}\n";

// Check if status filtering is the issue
echo "\n=== Checking status distribution ===\n";
$statusCounts = Place::where('created_by_user_id', $userId)
    ->groupBy('status')
    ->selectRaw('status, count(*) as count')
    ->pluck('count', 'status');
echo "Status distribution: " . json_encode($statusCounts) . "\n";