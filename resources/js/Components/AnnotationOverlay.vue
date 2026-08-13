<script setup>
defineProps({
    shapes: { type: Array, default: () => [] },
});
</script>

<template>
    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="pointer-events-none absolute inset-0 h-full w-full">
        <rect
            v-for="(shape, index) in shapes.filter((s) => s.type === 'box')"
            :key="`box-${index}`"
            :x="shape.x"
            :y="shape.y"
            :width="shape.w"
            :height="shape.h"
            fill="none"
            stroke="#daaf37"
            stroke-width="0.6"
            vector-effect="non-scaling-stroke"
        />
        <circle
            v-for="(shape, index) in shapes.filter((s) => s.type === 'point')"
            :key="`point-${index}`"
            :cx="shape.x"
            :cy="shape.y"
            r="1.4"
            fill="#daaf37"
            stroke="#0a1a3a"
            stroke-width="0.4"
            vector-effect="non-scaling-stroke"
        />
    </svg>

    <span
        v-for="(shape, index) in shapes.filter((s) => s.type === 'point' && s.text)"
        :key="`label-${index}`"
        class="absolute -translate-x-1/2 -translate-y-full rounded bg-brand-navy px-1.5 py-0.5 text-[10px] whitespace-nowrap text-white"
        :style="{ left: shape.x + '%', top: shape.y + '%' }"
    >
        {{ shape.text }}
    </span>
</template>
