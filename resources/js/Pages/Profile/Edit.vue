<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateAlpacaCredentials from './Partials/UpdateAlpacaCredentials.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-white"
            >
                Profile Settings
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <div
                    class="bg-slate-900/50 border border-slate-800 p-6 shadow-xl backdrop-blur-sm sm:rounded-xl sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-slate-900/50 border border-slate-800 p-6 shadow-xl backdrop-blur-sm sm:rounded-xl sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="bg-slate-900/50 border border-slate-800 p-6 shadow-xl backdrop-blur-sm sm:rounded-xl sm:p-8"
                >
                    <UpdateAlpacaCredentials class="max-w-xl" />
                </div>

                <div
                    class="bg-slate-900/50 border border-slate-800 p-6 shadow-xl backdrop-blur-sm sm:rounded-xl sm:p-8"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
