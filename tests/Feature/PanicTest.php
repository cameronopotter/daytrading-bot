<?php

namespace Tests\Feature;

use App\Models\DecisionLog;
use App\Models\Order;
use App\Models\Position;
use App\Models\Strategy;
use App\Models\StrategyRun;
use App\Trading\Adapters\AlpacaAdapter;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class PanicTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Create strategy and run for FK constraints
        $strategy = Strategy::create([
            'name' => 'Test Strategy',
            'class' => \App\Trading\Strategies\SMA::class,
            'config' => ['symbol' => 'AAPL', 'qty' => 10, 'fast' => 9, 'slow' => 21],
            'is_enabled' => true,
        ]);

        StrategyRun::create([
            'strategy_id' => $strategy->id,
            'status' => 'running',
            'mode' => 'paper',
            'started_at' => now(),
        ]);
    }

    public function test_panic_closes_all_positions()
    {
        // Create positions
        Position::create([
            'symbol' => 'AAPL',
            'qty' => 10,
            'avg_entry_price' => 150.00,
            'unrealized_pl' => 50.00,
            'mode' => 'paper',
            'raw' => [],
        ]);

        Position::create([
            'symbol' => 'TSLA',
            'qty' => 5,
            'avg_entry_price' => 200.00,
            'unrealized_pl' => -25.00,
            'mode' => 'paper',
            'raw' => [],
        ]);

        // Mock AlpacaAdapter
        $mockAdapter = Mockery::mock(AlpacaAdapter::class);
        $mockAdapter->shouldReceive('closeAllPositions')
            ->once();

        $this->app->instance(AlpacaAdapter::class, $mockAdapter);

        $response = $this->postJson('/api/panic');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'results',
            ])
            ->assertJson([
                'message' => 'Panic executed',
            ]);

        // Verify positions were closed
        $mockAdapter->shouldHaveReceived('closeAllPositions')->once();
    }

    public function test_panic_cancels_all_open_orders()
    {
        $run = StrategyRun::first();

        // Create open orders
        Order::create([
            'strategy_run_id' => $run->id,
            'client_order_id' => 'order-1',
            'broker' => 'alpaca',
            'symbol' => 'AAPL',
            'side' => 'buy',
            'type' => 'limit',
            'qty' => 10,
            'limit_price' => 145.00,
            'time_in_force' => 'day',
            'status' => 'new',
            'broker_order_id' => 'alpaca-123',
            'placed_at' => now(),
            'filled_qty' => 0,
        ]);

        Order::create([
            'strategy_run_id' => $run->id,
            'client_order_id' => 'order-2',
            'broker' => 'alpaca',
            'symbol' => 'TSLA',
            'side' => 'buy',
            'type' => 'limit',
            'qty' => 5,
            'limit_price' => 195.00,
            'time_in_force' => 'day',
            'status' => 'new',
            'broker_order_id' => 'alpaca-456',
            'placed_at' => now(),
            'filled_qty' => 0,
        ]);

        // Mock AlpacaAdapter
        $mockAdapter = Mockery::mock(AlpacaAdapter::class);
        $mockAdapter->shouldReceive('closeAllPositions')->once();
        $mockAdapter->shouldReceive('cancelOrder')
            ->with('alpaca-123')
            ->once();
        $mockAdapter->shouldReceive('cancelOrder')
            ->with('alpaca-456')
            ->once();

        $this->app->instance(AlpacaAdapter::class, $mockAdapter);

        $response = $this->postJson('/api/panic');

        $response->assertStatus(200);

        // Verify cancel was attempted for both orders
        $mockAdapter->shouldHaveReceived('cancelOrder')->twice();
    }

    public function test_panic_stops_running_strategies()
    {
        $response = $this->postJson('/api/panic');

        $response->assertStatus(200);

        // Verify all running strategies are stopped
        $this->assertDatabaseMissing('strategy_runs', [
            'status' => 'running',
        ]);

        $this->assertDatabaseHas('strategy_runs', [
            'status' => 'stopped',
        ]);
    }

    public function test_panic_logs_decision()
    {
        // Mock AlpacaAdapter
        $mockAdapter = Mockery::mock(AlpacaAdapter::class);
        $mockAdapter->shouldReceive('closeAllPositions')->once();

        $this->app->instance(AlpacaAdapter::class, $mockAdapter);

        $response = $this->postJson('/api/panic');

        $response->assertStatus(200);

        // Verify decision log entry was created
        $this->assertDatabaseHas('decision_logs', [
            'level' => 'warn',
            'context' => 'panic',
            'message' => 'PANIC: Flatten all positions and cancel orders triggered',
        ]);
    }

    public function test_panic_handles_errors_gracefully()
    {
        // Mock AlpacaAdapter to throw exception
        $mockAdapter = Mockery::mock(AlpacaAdapter::class);
        $mockAdapter->shouldReceive('closeAllPositions')
            ->andThrow(new \Exception('Broker API error'));

        $this->app->instance(AlpacaAdapter::class, $mockAdapter);

        $response = $this->postJson('/api/panic');

        // Should still return 200 with error details
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'results',
            ]);

        // Verify decision log was still created (with warn level, but errors in payload)
        $this->assertDatabaseHas('decision_logs', [
            'level' => 'warn',
            'context' => 'panic',
        ]);

        // Verify error was included in the payload
        $log = DecisionLog::where('context', 'panic')->first();
        $this->assertNotEmpty($log->payload['errors']);
    }

    public function test_panic_does_not_cancel_already_filled_orders()
    {
        $run = StrategyRun::first();

        Order::create([
            'strategy_run_id' => $run->id,
            'client_order_id' => 'order-filled',
            'broker' => 'alpaca',
            'symbol' => 'AAPL',
            'side' => 'buy',
            'type' => 'market',
            'qty' => 10,
            'time_in_force' => 'day',
            'status' => 'filled',
            'broker_order_id' => 'alpaca-789',
            'placed_at' => now(),
            'filled_qty' => 10,
            'avg_fill_price' => 150.00,
        ]);

        // Mock AlpacaAdapter
        $mockAdapter = Mockery::mock(AlpacaAdapter::class);
        $mockAdapter->shouldReceive('closeAllPositions')->once();
        // Should not call cancelOrder for filled orders
        $mockAdapter->shouldNotReceive('cancelOrder');

        $this->app->instance(AlpacaAdapter::class, $mockAdapter);

        $response = $this->postJson('/api/panic');

        $response->assertStatus(200);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
