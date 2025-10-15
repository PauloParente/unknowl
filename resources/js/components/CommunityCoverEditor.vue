<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pencil, Check, X, Upload, Image as ImageIcon } from 'lucide-vue-next';

interface Props {
    communityId: number;
    initialCoverUrl: string | null;
    canEdit: boolean;
}

const props = defineProps<Props>();

const isEditing = ref(false);
const coverPreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement>();

const form = useForm({
    cover: null as File | null,
});

const currentCoverUrl = computed(() => {
    // Se há preview (usuário selecionou uma imagem), usar o preview
    if (coverPreview.value) {
        return coverPreview.value;
    }
    
    // Se há capa inicial (do banco de dados), usar ela
    if (props.initialCoverUrl) {
        return props.initialCoverUrl;
    }
    
    // Fallback para placeholder padrão
    return '/images/placeholders/community-cover.svg';
});

const startEditing = () => {
    isEditing.value = true;
    coverPreview.value = null;
    form.reset();
};

const cancelEditing = () => {
    isEditing.value = false;
    coverPreview.value = null;
    form.reset();
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const handleCoverChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        // Validar tipo de arquivo
        if (!file.type.startsWith('image/')) {
            alert('Apenas imagens são permitidas.');
            return;
        }

        // Validar tamanho (5MB = 5 * 1024 * 1024 bytes)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('Arquivo muito grande. Tamanho máximo: 5MB.');
            return;
        }

        form.cover = file;
        coverPreview.value = URL.createObjectURL(file);
    }
};

const saveCover = () => {
    if (!form.cover) {
        alert('Selecione uma imagem para fazer upload.');
        return;
    }

    form.post(route('communities.update-cover', props.communityId), {
        forceFormData: true,
        onSuccess: () => {
            isEditing.value = false;
            coverPreview.value = null;
            if (fileInputRef.value) {
                fileInputRef.value.value = '';
            }
        },
    });
};

const removeCover = () => {
    form.cover = null;
    coverPreview.value = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <Label class="text-sm font-medium">Capa da comunidade</Label>
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

        <!-- Preview da capa atual -->
        <div class="relative h-32 w-full overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border">
            <img 
                :src="currentCoverUrl" 
                :alt="`Capa da comunidade`" 
                class="h-full w-full object-cover"
            />
            <div v-if="!isEditing && canEdit" class="absolute inset-0 bg-black/0 hover:bg-black/20 transition-colors flex items-center justify-center opacity-0 hover:opacity-100">
                <Button
                    variant="secondary"
                    size="sm"
                    @click="startEditing"
                    class="bg-white/90 text-black hover:bg-white"
                >
                    <Pencil class="h-4 w-4 mr-1" />
                    Editar capa
                </Button>
            </div>
        </div>

        <!-- Formulário de edição -->
        <div v-if="isEditing" class="space-y-3">
            <div class="space-y-2">
                <Label for="cover">Selecionar nova imagem</Label>
                <div class="h-32 w-full rounded-lg border-2 border-dashed border-muted-foreground/25 flex items-center justify-center overflow-hidden">
                    <img
                        v-if="coverPreview"
                        :src="coverPreview"
                        alt="Preview da capa"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="flex flex-col items-center gap-2 text-muted-foreground">
                        <Upload class="h-8 w-8" />
                        <span class="text-sm">Clique para selecionar</span>
                    </div>
                </div>
                <Input
                    ref="fileInputRef"
                    id="cover"
                    type="file"
                    accept="image/*"
                    @change="handleCoverChange"
                    class="cursor-pointer"
                />
                <p class="text-xs text-muted-foreground">
                    PNG, JPG, GIF até 5MB. Recomendado: 1920x384px
                </p>
            </div>

            <div v-if="form.errors.cover" class="text-sm text-red-600">
                {{ form.errors.cover }}
            </div>

            <div class="flex items-center justify-between">
                <Button
                    v-if="coverPreview"
                    variant="outline"
                    size="sm"
                    @click="removeCover"
                    :disabled="form.processing"
                >
                    <X class="h-4 w-4 mr-1" />
                    Remover
                </Button>
                <div class="flex gap-2 ml-auto">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="cancelEditing"
                        :disabled="form.processing"
                    >
                        <X class="h-4 w-4 mr-1" />
                        Cancelar
                    </Button>
                    <Button
                        size="sm"
                        @click="saveCover"
                        :disabled="form.processing || !form.cover"
                    >
                        <Check class="h-4 w-4 mr-1" />
                        {{ form.processing ? 'Salvando...' : 'Salvar' }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
