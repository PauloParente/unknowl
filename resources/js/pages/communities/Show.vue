<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PostCard from '@/components/PostCard.vue';
import CommunityDetailsCard from '@/components/CommunityDetailsCard.vue';
import type { BreadcrumbItem, Community, Post } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const communityName = page.props.name as string;
const postsCount = ((page.props as any)?.community?.posts_count as number | undefined) ?? 0;
const membersCount = ((page.props as any)?.community?.members_count as number | undefined) ?? 0;
const onlineCount = ((page.props as any)?.community?.online_count as number | undefined) ?? 0;


// Dados do usuário atual
const userRole = (page.props as any).userRole as string | undefined;
const userRoleLabel = (page.props as any).userRoleLabel as string | undefined;
const isMember = (page.props as any).isMember as boolean;
const isOwner = (page.props as any).isOwner as boolean;
const isModerator = (page.props as any).isModerator as boolean;
const canEdit = (page.props as any).canEdit as boolean;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `r/${communityName}`, href: `/r/${communityName}` },
];

// Comunidade vinda do backend
const community = (page.props as any).community as Community;

const posts = ((page.props as any).posts as Post[]) ?? [];

// Usar regras da comunidade ou fallback
const rules: string[] = community?.rules || [
    'Seja respeitoso. Ataques pessoais não serão tolerados.',
    'Sem spam ou autopromoção excessiva.',
    'Use títulos claros e descritivos.',
    'Conteúdo fora do tema será removido.',
];

// Posts fixados reais da comunidade
const pinned: Post[] = posts.filter(post => (post as any).is_pinned).slice(0, 3);
</script>

<template>
    <Head :title="community.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative">
            <!-- Layout responsivo: desktop com sidebar, mobile empilhado -->
            <div class="flex h-full flex-1 gap-6 overflow-x-auto rounded-xl p-2 sm:p-4">
                <!-- Espaço vazio à esquerda - apenas em desktop -->
                <div class="hidden xl:block w-80 flex-shrink-0"></div>

                <!-- Conteúdo principal -->
                <div class="flex-1 space-y-4 xl:pr-80">
                    <!-- Cabeçalho da comunidade -->
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                        <!-- Cabeçalho com capa -->
                        <div class="relative mb-4">
                            <!-- Capa da comunidade -->
                            <div 
                                v-if="community.cover_url" 
                                class="h-32 w-full rounded-lg bg-cover bg-center bg-no-repeat"
                                :style="{ backgroundImage: `url(${community.cover_url})` }"
                            />
                            <div 
                                v-else 
                                class="h-32 w-full rounded-lg bg-gradient-to-r from-blue-500 to-purple-600"
                            />
                            
                            <!-- Avatar da comunidade -->
                            <div class="absolute -bottom-6 left-4">
                                <div v-if="canEdit" class="relative">
                                    <CommunityAvatarUpload 
                                        :community="{
                                            id: community.id,
                                            name: community.name,
                                            title: community.title,
                                            avatar: community.avatar
                                        }"
                                    />
                                </div>
                                <div v-else class="h-16 w-16 rounded-full border-4 border-background bg-background p-1 dark:border-background">
                                    <img 
                                        v-if="community.avatar" 
                                        :src="community.avatar" 
                                        :alt="`Avatar de ${community.title}`"
                                        class="h-full w-full rounded-full object-cover"
                                    />
                                    <div 
                                        v-else 
                                        class="flex h-full w-full items-center justify-center rounded-full bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold text-xl"
                                    >
                                        {{ community.title.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-start gap-4 pt-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                                    <h1 class="text-lg sm:text-xl font-semibold">{{ community.name }}</h1>
                                    <span v-if="userRoleLabel" class="rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary self-start">
                                        {{ userRoleLabel }}
                                    </span>
                                </div>
                                <div class="text-sm sm:text-base text-muted-foreground">{{ community.title }}</div>
                            </div>
                            <div class="flex-shrink-0">
                                <CommunityActionButtons
                                    :community-id="community.id"
                                    :community-name="communityName"
                                    :is-member="isMember"
                                    :is-owner="isOwner"
                                    :is-moderator="isModerator"
                                    :can-create-post="true"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Posts -->
                    <div>
                        <h2 class="mb-4 text-lg font-semibold">Posts recentes</h2>
                        <div class="space-y-4">
                            <PostCard v-for="post in posts" :key="post.id" :post="post" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar com detalhes da comunidade - Fixa -->
            <div class="hidden xl:block fixed top-20 right-4 w-80 z-40 max-h-[calc(100vh-6rem)] overflow-y-auto">
                <CommunityDetailsCard
                    :community="community"
                    :community-name="communityName"
                    :posts="posts"
                    :pinned-posts="pinned"
                    :rules="rules"
                    :user-role="userRole"
                    :user-role-label="userRoleLabel"
                    :is-member="isMember"
                    :is-owner="isOwner"
                    :is-moderator="isModerator"
                    :can-edit="canEdit"
                    :members-count="membersCount"
                    :online-count="onlineCount"
                    :posts-count="postsCount"
                />
            </div>
            
            <!-- Sidebar mobile/tablet - abaixo do conteúdo -->
            <div class="xl:hidden mt-6">
                <CommunityDetailsCard
                    :community="community"
                    :community-name="communityName"
                    :posts="posts"
                    :pinned-posts="pinned"
                    :rules="rules"
                    :user-role="userRole"
                    :user-role-label="userRoleLabel"
                    :is-member="isMember"
                    :is-owner="isOwner"
                    :is-moderator="isModerator"
                    :can-edit="canEdit"
                    :members-count="membersCount"
                    :online-count="onlineCount"
                    :posts-count="postsCount"
                />
            </div>
        </div>
    </AppLayout>
</template>


