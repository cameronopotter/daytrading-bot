<?php

namespace Tests\Unit;

use App\Models\RiskLimit;
use App\Trading\DTO\OrderRequest;
use App\Trading\Risk\RiskGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RiskGuardTest extends TestCase
{
    use RefreshDatabase;

    private RiskGuard $riskGuard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->riskGuard = new RiskGuard();

        // Create default risk limits
        RiskLimit::create([
            'daily_max_loss' => 1000.00,
            'max_position_qty' => 100,
            'max_orders_per_min' => 10,
            'mode' => 'paper',
        ]);

        // Clear rate limit cache
        Cache::clear();
    }

    public function test_allows_order_when_all_checks_pass()
    {
        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'market',
            qty: 50
        );

        $state = [
            'day_pl' => 100.00, // Profit, well within limit
        ];

        $this->assertTrue($this->riskGuard->allows($orderRequest, $state));
    }

    public function test_denies_order_when_daily_loss_limit_exceeded()
    {
        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'market',
            qty: 10
        );

        $state = [
            'day_pl' => -1500.00, // Loss exceeds limit of -1000
        ];

        $this->assertFalse($this->riskGuard->allows($orderRequest, $state));
    }

    public function test_denies_order_when_position_size_too_large()
    {
        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'market',
            qty: 150 // Exceeds max_position_qty of 100
        );

        $state = [
            'day_pl' => 0,
        ];

        $this->assertFalse($this->riskGuard->allows($orderRequest, $state));
    }

    public function test_allows_order_at_max_position_size()
    {
        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'market',
            qty: 100 // Exactly at max_position_qty
        );

        $state = [
            'day_pl' => 0,
        ];

        $this->assertTrue($this->riskGuard->allows($orderRequest, $state));
    }

    public function test_denies_order_when_rate_limit_exceeded()
    {
        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'market',
            qty: 10
        );

        $state = [
            'day_pl' => 0,
        ];

        // Make 10 orders (at the limit)
        for ($i = 0; $i < 10; $i++) {
            $result = $this->riskGuard->allows($orderRequest, $state);
            $this->assertTrue($result, "Order {$i} should be allowed");
        }

        // 11th order should be denied
        $this->assertFalse($this->riskGuard->allows($orderRequest, $state));
    }

    public function test_allows_order_at_exact_daily_loss_limit()
    {
        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'market',
            qty: 10
        );

        $state = [
            'day_pl' => -1000.00, // Exactly at limit
        ];

        $this->assertTrue($this->riskGuard->allows($orderRequest, $state));
    }

    public function test_different_modes_use_different_limits()
    {
        // Create live mode limits
        RiskLimit::create([
            'daily_max_loss' => 500.00,
            'max_position_qty' => 50,
            'max_orders_per_min' => 5,
            'mode' => 'live',
        ]);

        config(['trading.mode' => 'live']);

        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'market',
            qty: 75 // Exceeds live limit of 50, but not paper limit of 100
        );

        $state = [
            'day_pl' => 0,
        ];

        $this->assertFalse($this->riskGuard->allows($orderRequest, $state));
    }

    public function test_allows_sell_orders_within_limits()
    {
        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'sell',
            type: 'market',
            qty: 50
        );

        $state = [
            'day_pl' => 0,
        ];

        $this->assertTrue($this->riskGuard->allows($orderRequest, $state));
    }
}
