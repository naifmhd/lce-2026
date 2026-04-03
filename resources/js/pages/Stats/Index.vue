<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
import { updateTheme } from '@/composables/useAppearance';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { home } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { type Auth } from '@/types/auth';
import CallCenterDashboard from './partials/CallCenterDashboard.vue';
import CandidateDashboard from './partials/CandidateDashboard.vue';
import MonitorDashboard from './partials/MonitorDashboard.vue';

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
    distributionVisibility: {
        showCouncilDistribution: boolean;
        showWdcDistribution: boolean;
        showRaeesaDistribution: boolean;
        showMayorDistribution: boolean;
    };
    statusCounts: CountItem[];
    zerodayStats: {
        name: string;
        pledgedVoted: Record<string, number>;
        pledgedNotVoted: Record<string, number>;
        totalVoted: number;
        totalEligible: number;
    }[];
};

const props = defineProps<Props>();

const { auth } = usePage<{ auth: Auth }>().props;

const isMonitor = computed(() => !auth.isAdmin && auth.canZeroday);
const isCallCenter = computed(() => !auth.isAdmin && !auth.canZeroday && auth.canCallCenter);
const canViewCandidates = computed(() => auth.canViewCandidates);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stats',
        href: home().url
    },
];

onMounted(() => {
    localStorage.setItem('appearance', 'light');
    updateTheme('light');
});
</script>

<template>

    <Head title="Stats" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <MonitorDashboard v-if="isMonitor" />
        <CallCenterDashboard v-else-if="isCallCenter" />
        <CandidateDashboard v-else-if="canViewCandidates" v-bind="props" />
    </AppHeaderLayout>
</template>
