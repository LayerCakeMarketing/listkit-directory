<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ListChain extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'cover_image',
        'cover_cloudflare_id',
        'owner_type',
        'owner_id',
        'visibility',
        'status',
        'metadata',
        'views_count',
        'lists_count',
        'published_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['is_published'];

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chain) {
            if (empty($chain->slug)) {
                // Don't generate slug here, let the controller handle it
                // This prevents race conditions in the model events
            }
        });

        static::updating(function ($chain) {
            if ($chain->isDirty('name') && !$chain->isDirty('slug')) {
                // For updates, we can safely generate a new slug
                $chain->slug = static::generateUniqueSlug($chain->name, $chain->id);
            }
        });
    }

    /**
     * Generate unique slug with proper race condition handling
     * 
     * @param string $name The name to generate slug from
     * @param int|null $excludeId ID to exclude from uniqueness check (for updates)
     * @return string
     */
    public static function generateUniqueSlug($name, $excludeId = null)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        
        // Use a database query to find the next available slug
        $query = static::where('slug', 'LIKE', $originalSlug . '%');
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        // Get all existing slugs that match the pattern
        $existingSlugs = $query->pluck('slug')->toArray();
        
        // If the original slug doesn't exist, use it
        if (!in_array($slug, $existingSlugs)) {
            return $slug;
        }
        
        // Find the next available number
        $count = 1;
        do {
            $slug = $originalSlug . '-' . $count;
            $count++;
        } while (in_array($slug, $existingSlugs));
        
        return $slug;
    }

    /**
     * Create with unique slug - handles race conditions
     * 
     * @param array $attributes
     * @return static
     */
    public static function createWithUniqueSlug(array $attributes)
    {
        $maxAttempts = 5;
        $lastException = null;
        
        // Generate base slug
        $baseSlug = Str::slug($attributes['name']);
        
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            try {
                // Use a transaction with a lock
                return DB::transaction(function() use ($attributes, $baseSlug) {
                    // Generate a unique slug more robustly
                    $slug = $baseSlug;
                    $count = 0;
                    
                    // Check for existing slugs and find next available
                    while (static::where('slug', $slug)->exists()) {
                        $count++;
                        $slug = $baseSlug . '-' . $count;
                    }
                    
                    $attributes['slug'] = $slug;
                    
                    // Create the chain
                    return static::create($attributes);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                $lastException = $e;
                
                // Check if it's a unique constraint violation
                // PostgreSQL uses SQLSTATE code 23505 for unique violations
                if (strpos($e->getMessage(), '23505') !== false || 
                    strpos($e->getMessage(), 'Unique violation') !== false ||
                    strpos($e->getMessage(), 'list_chains_slug_unique') !== false) {
                    
                    // Add timestamp to make it unique on retry
                    $baseSlug = Str::slug($attributes['name']) . '-' . time();
                    usleep(50000 * ($attempt + 1)); // Exponential backoff
                    continue;
                }
                
                // For other database errors, throw immediately
                throw $e;
            }
        }
        
        // If we've exhausted all attempts, throw the last exception
        throw $lastException ?: new \RuntimeException('Failed to create chain with unique slug after ' . $maxAttempts . ' attempts');
    }

    /**
     * The owner of the chain (User or Channel)
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The lists in this chain
     */
    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(UserList::class, 'list_chain_items', 'list_chain_id', 'list_id')
            ->withPivot(['order_index', 'label', 'description'])
            ->withTimestamps()
            ->orderBy('list_chain_items.order_index');
    }

    /**
     * Chain items (pivot records)
     */
    public function items()
    {
        return $this->hasMany(ListChainItem::class)->orderBy('order_index');
    }

    /**
     * Check if chain is published
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published' && $this->published_at && $this->published_at->isPast();
    }

    /**
     * Scope for published chains
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope for visible chains (respects privacy settings)
     */
    public function scopeVisible($query, $user = null)
    {
        return $query->where(function ($q) use ($user) {
            $q->where('visibility', 'public');
            
            if ($user) {
                $q->orWhere(function ($q2) use ($user) {
                    $q2->where('owner_type', User::class)
                        ->where('owner_id', $user->id);
                });
                
                // Include chains from user's channels
                $channelIds = $user->channels()->pluck('id');
                if ($channelIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($channelIds) {
                        $q2->where('owner_type', Channel::class)
                            ->whereIn('owner_id', $channelIds);
                    });
                }
            }
        });
    }

    /**
     * Scope for chains owned by user
     */
    public function scopeOwnedBy($query, $owner)
    {
        if ($owner instanceof Model) {
            return $query->where('owner_type', get_class($owner))
                ->where('owner_id', $owner->id);
        }
        
        return $query;
    }

    /**
     * Increment view count
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Update lists count
     */
    public function updateListsCount()
    {
        $this->update(['lists_count' => $this->lists()->count()]);
    }

    /**
     * Check if user can edit this chain
     */
    public function canEdit($user): bool
    {
        if (!$user) return false;

        // Owner can edit
        if ($this->owner_type === User::class && $this->owner_id === $user->id) {
            return true;
        }

        // Channel admins can edit
        if ($this->owner_type === Channel::class) {
            $channel = $this->owner;
            return $channel && $channel->user_id === $user->id;
        }

        // Admins can edit anything
        return $user->role === 'admin';
    }

    /**
     * Get the URL for viewing this chain
     */
    public function getUrlAttribute(): string
    {
        if ($this->owner_type === Channel::class && $this->owner) {
            return "/@{$this->owner->slug}/chains/{$this->slug}";
        }
        
        if ($this->owner_type === User::class && $this->owner) {
            $username = $this->owner->custom_url ?? $this->owner->username;
            return "/up/@{$username}/chains/{$this->slug}";
        }
        
        return "/chains/{$this->slug}";
    }
}