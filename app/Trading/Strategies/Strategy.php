<?php

namespace App\Trading\Strategies;

use App\Trading\Signals\Signal;

interface Strategy
{
    public function onBar(array $bar, array $state): ?Signal;

    public function name(): string;
}
