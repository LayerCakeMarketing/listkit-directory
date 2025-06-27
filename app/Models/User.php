<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'username', 'custom_url', 'role', 'bio',
        'avatar', 'cover_image', 'social_links', 'preferences',
        'permissions', 'is_public'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'social_links' => 'array',
        'preferences' => 'array',
        'permissions' => 'array',
        'is_public' => 'boolean',
        'last_active_at' => 'datetime',
    ];


    // Role-based permissions
    public function hasRole($role)
    {
        return $this->role === $role;
    }

    public function hasAnyRole($roles)
    {
        return in_array($this->role, $roles);
    }

    public function canManageContent()
    {
        return in_array($this->role, ['admin', 'manager', 'editor']);
    }

    public function canPublishContent()
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function canModerateComments()
    {
        return in_array($this->role, ['admin', 'manager', 'editor']);
    }

    public function canManageUsers()
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function canClaimBusinesses()
    {
        return in_array($this->role, ['admin', 'manager', 'business_owner', 'user']);
    }

    // Relationships
    public function createdEntries()
    {
        return $this->hasMany(DirectoryEntry::class, 'created_by_user_id');
    }

    public function ownedEntries()
    {
        return $this->hasMany(DirectoryEntry::class, 'owner_user_id');
    }

    public function lists()
    {
        return $this->hasMany(UserList::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    // Helper methods
    public function getProfileUrlAttribute()
    {
        return route('user.profile', ['username' => $this->username]);
    }

    public function getUrlSlug()
    {
        return $this->custom_url ?: $this->username ?: $this->id;
    }

    public function getPublicListsUrl()
    {
        $slug = $this->getUrlSlug();
        return $slug ? "/{$slug}/lists" : "/user/{$this->id}/lists";
    }

    public function getListUrl($listSlug)
    {
        $userSlug = $this->getUrlSlug();
        return $userSlug ? "/{$userSlug}/{$listSlug}" : "/user/{$this->id}/{$listSlug}";
    }

    public static function findByUrlSlug($slug)
    {
        $query = static::where('custom_url', $slug)
                      ->orWhere('username', $slug);
        
        // Only check ID if the slug is numeric
        if (is_numeric($slug)) {
            $query->orWhere('id', $slug);
        }
        
        return $query->first();
    }

   // User Last Active
    public function updateLastActive()
    {
        $this->last_active_at = now();
        $this->saveQuietly(); 
    }
}