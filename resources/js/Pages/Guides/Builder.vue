<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { PaperAirplaneIcon, PlusIcon } from '@heroicons/vue/24/outline';
import Sortable from 'sortablejs';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { route } from '../../../../vendor/tightenco/ziggy';
import StepEditor from '../../Components/StepEditor.vue';
import TagInput from '../../Components/TagInput.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import { useTranslations } from '../../i18n';

const props = defineProps({
    guide: { type: Object, required: true },
    steps: { type: Array, required: true },
    categories: { type: Array, required: true },
    languages: { type: Array, required: true },
});

const { t } = useTranslations();
const stepsContainer = ref(null);
let sortable = null;

onMounted(() => {
    sortable = Sortable.create(stepsContainer.value, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: () => {
            const stepIds = Array.from(stepsContainer.value.children).map((el) => Number(el.dataset.stepId));

            router.patch(route('steps.reorder', props.guide.slug), { stepIds }, { preserveScroll: true });
        },
    });
});

onBeforeUnmount(() => {
    sortable?.destroy();
});

const detailsForm = useForm({
    title: props.guide.title,
    description: props.guide.description ?? '',
    category_id: props.guide.category_id,
    language: props.guide.language,
    tags: [...props.guide.tags],
});

function saveDetails() {
    detailsForm.patch(route('guides.update', props.guide.slug), { preserveScroll: true });
}

function addStep() {
    router.post(route('steps.store', props.guide.slug), {}, { preserveScroll: true });
}

function submitForReview() {
    router.post(route('guides.submit', props.guide.slug));
}
</script>

<template>
    <AppLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-lg font-semibold text-brand-navy">{{ guide.title }}</h1>
            <button
                v-if="guide.status === 'draft'"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-md bg-brand-navy px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-navy/90 active:scale-[0.98] disabled:opacity-60"
                :disabled="!steps.length"
                @click="submitForReview"
            >
                <PaperAirplaneIcon class="h-4 w-4 rtl:scale-x-[-1]" />
                {{ t('Submit for review') }}
            </button>
        </div>

        <div class="mb-8 rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-sm font-semibold text-brand-navy">{{ t('Guide details') }}</h2>

            <div class="mb-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Title') }}</label>
                    <input
                        v-model="detailsForm.title"
                        type="text"
                        class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Category') }}</label>
                    <select
                        v-model="detailsForm.category_id"
                        class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                    >
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Description') }}</label>
                <textarea
                    v-model="detailsForm.description"
                    rows="2"
                    class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                />
            </div>

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="w-40">
                    <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Language') }}</label>
                    <select
                        v-model="detailsForm.language"
                        class="w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
                    >
                        <option v-for="lang in languages" :key="lang" :value="lang">{{ lang.toUpperCase() }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Tags') }}</label>
                    <TagInput v-model="detailsForm.tags" />
                </div>
            </div>

            <button
                type="button"
                class="rounded-md bg-brand-blue px-3 py-1.5 text-xs font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98]"
                :disabled="detailsForm.processing"
                @click="saveDetails"
            >
                {{ t('Save details') }}
            </button>
        </div>

        <h2 class="mb-3 text-sm font-semibold text-brand-navy">{{ t('Steps') }}</h2>

        <p v-if="!steps.length" class="mb-4 text-sm text-brand-muted">
            {{ t('This guide has no steps yet. Add one below.') }}
        </p>

        <div ref="stepsContainer" class="space-y-4">
            <StepEditor
                v-for="(step, index) in steps"
                :key="step.id"
                :data-step-id="step.id"
                :step="step"
                :index="index"
                :rtl="guide.language === 'ar'"
            />
        </div>

        <button
            type="button"
            class="mt-4 flex w-full items-center justify-center gap-1.5 rounded-md border border-dashed border-brand-gray/40 py-2 text-sm text-brand-muted transition-colors duration-150 hover:border-brand-blue hover:text-brand-blue"
            @click="addStep"
        >
            <PlusIcon class="h-4 w-4" />
            {{ t('Add step') }}
        </button>
    </AppLayout>
</template>
