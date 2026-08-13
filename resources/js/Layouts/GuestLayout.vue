<script setup>
import { Link } from '@inertiajs/vue3';
import FlashToast from '../Components/FlashToast.vue';
import LocaleSwitcher from '../Components/LocaleSwitcher.vue';
import { useSyncDocumentDirection, useTranslations } from '../i18n';

const { t } = useTranslations();

useSyncDocumentDirection();
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-brand-navy px-4 py-12">
        <FlashToast />

        <div class="w-full max-w-sm animate-fade-in-up">
            <div class="mb-8 text-center">
                <Link href="/" class="text-lg font-semibold text-brand-white">
                    {{ t('FlexCube Guide Platform') }}
                </Link>
            </div>

            <div class="rounded-lg border border-white/10 bg-brand-white p-6 shadow-xl">
                <slot />
            </div>

            <div class="mt-6 flex justify-center">
                <LocaleSwitcher dark />
            </div>
        </div>
    </div>
</template>
