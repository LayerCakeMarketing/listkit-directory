<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'likeable_type',
        'likeable_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the owning likeable model (post, list, place, comment).
     */
    public function likeable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that created the like.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include likes by a specific user.
     */
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    /**
     * Scope a query to only include likes for a specific model type.
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('likeable_type', $type);
    }
}