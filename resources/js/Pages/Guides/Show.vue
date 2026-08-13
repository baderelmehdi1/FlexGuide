<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentArrowDownIcon, ExclamationTriangleIcon, PencilSquareIcon } from '@heroicons/vue/24/outline';
import AnnotationOverlay from '../../Components/AnnotationOverlay.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTranslations } from '../../i18n';

const props = defineProps({
    guide: { type: Object, required: true },
});

const { t } = useTranslations();
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-4xl">
            <div class="mb-4 flex items-center justify-between">
                <Link href="/guides" class="inline-flex items-center gap-1 text-sm text-brand-blue hover:underline">
                    <ArrowUturnLeftIcon class="h-3.5 w-3.5 rtl:scale-x-[-1]" />
                    {{ t('Back to guides') }}
                </Link>
                <div class="flex items-center gap-2">
                    <a
                        :href="`/guides/${guide.slug}/pdf`"
                        class="inline-flex items-center gap-1.5 rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy transition-colors duration-150 hover:bg-slate-100 active:scale-[0.98]"
                    >
                        <DocumentArrowDownIcon class="h-4 w-4" />
                        {{ t('Download PDF') }}
                    </a>
                    <Link
                        v-if="guide.canEdit"
                        :href="`/guides/${guide.slug}/edit`"
                        class="inline-flex items-center gap-1.5 rounded-md border border-brand-blue px-3 py-1.5 text-sm text-brand-blue transition-colors duration-150 hover:bg-brand-blue/10 active:scale-[0.98]"
                    >
                        <PencilSquareIcon class="h-4 w-4" />
                        {{ t('Edit') }}
                    </Link>
                </div>
            </div>

            <div class="mb-6 rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm">
                <div class="mb-2 flex items-start justify-between gap-3">
                    <h1 class="text-xl font-semibold text-brand-navy" :dir="guide.language === 'ar' ? 'rtl' : 'ltr'">
                        {{ guide.title }}
                    </h1>
                    <span class="shrink-0 rounded-full border px-2 py-0.5 text-xs font-medium" :class="guide.badgeClasses">
                        {{ t(guide.statusLabel) }}
                    </span>
                </div>

                <p v-if="guide.description" class="mb-3 text-sm text-brand-muted" :dir="guide.language === 'ar' ? 'rtl' : 'ltr'">
                    {{ guide.description }}
                </p>

                <div class="flex flex-wrap items-center gap-2 text-xs text-brand-muted">
                    <span v-if="guide.category">{{ guide.category.name }}</span>
                    <span v-for="tag in guide.tags" :key="tag" class="rounded-full bg-slate-100 px-2 py-0.5">
                        {{ tag }}
                    </span>
                </div>
            </div>

            <div v-if="!guide.steps.length" class="rounded-xl border border-dashed border-brand-gray/30 p-8 text-center text-sm text-brand-muted">
                {{ t('This guide has no steps yet.') }}
            </div>

            <ol v-else class="space-y-6">
                <li
                    v-for="(step, index) in guide.steps"
                    :key="step.id"
                    class="animate-fade-in-up rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm"
                    :style="{ animationDelay: Math.min(index * 60, 480) + 'ms' }"
                    :dir="guide.language === 'ar' ? 'rtl' : 'ltr'"
                >
                    <div class="mb-4 flex items-center gap-3 border-b border-brand-gray/10 pb-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-blue text-xs font-semibold text-white">
                            {{ index + 1 }}
                        </span>
                        <h2 v-if="step.title" class="text-sm font-semibold text-brand-navy">{{ step.title }}</h2>
                    </div>

                    <div v-if="step.warning" class="mb-3 flex items-start gap-2 rounded-md border-s-4 border-brand-gold bg-brand-gold/10 px-3 py-2 text-sm text-brand-navy">
                        <ExclamationTriangleIcon class="mt-0.5 h-4 w-4 shrink-0 text-brand-gold" />
                        <span><strong class="font-medium">{{ t('Warning') }}:</strong> {{ step.warning }}</span>
                    </div>

                    <div v-if="step.body" class="prose prose-sm max-w-none" v-html="step.body" />

                    <div v-if="step.screenshots.length" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <a
                            v-for="shot in step.screenshots"
                            :key="shot.id"
                            :href="shot.url"
                            target="_blank"
                            rel="noopener"
                            class="relative block"
                        >
                            <img :src="shot.thumbUrl" :alt="step.title ?? ''" class="w-full rounded-md border border-brand-gray/20" />
                            <AnnotationOverlay :shapes="shot.annotations" />
                        </a>
                    </div>
                </li>
            </ol>
        </div>
    </AppLayout>
</template>
