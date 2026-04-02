<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import { Button } from '@/components/ui/button';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import { index as rolePermissionsIndex, update as rolePermissionsUpdate } from '@/routes/role-permissions';
import { type BreadcrumbItem } from '@/types';

type PermissionItem = {
    key: string;
    label: string;
};

type PermissionGroup = {
    key: string;
    label: string;
    items: PermissionItem[];
};

type RolePermissionRow = {
    role: string;
    label: string;
    permissions: Record<string, boolean>;
};

type Props = {
    rolePermissions: RolePermissionRow[];
    permissionGroups: PermissionGroup[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pledge Permissions',
        href: rolePermissionsIndex().url,
    },
];

// Track local edits per role
const localPermissions = reactive<Record<string, Record<string, boolean>>>({});
const savingRole = ref<string | null>(null);
const savedRole = ref<string | null>(null);

// Initialize local state from props
props.rolePermissions.forEach((row) => {
    localPermissions[row.role] = { ...row.permissions };
});

const isDirty = computed(() => (role: string): boolean => {
    const original = props.rolePermissions.find((r) => r.role === role);
    if (!original) return false;
    const local = localPermissions[role];
    return Object.keys(original.permissions).some((k) => original.permissions[k] !== local[k]);
});

const allPermissionKeys = computed(() =>
    props.permissionGroups.flatMap((g) => g.items.map((i) => i.key)),
);

const saveRole = (role: string): void => {
    savingRole.value = role;
    savedRole.value = null;

    router.put(
        rolePermissionsUpdate.url(role),
        { permissions: localPermissions[role] },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
            onSuccess: () => {
                savedRole.value = role;
                setTimeout(() => {
                    if (savedRole.value === role) savedRole.value = null;
                }, 2000);
            },
            onFinish: () => {
                savingRole.value = null;
            },
        },
    );
};

const resetRole = (role: string): void => {
    const original = props.rolePermissions.find((r) => r.role === role);
    if (original) {
        localPermissions[role] = { ...original.permissions };
    }
};
</script>

<template>

    <Head title="Pledge Permissions" />

    <AppHeaderLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4">

            <div class="rounded-xl border bg-card p-4 md:p-5">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">Pledge Permissions</h2>
                    <p class="text-sm text-muted-foreground">
                        Control which pledge fields each role can view and edit. Changes are saved per role.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr>
                                <!-- Role column header -->
                                <th class="sticky left-0 z-10 bg-card px-4 py-3 text-left font-medium min-w-40">
                                    Role
                                </th>

                                <!-- Permission group headers -->
                                <template v-for="group in permissionGroups" :key="group.key">
                                    <th
                                        :colspan="group.items.length"
                                        class="px-4 py-2 text-center font-semibold border-b border-x bg-muted/40"
                                    >
                                        {{ group.label }}
                                    </th>
                                </template>

                                <!-- Actions column -->
                                <th class="px-4 py-3 text-left font-medium"></th>
                            </tr>
                            <tr class="bg-muted/20">
                                <!-- Empty for role column -->
                                <th class="sticky left-0 z-10 bg-muted/20 px-4 py-2"></th>

                                <!-- Permission item sub-headers -->
                                <template v-for="group in permissionGroups" :key="group.key">
                                    <th
                                        v-for="item in group.items"
                                        :key="item.key"
                                        class="px-3 py-2 text-center text-xs font-medium text-muted-foreground whitespace-nowrap border-x"
                                    >
                                        {{ item.label }}
                                    </th>
                                </template>

                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in rolePermissions"
                                :key="row.role"
                                class="border-t transition-colors"
                                :class="isDirty(row.role) ? 'bg-amber-50 dark:bg-amber-950/20' : 'hover:bg-muted/20'"
                            >
                                <!-- Role label -->
                                <td class="sticky left-0 z-10 bg-inherit px-4 py-3 font-medium whitespace-nowrap">
                                    {{ row.label }}
                                </td>

                                <!-- Checkboxes -->
                                <td
                                    v-for="permKey in allPermissionKeys"
                                    :key="permKey"
                                    class="px-3 py-3 text-center border-x"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 cursor-pointer accent-primary"
                                        :checked="localPermissions[row.role]?.[permKey] ?? false"
                                        @change="(e) => {
                                            if (localPermissions[row.role]) {
                                                localPermissions[row.role][permKey] = (e.target as HTMLInputElement).checked;
                                            }
                                        }"
                                    />
                                </td>

                                <!-- Save / Reset actions -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <Button
                                            v-if="isDirty(row.role)"
                                            size="sm"
                                            :disabled="savingRole === row.role"
                                            @click="saveRole(row.role)"
                                        >
                                            {{ savingRole === row.role ? 'Saving…' : 'Save' }}
                                        </Button>
                                        <Button
                                            v-if="isDirty(row.role)"
                                            size="sm"
                                            variant="outline"
                                            @click="resetRole(row.role)"
                                        >
                                            Reset
                                        </Button>
                                        <span
                                            v-if="savedRole === row.role"
                                            class="text-xs text-green-600 dark:text-green-400"
                                        >
                                            Saved
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppHeaderLayout>
</template>
