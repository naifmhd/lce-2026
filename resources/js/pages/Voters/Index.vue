<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import Pledge from '@/components/Pledge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { updateTheme } from '@/composables/useAppearance';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { exportMethod as votersExport, index as votersIndex, update as votersUpdate } from '@/routes/voters';
import { type BreadcrumbItem } from '@/types';

type VoterListItem = {
    id: number;
    list_number: number | null;
    id_card_number: string | null;
    agent: string | null;
    name: string | null;
    sex: string | null;
    mobile: string | null;
    dob: string | null;
    age: number | null;
    registered_box: string | null;
    address: string | null;
    dhaairaa: string | null;
    majilis_con: string | null;
    re_reg_travel: string | null;
    comments: string | null;
    vote_status: string | null;
    pledge: {
        mayor: string | null;
        raeesa: string | null;
        council: string | null;
        wdc: string | null;
    } | null;
    photo_url: string | null;
};

type VoterDetail = VoterListItem & {
    sex: string | null;
    dob: string | null;
    age: number | null;
    registered_box: string | null;
    pledge: {
        mayor: string | null;
        raeesa: string | null;
        council: string | null;
        wdc: string | null;
    } | null;
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
    links?: PaginationLink[];
    from: number | null;
    to: number | null;
    total?: number | null;
    current_page: number;
    last_page?: number;
    per_page?: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
};

type Props = {
    voters: PaginatedVoters;
    filters: {
        search: string;
        dhaairaa: string;
        registered_box: string;
        agent: string;
        age_from: string;
        age_to: string;
        per_page: string;
        council_pledge: string;
        wdc_pledge: string;
        mayor_pledge: string;
        raeesa_pledge: string;
        re_reg_travel_filter: string;
    };
    filterOptions: {
        dhaairaa: string[];
        registered_box: string[];
        agent: string[];
    };
    selectedVoter: VoterDetail | null;
    pledgeOptions: string[];
    pledgeFilterVisibility: {
        council: boolean;
        wdc: boolean;
        mayor: boolean;
        raeesa: boolean;
    };
    pledgeVisibility: {
        council: boolean;
        wdc: boolean;
        mayor: boolean;
        raeesa: boolean;
    };
    canViewVotersList: boolean;
};

const props = defineProps<Props>();
const blankPledgeFilterValue = '__BLANK__';
const pledgeOptions = computed(() => props.pledgeOptions);
const pledgeFilterOptions = computed(() => [...props.pledgeOptions, blankPledgeFilterValue]);
const pledgeFilterVisibility = computed(() => props.pledgeFilterVisibility);
const page = usePage<{ auth?: { user?: { name?: string } } }>();
const perPageStorageKey = 'voters:per-page';
const perPageOptions = ['15', '25', '50', '100'];
const columnsStorageKey = 'voters:visible-columns';

type ColumnKey = 'no' | 'photo' | 'name' | 'mobile' | 'box' | 'dob' | 'age' | 'agent' | 'dhaairaa' | 'majilis_con' | 're_reg_travel' | 'comments' | 'council' | 'wdc' | 'mayor' | 'raeesa';

const allColumns: { key: ColumnKey; label: string }[] = [
    { key: 'no', label: 'No.' },
    { key: 'photo', label: 'Photo' },
    { key: 'name', label: 'Name / ID Card' },
    { key: 'mobile', label: 'Mobile' },
    { key: 'box', label: 'Box' },
    { key: 'dob', label: 'Date of Birth' },
    { key: 'age', label: 'Age' },
    { key: 'agent', label: 'Agent' },
    { key: 'dhaairaa', label: 'Dhaairaa' },
    { key: 'majilis_con', label: 'Majilis Con' },
    { key: 're_reg_travel', label: 'Re-Reg / Travel' },
    { key: 'comments', label: 'Comments' },
    { key: 'council', label: 'Council' },
    { key: 'wdc', label: 'WDC' },
    { key: 'mayor', label: 'Mayor' },
    { key: 'raeesa', label: 'Raeesa' },
];

const availableColumns = computed(() =>
    allColumns.filter((col) => {
        if (col.key === 'council') return props.pledgeVisibility.council;
        if (col.key === 'wdc') return props.pledgeVisibility.wdc;
        if (col.key === 'mayor') return props.pledgeVisibility.mayor;
        if (col.key === 'raeesa') return props.pledgeVisibility.raeesa;
        return true;
    }),
);

const defaultVisibleColumns: ColumnKey[] = ['no', 'photo', 'name', 'mobile', 'box', 'council', 'wdc', 'mayor', 'raeesa'];

const visibleColumns = ref<Set<ColumnKey>>(new Set(defaultVisibleColumns));

const isColumnVisible = (key: ColumnKey): boolean => visibleColumns.value.has(key);

const toggleColumn = (key: ColumnKey): void => {
    const next = new Set(visibleColumns.value);
    if (next.has(key)) {
        next.delete(key);
    } else {
        next.add(key);
    }
    visibleColumns.value = next;
    localStorage.setItem(columnsStorageKey, JSON.stringify([...next]));
};

const pledgeOptionLabel = (option: string): string => {
    if (option === blankPledgeFilterValue) {
        return 'Blank / Empty';
    }

    return option;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Voters',
        href: votersIndex().url,
    },
];

const filterForm = reactive({
    search: props.filters.search ?? '',
    dhaairaa: props.filters.dhaairaa ?? '',
    registered_box: props.filters.registered_box ?? '',
    agent: props.filters.agent ?? '',
    age_from: props.filters.age_from ?? '',
    age_to: props.filters.age_to ?? '',
    per_page: props.filters.per_page ?? '15',
    council_pledge: props.filters.council_pledge ?? '',
    wdc_pledge: props.filters.wdc_pledge ?? '',
    mayor_pledge: props.filters.mayor_pledge ?? '',
    raeesa_pledge: props.filters.raeesa_pledge ?? '',
    re_reg_travel_filter: props.filters.re_reg_travel_filter ?? '',
});

