<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { index as zerodayIndex } from '@/actions/App/Http/Controllers/ZerodayController';
</script>

<template>
    <div class="flex min-h-[60vh] items-center justify-center p-8">
        <Link :href="zerodayIndex().url" class="block w-full max-w-sm">
            <div
                class="flex flex-col items-center justify-center gap-4 rounded-2xl border-2 border-primary bg-primary p-12 text-primary-foreground shadow-lg transition-opacity hover:opacity-90"
            >
                <span class="text-center text-2xl font-bold">Go to Zero Day</span>
            </div>
        </Link>
    </div>
</template>
