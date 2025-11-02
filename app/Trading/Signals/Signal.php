<?php

namespace App\Trading\Signals;

use App\Trading\DTO\OrderRequest;

class Signal
{
    public function __construct(
        public ?OrderRequest $order = null,
        public ?string $note = null
    ) {}

    public static function buy(string $symbol, float $qty, string $type = 'market'): self
    {
        return new self(
            order: new OrderRequest(
                symbol: $symbol,
                side: 'buy',
                type: $type,
                qty: $qty
            )
        );
    }

    public static function sell(string $symbol, float $qty, string $type = 'market'): self
    {
        return new self(
            order: new OrderRequest(
                symbol: $symbol,
                side: 'sell',
                type: $type,
                qty: $qty
            )
        );
    }

    public static function noAction(string $note = 'No action'): self
    {
        return new self(note: $note);
    }
}
