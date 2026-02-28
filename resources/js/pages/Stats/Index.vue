<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { updateTheme } from '@/composables/useAppearance';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { home } from '@/routes';
import { type BreadcrumbItem } from '@/types';

type CountItem = {
    label: string;
    count: number;
};

type PledgeByDhaairaaItem = {
    dhaairaa: string;
    total_voters: number;
    total_pledges: number;
    pledge_counts: Record<string, number>;
};

type RoleBreakdownBucket = 'PNC' | 'MDP' | 'UN' | 'NOT VOTING' | 'Blank';
type RoleField = 'council' | 'wdc' | 'raeesa' | 'mayor';
type RoleBreakdownCounts = Record<RoleBreakdownBucket, number>;
type RoleCountsByDhaairaaItem = {
    dhaairaa: string;
    total_voters: number;
    roles: Record<RoleField, RoleBreakdownCounts>;
};

type Props = {
    summary: {
        total_voters: number;
        voters_with_photo: number;
        voters_with_any_pledge: number;
        total_pledge_entries: number;
    };
    pledgeOptions: string[];
    pledgeByDhaairaa: PledgeByDhaairaaItem[];
    overallPledgeCounts: Record<string, number>;
    roleCountsByDhaairaa: RoleCountsByDhaairaaItem[];
    overallRoleTotals: {
        raeesa: RoleBreakdownCounts;
        mayor: RoleBreakdownCounts;
    };
    cardVisibility: {
        showOverallRaeesaTotal: boolean;
        showOverallMayorTotal: boolean;
        showCouncilByDhaairaa: boolean;
        showWdcByDhaairaa: boolean;
        showRaeesaByDhaairaa: boolean;
        showMayorByDhaairaa: boolean;
    };
    statusCounts: CountItem[];
    sexCounts: CountItem[];
};

const props = defineProps<Props>();
const roleFields: { key: RoleField; label: string }[] = [
    { key: 'council', label: 'Council' },
    { key: 'wdc', label: 'WDC' },
    { key: 'raeesa', label: 'Raeesa' },
    { key: 'mayor', label: 'Mayor' },
];
const roleBuckets: RoleBreakdownBucket[] = ['PNC', 'MDP', 'UN', 'NOT VOTING', 'Blank'];
const visibleRoleFields = computed(() =>
    roleFields.filter((role) => {
        if (role.key === 'council') {
            return props.cardVisibility.showCouncilByDhaairaa;
        }

        if (role.key === 'wdc') {
            return props.cardVisibility.showWdcByDhaairaa;
        }

        if (role.key === 'raeesa') {
            return props.cardVisibility.showRaeesaByDhaairaa;
        }

        return props.cardVisibility.showMayorByDhaairaa;
    }),
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stats',
        href: home().url
    },
];

const maxOverallPledgeCount = computed(() =>
    Math.max(1, ...Object.values(props.overallPledgeCounts)),
);
const maxStatusCount = computed(() =>
    Math.max(1, ...props.statusCounts.map((item) => item.count)),
);
const maxSexCount = computed(() => Math.max(1, ...props.sexCounts.map((item) => item.count)));

const topDhaairaa = computed(() => props.pledgeByDhaairaa.slice(0, 10));
const maxDhaairaaPledge = computed(() =>
    Math.max(1, ...topDhaairaa.value.map((item) => item.total_pledges)),
);

onMounted(() => {
    localStorage.setItem('appearance', 'light');
    updateTheme('light');
});
</script>

