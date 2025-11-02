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
        strategyConfig.value = strategy.config || {};
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
    <Head title="Trading Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-100 leading-tight">Trading Terminal</h2>
                <div class="flex items-center gap-3">
                    <button
                        @click="showConfigModal = true"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded-lg transition-colors text-sm font-medium"
                    >
                        ⚙️ Strategy Config
                    </button>
                    <span :class="mode === 'paper' ? 'bg-amber-500/20 text-amber-400 border-amber-500/30' : 'bg-red-500/20 text-red-400 border-red-500/30'"
                          class="px-3 py-1.5 rounded-lg font-bold text-sm border">
                        {{ mode.toUpperCase() }}
                    </span>
                    <div class="flex items-center gap-2">
                        <span :class="health.status === 'ok' ? 'bg-green-500' : 'bg-red-500'" class="w-2 h-2 rounded-full"></span>
                        <span :class="health.status === 'ok' ? 'text-green-400' : 'text-red-400'" class="font-medium text-sm">
                            {{ health.status === 'ok' ? 'Online' : 'Offline' }}
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Control Panel -->
                <div class="bg-gray-800 border border-gray-700 overflow-hidden shadow-xl rounded-xl p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex gap-3">
                            <button
                                @click="startStrategy"
                                :disabled="strategyStatus === 'running' || isStarting"
                                class="px-6 py-2.5 bg-green-600 hover:bg-green-700 disabled:bg-gray-700 disabled:text-gray-500 text-white rounded-lg transition-all font-medium shadow-lg hover:shadow-xl disabled:cursor-not-allowed disabled:shadow-none"
                            >
                                {{ isStarting ? 'Starting...' : 'Start' }}
                            </button>

                            <button
                                @click="stopStrategy"
                                :disabled="strategyStatus === 'stopped' || isStopping"
                                class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-700 disabled:text-gray-500 text-white rounded-lg transition-all font-medium shadow-lg hover:shadow-xl disabled:cursor-not-allowed disabled:shadow-none"
                            >
                                {{ isStopping ? 'Stopping...' : 'Stop' }}
                            </button>

                            <button
                                @click="panic"
                                :disabled="isPanicking"
                                class="px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:bg-gray-700 disabled:text-gray-500 text-white rounded-lg transition-all font-medium shadow-lg hover:shadow-xl disabled:cursor-not-allowed disabled:shadow-none"
                            >
                                {{ isPanicking ? 'Executing...' : 'PANIC' }}
                            </button>
                        </div>

                        <div class="flex items-center gap-3 px-4 py-2 bg-gray-900/50 rounded-lg border border-gray-700">
                            <span class="text-sm text-gray-400 font-medium">Status:</span>
                            <div class="flex items-center gap-2">
                                <span :class="strategyStatus === 'running' ? 'bg-green-500 animate-pulse' : 'bg-gray-600'" class="w-2 h-2 rounded-full"></span>
                                <span :class="strategyStatus === 'running' ? 'text-green-400' : 'text-gray-400'" class="font-bold text-sm">
                                    {{ strategyStatus === 'running' ? 'RUNNING' : 'STOPPED' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-gray-800 border border-gray-700 overflow-hidden shadow-xl rounded-xl p-6">
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Equity</div>
                        <div class="text-2xl font-bold text-gray-100">{{ formatCurrency(account.equity) }}</div>
                    </div>

                    <div class="bg-gray-800 border border-gray-700 overflow-hidden shadow-xl rounded-xl p-6">
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Buying Power</div>
                        <div class="text-2xl font-bold text-gray-100">{{ formatCurrency(account.buying_power) }}</div>
                    </div>

                    <div class="bg-gray-800 border border-gray-700 overflow-hidden shadow-xl rounded-xl p-6">
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Day P&L</div>
                        <div :class="parseFloat(account.equity) - parseFloat(account.last_equity) >= 0 ? 'text-green-400' : 'text-red-400'" class="text-2xl font-bold">
                            {{ formatCurrency(parseFloat(account.equity || 0) - parseFloat(account.last_equity || 0)) }}
                        </div>
                    </div>

                    <div class="bg-gray-800 border border-gray-700 overflow-hidden shadow-xl rounded-xl p-6">
                        <div class="text-xs text-gray-400 uppercase tracking-wider mb-2">Open Positions</div>
                        <div class="text-2xl font-bold text-gray-100">{{ positions.length }}</div>
                    </div>
                </div>

                <!-- Positions -->
                <div class="bg-gray-800 border border-gray-700 overflow-hidden shadow-xl rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-100">Open Positions</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Avg Entry</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Unrealized P&L</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="position in positions" :key="position.id" class="hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-100">{{ position.symbol }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ formatNumber(position.qty, 4) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ formatCurrency(position.avg_entry_price) }}</td>
                                    <td :class="position.unrealized_pl >= 0 ? 'text-green-400' : 'text-red-400'" class="px-6 py-4 whitespace-nowrap font-bold">
                                        {{ formatCurrency(position.unrealized_pl) }}
                                    </td>
                                </tr>
                                <tr v-if="positions.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">No open positions</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Orders -->
                <div class="bg-gray-800 border border-gray-700 overflow-hidden shadow-xl rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-100">Recent Orders</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Side</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Filled</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="order in orders.slice(0, 10)" :key="order.id" class="hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ formatDate(order.placed_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-100">{{ order.symbol }}</td>
                                    <td :class="order.side === 'buy' ? 'text-green-400' : 'text-red-400'" class="px-6 py-4 whitespace-nowrap font-bold uppercase text-sm">
                                        {{ order.side }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ formatNumber(order.qty, 4) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="{
                                            'bg-green-500/20 text-green-400 border-green-500/30': order.status === 'filled',
                                            'bg-blue-500/20 text-blue-400 border-blue-500/30': order.status === 'new' || order.status === 'partially_filled',
                                            'bg-gray-500/20 text-gray-400 border-gray-500/30': order.status === 'canceled',
                                            'bg-red-500/20 text-red-400 border-red-500/30': order.status === 'rejected'
                                        }" class="px-2 py-1 rounded border text-xs font-semibold uppercase">
                                            {{ order.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300 text-sm">{{ formatNumber(order.filled_qty, 4) }} / {{ formatNumber(order.qty, 4) }}</td>
                                </tr>
                                <tr v-if="orders.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">No orders</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Fills -->
                <div class="bg-gray-800 border border-gray-700 overflow-hidden shadow-xl rounded-xl">
                    <div class="px-6 py-4 border-b border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-100">Recent Fills</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Symbol</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Side</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="fill in fills.slice(0, 10)" :key="fill.id" class="hover:bg-gray-700/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">{{ formatDate(fill.fill_at) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-gray-100">{{ fill.symbol }}</td>
                                    <td :class="fill.side === 'buy' ? 'text-green-400' : 'text-red-400'" class="px-6 py-4 whitespace-nowrap font-bold uppercase text-sm">
                                        {{ fill.side }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ formatNumber(fill.qty, 4) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-300">{{ formatCurrency(fill.price) }}</td>
                                </tr>
                                <tr v-if="fills.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No fills</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strategy Config Modal -->
        <div v-if="showConfigModal" class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div class="bg-gray-800 border border-gray-700 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between">
                    <h3 class="text-xl font-semibold text-gray-100">Strategy Configuration</h3>
                    <button @click="showConfigModal = false" class="text-gray-400 hover:text-gray-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Symbol</label>
                        <input
                            v-model="strategyConfig.symbol"
                            type="text"
                            class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="AAPL"
                        />
                        <p class="mt-1 text-xs text-gray-500">Stock ticker symbol to trade</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Quantity</label>
                        <input
                            v-model.number="strategyConfig.qty"
                            type="number"
                            min="1"
                            class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="10"
                        />
                        <p class="mt-1 text-xs text-gray-500">Number of shares per trade</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Fast SMA Period</label>
                            <input
                                v-model.number="strategyConfig.fast"
                                type="number"
                                min="1"
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="9"
                            />
                            <p class="mt-1 text-xs text-gray-500">Fast moving average period</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-2">Slow SMA Period</label>
                            <input
                                v-model.number="strategyConfig.slow"
                                type="number"
                                min="1"
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="21"
                            />
                            <p class="mt-1 text-xs text-gray-500">Slow moving average period</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Bar Interval</label>
                        <select
                            v-model="strategyConfig.bar_interval"
                            class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="1Min">1 Minute</option>
                            <option value="5Min">5 Minutes</option>
                            <option value="15Min">15 Minutes</option>
                            <option value="1Hour">1 Hour</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Time frame for strategy execution</p>
                    </div>

                    <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="text-sm text-blue-300">
                                <p class="font-medium mb-1">Strategy: SMA Crossover</p>
                                <p class="text-blue-400/80">Buy when fast SMA crosses above slow SMA. Sell when fast SMA crosses below slow SMA.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-900/50 border-t border-gray-700 flex justify-end gap-3">
                    <button
                        @click="showConfigModal = false"
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded-lg transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button
                        @click="saveConfig"
                        :disabled="isSavingConfig"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-700 disabled:text-gray-500 text-white rounded-lg transition-colors font-medium disabled:cursor-not-allowed"
                    >
                        {{ isSavingConfig ? 'Saving...' : 'Save Configuration' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
