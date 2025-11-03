<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const currentYear = computed(() => new Date().getFullYear());
</script>

<template>
    <footer class="bg-slate-900/95 border-t border-slate-800 backdrop-blur supports-[backdrop-filter]:bg-slate-900/60 mt-auto">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand Section -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-white tracking-tight">Apex Trading</span>
                    </div>
                    <p class="text-sm text-slate-400 max-w-md mb-4">
                        Advanced algorithmic trading platform powered by cutting-edge technology. Execute sophisticated trading strategies with confidence and precision.
                    </p>
                    <p class="text-xs text-slate-500">
                        © {{ currentYear }} Apex Trading. All rights reserved.
                    </p>
                </div>

                <!-- Legal Links -->
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Legal</h3>
                    <ul class="space-y-3">
                        <li>
                            <Link :href="route('privacy.policy')" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors">
                                Privacy Policy
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('terms.service')" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors">
                                Terms of Service
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('risk.disclosure')" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors">
                                Risk Disclosure
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Resources -->
                <div>
                    <h3 class="text-sm font-semibold text-white uppercase tracking-wider mb-4">Resources</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="#" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors">
                                Documentation
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors">
                                API Reference
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-sm text-slate-400 hover:text-emerald-400 transition-colors">
                                Support
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Disclaimer -->
            <div class="mt-8 pt-8 border-t border-slate-800">
                <p class="text-xs text-slate-500 text-center max-w-4xl mx-auto">
                    <span class="font-semibold text-slate-400">Risk Warning:</span> Trading in financial instruments carries a high level of risk and may not be suitable for all investors.
                    Past performance is not indicative of future results. You should carefully consider your investment objectives, level of experience, and risk appetite.
                    The possibility exists that you could sustain a loss of some or all of your initial investment and therefore you should not invest money that you cannot afford to lose.
                </p>
            </div>
        </div>
    </footer>
</template>
