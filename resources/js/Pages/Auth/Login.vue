<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from '../../../../vendor/tightenco/ziggy';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import { useTranslations } from '../../i18n';

const { t } = useTranslations();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout>
        <h1 class="mb-1 text-lg font-semibold text-brand-navy">{{ t('Sign in') }}</h1>
        <p class="mb-6 text-sm text-brand-muted">{{ t('Sign in to your account to continue.') }}</p>

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
                    autofocus
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
                    autocomplete="current-password"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-2 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
                <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-brand-muted">
                    <input v-model="form.remember" type="checkbox" class="rounded border-brand-gray/40" />
                    {{ t('Remember me') }}
                </label>
                <a :href="route('password.request')" class="text-sm text-brand-blue hover:underline">
                    {{ t('Forgot your password?') }}
                </a>
            </div>

            <button
                type="submit"
                class="w-full rounded-md bg-brand-blue px-4 py-2 text-sm font-medium text-brand-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ t('Log in') }}
            </button>
        </form>
    </GuestLayout>
</template>
