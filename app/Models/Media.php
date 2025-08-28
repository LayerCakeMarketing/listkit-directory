<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cloudflare_id',
        'url',
        'filename',
        'mime_type',
        'file_size',
        'width',
        'height',
        'entity_type',
        'entity_id',
        'user_id',
        'context',
        'metadata',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'entity_id' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'is_image',
        'thumbnail_url',
        'medium_url',
        'large_url',
        'formatted_file_size',
        'entity_name'
    ];

    /**
     * Get the parent entity model.
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded the media.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to filter by context.
     */
    public function scopeByContext($query, ?string $context)
    {
        if ($context) {
            return $query->where('context', $context);
        }
        return $query;
    }

    /**
     * Scope to filter by entity.
     */
    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get the Cloudflare delivery URL with variant.
     */
    public function getUrl(string $variant = 'public'): string
    {
        $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
        return "{$deliveryUrl}/{$this->cloudflare_id}/{$variant}";
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): string
    {
        return $this->getUrl('thumbnail');
    }

    /**
     * Get medium URL.
     */
    public function getMediumUrlAttribute(): string
    {
        return $this->getUrl('medium');
    }

    /**
     * Get large URL.
     */
    public function getLargeUrlAttribute(): string
    {
        return $this->getUrl('large');
    }

    /**
     * Check if media is an image.
     */
    public function getIsImageAttribute(): bool
    {
        if (!$this->mime_type) {
            return false;
        }
        
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get entity name for display.
     */
    public function getEntityNameAttribute(): string
    {
        if (!$this->entity_type) {
            return 'None';
        }

        $className = class_basename($this->entity_type);
        
        return match($className) {
            'User' => 'User Profile',
            'UserList' => 'List',
            'Channel' => 'Channel',
            'Place', 'DirectoryEntry' => 'Place',
            'Post' => 'Post',
            'Region' => 'Region',
            default => $className
        };
    }
}