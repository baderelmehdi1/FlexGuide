<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpenIcon, CheckBadgeIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline';
import CategoryTree from './CategoryTree.vue';
import { useTranslations } from '../i18n';

const { t } = useTranslations();
const page = usePage();
const user = page.props.auth.user;

const navLinkClass = 'flex items-center gap-2.5 rounded-md px-2.5 py-1.5 text-sm transition-colors duration-150';
const navLinkInactive = 'text-white/75 hover:bg-white/10 hover:text-white';
const navLinkActive = 'bg-brand-blue/20 font-medium text-white';
</script>

<template>
    <aside class="fixed inset-y-0 start-0 z-40 flex w-64 flex-col bg-brand-navy">
        <div class="px-4 py-5">
            <Link href="/guides" class="text-sm font-semibold text-white">
                {{ t('FlexCube Guide Platform') }}
            </Link>
        </div>

        <nav class="space-y-0.5 px-2">
            <Link
                href="/guides"
                :class="[navLinkClass, page.component === 'Guides/Index' && !page.props.activeCategory ? navLinkActive : navLinkInactive]"
            >
                <BookOpenIcon class="h-4 w-4 shrink-0" />
                {{ t('Guides') }}
            </Link>
            <Link
                v-if="user?.isApprover"
                href="/review"
                :class="[navLinkClass, page.component === 'Review/Index' ? navLinkActive : navLinkInactive]"
            >
                <CheckBadgeIcon class="h-4 w-4 shrink-0" />
                {{ t('Review queue') }}
            </Link>
            <Link
                v-if="user?.isAdmin"
                href="/admin"
                :class="[navLinkClass, page.component?.startsWith('Admin/') ? navLinkActive : navLinkInactive]"
            >
                <Cog6ToothIcon class="h-4 w-4 shrink-0" />
                {{ t('Admin') }}
            </Link>
        </nav>

        <div class="mt-4 flex-1 overflow-y-auto border-t border-white/10 px-2 py-4">
            <CategoryTree :categories="page.props.sidebarCategories ?? []" :active-category="page.props.activeCategory ?? null" />
        </div>
    </aside>
</template>
