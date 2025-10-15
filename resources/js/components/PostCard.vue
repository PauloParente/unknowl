<script setup lang="ts">
import { timeAgo } from '@/lib/utils';
import { type Post } from '@/types';
import { Link, router, usePage, Form } from '@inertiajs/vue3';
import { show as postShow } from '@/routes/posts/index';
import { show as communityShow } from '@/routes/communities/index';
import { show as userShow } from '@/routes/users/index';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { X, Pin, MessageCircle, ThumbsUp, ThumbsDown } from 'lucide-vue-next';
import { EllipsisVerticalIcon, PencilIcon, TrashIcon, FlagIcon, LinkIcon, BookmarkIcon, UserMinusIcon, ShareIcon, ClipboardDocumentIcon, EyeIcon, ChartBarIcon } from '@heroicons/vue/24/outline';
import VoteButtons from '@/components/VoteButtons.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useAvatar } from '@/composables/useAvatar';

const props = defineProps<{ post: Post; showCommentForm?: boolean }>();

// Extrair nome da comunidade (remover r/ do início)
const getCommunityName = (communityName: string) => {
    return communityName.startsWith('r/') ? communityName.substring(2) : communityName;
};

// Removido fallback hardcoded - usando AvatarFallback com iniciais

// Composables
const { getAvatarUrl, shouldShowAvatar, getUserAvatarFallback, getCommunityAvatarFallback } = useAvatar();

const page = usePage();

// Estado do menu
const isMenuOpen = ref(false);
const menuRef = ref<HTMLElement>();

// Verificar se o usuário atual é o autor do post
const isAuthor = computed(() => {
    return page.props.auth.user.id === props.post.author.id;
});

// Estado de votação
const isVoting = ref(false);

// Classes para botões de votação
const likeButtonClass = computed(() => {
    const baseClass = 'flex items-center justify-center p-1.5 rounded-md transition-all duration-200 ease-in-out cursor-pointer';
    
    if (props.post.user_vote === 'like') {
        return `${baseClass} text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300`;
    }
    
    return `${baseClass} text-muted-foreground hover:text-foreground`;
});

const dislikeButtonClass = computed(() => {
    const baseClass = 'flex items-center justify-center p-1.5 rounded-md transition-all duration-200 ease-in-out cursor-pointer';
    
    if (props.post.user_vote === 'dislike') {
        return `${baseClass} text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300`;
    }
    
    return `${baseClass} text-muted-foreground hover:text-foreground`;
});

const scoreClass = computed(() => {
    const baseClass = 'text-sm font-medium';
    
    if (props.post.score > 0) {
        return `${baseClass} text-green-600 dark:text-green-400`;
    } else if (props.post.score < 0) {
        return `${baseClass} text-red-600 dark:text-red-400`;
    }
    
    return `${baseClass} text-muted-foreground`;
});

