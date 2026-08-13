<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { route } from '../../../../vendor/tightenco/ziggy';
import GuestLayout from '../../Layouts/GuestLayout.vue';
import { useTranslations } from '../../i18n';

const { t } = useTranslations();
const page = usePage();

const form = useForm({
    email: '',
});

function submit() {
    form.post(route('password.email'));
}
</script>

<template>
    <GuestLayout>
        <h1 class="mb-1 text-lg font-semibold text-brand-navy">{{ t('Forgot password') }}</h1>
        <p class="mb-6 text-sm text-brand-muted">
            {{ t('Enter your email and we will send you a password reset link.') }}
        </p>

        <p v-if="page.props.flash.status" class="mb-4 rounded-md bg-brand-blue/10 px-3 py-2 text-sm text-brand-blue">
            {{ page.props.flash.status }}
        </p>

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

            <button
                type="submit"
                class="w-full rounded-md bg-brand-blue px-4 py-2 text-sm font-medium text-brand-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ t('Send reset link') }}
            </button>

            <a :href="route('login')" class="block text-center text-sm text-brand-blue hover:underline">
                {{ t('Back to sign in') }}
            </a>
        </form>
    </GuestLayout>
</template>
