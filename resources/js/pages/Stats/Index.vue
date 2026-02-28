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
        male_count: number;
        female_count: number;
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
const visibleDistributionRoleFields = computed(() =>
    roleFields.filter((role) => {
        if (role.key === 'council') {
            return props.cardVisibility.showCouncilByDhaairaa;
        }

        if (role.key === 'wdc') {
            return props.cardVisibility.showWdcByDhaairaa;
        }

        if (role.key === 'raeesa') {
            return props.cardVisibility.showOverallRaeesaTotal;
        }

        return props.cardVisibility.showOverallMayorTotal;
    }),
);
const roleDistributionTotals = computed(() => {
    const totals = {
        council: { PNC: 0, MDP: 0, UN: 0, 'NOT VOTING': 0, Blank: 0 },
        wdc: { PNC: 0, MDP: 0, UN: 0, 'NOT VOTING': 0, Blank: 0 },
        raeesa: { PNC: 0, MDP: 0, UN: 0, 'NOT VOTING': 0, Blank: 0 },
        mayor: { PNC: 0, MDP: 0, UN: 0, 'NOT VOTING': 0, Blank: 0 },
    } satisfies Record<RoleField, RoleBreakdownCounts>;

    for (const item of props.roleCountsByDhaairaa) {
        for (const role of roleFields) {
            for (const bucket of roleBuckets) {
                totals[role.key][bucket] += item.roles[role.key][bucket] ?? 0;
            }
        }
    }

    return totals;
});
const maxRoleDistributionCountByRole = computed(() => ({
    council: Math.max(1, ...roleBuckets.map((bucket) => roleDistributionTotals.value.council[bucket] ?? 0)),
    wdc: Math.max(1, ...roleBuckets.map((bucket) => roleDistributionTotals.value.wdc[bucket] ?? 0)),
    raeesa: Math.max(1, ...roleBuckets.map((bucket) => roleDistributionTotals.value.raeesa[bucket] ?? 0)),
    mayor: Math.max(1, ...roleBuckets.map((bucket) => roleDistributionTotals.value.mayor[bucket] ?? 0)),
}));
const blankVsFilledByRole = computed(() =>
    roleFields.reduce((accumulator, role) => {
        const blank = roleDistributionTotals.value[role.key].Blank ?? 0;
        const total = roleBuckets.reduce(
            (sum, bucket) => sum + (roleDistributionTotals.value[role.key][bucket] ?? 0),
            0,
        );

        accumulator[role.key] = {
            blank,
            filled: Math.max(0, total - blank),
        };

        return accumulator;
    }, {
        council: { blank: 0, filled: 0 },
        wdc: { blank: 0, filled: 0 },
        raeesa: { blank: 0, filled: 0 },
        mayor: { blank: 0, filled: 0 },
    } as Record<RoleField, { blank: number; filled: number }>),
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stats',
        href: home().url
    },
];

