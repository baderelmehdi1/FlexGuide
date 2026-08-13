<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { CheckCircleIcon, ClockIcon, FolderIcon, PencilIcon } from '@heroicons/vue/24/outline';
import { useTranslations } from '../../i18n';
import AppLayout from '../../Layouts/AppLayout.vue';

defineProps({
    guides: { type: Array, required: true },
    activeCategory: { type: String, default: null },
});

const { t } = useTranslations();
const page = usePage();

const statusIcons = {
    draft: PencilIcon,
    pending: ClockIcon,
    published: CheckCircleIcon,
};
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-lg font-semibold text-brand-navy">{{ t('Guides') }}</h1>
            <Link
                v-if="page.props.auth.user?.isContributor"
                href="/guides/create"
                class="inline-block rounded-md bg-brand-blue px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98]"
            >
                {{ t('New guide') }}
            </Link>
        </div>

        <div v-if="!guides.length" class="animate-fade-in-up rounded-lg border border-dashed border-brand-gray/30 p-8 text-center text-sm text-brand-muted">
            {{ t('No guides yet.') }}
        </div>

        <ul v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <li
                v-for="(guide, index) in guides"
                :key="guide.id"
                class="animate-fade-in-up relative rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:border-brand-blue/40 hover:shadow-md"
                :style="{ animationDelay: Math.min(index * 40, 400) + 'ms' }"
            >
                <span
                    class="absolute end-4 top-4 inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-medium"
                    :class="guide.badgeClasses"
                >
                    <component :is="statusIcons[guide.status]" class="h-3.5 w-3.5" />
                    {{ t(guide.statusLabel) }}
                </span>

                <div class="mb-3 flex items-center gap-2 text-brand-blue">
                    <FolderIcon class="h-5 w-5" />
                </div>

                <Link :href="`/guides/${guide.slug}`" class="block pe-16 text-sm font-semibold text-brand-navy transition-colors duration-150 hover:text-brand-blue">
                    {{ guide.title }}
                </Link>
                <p v-if="guide.description" class="mt-1.5 line-clamp-2 text-xs text-brand-muted">{{ guide.description }}</p>

                <div class="mt-4 flex items-center justify-between border-t border-brand-gray/10 pt-3 text-xs text-brand-muted">
                    <span>{{ guide.category?.name }}</span>
                    <span :dir="guide.language === 'ar' ? 'rtl' : 'ltr'">{{ guide.language.toUpperCase() }}</span>
                </div>
            </li>
        </ul>
    </AppLayout>
</template>
