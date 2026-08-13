<script setup>
import { ref } from 'vue';
import { useTranslations } from '../i18n';

const props = defineProps({
    modelValue: { type: Array, required: true },
});

const emit = defineEmits(['update:modelValue']);

const { t } = useTranslations();
const draft = ref('');

function commitDraft() {
    const value = draft.value.trim();
    draft.value = '';

    if (value === '' || props.modelValue.includes(value)) {
        return;
    }

    emit('update:modelValue', [...props.modelValue, value]);
}

function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        commitDraft();
        return;
    }

    if (event.key === 'Backspace' && draft.value === '' && props.modelValue.length) {
        emit('update:modelValue', props.modelValue.slice(0, -1));
    }
}

function removeTag(index) {
    emit('update:modelValue', props.modelValue.filter((_, i) => i !== index));
}
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-1.5 rounded-md border border-brand-gray/40 px-2 py-1.5 focus-within:border-brand-blue focus-within:ring-1 focus-within:ring-brand-blue"
        @click="$refs.input.focus()"
    >
        <span
            v-for="(tag, index) in modelValue"
            :key="tag"
            class="flex items-center gap-1 rounded-full bg-brand-blue/10 px-2 py-0.5 text-xs text-brand-blue"
        >
            {{ tag }}
            <button type="button" class="text-brand-blue/70 hover:text-brand-blue" @click.stop="removeTag(index)">×</button>
        </span>

        <input
            ref="input"
            v-model="draft"
            type="text"
            :placeholder="modelValue.length ? '' : t('Add a tag and press Enter')"
            class="min-w-[8rem] flex-1 border-none p-0.5 text-sm text-brand-navy outline-none"
            @keydown="onKeydown"
            @blur="commitDraft"
        />
    </div>
</template>
