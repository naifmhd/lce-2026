<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Pencil } from 'lucide-vue-next';
import { reactive, ref } from 'vue';
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
import { index as callCenterIndex, updateRemark as callCenterUpdateRemark } from '@/routes/call-center';
import { type BreadcrumbItem } from '@/types';

type VoterItem = {
    id: number;
    list_number: number | null;
    id_card_number: string | null;
    name: string | null;
    address: string | null;
    mobile: string | null;
    registered_box: string | null;
    agent: string | null;
    vote_status: string | null;
    cc_remarks: string | null;
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
    voters: PaginatedVoters;
    agents: string[];
    filters: {
        search: string;
        cc_filter: string;
        agent_filter: string;
        include_voted: boolean;
        per_page: string;
    };
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Call Center',
        href: callCenterIndex().url,
    },
];

const filterForm = reactive({
    search: props.filters.search ?? '',
    cc_filter: props.filters.cc_filter ?? '',
    agent_filter: props.filters.agent_filter ?? '',
    include_voted: props.filters.include_voted ?? false,
    per_page: props.filters.per_page ?? '15',
});

const remarkVoter = ref<VoterItem | null>(null);
const remarkForm = useForm({ cc_remarks: '' });


const debouncedSearch = useDebounceFn(() => applyFilters(), 400);

