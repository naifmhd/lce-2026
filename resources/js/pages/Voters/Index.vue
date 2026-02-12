<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { updateTheme } from '@/composables/useAppearance';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { index as votersIndex } from '@/routes/voters';
import { type BreadcrumbItem } from '@/types';

type VoterListItem = {
    id: number;
    list_number: number | null;
    id_card_number: string | null;
    name: string | null;
    mobile: string | null;
    address: string | null;
    dhaairaa: string | null;
    majilis_con: string | null;
    vote_status: string | null;
    photo_url: string | null;
};

type VoterDetail = VoterListItem & {
    sex: string | null;
    dob: string | null;
    age: number | null;
    island: string | null;
    mayor: string | null;
    raeesa: string | null;
    council: string | null;
    wdc: string | null;
    re_reg_travel: string | null;
    comments: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedVoters = {
    data: VoterListItem[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
};

type Props = {
    voters: PaginatedVoters;
    filters: {
        search: string;
        dhaairaa: string;
        majilis_con: string;
        selected: number | null;
    };
    filterOptions: {
        dhaairaa: string[];
        majilis_con: string[];
    };
    selectedVoter: VoterDetail | null;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Voters',
        href: votersIndex().url,
    },
];

const filterForm = reactive({
    search: props.filters.search ?? '',
    dhaairaa: props.filters.dhaairaa ?? '',
    majilis_con: props.filters.majilis_con ?? '',
});

const selectedVoter = computed(() => props.selectedVoter);

onMounted(() => {
    localStorage.setItem('appearance', 'light');
    updateTheme('light');
});

const buildQuery = (overrides: Partial<Record<string, string | number | null>> = {}) => {
    const baseQuery: Record<string, string | number | null> = {
        search: filterForm.search.trim() === '' ? null : filterForm.search.trim(),
        dhaairaa: filterForm.dhaairaa === '' ? null : filterForm.dhaairaa,
        majilis_con: filterForm.majilis_con === '' ? null : filterForm.majilis_con,
        page: props.voters.current_page > 1 ? props.voters.current_page : null,
        selected: props.filters.selected,
    };

    const merged = {
        ...baseQuery,
        ...overrides,
    };

    return Object.fromEntries(
        Object.entries(merged).filter(([, value]) => value !== null && value !== ''),
    );
};

const applyFilters = (): void => {
    router.get(
        votersIndex.url({
            query: buildQuery({
                page: null,
                selected: null,
            }),
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = (): void => {
    filterForm.search = '';
    filterForm.dhaairaa = '';
    filterForm.majilis_con = '';
    applyFilters();
};

const openVoterDetails = (voterId: number): void => {
    router.get(
        votersIndex.url({
            query: buildQuery({
                selected: voterId,
            }),
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const closeVoterDetails = (): void => {
    router.get(
        votersIndex.url({
            query: buildQuery({
                selected: null,
            }),
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};
</script>

<template>
    <Head title="Voters" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">
            <div class="rounded-xl border bg-card p-4 md:p-5">
                <form
                    class="grid grid-cols-1 gap-4 lg:grid-cols-12"
                    @submit.prevent="applyFilters"
                >
                    <div class="space-y-2 lg:col-span-6">
                        <Label for="search">Search</Label>
                        <Input
                            id="search"
                            v-model="filterForm.search"
                            placeholder="Search by ID, name, address, or mobile"
                        />
                    </div>

                    <div class="space-y-2 lg:col-span-3">
                        <Label for="dhaairaa">Dhaairaa</Label>
                        <select
                            id="dhaairaa"
                            v-model="filterForm.dhaairaa"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">All Dhaairaa</option>
                            <option
                                v-for="option in filterOptions.dhaairaa"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2 lg:col-span-3">
                        <Label for="majilis-con">Majilis Con</Label>
                        <select
                            id="majilis-con"
                            v-model="filterForm.majilis_con"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">All Majilis Con</option>
                            <option
                                v-for="option in filterOptions.majilis_con"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </option>
                        </select>
                    </div>

                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center lg:col-span-12 lg:justify-end"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full sm:w-auto"
                            @click="clearFilters"
                        >
                            Reset
                        </Button>
                        <Button type="submit" class="w-full sm:w-auto">
                            Apply Filters
                        </Button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border bg-card">
                <div
                    class="flex flex-col gap-1 border-b px-4 py-3 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between md:px-5"
                >
                    <p>Showing {{ voters.from ?? 0 }} to {{ voters.to ?? 0 }}</p>
                    <p>Total {{ voters.total }} voters</p>
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">No.</th>
                                <th class="px-4 py-3 font-medium">Photo</th>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">ID Card</th>
                                <th class="px-4 py-3 font-medium">Mobile</th>
                                <th class="px-4 py-3 font-medium">Address</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="voter in voters.data"
                                :key="voter.id"
                                class="cursor-pointer border-t transition-colors hover:bg-muted/30"
                                @click="openVoterDetails(voter.id)"
                            >
                                <td class="px-4 py-3">{{ voter.list_number }}</td>
                                <td class="px-4 py-3">
                                    <img
                                        v-if="voter.photo_url"
                                        :src="voter.photo_url"
                                        :alt="voter.name ?? 'Voter photo'"
                                        class="h-10 w-10 rounded-md object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-10 w-10 rounded-md bg-muted"
                                    />
                                </td>
                                <td class="px-4 py-3 font-medium">
                                    {{ voter.name ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ voter.id_card_number ?? '-' }}
                                </td>
                                <td class="px-4 py-3">{{ voter.mobile ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    {{ voter.address ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <Badge variant="outline">
                                        {{ voter.vote_status ?? 'Unknown' }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="voters.data.length === 0">
                                <td
                                    colspan="7"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    No voters found for the current search and
                                    filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-3 p-3 md:hidden">
                    <button
                        v-for="voter in voters.data"
                        :key="voter.id"
                        type="button"
                        class="flex items-start gap-3 rounded-lg border p-3 text-left"
                        @click="openVoterDetails(voter.id)"
                    >
                        <img
                            v-if="voter.photo_url"
                            :src="voter.photo_url"
                            :alt="voter.name ?? 'Voter photo'"
                            class="h-14 w-14 rounded-md object-cover"
                        />
                        <div v-else class="h-14 w-14 rounded-md bg-muted" />

                        <div class="min-w-0 flex-1 space-y-1">
                            <p class="truncate font-medium">
                                {{ voter.name ?? '-' }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ voter.id_card_number ?? '-' }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ voter.mobile ?? '-' }}
                            </p>
                            <Badge variant="outline" class="text-[11px]">
                                {{ voter.vote_status ?? 'Unknown' }}
                            </Badge>
                        </div>
                    </button>

                    <p
                        v-if="voters.data.length === 0"
                        class="rounded-lg border p-6 text-center text-sm text-muted-foreground"
                    >
                        No voters found for the current search and filters.
                    </p>
                </div>

                <div
                    v-if="voters.links.length > 3"
                    class="flex flex-wrap items-center justify-center gap-2 border-t p-3 md:justify-end"
                >
                    <template v-for="(link, index) in voters.links" :key="index">
                        <span
                            v-if="link.url === null"
                            class="rounded-md border px-3 py-1.5 text-sm text-muted-foreground"
                            v-html="link.label"
                        />
                        <Link
                            v-else
                            :href="link.url"
                            preserve-scroll
                            preserve-state
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'hover:bg-muted'
                            "
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppHeaderLayout>

    <Dialog
        :open="selectedVoter !== null"
        @update:open="(isOpen) => { if (!isOpen) { closeVoterDetails(); } }"
    >
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    {{ selectedVoter?.name ?? 'Voter Details' }}
                </DialogTitle>
                <DialogDescription>
                    Full profile and voting information
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedVoter" class="space-y-4">
                <div class="flex items-center gap-4 rounded-lg border p-3">
                    <img
                        v-if="selectedVoter.photo_url"
                        :src="selectedVoter.photo_url"
                        :alt="selectedVoter.name ?? 'Voter photo'"
                        class="h-20 w-20 rounded-md object-cover"
                    />
                    <div v-else class="h-20 w-20 rounded-md bg-muted" />

                    <div class="min-w-0 space-y-1">
                        <p class="text-base font-semibold">
                            {{ selectedVoter.name ?? '-' }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            ID: {{ selectedVoter.id_card_number ?? '-' }}
                        </p>
                        <Badge variant="outline">
                            {{ selectedVoter.vote_status ?? 'Unknown' }}
                        </Badge>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">Mobile</p>
                        <p class="font-medium">{{ selectedVoter.mobile ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">Sex / Age</p>
                        <p class="font-medium">
                            {{ selectedVoter.sex ?? '-' }} /
                            {{ selectedVoter.age ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">DOB</p>
                        <p class="font-medium">{{ selectedVoter.dob ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">Island</p>
                        <p class="font-medium">{{ selectedVoter.island ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">Dhaairaa</p>
                        <p class="font-medium">
                            {{ selectedVoter.dhaairaa ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">Majilis Con</p>
                        <p class="font-medium">
                            {{ selectedVoter.majilis_con ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-3 sm:col-span-2">
                        <p class="text-xs text-muted-foreground">Address</p>
                        <p class="font-medium">
                            {{ selectedVoter.address ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">Mayor</p>
                        <p class="font-medium">{{ selectedVoter.mayor ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">Raeesa</p>
                        <p class="font-medium">{{ selectedVoter.raeesa ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">Council</p>
                        <p class="font-medium">
                            {{ selectedVoter.council ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-3">
                        <p class="text-xs text-muted-foreground">WDC</p>
                        <p class="font-medium">{{ selectedVoter.wdc ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border p-3 sm:col-span-2">
                        <p class="text-xs text-muted-foreground">Re-Reg / Travel</p>
                        <p class="font-medium">
                            {{ selectedVoter.re_reg_travel ?? '-' }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-3 sm:col-span-2">
                        <p class="text-xs text-muted-foreground">Comments</p>
                        <p class="font-medium">
                            {{ selectedVoter.comments ?? '-' }}
                        </p>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
