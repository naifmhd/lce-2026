<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
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
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { index as usersIndex, store as usersStore, update as usersUpdate } from '@/routes/users';
import { type BreadcrumbItem } from '@/types';

type UserListItem = {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedUsers = {
    data: UserListItem[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number | null;
    current_page: number;
};

type RoleOption = {
    key: string;
    label: string;
};

type Props = {
    users: PaginatedUsers;
    filters: {
        search: string;
    };
    roleOptions: RoleOption[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: usersIndex().url,
    },
];

const filterForm = reactive({
    search: props.filters.search ?? '',
});

const roleLabelByKey = computed<Record<string, string>>(() =>
    Object.fromEntries(props.roleOptions.map((role) => [role.key, role.label])),
);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});
const createDialogOpen = ref(false);

const selectedUser = ref<UserListItem | null>(null);
const editDialogOpen = computed(() => selectedUser.value !== null);
const editForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

const applyFilters = (): void => {
    router.get(
        usersIndex.url({
            query: {
                search: filterForm.search.trim() === '' ? null : filterForm.search.trim(),
            },
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters'],
        },
    );
};

const resetFilters = (): void => {
    filterForm.search = '';
    applyFilters();
};

const createUser = (): void => {
    createForm.post(
        usersStore.url({
            query: {
                search: filterForm.search.trim() === '' ? null : filterForm.search.trim(),
            },
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                createForm.reset();
                createDialogOpen.value = false;
            },
        },
    );
};

const closeCreateUser = (): void => {
    createDialogOpen.value = false;
    createForm.clearErrors();
    createForm.reset();
};

const openEditUser = (user: UserListItem): void => {
    selectedUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.roles = [...user.roles];
    editForm.clearErrors();
};

const closeEditUser = (): void => {
    selectedUser.value = null;
    editForm.clearErrors();
};

const updateUser = (): void => {
    if (selectedUser.value === null) {
        return;
    }

    editForm.patch(
        usersUpdate.url(selectedUser.value.id, {
            query: {
                search: filterForm.search.trim() === '' ? null : filterForm.search.trim(),
            },
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                closeEditUser();
            },
        },
    );
};
</script>

<template>
    <Head title="Users" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">
            <div class="rounded-xl border bg-card p-4 md:p-5">
                <form class="grid grid-cols-1 gap-3 md:grid-cols-4" @submit.prevent="applyFilters">
                    <div class="space-y-2 md:col-span-3">
                        <Label for="search-users">Search</Label>
                        <Input id="search-users" v-model="filterForm.search" placeholder="Search by name or email" />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button type="submit">Search</Button>
                        <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border bg-card">
                <div class="flex items-center justify-between border-b px-4 py-3 md:px-5">
                    <div class="text-sm text-muted-foreground">
                        <p>Showing {{ users.from ?? 0 }} to {{ users.to ?? 0 }}</p>
                        <p>Total {{ users.total ?? 0 }} users</p>
                    </div>
                    <Button type="button" @click="createDialogOpen = true">Add User</Button>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/40 text-left">
                            <tr>
                                <th class="px-4 py-3 font-medium">Name</th>
                                <th class="px-4 py-3 font-medium">Email</th>
                                <th class="px-4 py-3 font-medium">Roles</th>
                                <th class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-t">
                                <td class="px-4 py-3">{{ user.name }}</td>
                                <td class="px-4 py-3">{{ user.email }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <Badge v-for="role in user.roles" :key="`${user.id}-${role}`" variant="outline">
                                            {{ roleLabelByKey[role] ?? role }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Button type="button" size="sm" variant="outline" @click="openEditUser(user)">
                                        Edit
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">No users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 border-t p-3">
                    <template v-for="(link, index) in users.links" :key="index">
                        <span v-if="link.url === null" class="rounded-md border px-3 py-1.5 text-sm text-muted-foreground"
                            v-html="link.label" />
                        <Link v-else :href="link.url" preserve-scroll preserve-state :only="['users', 'filters']"
                            class="rounded-md border px-3 py-1.5 text-sm"
                            :class="link.active ? 'border-primary bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AppHeaderLayout>

    <Dialog :open="createDialogOpen" @update:open="(isOpen) => { if (!isOpen) { closeCreateUser(); } }">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Create User</DialogTitle>
                <DialogDescription>Create a new user and assign roles.</DialogDescription>
            </DialogHeader>

            <form class="grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="createUser">
                <div class="space-y-2">
                    <Label for="create-name">Name</Label>
                    <Input id="create-name" v-model="createForm.name" />
                    <p v-if="createForm.errors.name" class="text-xs text-destructive">{{ createForm.errors.name }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="create-email">Email</Label>
                    <Input id="create-email" v-model="createForm.email" type="email" />
                    <p v-if="createForm.errors.email" class="text-xs text-destructive">{{ createForm.errors.email }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="create-password">Password</Label>
                    <Input id="create-password" v-model="createForm.password" type="password" />
                    <p v-if="createForm.errors.password" class="text-xs text-destructive">{{ createForm.errors.password }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="create-password-confirmation">Confirm Password</Label>
                    <Input id="create-password-confirmation" v-model="createForm.password_confirmation" type="password" />
                </div>
                <div class="space-y-2 md:col-span-2">
                    <p class="text-sm font-medium">Roles</p>
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
                        <label v-for="roleOption in roleOptions" :key="`create-${roleOption.key}`"
                            class="flex items-center gap-2 rounded border p-2 text-sm">
                            <input v-model="createForm.roles" type="checkbox" :value="roleOption.key" />
                            <span>{{ roleOption.label }}</span>
                        </label>
                    </div>
                    <p v-if="createForm.errors.roles" class="text-xs text-destructive">{{ createForm.errors.roles }}</p>
                    <p v-if="createForm.errors['roles.0']" class="text-xs text-destructive">{{ createForm.errors['roles.0'] }}</p>
                </div>
                <div class="flex gap-2 md:col-span-2">
                    <Button type="button" variant="outline" @click="closeCreateUser">Cancel</Button>
                    <Button type="submit" :disabled="createForm.processing">Create User</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog :open="editDialogOpen" @update:open="(isOpen) => { if (!isOpen) { closeEditUser(); } }">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Edit User</DialogTitle>
                <DialogDescription>Update user details, password (optional), and roles.</DialogDescription>
            </DialogHeader>

            <form class="grid grid-cols-1 gap-3 md:grid-cols-2" @submit.prevent="updateUser">
                <div class="space-y-2">
                    <Label for="edit-name">Name</Label>
                    <Input id="edit-name" v-model="editForm.name" />
                    <p v-if="editForm.errors.name" class="text-xs text-destructive">{{ editForm.errors.name }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="edit-email">Email</Label>
                    <Input id="edit-email" v-model="editForm.email" type="email" />
                    <p v-if="editForm.errors.email" class="text-xs text-destructive">{{ editForm.errors.email }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="edit-password">New Password (Optional)</Label>
                    <Input id="edit-password" v-model="editForm.password" type="password" />
                    <p v-if="editForm.errors.password" class="text-xs text-destructive">{{ editForm.errors.password }}</p>
                </div>
                <div class="space-y-2">
                    <Label for="edit-password-confirmation">Confirm Password</Label>
                    <Input id="edit-password-confirmation" v-model="editForm.password_confirmation" type="password" />
                </div>
                <div class="space-y-2 md:col-span-2">
                    <p class="text-sm font-medium">Roles</p>
                    <div class="grid grid-cols-2 gap-2 md:grid-cols-3">
                        <label v-for="roleOption in roleOptions" :key="`edit-${roleOption.key}`"
                            class="flex items-center gap-2 rounded border p-2 text-sm">
                            <input v-model="editForm.roles" type="checkbox" :value="roleOption.key" />
                            <span>{{ roleOption.label }}</span>
                        </label>
                    </div>
                    <p v-if="editForm.errors.roles" class="text-xs text-destructive">{{ editForm.errors.roles }}</p>
                    <p v-if="editForm.errors['roles.0']" class="text-xs text-destructive">{{ editForm.errors['roles.0'] }}</p>
                </div>
                <div class="flex gap-2 md:col-span-2">
                    <Button type="button" variant="outline" @click="closeEditUser">Cancel</Button>
                    <Button type="submit" :disabled="editForm.processing">Save</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
