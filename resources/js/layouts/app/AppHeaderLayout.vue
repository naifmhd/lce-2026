<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const printedBy = computed(() => (page.props.auth as any)?.user?.name ?? '');
const printedAt = computed(() => new Date().toLocaleString());
</script>

<template>
    <AppShell class="flex-col">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent>
            <slot />
        </AppContent>
        <div class="hidden print:block border-t pt-2 mt-4 text-xs text-muted-foreground px-4">
            Printed by {{ printedBy }} &mdash; {{ printedAt }}
        </div>
    </AppShell>
</template>
