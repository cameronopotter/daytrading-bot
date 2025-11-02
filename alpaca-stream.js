import WebSocket from 'ws';
import axios from 'axios';
import crypto from 'crypto';
import dotenv from 'dotenv';
import fs from 'fs';
import path from 'path';

// Load environment variables from .env file
dotenv.config();

// Configuration
const ALPACA_KEY_ID = process.env.ALPACA_KEY_ID;
const ALPACA_SECRET = process.env.ALPACA_SECRET;
const ALPACA_DATA_WS = process.env.ALPACA_DATA_WS || 'wss://stream.data.alpaca.markets/v2/sip';
const WEBHOOK_SECRET = process.env.STREAM_WEBHOOK_SECRET;
const WEBHOOK_URL = process.env.STREAM_WEBHOOK_URL || 'http://localhost:8000/api/stream/alpaca';

// Symbols to subscribe to (you can make this dynamic later)
const SYMBOLS = ['AAPL'];

// Validate configuration
if (!ALPACA_KEY_ID || !ALPACA_SECRET) {
    console.error('❌ ERROR: Missing Alpaca credentials in .env file');
    console.error('   Please set ALPACA_KEY_ID and ALPACA_SECRET');
    process.exit(1);
}

if (!WEBHOOK_SECRET) {
    console.error('❌ ERROR: Missing STREAM_WEBHOOK_SECRET in .env file');
    process.exit(1);
}

console.log('🚀 Alpaca Market Data Streamer Starting...');
console.log(`📡 WebSocket URL: ${ALPACA_DATA_WS}`);
console.log(`🎯 Symbols: ${SYMBOLS.join(', ')}`);
console.log(`🔗 Webhook: ${WEBHOOK_URL}`);
console.log('');

let ws = null;
let reconnectAttempts = 0;
const MAX_RECONNECT_ATTEMPTS = 10;
const RECONNECT_DELAY = 5000;

// Generate HMAC signature for webhook
function signPayload(payload) {
    const payloadStr = JSON.stringify(payload);
    return crypto
        .createHmac('sha256', WEBHOOK_SECRET)
        .update(payloadStr)
        .digest('hex');
}

// Send event to Laravel webhook
async function sendToWebhook(type, data) {
    try {
        const payload = { type, data };
        const signature = signPayload(payload);

        const response = await axios.post(WEBHOOK_URL, payload, {
            headers: {
                'X-Stream-Signature': signature,
                'Content-Type': 'application/json',
            },
            timeout: 5000,
        });

        console.log(`✅ Sent ${type} to webhook: ${data.symbol || 'N/A'}`);
    } catch (error) {
        console.error(`❌ Failed to send ${type} to webhook:`, error.message);
        if (error.response) {
            console.error('   Response:', error.response.status, error.response.data);
        }
    }
}

// Handle incoming WebSocket messages
function handleMessage(message) {
    try {
        const data = JSON.parse(message);

        // Handle different message types
        data.forEach((msg) => {
            const msgType = msg.T;

            switch (msgType) {
                case 'success':
                    console.log('✅ Connected:', msg.msg);
                    break;

                case 'subscription':
                    console.log('📊 Subscriptions confirmed:', JSON.stringify(msg));
                    break;

                case 'error':
                    console.error('❌ Alpaca error:', msg.msg, msg.code);
                    break;

                case 'b': // Bar (minute bar)
                    const bar = {
                        symbol: msg.S,
                        open: msg.o,
                        high: msg.h,
                        low: msg.l,
                        close: msg.c,
                        volume: msg.v,
                        timestamp: msg.t,
                    };
                    console.log(`📊 BAR: ${bar.symbol} @ $${bar.close} (${new Date(bar.timestamp).toISOString()})`);
                    sendToWebhook('bar', bar);
                    break;

                case 'q': // Quote
                    const quote = {
                        symbol: msg.S,
                        bid: msg.bp,
                        ask: msg.ap,
                        bid_size: msg.bs,
                        ask_size: msg.as,
                        timestamp: msg.t,
                    };
                    // Don't log quotes (too noisy), just send to webhook
                    sendToWebhook('quote', quote);
                    break;

                case 't': // Trade
                    const trade = {
                        symbol: msg.S,
                        price: msg.p,
                        size: msg.s,
                        timestamp: msg.t,
                    };
                    console.log(`💸 TRADE: ${trade.symbol} @ $${trade.price} x ${trade.size}`);
                    sendToWebhook('trade', trade);
                    break;

                default:
                    console.log('🔔 Unknown message type:', msgType, msg);
            }
        });
    } catch (error) {
        console.error('❌ Error parsing message:', error.message);
        console.error('   Raw message:', message);
    }
}

// Connect to Alpaca WebSocket
function connect() {
    console.log(`🔌 Connecting to Alpaca WebSocket... (attempt ${reconnectAttempts + 1})`);

    ws = new WebSocket(ALPACA_DATA_WS);

    ws.on('open', () => {
        console.log('✅ WebSocket connected!');
        reconnectAttempts = 0;

        // Authenticate
        const authMsg = {
            action: 'auth',
            key: ALPACA_KEY_ID,
            secret: ALPACA_SECRET,
        };
        ws.send(JSON.stringify(authMsg));
        console.log('🔐 Sent authentication...');

        // Subscribe to bars (minute bars) after a short delay to ensure auth completes
        setTimeout(() => {
            const subscribeMsg = {
                action: 'subscribe',
                bars: SYMBOLS,
            };
            ws.send(JSON.stringify(subscribeMsg));
            console.log(`📡 Subscribed to bars: ${SYMBOLS.join(', ')}`);
        }, 1000);
    });

    ws.on('message', (data) => {
        handleMessage(data.toString());
    });

    ws.on('error', (error) => {
        console.error('❌ WebSocket error:', error.message);
    });

    ws.on('close', (code, reason) => {
        console.warn(`⚠️  WebSocket closed: ${code} - ${reason || 'No reason provided'}`);

        // Attempt to reconnect
        if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
            reconnectAttempts++;
            console.log(`🔄 Reconnecting in ${RECONNECT_DELAY / 1000} seconds...`);
            setTimeout(connect, RECONNECT_DELAY);
        } else {
            console.error('❌ Max reconnection attempts reached. Exiting.');
            process.exit(1);
        }
    });
}

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\n👋 Shutting down gracefully...');
    if (ws) {
        ws.close();
    }
    process.exit(0);
});

process.on('SIGTERM', () => {
    console.log('\n👋 Shutting down gracefully...');
    if (ws) {
        ws.close();
    }
    process.exit(0);
});

// Start the streamer
connect();
