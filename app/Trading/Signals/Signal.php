<?php

namespace App\Trading\Signals;

use App\Trading\DTO\OrderRequest;

class Signal
{
    public function __construct(
        public ?OrderRequest $order = null,
        public ?string $note = null,
        public ?float $stopLoss = null,
        public ?float $takeProfit = null,
        public ?string $reason = null
    ) {}

    public static function buy(
        string $symbol,
        float $qty,
        string $type = 'market',
        ?float $stopLoss = null,
        ?float $takeProfit = null
    ): self {
        return new self(
            order: new OrderRequest(
                symbol: $symbol,
                side: 'buy',
                type: $type,
                qty: $qty
            ),
            stopLoss: $stopLoss,
            takeProfit: $takeProfit
        );
    }

    public static function sell(
        string $symbol,
        float $qty,
        string $type = 'market',
        ?string $reason = null
    ): self {
        return new self(
            order: new OrderRequest(
                symbol: $symbol,
                side: 'sell',
                type: $type,
                qty: $qty
            ),
            reason: $reason
        );
    }

    public static function noAction(string $note = 'No action'): self
    {
        return new self(note: $note);
    }
}
