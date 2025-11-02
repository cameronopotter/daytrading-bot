<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskLimit extends Model
{
    protected $fillable = [
        'daily_max_loss',
        'max_position_qty',
        'max_orders_per_min',
        'mode',
    ];

    protected $casts = [
        'daily_max_loss' => 'decimal:4',
        'max_position_qty' => 'decimal:4',
        'max_orders_per_min' => 'integer',
    ];
}
