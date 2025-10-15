<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useAvatar } from '@/composables/useAvatar';
import { Upload, X, Camera } from 'lucide-vue-next';

interface Props {
    user: {
        name: string;
        avatar?: string;
        email: string;
    };
    currentAvatar?: string;
}

const props = defineProps<Props>();

const { getUserAvatarFallback } = useAvatar();

const fileInput = ref<HTMLInputElement>();
const isUploading = ref(false);

const avatarUrl = computed(() => {
    if (props.currentAvatar) {
        return `/storage/${props.currentAvatar}`;
    }
    return props.user.avatar ? `/storage/${props.user.avatar}` : null;
});

const initials = computed(() => getUserAvatarFallback(props.user.name));

const selectFile = () => {
    fileInput.value?.click();
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (!file) return;

    // Validar tamanho do arquivo (2MB)
    if (file.size > 2 * 1024 * 1024) {
        alert('O arquivo deve ter no máximo 2MB.');
        return;
    }

    // Validar tipo do arquivo
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        alert('Formato de arquivo não suportado. Use JPEG, PNG, JPG, GIF ou WebP.');
        return;
    }

    uploadFile(file);
};

const uploadFile = (file: File) => {
    isUploading.value = true;
    
    const formData = new FormData();
    formData.append('avatar', file);

    router.post('/settings/profile/avatar', formData, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            isUploading.value = false;
            // Limpar o input para permitir o mesmo arquivo novamente
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const deleteAvatar = () => {
    router.delete('/settings/profile/avatar', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex flex-col items-center space-y-4">
        <div class="relative">
            <Avatar class="h-24 w-24">
                <AvatarImage :src="avatarUrl" :alt="`Avatar de ${user.name}`" />
                <AvatarFallback class="text-lg">
                    {{ initials }}
                </AvatarFallback>
            </Avatar>
            
            <!-- Overlay para upload -->
            <div 
                class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity hover:opacity-100 cursor-pointer"
                @click="selectFile"
            >
                <Camera class="h-6 w-6 text-white" />
            </div>
        </div>

        <div class="flex flex-col items-center space-y-2">
            <div class="flex space-x-2">
                <Button 
                    variant="outline" 
                    size="sm" 
                    @click="selectFile"
                    :disabled="isUploading"
                >
                    <Upload class="mr-2 h-4 w-4" />
                    {{ isUploading ? 'Enviando...' : 'Alterar Avatar' }}
                </Button>
                
                <Button 
                    v-if="avatarUrl"
                    variant="outline" 
                    size="sm" 
                    @click="deleteAvatar"
                    :disabled="isUploading"
                >
                    <X class="mr-2 h-4 w-4" />
                    Remover
                </Button>
            </div>

            <p class="text-xs text-muted-foreground text-center max-w-xs">
                JPG, PNG, GIF ou WebP. Máximo 2MB. Recomendado 400x400px.
            </p>
        </div>

        <!-- Input file hidden -->
        <input
            ref="fileInput"
            type="file"
            accept="image/*"
            class="hidden"
            @change="handleFileChange"
        />
    </div>
</template>
