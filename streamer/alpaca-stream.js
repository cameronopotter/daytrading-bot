const WebSocket = require('ws');
const axios = require('axios');
const crypto = require('crypto');

// Configuration from environment
const ALPACA_KEY_ID = process.env.ALPACA_KEY_ID || '';
const ALPACA_SECRET = process.env.ALPACA_SECRET || '';
const ALPACA_DATA_WS = process.env.ALPACA_DATA_WS || 'wss://stream.data.alpaca.markets/v2/sip';
const ALPACA_TRADING_WS = process.env.ALPACA_TRADING_WS || 'wss://paper-api.alpaca.markets/stream';
const STREAM_WEBHOOK_URL = process.env.STREAM_WEBHOOK_URL || 'http://localhost:8000/api/stream/alpaca';
const STREAM_WEBHOOK_SECRET = process.env.STREAM_WEBHOOK_SECRET || 'change-me';
const SYMBOLS = (process.env.SYMBOLS || 'AAPL,MSFT,SPY').split(',');

console.log('[ALPACA STREAMER] Starting...');
console.log('[ALPACA STREAMER] Symbols:', SYMBOLS.join(', '));
console.log('[ALPACA STREAMER] Webhook URL:', STREAM_WEBHOOK_URL);

// HMAC signature generation
function generateSignature(payload) {
    const hmac = crypto.createHmac('sha256', STREAM_WEBHOOK_SECRET);
    hmac.update(JSON.stringify(payload));
    return hmac.digest('hex');
}

// Post event to Laravel webhook
async function postToLaravel(eventType, data) {
    try {
        const payload = {
            event_type: eventType,
            data: data,
            timestamp: new Date().toISOString(),
        };

        const signature = generateSignature(payload);

        await axios.post(STREAM_WEBHOOK_URL, payload, {
            headers: {
                'X-Stream-Signature': signature,
                'Content-Type': 'application/json',
            },
            timeout: 10000,
        });

        console.log(`[WEBHOOK] Posted ${eventType}`);
    } catch (error) {
        console.error(`[WEBHOOK ERROR] Failed to post ${eventType}:`, error.message);
    }
}

// Data Stream Connection
let dataWs;
function connectDataStream() {
    console.log('[DATA WS] Connecting...');
    dataWs = new WebSocket(ALPACA_DATA_WS);

    dataWs.on('open', () => {
        console.log('[DATA WS] Connected');
        // Authenticate
        dataWs.send(JSON.stringify({
            action: 'auth',
            key: ALPACA_KEY_ID,
            secret: ALPACA_SECRET,
        }));
    });

    dataWs.on('message', async (data) => {
        try {
            const messages = JSON.parse(data);
            if (!Array.isArray(messages)) return;

            for (const msg of messages) {
                if (msg.T === 'success' && msg.msg === 'authenticated') {
                    console.log('[DATA WS] Authenticated');
                    // Subscribe to bars, quotes, and trades
                    dataWs.send(JSON.stringify({
                        action: 'subscribe',
                        bars: SYMBOLS,
                        quotes: SYMBOLS,
                        trades: SYMBOLS,
                    }));
                } else if (msg.T === 'subscription') {
                    console.log('[DATA WS] Subscriptions:', msg);
                } else if (msg.T === 'b') {
                    // Bar (minute bar)
                    await postToLaravel('bar', {
                        symbol: msg.S,
                        timestamp: msg.t,
                        open: msg.o,
                        high: msg.h,
                        low: msg.l,
                        close: msg.c,
                        volume: msg.v,
                    });
                } else if (msg.T === 'q') {
                    // Quote
                    await postToLaravel('quote', {
                        symbol: msg.S,
                        timestamp: msg.t,
                        bid_price: msg.bp,
                        bid_size: msg.bs,
                        ask_price: msg.ap,
                        ask_size: msg.as,
                    });
                } else if (msg.T === 't') {
                    // Trade
                    await postToLaravel('trade', {
                        symbol: msg.S,
                        timestamp: msg.t,
                        price: msg.p,
                        size: msg.s,
                    });
                }
            }
        } catch (error) {
            console.error('[DATA WS ERROR]', error.message);
        }
    });

    dataWs.on('error', (error) => {
        console.error('[DATA WS ERROR]', error.message);
    });

    dataWs.on('close', () => {
        console.log('[DATA WS] Disconnected. Reconnecting in 5s...');
        setTimeout(connectDataStream, 5000);
    });
}

// Trading Stream Connection (account updates, orders, positions)
let tradingWs;
function connectTradingStream() {
    console.log('[TRADING WS] Connecting...');
    tradingWs = new WebSocket(ALPACA_TRADING_WS);

    tradingWs.on('open', () => {
        console.log('[TRADING WS] Connected');
        // Authenticate
        tradingWs.send(JSON.stringify({
            action: 'auth',
            key: ALPACA_KEY_ID,
            secret: ALPACA_SECRET,
        }));
    });

    tradingWs.on('message', async (data) => {
        try {
            const msg = JSON.parse(data);

            if (msg.stream === 'authorization' && msg.data.status === 'authorized') {
                console.log('[TRADING WS] Authenticated');
                // Subscribe to account updates
                tradingWs.send(JSON.stringify({
                    action: 'listen',
                    data: {
                        streams: ['trade_updates'],
                    },
                }));
            } else if (msg.stream === 'listening') {
                console.log('[TRADING WS] Subscriptions:', msg.data.streams);
            } else if (msg.stream === 'trade_updates') {
                // Order/Trade updates
                const event = msg.data.event; // fill, partial_fill, canceled, etc.
                const order = msg.data.order;

                await postToLaravel('order_update', {
                    event: event,
                    order: {
                        id: order.id,
                        client_order_id: order.client_order_id,
                        symbol: order.symbol,
                        side: order.side,
                        type: order.type,
                        qty: order.qty,
                        filled_qty: order.filled_qty,
                        limit_price: order.limit_price,
                        stop_price: order.stop_price,
                        status: order.status,
                        submitted_at: order.submitted_at,
                    },
                });
            }
        } catch (error) {
            console.error('[TRADING WS ERROR]', error.message);
        }
    });

    tradingWs.on('error', (error) => {
        console.error('[TRADING WS ERROR]', error.message);
    });

    tradingWs.on('close', () => {
        console.log('[TRADING WS] Disconnected. Reconnecting in 5s...');
        setTimeout(connectTradingStream, 5000);
    });
}

// Start connections
connectDataStream();
connectTradingStream();

// Handle process termination
process.on('SIGINT', () => {
    console.log('[ALPACA STREAMER] Shutting down...');
    if (dataWs) dataWs.close();
    if (tradingWs) tradingWs.close();
    process.exit(0);
});
