<script setup lang="ts">
import { usePoll } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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

type ZerodayStatRow = {
    name: string;
    pledgedVoted: Record<string, number>;
    pledgedNotVoted: Record<string, number>;
    totalVoted: number;
    totalEligible: number;
};

type DhaairaRow = {
    box: string;
    eligible: number;
    voted: number;
    remaining: number;
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
    distributionVisibility: {
        showCouncilDistribution: boolean;
        showWdcDistribution: boolean;
        showRaeesaDistribution: boolean;
        showMayorDistribution: boolean;
    };
    statusCounts: CountItem[];
    zerodayStats: ZerodayStatRow[];
    dhaairaStats: DhaairaRow[];
};

const props = defineProps<Props>();

const activeTab = ref<'pledges' | 'zeroday'>('zeroday');

const { start: startPoll, stop: stopPoll } = usePoll(15000, { only: ['zerodayStats'], autoStart: false });

watch(
    activeTab,
    (tab) => {
        if (tab === 'zeroday') {
            startPoll();
        } else {
            stopPoll();
        }
    },
    { immediate: false },
);

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
        if (role.key === 'council') return props.distributionVisibility.showCouncilDistribution;
        if (role.key === 'wdc') return props.distributionVisibility.showWdcDistribution;
        if (role.key === 'raeesa') return props.distributionVisibility.showRaeesaDistribution;
        return props.distributionVisibility.showMayorDistribution;
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

// Box summary totals
const dhaairaTotals = computed(() => ({
    eligible: props.dhaairaStats.reduce((s, r) => s + r.eligible, 0),
    voted: props.dhaairaStats.reduce((s, r) => s + r.voted, 0),
    remaining: props.dhaairaStats.reduce((s, r) => s + r.remaining, 0),
}));

// Zeroday helpers
function turnoutPct(row: ZerodayStatRow): number {
    if (row.totalEligible === 0) return 0;
    return Math.round((row.totalVoted / row.totalEligible) * 100);
}

function pledgedVotedTotal(row: ZerodayStatRow): number {
    return props.pledgeOptions.reduce((s, opt) => s + (row.pledgedVoted[opt] ?? 0), 0);
}

function pledgedNotVotedTotal(row: ZerodayStatRow): number {
    return props.pledgeOptions.reduce((s, opt) => s + (row.pledgedNotVoted[opt] ?? 0), 0);
}

function unpledgedVoted(row: ZerodayStatRow): number {
    return Math.max(0, row.totalVoted - pledgedVotedTotal(row));
}
</script>

