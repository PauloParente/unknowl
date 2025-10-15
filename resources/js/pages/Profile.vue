<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = page.props.auth.user as { name: string; bio?: string };

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profile',
        href: '/profile',
    },
];
</script>

<template>
    <Head title="Profile" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                <h1 class="text-2xl font-semibold">{{ user.name }}</h1>
                <p class="mt-2 text-sm text-muted-foreground" v-if="user.bio && user.bio.length">
                    {{ user.bio }}
                </p>
                <p class="mt-2 text-sm text-muted-foreground" v-else>
                    No bio provided.
                </p>
            </div>
        </div>
    </AppLayout>
    
</template>
