<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const mode = ref('paper');
const health = ref({});
const account = ref({});
const positions = ref([]);
const orders = ref([]);
const fills = ref([]);
const strategyStatus = ref('stopped');
const strategyConfig = ref({});
const isStarting = ref(false);
const isStopping = ref(false);
const isPanicking = ref(false);
const showConfigModal = ref(false);
const isSavingConfig = ref(false);

const fetchData = async () => {
    try {
        const [healthRes, accountRes, positionsRes, ordersRes, fillsRes, strategyRes] = await Promise.all([
            fetch('/api/health'),
            fetch('/api/account'),
            fetch('/api/positions'),
            fetch('/api/orders'),
            fetch('/api/fills'),
            fetch('/api/strategy/config'),
        ]);

        health.value = await healthRes.json();
        mode.value = health.value.mode || 'paper';

        if (accountRes.ok) account.value = await accountRes.json();
        if (positionsRes.ok) positions.value = await positionsRes.json();
        if (ordersRes.ok) orders.value = await ordersRes.json();
        if (fillsRes.ok) fills.value = await fillsRes.json();

        const strategy = await strategyRes.json();
        const defaultConfig = {
            symbol: 'AAPL',
            qty: 10,
            fast: 9,
            slow: 21,
            bar_interval: '1Min',
            atr_period: 14,
            stop_loss_atr_multiplier: 2.0,
            take_profit_atr_multiplier: 3.0,
            use_trailing_stop: true,
            trailing_stop_atr_multiplier: 2.0,
            use_regime_filter: true,
            adx_period: 14,
            adx_trending_threshold: 25,
            adx_ranging_threshold: 20,
            bb_period: 20,
            bb_std_dev: 2.0,
            use_rsi_filter: true,
            rsi_period: 14,
            rsi_overbought: 70,
            rsi_oversold: 30,
            use_macd_confirmation: true,
            macd_fast: 12,
            macd_slow: 26,
            macd_signal: 9,
            use_volume_filter: true,
            volume_period: 20,
            volume_multiplier: 1.5,
            use_time_filter: true,
            use_dynamic_sizing: true,
            risk_per_trade: 0.01,
            max_position_size: 100,
            account_balance: 100000,
        };
        strategyConfig.value = { ...defaultConfig, ...(strategy.config || {}) };
        const activeRun = strategy.runs?.find(r => r.status === 'running');
        strategyStatus.value = activeRun ? 'running' : 'stopped';
    } catch (error) {
        console.error('Failed to fetch data:', error);
    }
};

const saveConfig = async () => {
    isSavingConfig.value = true;
    try {
        const response = await fetch('/api/strategy/config', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ config: strategyConfig.value }),
        });

        if (response.ok) {
            showConfigModal.value = false;
            await fetchData();
        }
    } catch (error) {
        console.error('Failed to save config:', error);
    } finally {
        isSavingConfig.value = false;
    }
};

const startStrategy = async () => {
    if (isStarting.value) return;
    isStarting.value = true;

    try {
        const response = await fetch('/api/strategy/start', { method: 'POST' });
        if (response.ok) {
            strategyStatus.value = 'running';
            await fetchData();
        }
    } catch (error) {
        console.error('Failed to start strategy:', error);
    } finally {
        isStarting.value = false;
    }
};

const stopStrategy = async () => {
    if (isStopping.value) return;
    isStopping.value = true;

    try {
        const response = await fetch('/api/strategy/stop', { method: 'POST' });
        if (response.ok) {
            strategyStatus.value = 'stopped';
            await fetchData();
        }
    } catch (error) {
        console.error('Failed to stop strategy:', error);
    } finally {
        isStopping.value = false;
    }
};

const panic = async () => {
    if (isPanicking.value) return;
    if (!confirm('⚠️ PANIC: This will close ALL positions and cancel ALL orders. Continue?')) return;

    isPanicking.value = true;

    try {
        const response = await fetch('/api/panic', { method: 'POST' });
        if (response.ok) {
            alert('✅ Panic executed successfully');
            await fetchData();
        }
    } catch (error) {
        console.error('Failed to execute panic:', error);
        alert('❌ Panic failed: ' + error.message);
    } finally {
        isPanicking.value = false;
    }
};

