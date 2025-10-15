<script setup lang="ts">
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea/index';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import type { Community } from '@/types';
import { Upload, X, Image as ImageIcon } from 'lucide-vue-next';

interface Props {
    modelValue: boolean;
}

interface MediaFile {
    file: File;
    preview: string;
    id: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void; (e: 'created'): void }>();

const page = usePage();
const communities = computed<Community[]>(() => (((page.props as any)?.auth?.communities ?? []) as Community[]));

const isOpen = ref(props.modelValue);
watch(
    () => props.modelValue,
    (val) => {
        isOpen.value = val;
    },
);
watch(isOpen, (val) => emit('update:modelValue', val));

const form = useForm({
    community_id: '' as string,
    title: '',
    text: '',
    media: [] as File[],
});

const mediaFiles = ref<MediaFile[]>([]);
const isDragOver = ref(false);
const fileInputRef = ref<HTMLInputElement>();

function createPreview(file: File): string {
    if (file.type.startsWith('image/')) {
        return URL.createObjectURL(file);
    }
    return '';
}

function addFiles(files: File[]) {
    // Validar quantidade máxima
    if (mediaFiles.value.length + files.length > 5) {
        alert('Você pode enviar no máximo 5 imagens por post.');
        return;
    }

    const validFiles: File[] = [];
    const errors: string[] = [];

    files.forEach(file => {
        // Validar tipo de arquivo
        if (!file.type.startsWith('image/')) {
            errors.push(`${file.name}: Apenas imagens são permitidas.`);
            return;
        }

        // Validar tamanho (2MB = 2 * 1024 * 1024 bytes)
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            errors.push(`${file.name}: Arquivo muito grande (máx. 2MB).`);
            return;
        }

        validFiles.push(file);
    });

    // Mostrar erros se houver
    if (errors.length > 0) {
        alert(errors.join('\n'));
    }

    // Adicionar apenas arquivos válidos
    if (validFiles.length > 0) {
        const newMediaFiles: MediaFile[] = validFiles.map(file => ({
            file,
            preview: createPreview(file),
            id: Math.random().toString(36).substr(2, 9)
        }));
        
        mediaFiles.value.push(...newMediaFiles);
        form.media = mediaFiles.value.map(mf => mf.file);
    }
}

function onFilesSelected(e: Event) {
    const target = e.target as HTMLInputElement;
    const files = target.files ? Array.from(target.files) : [];
    addFiles(files);
}

function removeMediaFile(id: string) {
    const index = mediaFiles.value.findIndex(mf => mf.id === id);
    if (index !== -1) {
        const mediaFile = mediaFiles.value[index];
        // Limpar o preview URL para evitar vazamentos de memória
        if (mediaFile.preview) {
            URL.revokeObjectURL(mediaFile.preview);
        }
        mediaFiles.value.splice(index, 1);
        form.media = mediaFiles.value.map(mf => mf.file);
    }
}

function onDragOver(e: DragEvent) {
    e.preventDefault();
    isDragOver.value = true;
}

function onDragLeave(e: DragEvent) {
    e.preventDefault();
    isDragOver.value = false;
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    isDragOver.value = false;
    
    const files = e.dataTransfer?.files ? Array.from(e.dataTransfer.files) : [];
    addFiles(files);
}

function openFileDialog() {
    fileInputRef.value?.click();
}

function resetAndClose() {
    // Limpar todos os previews para evitar vazamentos de memória
    mediaFiles.value.forEach(mf => {
        if (mf.preview) {
            URL.revokeObjectURL(mf.preview);
        }
    });
    mediaFiles.value = [];
    form.reset();
    isOpen.value = false;
}

