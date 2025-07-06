<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserListShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_id',
        'user_id',
        'shared_by',
        'permission',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the list being shared
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(UserList::class, 'list_id');
    }

    /**
     * Get the user with whom the list is shared
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who shared the list
     */
    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    /**
     * Scope to only active shares (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Check if share has edit permission
     */
    public function canEdit(): bool
    {
        return $this->permission === 'edit';
    }

    /**
     * Check if share is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}