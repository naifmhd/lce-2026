<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { index as candidatesIndex, store as candidatesStore, update as candidatesUpdate, destroy as candidatesDestroy } from '@/routes/candidates';
import { type BreadcrumbItem } from '@/types';

type Race = {
    id: number;
    name: string;
    dhaaira: string | null;
    type: string;
};

type CandidateListItem = {
    id: number;
    name: string;
    candidate_number: string | null;
    affiliation: string;
    address: string | null;
    race_id: number;
    race_name: string;
};

type Props = {
    candidates: CandidateListItem[];
    races: Race[];
};

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Candidates', href: candidatesIndex().url },
];

const affiliationClass = (affiliation: string): string => {
    if (affiliation === 'MDP') return 'bg-yellow-300 text-yellow-800 font-bold';
    if (affiliation === 'PNC') return 'bg-cyan-300 text-cyan-900 font-bold';
    return 'bg-muted text-muted-foreground';
};

// Create
const createDialogOpen = ref(false);
const createForm = useForm({ name: '', candidate_number: '', affiliation: 'MDP', address: '', race_id: '' as number | '' });

const createCandidate = (): void => {
    createForm.post(candidatesStore.url(), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createDialogOpen.value = false;
        },
    });
};

const closeCreateDialog = (): void => {
    createDialogOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

// Edit
const selectedCandidate = ref<CandidateListItem | null>(null);
const editDialogOpen = computed(() => selectedCandidate.value !== null);
const editForm = useForm({ name: '', candidate_number: '', affiliation: 'MDP', address: '', race_id: 0 as number });

const openEdit = (candidate: CandidateListItem): void => {
    selectedCandidate.value = candidate;
    editForm.name = candidate.name;
    editForm.candidate_number = candidate.candidate_number ?? '';
    editForm.affiliation = candidate.affiliation;
    editForm.address = candidate.address ?? '';
    editForm.race_id = candidate.race_id;
    editForm.clearErrors();
};

const closeEditDialog = (): void => {
    selectedCandidate.value = null;
    editForm.clearErrors();
};

const updateCandidate = (): void => {
    if (selectedCandidate.value === null) return;
    editForm.patch(candidatesUpdate.url(selectedCandidate.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEditDialog(),
    });
};

// Delete
const deleteCandidate = (candidate: CandidateListItem): void => {
    if (!confirm(`Delete ${candidate.name}?`)) return;
    router.delete(candidatesDestroy.url(candidate.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Candidates" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">
            <div class="rounded-xl border bg-card">
                <div class="flex items-center justify-between border-b px-4 py-3 md:px-5">
                    <div>
                        <p class="font-medium">Candidates</p>
                        <p class="text-sm text-muted-foreground">{{ candidates.length }} total</p>
                    </div>
                    <Button type="button" @click="createDialogOpen = true">Add Candidate</Button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">#</th>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">Race</th>
                                <th class="px-4 py-3 font-medium">Affiliation</th>
                                <th class="px-4 py-3 font-medium">Address</th>
                                <th class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="candidate in candidates" :key="candidate.id" class="border-t">
                                <td class="px-4 py-3 text-muted-foreground">{{ candidate.candidate_number ?? '—' }}</td>
                                <td class="px-4 py-3 font-medium">{{ candidate.name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ candidate.race_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-xl px-2 py-0.5 text-xs" :class="affiliationClass(candidate.affiliation)">
                                        {{ candidate.affiliation }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ candidate.address ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button type="button" size="sm" variant="outline" @click="openEdit(candidate)">
                                            Edit
                                        </Button>
                                        <Button type="button" size="sm" variant="destructive" @click="deleteCandidate(candidate)">
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="candidates.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                    No candidates yet. Add your first candidate.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppHeaderLayout>

    <!-- Create Dialog -->
    <Dialog :open="createDialogOpen" @update:open="(isOpen) => { if (!isOpen) closeCreateDialog(); }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Add Candidate</DialogTitle>
                <DialogDescription>Add a new candidate and assign them to a race.</DialogDescription>
            </DialogHeader>
            <form class="flex flex-col gap-4" @submit.prevent="createCandidate">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="create-name">Name</Label>
                        <Input id="create-name" v-model="createForm.name" placeholder="Full name" />
                        <p v-if="createForm.errors.name" class="text-xs text-destructive">{{ createForm.errors.name }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="create-number">Candidate No.</Label>
                        <Input id="create-number" v-model="createForm.candidate_number" placeholder="e.g. 12" />
                        <p v-if="createForm.errors.candidate_number" class="text-xs text-destructive">{{ createForm.errors.candidate_number }}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="create-address">Address</Label>
                    <Input id="create-address" v-model="createForm.address" placeholder="Home address" />
                    <p v-if="createForm.errors.address" class="text-xs text-destructive">{{ createForm.errors.address }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="create-race">Race</Label>
                    <select
                        id="create-race"
                        v-model="createForm.race_id"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                        <option value="" disabled>Select a race</option>
                        <option v-for="race in races" :key="race.id" :value="race.id">{{ race.name }}</option>
                    </select>
                    <p v-if="createForm.errors.race_id" class="text-xs text-destructive">{{ createForm.errors.race_id }}</p>
                </div>
                <div class="space-y-2">
                    <Label>Affiliation</Label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="createForm.affiliation" type="radio" value="MDP" />
                            MDP
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="createForm.affiliation" type="radio" value="PNC" />
                            PNC
                        </label>
                    </div>
                    <p v-if="createForm.errors.affiliation" class="text-xs text-destructive">{{ createForm.errors.affiliation }}</p>
                </div>
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="closeCreateDialog">Cancel</Button>
                    <Button type="submit" :disabled="createForm.processing">Add Candidate</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Edit Dialog -->
    <Dialog :open="editDialogOpen" @update:open="(isOpen) => { if (!isOpen) closeEditDialog(); }">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit Candidate</DialogTitle>
                <DialogDescription>Update candidate details.</DialogDescription>
            </DialogHeader>
            <form class="flex flex-col gap-4" @submit.prevent="updateCandidate">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="edit-name">Name</Label>
                        <Input id="edit-name" v-model="editForm.name" placeholder="Full name" />
                        <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="edit-number">Candidate No.</Label>
                        <Input id="edit-number" v-model="editForm.candidate_number" placeholder="e.g. 12" />
                        <p v-if="editForm.errors.candidate_number" class="text-xs text-destructive">{{ editForm.errors.candidate_number }}</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="edit-address">Address</Label>
                    <Input id="edit-address" v-model="editForm.address" placeholder="Home address" />
                    <p v-if="editForm.errors.address" class="text-xs text-destructive">{{ editForm.errors.address }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="edit-race">Race</Label>
                    <select
                        id="edit-race"
                        v-model="editForm.race_id"
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                        <option v-for="race in races" :key="race.id" :value="race.id">{{ race.name }}</option>
                    </select>
                    <p v-if="editForm.errors.race_id" class="text-xs text-destructive">{{ editForm.errors.race_id }}</p>
                </div>
                <div class="space-y-2">
                    <Label>Affiliation</Label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="editForm.affiliation" type="radio" value="MDP" />
                            MDP
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="editForm.affiliation" type="radio" value="PNC" />
                            PNC
                        </label>
                    </div>
                    <p v-if="editForm.errors.affiliation" class="text-xs text-destructive">{{ editForm.errors.affiliation }}</p>
                </div>
                <div class="flex gap-2">
                    <Button type="button" variant="outline" @click="closeEditDialog">Cancel</Button>
                    <Button type="submit" :disabled="editForm.processing">Save</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
