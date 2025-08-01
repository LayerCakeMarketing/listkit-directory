<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

try {
    $count = User::count();
    echo "User count: $count\n";
    
    // Get first user
    $user = User::first();
    if ($user) {
        echo "First user email: {$user->email}\n";
        echo "Has firstname: " . ($user->firstname ? 'Yes' : 'No') . "\n";
        echo "Has lastname: " . ($user->lastname ? 'Yes' : 'No') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}