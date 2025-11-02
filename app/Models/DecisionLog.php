<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecisionLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'strategy_run_id',
        'level',
        'context',
        'message',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function strategyRun(): BelongsTo
    {
        return $this->belongsTo(StrategyRun::class);
    }
}
