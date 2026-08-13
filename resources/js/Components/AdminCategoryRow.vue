<script setup>
import { useForm } from '@inertiajs/vue3';
import { TrashIcon } from '@heroicons/vue/24/outline';
import { route } from '../../../vendor/tightenco/ziggy';
import { useTranslations } from '../i18n';

const props = defineProps({
    category: { type: Object, required: true },
    depth: { type: Number, required: true },
    parentOptions: { type: Array, required: true },
});

const { t } = useTranslations();

const form = useForm({
    name: props.category.name,
    parent_id: props.category.parentId,
    order: props.category.order,
});

const deleteForm = useForm({});

function save() {
    form.patch(route('admin.categories.update', props.category.id), { preserveScroll: true });
}

function destroy() {
    if (!confirm(t('Are you sure you want to delete this category?'))) {
        return;
    }

    deleteForm.delete(route('admin.categories.destroy', props.category.id), { preserveScroll: true });
}
</script>

<template>
    <tr class="border-b border-brand-gray/10 even:bg-slate-50">
        <td class="py-2 pe-3">
            <div class="flex items-center gap-1">
                <span
                    v-if="depth > 0"
                    class="inline-block h-4 w-4 shrink-0 border-b border-s border-brand-gray/40"
                    :style="{ marginInlineStart: (depth - 1) * 1.25 + 'rem' }"
                />
                <input
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-md border border-brand-gray/40 px-2 py-1 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
            </div>
            <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
        </td>
        <td class="py-2 pe-3">
            <select
                v-model="form.parent_id"
                class="w-full rounded-md border border-brand-gray/40 px-2 py-1 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
            >
                <option :value="null">{{ t('None (top level)') }}</option>
                <option v-for="option in parentOptions" :key="option.id" :value="option.id">
                    {{ option.name }}
                </option>
            </select>
            <p v-if="form.errors.parent_id" class="mt-1 text-xs text-red-600">{{ form.errors.parent_id }}</p>
        </td>
        <td class="w-20 py-2 pe-3">
            <input
                v-model.number="form.order"
                type="number"
                min="0"
                class="w-full rounded-md border border-brand-gray/40 px-2 py-1 text-sm text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
            />
        </td>
        <td class="w-24 py-2 pe-3 text-xs text-brand-muted">{{ category.guidesCount }}</td>
        <td class="w-28 py-2 pe-3 text-xs text-brand-muted">{{ category.childrenCount }}</td>
        <td class="w-40 py-2">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-md bg-brand-blue px-2 py-1 text-xs font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                    :disabled="form.processing"
                    @click="save"
                >
                    {{ t('Save') }}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md border border-red-200 px-2 py-1 text-xs text-red-600 transition-colors duration-150 hover:bg-red-50 disabled:opacity-60"
                    :disabled="deleteForm.processing"
                    @click="destroy"
                >
                    <TrashIcon class="h-3.5 w-3.5" />
                    {{ t('Delete') }}
                </button>
            </div>
            <p v-if="deleteForm.errors.category" class="mt-1 text-xs text-red-600">{{ deleteForm.errors.category }}</p>
        </td>
    </tr>
</template>
