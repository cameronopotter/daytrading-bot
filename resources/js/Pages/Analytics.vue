<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const dailyPnL = ref([]);
const loading = ref(true);
const totalPnL = ref(0);
const winDays = ref(0);
const lossDays = ref(0);
const bestDay = ref(0);
const worstDay = ref(0);

const fetchAnalytics = async () => {
    try {
        const response = await fetch('/api/analytics/daily-pnl');
        if (response.ok) {
            const data = await response.json();
            dailyPnL.value = data.daily_pnl || [];

            // Calculate stats
            totalPnL.value = dailyPnL.value.reduce((sum, day) => sum + day.pnl, 0);
            winDays.value = dailyPnL.value.filter(day => day.pnl > 0).length;
            lossDays.value = dailyPnL.value.filter(day => day.pnl < 0).length;
            bestDay.value = Math.max(...dailyPnL.value.map(day => day.pnl), 0);
            worstDay.value = Math.min(...dailyPnL.value.map(day => day.pnl), 0);
        }
    } catch (error) {
        console.error('Failed to fetch analytics:', error);
    } finally {
        loading.value = false;
    }
};

const getColorClass = (pnl) => {
    if (pnl === 0) return 'bg-slate-800 border-slate-700';

    const absValue = Math.abs(pnl);

    if (pnl > 0) {
        // Green for profits
        if (absValue > 1000) return 'bg-emerald-500 border-emerald-400';
        if (absValue > 500) return 'bg-emerald-600 border-emerald-500';
        if (absValue > 100) return 'bg-emerald-700 border-emerald-600';
        return 'bg-emerald-800 border-emerald-700';
    } else {
        // Red for losses
        if (absValue > 1000) return 'bg-red-500 border-red-400';
        if (absValue > 500) return 'bg-red-600 border-red-500';
        if (absValue > 100) return 'bg-red-700 border-red-600';
        return 'bg-red-800 border-red-700';
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(value);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const getDayOfWeek = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'short' });
};

// Group by weeks for calendar layout
const weeklyData = computed(() => {
    const weeks = [];
    let currentWeek = [];

    dailyPnL.value.forEach((day, index) => {
        currentWeek.push(day);

        // If it's Sunday or the last day, start a new week
        const dayOfWeek = new Date(day.date).getDay();
        if (dayOfWeek === 0 || index === dailyPnL.value.length - 1) {
            weeks.push([...currentWeek]);
            currentWeek = [];
        }
    });

    return weeks;
});

onMounted(() => {
    fetchData();
    // Refresh every 30 seconds
    setInterval(fetchAnalytics, 30000);
});

const fetchData = () => {
    fetchAnalytics();
};
</script>

