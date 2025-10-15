<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PostCard from '@/components/PostCard.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem, Post } from '@/types';

interface Props {
    posts: {
        data: Post[];
        links: any[];
        meta: any;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Explorar', href: '/explore' },
];
</script>

<template>
    <Head title="Explorar" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="mx-auto w-full max-w-3xl">
                <div class="space-y-4">
                    <PostCard v-for="post in props.posts.data" :key="post.id" :post="post" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
