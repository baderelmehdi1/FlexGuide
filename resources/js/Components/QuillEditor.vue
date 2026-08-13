<script setup>
import Quill from 'quill';
import 'quill/dist/quill.snow.css';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { serializeHtml } from '../lib/quillSerializer';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    rtl: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const editorEl = ref(null);
let quill = null;

onMounted(() => {
    quill = new Quill(editorEl.value, {
        theme: 'snow',
        placeholder: props.placeholder,
        modules: {
            toolbar: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'code'],
                ['clean'],
            ],
        },
    });

    quill.root.classList.add('prose', 'prose-sm', 'max-w-none');
    quill.root.setAttribute('dir', props.rtl ? 'rtl' : 'ltr');

    if (props.modelValue) {
        quill.clipboard.dangerouslyPasteHTML(props.modelValue);
    }

    /*
     | Deliberately no getFormat()/focus() call here. Calling either
     | immediately after mount, before the editor has ever been interacted
     | with, was the root cause of a "Cannot read properties of null
     | (reading 'offset')" crash traced from a real browser stack trace --
     | Quill tries to compute/restore a selection nobody has created yet.
     */
    quill.on('text-change', () => {
        emit('update:modelValue', serializeHtml(quill));
    });
});

onBeforeUnmount(() => {
    quill = null;
});
</script>

<template>
    <div ref="editorEl" />
</template>
