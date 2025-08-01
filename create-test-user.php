<?php
require_once __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

try {
    $user = User::create([
        'firstname' => 'Test',
        'lastname' => 'User',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
        'username' => 'testuser',
        'email_verified_at' => now(),
    ]);
    
    echo "User created successfully!\n";
    echo "Email: test@example.com\n";
    echo "Password: password\n";
} catch (Exception $e) {
    echo "Error creating user: " . $e->getMessage() . "\n";
}