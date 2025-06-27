<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'icon',
        'order_index',
    ];

    protected $casts = [
        'order_index' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
            
            // Handle duplicate slugs
            $count = static::where('slug', 'like', $category->slug . '%')->count();
            if ($count > 0) {
                $category->slug = $category->slug . '-' . ($count + 1);
            }
        });
    }

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order_index');
    }

    public function directoryEntries()
    {
        return $this->hasMany(DirectoryEntry::class);
    }

    // Scopes
    public function scopeRootCategories($query)
    {
        return $query->whereNull('parent_id')->orderBy('order_index');
    }

    public function scopeWithChildren($query)
    {
        return $query->with(['children' => function ($q) {
            $q->orderBy('order_index');
        }]);
    }

    // Helper methods
    public function isRoot()
    {
        return is_null($this->parent_id);
    }

    public function hasChildren()
    {
        return $this->children()->exists();
    }

    public function getFullNameAttribute()
    {
        if ($this->parent) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }

    public function getPathAttribute()
    {
        $path = collect([$this]);
        $parent = $this->parent;
        
        while ($parent) {
            $path->prepend($parent);
            $parent = $parent->parent;
        }
        
        return $path;
    }

    // Get all descendant categories
    public function descendants()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }
        
        return $descendants;
    }

    // Get count of all entries in this category and its children
    public function getTotalEntriesCountAttribute()
    {
        $count = $this->directory_entries_count ?? 0;
        
        foreach ($this->children as $child) {
            $count += $child->total_entries_count;
        }
        
        return $count;
    }
}