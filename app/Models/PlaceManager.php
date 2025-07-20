<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PlaceManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'place_id',
        'manageable_type',
        'manageable_id',
        'role',
        'permissions',
        'is_active',
        'accepted_at',
        'invited_at',
        'invited_by',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
        'accepted_at' => 'datetime',
        'invited_at' => 'datetime',
    ];

    /**
     * Get the place that is being managed
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Get the manageable entity (User, Team, etc.)
     */
    public function manageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who invited this manager
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Scope to active managers only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to accepted invitations only
     */
    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }

    /**
     * Check if this is an owner
     */
    public function isOwner(): bool
    {
        return in_array($this->role, ['owner', 'claimed_owner']);
    }

    /**
     * Check if this is a manager
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if the manager has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Owners have all permissions
        if ($this->isOwner()) {
            return true;
        }

        // Check specific permissions
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Accept the invitation
     */
    public function accept(): void
    {
        $this->update(['accepted_at' => now()]);
    }
}