const applyFilters = (overrides: Partial<typeof filterForm> = {}): void => {
    const merged = { ...filterForm, ...overrides };
    const query: Record<string, string | null> = {
        search: merged.search.trim() || null,
        cc_filter: merged.cc_filter || null,
        agent_filter: merged.agent_filter.trim() || null,
        include_voted: merged.include_voted ? '1' : null,
        per_page: merged.per_page === '15' ? null : merged.per_page,
    };

    router.get(
        callCenterIndex.url({
            query: Object.fromEntries(Object.entries(query).filter(([, v]) => v !== null)),
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            only: ['voters', 'filters'],
        },
    );
};

const setCcFilter = (value: string): void => {
    filterForm.cc_filter = value;
    applyFilters({ page: null } as Partial<typeof filterForm>);
};


const openRemark = (voter: VoterItem): void => {
    remarkVoter.value = voter;
    remarkForm.cc_remarks = voter.cc_remarks ?? '';
};

const closeRemark = (): void => {
    remarkVoter.value = null;
    remarkForm.reset();
};

const submitRemark = (): void => {
    if (remarkVoter.value === null) {
        return;
    }

    remarkForm.patch(
        callCenterUpdateRemark.url(remarkVoter.value.id, {
            query: Object.fromEntries(
                Object.entries({
                    search: filterForm.search.trim() || null,
                    cc_filter: filterForm.cc_filter || null,
                    agent_filter: filterForm.agent_filter.trim() || null,
                    include_voted: filterForm.include_voted ? '1' : null,
                    per_page: filterForm.per_page === '15' ? null : filterForm.per_page,
                }).filter(([, v]) => v !== null),
            ),
        }),
        {
            preserveScroll: true,
            onSuccess: () => closeRemark(),
        },
    );
};
</script>

<template>

    <Head title="Call Center" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">

            <!-- Filter bar -->
            <div class="rounded-xl border bg-card p-4 md:p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="flex-1 space-y-2">
                        <Label for="search">Search Voters</Label>
                        <Input
                            id="search"
                            v-model="filterForm.search"
                            placeholder="Search by ID card, name, address, or mobile"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="agent-filter">Agent</Label>
                        <select
                            id="agent-filter"
                            v-model="filterForm.agent_filter"
                            class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            @change="applyFilters()"
                        >
                            <option value="">All Agents</option>
                            <option v-for="agent in agents" :key="agent" :value="agent">{{ agent }}</option>
                        </select>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 pb-0.5">
                        <span class="text-sm font-medium text-muted-foreground">CC Remarks:</span>
                        <Button
                            size="sm"
                            :variant="filterForm.cc_filter === '' ? 'default' : 'outline'"
                            @click="setCcFilter('')"
                        >
                            All
                        </Button>
                        <Button
                            size="sm"
                            :variant="filterForm.cc_filter === 'filled' ? 'default' : 'outline'"
                            @click="setCcFilter('filled')"
                        >
                            Filled
                        </Button>
                        <Button
                            size="sm"
                            :variant="filterForm.cc_filter === 'blank' ? 'default' : 'outline'"
                            @click="setCcFilter('blank')"
                        >
                            Blank
                        </Button>
                    </div>
                    <div class="flex items-center gap-2 pb-1">
                        <input
                            id="include-voted"
                            v-model="filterForm.include_voted"
                            type="checkbox"
                            class="h-4 w-4 cursor-pointer rounded-sm border border-input accent-primary"
                            @change="applyFilters()"
                        />
                        <Label for="include-voted" class="cursor-pointer font-normal">Include voted</Label>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        class="pb-0.5"
                        @click="filterForm.search = ''; filterForm.cc_filter = ''; filterForm.agent_filter = ''; filterForm.include_voted = false; applyFilters();"
                    >
                        Reset
                    </Button>
                </div>
            </div>

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

                <!-- Desktop table -->
                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">No.</th>
                                <th class="px-4 py-3 font-medium">Photo</th>
                                <th class="px-4 py-3 font-medium">Name / Address</th>
                                <!-- <th class="px-4 py-3 font-medium">ID Card</th> -->
                                <th class="px-4 py-3 font-medium">Box</th>
                                <th class="px-4 py-3 font-medium">Mobile</th>
                                <th class="px-4 py-3 font-medium">Agent</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">CC Remarks</th>
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
                                <!-- <td class="px-4 py-3 font-mono text-xs">{{ voter.id_card_number ?? '-' }}</td> -->
                                <td class="px-4 py-3">{{ voter.registered_box ?? '-' }}</td>
                                <td class="px-4 py-3">{{ voter.mobile ?? '-' }}</td>
                                <td class="px-4 py-3">{{ voter.agent ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <Badge
                                        variant="outline"
                                        :class="voter.vote_status === 'voted' ? 'border-green-300 bg-green-100 text-green-700' : ''"
                                    >
                                        {{ voter.vote_status ?? 'Not Voted' }}
                                    </Badge>
                                </td>
                                <td class="max-w-48 px-4 py-3">
                                    <span v-if="voter.cc_remarks" class="line-clamp-2 text-xs text-muted-foreground">{{ voter.cc_remarks }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Button
                                        size="icon"
                                        variant="outline"
                                        class="h-8 w-8"
                                        title="Add / edit CC remarks"
                                        @click="openRemark(voter)"
                                    >
                                        <Pencil class="h-4 w-4" />
                                    </Button>
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

                <!-- Mobile cards -->
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
                                <p v-if="voter.agent" class="text-xs text-muted-foreground">Agent: {{ voter.agent }}</p>
                            </div>
                        </div>
                        <div v-if="voter.cc_remarks" class="mt-2 rounded-md bg-muted/30 px-3 py-2 text-xs text-muted-foreground">
                            {{ voter.cc_remarks }}
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <Badge
                                variant="outline"
                                :class="voter.vote_status === 'voted' ? 'border-green-300 bg-green-100 text-green-700' : ''"
                            >
                                {{ voter.vote_status ?? 'Not Voted' }}
                            </Badge>
                            <Button
                                size="icon"
                                variant="outline"
                                class="h-8 w-8"
                                title="Add / edit CC remarks"
                                @click="openRemark(voter)"
                            >
                                <Pencil class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <div v-if="voters.data.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        No voters found.
                    </div>
                </div>

                <!-- Pagination -->
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

        </div>
    </AppHeaderLayout>

    <!-- Remarks modal -->
    <Dialog :open="remarkVoter !== null" @update:open="(open) => { if (!open) closeRemark(); }">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>CC Remarks</DialogTitle>
                <DialogDescription>
                    Add or update call center remarks for this voter.
                </DialogDescription>
            </DialogHeader>
            <div v-if="remarkVoter" class="flex items-center gap-3 rounded-lg border bg-muted/30 p-3">
                <img
                    v-if="remarkVoter.photo_url"
                    :src="remarkVoter.photo_url"
                    :alt="remarkVoter.name ?? 'Voter photo'"
                    class="h-14 w-14 rounded-md object-cover"
                />
                <div v-else class="h-14 w-14 shrink-0 rounded-md bg-muted" />
                <div class="space-y-1">
                    <p class="font-medium">{{ remarkVoter.name ?? '-' }}</p>
                    <p class="text-sm text-muted-foreground">{{ remarkVoter.id_card_number ?? '-' }}</p>
                    <p class="text-sm text-muted-foreground">Box: {{ remarkVoter.registered_box ?? '-' }}</p>
                </div>
            </div>
            <div class="space-y-2">
                <Label for="cc-remarks">Remarks</Label>
                <textarea
                    id="cc-remarks"
                    v-model="remarkForm.cc_remarks"
                    placeholder="Enter call center remarks..."
                    rows="4"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                />
            </div>
            <DialogFooter>
                <Button variant="outline" @click="closeRemark">Cancel</Button>
                <Button :disabled="remarkForm.processing" @click="submitRemark">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

</template>
