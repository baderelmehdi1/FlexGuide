<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, CheckIcon } from '@heroicons/vue/24/outline';
import { route } from '../../../../vendor/tightenco/ziggy';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTranslations } from '../../i18n';

defineProps({
    guides: { type: Array, required: true },
});

const { t } = useTranslations();

function publish(guide) {
    router.post(route('review.publish', guide.slug));
}

function sendBack(guide) {
    router.post(route('review.sendBack', guide.slug));
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-lg font-semibold text-brand-navy">{{ t('Review queue') }}</h1>

        <div v-if="!guides.length" class="animate-fade-in-up rounded-lg border border-dashed border-brand-gray/30 p-8 text-center text-sm text-brand-muted">
            {{ t('No guides pending review.') }}
        </div>

        <ul v-else class="space-y-3">
            <li
                v-for="(guide, index) in guides"
                :key="guide.slug"
                class="animate-fade-in-up flex items-center justify-between rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm transition-shadow duration-150 hover:shadow-md"
                :style="{ animationDelay: Math.min(index * 40, 400) + 'ms' }"
            >
                <div>
                    <Link :href="`/guides/${guide.slug}`" class="text-sm font-semibold text-brand-navy transition-colors hover:text-brand-blue">
                        {{ guide.title }}
                    </Link>
                    <p class="mt-1 text-xs text-brand-muted">
                        {{ guide.category }} · {{ t('Author') }}: {{ guide.author }} · {{ t('Steps') }}: {{ guide.stepsCount }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy transition-colors duration-150 hover:bg-slate-100 active:scale-[0.98]"
                        @click="sendBack(guide)"
                    >
                        <ArrowUturnLeftIcon class="h-4 w-4 rtl:scale-x-[-1]" />
                        {{ t('Send back') }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-md bg-brand-blue px-3 py-1.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98]"
                        @click="publish(guide)"
                    >
                        <CheckIcon class="h-4 w-4" />
                        {{ t('Publish') }}
                    </button>
                </div>
            </li>
        </ul>
    </AppLayout>
</template>
