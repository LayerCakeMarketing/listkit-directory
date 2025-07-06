<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Services\CloudflareImageService;

class CloudflareImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'cloudflare_id',
        'filename',
        'user_id',
        'context',
        'entity_type',
        'entity_id',
        'metadata',
        'file_size',
        'width',
        'height',
        'mime_type',
        'uploaded_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'uploaded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded this image
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the entity this image belongs to (morphTo relationship)
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the Cloudflare image URL
     */
    public function getUrlAttribute(): string
    {
        $service = app(CloudflareImageService::class);
        return $service->getImageUrl($this->cloudflare_id);
    }

    /**
     * Get the Cloudflare thumbnail URL
     */
    public function getThumbnailAttribute(): string
    {
        $service = app(CloudflareImageService::class);
        return $service->getImageUrl($this->cloudflare_id, [
            'width' => 200,
            'height' => 200,
            'fit' => 'cover'
        ]);
    }

    /**
     * Get a custom sized image URL
     */
    public function getSizedUrl(?int $width = null, ?int $height = null, string $fit = 'cover'): string
    {
        $service = app(CloudflareImageService::class);
        $options = ['fit' => $fit];
        
        if ($width) $options['width'] = $width;
        if ($height) $options['height'] = $height;
        
        return $service->getImageUrl($this->cloudflare_id, $options);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) return 'Unknown';
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope for filtering by context
     */
    public function scopeByContext($query, string $context)
    {
        return $query->where('context', $context);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by entity
     */
    public function scopeByEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)
                    ->where('entity_id', $entityId);
    }

    /**
     * Create a new CloudflareImage record from upload response
     */
    public static function createFromUpload(array $uploadData, ?int $userId = null, ?string $context = null): self
    {
        return self::create([
            'cloudflare_id' => $uploadData['id'],
            'filename' => $uploadData['filename'] ?? 'unknown.jpg',
            'user_id' => $userId,
            'context' => $context,
            'metadata' => $uploadData,
            'file_size' => $uploadData['meta']['size'] ?? null,
            'width' => $uploadData['meta']['width'] ?? null,
            'height' => $uploadData['meta']['height'] ?? null,
            'mime_type' => $uploadData['meta']['type'] ?? null,
            'uploaded_at' => $uploadData['uploaded'] ?? now(),
        ]);
    }
}
