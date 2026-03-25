<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { CheckCircle2 } from 'lucide-vue-next';
import { onMounted, reactive, ref, watch } from 'vue';
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
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { index as zerodayIndex, markVoted as zerodayMarkVoted } from '@/routes/zeroday';
import { type BreadcrumbItem } from '@/types';

type VoterItem = {
    id: number;
    list_number: number | null;
    id_card_number: string | null;
    name: string | null;
    address: string | null;
    mobile: string | null;
    registered_box: string | null;
    vote_status: string | null;
    photo_url: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedVoters = {
    data: VoterItem[];
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
    voters: PaginatedVoters | null;
    filters: {
        search: string;
        per_page: string;
        include_voted: boolean;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Zeroday',
        href: zerodayIndex().url,
    },
];

const includeVotedStorageKey = 'zeroday:include-voted';

const filterForm = reactive({
    search: props.filters.search ?? '',
    per_page: props.filters.per_page ?? '15',
    include_voted: props.filters.include_voted ?? false,
});

const confirmVoter = ref<VoterItem | null>(null);
const votedForm = useForm({});

onMounted(() => {
    const saved = localStorage.getItem(includeVotedStorageKey);
    if (saved !== null) {
        const savedValue = saved === 'true';
        if (savedValue !== filterForm.include_voted) {
            filterForm.include_voted = savedValue;
            if (filterForm.search.trim() !== '') {
                applyFilters();
            }
        }
    }
});

watch(
    () => filterForm.include_voted,
    (value) => {
        localStorage.setItem(includeVotedStorageKey, String(value));
    },
);

const buildQuery = (overrides: Partial<Record<string, string | number | boolean | null>> = {}) => {
    const base: Record<string, string | number | boolean | null> = {
        search: filterForm.search.trim() === '' ? null : filterForm.search.trim(),
        per_page: filterForm.per_page === '15' ? null : filterForm.per_page,
        include_voted: filterForm.include_voted ? '1' : null,
    };

    const merged = { ...base, ...overrides };

    return Object.fromEntries(
        Object.entries(merged).filter(([, value]) => value !== null && value !== ''),
    );
};

const applyFilters = (): void => {
    router.get(
        zerodayIndex.url({ query: buildQuery({ page: null }) }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['voters', 'filters'],
        },
    );
};

const debouncedSearch = useDebounceFn(applyFilters, 400);

const openConfirm = (voter: VoterItem): void => {
    confirmVoter.value = voter;
};

const closeConfirm = (): void => {
    confirmVoter.value = null;
};

const submitMarkVoted = (): void => {
    if (confirmVoter.value === null) {
        return;
    }

    votedForm.patch(
        zerodayMarkVoted.url(confirmVoter.value.id, {
            query: buildQuery(),
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmVoter.value = null;
                filterForm.search = '';
            },
        },
    );
};
</script>

<template>

    <Head title="Zeroday" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">

            <div class="rounded-xl border bg-card p-4 md:p-5">
                <form class="flex flex-col gap-4 sm:flex-row sm:items-end" @submit.prevent="applyFilters">
                    <div class="flex-1 space-y-2">
                        <Label for="search">Search Voters</Label>
                        <Input
                            id="search"
                            v-model="filterForm.search"
                            placeholder="Search by ID card, name, address, or mobile"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="flex items-center gap-2 pb-1">
                        <input
                            id="include-voted"
                            v-model="filterForm.include_voted"
                            type="checkbox"
                            class="h-4 w-4 cursor-pointer rounded-sm border border-input accent-primary"
                            @change="applyFilters"
                        />
                        <Label for="include-voted" class="cursor-pointer font-normal">Include voted</Label>
                    </div>
                    <Button type="button" variant="outline" @click="filterForm.search = ''; applyFilters();">Reset</Button>
                    <Button type="submit">Search</Button>
                </form>
            </div>

            <div v-if="voters === null" class="rounded-xl border bg-card px-4 py-16 text-center text-muted-foreground">
                Enter a search term to find voters.
            </div>

            <template v-else>
                <div class="rounded-xl border bg-card">
                    <div class="flex items-center justify-between border-b px-4 py-3 text-sm text-muted-foreground md:px-5">
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

                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/40 text-left">
                                <tr>
                                    <th class="px-4 py-3 font-medium">No.</th>
                                    <th class="px-4 py-3 font-medium">Photo</th>
                                    <th class="px-4 py-3 font-medium">Name / Address</th>
                                    <th class="px-4 py-3 font-medium">ID Card</th>
                                    <th class="px-4 py-3 font-medium">Box</th>
                                    <th class="px-4 py-3 font-medium">Mobile</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="voter in voters.data"
                                    :key="voter.id"
                                    class="border-t transition-colors hover:bg-muted/30"
                                >
                                    <td class="px-4 py-3 text-muted-foreground">{{ voter.list_number ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <img
                                            v-if="voter.photo_url"
                                            :src="voter.photo_url"
                                            :alt="voter.name ?? 'Voter photo'"
                                            class="h-10 w-10 rounded-md object-cover"
                                        />
                                        <div v-else class="h-10 w-10 rounded-md bg-muted" />
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="space-y-1">
                                            <p class="font-medium">{{ voter.name ?? '-' }}</p>
                                            <p class="text-xs text-muted-foreground">{{ voter.address ?? '-' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ voter.id_card_number ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ voter.registered_box ?? '-' }}</td>
                                    <td class="px-4 py-3">{{ voter.mobile ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <Badge
                                            variant="outline"
                                            :class="voter.vote_status === 'voted' ? 'border-green-300 bg-green-100 text-green-700' : ''"
                                        >
                                            {{ voter.vote_status ?? 'Not Voted' }}
                                        </Badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Button
                                            v-if="voter.vote_status !== 'voted'"
                                            size="icon"
                                            variant="outline"
                                            class="h-8 w-8"
                                            title="Mark as voted"
                                            @click="openConfirm(voter)"
                                        >
                                            <CheckCircle2 class="h-4 w-4" />
                                        </Button>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                </tr>
                                <tr v-if="voters.data.length === 0">
                                    <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">
                                        No voters found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="grid gap-3 p-3 md:hidden">
                        <div
                            v-for="voter in voters.data"
                            :key="voter.id"
                            class="rounded-lg border p-3"
                        >
                            <div class="flex items-start gap-3">
                                <img
                                    v-if="voter.photo_url"
                                    :src="voter.photo_url"
                                    :alt="voter.name ?? 'Voter photo'"
                                    class="h-12 w-12 rounded-md object-cover"
                                />
                                <div v-else class="h-12 w-12 shrink-0 rounded-md bg-muted" />
                                <div class="min-w-0 flex-1 space-y-1">
                                    <p class="font-medium">{{ voter.name ?? '-' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ voter.address ?? '-' }}</p>
                                    <p class="text-xs text-muted-foreground">ID: {{ voter.id_card_number ?? '-' }}</p>
                                    <p class="text-xs text-muted-foreground">Box: {{ voter.registered_box ?? '-' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ voter.mobile ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center justify-between">
                                <Badge
                                    variant="outline"
                                    :class="voter.vote_status === 'voted' ? 'border-green-300 bg-green-100 text-green-700' : ''"
                                >
                                    {{ voter.vote_status ?? 'Not Voted' }}
                                </Badge>
                                <Button
                                    v-if="voter.vote_status !== 'voted'"
                                    size="icon"
                                    variant="outline"
                                    class="h-8 w-8"
                                    title="Mark as voted"
                                    @click="openConfirm(voter)"
                                >
                                    <CheckCircle2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                        <div v-if="voters.data.length === 0" class="py-8 text-center text-muted-foreground text-sm">
                            No voters found.
                        </div>
                    </div>

                    <div
                        v-if="(voters.links ?? []).length > 3"
                        class="flex items-center justify-center gap-1 border-t px-4 py-3"
                    >
                        <template v-for="link in voters.links" :key="link.label">
                            <a
                                v-if="link.url"
                                :href="link.url"
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm transition-colors hover:bg-muted"
                                :class="link.active ? 'bg-muted font-medium' : ''"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm text-muted-foreground opacity-50"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </template>

        </div>
    </AppHeaderLayout>

    <Dialog :open="confirmVoter !== null" @update:open="(open) => { if (!open) closeConfirm(); }">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Confirm Voted Status</DialogTitle>
                <DialogDescription>
                    Mark this voter as voted? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <div v-if="confirmVoter" class="flex items-center gap-3 rounded-lg border bg-muted/30 p-3">
                <img
                    v-if="confirmVoter.photo_url"
                    :src="confirmVoter.photo_url"
                    :alt="confirmVoter.name ?? 'Voter photo'"
                    class="h-14 w-14 rounded-md object-cover"
                />
                <div v-else class="h-14 w-14 shrink-0 rounded-md bg-muted" />
                <div class="space-y-1">
                    <p class="font-medium">{{ confirmVoter.name ?? '-' }}</p>
                    <p class="text-sm text-muted-foreground">{{ confirmVoter.id_card_number ?? '-' }}</p>
                    <p class="text-sm text-muted-foreground">Box: {{ confirmVoter.registered_box ?? '-' }}</p>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="closeConfirm">Cancel</Button>
                <Button :disabled="votedForm.processing" @click="submitMarkVoted">
                    Confirm Voted
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</template>
