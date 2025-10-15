<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Popular', href: '/popular' },
];
</script>

<template>
    <Head title="Popular" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <h1 class="text-xl font-semibold">Popular</h1>
            <p class="mt-2 text-sm text-muted-foreground">Posts mais relevantes e comentados recentemente.</p>
        </div>
    </AppLayout>
</template>
