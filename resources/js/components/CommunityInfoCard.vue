<script setup lang="ts">
import type { Community } from '@/types';
import { timeAgo } from '@/lib/utils';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { show as communityShow } from '@/routes/communities/index';

interface Props {
    community: Community;
}

const props = defineProps<Props>();

const page = usePage();
const followedIdsFromProps = computed<number[]>(() => ((page.props as any)?.auth?.communities || []).map((c: any) => c.id));
const optimisticFollows = ref<Set<number>>(new Set());
const optimisticUnfollows = ref<Set<number>>(new Set());

function isFollowing(communityId: number) {
    if (optimisticFollows.value.has(communityId)) return true;
    if (optimisticUnfollows.value.has(communityId)) return false;
    return followedIdsFromProps.value.includes(communityId);
}

function refreshShared() {
    // Reassign to trigger reactivity after clearing
    optimisticFollows.value = new Set();
    optimisticUnfollows.value = new Set();
    // Reload full page data to ensure feed/explore reflect new follow state
    router.reload();
}

function follow(communityId: number) {
    const next = new Set(optimisticFollows.value);
    next.add(communityId);
    optimisticFollows.value = next;
    router.post(`/communities/${communityId}/follow`, {}, { preserveScroll: true, onFinish: refreshShared });
}

function unfollow(communityId: number) {
    if (!confirm('Tem certeza que deseja deixar de seguir esta comunidade?')) return;
    const next = new Set(optimisticUnfollows.value);
    next.add(communityId);
    optimisticUnfollows.value = next;
    router.delete(`/communities/${communityId}/follow`, { preserveScroll: true, onFinish: refreshShared });
}

function navigateToCommunity() {
    router.visit(communityShow(props.community.name).url);
}
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
        <!-- Cabeçalho com capa -->
        <div class="relative mb-4">
            <!-- Capa da comunidade -->
            <div 
                v-if="community.cover_url" 
                class="h-20 w-full rounded-lg bg-cover bg-center bg-no-repeat"
                :style="{ backgroundImage: `url(${community.cover_url})` }"
            />
            <div 
                v-else 
                class="h-20 w-full rounded-lg bg-gradient-to-r from-blue-500 to-purple-600"
            />
            
            <!-- Avatar da comunidade -->
            <div class="absolute -bottom-4 left-4">
                <div class="h-12 w-12 rounded-full border-4 border-background bg-background p-1 dark:border-background">
                    <img 
                        v-if="community.avatar" 
                        :src="community.avatar" 
                        :alt="`Avatar de ${community.title}`"
                        class="h-full w-full rounded-full object-cover"
                    />
                    <div 
                        v-else 
                        class="flex h-full w-full items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold text-lg"
                    >
                        {{ community.title.charAt(0).toUpperCase() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações da comunidade -->
        <div class="mt-6 space-y-3">
            <!-- Nome e título -->
            <div>
                <h3 
                    @click="navigateToCommunity"
                    class="text-lg font-semibold text-foreground hover:text-primary cursor-pointer transition-colors"
                >
                    {{ community.title }}
                </h3>
                <p 
                    @click="navigateToCommunity"
                    class="text-sm text-muted-foreground hover:text-primary cursor-pointer transition-colors"
                >
                    r/{{ community.name }}
                </p>
            </div>

            <!-- Descrição -->
            <p v-if="community.description" class="text-sm text-muted-foreground leading-relaxed">
                {{ community.description }}
            </p>

            <!-- Estatísticas -->
            <div class="space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Membros</span>
                    <span class="font-medium">{{ community.members_count?.toLocaleString() || 0 }}</span>
                </div>
                
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Online</span>
                    <span class="font-medium text-green-600 dark:text-green-400">
                        {{ community.online_count?.toLocaleString() || 0 }}
                    </span>
                </div>
                
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Posts</span>
                    <span class="font-medium">{{ community.posts_count?.toLocaleString() || 0 }}</span>
                </div>
                
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">Criada</span>
                    <span class="font-medium">{{ timeAgo(community.created_at) }}</span>
                </div>
            </div>

            <!-- Botão de ação (seguir/deixar de seguir) -->
            <div class="pt-2">
                <button
                    v-if="!isFollowing(community.id)"
                    @click="follow(community.id)"
                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                >
                    Seguir
                </button>
                <button
                    v-else
                    @click="unfollow(community.id)"
                    class="w-full rounded-lg bg-gray-200 dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                >
                    Deixar de seguir
                </button>
            </div>
        </div>
    </div>
</template>