const maxStatusCount = computed(() =>
    Math.max(1, ...props.statusCounts.map((item) => item.count)),
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
                        <CardTitle class="text-sm text-muted-foreground">Male / Female</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground">Male</p>
                                <p class="text-3xl font-semibold">{{ summary.male_count }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">Female</p>
                                <p class="text-3xl font-semibold">{{ summary.female_count }}</p>
                            </div>
                        </div>
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

                <Card v-if="roleFields.length > 0">
                    <CardHeader>
                        <CardTitle>Pledge Completion</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full text-sm">
                                <thead class="bg-muted/40 text-left">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Role</th>
                                        <th class="px-3 py-2 font-medium">Filled</th>
                                        <th class="px-3 py-2 font-medium">Blank</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="role in roleFields" :key="`blank-filled-row-${role.key}`"
                                        class="border-t">
                                        <td class="px-3 py-2 font-medium">{{ role.label }}</td>
                                        <td class="px-3 py-2">{{ blankVsFilledByRole[role.key].filled }}</td>
                                        <td class="px-3 py-2">{{ blankVsFilledByRole[role.key].blank }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid gap-2 md:hidden">
                            <div v-for="role in roleFields" :key="`blank-filled-mobile-${role.key}`"
                                class="rounded-lg border p-3 text-sm">
                                <p class="font-medium">{{ role.label }}</p>
                                <p class="text-xs text-muted-foreground">Filled: {{ blankVsFilledByRole[role.key].filled
                                }}</p>
                                <p class="text-xs text-muted-foreground">Blank: {{ blankVsFilledByRole[role.key].blank
                                }}</p>
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

            <div v-if="visibleDistributionRoleFields.length > 0" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                <Card v-for="role in visibleDistributionRoleFields" :key="`role-card-${role.key}`">
                    <CardHeader>
                        <CardTitle>{{ role.label }} Pledge Distribution</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="bucket in roleBuckets" :key="`role-graph-${role.key}-${bucket}`" class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <span>{{ bucket }}</span>
                                <span class="font-medium">{{ roleDistributionTotals[role.key][bucket] ?? 0 }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted">
                                <div class="h-2 rounded-full bg-primary"
                                    :style="{ width: `${((roleDistributionTotals[role.key][bucket] ?? 0) / maxRoleDistributionCountByRole[role.key]) * 100}%` }" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
                <Card v-for="role in visibleRoleFields" :key="`role-table-card-${role.key}`">
                    <CardHeader>
                        <CardTitle>{{ role.label }} Counts By Dhaairaa</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full text-sm">
                                <thead class="bg-muted/40 text-left">
                                    <tr>
                                        <th class="px-3 py-2 font-medium">Dhaairaa</th>
                                        <th class="px-3 py-2 font-medium">Voters</th>
                                        <th v-for="bucket in roleBuckets" :key="`table-head-${role.key}-${bucket}`"
                                            class="px-3 py-2 font-medium">
                                            {{ bucket }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="item in roleCountsByDhaairaa"
                                        :key="`table-row-${role.key}-${item.dhaairaa}`" class="border-t">
                                        <td class="px-3 py-2 font-medium">{{ item.dhaairaa }}</td>
                                        <td class="px-3 py-2">{{ item.total_voters }}</td>
                                        <td v-for="bucket in roleBuckets"
                                            :key="`table-cell-${role.key}-${item.dhaairaa}-${bucket}`"
                                            class="px-3 py-2">
                                            {{ item.roles[role.key][bucket] ?? 0 }}
                                        </td>
                                    </tr>
                                    <tr v-if="(role.key === 'raeesa' && cardVisibility.showOverallRaeesaTotal) || (role.key === 'mayor' && cardVisibility.showOverallMayorTotal)"
                                        class="border-t bg-muted/20 font-semibold">
                                        <td class="px-3 py-2">All Dhaairaa</td>
                                        <td class="px-3 py-2">{{ summary.total_voters }}</td>
                                        <td v-for="bucket in roleBuckets" :key="`table-overall-${role.key}-${bucket}`"
                                            class="px-3 py-2">
                                            {{ role.key === 'raeesa' ? (overallRoleTotals.raeesa[bucket] ?? 0) :
                                                (overallRoleTotals.mayor[bucket] ?? 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid gap-3 md:hidden">
                            <div v-for="item in roleCountsByDhaairaa" :key="`table-mobile-${role.key}-${item.dhaairaa}`"
                                class="rounded-lg border p-3">
                                <p class="font-medium">{{ item.dhaairaa }}</p>
                                <p class="text-xs text-muted-foreground">Voters: {{ item.total_voters }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-1 text-xs">
                                    <p v-for="bucket in roleBuckets"
                                        :key="`table-mobile-cell-${role.key}-${item.dhaairaa}-${bucket}`">
                                        {{ bucket }}: {{ item.roles[role.key][bucket] ?? 0 }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="(role.key === 'raeesa' && cardVisibility.showOverallRaeesaTotal) || (role.key === 'mayor' && cardVisibility.showOverallMayorTotal)"
                                class="rounded-lg border border-primary/20 bg-muted/20 p-3">
                                <p class="font-medium">All Dhaairaa</p>
                                <p class="text-xs text-muted-foreground">Voters: {{ summary.total_voters }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-1 text-xs">
                                    <p v-for="bucket in roleBuckets"
                                        :key="`table-mobile-overall-${role.key}-${bucket}`">
                                        {{ bucket }}: {{ role.key === 'raeesa' ? (overallRoleTotals.raeesa[bucket] ?? 0)
                                            : (overallRoleTotals.mayor[bucket] ?? 0) }}
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
