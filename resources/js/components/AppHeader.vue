<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BarChart2, ClipboardList, LayoutGrid, Menu, MonitorSmartphone, Phone, Search, ShieldCheck, Trophy, Users, UsersRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Separator } from '@/components/ui/separator';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import { home, login } from '@/routes';
import { index as activityLogIndex } from '@/routes/activity-log';
import { index as sessionsIndex } from '@/routes/sessions';
import { index as callCenterIndex } from '@/routes/call-center';
import { index as candidatesIndex } from '@/routes/candidates';
import { index as resultsIndex } from '@/routes/results';
import { index as rolePermissionsIndex } from '@/routes/role-permissions';
import { index as usersIndex } from '@/routes/users';
import { index as votersIndex } from '@/routes/voters';
import { index as zerodayIndex } from '@/routes/zeroday';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const currentUser = computed(() => auth.value?.user ?? null);
const isAdmin = computed(() => auth.value?.isAdmin ?? false);
const canViewVoters = computed(() => auth.value?.canViewVoters ?? false);
const canCallCenter = computed(() => auth.value?.canCallCenter ?? false);
const canResults = computed(() => auth.value?.canResults ?? false);
const canZeroday = computed(() => auth.value?.canZeroday ?? false);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const mobileMenuOpen = ref(false);

const activeItemStyles =
    'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: home(),
        icon: LayoutGrid,
    },
];

const votersNavItem: NavItem = {
    title: 'Voters',
    href: votersIndex(),
    icon: Users,
};

const callCenterNavItem: NavItem = {
    title: 'Call Center',
    href: callCenterIndex(),
    icon: Phone,
};

const resultsNavItem: NavItem = {
    title: 'Results',
    href: resultsIndex(),
    icon: BarChart2,
};

const zerodayNavItem: NavItem = {
    title: 'Zeroday',
    href: zerodayIndex(),
    icon: Search,
};

const adminNavItems: NavItem[] = [
    {
        title: 'Candidates',
        href: candidatesIndex(),
        icon: Trophy,
    },
    {
        title: 'Users',
        href: usersIndex(),
        icon: UsersRound,
    },
    {
        title: 'Pledge Permissions',
        href: rolePermissionsIndex(),
        icon: ShieldCheck,
    },
    {
        title: 'Activity Log',
        href: activityLogIndex(),
        icon: ClipboardList,
    },
    {
        title: 'Sessions',
        href: sessionsIndex(),
        icon: MonitorSmartphone,
    },
];

const rightNavItems: NavItem[] = [];
</script>

