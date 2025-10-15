<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PostCard from '@/components/PostCard.vue';
import type { BreadcrumbItem, User, Post } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Calendar, MessageSquare, Users, Trophy } from 'lucide-vue-next';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useAvatar } from '@/composables/useAvatar';

const page = usePage();
const user = (page.props as any).user as User;
const posts = (page.props as any).posts as { data: Post[]; links: any[]; meta: any };
const stats = (page.props as any).stats as {
    posts_count: number;
    communities_count: number;
    total_score: number;
};


const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: `u/${user.name}`, href: `/u/${user.name}` },
];

const fallbackCover = '/images/placeholders/community-cover.svg';

// Composables
const { getUserAvatarFallback } = useAvatar();

// Formatar data de criação da conta
const formatJoinDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', { 
        year: 'numeric', 
        month: 'long' 
    });
};
</script>

<template>
    <Head :title="`u/${user.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="mx-auto w-full max-w-4xl">
                <!-- Capa -->
                <div class="relative mb-3 h-40 w-full overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <img 
                        :src="user.cover_url ? `/storage/${user.cover_url}` : fallbackCover" 
                        :alt="`Capa de ${user.name}`" 
                        class="h-full w-full object-cover"
                        @error="(event) => { event.target.src = fallbackCover; }"
                    />
                </div>

                <!-- Header do perfil -->
                <div class="mb-4 rounded-xl border border-sidebar-border/70 bg-background p-6 dark:border-sidebar-border">
                    <div class="flex items-start gap-6">
                        <!-- Avatar -->
                        <div class="relative">
                            <Avatar class="h-20 w-20 overflow-hidden rounded-full border-2 border-sidebar-border/70">
                                <AvatarImage 
                                    v-if="user.avatar" 
                                    :src="`/storage/${user.avatar}`" 
                                    :alt="`Avatar de ${user.name}`" 
                                />
                                <AvatarFallback class="rounded-full bg-blue-500 text-white font-semibold text-lg">
                                    {{ getUserAvatarFallback(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </div>
                        
                        <!-- Informações do usuário -->
                        <div class="min-w-0 flex-1">
                            <div class="mb-2">
                                <h1 class="text-2xl font-bold">u/{{ user.name }}</h1>
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <Calendar class="h-4 w-4" />
                                    <span>Membro desde {{ formatJoinDate(user.created_at) }}</span>
                                </div>
                            </div>
                            
                            <!-- Bio -->
                            <div v-if="user.bio" class="mb-4">
                                <p class="text-sm leading-relaxed">{{ user.bio }}</p>
                            </div>
                            
                            <!-- Estatísticas -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="rounded-lg border border-sidebar-border/70 bg-background p-3 text-center dark:border-sidebar-border">
                                    <div class="flex items-center justify-center gap-1 text-2xl font-semibold text-primary">
                                        <MessageSquare class="h-5 w-5" />
                                        {{ stats.posts_count.toLocaleString() }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">Posts</div>
                                </div>
                                
                                <div class="rounded-lg border border-sidebar-border/70 bg-background p-3 text-center dark:border-sidebar-border">
                                    <div class="flex items-center justify-center gap-1 text-2xl font-semibold text-primary">
                                        <Users class="h-5 w-5" />
                                        {{ stats.communities_count.toLocaleString() }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">Comunidades</div>
                                </div>
                                
                                <div class="rounded-lg border border-sidebar-border/70 bg-background p-3 text-center dark:border-sidebar-border">
                                    <div class="flex items-center justify-center gap-1 text-2xl font-semibold text-primary">
                                        <Trophy class="h-5 w-5" />
                                        {{ stats.total_score.toLocaleString() }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">Karma</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Posts do usuário -->
                <div class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                    <h2 class="mb-4 text-lg font-semibold">Posts recentes</h2>
                    
                    <div v-if="posts.data.length > 0" class="space-y-4">
                        <PostCard v-for="post in posts.data" :key="post.id" :post="post" />
                    </div>
                    
                    <div v-else class="py-8 text-center">
                        <div class="mx-auto mb-4 h-16 w-16 rounded-full bg-muted flex items-center justify-center">
                            <MessageSquare class="h-8 w-8 text-muted-foreground" />
                        </div>
                        <h3 class="text-lg font-semibold">Nenhum post ainda</h3>
                        <p class="text-sm text-muted-foreground">
                            u/{{ user.name }} ainda não fez nenhuma publicação.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
