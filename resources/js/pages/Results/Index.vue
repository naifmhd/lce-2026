<script setup lang="ts">
import { Head, useForm, usePoll } from '@inertiajs/vue3';
import { Box, CheckCircle2, Clock, Sparkles, TrendingUp, Users, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { index as resultsIndex, storeBox as resultsStoreBox } from '@/routes/results';
import { type BreadcrumbItem } from '@/types';

type Race = {
    id: number;
    name: string;
    dhaaira: string | null;
    type: string;
    sort_order: number;
    projected_winner: string | null;
    projection_confidence: string | null;
    projection_reasoning: string | null;
    projection_updated_at: string | null;
};

type Candidate = {
    id: number;
    name: string;
    affiliation: string;
    race_id: number;
};

type RaceStats = {
    race_id: number;
    eligible: number;
    total_voted: number;
    votes_per_party: Record<string, number>;
    estimate_per_party: Record<string, number>;
    difference: Record<string, number>;
    mdp_vs_pnc: number;
    turnout_pct: number;
    votes_counted_pct: number;
    total_boxes: number;
    counted_boxes: number;
    uncounted_boxes: number;
    votes_remaining: number;
};

type AvailableBox = {
    registered_box: string;
    dhaairas: string[];
    eligible: number;
    voted: number;
};

type IslandSummary = {
    total_boxes: number;
    counted_boxes: number;
    uncounted_boxes: number;
    total_eligible: number;
    total_known_voted: number;
    valid_votes: Record<string, number>;
    invalid_votes: Record<string, number>;
    total_voted: number;
    turnout_pct: number;
    votes_remaining: number;
};

type ExistingBoxData = {
    invalid_votes: Record<string, number>;
    races: Record<number, Record<number, number>>;
};

type ElectionType = {
    key: string;
    label: string;
    raceTypes: string[];
};

type Props = {
    canEnterResults: boolean;
    electionTypes: ElectionType[];
    races: Race[];
    candidates: Candidate[];
    raceStats: Record<number, RaceStats>;
    availableBoxes: AvailableBox[];
    islandSummary: IslandSummary;
    existingBoxData: Record<string, ExistingBoxData>;
};

const props = defineProps<Props>();

usePoll(15000, { only: ['races', 'raceStats', 'islandSummary'] });

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Results', href: resultsIndex().url },
];

// --- Modal state ---
const modalOpen = ref(false);
const selectedBox = ref<AvailableBox | null>(null);
const boxSearch = ref('');

const filteredBoxes = computed(() =>
    props.availableBoxes.filter((b) =>
        b.registered_box.toLowerCase().includes(boxSearch.value.toLowerCase()),
    ),
);

// Races relevant to the selected box
const relevantRaces = computed<Race[]>(() => {
    if (selectedBox.value === null) return [];
    const dhaairas = selectedBox.value.dhaairas;
    return props.races.filter(
        (r) => r.dhaaira === null || dhaairas.includes(r.dhaaira),
    );
});

// Candidates indexed by race_id
const candidatesByRace = computed<Record<number, Candidate[]>>(() => {
    const map: Record<number, Candidate[]> = {};
    for (const c of props.candidates) {
        if (!map[c.race_id]) map[c.race_id] = [];
        map[c.race_id].push(c);
    }
    return map;
});

// Build initial form data keyed by race_id → candidate_id → votes
type RaceEntry = { race_id: number; votes: Record<number, number> };

const buildFormData = () => {
    const races: RaceEntry[] = props.races.map((race) => ({
        race_id: race.id,
        votes: Object.fromEntries(
            (candidatesByRace.value[race.id] ?? []).map((c) => [c.id, 0]),
        ),
    }));
    const invalid_votes = Object.fromEntries(props.electionTypes.map((e) => [e.key, 0]));
    return { registered_box: '', invalid_votes, races };
};

const form = useForm(buildFormData());

const selectBox = (box: AvailableBox): void => {
    selectedBox.value = box;
    form.registered_box = box.registered_box;

    const existing = props.existingBoxData[box.registered_box];
    if (existing) {
        for (const electionType of props.electionTypes) {
            form.invalid_votes[electionType.key] = existing.invalid_votes[electionType.key] ?? 0;
        }
        for (const raceEntry of form.races) {
            const existingRace = existing.races[raceEntry.race_id];
            if (existingRace) {
                for (const candidateId of Object.keys(raceEntry.votes)) {
                    raceEntry.votes[Number(candidateId)] = existingRace[Number(candidateId)] ?? 0;
                }
            }
        }
    }
};

