<script setup lang="ts">
import type { Community, Post } from '@/types';
import { timeAgo } from '@/lib/utils';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CommunityDescriptionEditor from '@/components/CommunityDescriptionEditor.vue';
import CommunityRulesEditor from '@/components/CommunityRulesEditor.vue';
import CommunityPinnedPostsEditor from '@/components/CommunityPinnedPostsEditor.vue';
import CommunityActionButtons from '@/components/CommunityActionButtons.vue';
import CommunityAvatarUpload from '@/components/CommunityAvatarUpload.vue';
import { Link } from '@inertiajs/vue3';

interface Props {
    community: Community;
    communityName: string;
    posts: Post[];
    pinnedPosts: Post[];
    rules: string[];
    userRole?: string;
    userRoleLabel?: string;
    isMember: boolean;
    isOwner: boolean;
    isModerator: boolean;
    canEdit: boolean;
    membersCount: number;
    onlineCount: number;
    postsCount: number;
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
    optimisticFollows.value = new Set();
    optimisticUnfollows.value = new Set();
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
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
        <!-- Informações da comunidade -->
        <div class="space-y-4">
            <!-- Role do usuário -->
            <div v-if="userRoleLabel" class="flex justify-center">
                <span class="rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary">
                    {{ userRoleLabel }}
                </span>
            </div>

            <!-- Estatísticas -->
            <div class="grid grid-cols-3 gap-3">
                <div class="text-center">
                    <div class="text-lg font-semibold">{{ membersCount?.toLocaleString?.() ?? membersCount }}</div>
                    <div class="text-xs text-muted-foreground">membros</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-green-600 dark:text-green-400">{{ onlineCount?.toLocaleString?.() ?? onlineCount }}</div>
                    <div class="text-xs text-muted-foreground">online</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold">{{ postsCount?.toLocaleString?.() ?? postsCount }}</div>
                    <div class="text-xs text-muted-foreground">posts</div>
                </div>
            </div>

            <!-- Botões de ação -->
            <div>
                <CommunityActionButtons
                    :community-id="community.id"
                    :community-name="communityName"
                    :is-member="isMember"
                    :is-owner="isOwner"
                    :is-moderator="isModerator"
                    :can-create-post="true"
                />
            </div>

            <!-- Descrição -->
            <CommunityDescriptionEditor
                :community-id="community.id"
                :initial-description="community.description || ''"
                :can-edit="canEdit"
            />

            <!-- Posts fixados -->
            <div v-if="pinnedPosts.length > 0" class="space-y-2">
                <h4 class="text-sm font-semibold text-foreground">Posts fixados</h4>
                <div class="space-y-2">
                    <div 
                        v-for="post in pinnedPosts" 
                        :key="post.id"
                        class="rounded-lg border border-sidebar-border/50 bg-card p-2 hover:bg-accent/50 transition-colors"
                    >
                        <Link :href="`/posts/${post.id}`" class="block">
                            <h5 class="text-sm font-medium text-foreground line-clamp-2">{{ post.title }}</h5>
                            <p class="text-xs text-muted-foreground mt-1">{{ timeAgo(post.created_at) }}</p>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Regras da comunidade -->
            <div class="space-y-2">
                <h4 class="text-sm font-semibold text-foreground">Regras da comunidade</h4>
                <CommunityRulesEditor
                    :community-id="community.id"
                    :initial-rules="rules"
                    :can-edit="canEdit"
                />
            </div>

            <!-- Informações adicionais -->
            <div class="pt-2 border-t border-sidebar-border/50">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Criada</span>
                    <span>{{ timeAgo(community.created_at) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
