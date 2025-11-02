<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'symbol',
        'qty',
        'avg_entry_price',
        'stop_loss',
        'take_profit',
        'trailing_stop',
        'unrealized_pl',
        'mode',
        'raw',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'avg_entry_price' => 'decimal:4',
        'stop_loss' => 'decimal:4',
        'take_profit' => 'decimal:4',
        'trailing_stop' => 'decimal:4',
        'unrealized_pl' => 'decimal:4',
        'raw' => 'array',
    ];
}
