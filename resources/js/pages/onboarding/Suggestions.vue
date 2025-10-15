<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import type { BreadcrumbItem, Community } from '@/types';

interface Props {
    suggestions: Community[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sugestões de comunidades', href: '/onboarding/suggestions' },
];

function getCommunityName(name: string) { return name.startsWith('r/') ? name.slice(2) : name; }
function follow(communityId: number) {
    router.post(`/communities/${communityId}/follow`, {}, { preserveScroll: true, onFinish: () => router.reload() });
}
</script>

<template>
    <Head title="Sugestões" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-6">
            <div class="rounded-xl border border-sidebar-border/70 bg-background p-8 text-center dark:border-sidebar-border">
                <h1 class="text-2xl font-semibold">Bem-vindo(a) ao MyApp</h1>
                <p class="mt-2 text-muted-foreground">Escolha algumas comunidades para começar sua experiência.</p>

                <div class="mx-auto mt-6 grid max-w-3xl grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="c in props.suggestions"
                        :key="c.id"
                        class="flex items-center justify-between gap-3 rounded-lg border border-sidebar-border/70 bg-card p-3 text-left dark:border-sidebar-border"
                    >
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ c.name }}</div>
                            <div class="truncate text-sm text-muted-foreground">{{ c.title }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link class="text-sm text-muted-foreground hover:underline" :href="`/r/${getCommunityName(c.name)}`">Ver</Link>
                            <button class="rounded-md bg-primary px-2 py-1 text-xs font-medium text-primary-foreground hover:opacity-90" @click="follow(c.id)">Seguir</button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 text-sm">
                    ou <Link class="text-primary hover:underline" href="/explore">explore comunidades em destaque</Link>
                </div>

                <div class="mt-8">
                    <Link class="rounded-md border px-3 py-1.5 text-sm hover:bg-accent" href="/dashboard">Ir para a página inicial</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


