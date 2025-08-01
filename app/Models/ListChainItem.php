<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListChainItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_chain_id',
        'list_id',
        'order_index',
        'label',
        'description'
    ];

    protected $casts = [
        'order_index' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * The chain this item belongs to
     */
    public function chain(): BelongsTo
    {
        return $this->belongsTo(ListChain::class, 'list_chain_id');
    }

    /**
     * The list in this chain item
     */
    public function list(): BelongsTo
    {
        return $this->belongsTo(UserList::class, 'list_id');
    }
}
