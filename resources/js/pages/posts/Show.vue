<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PostCard from '@/components/PostCard.vue';
import CommentItem from '@/components/CommentItem.vue';
import CommunityInfoCard from '@/components/CommunityInfoCard.vue';
import type { BreadcrumbItem, Post, Comment } from '@/types';
import { Head } from '@inertiajs/vue3';

interface Props {
    post: Post;
    comments: Comment[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: props.post.title, href: `/posts/${props.post.id}` },
];
</script>

<template>
    <Head title="Post" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative">
            <!-- Layout responsivo: desktop com sidebar, mobile empilhado -->
            <div class="flex h-full flex-1 gap-6 overflow-x-auto rounded-xl p-2 sm:p-4">
                <!-- Espaço vazio à esquerda - apenas em desktop -->
                <div class="hidden xl:block w-80 flex-shrink-0"></div>

                <!-- Conteúdo principal -->
                <div class="flex-1 space-y-4 xl:pr-80">
                    <PostCard :post="props.post" :show-comment-form="true" />

                    <section class="rounded-xl border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border">
                        <h3 class="mb-4 text-lg font-semibold">Comentários ({{ props.comments.length }})</h3>
                        <div class="space-y-3">
                            <CommentItem v-for="c in props.comments" :key="c.id" :comment="c" />
                        </div>
                    </section>
                </div>
            </div>

            <!-- Sidebar com informações da comunidade - Responsiva -->
            <div class="hidden xl:block fixed top-20 right-4 w-80 z-40 max-h-[calc(100vh-6rem)] overflow-y-auto">
                <CommunityInfoCard :community="props.post.community" />
            </div>
        </div>
    </AppLayout>
</template>


