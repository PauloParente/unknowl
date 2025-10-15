<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useAvatar } from '@/composables/useAvatar';
import { Pencil, Check, X, Pin, PinOff, Search } from 'lucide-vue-next';
import type { Post } from '@/types';

interface Props {
    communityId: number;
    pinnedPosts: Post[];
    allPosts: Post[];
    canEdit: boolean;
}

const props = defineProps<Props>();

const { getUserAvatarFallback } = useAvatar();

const isEditing = ref(false);
const localPinnedPosts = ref([...props.pinnedPosts]);
const searchQuery = ref('');

const form = useForm({
    pinned_post_ids: props.pinnedPosts.map(post => post.id),
});

// Watcher para atualizar localPinnedPosts quando props mudarem
watch(() => props.pinnedPosts, (newPinnedPosts) => {
    localPinnedPosts.value = [...newPinnedPosts];
}, { deep: true });

const startEditing = () => {
    isEditing.value = true;
    localPinnedPosts.value = [...props.pinnedPosts];
};

const cancelEditing = () => {
    isEditing.value = false;
    localPinnedPosts.value = [...props.pinnedPosts];
    searchQuery.value = '';
    form.reset();
};

const togglePinPost = (post: Post) => {
    const isPinned = localPinnedPosts.value.some(p => p.id === post.id);
    
    if (isPinned) {
        localPinnedPosts.value = localPinnedPosts.value.filter(p => p.id !== post.id);
    } else {
        // Limitar a 3 posts fixados
        if (localPinnedPosts.value.length < 3) {
            localPinnedPosts.value.push(post);
        }
    }
};

const isPostPinned = (post: Post) => {
    return localPinnedPosts.value.some(p => p.id === post.id);
};

const savePinnedPosts = () => {
    form.pinned_post_ids = localPinnedPosts.value.map(post => post.id);
    
    // Construir URL manualmente
    const url = `/communities/${props.communityId}/pinned-posts`;
    
    form.patch(url, {
        onSuccess: () => {
            isEditing.value = false;
            searchQuery.value = '';
            // Atualizar apenas os dados necessários sem recarregar a página
            router.visit(window.location.pathname, {
                method: 'get',
                preserveState: true,
                preserveScroll: true,
                only: ['posts', 'community']
            });
        },
        onError: (errors) => {
            console.error('Erro ao salvar posts fixados:', errors);
            // Em caso de erro, manter o modo de edição
        },
    });
};

// Filtrar posts baseado na busca
const filteredPosts = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.allPosts.slice(0, 20); // Limitar a 20 posts por padrão
    }
    
    const query = searchQuery.value.toLowerCase();
    return props.allPosts.filter(post => 
        post.title.toLowerCase().includes(query) ||
        post.text?.toLowerCase().includes(query) ||
        post.author.name.toLowerCase().includes(query)
    ).slice(0, 20);
});
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium">Posts fixados</h3>
            <Button
                v-if="canEdit && !isEditing"
                variant="ghost"
                size="sm"
                @click="startEditing"
                class="h-8 w-8 p-0"
            >
                <Pencil class="h-4 w-4" />
            </Button>
        </div>

        <div v-if="isEditing" class="space-y-3">
            <!-- Campo de busca -->
            <div class="relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="searchQuery"
                    placeholder="Buscar posts por título, conteúdo ou autor..."
                    class="pl-10"
                />
            </div>
            
            <div class="space-y-2">
                <div v-if="filteredPosts.length === 0" class="text-center py-4 text-sm text-muted-foreground">
                    Nenhum post encontrado para "{{ searchQuery }}"
                </div>
                <div
                    v-for="post in filteredPosts"
                    :key="post.id"
                    class="flex items-center gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border"
                >
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="togglePinPost(post)"
                        :disabled="!isPostPinned(post) && localPinnedPosts.length >= 3"
                        :class="[
                            'h-8 w-8 p-0 transition-all duration-200',
                            isPostPinned(post) 
                                ? 'bg-primary/10 hover:bg-primary/20' 
                                : 'hover:bg-muted'
                        ]"
                    >
                        <Pin 
                            v-if="isPostPinned(post)" 
                            class="h-4 w-4 text-primary animate-in zoom-in-50 duration-200" 
                        />
                        <PinOff 
                            v-else 
                            class="h-4 w-4 transition-colors duration-200" 
                        />
                    </Button>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <img :src="post.author.avatar" class="h-5 w-5 rounded" alt="" />
                            <span class="font-medium text-sm truncate">{{ post.title }}</span>
                        </div>
                        <p class="text-xs text-muted-foreground line-clamp-1 mt-1">
                            {{ post.text }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-xs text-muted-foreground">
                Máximo de 3 posts fixados. {{ localPinnedPosts.length }}/3 selecionados.
            </div>

            <div class="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    @click="cancelEditing"
                    :disabled="form.processing"
                    class="flex-1"
                >
                    <X class="h-4 w-4 mr-1" />
                    Cancelar
                </Button>
                <Button
                    size="sm"
                    @click="savePinnedPosts"
                    :disabled="form.processing"
                    class="flex-1"
                >
                    <Check v-if="!form.processing" class="h-4 w-4 mr-1" />
                    <div v-else class="h-4 w-4 mr-1 animate-spin rounded-full border-2 border-current border-t-transparent" />
                    {{ form.processing ? 'Salvando...' : 'Salvar' }}
                </Button>
            </div>
        </div>

        <div v-else>
            <div v-if="pinnedPosts.length > 0" class="space-y-3">
                <div
                    v-for="post in pinnedPosts"
                    :key="post.id"
                    class="rounded-lg border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border hover:bg-muted/50 transition-colors duration-200"
                >
                    <div class="flex items-start gap-3">
                        <Pin class="h-4 w-4 text-primary animate-pulse mt-0.5 flex-shrink-0" />
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <Avatar class="h-5 w-5 flex-shrink-0">
                                    <AvatarImage v-if="post.author.avatar" :src="`/storage/${post.author.avatar}`" :alt="post.author.name" />
                                    <AvatarFallback class="text-xs">
                                        {{ getUserAvatarFallback(post.author.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="font-medium truncate">{{ post.title }}</span>
                            </div>
                            <p class="text-muted-foreground line-clamp-2 text-xs leading-relaxed">{{ post.text }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground italic">
                Nenhum post fixado.
            </p>
        </div>
    </div>
</template>