// Funções de votação
async function vote(isLike: boolean) {
    if (isVoting.value) return;
    
    isVoting.value = true;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        
        const response = await fetch(`/posts/${props.post.id}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ is_like: isLike }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            props.post.score = data.score;
            props.post.user_vote = data.user_vote;
        }
    } catch (err) {
        console.error('Erro ao votar:', err);
    } finally {
        isVoting.value = false;
    }
}

async function removeVote() {
    if (isVoting.value) return;
    
    isVoting.value = true;
    
    try {
        const response = await fetch(`/posts/${props.post.id}/vote`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        const data = await response.json();
        
        if (data.success) {
            props.post.score = data.score;
            props.post.user_vote = data.user_vote;
        }
    } catch (err) {
        console.error('Erro ao remover voto:', err);
    } finally {
        isVoting.value = false;
    }
}

function handleLike() {
    if (props.post.user_vote === 'like') {
        removeVote();
    } else {
        vote(true);
    }
}

function handleDislike() {
    if (props.post.user_vote === 'dislike') {
        removeVote();
    } else {
        vote(false);
    }
}

// Funções do menu
const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};

const handleEdit = () => {
    console.log('Editar post:', props.post.id);
    closeMenu();
};

const handleDelete = () => {
    console.log('Apagar post:', props.post.id);
    closeMenu();
};

const handleReport = () => {
    console.log('Denunciar post:', props.post.id);
    closeMenu();
};

const handleCopyLink = async () => {
    const url = `${window.location.origin}/posts/${props.post.id}`;
    try {
        await navigator.clipboard.writeText(url);
        console.log('Link copiado para a área de transferência');
    } catch (err) {
        console.error('Erro ao copiar link:', err);
    }
    closeMenu();
};

const handleCopyText = async () => {
    const text = `${props.post.title}\n\n${props.post.text || ''}`;
    try {
        await navigator.clipboard.writeText(text);
        console.log('Texto copiado para a área de transferência');
    } catch (err) {
        console.error('Erro ao copiar texto:', err);
    }
    closeMenu();
};

const handleSave = () => {
    console.log('Salvar post:', props.post.id);
    closeMenu();
};

const handleMuteAuthor = () => {
    console.log('Silenciar autor:', props.post.author.id);
    closeMenu();
};

const handleShare = () => {
    console.log('Compartilhar post:', props.post.id);
    closeMenu();
};

const handleViewStats = () => {
    console.log('Ver estatísticas do post:', props.post.id);
    closeMenu();
};

// Fechar menu ao clicar fora
const handleClickOutside = (event: Event) => {
    if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
        closeMenu();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Modal de imagem
const imageModalOpen = ref(false);
const currentImageIndex = ref(0);
const currentImageUrl = ref('');
const currentPostMediaUrls = ref<string[]>([]);

// Campo de comentário
const commentText = ref('');
const isCommentInputFocused = ref(false);


function openImageModal(imageUrl: string, index: number, allMediaUrls?: string[], event?: Event) {
    // Prevenir qualquer comportamento padrão do evento
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Prevenir abertura se não há URL válida
    if (!imageUrl) return;
    
    currentImageUrl.value = imageUrl;
    currentImageIndex.value = index;
    currentPostMediaUrls.value = allMediaUrls || [imageUrl];
    imageModalOpen.value = true;
}

function closeImageModal() {
    imageModalOpen.value = false;
    currentImageUrl.value = '';
    currentImageIndex.value = 0;
    currentPostMediaUrls.value = [];
}

function nextImage() {
    if (currentImageIndex.value < currentPostMediaUrls.value.length - 1) {
        currentImageIndex.value++;
        currentImageUrl.value = currentPostMediaUrls.value[currentImageIndex.value];
    }
}

function prevImage() {
    if (currentImageIndex.value > 0) {
        currentImageIndex.value--;
        currentImageUrl.value = currentPostMediaUrls.value[currentImageIndex.value];
    }
}

function handleImageError(event: Event) {
    const img = event.target as HTMLImageElement;
    img.style.display = 'none';
    // Opcional: mostrar um placeholder de imagem quebrada
}

// Suporte a teclado para o modal
function handleKeydown(event: KeyboardEvent) {
    if (!imageModalOpen.value) return;
    
    switch (event.key) {
        case 'Escape':
            closeImageModal();
            break;
        case 'ArrowLeft':
            if (currentPostMediaUrls.value.length > 1) {
                prevImage();
            }
            break;
        case 'ArrowRight':
            if (currentPostMediaUrls.value.length > 1) {
                nextImage();
            }
            break;
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

// Funções para o campo de comentário

const handleCommentInputFocus = () => {
    isCommentInputFocused.value = true;
};

const handleCommentInputBlur = () => {
    // Só remove o foco se o campo estiver vazio
    if (!commentText.value.trim()) {
        isCommentInputFocused.value = false;
    }
};
</script>

<template>
    <article class="rounded-xl border border-sidebar-border/70 bg-background p-3 sm:p-4 dark:border-sidebar-border">
        <header class="mb-3 flex items-start justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <Avatar class="h-6 w-6 flex-shrink-0">
                    <AvatarImage 
                        v-if="shouldShowAvatar(post.community.avatar)" 
                        :src="getAvatarUrl(post.community.avatar)!" 
                        :alt="post.community.name" 
                    />
                    <AvatarFallback class="rounded bg-blue-500 text-white text-xs">
                        {{ getCommunityAvatarFallback(post.community.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1 sm:gap-2 text-sm text-muted-foreground">
                        <Link class="font-medium text-foreground hover:underline truncate" :href="communityShow(getCommunityName(post.community.name))">{{ post.community.name }}</Link>
                        <span class="hidden sm:inline">•</span>
                        <span class="text-xs sm:text-sm">{{ timeAgo(post.created_at) }}</span>
                        <!-- Indicador de post fixado -->
                        <div v-if="post.is_pinned" class="flex items-center gap-1 text-primary">
                            <Pin class="h-3 w-3" />
                            <span class="text-xs font-medium hidden sm:inline">FIXADO</span>
                            <span class="text-xs font-medium sm:hidden">📌</span>
                        </div>
                    </div>
                    <div class="mt-1 flex items-center gap-1 sm:gap-2 text-xs text-muted-foreground">
                        <Avatar class="h-4 w-4 overflow-hidden rounded-full flex-shrink-0">
                            <AvatarImage 
                                v-if="shouldShowAvatar(post.author.avatar)" 
                                :src="getAvatarUrl(post.author.avatar)!" 
                                :alt="post.author.name" 
                            />
                            <AvatarFallback class="rounded-full bg-blue-500 text-white text-xs">
                                {{ getUserAvatarFallback(post.author.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <span class="hidden sm:inline">por</span>
                        <Link 
                            :href="userShow(post.author.name).url" 
                            class="font-medium text-foreground hover:text-primary hover:underline truncate"
                        >
                            u/{{ post.author.name }}
                        </Link>
                    </div>
                </div>
            </div>
            <!-- Menu de opções -->
            <div class="relative shrink-0" ref="menuRef">
                <button
                    @click="toggleMenu"
                    class="p-1 rounded-full transition-all duration-200 cursor-pointer group"
                >
                    <EllipsisVerticalIcon class="h-4 w-4 text-gray-500 dark:text-gray-400 transition-all duration-200 group-hover:scale-110" />
                </button>
                
                <!-- Submenu -->
                <Transition
                    enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-[-8px]"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-[-8px]"
                >
                    <div
                        v-if="isMenuOpen"
                        class="absolute right-0 top-8 z-10 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg py-1"
                    >
                        <!-- Ações do autor -->
                        <template v-if="isAuthor">
                            <button
                                @click="handleEdit"
                                class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                            >
                                <PencilIcon class="h-4 w-4" />
                                Editar
                            </button>
                            
                            <button
                                @click="handleViewStats"
                                class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                            >
                                <ChartBarIcon class="h-4 w-4" />
                                Estatísticas
                            </button>
                            
                            <button
                                @click="handleDelete"
                                class="w-full px-3 py-2 text-left text-sm text-red-600 dark:text-red-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                            >
                                <TrashIcon class="h-4 w-4" />
                                Apagar
                            </button>
                        </template>
                        
                        <!-- Compartilhamento -->
                        <div v-if="!isAuthor" class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                        <button
                            @click="handleCopyLink"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                        >
                            <LinkIcon class="h-4 w-4" />
                            Copiar link
                        </button>
                        
                        <button
                            @click="handleCopyText"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                        >
                            <ClipboardDocumentIcon class="h-4 w-4" />
                            Copiar texto
                        </button>
                        
                        <button
                            @click="handleShare"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                        >
                            <ShareIcon class="h-4 w-4" />
                            Compartilhar
                        </button>
                        
                        <!-- Controle de conteúdo -->
                        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                        <button
                            @click="handleSave"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                        >
                            <BookmarkIcon class="h-4 w-4" />
                            Salvar
                        </button>
                        
                        <button
                            v-if="!isAuthor"
                            @click="handleMuteAuthor"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                        >
                            <UserMinusIcon class="h-4 w-4" />
                            Silenciar autor
                        </button>
                        
                        <!-- Denunciar -->
                        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                        <button
                            @click="handleReport"
                            class="w-full px-3 py-2 text-left text-sm text-orange-600 dark:text-orange-400 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                        >
                            <FlagIcon class="h-4 w-4" />
                            Denunciar
                        </button>
                    </div>
                </Transition>
            </div>
        </header>

        <!-- Conteúdo clicável do post (título, texto, footer) -->
        <Link :href="postShow(post.id)" class="block">
            <h2 class="mb-2 text-lg font-semibold hover:text-primary">{{ post.title }}</h2>
            <p v-if="post.text" class="mb-3 whitespace-pre-line text-sm leading-6">{{ post.text }}</p>
        </Link>

        <!-- Imagens separadas do link (para evitar conflitos) -->
        <div v-if="post.imageUrl || (post.mediaUrls && post.mediaUrls.length > 0)" class="mb-3" @click.stop>
            <!-- Se há múltiplas imagens, mostrar em grid responsivo -->
            <div v-if="post.mediaUrls && post.mediaUrls.length > 1" class="grid gap-2 rounded-lg overflow-hidden" :class="{
                'grid-cols-1': post.mediaUrls.length === 1,
                'grid-cols-2': post.mediaUrls.length === 2,
                'grid-cols-2 md:grid-cols-3': post.mediaUrls.length >= 3,
                'grid-cols-1 sm:grid-cols-2 md:grid-cols-3': post.mediaUrls.length >= 6
            }">
                <img 
                    v-for="(mediaUrl, index) in post.mediaUrls.slice(0, 6)" 
                    :key="index"
                    :src="mediaUrl" 
                    :alt="`Imagem ${index + 1} do post`" 
                    class="h-32 sm:h-48 w-full object-cover hover:opacity-90 transition-opacity cursor-pointer"
                    @click="openImageModal(mediaUrl, index, post.mediaUrls, $event)"
                />
                <!-- Mostrar indicador se há mais imagens -->
                <div v-if="post.mediaUrls.length > 6" class="relative h-32 sm:h-48 w-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                    <span class="text-sm sm:text-lg font-semibold text-gray-600 dark:text-gray-400">+{{ post.mediaUrls.length - 6 }} mais</span>
                </div>
            </div>
            <!-- Se há apenas uma imagem, mostrar em tamanho maior -->
            <img 
                v-else
                :src="post.imageUrl || post.mediaUrls?.[0]" 
                :alt="'Imagem do post'" 
                class="max-h-64 sm:max-h-96 w-full rounded-lg object-cover cursor-pointer hover:opacity-90 transition-opacity"
                @click="openImageModal(post.imageUrl || post.mediaUrls?.[0] || '', 0, post.mediaUrls, $event)"
                @error="handleImageError"
            />
        </div>

        <footer class="mt-3 flex items-center justify-between gap-2">
            <!-- Seção de votação -->
            <div v-if="page.props.auth.user.id !== post.author.id" class="flex items-center gap-1 sm:gap-1.5">
                <!-- Botão de like -->
                <button
                    :class="likeButtonClass"
                    :disabled="isVoting"
                    @click="handleLike"
                    class="group"
                >
                    <ThumbsUp class="h-3 w-3 sm:h-4 sm:w-4 transition-all duration-200 group-hover:scale-110" :class="post.user_vote === 'like' ? 'animate-bounce' : ''" />
                </button>
                
                <!-- Pontuação -->
                <span :class="scoreClass" class="min-w-[1.5rem] sm:min-w-[2rem] text-center text-xs sm:text-sm">
                    {{ post.score }}
                </span>
                
                <!-- Botão de dislike -->
                <button
                    :class="dislikeButtonClass"
                    :disabled="isVoting"
                    @click="handleDislike"
                    class="group"
                >
                    <ThumbsDown class="h-3 w-3 sm:h-4 sm:w-4 transition-all duration-200 group-hover:scale-110" :class="post.user_vote === 'dislike' ? 'animate-bounce' : ''" />
                </button>
            </div>
            
            <!-- Seção de comentários -->
            <div class="flex items-center gap-1 sm:gap-1.5 text-xs sm:text-sm text-muted-foreground hover:text-foreground transition-colors cursor-pointer group p-1 sm:p-1.5 rounded-md">
                <MessageCircle class="h-3 w-3 sm:h-4 sm:w-4 transition-all duration-200 group-hover:scale-110" />
                <span class="font-medium">{{ post.comments_count }}</span>
            </div>
        </footer>

        <!-- Campo de Comentário (apenas na página de detalhes) -->
        <div v-if="showCommentForm" class="mt-4 border-t border-sidebar-border/70 pt-4 dark:border-sidebar-border">
            <Form
                :action="`/posts/${post.id}/comments`"
                method="post"
                #default="{ processing, submit }"
                @success="() => { commentText = ''; isCommentInputFocused = false; }"
            >
                <div class="space-y-3">
                    <div class="relative">
                        <textarea
                            v-model="commentText"
                            name="text"
                            rows="2"
                            placeholder="Adicionar comentário..."
                            class="w-full px-3 py-2 pr-12 border border-input rounded-lg bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/50 focus:border-ring resize-none transition-all duration-200"
                            :class="{
                                'h-12': !isCommentInputFocused && !commentText.trim(),
                                'h-20': isCommentInputFocused || commentText.trim()
                            }"
                            @focus="handleCommentInputFocus"
                            @blur="handleCommentInputBlur"
                            @keydown.ctrl.enter.prevent="submit()"
                            required
                        />
                        
                        <!-- Botão de comentar (aparece apenas quando ativo) -->
                        <button
                            v-if="isCommentInputFocused || commentText.trim()"
                            type="submit"
                            :disabled="!commentText.trim() || processing"
                            class="absolute bottom-3 right-3 inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                        >
                            <MessageCircle class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </Form>
        </div>
    </article>

    <!-- Modal de imagem customizado -->
    <div v-if="imageModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 animate-in fade-in duration-200" @click="closeImageModal">
        <div class="relative max-w-4xl max-h-[90vh] mx-4 animate-in zoom-in-95 duration-200" @click.stop>
            <!-- Botão de fechar -->
            <button 
                @click="closeImageModal"
                class="absolute top-4 right-4 z-10 rounded-full bg-black/50 p-2 text-white hover:bg-black/70 transition-colors"
            >
                <X class="h-6 w-6" />
            </button>

            <!-- Imagem -->
            <img 
                :src="currentImageUrl" 
                :alt="'Imagem do post'"
                class="max-h-[80vh] w-full object-contain rounded-lg"
                @click.stop
            />

            <!-- Navegação entre imagens (se houver múltiplas) -->
            <div v-if="currentPostMediaUrls.length > 1" class="absolute inset-y-0 left-0 right-0 flex items-center justify-between p-4">
                <button 
                    v-if="currentImageIndex > 0"
                    @click="prevImage"
                    class="rounded-full bg-black/50 p-2 text-white hover:bg-black/70 transition-colors"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <button 
                    v-if="currentImageIndex < currentPostMediaUrls.length - 1"
                    @click="nextImage"
                    class="rounded-full bg-black/50 p-2 text-white hover:bg-black/70 transition-colors ml-auto"
                >
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Indicador de posição -->
            <div v-if="currentPostMediaUrls.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black/50 text-white px-3 py-1 rounded-full text-sm">
                {{ currentImageIndex + 1 }} / {{ currentPostMediaUrls.length }}
            </div>
        </div>
    </div>
</template>