const openModal = (): void => {
    selectedBox.value = null;
    boxSearch.value = '';
    form.reset();
    Object.assign(form, buildFormData());
    modalOpen.value = true;
};

const closeModal = (): void => {
    modalOpen.value = false;
    selectedBox.value = null;
    boxSearch.value = '';
    form.clearErrors();
};

const raceFormEntry = (raceId: number): RaceEntry | undefined =>
    form.races.find((r: RaceEntry) => r.race_id === raceId);

const submitResults = (): void => {
    const payload = {
        registered_box: form.registered_box,
        invalid_votes: form.invalid_votes,
        races: relevantRaces.value.map((race) => {
            const entry = raceFormEntry(race.id);
            return {
                race_id: race.id,
                votes: entry?.votes ?? {},
            };
        }),
    };

    useForm(payload).post(resultsStoreBox.url(), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// --- Stats helpers ---
const stats = (raceId: number): RaceStats | undefined => props.raceStats[raceId];

const pct = (val: number): string => `${val.toFixed(1)}%`;
const diffLabel = (val: number): string => (val > 0 ? `+${val}` : `${val}`);

const affiliationClass = (affiliation: string): string => {
    if (affiliation === 'MDP') return 'bg-yellow-300 text-yellow-800 font-bold';
    if (affiliation === 'PNC') return 'bg-cyan-300 text-cyan-900 font-bold';
    if (affiliation === 'Yes') return 'bg-green-300 text-green-800 font-bold';
    if (affiliation === 'No') return 'bg-red-300 text-red-800 font-bold';
    return 'bg-muted text-muted-foreground';
};

const projectionClass = (winner: string): string => {
    if (winner === 'MDP') return 'bg-yellow-300 text-yellow-800 font-bold';
    if (winner === 'PNC') return 'bg-cyan-300 text-cyan-900 font-bold';
    return 'bg-muted text-muted-foreground';
};

const relativeTime = (iso: string): string => {
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    return `${Math.floor(diff / 3600)}h ago`;
};

// Vote share bar helpers
const mdpSharePct = (raceId: number): number => {
    const s = stats(raceId);
    if (!s) return 0;
    const total = (s.votes_per_party['MDP'] ?? 0) + (s.votes_per_party['PNC'] ?? 0);
    if (total === 0) return 50;
    return ((s.votes_per_party['MDP'] ?? 0) / total) * 100;
};

const pncSharePct = (raceId: number): number => {
    const s = stats(raceId);
    if (!s) return 0;
    const total = (s.votes_per_party['MDP'] ?? 0) + (s.votes_per_party['PNC'] ?? 0);
    if (total === 0) return 50;
    return ((s.votes_per_party['PNC'] ?? 0) / total) * 100;
};
</script>

<template>
    <Head title="Election Results" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">

            <!-- Island-wide summary bar -->
            <div class="rounded-xl border bg-card p-4">
                <div class="mb-4 flex items-center justify-between">
                    <p class="font-semibold">Island-wide Summary</p>
                    <Button v-if="canEnterResults" type="button" @click="openModal">Enter Box Results</Button>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <!-- Boxes -->
                    <div class="flex flex-col gap-1 rounded-lg border bg-muted/20 p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Box class="size-3.5" />
                            Boxes
                        </div>
                        <p class="text-xl font-bold">
                            {{ islandSummary.counted_boxes }}
                            <span class="text-sm font-normal text-muted-foreground">/ {{ islandSummary.total_boxes }}</span>
                        </p>
                        <p class="text-xs text-muted-foreground">{{ islandSummary.uncounted_boxes }} uncounted</p>
                    </div>

                    <!-- Eligible -->
                    <div class="flex flex-col gap-1 rounded-lg border bg-muted/20 p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Users class="size-3.5" />
                            Eligible
                        </div>
                        <p class="text-xl font-bold">{{ islandSummary.total_eligible.toLocaleString() }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ islandSummary.total_known_voted.toLocaleString() }} voted
                        </p>
                    </div>

                    <!-- Valid Votes (per election type) -->
                    <div class="flex flex-col gap-1 rounded-lg border bg-muted/20 p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <CheckCircle2 class="size-3.5" />
                            Valid Votes
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <div
                                v-for="et in electionTypes"
                                :key="et.key"
                                class="flex items-baseline justify-between gap-1"
                            >
                                <span class="truncate text-xs text-muted-foreground">{{ et.label }}</span>
                                <span class="shrink-0 font-bold text-green-600 dark:text-green-400">
                                    {{ (islandSummary.valid_votes[et.key] ?? 0).toLocaleString() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Invalid Votes (per election type) -->
                    <div class="flex flex-col gap-1 rounded-lg border bg-muted/20 p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <XCircle class="size-3.5" />
                            Invalid
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <div
                                v-for="et in electionTypes"
                                :key="et.key"
                                class="flex items-baseline justify-between gap-1"
                            >
                                <span class="truncate text-xs text-muted-foreground">{{ et.label }}</span>
                                <span class="shrink-0 font-bold text-amber-600 dark:text-amber-400">
                                    {{ (islandSummary.invalid_votes[et.key] ?? 0).toLocaleString() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Turnout -->
                    <div class="flex flex-col gap-1 rounded-lg border bg-muted/20 p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <TrendingUp class="size-3.5" />
                            Turnout
                        </div>
                        <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                            {{ pct(islandSummary.turnout_pct) }}
                        </p>
                    </div>

                    <!-- Remaining -->
                    <div class="flex flex-col gap-1 rounded-lg border bg-muted/20 p-3">
                        <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Clock class="size-3.5" />
                            Remaining
                        </div>
                        <p class="text-xl font-bold">{{ islandSummary.votes_remaining.toLocaleString() }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ islandSummary.total_known_voted > 0 ? 'of voted' : 'of eligible' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Race cards grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <Card v-for="race in races" :key="race.id">
                    <CardHeader class="pb-2">
                        <div class="flex items-start justify-between gap-2">
                            <CardTitle class="text-base">{{ race.name }}</CardTitle>
                            <!-- Projection badge -->
                            <div v-if="race.projected_winner" class="flex shrink-0 items-center gap-1.5">
                                <Sparkles class="size-3 text-muted-foreground" />
                                <span class="rounded-xl px-2 py-0.5 text-xs" :class="projectionClass(race.projected_winner)">
                                    {{ race.projected_winner }}
                                </span>
                                <span v-if="race.projection_confidence" class="text-xs text-muted-foreground capitalize">
                                    {{ race.projection_confidence }}
                                </span>
                            </div>
                        </div>
                        <p v-if="race.projected_winner && race.projection_reasoning" class="text-xs text-muted-foreground">
                            {{ race.projection_reasoning }}
                            <span v-if="race.projection_updated_at" class="ml-1 opacity-60">· {{ relativeTime(race.projection_updated_at) }}</span>
                        </p>
                    </CardHeader>
                    <CardContent>
                        <template v-if="stats(race.id)">
                            <div class="space-y-3">
                                <!-- Vote share bar -->
                                <div>
                                    <div class="flex h-3 overflow-hidden rounded-full bg-muted"
                                        v-if="race.type === 'referendum'">
                                        <div
class="bg-green-400 transition-all duration-500"
                                            :style="{ width: pncSharePct(race.id) + '%' }"
                                        ></div>
                                        <div
class="bg-red-400 transition-all duration-500"
                                            :style="{ width: mdpSharePct(race.id) + '%' }"
                                        ></div>
                                    </div>
                                    <div class="flex h-3 overflow-hidden rounded-full bg-muted" v-else>
                                        <div class="bg-yellow-400 transition-all duration-500"
                                            :style="{ width: mdpSharePct(race.id) + '%' }"></div>
                                        <div class="bg-cyan-400 transition-all duration-500"
                                            :style="{ width: pncSharePct(race.id) + '%' }"></div>
                                    </div>

                                </div>

                                <!-- Party vote totals -->
                                <div class="grid grid-cols-2 gap-2 text-sm" v-if="race.type === 'referendum'">
                                    <div v-for="party in ['Yes', 'No']" :key="party" class="rounded-lg border p-3">
                                        <div class="flex items-center justify-between">
                                            <!-- {{ party }} -->
                                            <span class="rounded-xl px-2 py-0.5 text-xs"
                                                :class="affiliationClass(party)">{{ party }}</span>
                                        </div>
                                        <p class="mt-1 text-2xl font-bold tabular-nums">{{
                                            (stats(race.id)!.votes_per_party[party == 'Yes' ? 'PNC' : 'MDP'] ??
                                                0).toLocaleString() }}</p>
                                        <p class="text-sm font-medium text-muted-foreground">
                                            {{ pct(party === 'Yes' ? pncSharePct(race.id) : mdpSharePct(race.id)) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm" v-else>
                                    <div v-for="party in ['MDP', 'PNC',]" :key="party" class="rounded-lg border p-3">
                                        <div class="flex items-center justify-between">
                                            <!-- {{ party }} -->
                                            <span class="rounded-xl px-2 py-0.5 text-xs"
                                                :class="affiliationClass(party)">{{ party }}</span>
                                            <span class="text-xs text-muted-foreground">est pledged {{
                                                stats(race.id)!.total_pledged_per_party[party] ?? 0 }}</span>
                                        </div>
                                        <p
                                            v-for="c in (candidatesByRace[race.id] ?? []).filter(c => c.affiliation === party)"
                                            :key="c.id"
                                            class="mt-0.5 text-xs text-muted-foreground"
                                        >{{ c.name }}</p>
                                        <p class="mt-1 text-2xl font-bold tabular-nums">{{
                                            (stats(race.id)!.votes_per_party[party] ?? 0).toLocaleString() }}</p>
                                        <p class="text-sm font-medium text-muted-foreground">
                                            {{ pct(party === 'MDP' ? mdpSharePct(race.id) : pncSharePct(race.id)) }}
                                        </p>
                                        <p class="text-xs"
                                            :class="(stats(race.id)!.difference[party] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                            {{ diffLabel(stats(race.id)!.difference[party] ?? 0) }} voted pledge
                                            difference
                                        </p>
                                    </div>
                                </div>
                                <!-- {{ race.type }} -->
                                <!-- Stats row -->
                                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 border-t pt-3 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1 text-muted-foreground"><Users class="size-3" /> Eligible</span>
                                        <span class="font-medium">{{ stats(race.id)!.eligible.toLocaleString() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1 text-muted-foreground"><CheckCircle2 class="size-3" /> Counted</span>
                                        <span class="font-medium">{{ stats(race.id)!.total_voted.toLocaleString() }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1 text-muted-foreground"><TrendingUp class="size-3" /> Turnout</span>
                                        <span class="font-medium">{{ pct(stats(race.id)!.turnout_pct) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="flex items-center gap-1 text-muted-foreground"><Clock class="size-3" /> Remaining</span>
                                        <span class="font-medium">{{ stats(race.id)!.votes_remaining.toLocaleString() }}</span>
                                    </div>
                                </div>

                                <!-- Box progress footer -->
                                <div class="flex items-center justify-between rounded-lg bg-muted/30 px-3 py-2 text-xs">
                                    <span class="flex items-center gap-1 text-muted-foreground">
                                        <Box class="size-3" />
                                        {{ stats(race.id)!.counted_boxes }} / {{ stats(race.id)!.total_boxes }} boxes
                                    </span>
                                    <span
                                        v-if="stats(race.id)!.uncounted_boxes > 0"
                                        class="font-medium text-amber-600 dark:text-amber-400"
                                    >
                                        {{ stats(race.id)!.uncounted_boxes }} remaining
                                    </span>
                                    <span v-else class="font-medium text-green-600 dark:text-green-400">All counted</span>
                                </div>
                            </div>
                        </template>
                        <template v-else>
                            <p class="py-4 text-center text-sm text-muted-foreground">No results entered yet.</p>
                        </template>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppHeaderLayout>

    <!-- Enter Box Results Dialog -->
    <Dialog :open="modalOpen" @update:open="(isOpen) => { if (!isOpen) closeModal(); }">
        <DialogContent class="flex max-h-[90vh] flex-col sm:max-w-5xl">
            <DialogHeader>
                <DialogTitle>Enter Box Results</DialogTitle>
                <DialogDescription>Select a polling box and enter vote counts for each race.</DialogDescription>
            </DialogHeader>

            <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto">
                <!-- Box selection -->
                <div v-if="selectedBox === null" class="flex flex-col gap-3">
                    <div class="space-y-2">
                        <Label>Search Box</Label>
                        <Input v-model="boxSearch" placeholder="Type to search boxes..." />
                    </div>
                    <div class="max-h-64 overflow-y-auto rounded-md border">
                        <button
                            v-for="box in filteredBoxes"
                            :key="box.registered_box"
                            type="button"
                            class="flex w-full items-start justify-between px-4 py-2.5 text-left text-sm hover:bg-muted"
                            @click="selectBox(box)"
                        >
                            <span class="font-medium">{{ box.registered_box }}</span>
                            <div class="flex items-center gap-2">
                                <span v-if="existingBoxData[box.registered_box]" class="text-xs font-medium text-green-600 dark:text-green-400">entered</span>
                                <span class="text-xs text-muted-foreground">{{ box.dhaairas.join(', ') }}</span>
                            </div>
                        </button>
                        <div v-if="filteredBoxes.length === 0" class="px-4 py-6 text-center text-sm text-muted-foreground">
                            No boxes found.
                        </div>
                    </div>
                </div>

                <!-- Vote entry form -->
                <div v-else class="flex flex-col gap-4">
                    <div class="flex items-center justify-between rounded-lg bg-muted/40 px-3 py-2">
                        <div>
                            <p class="font-medium">{{ selectedBox.registered_box }}</p>
                            <p class="text-xs text-muted-foreground">{{ selectedBox.dhaairas.join(', ') }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                {{ selectedBox.eligible.toLocaleString() }} eligible
                                · {{ selectedBox.voted.toLocaleString() }} voted
                            </p>
                        </div>
                        <Button type="button" variant="ghost" size="sm" @click="selectedBox = null">Change</Button>
                    </div>

                    <!-- 3-column grid — one column per election type -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div v-for="electionType in electionTypes" :key="electionType.key"
                            class="flex flex-col gap-3 rounded-lg border p-4">
                            <!-- Column header -->
                            <p class="border-b pb-2 text-sm font-semibold">{{ electionType.label }}</p>

                            <!-- Races for this election type -->
                            <template v-for="race in relevantRaces.filter(r => electionType.raceTypes.includes(r.type))"
                                :key="race.id">
                                <div class="rounded-md bg-muted/30 p-3">
                                    <p class="mb-2 text-xs font-medium text-muted-foreground uppercase tracking-wide">{{
                                        race.name }}</p>
                                    <div class="flex flex-col gap-2">
                                        <div v-for="candidate in (candidatesByRace[race.id] ?? [])" :key="candidate.id"
                                            class="flex items-center gap-2">
                                            <span class="w-10 shrink-0 rounded-xl px-1.5 py-0.5 text-center text-xs"
                                                v-if="electionType.key != 'referendum'"
                                                :class="candidate.affiliation ? affiliationClass(candidate.affiliation) : 'bg-muted text-muted-foreground'">
                                                {{ candidate.affiliation || '—' }}
                                            </span>

                                            <Label v-if="electionType.key === 'referendum'"
                                                :for="`votes-${race.id}-${candidate.id}`" class="flex-1 text-xs">
                                                <span class="w-10 shrink-0 rounded-xl px-1.5 py-0.5 text-center text-xs"
                                                    :class="candidate.name ? affiliationClass(candidate.name) : 'bg-muted text-muted-foreground'">
                                                    {{ candidate.name || '—' }}
                                                </span>
                                            </Label>
                                            <Label v-else :for="`votes-${race.id}-${candidate.id}`"
                                                class="flex-1 text-xs">
                                                {{ candidate.name || '—' }}
                                            </Label>
                                            <Input :id="`votes-${race.id}-${candidate.id}`"
                                                v-model.number="raceFormEntry(race.id)!.votes[candidate.id]"
                                                type="number" min="0" class="w-20 text-sm" />
                                        </div>
                                        <div v-if="(candidatesByRace[race.id] ?? []).length === 0"
                                            class="text-xs text-muted-foreground">
                                            No candidates registered.
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-if="relevantRaces.filter(r => electionType.raceTypes.includes(r.type)).length === 0"
                                class="text-xs text-muted-foreground">
                                No races for this election type.
                            </div>

                            <!-- Invalid votes — per election type -->
                            <div
                                class="mt-auto flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 dark:border-amber-900 dark:bg-amber-950/30">
                                <XCircle class="size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                                <Label :for="`invalid-${electionType.key}`" class="flex-1 text-xs font-medium">Invalid
                                    votes</Label>
                                <Input
:id="`invalid-${electionType.key}`"
                                    v-model.number="form.invalid_votes[electionType.key]" type="number" min="0"
                                    class="w-20 text-sm" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t pt-4">
                <Button type="button" variant="outline" @click="closeModal">Cancel</Button>
                <Button
                    type="button"
                    :disabled="selectedBox === null || form.processing"
                    @click="submitResults"
                >
                    Save Results
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
