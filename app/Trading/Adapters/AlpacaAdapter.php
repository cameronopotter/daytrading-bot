<?php

namespace App\Trading\Adapters;

use App\Trading\BrokerAdapter;
use App\Trading\DTO\OrderRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;

class AlpacaAdapter implements BrokerAdapter
{
    private Client $client;
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.alpaca.base_url');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'APCA-API-KEY-ID' => config('services.alpaca.key_id'),
                'APCA-API-SECRET-KEY' => config('services.alpaca.secret'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    // Method for testing - allows injecting a mocked HTTP client
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getAccount(): array
    {
        try {
            $response = $this->client->get('/v2/account');
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to get account: ' . $e->getMessage(), 0, $e);
        }
    }

    public function getPositions(): array
    {
        try {
            $response = $this->client->get('/v2/positions');
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to get positions: ' . $e->getMessage(), 0, $e);
        }
    }

    public function placeOrder(OrderRequest $o): array
    {
        try {
            $payload = [
                'symbol' => $o->symbol,
                'side' => $o->side,
                'type' => $o->type,
                'qty' => (string) $o->qty,
                'time_in_force' => $o->tif,
            ];

            if ($o->limit !== null) {
                $payload['limit_price'] = (string) $o->limit;
            }

            if ($o->stop !== null) {
                $payload['stop_price'] = (string) $o->stop;
            }

            if ($o->clientId) {
                $payload['client_order_id'] = $o->clientId;
            } else {
                $payload['client_order_id'] = (string) Str::uuid();
            }

            $response = $this->client->post('/v2/orders', [
                'json' => $payload,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Normalize to our format
            return [
                'broker_order_id' => $result['id'] ?? null,
                'client_order_id' => $result['client_order_id'] ?? $payload['client_order_id'],
                'symbol' => $result['symbol'] ?? $o->symbol,
                'side' => $result['side'] ?? $o->side,
                'type' => $result['type'] ?? $o->type,
                'qty' => (float) ($result['qty'] ?? $o->qty),
                'filled_qty' => (float) ($result['filled_qty'] ?? 0),
                'limit_price' => isset($result['limit_price']) && $result['limit_price'] ? (float) $result['limit_price'] : null,
                'stop_price' => isset($result['stop_price']) && $result['stop_price'] ? (float) $result['stop_price'] : null,
                'status' => $this->normalizeStatus($result['status'] ?? 'new'),
                'time_in_force' => $result['time_in_force'] ?? $o->tif,
                'placed_at' => $result['submitted_at'] ?? $result['created_at'] ?? now()->toIso8601String(),
                'raw' => $result,
            ];
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to place order: ' . $e->getMessage(), 0, $e);
        }
    }

    public function cancelOrder(string $brokerOrderId): void
    {
        try {
            $this->client->delete("/v2/orders/{$brokerOrderId}");
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to cancel order: ' . $e->getMessage(), 0, $e);
        }
    }

    public function closeAllPositions(): void
    {
        try {
            $this->client->delete('/v2/positions', [
                'query' => ['cancel_orders' => 'true'],
            ]);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to close all positions: ' . $e->getMessage(), 0, $e);
        }
    }

    private function normalizeStatus(string $alpacaStatus): string
    {
        return match ($alpacaStatus) {
            'new', 'accepted', 'pending_new' => 'new',
            'partial_fill', 'partially_filled' => 'partially_filled',
            'filled' => 'filled',
            'canceled', 'pending_cancel', 'expired', 'stopped' => 'canceled',
            'rejected', 'suspended', 'pending_replace' => 'rejected',
            default => 'new',
        };
    }
}
