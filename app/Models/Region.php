<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'parent_id',
    ];

    // Relationships
    public function parent()
    {
        return $this->belongsTo(Region::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Region::class, 'parent_id');
    }

    public function directoryEntries()
    {
        return $this->hasMany(DirectoryEntry::class);
    }

    // Scopes
    public function scopeStates($query)
    {
        return $query->where('type', 'state');
    }

    public function scopeCities($query)
    {
        return $query->where('type', 'city');
    }

    public function scopeCounties($query)
    {
        return $query->where('type', 'county');
    }

    public function scopeRootRegions($query)
    {
        return $query->whereNull('parent_id');
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
            return $this->name . ', ' . $this->parent->name;
        }
        return $this->name;
    }

    // Get hierarchical path
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

    // Get all descendant regions
    public function descendants()
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }
        
        return $descendants;
    }
}