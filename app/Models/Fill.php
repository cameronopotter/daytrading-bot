<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fill extends Model
{
    protected $fillable = [
        'order_id',
        'symbol',
        'qty',
        'price',
        'side',
        'fill_at',
        'raw',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'price' => 'decimal:4',
        'fill_at' => 'datetime',
        'raw' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
