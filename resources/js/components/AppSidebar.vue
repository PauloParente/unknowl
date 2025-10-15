<script setup lang="ts">
import { Sidebar, SidebarContent, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/vue3';
import { Compass, Flame, Home, MessageCircle } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import { computed, ref } from 'vue';
import type { Community } from '@/types';
import ChatModal from './ChatModal.vue';
import { useNotifications } from '@/composables/useNotifications';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useAvatar } from '@/composables/useAvatar';

const page = usePage();
const communities = computed<Community[]>(() => (((page.props as any)?.auth?.communities ?? []) as Community[]));

// Composables
const { getAvatarUrl, shouldShowAvatar, getCommunityAvatarFallback } = useAvatar();

// Chat Modal
const isChatModalOpen = ref(false);
const { totalUnreadCount } = useNotifications();

const openChatModal = () => {
  console.log('Abrindo modal de chat principal')
  isChatModalOpen.value = true;
};

const closeChatModal = () => {
  console.log('Fechando modal de chat principal')
  console.trace('Stack trace do fechamento do modal')
  isChatModalOpen.value = false;
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link href="/dashboard">
                            <Home />
                            <span>Página inicial</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link href="/explore">
                            <Compass />
                            <span>Explorar</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <Link href="/popular">
                            <Flame />
                            <span>Popular</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton @click="openChatModal">
                        <MessageCircle />
                        <span>Chat</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <div class="my-3 border-t border-sidebar-border/70"></div>

            <SidebarMenu>
                <template v-for="c in communities" :key="c.id">
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link :href="`/r/${c.name?.startsWith('r/') ? c.name.slice(2) : c.name}`">
                                <Avatar class="h-4 w-4">
                                    <AvatarImage 
                                        v-if="shouldShowAvatar(c.avatar)" 
                                        :src="getAvatarUrl(c.avatar)" 
                                        :alt="c.name" 
                                    />
                                    <AvatarFallback class="rounded bg-blue-500 text-white text-xs">
                                        {{ getCommunityAvatarFallback(c.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span>{{ c.name }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </template>
                <SidebarMenuItem v-if="!communities.length">
                    <SidebarMenuButton as-child>
                        <span class="text-muted-foreground">Sem comunidades seguidas</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarContent>
    </Sidebar>
    
    <!-- Chat Modal -->
    <ChatModal 
        :is-open="isChatModalOpen" 
        @close="closeChatModal" 
    />
    
    <slot />
</template>
