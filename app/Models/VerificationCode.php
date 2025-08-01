<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'verifiable_id',
        'verifiable_type',
        'type',
        'code',
        'destination',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
    ];

    /**
     * The entity this verification code belongs to
     */
    public function verifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate a new verification code
     */
    public static function generate($verifiable, string $type, string $destination): self
    {
        // Invalidate any existing codes
        static::where('verifiable_id', $verifiable->id)
            ->where('verifiable_type', get_class($verifiable))
            ->where('type', $type)
            ->whereNull('verified_at')
            ->update(['expires_at' => now()]);

        return static::create([
            'verifiable_id' => $verifiable->id,
            'verifiable_type' => get_class($verifiable),
            'type' => $type,
            'code' => static::generateCode(),
            'destination' => $destination,
            'expires_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Generate a random 6-digit code
     */
    protected static function generateCode(): string
    {
        // Use test OTP in test mode
        $testMode = config('services.claiming.test_mode');
        $testOtp = config('services.claiming.test_otp', '123456');
        
        \Log::info('Generating verification code', [
            'test_mode' => $testMode,
            'test_otp' => $testOtp,
            'env' => app()->environment(),
        ]);
        
        if ($testMode) {
            return $testOtp;
        }
        
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify the code
     */
    public function verify(string $code): bool
    {
        \Log::info('Verifying code', [
            'submitted' => $code,
            'stored' => $this->code,
            'verified_at' => $this->verified_at,
            'expires_at' => $this->expires_at,
            'expired' => $this->expires_at->isPast(),
            'attempts' => $this->attempts,
        ]);

        // Check if already verified
        if ($this->verified_at) {
            \Log::info('Code already verified');
            return false;
        }

        // Check if expired
        if ($this->expires_at->isPast()) {
            \Log::info('Code expired');
            return false;
        }

        // Check if too many attempts
        if ($this->attempts >= 5) {
            \Log::info('Too many attempts');
            return false;
        }

        // Increment attempts
        $this->increment('attempts');

        // Check if code matches
        if ($this->code !== $code) {
            \Log::info('Code mismatch', [
                'submitted' => $code,
                'stored' => $this->code,
                'match' => $this->code === $code,
            ]);
            return false;
        }

        // Mark as verified
        $this->update(['verified_at' => now()]);

        return true;
    }

    /**
     * Check if the code is valid
     */
    public function isValid(): bool
    {
        return !$this->verified_at && 
               !$this->expires_at->isPast() && 
               $this->attempts < 5;
    }

    /**
     * Scope for valid codes
     */
    public function scopeValid($query)
    {
        return $query->whereNull('verified_at')
                    ->where('expires_at', '>', now())
                    ->where('attempts', '<', 5);
    }
}
