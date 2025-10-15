<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import type { BreadcrumbItem, Community } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useAvatar } from '@/composables/useAvatar';

interface Props {
    communities: Community[];
    count: number;
}

const props = defineProps<Props>();

// Composables
const { getCommunityAvatarFallback } = useAvatar();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Minhas comunidades', href: '/communities/following' },
];
</script>

<template>
    <Head title="Minhas comunidades" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <h1 class="text-xl font-semibold">Minhas comunidades</h1>
            <p class="mt-2 text-sm text-muted-foreground">Você segue {{ props.count }} {{ props.count === 1 ? 'comunidade' : 'comunidades' }}.</p>

            <div class="mt-4 grid grid-cols-1 gap-2">
                <template v-if="props.communities && props.communities.length">
                    <Link
                        v-for="c in props.communities"
                        :key="c.id"
                        :href="`/r/${c.name?.startsWith('r/') ? c.name.slice(2) : c.name}`"
                        class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 bg-background p-3 hover:bg-accent dark:border-sidebar-border"
                    >
                        <Avatar class="h-8 w-8">
                            <AvatarImage 
                                v-if="c.avatar" 
                                :src="`/storage/${c.avatar}`" 
                                :alt="c.name" 
                            />
                            <AvatarFallback class="rounded bg-blue-500 text-white">
                                {{ getCommunityAvatarFallback(c.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ c.name }}</div>
                            <div class="truncate text-sm text-muted-foreground">{{ c.title }}</div>
                        </div>
                    </Link>
                </template>
                <div v-else class="text-sm text-muted-foreground">Você ainda não segue nenhuma comunidade.</div>
            </div>
        </div>
    </AppLayout>
</template>
