<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { TrashIcon } from '@heroicons/vue/24/outline';
import { route } from '../../../vendor/tightenco/ziggy';
import { useTranslations } from '../i18n';
import QuillEditor from './QuillEditor.vue';
import ScreenshotUploader from './ScreenshotUploader.vue';

const props = defineProps({
    step: { type: Object, required: true },
    index: { type: Number, required: true },
    rtl: { type: Boolean, default: false },
});

const { t } = useTranslations();

const form = useForm({
    title: props.step.title ?? '',
    warning: props.step.warning ?? '',
    body: props.step.body ?? '',
});

function save() {
    form.patch(route('steps.update', props.step.id), { preserveScroll: true });
}

function destroy() {
    if (confirm(t('Delete step') + '?')) {
        router.delete(route('steps.destroy', props.step.id), { preserveScroll: true });
    }
}
</script>

<template>
    <div class="rounded-xl border border-brand-gray/20 bg-white p-6 shadow-sm transition-colors duration-150 focus-within:border-brand-blue/50 focus-within:bg-brand-blue/[0.02]">
        <div class="mb-4 flex items-center justify-between border-b border-brand-gray/10 pb-3">
            <div class="flex items-center gap-2.5">
                <span class="drag-handle cursor-move select-none text-lg leading-none text-brand-gray" :title="t('Drag to reorder')">⠿</span>
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-blue text-xs font-semibold text-white">
                    {{ index + 1 }}
                </span>
            </div>
            <button
                type="button"
                class="rounded-md p-1.5 text-brand-muted transition-colors duration-150 hover:bg-red-50 hover:text-red-600"
                :aria-label="t('Delete step')"
                @click="destroy"
            >
                <TrashIcon class="h-4 w-4" />
            </button>
        </div>

        <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Step title (optional)') }}</label>
        <input
            v-model="form.title"
            type="text"
            :dir="rtl ? 'rtl' : 'ltr'"
            class="mb-3 w-full rounded-md border border-brand-gray/40 px-3 py-1.5 text-base text-brand-navy focus:border-brand-blue focus:outline-none focus:ring-1 focus:ring-brand-blue"
        />

        <label class="mb-1 block text-sm font-medium text-brand-muted">{{ t('Warning (optional)') }}</label>
        <input
            v-model="form.warning"
            type="text"
            :dir="rtl ? 'rtl' : 'ltr'"
            class="mb-3 w-full rounded-md border border-brand-gold/50 px-3 py-1.5 text-base text-brand-navy focus:border-brand-gold focus:outline-none focus:ring-1 focus:ring-brand-gold"
        />

        <div class="mb-3 overflow-hidden rounded-md border border-brand-gray/40">
            <QuillEditor v-model="form.body" :rtl="rtl" />
        </div>

        <ScreenshotUploader :step-id="step.id" :screenshots="step.screenshots ?? []" class="mb-3" />

        <button
            type="button"
            class="rounded-md bg-brand-blue px-3 py-1.5 text-xs font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
            :disabled="form.processing"
            @click="save"
        >
            {{ t('Save step') }}
        </button>
        <span v-if="form.recentlySuccessful" class="ms-2 text-xs text-brand-muted">{{ t('Save step') }} ✓</span>
    </div>
</template>
