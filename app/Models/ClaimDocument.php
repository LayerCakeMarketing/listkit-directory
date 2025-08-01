<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ClaimDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_id',
        'document_type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'status',
        'notes',
        'verified_at',
        'verified_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Document types
     */
    const TYPE_BUSINESS_LICENSE = 'business_license';
    const TYPE_TAX_DOCUMENT = 'tax_document';
    const TYPE_UTILITY_BILL = 'utility_bill';
    const TYPE_INCORPORATION = 'incorporation';
    const TYPE_OTHER = 'other';

    /**
     * Document statuses
     */
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    /**
     * The claim this document belongs to
     */
    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    /**
     * The user who verified this document
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the document URL
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get human readable file size
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get available document types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_BUSINESS_LICENSE => 'Business License',
            self::TYPE_TAX_DOCUMENT => 'Tax Document',
            self::TYPE_UTILITY_BILL => 'Utility Bill',
            self::TYPE_INCORPORATION => 'Articles of Incorporation',
            self::TYPE_OTHER => 'Other',
        ];
    }

    /**
     * Verify the document
     */
    public function verify(User $verifiedBy, string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_at' => now(),
            'verified_by' => $verifiedBy->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Reject the document
     */
    public function reject(User $verifiedBy, string $notes): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'verified_at' => now(),
            'verified_by' => $verifiedBy->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Delete the file when the model is deleted
     */
    protected static function booted()
    {
        static::deleting(function ($document) {
            Storage::delete($document->file_path);
        });
    }
}
