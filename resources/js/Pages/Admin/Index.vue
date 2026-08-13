<script setup>
import { Link } from '@inertiajs/vue3';
import { FolderIcon, UsersIcon } from '@heroicons/vue/24/outline';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTranslations } from '../../i18n';

const { t } = useTranslations();
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-lg font-semibold text-brand-navy">{{ t('Admin') }}</h1>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Link
                href="/admin/categories"
                class="rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm transition-colors duration-150 hover:border-brand-blue hover:shadow-md"
            >
                <FolderIcon class="mb-3 h-6 w-6 text-brand-blue" />
                <h2 class="mb-1 text-sm font-semibold text-brand-navy">{{ t('Categories') }}</h2>
                <p class="text-xs text-brand-muted">{{ t('Manage categories') }}</p>
            </Link>

            <Link
                href="/admin/users"
                class="rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm transition-colors duration-150 hover:border-brand-blue hover:shadow-md"
            >
                <UsersIcon class="mb-3 h-6 w-6 text-brand-blue" />
                <h2 class="mb-1 text-sm font-semibold text-brand-navy">{{ t('Users') }}</h2>
                <p class="text-xs text-brand-muted">{{ t('Manage user roles') }}</p>
            </Link>
        </div>
    </AppLayout>
</template>
