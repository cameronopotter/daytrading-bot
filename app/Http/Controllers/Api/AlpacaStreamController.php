<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DecisionLog;
use App\Models\Order;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AlpacaStreamController extends Controller
{
    public function ingest(Request $request)
    {
        // Verify HMAC signature
        $signature = $request->header('X-Stream-Signature');
        $payload = $request->getContent();

        if (! $signature) {
            Log::warning('[WEBHOOK] Missing signature');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $expectedSignature = hash_hmac('sha256', $payload, config('trading.webhook_secret', 'change-me'));

        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('[WEBHOOK] Invalid signature');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->json()->all();
        $eventType = $data['type'] ?? $data['event_type'] ?? null;
        $eventData = $data['data'] ?? [];

        Log::info("[WEBHOOK] Received {$eventType}", ['data' => $eventData]);

        try {
            match ($eventType) {
                'bar' => $this->handleBar($eventData),
                'quote' => $this->handleQuote($eventData),
                'trade' => $this->handleTrade($eventData),
                'order_update' => $this->handleOrderUpdate($eventData),
                default => Log::info("[WEBHOOK] Unhandled event type: {$eventType}"),
            };

            return response()->json(['status' => 'ok', 'type' => $eventType]);
        } catch (\Exception $e) {
            Log::error("[WEBHOOK ERROR] {$eventType}: ".$e->getMessage());

            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function handleBar(array $data): void
    {
        Log::debug('[BAR] '.$data['symbol'], $data);

        // Dispatch to Engine\Runner for strategy processing
        $runner = app(\App\Trading\Engine\Runner::class);
        $runner->processBar($data);
    }

    private function handleQuote(array $data): void
    {
        // Quote data for real-time pricing
        Log::debug('[QUOTE] '.$data['symbol'], $data);
    }

    private function handleTrade(array $data): void
    {
        // Trade tick data
        Log::debug('[TRADE] '.$data['symbol'], $data);
    }

    private function handleOrderUpdate(array $data): void
    {
        // Handle both formats: { event, order } or direct order data
        $event = $data['event'] ?? 'update';
        $orderData = $data['order'] ?? $data;

        Log::info('[ORDER UPDATE] '.$event, $orderData);

        // Find and update the order in our database
        $order = Order::where('broker_order_id', $orderData['id'] ?? null)
            ->orWhere('client_order_id', $orderData['client_order_id'] ?? null)
            ->first();

        if ($order) {
            $updates = [
                'status' => $this->normalizeStatus($orderData['status'] ?? 'new'),
                'filled_qty' => $orderData['filled_qty'] ?? $order->filled_qty,
                'raw' => array_merge($order->raw ?? [], $orderData),
            ];

            // Handle filled_avg_price if present
            if (isset($orderData['filled_avg_price'])) {
                $updates['avg_fill_price'] = $orderData['filled_avg_price'];
            }

            $order->update($updates);

            // Broadcast to UI
            // TODO: In Task H, broadcast OrderUpdated event
            // event(new OrderUpdated($order));

            // Create decision log
            DecisionLog::create([
                'strategy_run_id' => $order->strategy_run_id,
                'level' => 'info',
                'context' => 'order_update',
                'message' => "Order {$order->client_order_id} {$event}",
                'payload' => $orderData,
                'created_at' => now(),
            ]);

            // If filled, update position
            $status = $orderData['status'] ?? '';
            if ($status === 'filled' || $status === 'partially_filled' || $event === 'fill' || $event === 'partial_fill') {
                $this->updatePosition($order);
            }
        } else {
            Log::warning('[ORDER UPDATE] Order not found', $orderData);
        }
    }

    private function updatePosition(Order $order): void
    {
        $mode = config('trading.mode', 'paper');

        $position = Position::firstOrNew([
            'symbol' => $order->symbol,
            'mode' => $mode,
        ]);

        // Simple position tracking (this is simplified for MVP)
        if ($order->side === 'buy') {
            $newQty = $position->qty + $order->filled_qty;
            $position->avg_entry_price = (($position->avg_entry_price * $position->qty) + ($order->avg_fill_price * $order->filled_qty)) / $newQty;
            $position->qty = $newQty;
        } else { // sell
            $position->qty -= $order->filled_qty;
        }

        $position->save();

        // TODO: In Task H, broadcast PositionUpdated event
        // event(new PositionUpdated($position));
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
