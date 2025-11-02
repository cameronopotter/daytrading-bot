<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const mode = ref('paper');
const health = ref({});
const account = ref({});
const positions = ref([]);
const orders = ref([]);
const fills = ref([]);
const strategyStatus = ref('stopped');
const isStarting = ref(false);
const isStopping = ref(false);
const isPanicking = ref(false);

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
        const activeRun = strategy.runs?.find(r => r.status === 'running');
        strategyStatus.value = activeRun ? 'running' : 'stopped';
    } catch (error) {
        console.error('Failed to fetch data:', error);
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
    // Refresh data every 5 seconds
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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Trading Dashboard</h2>
                <div class="flex items-center gap-4">
                    <Link :href="route('profile.edit')" class="text-sm text-gray-600 hover:text-gray-900">Strategy Config</Link>
                    <span :class="mode === 'paper' ? 'bg-yellow-500' : 'bg-red-500'" class="px-4 py-2 rounded-lg text-white font-bold text-lg">
                        {{ mode.toUpperCase() }}
                    </span>
                    <span :class="health.status === 'ok' ? 'text-green-600' : 'text-red-600'" class="font-semibold">
                        {{ health.status === 'ok' ? '● Online' : '● Offline' }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Control Buttons -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex gap-4">
                        <button
                            @click="startStrategy"
                            :disabled="strategyStatus === 'running' || isStarting"
                            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold"
                        >
                            {{ isStarting ? 'Starting...' : 'Start Strategy' }}
                        </button>

                        <button
                            @click="stopStrategy"
                            :disabled="strategyStatus === 'stopped' || isStopping"
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold"
                        >
                            {{ isStopping ? 'Stopping...' : 'Stop Strategy' }}
                        </button>

                        <button
                            @click="panic"
                            :disabled="isPanicking"
                            class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold"
                        >
                            {{ isPanicking ? 'Executing...' : '⚠️ PANIC' }}
                        </button>

                        <div class="ml-auto flex items-center">
                            <span class="text-sm text-gray-600 mr-2">Status:</span>
                            <span :class="strategyStatus === 'running' ? 'text-green-600' : 'text-gray-600'" class="font-bold">
                                {{ strategyStatus === 'running' ? '▶ RUNNING' : '■ STOPPED' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Account Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Equity</div>
                        <div class="text-2xl font-bold text-gray-900">{{ formatCurrency(account.equity) }}</div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Buying Power</div>
                        <div class="text-2xl font-bold text-gray-900">{{ formatCurrency(account.buying_power) }}</div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Day P&L</div>
                        <div :class="parseFloat(account.equity) - parseFloat(account.last_equity) >= 0 ? 'text-green-600' : 'text-red-600'" class="text-2xl font-bold">
                            {{ formatCurrency(parseFloat(account.equity || 0) - parseFloat(account.last_equity || 0)) }}
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="text-sm text-gray-600">Open Positions</div>
                        <div class="text-2xl font-bold text-gray-900">{{ positions.length }}</div>
                    </div>
                </div>

                <!-- Positions Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Open Positions</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symbol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Entry</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unrealized P&L</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="position in positions" :key="position.id">
                                        <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ position.symbol }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatNumber(position.qty, 4) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(position.avg_entry_price) }}</td>
                                        <td :class="position.unrealized_pl >= 0 ? 'text-green-600' : 'text-red-600'" class="px-6 py-4 whitespace-nowrap font-semibold">
                                            {{ formatCurrency(position.unrealized_pl) }}
                                        </td>
                                    </tr>
                                    <tr v-if="positions.length === 0">
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No open positions</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Orders</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symbol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Side</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Filled</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="order in orders.slice(0, 10)" :key="order.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(order.placed_at) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ order.symbol }}</td>
                                        <td :class="order.side === 'buy' ? 'text-green-600' : 'text-red-600'" class="px-6 py-4 whitespace-nowrap font-semibold uppercase">
                                            {{ order.side }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatNumber(order.qty, 4) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{
                                                'bg-green-100 text-green-800': order.status === 'filled',
                                                'bg-blue-100 text-blue-800': order.status === 'new' || order.status === 'partially_filled',
                                                'bg-gray-100 text-gray-800': order.status === 'canceled',
                                                'bg-red-100 text-red-800': order.status === 'rejected'
                                            }" class="px-2 py-1 rounded text-xs font-semibold">
                                                {{ order.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatNumber(order.filled_qty, 4) }} / {{ formatNumber(order.qty, 4) }}</td>
                                    </tr>
                                    <tr v-if="orders.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No orders</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Fills Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Recent Fills</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Symbol</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Side</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="fill in fills.slice(0, 10)" :key="fill.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">{{ formatDate(fill.fill_at) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ fill.symbol }}</td>
                                        <td :class="fill.side === 'buy' ? 'text-green-600' : 'text-red-600'" class="px-6 py-4 whitespace-nowrap font-semibold uppercase">
                                            {{ fill.side }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatNumber(fill.qty, 4) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatCurrency(fill.price) }}</td>
                                    </tr>
                                    <tr v-if="fills.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No fills</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
