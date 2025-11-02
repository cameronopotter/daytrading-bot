<?php

namespace App\Trading;

use App\Trading\DTO\OrderRequest;

interface BrokerAdapter
{
    public function getAccount(): array;

    public function getPositions(): array;

    public function placeOrder(OrderRequest $o): array; // returns normalized order

    public function cancelOrder(string $brokerOrderId): void;

    public function closeAllPositions(): void;
}
