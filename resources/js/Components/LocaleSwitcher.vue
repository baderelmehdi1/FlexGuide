<script setup>
import { router } from '@inertiajs/vue3';
import { route } from '../../../vendor/tightenco/ziggy';
import { useLocale } from '../i18n';

const props = defineProps({
    dark: { type: Boolean, default: false },
});

const locale = useLocale();

function switchTo(value) {
    if (value === locale.value.current) {
        return;
    }

    router.post(route('locale.update'), { locale: value }, { preserveScroll: true });
}
</script>

<template>
    <div class="flex items-center gap-1 text-xs">
        <button
            v-for="option in locale.options"
            :key="option.value"
            type="button"
            class="rounded px-2 py-1 transition"
            :class="[
                option.value === locale.current
                    ? dark
                        ? 'bg-white/15 font-medium text-white'
                        : 'bg-brand-blue/10 font-medium text-brand-blue'
                    : dark
                      ? 'text-white/60 hover:text-white'
                      : 'text-brand-muted hover:text-brand-navy',
            ]"
            @click="switchTo(option.value)"
        >
            {{ option.label }}
        </button>
    </div>
</template>
