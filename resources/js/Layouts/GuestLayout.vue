<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Footer from '@/Components/Footer.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col bg-slate-950">
        <div class="flex flex-1 flex-col items-center justify-center pt-6 sm:pt-0">
            <div>
                <Link href="/">
                    <div class="flex items-center space-x-2">
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-white tracking-tight">Apex</span>
                    </div>
                </Link>
            </div>

            <div class="mt-6 w-full overflow-hidden bg-slate-900/50 border border-slate-800 px-6 py-8 shadow-xl backdrop-blur-sm sm:max-w-md sm:rounded-xl">
                <slot />
            </div>
        </div>

        <!-- Footer -->
        <Footer />
    </div>
</template>
