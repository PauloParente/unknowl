<script setup lang="ts">
import { timeAgo } from '@/lib/utils';
import type { Comment } from '@/types';
import VoteButtons from '@/components/VoteButtons.vue';
import { usePage, router, Form, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { EllipsisVerticalIcon, PencilIcon, TrashIcon, CheckIcon, XMarkIcon, ChevronLeftIcon, ChevronRightIcon, FlagIcon, LinkIcon, BookmarkIcon, UserMinusIcon, ShareIcon, ClipboardDocumentIcon, EyeIcon, UserIcon } from '@heroicons/vue/24/outline';
import { ThumbsUp, ThumbsDown, MessageCircle } from 'lucide-vue-next';

const props = defineProps<{ comment: Comment }>();
const page = usePage();

// Estado de votação
const isVoting = ref(false);

// Classes para botões de votação
const likeButtonClass = computed(() => {
    const baseClass = 'flex items-center justify-center p-1.5 rounded-md transition-all duration-200 ease-in-out cursor-pointer';
    
    if (props.comment.user_vote === 'like') {
        return `${baseClass} text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300`;
    }
    
    return `${baseClass} text-muted-foreground hover:text-foreground`;
});

const dislikeButtonClass = computed(() => {
    const baseClass = 'flex items-center justify-center p-1.5 rounded-md transition-all duration-200 ease-in-out cursor-pointer';
    
    if (props.comment.user_vote === 'dislike') {
        return `${baseClass} text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300`;
    }
    
    return `${baseClass} text-muted-foreground hover:text-foreground`;
});

const scoreClass = computed(() => {
    const baseClass = 'text-sm font-medium';
    
    if (props.comment.score > 0) {
        return `${baseClass} text-green-600 dark:text-green-400`;
    } else if (props.comment.score < 0) {
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
        
        const response = await fetch(`/comments/${props.comment.id}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ is_like: isLike }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            props.comment.score = data.score;
            props.comment.user_vote = data.user_vote;
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
        const response = await fetch(`/comments/${props.comment.id}/vote`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        const data = await response.json();
        
        if (data.success) {
            props.comment.score = data.score;
            props.comment.user_vote = data.user_vote;
        }
    } catch (err) {
        console.error('Erro ao remover voto:', err);
    } finally {
        isVoting.value = false;
    }
}

function handleLike() {
    if (props.comment.user_vote === 'like') {
        removeVote();
    } else {
        vote(true);
    }
}

function handleDislike() {
    if (props.comment.user_vote === 'dislike') {
        removeVote();
    } else {
        vote(false);
    }
}

// Estado do menu
const isMenuOpen = ref(false);
const menuRef = ref<HTMLElement>();
const isEditing = ref(false);
const editText = ref('');
const isExpanded = ref(false);
const currentVersionIndex = ref(0);

// Estado do campo de resposta
const isReplying = ref(false);
const replyText = ref('');
const isReplyInputFocused = ref(false);

// Verificar se o usuário atual é o autor do comentário
const isAuthor = computed(() => {
    return page.props.auth.user.id === props.comment.author.id;
});

// Verificar se o comentário foi editado
const isEdited = computed(() => {
    if (!props.comment.updated_at || !props.comment.created_at) return false;
    
    const created = new Date(props.comment.created_at);
    const updated = new Date(props.comment.updated_at);
    
    // Considera editado se updated_at é pelo menos 1 segundo depois de created_at
    return updated.getTime() - created.getTime() > 1000;
});

// Computed properties para o carrossel
const allVersions = computed(() => {
    const versions = [];
    
    // Versão atual (mais recente)
    versions.push({
        text: props.comment.text,
        version_number: props.comment.edit_history.length + 1,
        is_current: true,
        created_at: props.comment.updated_at
    });
    
    // Histórico de edições (ordenado do mais recente para o mais antigo)
    props.comment.edit_history.forEach(edit => {
        versions.push({
            text: edit.text,
            version_number: edit.version_number,
            is_current: false,
            created_at: edit.created_at
        });
    });
    
    return versions;
});

const totalVersions = computed(() => allVersions.value.length);
const currentVersion = computed(() => allVersions.value[currentVersionIndex.value]);
const canGoPrevious = computed(() => currentVersionIndex.value > 0);
const canGoNext = computed(() => currentVersionIndex.value < totalVersions.value - 1);
const isOriginalVersion = computed(() => currentVersionIndex.value === totalVersions.value - 1);

// Funções do menu
const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};

const handleEdit = () => {
    editText.value = props.comment.text;
    isEditing.value = true;
    closeMenu();
};

const handleDelete = () => {
    console.log('Apagar comentário:', props.comment.id);
    closeMenu();
};

const handleReport = () => {
    console.log('Denunciar comentário:', props.comment.id);
    closeMenu();
};

const handleCopyLink = async () => {
    // Usar o ID do post da URL atual ou do contexto
    const postId = window.location.pathname.split('/posts/')[1]?.split('/')[0] || 'unknown';
    const url = `${window.location.origin}/posts/${postId}#comment-${props.comment.id}`;
    try {
        await navigator.clipboard.writeText(url);
        console.log('Link copiado para a área de transferência');
    } catch (err) {
        console.error('Erro ao copiar link:', err);
    }
    closeMenu();
};

const handleCopyText = async () => {
    try {
        await navigator.clipboard.writeText(props.comment.text);
        console.log('Texto copiado para a área de transferência');
    } catch (err) {
        console.error('Erro ao copiar texto:', err);
    }
    closeMenu();
};

const handleSave = () => {
    console.log('Salvar comentário:', props.comment.id);
    closeMenu();
};

const handleMuteAuthor = () => {
    console.log('Silenciar autor:', props.comment.author.id);
    closeMenu();
};

const handleViewProfile = () => {
    router.visit(`/u/${props.comment.author.name}`);
    closeMenu();
};

const handleViewPost = () => {
    // Usar o ID do post da URL atual ou do contexto
    const postId = window.location.pathname.split('/posts/')[1]?.split('/')[0] || 'unknown';
    router.visit(`/posts/${postId}`);
    closeMenu();
};

const confirmEdit = () => {
    const next = editText.value.trim();
    if (!next) return;
    
    // Enviar para o backend
    router.patch(`/comments/${props.comment.id}`, {
        text: next
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        }
    });
};

const cancelEdit = () => {
    editText.value = props.comment.text;
    isEditing.value = false;
};

// Função para alternar expansão do comentário
const toggleExpansion = () => {
    isExpanded.value = !isExpanded.value;
    if (isExpanded.value) {
        currentVersionIndex.value = 0; // Sempre começar na versão atual
    }
};

// Funções para navegar no carrossel
const goToPreviousVersion = () => {
    if (canGoPrevious.value) {
        currentVersionIndex.value--;
    }
};

const goToNextVersion = () => {
    if (canGoNext.value) {
        currentVersionIndex.value++;
    }
};

// Funções para o campo de resposta
const handleReplyInputFocus = () => {
    isReplyInputFocused.value = true;
};

const handleReplyInputBlur = () => {
    // Só remove o foco se o campo estiver vazio
    if (!replyText.value.trim()) {
        isReplyInputFocused.value = false;
    }
};

const toggleReply = () => {
    isReplying.value = !isReplying.value;
    if (!isReplying.value) {
        replyText.value = '';
        isReplyInputFocused.value = false;
    }
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
</script>

<template>
    <div class="rounded-lg border border-sidebar-border/70 p-2 sm:p-3 dark:border-sidebar-border">
        <div class="mb-2 flex items-center justify-between gap-2">
            <div class="flex items-center gap-1 sm:gap-2 text-xs sm:text-sm text-muted-foreground min-w-0 flex-1">
                <img v-if="comment.author.avatar" :src="comment.author.avatar" alt="avatar" class="h-4 w-4 sm:h-5 sm:w-5 rounded-full flex-shrink-0" />
                <Link 
                    :href="`/u/${comment.author.name}`"
                    class="font-medium text-foreground hover:text-blue-600 dark:hover:text-blue-400 transition-colors cursor-pointer truncate"
                >
                    {{ comment.author.name }}
                </Link>
                <span class="hidden sm:inline">•</span>
                <span class="text-xs">{{ timeAgo(comment.created_at) }}</span>
                <span v-if="allVersions.length > 1" class="hidden sm:inline">•</span>
                <button
                    v-if="allVersions.length > 1"
                    @click="toggleExpansion"
                    class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 px-1.5 sm:px-2 py-0.5 rounded-full hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-all duration-200 cursor-pointer transform hover:scale-105 active:scale-95 flex-shrink-0"
                    :class="{
                        'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400': isExpanded
                    }"
                    :title="isExpanded ? 'Ocultar histórico de versões' : 'Ver histórico de versões'"
                >
                    <span class="hidden sm:inline">editado</span>
                    <span class="sm:hidden">✏️</span>
                </button>
            </div>
            
            <!-- Botão de Menu -->
            <div class="relative" ref="menuRef">
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
                        
                        <!-- Navegação -->
                        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                        <button
                            v-if="!isAuthor"
                            @click="handleViewProfile"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                        >
                            <UserIcon class="h-4 w-4" />
                            Ver perfil
                        </button>
                        
                        <button
                            @click="handleViewPost"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/50 flex items-center gap-2 cursor-pointer"
                        >
                            <EyeIcon class="h-4 w-4" />
                            Ver post
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
        </div>
        <div v-if="!isEditing">
            <!-- Comentário atual -->
            <p class="whitespace-pre-line text-sm leading-6">{{ comment.text }}</p>
            
            <!-- Histórico de versões (quando expandido) com animação fluida -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 scale-y-0 origin-top"
                enter-to-class="opacity-100 scale-y-100 origin-top"
                leave-active-class="transition-all duration-300 ease-in"
                leave-from-class="opacity-100 scale-y-100 origin-top"
                leave-to-class="opacity-0 scale-y-0 origin-top"
            >
                <div v-if="isExpanded && allVersions.length > 1" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <!-- Cabeçalho do carrossel -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                Versão {{ currentVersionIndex + 1 }}/{{ totalVersions }}
                            </span>
                            <span v-if="currentVersion.is_current" class="text-xs bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400 px-2 py-0.5 rounded-full">
                                atual
                            </span>
                            <span v-else-if="isOriginalVersion" class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-full">
                                original
                            </span>
                        </div>
                        
                        <!-- Controles de navegação -->
                        <div class="flex items-center gap-1">
                            <button
                                @click="goToPreviousVersion"
                                :disabled="!canGoPrevious"
                                class="p-1 rounded-full hover:bg-gray-50 dark:hover:bg-gray-800/50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                title="Versão anterior"
                            >
                                <ChevronLeftIcon class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                            </button>
                            
                            <button
                                @click="goToNextVersion"
                                :disabled="!canGoNext"
                                class="p-1 rounded-full hover:bg-gray-50 dark:hover:bg-gray-800/50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                title="Próxima versão"
                            >
                                <ChevronRightIcon class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                            </button>
                        </div>
                    </div>
                    
                    <!-- Conteúdo da versão atual com animação horizontal -->
                    <Transition
                        mode="out-in"
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 transform translate-x-4"
                        enter-to-class="opacity-100 transform translate-x-0"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 transform translate-x-0"
                        leave-to-class="opacity-0 transform -translate-x-4"
                    >
                        <p 
                            :key="currentVersionIndex"
                            class="whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-gray-400 italic"
                        >
                            {{ currentVersion.text }}
                        </p>
                    </Transition>
                </div>
            </Transition>
        </div>

        <!-- Modo de edição, com UI igual ao campo de comentário do post -->
        <div v-else class="mt-2">
            <div class="relative">
                <textarea
                    v-model="editText"
                    rows="2"
                    placeholder="Editar comentário..."
                    class="w-full px-3 py-2 pr-20 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white resize-none transition-all duration-200"
                    @keydown.ctrl.enter.prevent="confirmEdit"
                    @keydown.esc.prevent="cancelEdit"
                />

                <!-- Botões dentro do campo -->
                <div class="absolute bottom-2 right-2 flex items-center gap-2">
                    <button
                        type="button"
                        @click="cancelEdit"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :title="'Cancelar'"
                    >
                        <XMarkIcon class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        @click="confirmEdit"
                        :disabled="!editText.trim()"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        :title="'Confirmar'"
                    >
                        <CheckIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
        <div class="mt-3 flex items-center justify-between gap-2">
            <!-- Seção de votação -->
            <div v-if="page.props.auth.user.id !== comment.author.id" class="flex items-center gap-1 sm:gap-1.5">
                <!-- Botão de like -->
                <button
                    :class="likeButtonClass"
                    :disabled="isVoting"
                    @click="handleLike"
                    class="group"
                >
                    <ThumbsUp class="h-3 w-3 sm:h-4 sm:w-4 transition-all duration-200 group-hover:scale-110" :class="comment.user_vote === 'like' ? 'animate-bounce' : ''" />
                </button>
                
                <!-- Pontuação -->
                <span :class="scoreClass" class="min-w-[1.5rem] sm:min-w-[2rem] text-center text-xs sm:text-sm">
                    {{ comment.score }}
                </span>
                
                <!-- Botão de dislike -->
                <button
                    :class="dislikeButtonClass"
                    :disabled="isVoting"
                    @click="handleDislike"
                    class="group"
                >
                    <ThumbsDown class="h-3 w-3 sm:h-4 sm:w-4 transition-all duration-200 group-hover:scale-110" :class="comment.user_vote === 'dislike' ? 'animate-bounce' : ''" />
                </button>
            </div>
            
            <!-- Seção de responder -->
            <button
                @click="toggleReply"
                class="flex items-center gap-1 sm:gap-1.5 text-xs sm:text-sm text-muted-foreground hover:text-foreground transition-colors cursor-pointer group p-1 sm:p-1.5 rounded-md"
                :title="'Responder'"
            >
                <MessageCircle class="h-3 w-3 sm:h-4 sm:w-4 transition-all duration-200 group-hover:scale-110" />
                <span class="font-medium hidden sm:inline">Responder</span>
                <span class="font-medium sm:hidden">💬</span>
            </button>
        </div>

        <!-- Campo de resposta com animação -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-y-0 origin-top"
            enter-to-class="opacity-100 scale-y-100 origin-top"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 scale-y-100 origin-top"
            leave-to-class="opacity-0 scale-y-0 origin-top"
        >
            <div v-if="isReplying" class="mt-3 border-t border-sidebar-border/70 pt-3 dark:border-sidebar-border">
            <Form
                :action="`/comments/${comment.id}/replies`"
                method="post"
                #default="{ processing, submit }"
                @success="() => { replyText = ''; isReplyInputFocused = false; isReplying = false; }"
            >
                <div class="space-y-3">
                    <div class="relative">
                        <textarea
                            v-model="replyText"
                            name="text"
                            rows="2"
                            placeholder="Responder ao comentário..."
                            class="w-full px-3 py-2 pr-12 border border-input rounded-lg bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring/50 focus:border-ring resize-none transition-all duration-200"
                            :class="{
                                'h-12': !isReplyInputFocused && !replyText.trim(),
                                'h-20': isReplyInputFocused || replyText.trim()
                            }"
                            @focus="handleReplyInputFocus"
                            @blur="handleReplyInputBlur"
                            @keydown.ctrl.enter.prevent="submit()"
                            required
                        />
                        
                        <!-- Botão de responder (aparece apenas quando ativo) -->
                        <button
                            v-if="isReplyInputFocused || replyText.trim()"
                            type="submit"
                            :disabled="!replyText.trim() || processing"
                            class="absolute bottom-3 right-3 inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                        >
                            <ChatBubbleLeftRightIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </Form>
            </div>
        </Transition>

        <div v-if="comment.replies?.length" class="mt-3 space-y-3 border-l pl-3">
            <CommentItem v-for="reply in comment.replies" :key="reply.id" :comment="reply" />
        </div>
    </div>
</template>



