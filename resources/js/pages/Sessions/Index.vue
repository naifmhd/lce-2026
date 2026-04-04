<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { index as sessionsIndex, destroy as sessionsDestroy } from '@/routes/sessions';
import { type BreadcrumbItem } from '@/types';
import { type Auth } from '@/types/auth';

type SessionItem = {
    id: number;
    user_name: string | null;
    user_email: string | null;
    ip_address: string | null;
    user_agent: string | null;
    last_activity_at: string | null;
    created_at: string;
    is_current: boolean;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedSessions = {
    data: SessionItem[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number | null;
};

type Props = {
    sessions: PaginatedSessions;
};

defineProps<Props>();

const { auth } = usePage<{ auth: Auth }>().props;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sessions', href: sessionsIndex().url },
];

function forceLogout(session: SessionItem): void {
    if (!confirm(`Force logout ${session.user_name ?? session.user_email ?? 'this user'}?`)) {
        return;
    }

    router.delete(sessionsDestroy(session.id).url, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Sessions" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">
            <div class="rounded-xl border bg-card">
                <div class="flex items-center justify-between border-b px-4 py-3 md:px-5">
                    <div class="text-sm text-muted-foreground">
                        <p>Showing {{ sessions.from ?? 0 }} to {{ sessions.to ?? 0 }}</p>
                        <p>Total {{ sessions.total ?? 0 }} active sessions</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">User</th>
                                <th class="px-4 py-3 font-medium">IP Address</th>
                                <th class="px-4 py-3 font-medium">Device</th>
                                <th class="px-4 py-3 font-medium">Last Active</th>
                                <th class="px-4 py-3 font-medium">Logged In</th>
                                <th class="px-4 py-3 font-medium text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="session in sessions.data" :key="session.id" class="border-t">
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ session.user_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ session.user_email }}</div>
                                    <span v-if="session.is_current" class="mt-0.5 inline-block rounded bg-primary/10 px-1.5 py-0.5 text-xs font-medium text-primary">
                                        Current
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ session.ip_address ?? '—' }}</td>
                                <td class="max-w-xs px-4 py-3">
                                    <span class="block truncate text-xs text-muted-foreground" :title="session.user_agent ?? ''">
                                        {{ session.user_agent ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ session.last_activity_at ?? '—' }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ session.created_at }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Button
                                        v-if="!session.is_current"
                                        type="button"
                                        size="sm"
                                        variant="destructive"
                                        @click="forceLogout(session)"
                                    >
                                        Force Logout
                                    </Button>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                            </tr>
                            <tr v-if="sessions.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">No active sessions found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 border-t p-3">
                    <template v-for="(link, index) in sessions.links" :key="index">
                        <span v-if="link.url === null" class="rounded-md border px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                        <Link
                            v-else
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            :only="['sessions']"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppHeaderLayout>
</template>
