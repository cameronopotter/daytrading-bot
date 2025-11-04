<?php

namespace Tests\Unit;

use App\Trading\Signals\Signal;
use App\Trading\Strategies\SMA;
use Tests\TestCase;

class SMAStrategyTest extends TestCase
{
    public function test_strategy_name()
    {
        $config = [
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 3,
            'slow' => 5,
        ];

        $strategy = new SMA($config);

        $this->assertEquals('SMA(3/5)', $strategy->name());
    }

    public function test_no_signal_when_insufficient_data()
    {
        $config = [
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 3,
            'slow' => 5,
        ];

        $strategy = new SMA($config);

        $bar1 = ['symbol' => 'AAPL', 'close' => 100.00];
        $bar2 = ['symbol' => 'AAPL', 'close' => 101.00];

        $signal1 = $strategy->onBar($bar1, []);
        $signal2 = $strategy->onBar($bar2, []);

        $this->assertNull($signal1->order);
        $this->assertNull($signal2->order);
    }

    public function test_buy_signal_on_cross_up()
    {
        $config = [
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 2,
            'slow' => 4,
        ];

        $strategy = new SMA($config);

        $bars = [
            ['symbol' => 'AAPL', 'close' => 100.00],
            ['symbol' => 'AAPL', 'close' => 99.00],
            ['symbol' => 'AAPL', 'close' => 98.00],
            ['symbol' => 'AAPL', 'close' => 97.00],
            ['symbol' => 'AAPL', 'close' => 105.00],
        ];

        $signal = null;
        foreach ($bars as $bar) {
            $signal = $strategy->onBar($bar, []);
        }

        $this->assertNotNull($signal->order);
        $this->assertEquals('AAPL', $signal->order->symbol);
        $this->assertEquals(10, $signal->order->qty);
        $this->assertEquals('buy', $signal->order->side);
    }

    public function test_sell_signal_on_cross_down_when_long()
    {
        $config = [
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 2,
            'slow' => 4,
        ];

        $strategy = new SMA($config);

        $setupBars = [
            ['symbol' => 'AAPL', 'close' => 100.00],
            ['symbol' => 'AAPL', 'close' => 99.00],
            ['symbol' => 'AAPL', 'close' => 98.00],
            ['symbol' => 'AAPL', 'close' => 97.00],
            ['symbol' => 'AAPL', 'close' => 105.00],
            ['symbol' => 'AAPL', 'close' => 110.00],
        ];

        foreach ($setupBars as $bar) {
            $strategy->onBar($bar, []);
        }

        $state = ['position' => 'long'];

        $crossDownBars = [
            ['symbol' => 'AAPL', 'close' => 95.00],
            ['symbol' => 'AAPL', 'close' => 90.00],
        ];

        $signal = null;
        foreach ($crossDownBars as $bar) {
            $signal = $strategy->onBar($bar, $state);
        }

        $this->assertNotNull($signal->order);
        $this->assertEquals('AAPL', $signal->order->symbol);
        $this->assertEquals(10, $signal->order->qty);
        $this->assertEquals('sell', $signal->order->side);
    }

    public function test_no_signal_when_already_long_and_cross_up()
    {
        $config = [
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 2,
            'slow' => 4,
        ];

        $strategy = new SMA($config);

        $bars = [
            ['symbol' => 'AAPL', 'close' => 100.00],
            ['symbol' => 'AAPL', 'close' => 101.00],
            ['symbol' => 'AAPL', 'close' => 102.00],
            ['symbol' => 'AAPL', 'close' => 103.00],
            ['symbol' => 'AAPL', 'close' => 104.00],
        ];

        foreach ($bars as $bar) {
            $strategy->onBar($bar, []);
        }

        $state = ['position' => 'long'];

        $signal = $strategy->onBar(['symbol' => 'AAPL', 'close' => 110.00], $state);

        $this->assertNull($signal->order);
    }

    public function test_no_signal_when_flat_and_cross_down()
    {
        $config = [
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 2,
            'slow' => 4,
        ];

        $strategy = new SMA($config);

        $bars = [
            ['symbol' => 'AAPL', 'close' => 110.00],
            ['symbol' => 'AAPL', 'close' => 109.00],
            ['symbol' => 'AAPL', 'close' => 108.00],
            ['symbol' => 'AAPL', 'close' => 107.00],
        ];

        foreach ($bars as $bar) {
            $strategy->onBar($bar, []);
        }

        $state = [];

        $signal = $strategy->onBar(['symbol' => 'AAPL', 'close' => 95.00], $state);

        $this->assertNull($signal->order);
    }

    public function test_sma_calculation_accuracy()
    {
        $config = [
            'symbol' => 'AAPL',
            'qty' => 10,
            'fast' => 3,
            'slow' => 3,
        ];

        $strategy = new SMA($config);

        $bars = [
            ['symbol' => 'AAPL', 'close' => 100.00],
            ['symbol' => 'AAPL', 'close' => 102.00],
            ['symbol' => 'AAPL', 'close' => 104.00],
        ];

        foreach ($bars as $bar) {
            $strategy->onBar($bar, []);
        }

        $signal = $strategy->onBar(['symbol' => 'AAPL', 'close' => 106.00], []);

        $this->assertNull($signal->order);
    }
}