async function submit() {
    await form.post('/posts', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            emit('created');
            resetAndClose();
        },
        onError: (errors) => {
            console.error('Erro ao criar post:', errors);
            
            // Mostrar erros específicos para o usuário
            if (errors.media) {
                if (Array.isArray(errors.media)) {
                    alert('Erro nas imagens:\n' + errors.media.join('\n'));
                } else {
                    alert('Erro nas imagens: ' + errors.media);
                }
            } else if (errors.title) {
                alert('Erro no título: ' + errors.title);
            } else if (errors.community_id) {
                alert('Erro na comunidade: ' + errors.community_id);
            } else {
                alert('Erro ao criar post. Verifique os dados e tente novamente.');
            }
        },
        onFinish: () => {
            console.log('Upload finalizado');
        },
    });
}

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-xl">
            <DialogHeader>
                <DialogTitle>Novo post</DialogTitle>
                <DialogDescription>Compartilhe um novo conteúdo com a comunidade.</DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="grid gap-2">
                    <Label for="community">Comunidade</Label>
                    <select 
                        id="community" 
                        v-model="form.community_id" 
                        class="flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="" disabled>Selecione a comunidade</option>
                        <option v-for="c in communities" :key="c.id" :value="String(c.id)">
                            {{ c.name }}
                        </option>
                    </select>
                    <div v-if="form.errors.community_id" class="text-sm text-red-500">{{ form.errors.community_id }}</div>
                </div>

                <div class="grid gap-2">
                    <Label for="title">Título</Label>
                    <Input id="title" v-model="form.title" placeholder="Digite um título" />
                    <div v-if="form.errors.title" class="text-sm text-red-500">{{ form.errors.title }}</div>
                </div>

                <div class="grid gap-2">
                    <Label for="text">Conteúdo</Label>
                    <Textarea id="text" v-model="form.text" :rows="5" placeholder="Escreva seu post" />
                    <div v-if="form.errors.text" class="text-sm text-red-500">{{ form.errors.text }}</div>
                </div>

                <div class="grid gap-2">
                    <Label for="media">Imagens/Vídeos</Label>
                    
                    <!-- Área de Upload com Drag & Drop -->
                    <div 
                        class="border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer hover:border-primary/50"
                        :class="{
                            'border-primary bg-primary/5': isDragOver,
                            'border-gray-300': !isDragOver
                        }"
                        @click="openFileDialog"
                        @dragover="onDragOver"
                        @dragleave="onDragLeave"
                        @drop="onDrop"
                    >
                        <input 
                            ref="fileInputRef"
                            id="media" 
                            type="file" 
                            multiple 
                            accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" 
                            class="hidden"
                            @change="onFilesSelected" 
                        />
                        
                        <div class="flex flex-col items-center gap-2">
                            <Upload class="w-8 h-8 text-gray-400" />
                            <div class="text-sm text-gray-600">
                                <span class="font-medium text-primary">Clique para fazer upload</span>
                                ou arraste e solte suas imagens aqui
                            </div>
                            <div class="text-xs text-gray-500">
                                PNG, JPG, GIF, WEBP (máx. 2MB cada, até 5 imagens)
                            </div>
                        </div>
                    </div>

                    <!-- Preview das Imagens -->
                    <div v-if="mediaFiles.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div 
                            v-for="mediaFile in mediaFiles" 
                            :key="mediaFile.id"
                            class="relative group"
                        >
                            <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                <img 
                                    v-if="mediaFile.preview" 
                                    :src="mediaFile.preview" 
                                    :alt="mediaFile.file.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <ImageIcon class="w-8 h-8 text-gray-400" />
                                </div>
                            </div>
                            
                            <!-- Botão de remover -->
                            <button 
                                type="button"
                                @click="removeMediaFile(mediaFile.id)"
                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600"
                            >
                                <X class="w-4 h-4" />
                            </button>
                            
                            <!-- Nome do arquivo e tamanho -->
                            <div class="mt-1 text-xs text-gray-500 truncate">
                                {{ mediaFile.file.name }}
                            </div>
                            <div class="text-xs text-gray-400">
                                {{ formatFileSize(mediaFile.file.size) }}
                            </div>
                        </div>
                    </div>

                    <!-- Mensagens de erro -->
                    <div v-if="form.errors.media" class="text-sm text-red-500">{{ form.errors.media }}</div>
                    <div v-if="(form.errors as any)['media.*']" class="text-sm text-red-500">{{ (form.errors as any)['media.*'] }}</div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <Button type="button" variant="ghost" @click="resetAndClose">Cancelar</Button>
                <Button type="button" :disabled="form.processing" @click="submit">Publicar</Button>
            </div>
        </DialogContent>
    </Dialog>
    
</template>


