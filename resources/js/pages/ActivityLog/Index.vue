<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { home } from '@/routes';
import { index as activityLogIndex } from '@/routes/activity-log';
import type { BreadcrumbItem } from '@/types';

type LogEntry = {
    id: number;
    log_name: 'voter' | 'pledge' | 'default';
    description: string;
    event: string | null;
    voter: { list_number: number; name: string } | null;
    causer: { id: number; name: string } | null;
    properties: {
        old?: Record<string, string | null>;
        attributes?: Record<string, string | null>;
        filters?: Record<string, string | null>;
        total_records?: number;
        ip?: string;
    };
    created_at: string;
};

type Causer = { id: number; name: string };

type PaginatedLogs = {
    data: LogEntry[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

type Props = {
    logs: PaginatedLogs;
    causers: Causer[];
    filters: { causer?: string; log_name?: string; event?: string };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: home().url },
    { title: 'Activity Log', href: activityLogIndex().url },
];

const filterForm = reactive({
    causer: props.filters.causer ?? '',
    log_name: props.filters.log_name ?? '',
    event: props.filters.event ?? '',
});

function applyFilters() {
    router.get(activityLogIndex().url, {
        causer: filterForm.causer || undefined,
        log_name: filterForm.log_name || undefined,
        event: filterForm.event || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    filterForm.causer = '';
    filterForm.log_name = '';
    filterForm.event = '';
    applyFilters();
}

function formatDate(dateStr: string) {
    return new Date(dateStr).toLocaleString();
}

function changedFields(log: LogEntry): string[] {
    const old = log.properties.old ?? {};
    const attrs = log.properties.attributes ?? {};
    const keys = new Set([...Object.keys(old), ...Object.keys(attrs)]);
    return [...keys].filter((k) => old[k] !== attrs[k]);
}
</script>

<template>
    <Head title="Activity Log" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">

            <!-- Filters -->
            <div class="rounded-xl border bg-card p-4">
                <form class="flex flex-wrap items-end gap-4" @submit.prevent="applyFilters">
                    <div class="space-y-1">
                        <label class="text-sm font-medium">User</label>
                        <select v-model="filterForm.causer"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Users</option>
                            <option v-for="c in causers" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium">Type</label>
                        <select v-model="filterForm.log_name"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Types</option>
                            <option value="voter">Voter Record</option>
                            <option value="pledge">Pledge</option>
                            <option value="default">Export</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium">Event</label>
                        <select v-model="filterForm.event"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Events</option>
                            <option value="created">Created</option>
                            <option value="updated">Updated</option>
                        </select>
                    </div>
                    <Button type="submit" size="sm">Filter</Button>
                    <Button type="button" size="sm" variant="outline" @click="clearFilters">Clear</Button>
                </form>
            </div>

            <!-- Table -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        Voter Record Changes
                        <span class="ml-2 text-sm font-normal text-muted-foreground">{{ logs.total }} entries</span>
                    </CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/40 text-left">
                                <tr>
                                    <th class="px-4 py-3 font-medium">When</th>
                                    <th class="px-4 py-3 font-medium">User</th>
                                    <th class="px-4 py-3 font-medium">Voter</th>
                                    <th class="px-4 py-3 font-medium">Type</th>
                                    <th class="px-4 py-3 font-medium">Event</th>
                                    <th class="px-4 py-3 font-medium">Changes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in logs.data" :key="log.id" class="border-t">
                                    <td class="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                        {{ formatDate(log.created_at) }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        {{ log.causer?.name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="log.voter">
                                            <p class="font-medium">#{{ log.voter.list_number }}</p>
                                            <p class="text-xs text-muted-foreground">{{ log.voter.name }}</p>
                                        </div>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'inline-block rounded px-2 py-0.5 text-xs font-medium',
                                            log.log_name === 'pledge'
                                                ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                                : log.log_name === 'default'
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                    : 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                                        ]">
                                            {{ log.log_name === 'pledge' ? 'Pledge' : log.log_name === 'default' ? 'Export' : 'Voter Record' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span v-if="log.event" :class="[
                                            'inline-block rounded px-2 py-0.5 text-xs font-medium',
                                            log.event === 'created'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                        ]">
                                            {{ log.event }}
                                        </span>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <!-- Export log -->
                                        <div v-if="log.log_name === 'default'" class="space-y-1 text-xs">
                                            <p><span class="font-medium">Records:</span> {{ log.properties.total_records ?? '—' }}</p>
                                            <div v-if="log.properties.filters && Object.keys(log.properties.filters).length > 0">
                                                <p class="font-medium">Filters:</p>
                                                <div v-for="(val, key) in log.properties.filters" :key="key" class="ml-2">
                                                    <span class="capitalize">{{ String(key).replace('_', ' ') }}:</span>
                                                    <span class="ml-1 text-muted-foreground">{{ val }}</span>
                                                </div>
                                            </div>
                                            <p v-else class="text-muted-foreground">No filters applied</p>
                                        </div>
                                        <!-- Updated log -->
                                        <div v-else-if="log.event === 'updated'" class="space-y-1">
                                            <div v-for="field in changedFields(log)" :key="field"
                                                class="flex items-center gap-2 text-xs">
                                                <span class="font-medium capitalize">{{ field.replace('_', ' ') }}:</span>
                                                <span class="text-muted-foreground line-through">
                                                    {{ log.properties.old?.[field] ?? 'blank' }}
                                                </span>
                                                <span>→</span>
                                                <span class="font-medium">
                                                    {{ log.properties.attributes?.[field] ?? 'blank' }}
                                                </span>
                                            </div>
                                        </div>
                                        <!-- Created log -->
                                        <div v-else class="space-y-1">
                                            <div v-for="(val, field) in log.properties.attributes" :key="field"
                                                class="text-xs">
                                                <span class="font-medium capitalize">{{ String(field).replace('_', ' ') }}:</span>
                                                <span class="ml-1">{{ val ?? 'blank' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                        No activity logs found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1"
                        class="flex flex-wrap items-center justify-center gap-2 border-t p-3 md:justify-end">
                        <template v-for="(link, index) in logs.links" :key="index">
                            <span v-if="link.url === null"
                                class="rounded-md border px-3 py-1.5 text-sm text-muted-foreground"
                                v-html="link.label" />
                            <Link v-else :href="link.url"
                                class="rounded-md border px-3 py-1.5 text-sm"
                                :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-muted'"
                                v-html="link.label" />
                        </template>
                    </div>
                </CardContent>
            </Card>

        </div>
    </AppHeaderLayout>
</template>
