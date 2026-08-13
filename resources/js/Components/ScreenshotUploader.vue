<script setup>
import { router } from '@inertiajs/vue3';
import * as FilePond from 'filepond';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { route } from '../../../vendor/tightenco/ziggy';
import { useTranslations } from '../i18n';
import AnnotationEditor from './AnnotationEditor.vue';
import AnnotationOverlay from './AnnotationOverlay.vue';
import RedactEditor from './RedactEditor.vue';

FilePond.registerPlugin(FilePondPluginImagePreview, FilePondPluginFileValidateType);

const props = defineProps({
    stepId: { type: Number, required: true },
    screenshots: { type: Array, required: true },
});

const { t } = useTranslations();
const inputEl = ref(null);
let pond = null;
const annotating = ref(null);
const redacting = ref(null);

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

onMounted(() => {
    pond = FilePond.create(inputEl.value, {
        // Must match the field name StepScreenshotController reads via
        // $request->file('file') -- FilePond otherwise sends it as
        // "filepond", which silently failed Laravel's validation.
        name: 'file',
        acceptedFileTypes: ['image/png', 'image/jpeg', 'image/webp'],
        labelIdle: t('Drop a screenshot or click to upload'),
        allowRevert: false,
        server: {
            process: {
                url: route('steps.screenshots.store', props.stepId),
                headers: { 'X-CSRF-TOKEN': csrfToken() },
                onload: () => {
                    router.reload({ only: ['steps'], preserveScroll: true });
                    pond.removeFiles();
                },
                onerror: (response) => {
                    console.error('Screenshot upload failed:', response);
                },
            },
        },
    });
});

onBeforeUnmount(() => {
    pond?.destroy();
});

function removeScreenshot(mediaId) {
    router.delete(route('steps.screenshots.destroy', [props.stepId, mediaId]), { preserveScroll: true });
}
</script>

<template>
    <div>
        <div v-if="screenshots.length" class="mb-3 grid grid-cols-3 gap-2">
            <div v-for="shot in screenshots" :key="shot.id" class="group relative">
                <img :src="shot.thumbUrl" class="aspect-video w-full rounded-md border border-brand-gray/20 object-cover" />
                <AnnotationOverlay :shapes="shot.annotations" />
                <div class="absolute end-1 top-1 hidden gap-1 group-hover:flex">
                    <button
                        type="button"
                        class="rounded bg-brand-navy/90 px-1.5 py-0.5 text-xs text-white"
                        @click="annotating = shot"
                    >
                        {{ t('Annotate') }}
                    </button>
                    <button
                        type="button"
                        class="rounded bg-red-700/90 px-1.5 py-0.5 text-xs text-white"
                        @click="redacting = shot"
                    >
                        {{ t('Redact') }}
                    </button>
                    <button
                        type="button"
                        :aria-label="t('Remove screenshot')"
                        class="rounded bg-red-600/90 px-1.5 py-0.5 text-xs text-white"
                        @click="removeScreenshot(shot.id)"
                    >
                        ×
                    </button>
                </div>
            </div>
        </div>

        <input ref="inputEl" type="file" name="file" />

        <AnnotationEditor v-if="annotating" :media="annotating" @close="annotating = null" />
        <RedactEditor v-if="redacting" :media="redacting" @close="redacting = null" />
    </div>
</template>
