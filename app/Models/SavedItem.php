<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SavedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'saveable_id',
        'saveable_type',
        'notes',
    ];

    protected $with = ['saveable'];

    /**
     * Get the owning saveable model (Place, UserList, or Region).
     */
    public function saveable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the saved item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get readable type name
     */
    public function getTypeAttribute(): string
    {
        return match($this->saveable_type) {
            'App\Models\Place' => 'place',
            'App\Models\UserList' => 'list',
            'App\Models\Region' => 'region',
            default => 'unknown'
        };
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, $type)
    {
        $typeMap = [
            'place' => Place::class,
            'list' => UserList::class,
            'region' => Region::class,
        ];

        if (isset($typeMap[$type])) {
            return $query->where('saveable_type', $typeMap[$type]);
        }

        return $query;
    }
}
