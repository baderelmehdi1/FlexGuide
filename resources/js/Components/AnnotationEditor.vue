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
const shapes = ref(structuredClone(props.media.annotations ?? []));
const tool = ref('box');
const drawing = ref(null);
const saving = ref(false);

function pct(clientX, clientY) {
    return clientToPercent(imageEl.value.getBoundingClientRect(), clientX, clientY);
}

function onMouseDown(event) {
    const { x, y } = pct(event.clientX, event.clientY);

    if (tool.value === 'point') {
        const text = window.prompt(t('Label (optional)')) ?? '';
        shapes.value.push({ type: 'point', x, y, text });
        return;
    }

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

    shapes.value.push({ type: 'box', ...normalizeBox(box) });
}

function removeShape(index) {
    shapes.value.splice(index, 1);
}

function save() {
    saving.value = true;

    router.patch(
        route('media.annotations.update', props.media.id),
        { shapes: shapes.value },
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
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-brand-navy">{{ t('Annotate') }}</h2>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-md border px-3 py-1 text-xs"
                        :class="tool === 'box' ? 'border-brand-blue bg-brand-blue/10 text-brand-blue' : 'border-brand-gray/40 text-brand-navy'"
                        @click="tool = 'box'"
                    >
                        {{ t('Box') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-md border px-3 py-1 text-xs"
                        :class="tool === 'point' ? 'border-brand-blue bg-brand-blue/10 text-brand-blue' : 'border-brand-gray/40 text-brand-navy'"
                        @click="tool = 'point'"
                    >
                        {{ t('Point') }}
                    </button>
                </div>
            </div>

            <p class="mb-2 text-xs text-brand-muted">{{ t('Click and drag to draw a box. Click to place a point.') }}</p>

            <div
                class="relative mb-3 max-h-[60vh] overflow-hidden rounded-md border border-brand-gray/30 select-none"
                @mousedown="onMouseDown"
                @mousemove="onMouseMove"
                @mouseup="onMouseUp"
                @mouseleave="drawing = null"
            >
                <img ref="imageEl" :src="media.url" class="block w-full" draggable="false" />
                <AnnotationOverlay :shapes="shapes" />
                <svg v-if="drawing" viewBox="0 0 100 100" preserveAspectRatio="none" class="pointer-events-none absolute inset-0 h-full w-full">
                    <rect
                        :x="drawing.w < 0 ? drawing.x + drawing.w : drawing.x"
                        :y="drawing.h < 0 ? drawing.y + drawing.h : drawing.y"
                        :width="Math.abs(drawing.w)"
                        :height="Math.abs(drawing.h)"
                        fill="rgba(218,175,55,0.2)"
                        stroke="#daaf37"
                        stroke-width="0.6"
                        vector-effect="non-scaling-stroke"
                    />
                </svg>
            </div>

            <ul v-if="shapes.length" class="mb-3 flex flex-wrap gap-2">
                <li
                    v-for="(shape, index) in shapes"
                    :key="index"
                    class="flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs text-brand-navy"
                >
                    {{ shape.type === 'box' ? t('Box') : t('Point') }}{{ shape.text ? `: ${shape.text}` : '' }}
                    <button type="button" class="text-red-600" @click="removeShape(index)">×</button>
                </li>
            </ul>

            <div class="flex justify-end gap-2">
                <button type="button" class="rounded-md border border-brand-gray/40 px-4 py-2 text-sm text-brand-navy" @click="emit('close')">
                    {{ t('Cancel') }}
                </button>
                <button
                    type="button"
                    class="rounded-md bg-brand-blue px-4 py-2 text-sm font-medium text-white transition-colors duration-150 hover:bg-brand-blue/90 active:scale-[0.98] disabled:opacity-60"
                    :disabled="saving"
                    @click="save"
                >
                    {{ t('Save annotations') }}
                </button>
            </div>
        </div>
    </div>
</template>