<template>
    <div class="mx-auto flex w-full max-w-7xl flex-col">

        <!-- Tab bar -->
        <div class="flex gap-1 border-b px-4 pt-4">
            <button
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                    activeTab === 'pledges'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                ]"
                @click="activeTab = 'pledges'"
            >
                Pledges
            </button>
            <button
                :class="[
                    'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                    activeTab === 'zeroday'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                ]"
                @click="activeTab = 'zeroday'"
            >
                Zeroday
            </button>
        </div>

        <!-- Pledges tab -->
        <div v-if="activeTab === 'pledges'" class="flex flex-col gap-4 p-4">
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
                                <p class="text-xs text-muted-foreground">Filled: {{ blankVsFilledByRole[role.key].filled }}</p>
                                <p class="text-xs text-muted-foreground">Blank: {{ blankVsFilledByRole[role.key].blank }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div v-if="visibleDistributionRoleFields.length > 0" class="space-y-4">
                    <Card v-for="role in visibleDistributionRoleFields" :key="`role-card-${role.key}`">
                        <CardHeader>
                            <CardTitle>{{ role.label }} Pledge Distribution</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-for="bucket in roleBuckets" :key="`role-graph-${role.key}-${bucket}`"
                                class="space-y-1">
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
                </div>
            </div>

            <div v-if="visibleDistributionRoleFields.length > 0" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
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

        <!-- Zeroday tab -->
        <div v-if="activeTab === 'zeroday'" class="flex flex-col gap-4 p-4">

            <!-- Dhaaira summary table -->
            <div v-if="dhaairaStats.length > 0" class="rounded-xl border bg-card">
                <div class="border-b px-4 py-3">
                    <h2 class="text-sm font-semibold">Box Summary</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">Box</th>
                                <th class="px-4 py-2.5 text-right font-medium">Voters</th>
                                <th class="px-4 py-2.5 text-right font-medium text-green-700 dark:text-green-400">Voted</th>
                                <th class="px-4 py-2.5 text-right font-medium text-amber-700 dark:text-amber-400">Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in dhaairaStats" :key="row.box" class="border-t">
                                <td class="px-4 py-2.5 font-medium">{{ row.box }}</td>
                                <td class="px-4 py-2.5 text-right">{{ row.eligible.toLocaleString() }}</td>
                                <td class="px-4 py-2.5 text-right font-medium text-green-700 dark:text-green-400">{{ row.voted.toLocaleString() }}</td>
                                <td class="px-4 py-2.5 text-right font-medium text-amber-700 dark:text-amber-400">{{ row.remaining.toLocaleString() }}</td>
                            </tr>
                            <tr class="border-t bg-muted/30 font-semibold">
                                <td class="px-4 py-2.5">Total</td>
                                <td class="px-4 py-2.5 text-right">{{ dhaairaTotals.eligible.toLocaleString() }}</td>
                                <td class="px-4 py-2.5 text-right text-green-700 dark:text-green-400">{{ dhaairaTotals.voted.toLocaleString() }}</td>
                                <td class="px-4 py-2.5 text-right text-amber-700 dark:text-amber-400">{{ dhaairaTotals.remaining.toLocaleString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-1 xl:grid-cols-2">
                <Card v-for="row in zerodayStats" :key="row.name">
                    <CardHeader class="pb-3">
                        <div class="flex items-start justify-between gap-2">
                            <CardTitle class="text-base leading-tight">{{ row.name }}</CardTitle>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-sm font-bold tabular-nums"
                                :class="turnoutPct(row) >= 50
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'"
                            >
                                {{ turnoutPct(row) }}%
                            </span>
                        </div>

                        <!-- Turnout progress bar -->
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-green-500 transition-all duration-500"
                                :style="{ width: turnoutPct(row) + '%' }"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ row.totalVoted.toLocaleString() }} / {{ row.totalEligible.toLocaleString() }} voted
                        </p>
                    </CardHeader>

                    <CardContent class="space-y-3 pt-0 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <!-- Voted panel -->
                        <div class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950/30">
                            <p class="mb-2 text-xs font-semibold text-green-700 dark:text-green-400">Pledged &amp; Voted</p>
                            <div class="space-y-1">
                                <div
                                    v-for="opt in pledgeOptions"
                                    :key="`voted-${row.name}-${opt}`"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-muted-foreground">{{ opt }}</span>
                                    <span class="font-medium tabular-nums">{{ (row.pledgedVoted[opt] ?? 0).toLocaleString() }}</span>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center justify-between border-t border-green-200 pt-2 text-sm dark:border-green-900">
                                <span class="font-medium text-green-700 dark:text-green-400">Total</span>
                                <span class="font-bold tabular-nums text-green-700 dark:text-green-400">{{ pledgedVotedTotal(row).toLocaleString() }}</span>
                            </div>
                            <div v-if="unpledgedVoted(row) > 0" class="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                                <span>+ unpledged voted</span>
                                <span class="tabular-nums">{{ unpledgedVoted(row).toLocaleString() }}</span>
                            </div>
                        </div>

                        <!-- Still out panel -->
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30">
                            <p class="mb-2 text-xs font-semibold text-amber-700 dark:text-amber-400">Pledged &amp; Still Out</p>
                            <div class="space-y-1">
                                <div
                                    v-for="opt in pledgeOptions"
                                    :key="`not-voted-${row.name}-${opt}`"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-muted-foreground">{{ opt }}</span>
                                    <span class="font-medium tabular-nums">{{ (row.pledgedNotVoted[opt] ?? 0).toLocaleString() }}</span>
                                </div>
                            </div>
                            <div class="mt-2 flex items-center justify-between border-t border-amber-200 pt-2 text-sm dark:border-amber-900">
                                <span class="font-medium text-amber-700 dark:text-amber-400">Total</span>
                                <span class="font-bold tabular-nums text-amber-700 dark:text-amber-400">{{ pledgedNotVotedTotal(row).toLocaleString() }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

    </div>
</template>