<template>

    <Head title="Stats" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm text-muted-foreground">Total Voters</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">{{ summary.total_voters }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm text-muted-foreground">With Photo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">{{ summary.voters_with_photo }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm text-muted-foreground">With Any Pledge</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">{{ summary.voters_with_any_pledge }}</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm text-muted-foreground">Total Pledge Entries</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold">{{ summary.total_pledge_entries }}</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Overall Pledge Distribution</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="option in pledgeOptions" :key="option" class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ option }}</span>
                                <span class="font-medium">{{ overallPledgeCounts[option] ?? 0 }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted">
                                <div class="h-2 rounded-full bg-primary"
                                    :style="{ width: `${((overallPledgeCounts[option] ?? 0) / maxOverallPledgeCount) * 100}%` }" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Vote Status Breakdown</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="item in statusCounts" :key="item.label" class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ item.label }}</span>
                                <span class="font-medium">{{ item.count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted">
                                <div class="h-2 rounded-full bg-emerald-600"
                                    :style="{ width: `${(item.count / maxStatusCount) * 100}%` }" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Sex Breakdown</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="item in sexCounts" :key="item.label" class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ item.label }}</span>
                                <span class="font-medium">{{ item.count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted">
                                <div class="h-2 rounded-full bg-sky-600"
                                    :style="{ width: `${(item.count / maxSexCount) * 100}%` }" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Pledge Entries By Dhaairaa (Top 10)</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="item in topDhaairaa" :key="item.dhaairaa" class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span class="truncate pr-2">{{ item.dhaairaa }}</span>
                                <span class="font-medium">{{ item.total_pledges }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted">
                                <div class="h-2 rounded-full bg-amber-600"
                                    :style="{ width: `${(item.total_pledges / maxDhaairaaPledge) * 100}%` }" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- <Card>
                <CardHeader>
                    <CardTitle>Pledge Counts Grouped By Dhaairaa</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/40 text-left">
                                <tr>
                                    <th class="px-3 py-2 font-medium">Dhaairaa</th>
                                    <th class="px-3 py-2 font-medium">Voters</th>
                                    <th class="px-3 py-2 font-medium">PNC</th>
                                    <th class="px-3 py-2 font-medium">MDP</th>
                                    <th class="px-3 py-2 font-medium">UN</th>
                                    <th class="px-3 py-2 font-medium">NOT VOTING</th>
                                    <th class="px-3 py-2 font-medium">Total Pledges</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in pledgeByDhaairaa" :key="item.dhaairaa" class="border-t">
                                    <td class="px-3 py-2 font-medium">{{ item.dhaairaa }}</td>
                                    <td class="px-3 py-2">{{ item.total_voters }}</td>
                                    <td class="px-3 py-2">{{ item.pledge_counts.PNC ?? 0 }}</td>
                                    <td class="px-3 py-2">{{ item.pledge_counts.MDP ?? 0 }}</td>
                                    <td class="px-3 py-2">{{ item.pledge_counts.UN ?? 0 }}</td>
                                    <td class="px-3 py-2">{{ item.pledge_counts['NOT VOTING'] ?? 0 }}</td>
                                    <td class="px-3 py-2 font-medium">{{ item.total_pledges }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="grid gap-3 md:hidden">
                        <div v-for="item in pledgeByDhaairaa" :key="item.dhaairaa" class="rounded-lg border p-3">
                            <p class="font-medium">{{ item.dhaairaa }}</p>
                            <p class="text-xs text-muted-foreground">
                                Voters: {{ item.total_voters }} | Pledges: {{ item.total_pledges }}
                            </p>
                            <div class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                <p>PNC: {{ item.pledge_counts.PNC ?? 0 }}</p>
                                <p>MDP: {{ item.pledge_counts.MDP ?? 0 }}</p>
                                <p>UN: {{ item.pledge_counts.UN ?? 0 }}</p>
                                <p>NOT VOTING: {{ item.pledge_counts['NOT VOTING'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card> -->

            <div v-if="visibleRoleFields.length > 0" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <Card v-for="role in visibleRoleFields" :key="`role-card-${role.key}`">
                    <CardHeader>
                        <CardTitle>{{ role.label }} Counts </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full text-sm">
                                <thead class="bg-muted/40 text-left">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Dhaairaa</th>
                                        <th class="px-3 py-2 font-medium">Voters</th>
                                        <th v-for="bucket in roleBuckets" :key="`head-${role.key}-${bucket}`"
                                            class="px-3 py-2 font-medium">
                                            {{ bucket }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in roleCountsByDhaairaa" :key="`row-${role.key}-${item.dhaairaa}`"
                                        class="border-t">
                                        <td class="px-3 py-2 font-medium">{{ item.dhaairaa }}</td>
                                        <td class="px-3 py-2">{{ item.total_voters }}</td>
                                        <td v-for="bucket in roleBuckets"
                                            :key="`cell-${role.key}-${item.dhaairaa}-${bucket}`" class="px-3 py-2">
                                            {{ item.roles[role.key][bucket] ?? 0 }}
                                        </td>
                                    </tr>
                                    <tr v-if="(role.key === 'raeesa' && cardVisibility.showOverallRaeesaTotal) || (role.key === 'mayor' && cardVisibility.showOverallMayorTotal)"
                                        class="border-t bg-muted/20 font-semibold">
                                        <td class="px-3 py-2">All Dhaairaa</td>
                                        <td class="px-3 py-2">{{ summary.total_voters }}</td>
                                        <td v-for="bucket in roleBuckets"
                                            :key="`overall-row-${role.key}-${bucket}`" class="px-3 py-2">
                                            {{ role.key === 'raeesa' ? (overallRoleTotals.raeesa[bucket] ?? 0) : (overallRoleTotals.mayor[bucket] ?? 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid gap-3 md:hidden">
                            <div v-for="item in roleCountsByDhaairaa" :key="`mobile-${role.key}-${item.dhaairaa}`"
                                class="rounded-lg border p-3">
                                <p class="font-medium">{{ item.dhaairaa }}</p>
                                <p class="text-xs text-muted-foreground">Voters: {{ item.total_voters }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-1 text-xs">
                                    <p v-for="bucket in roleBuckets"
                                        :key="`mobile-bucket-${role.key}-${item.dhaairaa}-${bucket}`">
                                        {{ bucket }}: {{ item.roles[role.key][bucket] ?? 0 }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="(role.key === 'raeesa' && cardVisibility.showOverallRaeesaTotal) || (role.key === 'mayor' && cardVisibility.showOverallMayorTotal)"
                                class="rounded-lg border border-primary/20 bg-muted/20 p-3">
                                <p class="font-medium">All Dhaairaa</p>
                                <p class="text-xs text-muted-foreground">Voters: {{ summary.total_voters }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-1 text-xs">
                                    <p v-for="bucket in roleBuckets" :key="`mobile-overall-${role.key}-${bucket}`">
                                        {{ bucket }}: {{ role.key === 'raeesa' ? (overallRoleTotals.raeesa[bucket] ?? 0) : (overallRoleTotals.mayor[bucket] ?? 0) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

        </div>
    </AppHeaderLayout>
</template>
