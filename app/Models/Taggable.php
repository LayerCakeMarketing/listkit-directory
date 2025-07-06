<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Taggable extends Model
{
    use HasFactory;

    protected $fillable = [
        'tag_id',
        'taggable_id',
        'taggable_type',
    ];

    // Relationships
    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function taggable()
    {
        return $this->morphTo();
    }
}
