<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import CreatePostDialog from '@/components/CreatePostDialog.vue';
import CreateCommunityDialog from '@/components/CreateCommunityDialog.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import Input from '@/components/ui/input/Input.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useAvatar } from '@/composables/useAvatar';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, Plus, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { BreadcrumbItemType } from '@/types';
import { useSearchAutocomplete } from '@/composables/useSearchAutocomplete';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const auth = computed(() => page.props.auth);
const { getAvatarUrl, shouldShowAvatar, getUserAvatarFallback } = useAvatar();

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
    <header
        class="fixed top-0 left-0 right-0 z-50 flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 bg-background px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <!-- Left side: Breadcrumbs -->
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Center search -->
        <div class="hidden w-full max-w-2xl md:block flex-1 flex justify-center">
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
        <div class="flex items-center gap-2">
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
    </header>

    <CreatePostDialog v-model="showCreatePost" />
    <CreateCommunityDialog v-model="showCreateCommunity" />
</template>
