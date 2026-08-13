<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { route } from '../../../vendor/tightenco/ziggy';
import FlashToast from '../Components/FlashToast.vue';
import LocaleSwitcher from '../Components/LocaleSwitcher.vue';
import Sidebar from '../Components/Sidebar.vue';
import { useSyncDocumentDirection, useTranslations } from '../i18n';

const { t } = useTranslations();
const page = usePage();
const user = page.props.auth.user;

useSyncDocumentDirection();

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <FlashToast />

        <Sidebar />

        <header class="ps-64 border-b border-brand-gray/20 bg-white">
            <div class="flex items-center justify-end gap-4 px-6 py-3">
                <LocaleSwitcher />
                <span class="text-sm text-brand-muted">{{ user?.name }}</span>
                <button
                    type="button"
                    class="rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy transition-colors duration-150 hover:bg-slate-100 active:scale-[0.98]"
                    @click="logout"
                >
                    {{ t('Log out') }}
                </button>
            </div>
        </header>

        <main class="ps-64">
            <div class="mx-auto max-w-7xl px-6 py-8">
                <slot />
            </div>
        </main>
    </div>
</template>
