<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const user = usePage().props.auth.user;
const isConnected = ref(false);
const isChecking = ref(false);
const connectionError = ref(null);
const connectionSuccess = ref(false);
const hasExistingCredentials = ref(false);

const form = useForm({
    alpaca_key_id: '',
    alpaca_secret: '',
    alpaca_is_paper: true, // Default to paper trading
});

const checkConnection = async () => {
    if (!form.alpaca_key_id || !form.alpaca_secret) {
        connectionError.value = 'Please enter both API Key ID and Secret';
        return;
    }

    isChecking.value = true;
    connectionError.value = null;
    connectionSuccess.value = false;

    try {
        const response = await fetch('/api/alpaca/test-connection', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                key_id: form.alpaca_key_id,
                secret: form.alpaca_secret,
                is_paper: form.alpaca_is_paper,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            isConnected.value = true;
            connectionError.value = null;
            connectionSuccess.value = true;
        } else {
            connectionError.value = data.message || `Connection failed (${response.status})`;
            isConnected.value = false;
            connectionSuccess.value = false;
        }
    } catch (error) {
        console.error('Connection test error:', error);
        connectionError.value = `Failed to test connection: ${error.message}`;
        isConnected.value = false;
        connectionSuccess.value = false;
    } finally {
        isChecking.value = false;
    }
};

const updateCredentials = () => {
    // Only send credentials if they've been entered
    const data = {};
    if (form.alpaca_key_id) data.alpaca_key_id = form.alpaca_key_id;
    if (form.alpaca_secret) data.alpaca_secret = form.alpaca_secret;
    data.alpaca_is_paper = form.alpaca_is_paper;

    form.transform(() => data).put(route('profile.alpaca.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Clear the input fields after successful save
            form.alpaca_key_id = '';
            form.alpaca_secret = '';
            connectionSuccess.value = false;
            // Re-check status
            checkExistingCredentials();
        },
    });
};

// Check if user already has credentials on mount
const checkExistingCredentials = async () => {
    try {
        const response = await fetch('/api/alpaca/status', { credentials: 'same-origin' });
        const data = await response.json();

        if (data.has_credentials) {
            hasExistingCredentials.value = true;
            isConnected.value = data.is_connected;
        } else {
            hasExistingCredentials.value = false;
        }
    } catch (error) {
        console.error('Failed to check Alpaca status:', error);
    }
};

onMounted(() => {
    checkExistingCredentials();
});
</script>

<template>
    <section>
        <header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">
                        Alpaca Trading Credentials
                    </h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Connect your Alpaca account to enable automated trading.
                    </p>
                </div>

                <div v-if="hasExistingCredentials && isConnected" class="flex items-center gap-2 px-3 py-1.5 bg-emerald-500/20 border border-emerald-500/30 rounded-lg">
                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-sm font-medium text-emerald-400">Connected</span>
                </div>
                <div v-else-if="hasExistingCredentials" class="flex items-center gap-2 px-3 py-1.5 bg-slate-700/50 border border-slate-600 rounded-lg">
                    <span class="w-2 h-2 bg-slate-500 rounded-full"></span>
                    <span class="text-sm font-medium text-slate-400">Credentials Saved</span>
                </div>
            </div>
        </header>

        <!-- Credentials Form -->
        <div class="mt-6 bg-slate-800/30 border border-slate-700 rounded-xl p-6">
            <h3 class="text-md font-semibold text-white mb-4">
                {{ hasExistingCredentials ? 'Update API Credentials' : 'Add API Credentials' }}
            </h3>

            <div v-if="hasExistingCredentials" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg">
                <p class="text-sm text-emerald-300">
                    ✓ Credentials are currently saved and encrypted. Leave fields blank to keep existing credentials.
                </p>
            </div>

            <form @submit.prevent="updateCredentials" class="space-y-6">
                <div>
                    <InputLabel for="alpaca_key_id" value="API Key ID" />

                    <TextInput
                        id="alpaca_key_id"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.alpaca_key_id"
                        autocomplete="off"
                        :placeholder="hasExistingCredentials ? 'Leave blank to keep existing' : 'Enter your Alpaca API Key ID'"
                    />

                    <InputError class="mt-2" :message="form.errors.alpaca_key_id" />
                </div>

                <div>
                    <InputLabel for="alpaca_secret" value="API Secret Key" />

                    <TextInput
                        id="alpaca_secret"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="form.alpaca_secret"
                        autocomplete="off"
                        :placeholder="hasExistingCredentials ? 'Leave blank to keep existing' : 'Enter your Alpaca API Secret'"
                    />

                    <InputError class="mt-2" :message="form.errors.alpaca_secret" />
                </div>

                <div class="bg-slate-800/50 border border-slate-700 rounded-lg p-4">
                    <label class="flex items-start cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="form.alpaca_is_paper"
                            class="mt-1 rounded border-slate-600 bg-slate-900 text-emerald-500 focus:ring-emerald-500 focus:ring-offset-slate-900"
                        />
                        <div class="ml-3">
                            <span class="text-base font-medium text-slate-200">Use Paper Trading</span>
                            <p class="text-sm text-slate-400 mt-1">
                                Paper trading uses simulated money for testing. Most users should keep this checked unless you have live trading API keys.
                            </p>
                            <p class="text-xs text-amber-400 mt-2">
                                ⚠️ Note: Paper and Live trading use different API keys from Alpaca.
                            </p>
                        </div>
                    </label>
                </div>

                <div v-if="connectionSuccess" class="bg-emerald-500/10 border border-emerald-500/30 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-emerald-300 font-medium">Successfully connected to Alpaca!</p>
                    </div>
                </div>

                <div v-if="connectionError" class="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-sm text-red-300">{{ connectionError }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">
                        {{ hasExistingCredentials ? 'Update Credentials' : 'Save Credentials' }}
                    </PrimaryButton>

                    <button
                        type="button"
                        @click="checkConnection"
                        :disabled="isChecking || !form.alpaca_key_id || !form.alpaca_secret"
                        class="px-4 py-2 bg-slate-700 hover:bg-slate-600 disabled:bg-slate-800 disabled:text-slate-600 text-white rounded-lg transition-colors font-medium border border-slate-600 disabled:cursor-not-allowed disabled:border-slate-700"
                    >
                        {{ isChecking ? 'Testing...' : 'Test Connection' }}
                    </button>

                    <Transition
                        enter-active-class="transition ease-in-out"
                        enter-from-class="opacity-0"
                        leave-active-class="transition ease-in-out"
                        leave-to-class="opacity-0"
                    >
                        <p
                            v-if="form.recentlySuccessful"
                            class="text-sm text-emerald-400"
                        >
                            Saved.
                        </p>
                    </Transition>
                </div>

                <div class="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="text-sm text-blue-300">
                            <p class="font-medium mb-1">How to get your API keys:</p>
                            <ol class="list-decimal list-inside space-y-1 text-blue-400/80">
                                <li>Go to <a href="https://alpaca.markets" target="_blank" class="underline hover:text-blue-300">alpaca.markets</a> and create an account</li>
                                <li>Navigate to "Paper Trading" in your dashboard</li>
                                <li>Generate a new API key pair</li>
                                <li>Copy and paste both the Key ID and Secret Key here</li>
                            </ol>
                            <p class="mt-2 text-xs text-blue-400/60">Your credentials are encrypted and stored securely.</p>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</template>
