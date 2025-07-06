<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_type',
        'subject_type',
        'subject_id',
        'metadata',
        'is_public'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function getActivityDescription()
    {
        switch ($this->activity_type) {
            case 'created_list':
                return 'created a new list';
            case 'followed_user':
                return 'started following';
            case 'followed_entry':
                return 'started following';
            case 'updated_profile':
                return 'updated their profile';
            case 'pinned_list':
                return 'pinned a list';
            default:
                return $this->activity_type;
        }
    }
}