<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Claim extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'user_id',
        'status',
        'tier',
        'stripe_payment_intent_id',
        'stripe_payment_status',
        'payment_amount',
        'payment_completed_at',
        'stripe_subscription_id',
        'subscription_starts_at',
        'verification_method',
        'business_email',
        'business_phone',
        'verification_notes',
        'rejection_reason',
        'verified_at',
        'approved_at',
        'rejected_at',
        'expires_at',
        'approved_by',
        'rejected_by',
        'metadata',
        'verification_fee_paid',
        'fee_kept',
        'fee_payment_intent_id',
        'fee_refunded_at',
        'fee_refund_id',
        'verification_fee_amount',
    ];

    protected $casts = [
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'expires_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'subscription_starts_at' => 'datetime',
        'payment_amount' => 'decimal:2',
        'verification_fee_paid' => 'boolean',
        'fee_kept' => 'boolean',
        'fee_refunded_at' => 'datetime',
        'verification_fee_amount' => 'decimal:2',
    ];

    /**
     * Constants for claim statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    /**
     * Constants for verification methods
     */
    const METHOD_EMAIL = 'email';
    const METHOD_PHONE = 'phone';
    const METHOD_DOCUMENT = 'document';
    const METHOD_MANUAL = 'manual';

    /**
     * Constants for tier types
     */
    const TIER_FREE = 'free';
    const TIER_1 = 'tier1';
    const TIER_2 = 'tier2';

    /**
     * Tier pricing in cents
     */
    const TIER_PRICES = [
        self::TIER_FREE => 0,
        self::TIER_1 => 999, // $9.99
        self::TIER_2 => 1999, // $19.99
    ];

    /**
     * Tier display names
     */
    const TIER_NAMES = [
        self::TIER_FREE => 'Free',
        self::TIER_1 => 'Professional',
        self::TIER_2 => 'Premium',
    ];

    /**
     * Verification fee amount in cents
     */
    const VERIFICATION_FEE = 599; // $5.99

    /**
     * The place being claimed
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class, 'place_id');
    }

    /**
     * The user making the claim
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who approved the claim
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * The admin who rejected the claim
     */
    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Documents uploaded for this claim
     */
    public function documents(): HasMany
    {
        return $this->hasMany(ClaimDocument::class);
    }

    /**
     * Verification codes for this claim
     */
    public function verificationCodes(): MorphMany
    {
        return $this->morphMany(VerificationCode::class, 'verifiable');
    }

    /**
     * Check if claim is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if claim is approved
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if claim is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               ($this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Approve the claim
     */
    public function approve(User $approvedBy): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedBy->id,
        ]);

        // Update the place ownership
        $this->place->update([
            'owner_user_id' => $this->user_id,
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);
    }

    /**
     * Reject the claim
     */
    public function reject(User $rejectedBy, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_by' => $rejectedBy->id,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Scope for pending claims
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for expired claims
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
              ->orWhere(function ($q2) {
                  $q2->where('status', self::STATUS_PENDING)
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
              });
        });
    }

    /**
     * Check if verification fee should be refunded
     */
    public function shouldRefundFee(): bool
    {
        return $this->verification_fee_paid && 
               !$this->fee_kept && 
               !$this->fee_refunded_at &&
               in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }

    /**
     * Mark fee as refunded
     */
    public function markFeeRefunded(string $refundId): void
    {
        $this->update([
            'fee_refunded_at' => now(),
            'fee_refund_id' => $refundId,
        ]);
    }

    /**
     * Get the display status for the claim
     */
    public function getDisplayStatus(): string
    {
        if ($this->status === self::STATUS_PENDING && !$this->verified_at) {
            return 'Awaiting Verification';
        }
        
        if ($this->status === self::STATUS_PENDING && $this->verified_at) {
            return 'Under Review';
        }
        
        return ucfirst($this->status);
    }
}
