<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Position;
use App\Models\Strategy;
use App\Models\StrategyRun;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config(['trading.webhook_secret' => 'test-secret-key']);
        config(['trading.mode' => 'paper']);

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

    private function generateSignature(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), 'test-secret-key');
    }

    public function test_webhook_rejects_invalid_signature()
    {
        $payload = [
            'type' => 'bar',
            'data' => [
                'symbol' => 'AAPL',
                'close' => 150.00,
                'timestamp' => '2025-11-01T12:00:00Z',
            ],
        ];

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => 'invalid-signature-here',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Invalid signature',
            ]);
    }

    public function test_webhook_accepts_valid_signature()
    {
        $payload = [
            'type' => 'bar',
            'data' => [
                'symbol' => 'AAPL',
                'close' => 150.00,
                'open' => 149.00,
                'high' => 151.00,
                'low' => 148.50,
                'volume' => 1000000,
                'timestamp' => '2025-11-01T12:00:00Z',
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => $signature,
        ]);

        $response->assertStatus(200);
    }

    public function test_webhook_rejects_missing_signature()
    {
        $payload = [
            'type' => 'bar',
            'data' => [
                'symbol' => 'AAPL',
                'close' => 150.00,
                'timestamp' => '2025-11-01T12:00:00Z',
            ],
        ];

        $response = $this->postJson('/api/stream/alpaca', $payload);

        $response->assertStatus(403);
    }

    public function test_webhook_handles_bar_event()
    {
        $payload = [
            'type' => 'bar',
            'data' => [
                'symbol' => 'AAPL',
                'close' => 150.00,
                'open' => 149.00,
                'high' => 151.00,
                'low' => 148.50,
                'volume' => 1000000,
                'timestamp' => '2025-11-01T12:00:00Z',
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => $signature,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'type' => 'bar',
            ]);
    }

    public function test_webhook_handles_quote_event()
    {
        $payload = [
            'type' => 'quote',
            'data' => [
                'symbol' => 'AAPL',
                'bid' => 149.95,
                'ask' => 150.05,
                'bid_size' => 100,
                'ask_size' => 200,
                'timestamp' => '2025-11-01T12:00:00Z',
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => $signature,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'type' => 'quote',
            ]);
    }

    public function test_webhook_handles_trade_event()
    {
        $payload = [
            'type' => 'trade',
            'data' => [
                'symbol' => 'AAPL',
                'price' => 150.00,
                'size' => 100,
                'timestamp' => '2025-11-01T12:00:00Z',
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => $signature,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'type' => 'trade',
            ]);
    }

    public function test_webhook_updates_order_status()
    {
        $run = StrategyRun::first();

        $order = Order::create([
            'strategy_run_id' => $run->id,
            'client_order_id' => 'test-order-123',
            'broker' => 'alpaca',
            'symbol' => 'AAPL',
            'side' => 'buy',
            'type' => 'market',
            'qty' => 10,
            'time_in_force' => 'day',
            'status' => 'new',
            'broker_order_id' => 'alpaca-order-456',
            'placed_at' => now(),
            'filled_qty' => 0,
        ]);

        $payload = [
            'type' => 'order_update',
            'data' => [
                'id' => 'alpaca-order-456',
                'client_order_id' => 'test-order-123',
                'status' => 'filled',
                'filled_qty' => '10',
                'filled_avg_price' => '150.00',
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Verify order was updated
        $order->refresh();
        $this->assertEquals('filled', $order->status);
        $this->assertEquals(10, (float) $order->filled_qty);
        $this->assertEquals(150.00, (float) $order->avg_fill_price);
    }

    public function test_webhook_updates_position()
    {
        $run = StrategyRun::first();

        // Create an order first
        Order::create([
            'strategy_run_id' => $run->id,
            'client_order_id' => 'new-client-order',
            'broker' => 'alpaca',
            'symbol' => 'TSLA',
            'side' => 'buy',
            'type' => 'market',
            'qty' => 5,
            'time_in_force' => 'day',
            'status' => 'new',
            'broker_order_id' => 'new-order',
            'placed_at' => now(),
            'filled_qty' => 0,
        ]);

        $payload = [
            'type' => 'order_update',
            'data' => [
                'id' => 'new-order',
                'client_order_id' => 'new-client-order',
                'symbol' => 'TSLA',
                'side' => 'buy',
                'status' => 'filled',
                'filled_qty' => '5',
                'filled_avg_price' => '200.00',
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Verify position was created/updated
        $this->assertDatabaseHas('positions', [
            'symbol' => 'TSLA',
            'mode' => 'paper',
        ]);

        $position = Position::where('symbol', 'TSLA')
            ->where('mode', 'paper')
            ->first();

        $this->assertNotNull($position);
        $this->assertEquals(5, $position->qty);
    }

    public function test_webhook_signature_uses_exact_payload()
    {
        $payload = [
            'type' => 'bar',
            'data' => [
                'symbol' => 'AAPL',
                'close' => 150.00,
                'timestamp' => '2025-11-01T12:00:00Z',
            ],
        ];

        // Generate signature with slightly different payload
        $differentPayload = [
            'type' => 'bar',
            'data' => [
                'symbol' => 'AAPL',
                'close' => 150.01, // Different value
                'timestamp' => '2025-11-01T12:00:00Z',
            ],
        ];

        $signature = $this->generateSignature($differentPayload);

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => $signature,
        ]);

        // Should reject because signature doesn't match actual payload
        $response->assertStatus(403);
    }

    public function test_webhook_handles_unknown_event_type()
    {
        $payload = [
            'type' => 'unknown_event',
            'data' => [
                'foo' => 'bar',
            ],
        ];

        $signature = $this->generateSignature($payload);

        $response = $this->postJson('/api/stream/alpaca', $payload, [
            'X-Stream-Signature' => $signature,
        ]);

        // Should accept but ignore unknown types
        $response->assertStatus(200);
    }
}