const selectedVoterState = ref<VoterDetail | null>(props.selectedVoter);
const selectedVoter = computed(() => selectedVoterState.value);
const selectedVoterMobileNumbers = computed<Array<{ display: string; href: string }>>(() => {
    const mobile = selectedVoter.value?.mobile?.trim();

    if (mobile === undefined || mobile === '') {
        return [];
    }

    return mobile
        .split(/[\/,]/)
        .map((mobileNumber) => mobileNumber.trim())
        .filter((mobileNumber) => mobileNumber !== '')
        .map((mobileNumber) => {
            const callableMobile = mobileNumber.replace(/[^+\d]/g, '');

            if (callableMobile === '') {
                return null;
            }

            return {
                display: mobileNumber,
                href: `tel:${callableMobile}`,
            };
        })
        .filter((mobileNumber): mobileNumber is { display: string; href: string } => mobileNumber !== null);
});
const isEditing = ref(false);
const shouldAutoSearch = ref(true);
const paginationLinks = computed<PaginationLink[]>(() => {
    if ((props.voters.links ?? []).length > 0) {
        return props.voters.links ?? [];
    }

    if (
        props.voters.prev_page_url === undefined &&
        props.voters.next_page_url === undefined
    ) {
        return [];
    }

    return [
        {
            url: props.voters.prev_page_url ?? null,
            label: 'Previous',
            active: false,
        },
        {
            url: props.voters.next_page_url ?? null,
            label: 'Next',
            active: false,
        },
    ];
});

const editForm = useForm({
    agent: '',
    registered_box: '',
    mobile: '',
    re_reg_travel: '',
    comments: '',
    pledge: {
        mayor: '',
        raeesa: '',
        council: '',
        wdc: '',
    },
});

onMounted(() => {
    localStorage.setItem('appearance', 'light');
    updateTheme('light');

    const savedPerPage = localStorage.getItem(perPageStorageKey);

    if (savedPerPage !== null && perPageOptions.includes(savedPerPage) && savedPerPage !== filterForm.per_page) {
        filterForm.per_page = savedPerPage;
    }

    const savedColumns = localStorage.getItem(columnsStorageKey);
    if (savedColumns !== null) {
        try {
            const parsed = JSON.parse(savedColumns) as ColumnKey[];
            visibleColumns.value = new Set(parsed.filter((k) => defaultVisibleColumns.includes(k)));
        } catch {
            // ignore malformed data
        }
    }
});

const buildQuery = (overrides: Partial<Record<string, string | number | null>> = {}) => {
    const baseQuery: Record<string, string | number | null> = {
        search: filterForm.search.trim() === '' ? null : filterForm.search.trim(),
        dhaairaa: filterForm.dhaairaa === '' ? null : filterForm.dhaairaa,
        registered_box: filterForm.registered_box === '' ? null : filterForm.registered_box,
        agent: filterForm.agent === '' ? null : filterForm.agent,
        age_from: filterForm.age_from === '' ? null : filterForm.age_from,
        age_to: filterForm.age_to === '' ? null : filterForm.age_to,
        per_page: filterForm.per_page === '' ? null : filterForm.per_page,
        council_pledge: filterForm.council_pledge === '' ? null : filterForm.council_pledge,
        wdc_pledge: filterForm.wdc_pledge === '' ? null : filterForm.wdc_pledge,
        mayor_pledge: filterForm.mayor_pledge === '' ? null : filterForm.mayor_pledge,
        raeesa_pledge: filterForm.raeesa_pledge === '' ? null : filterForm.raeesa_pledge,
        re_reg_travel_filter: filterForm.re_reg_travel_filter === '' ? null : filterForm.re_reg_travel_filter,
        page: props.voters.current_page > 1 ? props.voters.current_page : null,
    };

    if (!props.pledgeFilterVisibility.council) {
        baseQuery.council_pledge = null;
    }

    if (!props.pledgeFilterVisibility.wdc) {
        baseQuery.wdc_pledge = null;
    }

    if (!props.pledgeFilterVisibility.mayor) {
        baseQuery.mayor_pledge = null;
    }

    if (!props.pledgeFilterVisibility.raeesa) {
        baseQuery.raeesa_pledge = null;
    }

    const merged = {
        ...baseQuery,
        ...overrides,
    };

    return Object.fromEntries(
        Object.entries(merged).filter(([, value]) => value !== null && value !== ''),
    );
};

