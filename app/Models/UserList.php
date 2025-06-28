<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserList extends Model
{
    use HasFactory;

    protected $table = 'lists';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'featured_image',
        'is_public',
        'visibility',
        'view_count',
        'settings'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'view_count' => 'integer',
        'settings' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($list) {
            if (empty($list->slug)) {
                $list->slug = Str::slug($list->name);
                
                // Handle duplicate slugs
                $count = static::where('slug', 'like', $list->slug . '%')
                              ->where('user_id', $list->user_id)
                              ->count();
                if ($count > 0) {
                    $list->slug = $list->slug . '-' . ($count + 1);
                }
            }
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(ListItem::class, 'list_id')->orderBy('order_index');
    }

    public function directoryEntries()
    {
        return $this->belongsToMany(DirectoryEntry::class, 'list_items')
                    ->withPivot('order_index', 'affiliate_url', 'notes')
                    ->orderBy('pivot_order_index')
                    ->withTimestamps();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'list_id');
    }

    // public function media()
    // {
    //     return $this->hasMany(ListMedia::class, 'list_id')->orderBy('order_index');
    // }

    // Visibility scopes
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    public function scopeUnlisted($query)
    {
        return $query->where('visibility', 'unlisted');
    }

    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    public function scopeViewableByGuests($query)
    {
        return $query->whereIn('visibility', ['public', 'unlisted']);
    }

    public function scopeSearchable($query)
    {
        return $query->where('visibility', 'public');
    }

    // Helper methods
    public function isPublic()
    {
        return $this->visibility === 'public';
    }

    public function isUnlisted()
    {
        return $this->visibility === 'unlisted';
    }

    public function isPrivate()
    {
        return $this->visibility === 'private';
    }

    public function isViewableByGuests()
    {
        return in_array($this->visibility, ['public', 'unlisted']);
    }

    // Additional scopes

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Helper methods
    public function isOwnedBy($user)
    {
        if (!$user) return false;
        return $this->user_id === $user->id;
    }

    public function canEdit($user = null)
    {
        $user = $user ?? auth()->user();
        if (!$user) return false;
        
        // Owner can always edit
        if ($this->isOwnedBy($user)) return true;
        
        // Admin and manager can edit any list
        if (in_array($user->role, ['admin', 'manager'])) return true;
        
        return false;
    }

    public function incrementViewCount()
    {
        $this->increment('view_count');
    }
}