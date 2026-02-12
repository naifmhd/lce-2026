<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, onMounted, reactive, ref, watch } from 'vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { updateTheme } from '@/composables/useAppearance';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { index as votersIndex, update as votersUpdate } from '@/routes/voters';
import { type BreadcrumbItem } from '@/types';

type VoterListItem = {
    id: number;
    list_number: number | null;
    id_card_number: string | null;
    name: string | null;
    sex: string | null;
    mobile: string | null;
    dob: string | null;
    age: number | null;
    island: string | null;
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
    island: string | null;
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
};

type Props = {
    voters: PaginatedVoters;
    filters: {
        search: string;
        dhaairaa: string;
        majilis_con: string;
    };
    filterOptions: {
        dhaairaa: string[];
        majilis_con: string[];
    };
    selectedVoter: VoterDetail | null;
    pledgeOptions: string[];
};

const props = defineProps<Props>();
const pledgeOptions = computed(() => props.pledgeOptions);

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

const selectedVoterState = ref<VoterDetail | null>(props.selectedVoter);
const selectedVoter = computed(() => selectedVoterState.value);
const isEditing = ref(false);
const shouldAutoSearch = ref(true);

const editForm = useForm({
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
});

const buildQuery = (overrides: Partial<Record<string, string | number | null>> = {}) => {
    const baseQuery: Record<string, string | number | null> = {
        search: filterForm.search.trim() === '' ? null : filterForm.search.trim(),
        dhaairaa: filterForm.dhaairaa === '' ? null : filterForm.dhaairaa,
        majilis_con: filterForm.majilis_con === '' ? null : filterForm.majilis_con,
        page: props.voters.current_page > 1 ? props.voters.current_page : null,
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

const clearFilters = (): void => {
    shouldAutoSearch.value = false;
    filterForm.search = '';
    filterForm.dhaairaa = '';
    filterForm.majilis_con = '';
    applyFilters();
};

const openVoterDetails = (voter: VoterListItem): void => {
    selectedVoterState.value = {
        ...voter,
        sex: voter.sex,
        dob: voter.dob,
        age: voter.age,
        island: voter.island,
        re_reg_travel: voter.re_reg_travel,
        comments: voter.comments,
    };
};

const closeVoterDetails = (): void => {
    isEditing.value = false;
    editForm.clearErrors();
    selectedVoterState.value = null;
};

const syncEditForm = (voter: VoterDetail | null): void => {
    editForm.mobile = voter?.mobile ?? '';
    editForm.re_reg_travel = voter?.re_reg_travel ?? '';
    editForm.comments = voter?.comments ?? '';
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
                        mobile: editForm.mobile,
                        re_reg_travel: editForm.re_reg_travel,
                        comments: editForm.comments,
                        pledge: {
                            mayor: editForm.pledge.mayor === '' ? null : editForm.pledge.mayor,
                            raeesa: editForm.pledge.raeesa === '' ? null : editForm.pledge.raeesa,
                            council: editForm.pledge.council === '' ? null : editForm.pledge.council,
                            wdc: editForm.pledge.wdc === '' ? null : editForm.pledge.wdc,
                        },
                    };
                }

                isEditing.value = false;
            },
        },
    );
};