const applyFilters = (): void => {
    shouldAutoSearch.value = false;
    router.get(
        votersIndex.url({
            query: buildQuery({
                page: null,
            }),
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['voters', 'filters'],
            onFinish: () => {
                shouldAutoSearch.value = true;
            },
        },
    );
};

const downloadCsv = (): void => {
    const query = buildQuery({ page: null });
    const params = new URLSearchParams();
    Object.entries(query).forEach(([k, v]) => {
        if (v !== null && v !== undefined && v !== '') {
            params.set(k, String(v));
        }
    });
    const url = votersExport.url({ query: Object.fromEntries(params) });
    window.location.href = url;
};

const clearFilters = (): void => {
    shouldAutoSearch.value = false;
    filterForm.search = '';
    filterForm.dhaairaa = '';
    filterForm.registered_box = '';
    filterForm.agent = '';
    filterForm.age_from = '';
    filterForm.age_to = '';
    filterForm.per_page = localStorage.getItem(perPageStorageKey) ?? '15';
    filterForm.council_pledge = '';
    filterForm.wdc_pledge = '';
    filterForm.mayor_pledge = '';
    filterForm.raeesa_pledge = '';
    filterForm.re_reg_travel_filter = '';
    applyFilters();
};

const openVoterDetails = (voter: VoterListItem): void => {
    selectedVoterState.value = {
        ...voter,
        sex: voter.sex,
        dob: voter.dob,
        age: voter.age,
        registered_box: voter.registered_box,
        re_reg_travel: voter.re_reg_travel,
        comments: voter.comments,
    };
};

const closeVoterDetails = (): void => {
    isEditing.value = false;
    editForm.clearErrors();
    selectedVoterState.value = null;
};

const appendCommentForDisplay = (existingComments: string | null, newComment: string): string | null => {
    const normalizedComment = newComment.trim();

    if (normalizedComment === '') {
        return existingComments;
    }

    const currentUserName = page.props.auth?.user?.name?.trim();
    const firstName = currentUserName?.split(/\s+/)[0] ?? '';
    const userPrefix = firstName === '' ? '' : `[${firstName}] `;
    const nextCommentLine = `${userPrefix}${normalizedComment}`;

    if (existingComments === null || existingComments.trim() === '') {
        return nextCommentLine;
    }

    return `${existingComments}\n${nextCommentLine}`;
};

const syncEditForm = (voter: VoterDetail | null): void => {
    editForm.agent = voter?.agent ?? '';
    editForm.registered_box = voter?.registered_box ?? '';
    editForm.mobile = voter?.mobile ?? '';
    editForm.re_reg_travel = voter?.re_reg_travel ?? '';
    editForm.comments = '';
    editForm.pledge.mayor = voter?.pledge?.mayor ?? '';
    editForm.pledge.raeesa = voter?.pledge?.raeesa ?? '';
    editForm.pledge.council = voter?.pledge?.council ?? '';
    editForm.pledge.wdc = voter?.pledge?.wdc ?? '';
};

const startEditing = (): void => {
    syncEditForm(selectedVoter.value);
    isEditing.value = true;
};

const cancelEditing = (): void => {
    syncEditForm(selectedVoter.value);
    editForm.clearErrors();
    isEditing.value = false;
};

const saveVoter = (): void => {
    if (selectedVoter.value === null) {
        return;
    }

    editForm.patch(
        votersUpdate.url(selectedVoter.value.id, {
            query: buildQuery(),
        }),
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onSuccess: () => {
                if (selectedVoterState.value !== null) {
                    selectedVoterState.value = {
                        ...selectedVoterState.value,
                        agent: editForm.agent,
                        registered_box: editForm.registered_box,
                        mobile: editForm.mobile,
                        re_reg_travel: editForm.re_reg_travel,
                        comments: appendCommentForDisplay(
                            selectedVoterState.value.comments,
                            editForm.comments,
                        ),
                        pledge: {
                            mayor: editForm.pledge.mayor === '' ? null : editForm.pledge.mayor,
                            raeesa: editForm.pledge.raeesa === '' ? null : editForm.pledge.raeesa,
                            council: editForm.pledge.council === '' ? null : editForm.pledge.council,
                            wdc: editForm.pledge.wdc === '' ? null : editForm.pledge.wdc,
                        },
                    };
                }

                editForm.comments = '';
                isEditing.value = false;
            },
        },
    );
};

