Day-Trading Bot (Laravel + Vue) — MVP Spec

Owner: Cameron
PM/Tech Lead: You (this doc)
Primary Broker: Alpaca (paper to start, live later)
Stack: PHP 8.2 + Laravel 11, MySQL 8, Redis, Node 20 (for stream helper), Vue 3 + Vite + Tailwind, Pusher (or Laravel WebSockets) + Echo

0) Objectives & Scope
Goals

Build a real-time trading UI that can run a strategy (start/stop) against Alpaca paper account, place/cancel orders, show positions, orders, fills, and P&L.

Use a clean BrokerAdapter interface so other brokers (IBKR, Binance, OANDA) can be added later without rewriting strategies.

Include risk controls (daily loss limit, max position size), panic/flatten control, logging, and event streaming to the UI.

Ship with a simple SMA cross strategy (configurable) as a working example.

Non-Goals (for this MVP)

Fancy backtesting suite (we’ll do a minimal bar cache & dry-run mode only).

Multi-broker live routing (we’ll architect for it; implement only Alpaca now).

Options/futures/shorting edge cases (we’ll handle equities long only first).

1) High-Level Architecture

Frontend (Vue 3 / Vite / Tailwind):

Dashboard: PAPER/LIVE status, Start/Stop, Panic button, health indicators.

Tiles: Equity, Buying Power, Day P&L, Open Risk.

Tables: Open Positions, Open Orders, Recent Fills, Decision Log.

Strategy Config page: symbol(s), size, SMA params, risk limits.

Real-time via Laravel Echo + Pusher or Laravel WebSockets.

Backend (Laravel 11):

