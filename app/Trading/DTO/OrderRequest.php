<?php

namespace App\Trading\DTO;

class OrderRequest
{
    public function __construct(
        public string $symbol,
        public string $side,         // buy|sell
        public string $type,         // market|limit|stop|stop_limit
        public float $qty,
        public ?float $limit = null,
        public ?float $stop = null,
        public string $tif = 'day',
        public ?string $clientId = null
    ) {}
}