const debouncedSearch = useDebounceFn((): void => {
    if (!shouldAutoSearch.value) {
        return;
    }

    router.get(
        votersIndex.url({
            query: buildQuery({
                page: null,
            }),
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['voters', 'filters'],
        },
    );
}, 400);

watch(
    selectedVoter,
    (voter) => {
        syncEditForm(voter);

        if (voter === null) {
            isEditing.value = false;
            editForm.clearErrors();

            return;
        }

        if (isEditing.value) {
            isEditing.value = false;
            editForm.clearErrors();
        }
    },
    { immediate: true },
);

watch(
    () => props.selectedVoter,
    (value) => {
        selectedVoterState.value = value;
    },
);

watch(
    () => filterForm.search,
    () => {
        debouncedSearch();
    },
);

watch(
    () => filterForm.per_page,
    (value) => {
        if (!perPageOptions.includes(value)) {
            return;
        }

        localStorage.setItem(perPageStorageKey, value);
        applyFilters();
    },
);
</script>

<template>

    <Head title="Voters" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4 print:gap-1 print:p-0">
            <template v-if="props.canViewVotersList">
            <div class="rounded-xl border bg-card p-4 md:p-5 print:hidden">
                <form class="grid grid-cols-2 gap-4 lg:grid-cols-12" @submit.prevent="applyFilters">
                    <div class="space-y-2 col-span-2 lg:col-span-6">
                        <Label for="search">Search</Label>
                        <Input id="search" v-model="filterForm.search"
                            placeholder="Search by ID, name, address, or mobile" />
                    </div>
                    <div class="space-y-2 col-span-1 lg:col-span-2">
                        <Label for="age-from">Age From</Label>
                        <Input id="age-from" v-model="filterForm.age_from" type="number" min="0" max="150"
                            placeholder="Min age" />
                    </div>

                    <div class="space-y-2 lg:col-span-2">
                        <Label for="age-to">Age To</Label>
                        <Input id="age-to" v-model="filterForm.age_to" type="number" min="0" max="150"
                            placeholder="Max age" />
                    </div>
                    <!-- <div></div> -->

                    <div class="space-y-2 lg:col-span-2">
                        <Label for="per-page">Per Page</Label>
                        <select id="per-page" v-model="filterForm.per_page"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option v-for="option in perPageOptions" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </div>


                    <div class="space-y-2 lg:col-span-2">
                        <Label for="dhaairaa">Dhaairaa</Label>
                        <select id="dhaairaa" v-model="filterForm.dhaairaa"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Dhaairaa</option>
                            <option v-for="option in filterOptions.dhaairaa" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2 lg:col-span-2">
                        <Label for="registered-box">Registered Box</Label>
                        <select id="registered-box" v-model="filterForm.registered_box"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Registered Boxes</option>
                            <option v-for="option in filterOptions.registered_box" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2 lg:col-span-2">
                        <Label for="agent">Agent</Label>
                        <select id="agent" v-model="filterForm.agent"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Agents</option>
                            <option v-for="option in filterOptions.agent" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </div>



                    <div v-if="pledgeFilterVisibility.council" class="space-y-2 lg:col-span-2">
                        <Label for="council-pledge">Council Pledge</Label>
                        <select id="council-pledge" v-model="filterForm.council_pledge"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Council Pledges</option>
                            <option v-for="option in pledgeFilterOptions" :key="`council-${option}`" :value="option">
                                {{ pledgeOptionLabel(option) }}
                            </option>
                        </select>
                    </div>

                    <div v-if="pledgeFilterVisibility.wdc" class="space-y-2 lg:col-span-2">
                        <Label for="wdc-pledge">WDC Pledge</Label>
                        <select id="wdc-pledge" v-model="filterForm.wdc_pledge"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All WDC Pledges</option>
                            <option v-for="option in pledgeFilterOptions" :key="`wdc-${option}`" :value="option">
                                {{ pledgeOptionLabel(option) }}
                            </option>
                        </select>
                    </div>

                    <div v-if="pledgeFilterVisibility.mayor" class="space-y-2 lg:col-span-2">
                        <Label for="mayor-pledge">Mayor Pledge</Label>
                        <select id="mayor-pledge" v-model="filterForm.mayor_pledge"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Mayor Pledges</option>
                            <option v-for="option in pledgeFilterOptions" :key="`mayor-${option}`" :value="option">
                                {{ pledgeOptionLabel(option) }}
                            </option>
                        </select>
                    </div>

                    <div v-if="pledgeFilterVisibility.raeesa" class="space-y-2 lg:col-span-2">
                        <Label for="raeesa-pledge">Raeesa Pledge</Label>
                        <select id="raeesa-pledge" v-model="filterForm.raeesa_pledge"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All Raeesa Pledges</option>
                            <option v-for="option in pledgeFilterOptions" :key="`raeesa-${option}`" :value="option">
                                {{ pledgeOptionLabel(option) }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2 lg:col-span-2">
                        <Label for="re-reg-travel-filter">Re-Reg / Travel</Label>
                        <select id="re-reg-travel-filter" v-model="filterForm.re_reg_travel_filter"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                            <option value="">All</option>
                            <option value="filled">Filled</option>
                            <option value="blank">Blank</option>
                        </select>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 col-span-2 lg:col-span-12 justify-between">
                        <div class="flex gap-2">
                            <Button type="button" variant="outline" @click="downloadCsv">
                                Download CSV
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button type="button" variant="outline">
                                        Columns
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start" class="w-44">
                                    <DropdownMenuLabel>Toggle Columns</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuCheckboxItem v-for="col in availableColumns" :key="col.key"
                                        :checked="isColumnVisible(col.key)"
                                        :class="isColumnVisible(col.key) ? 'bg-accent font-medium' : 'opacity-50'"
                                        @select.prevent="toggleColumn(col.key)">
                                        {{ col.label }}
                                    </DropdownMenuCheckboxItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <div class="flex gap-2">
                            <Button type="button" variant="outline" @click="clearFilters">
                                Reset
                            </Button>
                            <Button type="submit">
                                Apply Filters
                            </Button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border bg-card print:rounded-none print:border-0">
                <div class="flex flex-col gap-1 border-b px-4 py-3 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between md:px-5 print:px-2 print:py-1 print:text-xs">
                    <p>
                        <template v-if="voters.total !== undefined && voters.total !== null">
                            Showing <span class="font-medium text-foreground">{{ voters.from ?? 0 }}</span>–<span class="font-medium text-foreground">{{ voters.to ?? 0 }}</span>
                            of <span class="font-medium text-foreground">{{ voters.total }}</span> voters
                            &middot;
                        </template>
                        Page <span class="font-medium text-foreground">{{ voters.current_page }}</span>
                        of <span class="font-medium text-foreground">{{ voters.last_page ?? 1 }}</span>
                    </p>
                </div>
                <div class="hidden overflow-x-auto md:block print:block">
                    <table class="min-w-full text-sm print:text-xs">
                        <thead class="bg-muted/40 text-left print:bg-transparent">
                            <tr>
                                <th v-if="isColumnVisible('no')" class="px-4 py-3 font-medium print:px-2 print:py-1">No.
                                </th>
                                <th v-if="isColumnVisible('photo')" class="px-4 py-3 font-medium print:hidden">Photo
                                </th>
                                <th v-if="isColumnVisible('name')" class="px-4 py-3 font-medium print:px-2 print:py-1">
                                    Name / Address</th>
                                <th v-if="isColumnVisible('mobile')"
                                    class="px-4 py-3 font-medium print:px-2 print:py-1">Mobile</th>
                                <th v-if="isColumnVisible('box')" class="px-4 py-3 font-medium print:px-2 print:py-1">Box</th>
                                <th v-if="isColumnVisible('dob')" class="px-4 py-3 font-medium print:px-2 print:py-1">DOB</th>
                                <th v-if="isColumnVisible('age')" class="px-4 py-3 font-medium print:px-2 print:py-1">Age</th>
                                <th v-if="isColumnVisible('agent')" class="px-4 py-3 font-medium print:px-2 print:py-1">Agent</th>
                                <th v-if="isColumnVisible('dhaairaa')" class="px-4 py-3 font-medium print:px-2 print:py-1">Dhaairaa</th>
                                <th v-if="isColumnVisible('majilis_con')" class="px-4 py-3 font-medium print:px-2 print:py-1">Majilis Con</th>
                                <th v-if="isColumnVisible('re_reg_travel')" class="px-4 py-3 font-medium print:px-2 print:py-1">Re-Reg / Travel</th>
                                <th v-if="isColumnVisible('comments')" class="px-4 py-3 font-medium print:px-2 print:py-1">Comments</th>
                                <th v-if="pledgeVisibility.council && isColumnVisible('council')"
                                    class="px-4 py-3 font-medium print:px-2 print:py-1">Council</th>
                                <th v-if="pledgeVisibility.wdc && isColumnVisible('wdc')"
                                    class="px-4 py-3 font-medium print:px-2 print:py-1">WDC</th>
                                <th v-if="pledgeVisibility.mayor && isColumnVisible('mayor')"
                                    class="px-4 py-3 font-medium print:px-2 print:py-1">Mayor</th>
                                <th v-if="pledgeVisibility.raeesa && isColumnVisible('raeesa')"
                                    class="px-4 py-3 font-medium print:px-2 print:py-1">Raeesa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="voter in voters.data" :key="voter.id"
                                class="cursor-pointer border-t transition-colors hover:bg-muted/30"
                                @click="openVoterDetails(voter)">
                                <td v-if="isColumnVisible('no')" class="px-4 py-3 print:px-2 print:py-1">{{
                                    voter.list_number }}</td>
                                <td v-if="isColumnVisible('photo')" class="px-4 py-3 print:hidden">
                                    <img v-if="voter.photo_url" :src="voter.photo_url"
                                        :alt="voter.name ?? 'Voter photo'" class="h-10 w-10 rounded-md object-cover" />
                                    <div v-else class="h-10 w-10 rounded-md bg-muted" />
                                </td>
                                <td v-if="isColumnVisible('name')" class="px-4 py-3 font-medium print:px-2 print:py-1">
                                    <div class="space-y-1">
                                        <p>{{ voter.name ?? '-' }}</p>
                                        <p class="text-xs font-normal text-muted-foreground">
                                            {{ voter.address ?? '-' }}
                                        </p>
                                    </div>
                                </td>
                                <td v-if="isColumnVisible('mobile')" class="px-4 py-3 print:px-2 print:py-1">{{
                                    voter.mobile ?? '-' }}</td>
                                <td v-if="isColumnVisible('box')" class="px-4 py-3 print:px-2 print:py-1">{{ voter.registered_box ?? '-' }}</td>
                                <td v-if="isColumnVisible('dob')" class="px-4 py-3 print:px-2 print:py-1">{{ voter.dob ?? '-' }}</td>
                                <td v-if="isColumnVisible('age')" class="px-4 py-3 print:px-2 print:py-1">{{ voter.age ?? '-' }}</td>
                                <td v-if="isColumnVisible('agent')" class="px-4 py-3 print:px-2 print:py-1">{{ voter.agent ?? '-' }}</td>
                                <td v-if="isColumnVisible('dhaairaa')" class="px-4 py-3 print:px-2 print:py-1">{{ voter.dhaairaa ?? '-' }}</td>
                                <td v-if="isColumnVisible('majilis_con')" class="px-4 py-3 print:px-2 print:py-1">{{ voter.majilis_con ?? '-' }}</td>
                                <td v-if="isColumnVisible('re_reg_travel')" class="px-4 py-3 print:px-2 print:py-1">{{ voter.re_reg_travel ?? '-' }}</td>
                                <td v-if="isColumnVisible('comments')" class="px-4 py-3 max-w-50 truncate print:px-2 print:py-1">{{ voter.comments ?? '-' }}</td>
                                <td v-if="pledgeVisibility.council && isColumnVisible('council')"
                                    class="px-4 py-3 align-top print:px-2 print:py-1">
                                    <Pledge :value="voter.pledge?.council" />
                                </td>
                                <td v-if="pledgeVisibility.wdc && isColumnVisible('wdc')"
                                    class="px-4 py-3 align-top print:px-2 print:py-1">
                                    <Pledge :value="voter.pledge?.wdc" />
                                </td>
                                <td v-if="pledgeVisibility.mayor && isColumnVisible('mayor')"
                                    class="px-4 py-3 align-top print:px-2 print:py-1">
                                    <Pledge :value="voter.pledge?.mayor" />
                                </td>
                                <td v-if="pledgeVisibility.raeesa && isColumnVisible('raeesa')"
                                    class="px-4 py-3 align-top print:px-2 print:py-1">
                                    <Pledge :value="voter.pledge?.raeesa" />
                                </td>


                                <!--
                                <td class="px-4 py-3 align-top">
                                    <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                                        <p>Mayor: {{ voter.pledge?.mayor ?? '-' }}</p>
                                        <p>Raeesa: {{ voter.pledge?.raeesa ?? '-' }}</p>
                                        <p>Council: {{ voter.pledge?.council ?? '-' }}</p>
                                        <p>WDC: {{ voter.pledge?.wdc ?? '-' }}</p>
                                    </div>
                                </td> -->
                                <!-- <td class="px-4 py-3">
                                    <Badge variant="outline">
                                        {{ voter.vote_status ?? 'Unknown' }}
                                    </Badge>
                                </td> -->
                            </tr>
                            <tr v-if="voters.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">
                                    No voters found for the current search and
                                    filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="grid gap-3 p-3 md:hidden print:hidden">
                    <button v-for="voter in voters.data" :key="voter.id" type="button"
                        class="rounded-lg border p-3 text-left" @click="openVoterDetails(voter)">
                        <div class="space-y-3">
                            <div class="flex items-start gap-3">
                                <img v-if="voter.photo_url" :src="voter.photo_url" :alt="voter.name ?? 'Voter photo'"
                                    class="h-14 w-14 rounded-md object-cover" />
                                <div v-else class="h-14 w-14 rounded-md bg-muted" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex justify-between gap-3">
                                        <div class="min-w-0 space-y-1">
                                            <p class="truncate font-medium">{{ voter.name ?? '-' }}</p>
                                            <p class="truncate text-xs text-muted-foreground">{{ voter.address ?? '-' }}
                                            </p>
                                            <p class="truncate text-xs text-muted-foreground">{{ voter.mobile ?? '-' }}
                                            </p>
                                        </div>
                                        <p class="shrink-0 text-xs text-muted-foreground">
                                            {{ voter.registered_box ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 gap-2 text-center">
                                <div v-if="pledgeVisibility.council" class="space-y-1">
                                    <p class="text-[11px] text-muted-foreground">Council</p>
                                    <Pledge :value="voter.pledge?.council" />
                                </div>
                                <div v-if="pledgeVisibility.wdc" class="space-y-1">
                                    <p class="text-[11px] text-muted-foreground">WDC</p>
                                    <Pledge :value="voter.pledge?.wdc" />
                                </div>
                                <div v-if="pledgeVisibility.mayor" class="space-y-1">
                                    <p class="text-[11px] text-muted-foreground">Mayor</p>
                                    <Pledge :value="voter.pledge?.mayor" />
                                </div>
                                <div v-if="pledgeVisibility.raeesa" class="space-y-1">
                                    <p class="text-[11px] text-muted-foreground">Raeesa</p>
                                    <Pledge :value="voter.pledge?.raeesa" />
                                </div>
                            </div>
                        </div>
                    </button>

                    <p v-if="voters.data.length === 0"
                        class="rounded-lg border p-6 text-center text-sm text-muted-foreground">
                        No voters found for the current search and filters.
                    </p>
                </div>

                <div v-if="paginationLinks.length > 0" class="flex flex-wrap items-center justify-center gap-1 border-t p-3 print:hidden sm:justify-end">
                        <template v-for="(link, index) in paginationLinks" :key="index">
                            <span v-if="link.url === null"
                                class="rounded-md border px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                            <Link v-else :href="link.url" preserve-scroll preserve-state :only="['voters', 'filters']"
                                class="rounded-md border px-3 py-1.5 text-sm" :class="link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'hover:bg-muted'
                                " v-html="link.label" />
                        </template>
                </div>
            </div>
            </template>
        </div>
    </AppHeaderLayout>

    <Dialog :open="selectedVoter !== null" @update:open="(isOpen) => { if (!isOpen) { closeVoterDetails(); } }">
        <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-6xl">
            <DialogHeader>
                <DialogTitle>
                    {{ selectedVoter?.name ?? 'Voter Details' }}
                </DialogTitle>
                <DialogDescription>
                    Full profile and voting information
                </DialogDescription>
            </DialogHeader>

            <div v-if="selectedVoter" class="space-y-4">
                <div class="flex items-center justify-end gap-2">
                    <Button v-if="!isEditing" type="button" size="sm" @click="startEditing">
                        Edit
                    </Button>
                    <template v-else>
                        <Button type="button" size="sm" variant="outline" @click="cancelEditing">
                            Cancel
                        </Button>
                        <Button type="button" size="sm" :disabled="editForm.processing" @click="saveVoter">
                            Save
                        </Button>
                    </template>
                </div>
                <div class="grid gap-2 grid-cols-1 md:grid-cols-2">
                    <div>
                        <div class="flex items-center gap-4 rounded-lg border p-3">
                            <img v-if="selectedVoter.photo_url" :src="selectedVoter.photo_url"
                                :alt="selectedVoter.name ?? 'Voter photo'" class="h-20 w-20 rounded-md object-cover" />
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
                    </div>
                    <div>
                        <div class="rounded-lg border p-3">
                            <p class="text-xs text-muted-foreground">Agent</p>
                            <Input v-if="isEditing" v-model="editForm.agent" class="mt-1" />
                            <p v-else class="font-medium">{{ selectedVoter.agent ?? '-' }}</p>
                            <p v-if="isEditing && editForm.errors.agent" class="mt-1 text-xs text-destructive">
                                {{ editForm.errors.agent }}
                            </p>
                        </div>
                    </div>




                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-lg border p-3 sm:col-span-2">
                            <p class="mb-3 text-sm font-semibold">Information</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-lg border p-3">
                                    <p class="text-xs text-muted-foreground">Mobile</p>
                                    <template v-if="isEditing">
                                        <Input v-model="editForm.mobile" class="mt-1" />
                                        <p v-if="editForm.errors.mobile" class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors.mobile }}
                                        </p>
                                    </template>
                                    <div v-else-if="selectedVoterMobileNumbers.length > 0" class="space-y-1">
                                        <p class="text-xs text-muted-foreground">Tap a number to call</p>
                                        <div class="space-y-1">
                                            <a v-for="(mobileNumber, index) in selectedVoterMobileNumbers"
                                                :key="`${mobileNumber.href}-${index}`" :href="mobileNumber.href"
                                                class="block w-fit font-medium underline-offset-2 hover:underline">
                                                {{ mobileNumber.display }}
                                            </a>
                                        </div>
                                    </div>
                                    <p v-else class="font-medium">-</p>
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
                                    <p class="text-xs text-muted-foreground">Registered Box</p>
                                    <Input v-if="isEditing" v-model="editForm.registered_box" class="mt-1" />
                                    <p v-else class="font-medium">{{ selectedVoter.registered_box ?? '-' }}</p>
                                    <p v-if="isEditing && editForm.errors.registered_box"
                                        class="mt-1 text-xs text-destructive">
                                        {{ editForm.errors.registered_box }}
                                    </p>
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
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                        <div class="rounded-lg border p-3 sm:col-span-2">
                            <p class="mb-3 text-sm font-semibold">Pledge</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div v-if="pledgeVisibility.mayor" class="rounded-md border p-3">
                                    <p class="text-xs text-muted-foreground">Mayor</p>
                                    <template v-if="isEditing && pledgeVisibility.mayor">
                                        <select v-model="editForm.pledge.mayor"
                                            class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Select option</option>
                                            <option v-for="option in pledgeOptions" :key="`mayor-${option}`"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        <p v-if="editForm.errors['pledge.mayor']" class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors['pledge.mayor'] }}
                                        </p>
                                    </template>
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.pledge?.mayor ?? '-' }}
                                    </p>
                                </div>
                                <div v-if="pledgeVisibility.raeesa" class="rounded-md border p-3">
                                    <p class="text-xs text-muted-foreground">Raeesa</p>
                                    <template v-if="isEditing && pledgeVisibility.raeesa">
                                        <select v-model="editForm.pledge.raeesa"
                                            class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Select option</option>
                                            <option v-for="option in pledgeOptions" :key="`raeesa-${option}`"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        <p v-if="editForm.errors['pledge.raeesa']"
                                            class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors['pledge.raeesa'] }}
                                        </p>
                                    </template>
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.pledge?.raeesa ?? '-' }}
                                    </p>
                                </div>
                                <div v-if="pledgeVisibility.council" class="rounded-md border p-3">
                                    <p class="text-xs text-muted-foreground">Council</p>
                                    <template v-if="isEditing && pledgeVisibility.council">
                                        <select v-model="editForm.pledge.council"
                                            class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Select option</option>
                                            <option v-for="option in pledgeOptions" :key="`council-${option}`"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        <p v-if="editForm.errors['pledge.council']"
                                            class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors['pledge.council'] }}
                                        </p>
                                    </template>
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.pledge?.council ?? '-' }}
                                    </p>
                                </div>
                                <div v-if="pledgeVisibility.wdc" class="rounded-md border p-3">
                                    <p class="text-xs text-muted-foreground">WDC</p>
                                    <template v-if="isEditing && pledgeVisibility.wdc">
                                        <select v-model="editForm.pledge.wdc"
                                            class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Select option</option>
                                            <option v-for="option in pledgeOptions" :key="`wdc-${option}`"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        <p v-if="editForm.errors['pledge.wdc']" class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors['pledge.wdc'] }}
                                        </p>
                                    </template>
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.pledge?.wdc ?? '-' }}
                                    </p>
                                </div>
                                <div class="rounded-lg border p-3 sm:col-span-2">
                                    <p class="text-xs text-muted-foreground">Re-Reg / Travel</p>
                                    <Input v-if="isEditing" v-model="editForm.re_reg_travel" class="mt-1" />
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.re_reg_travel ?? '-' }}
                                    </p>
                                    <p v-if="isEditing && editForm.errors.re_reg_travel"
                                        class="mt-1 text-xs text-destructive">
                                        {{ editForm.errors.re_reg_travel }}
                                    </p>
                                </div>
                                <div class="rounded-lg border p-3 sm:col-span-2">
                                    <p class="text-xs text-muted-foreground">Comments</p>
                                    <textarea v-if="isEditing" v-model="editForm.comments" rows="3"
                                        class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                                    <p v-else class="font-medium whitespace-pre-line">
                                        {{ selectedVoter.comments ?? '-' }}
                                    </p>
                                    <p v-if="isEditing && editForm.errors.comments"
                                        class="mt-1 text-xs text-destructive">
                                        {{ editForm.errors.comments }}
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!--
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

                        <div class="rounded-lg border p-3 sm:col-span-2">
                            <p class="mb-3 text-sm font-semibold">Pledge</p>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div class="rounded-md border p-3">
                                    <p class="text-xs text-muted-foreground">Mayor</p>
                                    <template v-if="isEditing">
                                        <select v-model="editForm.pledge.mayor"
                                            class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Select option</option>
                                            <option v-for="option in pledgeOptions" :key="`mayor-${option}`"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        <p v-if="editForm.errors['pledge.mayor']" class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors['pledge.mayor'] }}
                                        </p>
                                    </template>
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.pledge?.mayor ?? '-' }}
                                    </p>
                                </div>
                                <div class="rounded-md border p-3">
                                    <p class="text-xs text-muted-foreground">Raeesa</p>
                                    <template v-if="isEditing">
                                        <select v-model="editForm.pledge.raeesa"
                                            class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Select option</option>
                                            <option v-for="option in pledgeOptions" :key="`raeesa-${option}`"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        <p v-if="editForm.errors['pledge.raeesa']"
                                            class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors['pledge.raeesa'] }}
                                        </p>
                                    </template>
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.pledge?.raeesa ?? '-' }}
                                    </p>
                                </div>
                                <div class="rounded-md border p-3">
                                    <p class="text-xs text-muted-foreground">Council</p>
                                    <template v-if="isEditing">
                                        <select v-model="editForm.pledge.council"
                                            class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Select option</option>
                                            <option v-for="option in pledgeOptions" :key="`council-${option}`"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        <p v-if="editForm.errors['pledge.council']"
                                            class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors['pledge.council'] }}
                                        </p>
                                    </template>
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.pledge?.council ?? '-' }}
                                    </p>
                                </div>
                                <div class="rounded-md border p-3">
                                    <p class="text-xs text-muted-foreground">WDC</p>
                                    <template v-if="isEditing">
                                        <select v-model="editForm.pledge.wdc"
                                            class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            <option value="">Select option</option>
                                            <option v-for="option in pledgeOptions" :key="`wdc-${option}`"
                                                :value="option">
                                                {{ option }}
                                            </option>
                                        </select>
                                        <p v-if="editForm.errors['pledge.wdc']" class="mt-1 text-xs text-destructive">
                                            {{ editForm.errors['pledge.wdc'] }}
                                        </p>
                                    </template>
                                    <p v-else class="font-medium">
                                        {{ selectedVoter.pledge?.wdc ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border p-3 sm:col-span-2">
                            <p class="text-xs text-muted-foreground">Re-Reg / Travel</p>
                            <Input v-if="isEditing" v-model="editForm.re_reg_travel" class="mt-1" />
                            <p v-else class="font-medium">
                                {{ selectedVoter.re_reg_travel ?? '-' }}
                            </p>
                            <p v-if="isEditing && editForm.errors.re_reg_travel" class="mt-1 text-xs text-destructive">
                                {{ editForm.errors.re_reg_travel }}
                            </p>
                        </div>
                        <div class="rounded-lg border p-3 sm:col-span-2">
                            <p class="text-xs text-muted-foreground">Comments</p>
                            <textarea v-if="isEditing" v-model="editForm.comments" rows="3"
                                class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                            <p v-else class="font-medium whitespace-pre-line">
                                {{ selectedVoter.comments ?? '-' }}
                            </p>
                            <p v-if="isEditing && editForm.errors.comments" class="mt-1 text-xs text-destructive">
                                {{ editForm.errors.comments }}
                            </p>
                        </div>
                    </div> -->
                </div>

                <!-- <div class="flex items-center gap-4 rounded-lg border p-3">
                    <img v-if="selectedVoter.photo_url" :src="selectedVoter.photo_url"
                        :alt="selectedVoter.name ?? 'Voter photo'" class="h-20 w-20 rounded-md object-cover" />
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
                        <template v-if="isEditing">
                            <Input v-model="editForm.mobile" class="mt-1" />
                            <p v-if="editForm.errors.mobile" class="mt-1 text-xs text-destructive">
                                {{ editForm.errors.mobile }}
                            </p>
                        </template>
                        <p v-else class="font-medium">{{ selectedVoter.mobile ?? '-' }}</p>
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
                        <p class="text-xs text-muted-foreground">Registered Box</p>
                        <p class="font-medium">{{ selectedVoter.registered_box ?? '-' }}</p>
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
                    <div class="rounded-lg border p-3 sm:col-span-2">
                        <p class="mb-3 text-sm font-semibold">Pledge</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-md border p-3">
                                <p class="text-xs text-muted-foreground">Mayor</p>
                                <template v-if="isEditing">
                                    <select v-model="editForm.pledge.mayor"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="">Select option</option>
                                        <option v-for="option in pledgeOptions" :key="`mayor-${option}`"
                                            :value="option">
                                            {{ option }}
                                        </option>
                                    </select>
                                    <p v-if="editForm.errors['pledge.mayor']" class="mt-1 text-xs text-destructive">
                                        {{ editForm.errors['pledge.mayor'] }}
                                    </p>
                                </template>
                                <p v-else class="font-medium">
                                    {{ selectedVoter.pledge?.mayor ?? '-' }}
                                </p>
                            </div>
                            <div class="rounded-md border p-3">
                                <p class="text-xs text-muted-foreground">Raeesa</p>
                                <template v-if="isEditing">
                                    <select v-model="editForm.pledge.raeesa"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="">Select option</option>
                                        <option v-for="option in pledgeOptions" :key="`raeesa-${option}`"
                                            :value="option">
                                            {{ option }}
                                        </option>
                                    </select>
                                    <p v-if="editForm.errors['pledge.raeesa']" class="mt-1 text-xs text-destructive">
                                        {{ editForm.errors['pledge.raeesa'] }}
                                    </p>
                                </template>
                                <p v-else class="font-medium">
                                    {{ selectedVoter.pledge?.raeesa ?? '-' }}
                                </p>
                            </div>
                            <div class="rounded-md border p-3">
                                <p class="text-xs text-muted-foreground">Council</p>
                                <template v-if="isEditing">
                                    <select v-model="editForm.pledge.council"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="">Select option</option>
                                        <option v-for="option in pledgeOptions" :key="`council-${option}`"
                                            :value="option">
                                            {{ option }}
                                        </option>
                                    </select>
                                    <p v-if="editForm.errors['pledge.council']" class="mt-1 text-xs text-destructive">
                                        {{ editForm.errors['pledge.council'] }}
                                    </p>
                                </template>
                                <p v-else class="font-medium">
                                    {{ selectedVoter.pledge?.council ?? '-' }}
                                </p>
                            </div>
                            <div class="rounded-md border p-3">
                                <p class="text-xs text-muted-foreground">WDC</p>
                                <template v-if="isEditing">
                                    <select v-model="editForm.pledge.wdc"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="">Select option</option>
                                        <option v-for="option in pledgeOptions" :key="`wdc-${option}`" :value="option">
                                            {{ option }}
                                        </option>
                                    </select>
                                    <p v-if="editForm.errors['pledge.wdc']" class="mt-1 text-xs text-destructive">
                                        {{ editForm.errors['pledge.wdc'] }}
                                    </p>
                                </template>
                                <p v-else class="font-medium">
                                    {{ selectedVoter.pledge?.wdc ?? '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border p-3 sm:col-span-2">
                        <p class="text-xs text-muted-foreground">Re-Reg / Travel</p>
                        <Input v-if="isEditing" v-model="editForm.re_reg_travel" class="mt-1" />
                        <p v-else class="font-medium">
                            {{ selectedVoter.re_reg_travel ?? '-' }}
                        </p>
                        <p v-if="isEditing && editForm.errors.re_reg_travel" class="mt-1 text-xs text-destructive">
                            {{ editForm.errors.re_reg_travel }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-3 sm:col-span-2">
                        <p class="text-xs text-muted-foreground">Comments</p>
                        <textarea v-if="isEditing" v-model="editForm.comments" rows="3"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                        <p v-else class="font-medium whitespace-pre-line">
                            {{ selectedVoter.comments ?? '-' }}
                        </p>
                        <p v-if="isEditing && editForm.errors.comments" class="mt-1 text-xs text-destructive">
                            {{ editForm.errors.comments }}
                        </p>
                    </div>
                </div> -->

                <!-- <DialogFooter v-if="isEditing">
                    <Button type="button" variant="outline" @click="cancelEditing">
                        Cancel
                    </Button>
                    <Button type="button" :disabled="editForm.processing" @click="saveVoter">
                        Save Changes
                    </Button>
                </DialogFooter> -->
            </div>
        </DialogContent>
    </Dialog>
</template>