Domain: app/Trading/*

BrokerAdapter interface

AlpacaAdapter implementation (REST + signed headers)

Strategy contracts + SMA strategy

RiskGuard and PanicService

Engine\Runner to convert ticks/bars → signals → orders

Jobs/Events: ExecuteOrder, CancelOrder, event broadcasting for fills/positions/logs.

HTTP: Controllers for strategy control, account/positions/orders read, and stream webhooks.

Data: Migrations for strategies, runs, orders, fills, positions, risk_limits, logs.

Streams: A Node 20 helper connects to Alpaca WebSocket (quotes/trades/account updates) and POSTs signed webhooks to Laravel to avoid long-lived PHP processes.

Services

Redis queues for async jobs.

Supervisor configs for queue workers and the Node stream helper.

2) Environments & Config

Create .env.example additions:

# --- Alpaca ---
ALPACA_ENV=paper
ALPACA_KEY_ID=
ALPACA_SECRET=
ALPACA_BASE_URL=https://paper-api.alpaca.markets
ALPACA_DATA_WS=wss://stream.data.alpaca.markets/v2/sip
ALPACA_TRADING_WS=wss://paper-api.alpaca.markets/stream  # or v2/account/updates depending on plan

# --- App Modes ---
TRADING_MODE=paper  # paper|live (UI reads this; server guards writes)

# --- Webhooks (Laravel receives stream events via Node helper) ---
STREAM_WEBHOOK_SECRET=change-me
STREAM_WEBHOOK_URL=https://yourserver.com/api/stream/alpaca  # local: http://localhost:8000/api/stream/alpaca

# --- Broadcasting ---
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_HOST=
PUSHER_PORT=
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1


Acceptance: Paper keys work; server boots; php artisan config:cache ok.

3) Dependencies & Project Bootstrap
Backend (Laravel)

Laravel 11 project

Composer packages:

"guzzlehttp/guzzle": "^7.9" (HTTP)

"laravel/sanctum": "^4.0" (auth for UI/API)

"pusher/pusher-php-server": "^7.2"

"ramsey/uuid": "^4.7"

(Optional) "beyondcode/laravel-websockets": "^1.15" if self-hosted websockets

Commands:

composer create-project laravel/laravel trading-bot
cd trading-bot
composer require guzzlehttp/guzzle pusher/pusher-php-server ramsey/uuid laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate

Frontend

Vue 3 + Vite + Tailwind + Echo/Pusher

Commands:

php artisan breeze:install vue
npm i
npm i -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
npm i pusher-js laravel-echo

Stream Helper (Node)

Directory: /streamer

Dependencies: ws, axios, tweetnacl (optional, or use HMAC with node crypto)

mkdir streamer && cd streamer
npm init -y
npm i ws axios


Acceptance: Repos boot; artisan serve and npm run dev run green.

4) Data Model & Migrations

Create migrations:

strategies

id, name (string), class (string, e.g., App\Trading\Strategies\SMA), config (json), is_enabled (bool), created_at/updated_at

strategy_runs

id, strategy_id (fk), status (pending|running|stopped|error), mode (paper|live), started_at, stopped_at, notes (text), created_at/updated_at

orders

id, strategy_run_id (fk), client_order_id (uuid), broker (alpaca), symbol, side (buy|sell), type (market|limit|stop|stop_limit), qty (decimal(18,4)), limit_price (decimal), stop_price (decimal), time_in_force (string), status (new|partially_filled|filled|canceled|rejected), broker_order_id (string), placed_at, filled_qty (decimal), avg_fill_price (decimal), raw (json), created_at/updated_at

fills

id, order_id (fk), symbol, qty (decimal), price (decimal), side, fill_at (datetime), raw (json), created_at/updated_at

positions

id, symbol (unique per mode), qty (decimal), avg_entry_price (decimal), unrealized_pl (decimal), mode (paper|live), raw (json), created_at/updated_at

risk_limits

id, daily_max_loss (decimal), max_position_qty (decimal), max_orders_per_min (int), mode (paper|live), created_at/updated_at

decision_logs

id, strategy_run_id (fk, nullable), level (info|warn|error), context (string), message (text), payload (json), created_at

Acceptance: Migrations run; foreign keys valid.

5) Domain Contracts & DTOs

Interfaces / DTOs

app/Trading/BrokerAdapter.php

namespace App\Trading;
use App\Trading\DTO\OrderRequest;

interface BrokerAdapter {
    public function getAccount(): array;
    public function getPositions(): array;
    public function placeOrder(OrderRequest $o): array; // returns normalized order
    public function cancelOrder(string $brokerOrderId): void;
    public function closeAllPositions(): void;
}


app/Trading/DTO/OrderRequest.php

namespace App\Trading\DTO;

class OrderRequest {
    public function __construct(
        public string $symbol,
        public string $side,         // buy|sell
        public string $type,         // market|limit|stop|stop_limit
        public float $qty,
        public ?float $limit = null,
        public ?float $stop = null,
        public string $tif = 'day',
        public ?string $clientId = null
    ) {}
}


Acceptance: Interface compiles; DTO autoloaded.

6) Alpaca Adapter (REST)

File: app/Trading/Adapters/AlpacaAdapter.php

Uses Guzzle with required headers:

APCA-API-KEY-ID, APCA-API-SECRET-KEY

Base URL from ALPACA_BASE_URL (paper vs live via env)

Methods:

getAccount() → GET /v2/account

getPositions() → GET /v2/positions

placeOrder() → POST /v2/orders

cancelOrder() → DELETE /v2/orders/{id}

closeAllPositions() → DELETE /v2/positions

Acceptance: Unit tests mock Guzzle and validate payload mapping and error handling.

7) Streaming: Node Helper + Webhook

We will NOT hold a PHP long-running WS in MVP. Instead:

7.1 Node stream client

File: /streamer/alpaca-stream.js

Connect to Alpaca streams (data + account updates).

Subscribe to:

quotes/trades for configured symbols

account/position/order updates (if available)

On messages:

Normalize and POST to Laravel webhook ${STREAM_WEBHOOK_URL} with header X-Stream-Signature: <HMAC-SHA256(body, STREAM_WEBHOOK_SECRET)>.

Env for Node (use .env or process.env):

ALPACA_KEY_ID=
ALPACA_SECRET=
ALPACA_DATA_WS=
ALPACA_TRADING_WS=
STREAM_WEBHOOK_URL=
STREAM_WEBHOOK_SECRET=
SYMBOLS=AAPL,MSFT,SPY


Run:

node streamer/alpaca-stream.js

7.2 Laravel webhook

Route: POST /api/stream/alpaca
File: app/Http/Controllers/Api/AlpacaStreamController.php

Verify HMAC signature.

Switch on event type: quote, trade, bar, order_update, position_update, etc.

Persist relevant data to tables; dispatch events for UI (OrderUpdated, FillReceived, PositionUpdated, DecisionLogged).

Acceptance: Start Node client, see webhook hits locally (tunnel if needed), events persisted and broadcast to UI.

8) Risk Guard & Panic Service

Files:

app/Trading/Risk/RiskGuard.php

allows(OrderRequest $o, array $state): bool

Checks: daily P&L (from account or computed), max position qty, order rate limit (Redis counter).

app/Trading/Risk/PanicService.php

flattenAll() → delegate to adapter closeAllPositions(), cancel open orders, mark run “stopped”.

Acceptance: Unit tests cover allow/deny and panic path.

9) Strategy Framework + SMA Example

Contract:

app/Trading/Strategies/Strategy.php

namespace App\Trading\Strategies;
use App\Trading\Signals\Signal;

interface Strategy {
    public function onBar(array $bar, array $state): ?Signal;
    public function name(): string;
}


Signal DTO:

app/Trading/Signals/Signal.php

namespace App\Trading\Signals;
use App\Trading\DTO\OrderRequest;

class Signal {
    public function __construct(public ?OrderRequest $order = null, public ?string $note = null) {}
    public static function buy(string $symbol, float $qty): self { /* ... */ }
    public static function sell(string $symbol, float $qty): self { /* ... */ }
}


