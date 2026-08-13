<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from '../../../vendor/tightenco/ziggy';
import { useTranslations } from '../i18n';
import { clientToPercent, isNegligibleDrag, normalizeBox } from '../lib/annotationMath';
import AnnotationOverlay from './AnnotationOverlay.vue';

const props = defineProps({
    media: { type: Object, required: true },
});

const emit = defineEmits(['close']);

const { t } = useTranslations();
const imageEl = ref(null);
const boxes = ref([]);
const drawing = ref(null);
const saving = ref(false);

function pct(clientX, clientY) {
    return clientToPercent(imageEl.value.getBoundingClientRect(), clientX, clientY);
}

function onMouseDown(event) {
    const { x, y } = pct(event.clientX, event.clientY);
    drawing.value = { x, y, w: 0, h: 0 };
}

function onMouseMove(event) {
    if (!drawing.value) {
        return;
    }

    const { x, y } = pct(event.clientX, event.clientY);
    drawing.value.w = x - drawing.value.x;
    drawing.value.h = y - drawing.value.y;
}

function onMouseUp() {
    if (!drawing.value) {
        return;
    }

    const box = drawing.value;
    drawing.value = null;

    if (isNegligibleDrag(box)) {
        return;
    }

    boxes.value.push(normalizeBox(box));
}

function removeBox(index) {
    boxes.value.splice(index, 1);
}

function confirmRedact() {
    if (!boxes.value.length) {
        return;
    }

    if (!window.confirm(t('This will permanently remove the selected areas from the image. Continue?'))) {
        return;
    }

    saving.value = true;

    router.post(
        route('media.redact', props.media.id),
        { boxes: boxes.value },
        {
            preserveScroll: true,
            onSuccess: () => emit('close'),
            onFinish: () => (saving.value = false),
        },
    );
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
        <div class="animate-scale-in w-full max-w-3xl rounded-lg bg-white p-4 shadow-xl">
            <h2 class="mb-1 text-sm font-semibold text-brand-navy">{{ t('Redact') }}</h2>
            <p class="mb-3 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700">
                {{ t('Redact permanently blacks out the selected areas in the image itself. This cannot be undone.') }}
            </p>
            <p class="mb-2 text-xs text-brand-muted">{{ t('Draw the areas to redact, then confirm.') }}</p>

            <div
                class="relative mb-3 max-h-[60vh] overflow-hidden rounded-md border border-brand-gray/30 select-none"
                @mousedown="onMouseDown"
                @mousemove="onMouseMove"
                @mouseup="onMouseUp"
                @mouseleave="drawing = null"
            >
                <img ref="imageEl" :src="media.url" class="block w-full" draggable="false" />
                <AnnotationOverlay :shapes="media.annotations" />

                <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="pointer-events-none absolute inset-0 h-full w-full">
                    <rect
                        v-for="(box, index) in boxes"
                        :key="index"
                        :x="box.x"
                        :y="box.y"
                        :width="box.w"
                        :height="box.h"
                        fill="black"
                    />
                    <rect
                        v-if="drawing"
                        :x="drawing.w < 0 ? drawing.x + drawing.w : drawing.x"
                        :y="drawing.h < 0 ? drawing.y + drawing.h : drawing.y"
                        :width="Math.abs(drawing.w)"
                        :height="Math.abs(drawing.h)"
                        fill="rgba(0,0,0,0.6)"
                        stroke="#dc2626"
                        stroke-width="0.6"
                        vector-effect="non-scaling-stroke"
                    />
                </svg>
            </div>

            <ul v-if="boxes.length" class="mb-3 flex flex-wrap gap-2">
                <li
                    v-for="(box, index) in boxes"
                    :key="index"
                    class="flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs text-brand-navy"
                >
                    {{ t('Box') }} {{ index + 1 }}
                    <button type="button" class="text-red-600" @click="removeBox(index)">×</button>
                </li>
            </ul>

            <div class="flex justify-end gap-2">
                <button type="button" class="rounded-md border border-brand-gray/40 px-4 py-2 text-sm text-brand-navy" @click="emit('close')">
                    {{ t('Cancel') }}
                </button>
                <button
                    type="button"
                    class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-red-700 active:scale-[0.98] disabled:opacity-60"
                    :disabled="saving || !boxes.length"
                    @click="confirmRedact"
                >
                    {{ t('Confirm redaction') }}
                </button>
            </div>
        </div>
    </div>
</template>
