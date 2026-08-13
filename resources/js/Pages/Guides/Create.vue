<script setup>
import { useForm } from '@inertiajs/vue3';
import { route } from '../../../../vendor/tightenco/ziggy';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTranslations } from '../../i18n';

const props = defineProps({
    categories: { type: Array, required: true },
    languages: { type: Array, required: true },
});

const { t } = useTranslations();

const form = useForm({
    title: '',
    description: '',
    category_id: props.categories[0]?.id ?? null,
    language: 'ar',
});

function submit() {
    form.post(route('guides.store'));
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto max-w-xl rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm">
            <h1 class="mb-6 text-lg font-semibold text-brand-navy">{{ t('New guide') }}</h1>

            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label for="title" class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Title') }}</label>
                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        autofocus
                        class="w-full rounded-md border border-brand-gray/40 px-3 py-2 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                    />
                    <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                </div>

                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-brand-muted">
                        {{ t('Description') }}
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-md border border-brand-gray/40 px-3 py-2 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="category" class="mb-1 block text-sm font-medium text-brand-muted">
                            {{ t('Category') }}
                        </label>
                        <select
                            id="category"
                            v-model="form.category_id"
                            class="w-full rounded-md border border-brand-gray/40 px-3 py-2 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                        >
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-600">{{ form.errors.category_id }}</p>
                    </div>

                    <div>
                        <label for="language" class="mb-1 block text-sm font-medium text-brand-muted">
                            {{ t('Language') }}
                        </label>
                        <select
                            id="language"
                            v-model="form.language"
                            class="w-full rounded-md border border-brand-gray/40 px-3 py-2 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                        >
                            <option v-for="lang in languages" :key="lang" :value="lang">{{ lang.toUpperCase() }}</option>
                        </select>
                    </div>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-md bg-brand-blue px-4 py-2 text-sm font-medium text-brand-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ t('Create guide') }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
