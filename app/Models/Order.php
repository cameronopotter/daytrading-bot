<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'strategy_run_id',
        'client_order_id',
        'broker',
        'symbol',
        'side',
        'type',
        'qty',
        'limit_price',
        'stop_price',
        'time_in_force',
        'status',
        'broker_order_id',
        'placed_at',
        'filled_qty',
        'avg_fill_price',
        'raw',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'limit_price' => 'decimal:4',
        'stop_price' => 'decimal:4',
        'filled_qty' => 'decimal:4',
        'avg_fill_price' => 'decimal:4',
        'raw' => 'array',
        'placed_at' => 'datetime',
    ];

    public function strategyRun(): BelongsTo
    {
        return $this->belongsTo(StrategyRun::class);
    }

    public function fills(): HasMany
    {
        return $this->hasMany(Fill::class);
    }
}