<template>
    <Head title="Analytics" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-white leading-tight">P/L Analytics</h2>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Stats -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Total P/L</div>
                        <div :class="totalPnL >= 0 ? 'text-emerald-400' : 'text-red-400'" class="text-2xl font-bold">
                            {{ formatCurrency(totalPnL) }}
                        </div>
                    </div>

                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Win Days</div>
                        <div class="text-2xl font-bold text-emerald-400">{{ winDays }}</div>
                    </div>

                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Loss Days</div>
                        <div class="text-2xl font-bold text-red-400">{{ lossDays }}</div>
                    </div>

                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Best Day</div>
                        <div class="text-2xl font-bold text-emerald-400">{{ formatCurrency(bestDay) }}</div>
                    </div>

                    <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                        <div class="text-xs text-slate-400 uppercase tracking-wider mb-2">Worst Day</div>
                        <div class="text-2xl font-bold text-red-400">{{ formatCurrency(worstDay) }}</div>
                    </div>
                </div>

                <!-- Calendar Heatmap -->
                <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl p-6 backdrop-blur-sm">
                    <h3 class="text-lg font-semibold text-white mb-6">Daily P/L Calendar (Last 60 Days)</h3>

                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <div class="text-slate-400">Loading...</div>
                    </div>

                    <div v-else-if="dailyPnL.length === 0" class="text-center py-12 text-slate-500">
                        No trading data available yet
                    </div>

                    <div v-else class="space-y-3">
                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-7 gap-2">
                            <!-- Day labels -->
                            <div class="text-xs text-slate-500 text-center font-medium">Mon</div>
                            <div class="text-xs text-slate-500 text-center font-medium">Tue</div>
                            <div class="text-xs text-slate-500 text-center font-medium">Wed</div>
                            <div class="text-xs text-slate-500 text-center font-medium">Thu</div>
                            <div class="text-xs text-slate-500 text-center font-medium">Fri</div>
                            <div class="text-xs text-slate-500 text-center font-medium">Sat</div>
                            <div class="text-xs text-slate-500 text-center font-medium">Sun</div>

                            <!-- Calendar squares -->
                            <div
                                v-for="day in dailyPnL.slice(-60)"
                                :key="day.date"
                                class="aspect-square rounded-lg border-2 transition-all duration-200 hover:scale-110 hover:z-10 relative group cursor-pointer"
                                :class="getColorClass(day.pnl)"
                            >
                                <!-- Tooltip on hover -->
                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block z-20">
                                    <div class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 shadow-xl whitespace-nowrap">
                                        <div class="text-xs text-slate-400">{{ formatDate(day.date) }}</div>
                                        <div :class="day.pnl >= 0 ? 'text-emerald-400' : 'text-red-400'" class="text-sm font-bold">
                                            {{ formatCurrency(day.pnl) }}
                                        </div>
                                        <div class="text-xs text-slate-500">{{ day.trades }} trades</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="flex items-center justify-center gap-6 pt-6 border-t border-slate-800">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400">Less</span>
                                <div class="flex gap-1">
                                    <div class="w-4 h-4 rounded bg-red-800 border border-red-700"></div>
                                    <div class="w-4 h-4 rounded bg-red-700 border border-red-600"></div>
                                    <div class="w-4 h-4 rounded bg-red-600 border border-red-500"></div>
                                    <div class="w-4 h-4 rounded bg-red-500 border border-red-400"></div>
                                </div>
                                <span class="text-xs text-slate-500 mx-2">Loss</span>
                            </div>

                            <div class="w-4 h-4 rounded bg-slate-800 border border-slate-700"></div>
                            <span class="text-xs text-slate-500">Break Even</span>

                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500 mr-2">Profit</span>
                                <div class="flex gap-1">
                                    <div class="w-4 h-4 rounded bg-emerald-800 border border-emerald-700"></div>
                                    <div class="w-4 h-4 rounded bg-emerald-700 border border-emerald-600"></div>
                                    <div class="w-4 h-4 rounded bg-emerald-600 border border-emerald-500"></div>
                                    <div class="w-4 h-4 rounded bg-emerald-500 border border-emerald-400"></div>
                                </div>
                                <span class="text-xs text-slate-400">More</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Days Detail -->
                <div class="bg-slate-900/50 border border-slate-800 overflow-hidden shadow-xl rounded-xl backdrop-blur-sm">
                    <div class="px-6 py-4 border-b border-slate-800">
                        <h3 class="text-lg font-semibold text-white">Recent Trading Days</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-800">
                            <thead class="bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Day</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Trades</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">P/L</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">Win Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800">
                                <tr v-for="day in dailyPnL.slice(-14).reverse()" :key="day.date" class="hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">{{ formatDate(day.date) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">{{ getDayOfWeek(day.date) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">{{ day.trades }}</td>
                                    <td :class="day.pnl >= 0 ? 'text-emerald-400' : 'text-red-400'" class="px-6 py-4 whitespace-nowrap font-bold">
                                        {{ formatCurrency(day.pnl) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-300">
                                        {{ day.trades > 0 ? Math.round((day.wins / day.trades) * 100) : 0 }}%
                                    </td>
                                </tr>
                                <tr v-if="dailyPnL.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-500">No trading history</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
