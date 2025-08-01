<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Claim;
use App\Models\VerificationCode;

$claimId = $argv[1] ?? 2;

$claim = Claim::find($claimId);
if (!$claim) {
    echo "Claim not found\n";
    exit;
}

echo "Claim #{$claimId} Details:\n";
echo "User ID: {$claim->user_id}\n";
echo "Place ID: {$claim->place_id}\n";
echo "Method: {$claim->verification_method}\n";
echo "Status: {$claim->status}\n";
echo "Verified At: " . ($claim->verified_at ?: 'Not verified') . "\n";
echo "\n";

$codes = $claim->verificationCodes()->get();
echo "Verification Codes (" . $codes->count() . "):\n";
foreach ($codes as $code) {
    echo "  - Code: {$code->code}\n";
    echo "    Type: {$code->type}\n";
    echo "    Attempts: {$code->attempts}\n";
    echo "    Expires: {$code->expires_at}\n";
    echo "    Expired: " . ($code->expires_at->isPast() ? 'Yes' : 'No') . "\n";
    echo "    Verified: " . ($code->verified_at ?: 'No') . "\n";
    echo "\n";
}

echo "Config:\n";
echo "Test Mode: " . (config('services.claiming.test_mode') ? 'Enabled' : 'Disabled') . "\n";
echo "Test OTP: " . config('services.claiming.test_otp') . "\n";