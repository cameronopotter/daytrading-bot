<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign In" />

    <div class="min-h-screen flex bg-slate-950">
        <!-- Left Side - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-12 flex-col justify-between relative overflow-hidden border-r border-slate-800">
            <!-- Background Pattern -->
            <div class="absolute inset-0 opacity-5">
                <div class="absolute inset-0" style="background-image: repeating-linear-gradient(0deg, transparent, transparent 2px, rgba(255,255,255,0.03) 2px, rgba(255,255,255,0.03) 4px), repeating-linear-gradient(90deg, transparent, transparent 2px, rgba(255,255,255,0.03) 2px, rgba(255,255,255,0.03) 4px);"></div>
            </div>

            <!-- Gradient Orbs -->
            <div class="absolute top-1/4 -left-20 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>

            <div class="relative z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                        </svg>
                    </div>
                    <span class="text-2xl font-bold text-white tracking-tight">Apex</span>
                </div>

                <div class="mt-20">
                    <h1 class="text-5xl font-bold text-white leading-tight">
                        Intelligent Trading<br/>
                        <span class="bg-gradient-to-r from-emerald-400 to-blue-500 bg-clip-text text-transparent">Automated</span>
                    </h1>
                    <p class="mt-6 text-lg text-slate-400 max-w-md leading-relaxed">
                        Enterprise-grade algorithmic trading platform with real-time execution,
                        advanced risk controls, and institutional-level performance.
                    </p>
                </div>
            </div>

            <div class="relative z-10">
                <div class="grid grid-cols-3 gap-8 bg-slate-900/50 backdrop-blur-sm rounded-2xl p-6 border border-slate-800">
                    <div>
                        <div class="text-3xl font-bold bg-gradient-to-r from-emerald-400 to-emerald-300 bg-clip-text text-transparent">99.9%</div>
                        <div class="text-xs text-slate-500 mt-1 uppercase tracking-wider">Uptime</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold bg-gradient-to-r from-blue-400 to-blue-300 bg-clip-text text-transparent">&lt;10ms</div>
                        <div class="text-xs text-slate-500 mt-1 uppercase tracking-wider">Latency</div>
                    </div>
                    <div>
                        <div class="text-3xl font-bold bg-gradient-to-r from-purple-400 to-purple-300 bg-clip-text text-transparent">24/7</div>
                        <div class="text-xs text-slate-500 mt-1 uppercase tracking-wider">Active</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 bg-slate-950">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden mb-10">
                    <div class="flex items-center space-x-3 justify-center">
                        <div class="w-11 h-11 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold text-white tracking-tight">Apex</span>
                    </div>
                </div>

                <div>
                    <h2 class="text-3xl font-bold text-white">Welcome back</h2>
                    <p class="mt-2 text-slate-400">Sign in to access your trading platform</p>
                </div>

                <div v-if="status" class="mt-6 p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl">
                    <p class="text-sm text-emerald-400">{{ status }}</p>
                </div>

                <form @submit.prevent="submit" class="mt-8 space-y-6">
                    <div>
                        <InputLabel for="email" value="Email Address" class="text-slate-300 font-medium" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-2 block w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="you@example.com"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Password" class="text-slate-300 font-medium" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-2 block w-full px-4 py-3 bg-slate-900 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-slate-700 bg-slate-900 text-emerald-500 focus:ring-emerald-500" />
                            <span class="ml-2 text-sm text-slate-400">Remember me</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm font-medium text-emerald-400 hover:text-emerald-300 transition"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <div>
                        <button
                            type="submit"
                            class="w-full bg-gradient-to-r from-emerald-500 to-emerald-600 text-white py-3 px-4 rounded-xl font-medium hover:from-emerald-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950 transition transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none shadow-lg shadow-emerald-500/20"
                            :disabled="form.processing"
                        >
                            <span v-if="!form.processing">Sign In</span>
                            <span v-else class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Signing in...
                            </span>
                        </button>
                    </div>

                    <p class="text-center text-sm text-slate-500 mt-6">
                        Protected by enterprise-grade encryption and security
                    </p>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-800"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-slate-950 text-slate-500">New to Apex?</span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <Link
                            :href="route('register')"
                            class="w-full flex justify-center py-3 px-4 border border-slate-700 rounded-xl text-slate-300 bg-slate-900/50 hover:bg-slate-800 hover:border-slate-600 transition font-medium"
                        >
                            Create an account
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
