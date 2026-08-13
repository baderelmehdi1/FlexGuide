<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    categories: { type: Array, required: true },
    activeCategory: { type: String, default: null },
});
</script>

<template>
    <ul class="space-y-0.5">
        <li v-for="category in categories" :key="category.id">
            <Link
                :href="`/guides?category=${category.slug}`"
                class="flex items-center justify-between rounded-md px-2.5 py-1.5 text-sm transition-colors duration-150"
                :class="
                    activeCategory === category.slug
                        ? 'bg-brand-blue/20 font-medium text-white'
                        : 'text-white/70 hover:bg-white/10 hover:text-white'
                "
            >
                <span class="truncate">{{ category.name }}</span>
                <span class="ms-2 shrink-0 text-xs text-white/40">{{ category.guidesCount }}</span>
            </Link>

            <div v-if="category.children.length" class="ms-3 border-s border-white/10 ps-2">
                <CategoryTree :categories="category.children" :active-category="activeCategory" />
            </div>
        </li>
    </ul>
</template>
