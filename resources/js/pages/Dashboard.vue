<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PostCard from '@/components/PostCard.vue';
import { type BreadcrumbItem, type Post, type Community } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Link, router } from '@inertiajs/vue3';

interface Props {
    posts: {
        data: Post[];
        links: any[];
        meta: any;
    };
    suggestions?: Community[];
}

const props = defineProps<Props>();
function getCommunityName(name: string) { return name.startsWith('r/') ? name.slice(2) : name; }
function follow(communityId: number) {
    router.post(`/communities/${communityId}/follow`, {}, { preserveScroll: true, onFinish: () => router.reload() });
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-2 sm:p-4">
            <div class="mx-auto w-full max-w-3xl">
                <template v-if="props.posts.data.length > 0">
                    <div class="space-y-4">
                        <PostCard v-for="post in props.posts.data" :key="post.id" :post="post" />
                    </div>
                </template>
                <template v-else>
                    <div class="rounded-xl border border-sidebar-border/70 bg-background p-8 text-center dark:border-sidebar-border">
                        <h2 class="text-2xl font-semibold">Bem-vindo(a) à sua página inicial</h2>
                        <p class="mt-2 text-muted-foreground">Siga algumas comunidades para começar a ver posts por aqui.</p>

                        <div class="mx-auto mt-6 grid max-w-2xl grid-cols-1 gap-3 sm:grid-cols-2">
                            <div
                                v-for="c in (props.suggestions || [])"
                                :key="c.id"
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-sidebar-border/70 bg-card p-3 text-left dark:border-sidebar-border"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium text-sm sm:text-base">{{ c.name }}</div>
                                    <div class="truncate text-xs sm:text-sm text-muted-foreground">{{ c.title }}</div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <Link class="text-xs sm:text-sm text-muted-foreground hover:underline" :href="`/r/${getCommunityName(c.name)}`">Ver</Link>
                                    <button class="rounded-md bg-primary px-2 py-1 text-xs font-medium text-primary-foreground hover:opacity-90" @click="follow(c.id)">Seguir</button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 text-sm">
                            ou <Link class="text-primary hover:underline" href="/explore">explore comunidades em destaque</Link>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