SMA Strategy:

app/Trading/Strategies/SMA.php

Config: fast=9, slow=21, symbol, qty, bar_interval="1Min"

Keeps rolling arrays for closes; emits buy on fast cross up, sell on cross down (flat-to-long only in MVP).

Bar Aggregation

For MVP, prefer Alpaca minute bars if available, else aggregate ticks to minute bars in Node and post bars to Laravel.

Acceptance: With test bars, strategy emits expected signals.

10) Engine Runner & Jobs

Files:

app/Trading/Engine/Runner.php

Listens for bar events (from webhook), loads active strategy_runs, calls strategy → signal → dispatch job.

app/Jobs/ExecuteOrder.php

Accepts OrderRequest, calls RiskGuard, invokes BrokerAdapter::placeOrder(), persists orders row, logs a decision_logs entry.

Scheduler:

Not strictly required for streaming, but add:

php artisan trading:health (checks connectivity, equity, errors) every 1 min.

php artisan trading:enforce-daily-loss every 1 min (kill-switch).

Acceptance: Bars arrive → signals → orders placed in paper; orders/positions flow to UI.

11) HTTP API (for Vue UI)

Routes (routes/api.php):

GET  /api/health                         -> HealthController@show
GET  /api/account                        -> TradingReadController@account
GET  /api/positions                      -> TradingReadController@positions
GET  /api/orders                         -> TradingReadController@orders
GET  /api/fills                          -> TradingReadController@fills
GET  /api/strategy/config                -> StrategyController@show
POST /api/strategy/config                -> StrategyController@update
POST /api/strategy/start                 -> StrategyController@start
POST /api/strategy/stop                  -> StrategyController@stop
POST /api/panic                          -> PanicController@flatten
POST /api/stream/alpaca                  -> AlpacaStreamController@ingest  (webhook)


Acceptance: OpenAPI (optional) or well-documented responses; 200s with JSON payloads.

12) Broadcasting & Events

Server Events (broadcast via Echo):

OrderUpdated (payload: normalized order state)

FillReceived (fill details, cumulative qty/avg)

PositionUpdated (symbol qty/avg/unrealized)

DecisionLogged (message + context)

SystemHealth (latency, status, mode)

Frontend subscribes to a channel:

Public MVP: private-trading (auth via Sanctum)

Acceptance: When webhook persists an update, UI reflects real-time changes without refresh.

13) Vue UI

Pages/Components

src/pages/Dashboard.vue

Header: BIG PAPER/LIVE badge, current mode.

Buttons: Start, Stop, Panic (Flatten) (Stop disables Start, Panic always enabled)

Tiles: Equity, Buying Power, Day P&L, Open Risk

Tables: Positions, Open Orders, Recent Fills

Log Stream: most recent 100 decision logs (auto-scroll)

src/pages/Strategy.vue

Form: symbol(s), qty, SMA fast/slow, bar interval, risk limits

Save button → POST /api/strategy/config

Status: current run state

State & Data

Use Pinia stores: useTradingStore, useStrategyStore

Echo client in main.ts (pusher-js)

Tailwind for styling

