<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const visible = ref(false);
const message = ref('');
let timer = null;
let unsubscribe = null;

function show(text) {
    message.value = text;
    visible.value = true;

    clearTimeout(timer);
    timer = setTimeout(() => {
        visible.value = false;
    }, 3000);
}

onMounted(() => {
    const initialStatus = usePage().props.flash.status;

    if (initialStatus) {
        show(initialStatus);
    }

    /*
     | A plain watch() on flash.status would miss two identical messages in a
     | row (e.g. "Step saved." twice) since the value never changes. Hooking
     | the router's own success event instead fires on every completed
     | visit, regardless of whether the text repeats.
     */
    unsubscribe = router.on('success', () => {
        const status = usePage().props.flash.status;

        if (status) {
            show(status);
        }
    });
});

onBeforeUnmount(() => {
    unsubscribe?.();
    clearTimeout(timer);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="toast">
            <div v-if="visible" class="pointer-events-none fixed inset-x-0 top-4 z-50 flex justify-center px-4">
                <div class="rounded-md bg-brand-navy px-4 py-2 text-sm text-white shadow-lg">
                    {{ message }}
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