const debouncedSearch = useDebounceFn((): void => {
    if (! shouldAutoSearch.value) {
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
                    <p v-if="voters.total !== undefined && voters.total !== null">
                        Total {{ voters.total }} voters
                    </p>
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">No.</th>
                                <th class="px-4 py-3 font-medium">Photo</th>
                                <th class="px-4 py-3 font-medium">Name / ID Card</th>
                                <th class="px-4 py-3 font-medium">Mobile</th>
                                <th class="px-4 py-3 font-medium">Address</th>
                                <th class="px-4 py-3 font-medium">Pledge</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="voter in voters.data"
                                :key="voter.id"
                                class="cursor-pointer border-t transition-colors hover:bg-muted/30"
                                @click="openVoterDetails(voter)"
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
                                    <div class="space-y-1">
                                        <p>{{ voter.name ?? '-' }}</p>
                                        <p class="text-xs font-normal text-muted-foreground">
                                            {{ voter.id_card_number ?? '-' }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ voter.mobile ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    {{ voter.address ?? '-' }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div
                                        class="grid grid-cols-2 gap-x-3 gap-y-1 text-xs"
                                    >
                                        <p>Mayor: {{ voter.pledge?.mayor ?? '-' }}</p>
                                        <p>Raeesa: {{ voter.pledge?.raeesa ?? '-' }}</p>
                                        <p>Council: {{ voter.pledge?.council ?? '-' }}</p>
                                        <p>WDC: {{ voter.pledge?.wdc ?? '-' }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge variant="outline">
                                        {{ voter.vote_status ?? 'Unknown' }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="voters.data.length === 0">
                                <td
                                    colspan="8"
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
                        @click="openVoterDetails(voter)"
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
                            <div
                                class="grid grid-cols-2 gap-x-3 gap-y-1 text-[11px] text-muted-foreground"
                            >
                                <p>Mayor: {{ voter.pledge?.mayor ?? '-' }}</p>
                                <p>Raeesa: {{ voter.pledge?.raeesa ?? '-' }}</p>
                                <p>Council: {{ voter.pledge?.council ?? '-' }}</p>
                                <p>WDC: {{ voter.pledge?.wdc ?? '-' }}</p>
                            </div>
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
                    v-if="(voters.links ?? []).length > 0"
                    class="flex flex-wrap items-center justify-center gap-2 border-t p-3 md:justify-end"
                >
                    <template v-for="(link, index) in (voters.links ?? [])" :key="index">
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
                            :only="['voters', 'filters']"
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
                <div class="flex items-center justify-end gap-2">
                    <Button
                        v-if="!isEditing"
                        type="button"
                        size="sm"
                        @click="startEditing"
                    >
                        Edit
                    </Button>
                    <template v-else>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="cancelEditing"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            :disabled="editForm.processing"
                            @click="saveVoter"
                        >
                            Save
                        </Button>
                    </template>
                </div>

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
                        <template v-if="isEditing">
                            <Input v-model="editForm.mobile" class="mt-1" />
                            <p
                                v-if="editForm.errors.mobile"
                                class="mt-1 text-xs text-destructive"
                            >
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
                    <div class="rounded-lg border p-3 sm:col-span-2">
                        <p class="mb-3 text-sm font-semibold">Pledge</p>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-md border p-3">
                                <p class="text-xs text-muted-foreground">Mayor</p>
                                <template v-if="isEditing">
                                    <select
                                        v-model="editForm.pledge.mayor"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">Select option</option>
                                        <option
                                            v-for="option in pledgeOptions"
                                            :key="`mayor-${option}`"
                                            :value="option"
                                        >
                                            {{ option }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="editForm.errors['pledge.mayor']"
                                        class="mt-1 text-xs text-destructive"
                                    >
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
                                    <select
                                        v-model="editForm.pledge.raeesa"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">Select option</option>
                                        <option
                                            v-for="option in pledgeOptions"
                                            :key="`raeesa-${option}`"
                                            :value="option"
                                        >
                                            {{ option }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="editForm.errors['pledge.raeesa']"
                                        class="mt-1 text-xs text-destructive"
                                    >
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
                                    <select
                                        v-model="editForm.pledge.council"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">Select option</option>
                                        <option
                                            v-for="option in pledgeOptions"
                                            :key="`council-${option}`"
                                            :value="option"
                                        >
                                            {{ option }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="editForm.errors['pledge.council']"
                                        class="mt-1 text-xs text-destructive"
                                    >
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
                                    <select
                                        v-model="editForm.pledge.wdc"
                                        class="mt-1 h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                                    >
                                        <option value="">Select option</option>
                                        <option
                                            v-for="option in pledgeOptions"
                                            :key="`wdc-${option}`"
                                            :value="option"
                                        >
                                            {{ option }}
                                        </option>
                                    </select>
                                    <p
                                        v-if="editForm.errors['pledge.wdc']"
                                        class="mt-1 text-xs text-destructive"
                                    >
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
                        <Input
                            v-if="isEditing"
                            v-model="editForm.re_reg_travel"
                            class="mt-1"
                        />
                        <p v-else class="font-medium">
                            {{ selectedVoter.re_reg_travel ?? '-' }}
                        </p>
                        <p
                            v-if="isEditing && editForm.errors.re_reg_travel"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ editForm.errors.re_reg_travel }}
                        </p>
                    </div>
                    <div class="rounded-lg border p-3 sm:col-span-2">
                        <p class="text-xs text-muted-foreground">Comments</p>
                        <textarea
                            v-if="isEditing"
                            v-model="editForm.comments"
                            rows="3"
                            class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p v-else class="font-medium">
                            {{ selectedVoter.comments ?? '-' }}
                        </p>
                        <p
                            v-if="isEditing && editForm.errors.comments"
                            class="mt-1 text-xs text-destructive"
                        >
                            {{ editForm.errors.comments }}
                        </p>
                    </div>
                </div>

                <DialogFooter v-if="isEditing">
                    <Button
                        type="button"
                        variant="outline"
                        @click="cancelEditing"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="editForm.processing"
                        @click="saveVoter"
                    >
                        Save Changes
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
