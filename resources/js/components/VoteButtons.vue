<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { ThumbsUp, ThumbsDown } from 'lucide-vue-next';

interface Props {
    itemId: number;
    itemType: 'post' | 'comment';
    currentScore: number;
    userVote?: 'like' | 'dislike' | null;
    authorId?: number;
    currentUserId?: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    scoreUpdated: [score: number];
    voteUpdated: [vote: 'like' | 'dislike' | null];
}>();

const isLoading = ref(false);
const error = ref<string | null>(null);
const localScore = ref(props.currentScore);
const localUserVote = ref<'like' | 'dislike' | null>(props.userVote || null);

// Estados para animações
const isLikeAnimating = ref(false);
const isDislikeAnimating = ref(false);

// Verificar se o usuário atual é o autor
const isAuthor = computed(() => {
    return props.authorId && props.currentUserId && props.authorId === props.currentUserId;
});

const likeButtonClass = computed(() => {
    const baseClass = 'flex items-center justify-center p-2 transition-all duration-200 ease-in-out transform cursor-pointer';
    const animationClass = isLikeAnimating.value ? 'scale-90' : 'scale-100 hover:scale-105';
    const pulseClass = localUserVote.value === 'like' ? 'animate-pulse' : '';
    
    if (localUserVote.value === 'like') {
        return `${baseClass} ${animationClass} ${pulseClass} text-green-700 hover:text-green-800 dark:text-green-300 dark:hover:text-green-200`;
    }
    
    return `${baseClass} ${animationClass} text-muted-foreground hover:text-foreground`;
});

const dislikeButtonClass = computed(() => {
    const baseClass = 'flex items-center justify-center p-2 transition-all duration-200 ease-in-out transform cursor-pointer';
    const animationClass = isDislikeAnimating.value ? 'scale-90' : 'scale-100 hover:scale-105';
    const pulseClass = localUserVote.value === 'dislike' ? 'animate-pulse' : '';
    
    if (localUserVote.value === 'dislike') {
        return `${baseClass} ${animationClass} ${pulseClass} text-red-700 hover:text-red-800 dark:text-red-300 dark:hover:text-red-200`;
    }
    
    return `${baseClass} ${animationClass} text-muted-foreground hover:text-foreground`;
});

const scoreClass = computed(() => {
    const baseClass = 'text-sm font-medium';
    
    if (localScore.value > 0) {
        return `${baseClass} text-green-700 dark:text-green-300`;
    } else if (localScore.value < 0) {
        return `${baseClass} text-red-700 dark:text-red-300`;
    }
    
    return `${baseClass} text-muted-foreground`;
});

async function vote(isLike: boolean) {
    if (isLoading.value) return;
    
    isLoading.value = true;
    error.value = null;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        console.log('CSRF Token:', csrfToken);
        console.log('URL:', `/${props.itemType}s/${props.itemId}/vote`);
        console.log('Data:', { is_like: isLike });
        
        const response = await fetch(`/${props.itemType}s/${props.itemId}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ is_like: isLike }),
        });
        
        const data = await response.json();
        
        if (data.success) {
            localScore.value = data.score;
            localUserVote.value = data.user_vote;
            
            emit('scoreUpdated', data.score);
            emit('voteUpdated', data.user_vote);
        } else {
            error.value = data.message || 'Erro ao votar';
        }
    } catch (err) {
        console.error('Erro ao votar:', err);
        error.value = 'Erro de conexão. Tente novamente.';
    } finally {
        isLoading.value = false;
    }
}

async function removeVote() {
    if (isLoading.value) return;
    
    isLoading.value = true;
    error.value = null;
    
    try {
        const response = await fetch(`/${props.itemType}s/${props.itemId}/vote`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        const data = await response.json();
        
        if (data.success) {
            localScore.value = data.score;
            localUserVote.value = data.user_vote;
            
            emit('scoreUpdated', data.score);
            emit('voteUpdated', data.user_vote);
        } else {
            error.value = data.message || 'Erro ao remover voto';
        }
    } catch (err) {
        console.error('Erro ao remover voto:', err);
        error.value = 'Erro de conexão. Tente novamente.';
    } finally {
        isLoading.value = false;
    }
}

// Funções para controlar animações
function triggerLikeAnimation() {
    isLikeAnimating.value = true;
    
    setTimeout(() => {
        isLikeAnimating.value = false;
    }, 150);
}

function triggerDislikeAnimation() {
    isDislikeAnimating.value = true;
    
    setTimeout(() => {
        isDislikeAnimating.value = false;
    }, 150);
}

function handleLike() {
    triggerLikeAnimation();
    
    if (localUserVote.value === 'like') {
        removeVote();
    } else {
        vote(true);
    }
}

function handleDislike() {
    triggerDislikeAnimation();
    
    if (localUserVote.value === 'dislike') {
        removeVote();
    } else {
        vote(false);
    }
}
</script>

<template>
    <div class="flex flex-col gap-1">
        <div class="flex items-center gap-2">
            <button
                v-if="!isAuthor"
                :class="likeButtonClass"
                :disabled="isLoading"
                @click="handleLike"
            >
                <ThumbsUp class="h-4 w-4 transition-transform duration-200" :class="localUserVote === 'like' ? 'animate-bounce' : ''" />
            </button>
            
            <button
                v-if="!isAuthor"
                :class="dislikeButtonClass"
                :disabled="isLoading"
                @click="handleDislike"
            >
                <ThumbsDown class="h-4 w-4 transition-transform duration-200" :class="localUserVote === 'dislike' ? 'animate-bounce' : ''" />
            </button>
            
            <span v-if="!isAuthor" class="text-muted-foreground">•</span>
            
            <span :class="scoreClass">
                {{ localScore }}
            </span>
        </div>
        
        <div v-if="error" class="text-xs text-red-600 dark:text-red-400 animate-in fade-in-0 slide-in-from-top-1 duration-200">
            {{ error }}
        </div>
    </div>
</template>
