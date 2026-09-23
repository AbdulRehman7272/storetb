<template>
    <a class="category-card" :href="$toUrl('/category/' + category.slug)" @mouseenter="hovered = true" @mouseleave="hovered = false">
        <img :src="image" :alt="category.name" width="450" height="563" loading="lazy" decoding="async">
        <span class="category-card__shade"></span>
        <span class="category-card__content">
            <strong>{{ category.name }}</strong>
            <span v-if="category.description" class="category-card__description rich-content" v-html="category.description"></span>
            <span class="category-card__action">Shop category &rarr;</span>
        </span>
    </a>
</template>

<script setup>
import { computed, ref, watchEffect } from 'vue';

const props = defineProps({
    category: { type: Object, required: true },
});

const hovered = ref(false);
const index = ref(0);
const images = computed(() => [props.category.image, props.category.hero, props.category.banner].filter(Boolean));
let timer = null;

watchEffect((onCleanup) => {
    clearInterval(timer);
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (hovered.value && !reduced && images.value.length > 1) {
        timer = window.setInterval(() => {
            index.value = (index.value + 1) % images.value.length;
        }, 1600);
    }
    onCleanup(() => clearInterval(timer));
});

const image = computed(() => images.value[index.value] || props.category.image);
</script>
