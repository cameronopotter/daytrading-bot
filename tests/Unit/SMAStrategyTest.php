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

        // Only 2 bars, need at least slow period (5)
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

        // Prices trending down then up to create cross-up
        $bars = [
            ['symbol' => 'AAPL', 'close' => 100.00],
            ['symbol' => 'AAPL', 'close' => 99.00],
            ['symbol' => 'AAPL', 'close' => 98.00],
            ['symbol' => 'AAPL', 'close' => 97.00],  // Fast SMA < Slow SMA
            ['symbol' => 'AAPL', 'close' => 105.00], // Fast jumps up, cross-up occurs here
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

        // First get into a long position with cross-up
        $setupBars = [
            ['symbol' => 'AAPL', 'close' => 100.00],
            ['symbol' => 'AAPL', 'close' => 99.00],
            ['symbol' => 'AAPL', 'close' => 98.00],
            ['symbol' => 'AAPL', 'close' => 97.00],
            ['symbol' => 'AAPL', 'close' => 105.00],
            ['symbol' => 'AAPL', 'close' => 110.00], // Cross-up
        ];

        foreach ($setupBars as $bar) {
            $strategy->onBar($bar, []);
        }

        // Simulate being in a long position
        $state = ['position' => 'long'];

        // Now cross-down with falling prices
        $crossDownBars = [
            ['symbol' => 'AAPL', 'close' => 95.00],
            ['symbol' => 'AAPL', 'close' => 90.00], // Cross-down occurs
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

        // Simulate already being long
        $state = ['position' => 'long'];

        // Another cross-up
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

        // Start high
        $bars = [
            ['symbol' => 'AAPL', 'close' => 110.00],
            ['symbol' => 'AAPL', 'close' => 109.00],
            ['symbol' => 'AAPL', 'close' => 108.00],
            ['symbol' => 'AAPL', 'close' => 107.00],
        ];

        foreach ($bars as $bar) {
            $strategy->onBar($bar, []);
        }

        // Not in a position (flat)
        $state = [];

        // Cross-down
        $signal = $strategy->onBar(['symbol' => 'AAPL', 'close' => 95.00], $state);

        // Should not generate sell signal when not in position
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

        // Feed known prices: 100, 102, 104
        // SMA(3) = (100 + 102 + 104) / 3 = 102
        $bars = [
            ['symbol' => 'AAPL', 'close' => 100.00],
            ['symbol' => 'AAPL', 'close' => 102.00],
            ['symbol' => 'AAPL', 'close' => 104.00],
        ];

        foreach ($bars as $bar) {
            $strategy->onBar($bar, []);
        }

        // We can't directly test SMA value, but we can verify no cross occurs
        // when fast === slow (should never cross)
        $signal = $strategy->onBar(['symbol' => 'AAPL', 'close' => 106.00], []);

        $this->assertNull($signal->order);
    }
}
