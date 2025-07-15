<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaceFollow extends Model
{
    protected $table = 'directory_entry_follows';
    
    protected $fillable = ['user_id', 'directory_entry_id'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function place()
    {
        return $this->belongsTo(Place::class, 'directory_entry_id');
    }
}