Acceptance: UI loads data from /api/*, updates on events, safe toggles.

14) Security & Safety

All write endpoints (start/stop/panic/config) require auth (Sanctum).

Webhook verifies X-Stream-Signature HMAC with STREAM_WEBHOOK_SECRET.

Server enforces mode guard:

If TRADING_MODE=paper, block live endpoints (or require separate env).

Panic button:

Cancels open orders, closes positions, marks run stopped, logs WARN.

Acceptance: Unauthenticated write calls denied; wrong signature rejected with 403.

15) Testing

Unit:

AlpacaAdapter request/response mapping (mock Guzzle)

RiskGuard rules

SMA signal logic (cross up/down)

Feature:

Strategy start/stop endpoints

Panic endpoint (verifies cancellations were attempted)

Webhook handler (valid HMAC accepted, invalid rejected)

Integration (happy path):

Simulated bar → Signal → ExecuteOrder job → mock adapter called once → order persisted → event broadcast

Acceptance: php artisan test green; coverage on core modules.

16) DevOps & Runbooks

Supervisor configs (examples):

queue:work (Laravel)

node streamer/alpaca-stream.js

Makefile (optional):

up:
  php artisan serve &
  npm run dev --prefix resources/frontend
queue:
  php artisan queue:work --retry=3 --sleep=1
stream:
  node streamer/alpaca-stream.js
health:
  php artisan trading:health


Run Local

php artisan migrate --seed

Set .env keys for Alpaca paper

php artisan queue:work

node streamer/alpaca-stream.js

npm run dev

Visit dashboard; press Start; watch orders in paper.

Acceptance: End-to-end happy path works locally against Alpaca paper.

17) Future Work (Follow-ups after MVP)

Backtesting service & market replay from cached minute bars.

Multi-strategy orchestration (portfolio of symbols).

Additional brokers: InteractiveBrokersAdapter, BinanceAdapter, OandaAdapter.

Advanced order types (OCO, bracket).

Per-symbol risk budgets; daily rollover/reset at market close.

18) Task Breakdown (For Claude Code)

Instruction: Work task-by-task in order. After each task, ensure acceptance tests pass and code compiles. Use the specified file paths.

Task A — Project Setup

Create Laravel 11 app + Sanctum; configure Pusher broadcasting.

Create Vue 3 (Breeze) scaffold with Tailwind; install Echo client.

Add .env.example keys above.

Done when: app boots; an “/health” endpoint returns {status:"ok", mode:"paper"}.

Task B — Migrations & Models

Implement migrations for all tables listed in §4.

Create Eloquent models with relationships & casts (config/raw as JSON).

Done when: php artisan migrate runs; Tinker can create rows.

Task C — Contracts & DTOs

Add BrokerAdapter, OrderRequest, Signal, Strategy interfaces/DTOs.

Done when: code compiles; basic unit test for DTO construction passes.

Task D — AlpacaAdapter (REST)

Implement with Guzzle; headers from env.

Methods: getAccount, getPositions, placeOrder, cancelOrder, closeAllPositions.

Add config to switch paper/live via env.

Done when: Unit tests (Guzzle mocked) pass and mappings are correct.

Task E — Node Stream Helper + Webhook

Create /streamer/alpaca-stream.js using ws to connect, subscribe to quotes/bars and account updates (if available).

Post normalized events to Laravel /api/stream/alpaca with HMAC header.

Implement AlpacaStreamController@ingest: verify HMAC, persist, broadcast.

Done when: Running the Node process produces incoming webhook events and broadcasting reaches front-end (temporary console log).

Task F — Risk & Panic

Implement RiskGuard and PanicService.

Wire PanicController@flatten endpoint (auth required).

Done when: Unit tests cover allow/deny; Panic endpoint triggers adapter calls (mocked) and stops runs.

Task G — Strategy: SMA

Implement Strategies\SMA with config fields.

Implement Engine\Runner that reacts to incoming bars and dispatches ExecuteOrder jobs when signals fire.

Done when: Feed sample bars; verify one BUY then SELL with crosses.

Task H — Jobs, Events, Broadcasting

Implement ExecuteOrder job with idempotent client_order_id.

Implement events: OrderUpdated, FillReceived, PositionUpdated, DecisionLogged, SystemHealth.

Done when: Orders placed in paper; order updates/positions broadcast to UI.

Task I — HTTP APIs

Implement controllers & routes in §11.

Add Sanctum-protected POST routes; open GET routes for read-only in local.

Done when: Frontend can load /api/account, /api/positions, /api/orders, start/stop strategy, and panic.

Task J — Vue UI

Create Dashboard.vue and Strategy.vue with stores and components as per §13.

Wire Echo subscriptions; render live updates.

Done when: A user can start the strategy, watch orders/fills/positions update in real-time, and press Panic to flatten.

Task K — Tests

Unit tests: adapter mapping, risk guard, SMA logic.

Feature tests: strategy control endpoints; webhook HMAC.

Done when: php artisan test passes.

19) Acceptance Demo Script

Start services: queue worker, Node streamer, Vite dev, Laravel server.

In UI, confirm PAPER badge and health = ok.

Set strategy config: AAPL, qty 10, SMA 9/21, interval 1Min.

Click Start; verify logs show “runner started”.

When a cross occurs (or simulate via test bar), verify BUY order placed and appears in Orders; Position shows 10 AAPL.

Verify fills populate; Day P&L updates; Decision logs stream.

Click Panic — all open orders canceled, positions closed, run stopped, WARN logged.

20) Notes & Guardrails

Paper vs Live: UI must show a conspicuous banner; write endpoints must check TRADING_MODE. Do not allow live trading without explicit env.

Idempotency: Use client_order_id = uuid() per order; retries must not duplicate.

Rate limits: Implement Guzzle middleware with exponential backoff on 429/5xx.

Logging: Every decision (signal, order, risk denial) should land in decision_logs.

Secrets: Never log API keys or webhook secrets.

That’s it. Execute Tasks A → K in order. If any ambiguity arises, prefer shipping a thin vertical slice that satisfies the Acceptance Demo Script, then iterate.