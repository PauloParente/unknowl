<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import { Plus, UserPlus, UserMinus, Settings } from 'lucide-vue-next';

interface Props {
    communityId: number;
    communityName: string;
    isMember: boolean;
    isOwner: boolean;
    isModerator: boolean;
    canCreatePost: boolean;
}

const props = defineProps<Props>();

const followForm = useForm({});

const toggleFollow = () => {
    if (props.isMember) {
        followForm.delete(route('communities.unfollow', props.communityId));
    } else {
        followForm.post(route('communities.follow', props.communityId));
    }
};

const shouldShowFollowButton = () => {
    return !props.isOwner && !props.isModerator;
};

const getFollowButtonText = () => {
    return props.isMember ? 'Deixar de Seguir' : 'Participar';
};

const getFollowButtonIcon = () => {
    return props.isMember ? UserMinus : UserPlus;
};
</script>

<template>
    <div class="flex items-center gap-2">
        <!-- Botão de Participar/Deixar de Seguir -->
        <Button
            v-if="shouldShowFollowButton()"
            @click="toggleFollow"
            :disabled="followForm.processing"
            :variant="isMember ? 'outline' : 'default'"
            class="text-sm"
        >
            <component :is="getFollowButtonIcon()" class="h-4 w-4 mr-1" />
            {{ getFollowButtonText() }}
        </Button>

        <!-- Botão de Criar Post -->
        <Button
            v-if="canCreatePost"
            as-child
            variant="outline"
            class="text-sm"
        >
            <Link :href="`/r/${communityName}/submit`">
                <Plus class="h-4 w-4 mr-1" />
                Criar post
            </Link>
        </Button>

        <!-- Botão de Gerenciar Comunidade (apenas para owners/admins) -->
        <Button
            v-if="isOwner || isModerator"
            as-child
            variant="outline"
            class="text-sm"
        >
            <Link :href="`/r/${communityName}/moderation`">
                <Settings class="h-4 w-4 mr-1" />
                Gerenciar
            </Link>
        </Button>
    </div>
</template>
