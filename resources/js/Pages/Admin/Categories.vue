<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from '../../../../vendor/tightenco/ziggy';
import AdminCategoryRow from '../../Components/AdminCategoryRow.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTranslations } from '../../i18n';

const props = defineProps({
    categories: { type: Array, required: true },
});

const { t } = useTranslations();

/**
 * The backend orders by parent_id then order, which groups siblings but
 * doesn't interleave them into a real depth-first tree order. Rebuilt here
 * so the table reads top-to-bottom the way the tree actually nests.
 */
const rows = computed(() => {
    const byParent = new Map();

    props.categories.forEach((category) => {
        const key = category.parentId ?? 'root';
        if (!byParent.has(key)) {
            byParent.set(key, []);
        }
        byParent.get(key).push(category);
    });

    byParent.forEach((list) => list.sort((a, b) => a.order - b.order));

    const result = [];

    function walk(parentKey, depth) {
        (byParent.get(parentKey) ?? []).forEach((category) => {
            result.push({ ...category, depth });
            walk(category.id, depth + 1);
        });
    }

    walk('root', 0);

    return result;
});

const createForm = useForm({
    name: '',
    parent_id: null,
});

function createCategory() {
    createForm.post(route('admin.categories.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
}

function parentOptionsFor(categoryId) {
    return props.categories.filter((category) => category.id !== categoryId);
}
</script>

<template>
    <AppLayout>
        <h1 class="mb-6 text-lg font-semibold text-brand-navy">{{ t('Categories') }}</h1>

        <form class="mb-6 flex items-end gap-3 rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm" @submit.prevent="createCategory">
            <div class="flex-1">
                <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Name') }}</label>
                <input
                    v-model="createForm.name"
                    type="text"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
                <p v-if="createForm.errors.name" class="mt-1 text-xs text-red-600">{{ createForm.errors.name }}</p>
            </div>
            <div class="w-56">
                <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Parent category') }}</label>
                <select
                    v-model="createForm.parent_id"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                >
                    <option :value="null">{{ t('None (top level)') }}</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>
            <button
                type="submit"
                class="rounded-md bg-brand-blue px-4 py-1.5 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                :disabled="createForm.processing"
            >
                {{ t('Add category') }}
            </button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-brand-gray/20 bg-white shadow-sm">
            <table class="w-full text-start">
                <thead>
                    <tr class="border-b border-brand-gray/20 text-start text-xs font-medium text-brand-muted">
                        <th class="px-3 py-2 text-start">{{ t('Name') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('Parent category') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('Order') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('Guides count') }}</th>
                        <th class="px-3 py-2 text-start">{{ t('Subcategories') }}</th>
                        <th class="px-3 py-2 text-start"></th>
                    </tr>
                </thead>
                <tbody>
                    <AdminCategoryRow
                        v-for="category in rows"
                        :key="category.id"
                        :category="category"
                        :depth="category.depth"
                        :parent-options="parentOptionsFor(category.id)"
                    />
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