<template>
    <div class="print:hidden">
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">

                <!-- Mobile hamburger button -->
                <Button
                    variant="ghost"
                    size="icon"
                    class="mr-2 h-9 w-9 lg:hidden"
                    @click="mobileMenuOpen = true"
                >
                    <Menu class="h-5 w-5" />
                </Button>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1 lg:justify-center">
                    <NavigationMenu class="flex h-full items-stretch">
                        <NavigationMenuList class="flex h-full items-stretch space-x-2">
                            <NavigationMenuItem v-for="(item, index) in mainNavItems" :key="index"
                                class="relative flex h-full items-center">
                                <Link :class="[
                                    navigationMenuTriggerStyle(),
                                    whenCurrentUrl(item.href, activeItemStyles),
                                    'h-9 cursor-pointer px-3',
                                ]" :href="item.href">
                                    <component v-if="item.icon" :is="item.icon" class="mr-2 h-4 w-4" />
                                    {{ item.title }}
                                </Link>
                                <div v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white" />
                            </NavigationMenuItem>

                            <!-- Voters (role-gated) -->
                            <NavigationMenuItem v-if="canViewVoters" class="relative flex h-full items-center">
                                <Link :class="[
                                    navigationMenuTriggerStyle(),
                                    whenCurrentUrl(votersNavItem.href, activeItemStyles),
                                    'h-9 cursor-pointer px-3',
                                ]" :href="votersNavItem.href">
                                    <component :is="votersNavItem.icon" class="mr-2 h-4 w-4" />
                                    {{ votersNavItem.title }}
                                </Link>
                                <div v-if="isCurrentUrl(votersNavItem.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white" />
                            </NavigationMenuItem>

                            <!-- Call Center (role-gated) -->
                            <NavigationMenuItem v-if="canCallCenter" class="relative flex h-full items-center">
                                <Link :class="[
                                    navigationMenuTriggerStyle(),
                                    whenCurrentUrl(callCenterNavItem.href, activeItemStyles),
                                    'h-9 cursor-pointer px-3',
                                ]" :href="callCenterNavItem.href">
                                    <component :is="callCenterNavItem.icon" class="mr-2 h-4 w-4" />
                                    {{ callCenterNavItem.title }}
                                </Link>
                                <div v-if="isCurrentUrl(callCenterNavItem.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white" />
                            </NavigationMenuItem>

                            <!-- Results (role-gated) -->
                            <NavigationMenuItem v-if="canResults" class="relative flex h-full items-center">
                                <Link :class="[
                                    navigationMenuTriggerStyle(),
                                    whenCurrentUrl(resultsNavItem.href, activeItemStyles),
                                    'h-9 cursor-pointer px-3',
                                ]" :href="resultsNavItem.href">
                                    <component :is="resultsNavItem.icon" class="mr-2 h-4 w-4" />
                                    {{ resultsNavItem.title }}
                                </Link>
                                <div v-if="isCurrentUrl(resultsNavItem.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white" />
                            </NavigationMenuItem>

                            <!-- Zeroday (role-gated) -->
                            <NavigationMenuItem v-if="canZeroday" class="relative flex h-full items-center">
                                <Link :class="[
                                    navigationMenuTriggerStyle(),
                                    whenCurrentUrl(zerodayNavItem.href, activeItemStyles),
                                    'h-9 cursor-pointer px-3',
                                ]" :href="zerodayNavItem.href">
                                    <component :is="zerodayNavItem.icon" class="mr-2 h-4 w-4" />
                                    {{ zerodayNavItem.title }}
                                </Link>
                                <div v-if="isCurrentUrl(zerodayNavItem.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white" />
                            </NavigationMenuItem>

                            <!-- Admin dropdown -->
                            <NavigationMenuItem v-if="isAdmin" class="relative flex h-full items-center">
                                <NavigationMenuTrigger :class="[
                                    navigationMenuTriggerStyle(),
                                    'h-9 cursor-pointer px-3',
                                ]">
                                    Admin
                                </NavigationMenuTrigger>
                                <NavigationMenuContent class="min-w-45 p-1">
                                    <Link v-for="item in adminNavItems" :key="item.title" :href="item.href"
                                        :class="[
                                            'flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-accent',
                                            whenCurrentUrl(item.href, activeItemStyles),
                                        ]">
                                        <component v-if="item.icon" :is="item.icon" class="h-4 w-4" />
                                        {{ item.title }}
                                    </Link>
                                </NavigationMenuContent>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="hidden space-x-1 lg:flex">
                        <template v-for="item in rightNavItems" :key="item.title">
                            <TooltipProvider :delay-duration="0">
                                <Tooltip>
                                    <TooltipTrigger>
                                        <Button variant="ghost" size="icon" as-child
                                            class="group h-9 w-9 cursor-pointer">
                                            <a :href="toUrl(item.href)" target="_blank" rel="noopener noreferrer">
                                                <span class="sr-only">{{ item.title }}</span>
                                                <component :is="item.icon"
                                                    class="size-5 opacity-80 group-hover:opacity-100" />
                                            </a>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{{ item.title }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </template>
                    </div>

                    <DropdownMenu v-if="currentUser">
                        <DropdownMenuTrigger :as-child="true">
                            <Button variant="ghost" size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary">
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage v-if="currentUser?.avatar" :src="currentUser.avatar"
                                        :alt="currentUser.name" />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(currentUser?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="currentUser" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <Button v-else as-child variant="default" size="sm">
                        <Link :href="login()">Login</Link>
                    </Button>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-b border-sidebar-border/70">
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>

    <!-- Mobile navigation drawer -->
    <Sheet :open="mobileMenuOpen" @update:open="mobileMenuOpen = $event">
        <SheetContent side="left" class="flex w-72 flex-col p-0">
            <SheetHeader class="border-b px-4 py-4">
                <SheetTitle class="text-left text-base font-semibold">Navigation</SheetTitle>
            </SheetHeader>

            <nav class="flex-1 overflow-y-auto px-3 py-3">
                <!-- Main nav items -->
                <div class="space-y-0.5">
                    <Link
                        v-for="item in mainNavItems"
                        :key="item.title"
                        :href="item.href"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent"
                        :class="whenCurrentUrl(item.href, activeItemStyles)"
                        @click="mobileMenuOpen = false"
                    >
                        <component v-if="item.icon" :is="item.icon" class="h-4 w-4 shrink-0" />
                        {{ item.title }}
                    </Link>
                </div>

                <!-- Role-gated items -->
                <template v-if="canViewVoters || canCallCenter || canResults || canZeroday">
                    <Separator class="my-3" />
                    <div class="space-y-0.5">
                        <Link
                            v-if="canViewVoters"
                            :href="votersNavItem.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent"
                            :class="whenCurrentUrl(votersNavItem.href, activeItemStyles)"
                            @click="mobileMenuOpen = false"
                        >
                            <component :is="votersNavItem.icon" class="h-4 w-4 shrink-0" />
                            {{ votersNavItem.title }}
                        </Link>
                        <Link
                            v-if="canCallCenter"
                            :href="callCenterNavItem.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent"
                            :class="whenCurrentUrl(callCenterNavItem.href, activeItemStyles)"
                            @click="mobileMenuOpen = false"
                        >
                            <component :is="callCenterNavItem.icon" class="h-4 w-4 shrink-0" />
                            {{ callCenterNavItem.title }}
                        </Link>
                        <Link
                            v-if="canResults"
                            :href="resultsNavItem.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent"
                            :class="whenCurrentUrl(resultsNavItem.href, activeItemStyles)"
                            @click="mobileMenuOpen = false"
                        >
                            <component :is="resultsNavItem.icon" class="h-4 w-4 shrink-0" />
                            {{ resultsNavItem.title }}
                        </Link>
                        <Link
                            v-if="canZeroday"
                            :href="zerodayNavItem.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent"
                            :class="whenCurrentUrl(zerodayNavItem.href, activeItemStyles)"
                            @click="mobileMenuOpen = false"
                        >
                            <component :is="zerodayNavItem.icon" class="h-4 w-4 shrink-0" />
                            {{ zerodayNavItem.title }}
                        </Link>
                    </div>
                </template>

                <!-- Admin items -->
                <template v-if="isAdmin">
                    <Separator class="my-3" />
                    <p class="mb-1 px-3 text-xs font-medium uppercase tracking-wider text-muted-foreground">Admin</p>
                    <div class="space-y-0.5">
                        <Link
                            v-for="item in adminNavItems"
                            :key="item.title"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-accent"
                            :class="whenCurrentUrl(item.href, activeItemStyles)"
                            @click="mobileMenuOpen = false"
                        >
                            <component v-if="item.icon" :is="item.icon" class="h-4 w-4 shrink-0" />
                            {{ item.title }}
                        </Link>
                    </div>
                </template>
            </nav>
        </SheetContent>
    </Sheet>
</template>
