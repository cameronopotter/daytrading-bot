<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'symbol',
        'qty',
        'avg_entry_price',
        'unrealized_pl',
        'mode',
        'raw',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'avg_entry_price' => 'decimal:4',
        'unrealized_pl' => 'decimal:4',
        'raw' => 'array',
    ];
}
