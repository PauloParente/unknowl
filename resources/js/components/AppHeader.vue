<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import CreatePostDialog from '@/components/CreatePostDialog.vue';
import CreateCommunityDialog from '@/components/CreateCommunityDialog.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NavigationMenu, NavigationMenuItem, NavigationMenuList, navigationMenuTriggerStyle } from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAvatar } from '@/composables/useAvatar';
import { toUrl, urlIsActive } from '@/lib/utils';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, NavItem } from '@/types';
import { InertiaLinkProps, Link, router, usePage } from '@inertiajs/vue3';
import { Bell, LayoutGrid, Menu, Plus, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Input from '@/components/ui/input/Input.vue';
import { useSearchAutocomplete } from '@/composables/useSearchAutocomplete';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { getAvatarUrl, shouldShowAvatar, getUserAvatarFallback } = useAvatar();

const isCurrentRoute = computed(() => (url: NonNullable<InertiaLinkProps['href']>) => urlIsActive(url, page.url));

const activeItemStyles = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        isCurrentRoute.value(toUrl(url)) ? 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100' : '',
);

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const showCreatePost = ref(false);
const showCreateCommunity = ref(false);
const {
    query,
    suggestions,
    isLoading: isSearchLoading,
    showSuggestions,
    selectedIndex,
    hasSuggestions,
    hasQuery,
    performSearch,
    selectSuggestion,
    handleKeydown,
    hideSuggestions,
    clearQuery,
} = useSearchAutocomplete();
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center justify-between px-4 md:max-w-7xl">
                <!-- Left side: Logo and Navigation -->
                <div class="flex items-center gap-6">
                    <!-- Mobile Menu -->
                    <div class="lg:hidden">
                        <Sheet>
                            <SheetTrigger :as-child="true">
                                <Button variant="ghost" size="icon" class="mr-2 h-9 w-9">
                                    <Menu class="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" class="w-[300px] p-6">
                                <SheetTitle class="sr-only">Navigation Menu</SheetTitle>
                                <SheetHeader class="flex justify-start text-left">
                                    <AppLogoIcon class="size-6 fill-current text-black dark:text-white" />
                                </SheetHeader>
                                <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-6">
                                    <nav class="-mx-3 space-y-1">
                                        <Link
                                            v-for="item in mainNavItems"
                                            :key="item.title"
                                            :href="item.href"
                                            class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                            :class="activeItemStyles(item.href)"
                                        >
                                            <component v-if="item.icon" :is="item.icon" class="h-5 w-5" />
                                            {{ item.title }}
                                        </Link>
                                    </nav>
                                    <div class="flex flex-col space-y-2">
                                        <form @submit.prevent="performSearch()" class="flex items-center gap-2">
                                            <Input v-model="query" placeholder="Buscar comunidades, posts e usuários" class="h-9" />
                                            <Button type="submit" variant="secondary" size="sm" class="h-9">
                                                <Search class="mr-2 h-4 w-4" /> Buscar
                                            </Button>
                                        </form>
                                        <div class="flex items-center gap-2">
                                            <Button variant="default" size="sm" class="h-9 cursor-pointer" @click="showCreateCommunity = true">
                                                <Plus class="mr-2 h-4 w-4" /> Nova Comunidade
                                            </Button>
                                            <Button variant="outline" size="sm" class="h-9 cursor-pointer" @click="showCreatePost = true">
                                                <Plus class="mr-2 h-4 w-4" /> Criar post
                                            </Button>
                                            <Button variant="ghost" size="icon" class="h-9 w-9 cursor-pointer">
                                                <Bell class="h-5 w-5" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    <Link :href="dashboard()" class="flex items-center gap-x-2">
                        <AppLogo />
                    </Link>

                    <!-- Desktop Menu -->
                    <div class="hidden h-full lg:flex">
                        <NavigationMenu class="flex h-full items-stretch">
                            <NavigationMenuList class="flex h-full items-stretch space-x-2">
                                <NavigationMenuItem v-for="(item, index) in mainNavItems" :key="index" class="relative flex h-full items-center">
                                    <Link
                                        :class="[navigationMenuTriggerStyle(), activeItemStyles(item.href), 'h-9 cursor-pointer px-3']"
                                        :href="item.href"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="mr-2 h-4 w-4" />
                                        {{ item.title }}
                                    </Link>
                                    <div
                                        v-if="isCurrentRoute(item.href)"
                                        class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                    ></div>
                                </NavigationMenuItem>
                            </NavigationMenuList>
                        </NavigationMenu>
                    </div>
                </div>

                <!-- Center search (desktop) -->
                <div class="hidden w-full max-w-2xl lg:block flex-1 flex justify-center">
                    <div class="relative w-full max-w-lg">
                        <form @submit.prevent="performSearch()">
                            <Input 
                                v-model="query" 
                                placeholder="Buscar comunidades, posts e usuários" 
                                class="h-9 pl-9 pr-9"
                                @keydown="handleKeydown"
                                @blur="hideSuggestions"
                            />
                            <Search class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <button
                                v-if="hasQuery"
                                type="button"
                                @click="clearQuery"
                                class="absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </form>
                        
                        <!-- Autocomplete dropdown -->
                        <div
                            v-if="showSuggestions && hasSuggestions"
                            class="absolute top-full left-0 right-0 mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50 max-h-80 overflow-y-auto"
                        >
                            <div
                                v-for="(suggestion, index) in suggestions"
                                :key="`${suggestion.type}-${suggestion.id}`"
                                :class="[
                                    'flex items-center gap-3 px-4 py-3 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0',
                                    selectedIndex === index ? 'bg-gray-50 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700'
                                ]"
                                @click="selectSuggestion(suggestion)"
                            >
                                <div class="flex-shrink-0">
                                    <div v-if="suggestion.type === 'community'" class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 dark:text-blue-400 text-sm font-medium">
                                            {{ suggestion.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div v-else class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                                        <span class="text-purple-600 dark:text-purple-400 text-sm font-medium">
                                            {{ suggestion.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ suggestion.type === 'community' ? 'r/' : 'u/' }}{{ suggestion.name }}
                                        </span>
                                        <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                            {{ suggestion.type === 'community' ? 'Comunidade' : 'Usuário' }}
                                        </span>
                                    </div>
                                    <p v-if="suggestion.title" class="text-sm text-gray-600 dark:text-gray-400 truncate">
                                        {{ suggestion.title }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right side: Actions -->
                <div class="flex items-center space-x-2">
                    <!-- Create Community -->
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger>
                                <Button variant="default" size="sm" class="hidden h-9 px-3 lg:inline-flex cursor-pointer" @click="showCreateCommunity = true">
                                    <Plus class="mr-2 h-4 w-4" /> Nova Comunidade
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>Criar comunidade</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>

                    <!-- Create Post -->
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger>
                                <Button variant="outline" size="sm" class="hidden h-9 px-3 lg:inline-flex cursor-pointer" @click="showCreatePost = true">
                                    <Plus class="mr-2 h-4 w-4" /> Novo post
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>Criar post</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>

                    <!-- Notifications -->
                    <TooltipProvider :delay-duration="0">
                        <Tooltip>
                            <TooltipTrigger>
                                <Button variant="ghost" size="icon" class="h-9 w-9 cursor-pointer">
                                    <Bell class="h-5 w-5" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>Notificações</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>

                    <!-- Profile menu (unchanged) -->
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary cursor-pointer"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage v-if="shouldShowAvatar(auth.user?.avatar)" :src="getAvatarUrl(auth.user?.avatar)" :alt="auth.user?.name" />
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getUserAvatarFallback(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <CreatePostDialog v-model="showCreatePost" />
        <CreateCommunityDialog v-model="showCreateCommunity" />

        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-b border-sidebar-border/70">
            <div class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
