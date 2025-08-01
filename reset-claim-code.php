<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Claim;

$claimId = $argv[1] ?? null;
if (!$claimId) {
    echo "Usage: php reset-claim-code.php <claim_id>\n";
    exit;
}

$claim = Claim::find($claimId);
if (!$claim) {
    echo "Claim not found\n";
    exit;
}

// Update the latest verification code to use test OTP
$code = $claim->verificationCodes()->latest()->first();
if ($code) {
    $code->update([
        'code' => '123456',
        'attempts' => 0,
        'expires_at' => now()->addMinutes(30)
    ]);
    echo "Reset verification code for claim #{$claimId} to: 123456\n";
    echo "Code expires at: " . $code->expires_at . "\n";
} else {
    echo "No verification code found for claim #{$claimId}\n";
}