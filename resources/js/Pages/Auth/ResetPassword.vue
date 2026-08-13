<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from '../../../../vendor/tightenco/ziggy';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import { useTranslations } from '../../i18n';

const { t } = useTranslations();

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <GuestLayout>
        <h1 class="mb-6 text-lg font-semibold text-brand-navy">{{ t('Reset password') }}</h1>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-brand-muted">
                    {{ t('Email') }}
                </label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-2 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-brand-muted">
                    {{ t('Password') }}
                </label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    autofocus
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-2 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium text-brand-muted">
                    {{ t('Confirm password') }}
                </label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-2 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-brand-blue px-4 py-2 text-sm font-medium text-brand-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ t('Save new password') }}
            </button>
        </form>
    </GuestLayout>
</template>
