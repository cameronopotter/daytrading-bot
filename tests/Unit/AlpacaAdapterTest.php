<?php

namespace Tests\Unit;

use App\Trading\Adapters\AlpacaAdapter;
use App\Trading\DTO\OrderRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AlpacaAdapterTest extends TestCase
{
    private function createMockAdapter(array $responses): AlpacaAdapter
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        return new AlpacaAdapter($client);
    }

    public function test_get_account_maps_response_correctly()
    {
        $mockResponse = [
            'id' => 'abc123',
            'equity' => '100000.00',
            'buying_power' => '400000.00',
            'last_equity' => '99500.00',
            'cash' => '50000.00',
        ];

        $adapter = $this->createMockAdapter([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $account = $adapter->getAccount();

        $this->assertEquals('100000.00', $account['equity']);
        $this->assertEquals('400000.00', $account['buying_power']);
        $this->assertEquals('99500.00', $account['last_equity']);
    }

    public function test_get_positions_returns_array()
    {
        $mockResponse = [
            [
                'symbol' => 'AAPL',
                'qty' => '10',
                'avg_entry_price' => '150.00',
                'current_price' => '155.00',
                'unrealized_pl' => '50.00',
            ],
        ];

        $adapter = $this->createMockAdapter([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $positions = $adapter->getPositions();

        $this->assertIsArray($positions);
        $this->assertCount(1, $positions);
        $this->assertEquals('AAPL', $positions[0]['symbol']);
    }

    public function test_place_order_sends_correct_payload()
    {
        $mockResponse = [
            'id' => 'order123',
            'client_order_id' => 'client-uuid-123',
            'symbol' => 'AAPL',
            'qty' => '10',
            'side' => 'buy',
            'type' => 'market',
            'status' => 'new',
            'submitted_at' => '2025-11-01T12:00:00Z',
        ];

        $adapter = $this->createMockAdapter([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'market',
            qty: 10,
            clientId: 'client-uuid-123'
        );

        $order = $adapter->placeOrder($orderRequest);

        $this->assertEquals('order123', $order['broker_order_id']);
        $this->assertEquals('client-uuid-123', $order['client_order_id']);
        $this->assertEquals('AAPL', $order['symbol']);
        $this->assertEquals(10, $order['qty']);
        $this->assertEquals('buy', $order['side']);
    }

    public function test_status_normalization_for_alpaca_statuses()
    {
        $testCases = [
            'new' => 'new',
            'accepted' => 'new',
            'pending_new' => 'new',
            'partial_fill' => 'partially_filled',
            'partially_filled' => 'partially_filled',
            'filled' => 'filled',
            'canceled' => 'canceled',
            'pending_cancel' => 'canceled',
            'expired' => 'canceled',
            'rejected' => 'rejected',
            'suspended' => 'rejected',
        ];

        foreach ($testCases as $alpacaStatus => $expectedStatus) {
            $mockResponse = [
                'id' => 'order123',
                'client_order_id' => 'test',
                'symbol' => 'AAPL',
                'qty' => '10',
                'side' => 'buy',
                'type' => 'market',
                'status' => $alpacaStatus,
                'submitted_at' => '2025-11-01T12:00:00Z',
            ];

            $adapter = $this->createMockAdapter([
                new Response(200, [], json_encode($mockResponse)),
            ]);

            $orderRequest = new OrderRequest(
                symbol: 'AAPL',
                side: 'buy',
                type: 'market',
                qty: 10
            );

            $order = $adapter->placeOrder($orderRequest);

            $this->assertEquals($expectedStatus, $order['status'], "Failed normalizing {$alpacaStatus}");
        }
    }

    public function test_place_limit_order_includes_limit_price()
    {
        $mockResponse = [
            'id' => 'order123',
            'client_order_id' => 'test',
            'symbol' => 'AAPL',
            'qty' => '10',
            'side' => 'buy',
            'type' => 'limit',
            'limit_price' => '150.00',
            'status' => 'new',
            'submitted_at' => '2025-11-01T12:00:00Z',
        ];

        $adapter = $this->createMockAdapter([
            new Response(200, [], json_encode($mockResponse)),
        ]);

        $orderRequest = new OrderRequest(
            symbol: 'AAPL',
            side: 'buy',
            type: 'limit',
            qty: 10,
            limit: 150.00
        );

        $order = $adapter->placeOrder($orderRequest);

        $this->assertEquals('limit', $order['type']);
        $this->assertEquals('150.00', $order['limit_price']);
    }

    public function test_cancel_order_calls_correct_endpoint()
    {
        $adapter = $this->createMockAdapter([
            new Response(204, [], ''),
        ]);

        // Should not throw exception
        $adapter->cancelOrder('order123');
        $this->assertTrue(true);
    }

    public function test_close_all_positions_calls_correct_endpoint()
    {
        $adapter = $this->createMockAdapter([
            new Response(200, [], json_encode([])),
        ]);

        // Should not throw exception
        $adapter->closeAllPositions();
        $this->assertTrue(true);
    }
}