onMounted(() => {
    fetchData();
    setInterval(fetchData, 5000);
});

const formatNumber = (num, decimals = 2) => {
    if (!num) return '0.00';
    return parseFloat(num).toFixed(decimals);
};

const formatCurrency = (num) => {
    if (!num) return '$0.00';
    return '$' + parseFloat(num).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString();
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-white leading-tight">Trading Terminal</h2>
                <div class="flex items-center gap-3">
                    <button
                        @click="showConfigModal = true"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg transition-colors text-sm font-medium border border-slate-700"
                    >
                        ⚙️ Strategy Config
                    </button>
                    <span :class="mode === 'paper' ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30'"
                          class="px-3 py-1.5 rounded-lg font-bold text-sm border">
                        {{ mode.toUpperCase() }}
                    </span>
                    <div class="flex items-center gap-2">
                        <span :class="health.status === 'ok' ? 'bg-emerald-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
                        <span :class="health.status === 'ok' ? 'text-emerald-400' : 'text-red-400'" class="font-medium text-sm">
                            {{ health.status === 'ok' ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Control Panel -->
                <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex gap-3">
                            <button
                                @click="startStrategy"
                                :disabled="strategyStatus === 'running' || isStarting"
                                class="px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 disabled:from-slate-700 disabled:to-slate-700 disabled:text-slate-500 text-white rounded-lg transition-all font-medium shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 disabled:cursor-not-allowed disabled:shadow-none"
                            >
                                {{ isStarting ? 'Starting...' : 'Start' }}
                            </button>

                            <button
                                @click="stopStrategy"
                                :disabled="strategyStatus === 'stopped' || isStopping"
                                class="px-6 py-2.5 bg-slate-700 hover:bg-slate-600 disabled:bg-slate-800 disabled:text-slate-600 text-white rounded-lg transition-all font-medium border border-slate-600 disabled:cursor-not-allowed disabled:border-slate-700"
                            >
                                {{ isStopping ? 'Stopping...' : 'Stop' }}
                            </button>

                            <button
                                @click="panic"
                                :disabled="isPanicking"
                                class="px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-slate-800 disabled:text-slate-600 text-white rounded-lg transition-all font-medium shadow-lg shadow-red-500/20 hover:shadow-red-500/30 disabled:cursor-not-allowed disabled:shadow-none"
                            >
                                {{ isPanicking ? 'Executing...' : 'PANIC' }}
                            </button>
                        </div>

                        <div class="flex items-center gap-3 px-4 py-2 bg-slate-800/50 rounded-lg border border-slate-700">
                            <span class="text-sm text-slate-400 font-medium">Status:</span>
                            <div class="flex items-center gap-2">
                                <span :class="strategyStatus === 'running' ? 'bg-emerald-500 animate-pulse' : 'bg-slate-600'" class="w-2 h-2 rounded-full"></span>
                                <span :class="strategyStatus === 'running' ? 'text-emerald-400' : 'text-slate-400'" class="font-bold text-sm">
                                    {{ strategyStatus === 'running' ? 'RUNNING' : 'STOPPED' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Equity</div>
                        <div class="text-2xl font-bold text-white">{{ formatCurrency(account.equity) }}</div>
                    </div>

                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Buying Power</div>
                        <div class="text-2xl font-bold text-white">{{ formatCurrency(account.buying_power) }}</div>
                    </div>

                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Day P&L</div>
                        <div :class="parseFloat(account.equity) - parseFloat(account.last_equity) >= 0 ? 'text-emerald-400' : 'text-red-400'" class="text-2xl font-bold">
                            {{ formatCurrency(parseFloat(account.equity || 0) - parseFloat(account.last_equity || 0)) }}
                        </div>
                    </div>

                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Open Positions</div>
                        <div class="text-2xl font-bold text-white">{{ positions.length }}</div>
                    </div>
                </div>

                <!-- Positions -->
                <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl backdrop-blur-sm">
                    <div class="px-6 py-4 border-b border-slate-800">
                        <h3 class="text-lg font-semibold text-white">Open Positions</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800">
                            <thead class="bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Avg Entry</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Unrealized P&L</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr v-for="position in positions" :key="position.id" class="hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-white">{{ position.symbol }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">{{ formatNumber(position.qty, 4) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">{{ formatCurrency(position.avg_entry_price) }}</td>
                                    <td :class="position.unrealized_pl >= 0 ? 'text-emerald-400' : 'text-red-400'" class="px-6 py-4 whitespace-nowrap font-bold">
                                        {{ formatCurrency(position.unrealized_pl) }}
                                    </td>
                                </tr>
                                <tr v-if="positions.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-500">No open positions</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Orders -->
                <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl backdrop-blur-sm">
                    <div class="px-6 py-4 border-b border-slate-800">
                        <h3 class="text-lg font-semibold text-white">Recent Orders</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800">
                            <thead class="bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Side</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Filled</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr v-for="order in orders.slice(0, 10)" :key="order.id" class="hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ formatDate(order.placed_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-white">{{ order.symbol }}</td>
                                    <td :class="order.side === 'buy' ? 'text-emerald-400' : 'text-red-400'" class="px-6 py-4 whitespace-nowrap font-bold uppercase text-sm">
                                        {{ order.side }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">{{ formatNumber(order.qty, 4) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="{
                                            'bg-emerald-500/20 text-emerald-400 border-emerald-500/30': order.status === 'filled',
                                            'bg-blue-500/20 text-blue-400 border-blue-500/30': order.status === 'new' || order.status === 'partially_filled',
                                            'bg-slate-500/20 text-slate-400 border-slate-500/30': order.status === 'canceled',
                                            'bg-red-500/20 text-red-400 border-red-500/30': order.status === 'rejected'
                                        }" class="px-2 py-1 rounded border text-xs font-semibold uppercase">
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300 text-sm">{{ formatNumber(order.filled_qty, 4) }} / {{ formatNumber(order.qty, 4) }}</td>
                                </tr>
                                <tr v-if="orders.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">No orders</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Fills -->
                <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl backdrop-blur-sm">
                    <div class="px-6 py-4 border-b border-slate-800">
                        <h3 class="text-lg font-semibold text-white">Recent Fills</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800">
                            <thead class="bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Side</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr v-for="fill in fills.slice(0, 10)" :key="fill.id" class="hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ formatDate(fill.fill_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-white">{{ fill.symbol }}</td>
                                    <td :class="fill.side === 'buy' ? 'text-emerald-400' : 'text-red-400'" class="px-6 py-4 whitespace-nowrap font-bold uppercase text-sm">
                                        {{ fill.side }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">{{ formatNumber(fill.qty, 4) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">{{ formatCurrency(fill.price) }}</td>
                                </tr>
                                <tr v-if="fills.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-500">No fills</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strategy Config Modal -->
        <div v-if="showConfigModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-white">Strategy Configuration</h3>
                    <button @click="showConfigModal = false" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                    <!-- Basic Configuration -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-semibold text-white uppercase tracking-wider">Basic Configuration</h4>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Symbol</label>
                                <input v-model="strategyConfig.symbol" type="text" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="AAPL" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Bar Interval</label>
                                <select v-model="strategyConfig.bar_interval" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="1Min">1 Minute</option>
                                    <option value="5Min">5 Minutes</option>
                                    <option value="15Min">15 Minutes</option>
                                    <option value="1Hour">1 Hour</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Fast SMA</label>
                                <input v-model.number="strategyConfig.fast" type="number" min="1" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="9" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Slow SMA</label>
                                <input v-model.number="strategyConfig.slow" type="number" min="1" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="21" />
                            </div>
                        </div>
                    </div>

                    <!-- Risk Management -->
                    <div class="space-y-4 pt-4 border-t border-slate-800">
                        <h4 class="text-sm font-semibold text-white uppercase tracking-wider">Risk Management (ATR-Based)</h4>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">ATR Period</label>
                                <input v-model.number="strategyConfig.atr_period" type="number" min="1" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="14" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Stop-Loss (x ATR)</label>
                                <input v-model.number="strategyConfig.stop_loss_atr_multiplier" type="number" min="0.1" step="0.1" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="2.0" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Take-Profit (x ATR)</label>
                                <input v-model.number="strategyConfig.take_profit_atr_multiplier" type="number" min="0.1" step="0.1" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="3.0" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input v-model="strategyConfig.use_trailing_stop" type="checkbox" id="use_trailing_stop" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                            <label for="use_trailing_stop" class="text-sm text-slate-300">Enable Trailing Stop (2x ATR)</label>
                        </div>
                    </div>

                    <!-- Position Sizing -->
                    <div class="space-y-4 pt-4 border-t border-slate-800">
                        <h4 class="text-sm font-semibold text-white uppercase tracking-wider">Position Sizing</h4>

                        <div class="flex items-center gap-3 mb-4">
                            <input v-model="strategyConfig.use_dynamic_sizing" type="checkbox" id="use_dynamic_sizing" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                            <label for="use_dynamic_sizing" class="text-sm text-slate-300">Use Dynamic Sizing (Recommended)</label>
                        </div>

                        <div v-if="!strategyConfig.use_dynamic_sizing">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Fixed Quantity</label>
                            <input v-model.number="strategyConfig.qty" type="number" min="1" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="10" />
                        </div>

                        <div v-else class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Risk Per Trade (%)</label>
                                <input v-model.number="strategyConfig.risk_per_trade" type="number" min="0.001" max="0.1" step="0.001" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="0.01" />
                                <p class="text-xs text-slate-500 mt-1">0.01 = 1% of account</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Max Position Size</label>
                                <input v-model.number="strategyConfig.max_position_size" type="number" min="1" class="w-full px-4 py-2 bg-slate-950 border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="100" />
                            </div>
                        </div>
                    </div>

                    <!-- Filters & Confirmations -->
                    <div class="space-y-4 pt-4 border-t border-slate-800">
                        <h4 class="text-sm font-semibold text-white uppercase tracking-wider">Filters & Confirmations</h4>

                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <input v-model="strategyConfig.use_regime_filter" type="checkbox" id="use_regime_filter" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                                <label for="use_regime_filter" class="text-sm text-slate-300">Market Regime Detection (ADX + Bollinger Bands)</label>
                            </div>

                            <div class="flex items-center gap-3">
                                <input v-model="strategyConfig.use_rsi_filter" type="checkbox" id="use_rsi_filter" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                                <label for="use_rsi_filter" class="text-sm text-slate-300">RSI Overbought/Oversold Filter</label>
                            </div>

                            <div class="flex items-center gap-3">
                                <input v-model="strategyConfig.use_macd_confirmation" type="checkbox" id="use_macd_confirmation" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                                <label for="use_macd_confirmation" class="text-sm text-slate-300">MACD Momentum Confirmation</label>
                            </div>

                            <div class="flex items-center gap-3">
                                <input v-model="strategyConfig.use_volume_filter" type="checkbox" id="use_volume_filter" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                                <label for="use_volume_filter" class="text-sm text-slate-300">Volume Confirmation (1.5x average)</label>
                            </div>

                            <div class="flex items-center gap-3">
                                <input v-model="strategyConfig.use_time_filter" type="checkbox" id="use_time_filter" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                                <label for="use_time_filter" class="text-sm text-slate-300">Time-of-Day Filter (9:30-10:30 AM, 3-4 PM)</label>
                            </div>
                        </div>
                    </div>

                    <!-- Info Box -->
                    <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-emerald-300">
                                <p class="font-medium mb-1">Enhanced SMA Strategy</p>
                                <p class="text-emerald-400/80">Professional-grade SMA crossover with market regime detection, ATR-based stops, dynamic position sizing, and multiple confirmation filters for optimal performance.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-950/50 border-t border-slate-800 flex justify-end gap-3">
                    <button
                        @click="showConfigModal = false"
                        class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveConfig"
                        :disabled="isSavingConfig"
                        class="px-4 py-2 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 disabled:from-slate-700 disabled:to-slate-700 disabled:text-slate-500 text-white rounded-lg transition-colors font-medium disabled:cursor-not-allowed shadow-lg shadow-emerald-500/20"
                    >
                        {{ isSavingConfig ? 'Saving...' : 'Save Configuration' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
