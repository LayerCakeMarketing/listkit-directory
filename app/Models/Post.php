<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use App\Traits\HasTags;
use App\Traits\Likeable;
use App\Traits\Commentable;
use App\Traits\Repostable;

class Post extends Model
{
    use HasFactory, SoftDeletes, HasTags, Likeable, Commentable, Repostable;

    protected $fillable = [
        'user_id',
        'content',
        'media',
        'media_type',
        'cloudflare_image_id',
        'cloudflare_video_id',
        'is_tacked',
        'tacked_at',
        'expires_in_days',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'media' => 'array',
        'metadata' => 'array',
        'is_tacked' => 'boolean',
        'tacked_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['is_expired', 'time_until_expiration', 'formatted_date', 'media_items'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set expiration date when creating
        static::creating(function ($post) {
            if ($post->expires_in_days && !$post->is_tacked) {
                $post->expires_at = Carbon::now()->addDays($post->expires_in_days);
            }
        });

        // Update expiration when tacking/untacking
        static::updating(function ($post) {
            if ($post->isDirty('is_tacked')) {
                if ($post->is_tacked) {
                    $post->tacked_at = Carbon::now();
                    $post->expires_at = null; // Tacked posts never expire
                } else {
                    $post->tacked_at = null;
                    // Re-set expiration if it should expire
                    if ($post->expires_in_days) {
                        $post->expires_at = Carbon::now()->addDays($post->expires_in_days);
                    }
                }
            }
        });

        // Clean up media when deleting
        static::deleting(function ($post) {
            if ($post->media && is_array($post->media)) {
                try {
                    $imageKitService = app(\App\Services\ImageKitService::class);
                    foreach ($post->media as $mediaItem) {
                        if (!empty($mediaItem['fileId'])) {
                            $imageKitService->deleteImage($mediaItem['fileId']);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to delete media during post deletion', [
                        'post_id' => $post->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    /**
     * Scopes
     */
    public function scopeNotExpired(Builder $query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', Carbon::now());
        });
    }

    public function scopeExpired(Builder $query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', Carbon::now());
    }

    public function scopeTacked(Builder $query)
    {
        return $query->where('is_tacked', true);
    }

    public function scopeVisible(Builder $query)
    {
        return $query->notExpired();
    }

    /**
     * Accessors
     */
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getTimeUntilExpirationAttribute()
    {
        if (!$this->expires_at || $this->is_tacked) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    public function getFormattedDateAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    public function getMediaUrlAttribute()
    {
        // For backward compatibility with single media
        if (!$this->media || empty($this->media)) {
            return null;
        }

        // If it's the old format with a single media item
        if (isset($this->media['url'])) {
            return $this->media['url'];
        }

        // If it's the new format with array of media items
        if (is_array($this->media) && isset($this->media[0]['url'])) {
            return $this->media[0]['url'];
        }

        return null;
    }

    public function getMediaItemsAttribute()
    {
        if (!$this->media || empty($this->media)) {
            return [];
        }

        // If it's the old format, convert to array
        if (isset($this->media['url'])) {
            return [$this->media];
        }

        // Return as is if it's already an array
        return $this->media;
    }

    /**
     * Methods
     */
    public function tack()
    {
        // Untack any existing tacked post for this user
        static::where('user_id', $this->user_id)
              ->where('is_tacked', true)
              ->where('id', '!=', $this->id)
              ->update(['is_tacked' => false]);

        $this->update([
            'is_tacked' => true,
            'tacked_at' => Carbon::now(),
            'expires_at' => null
        ]);
    }

    public function untack()
    {
        $this->update([
            'is_tacked' => false,
            'tacked_at' => null
        ]);

        // Re-calculate expiration if needed
        if ($this->expires_in_days) {
            $this->update([
                'expires_at' => Carbon::now()->addDays($this->expires_in_days)
            ]);
        }
    }

    /**
     * Get the default expiration days from settings
     */
    public static function getDefaultExpirationDays()
    {
        // This can be fetched from a settings table or config
        return config('app.post_expiration_days', 30);
    }
}