<script setup lang="ts">
import { computed } from 'vue';

type Props = {
    value: string | null | undefined;
};

const props = defineProps<Props>();

const normalizedValue = computed(() => props.value?.trim().toUpperCase() ?? '');

const backgroundClass = computed(() => {
    if (normalizedValue.value === 'MDP') {
        return 'bg-yellow-300  text-yellow-800 font-bold';
    }

    if (normalizedValue.value === 'PNC') {
        return 'bg-cyan-300 text-cyan-900 font-bold';
    }

    if (normalizedValue.value === 'UN') {
        return 'bg-slate-100 text-slate-900 font-bold';
    }

    return '';
});
</script>

<template>
    <span class="rounded-xl px-2 py-0.5 text-xs" :class="backgroundClass">
        {{ value ?? '-' }}
    </span>
</template>
