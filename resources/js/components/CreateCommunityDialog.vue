<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { store, create } from '@/routes/communities';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Plus, X, Check, AlertCircle, ExternalLink } from 'lucide-vue-next';

interface Props {
    modelValue: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void; (e: 'created'): void }>();

const isOpen = ref(props.modelValue);
watch(() => props.modelValue, (val) => { isOpen.value = val; });
watch(isOpen, (val) => emit('update:modelValue', val));

const form = useForm({
    name: '',
    title: '',
    description: '',
});

const nameAvailability = ref<{
    available: boolean;
    message: string;
    suggestions?: string[];
} | null>(null);

const isCheckingName = ref(false);

// Verificar disponibilidade do nome em tempo real
watch(
    () => form.name,
    async (newName) => {
        if (newName && newName.length >= 3) {
            await checkNameAvailability(newName);
        } else {
            nameAvailability.value = null;
        }
    },
    { debounce: 500 }
);

async function checkNameAvailability(name: string): Promise<void> {
    isCheckingName.value = true;
    try {
        const response = await fetch(`/communities/check-name?name=${encodeURIComponent(name)}`);
        nameAvailability.value = await response.json();
    } catch (error) {
        console.error('Erro ao verificar disponibilidade do nome:', error);
    } finally {
        isCheckingName.value = false;
    }
}

function useSuggestion(suggestion: string): void {
    form.name = suggestion;
    checkNameAvailability(suggestion);
}

function submitForm(): void {
    form.post(store.url(), {
        forceFormData: true,
        onSuccess: () => {
            emit('created');
            isOpen.value = false;
            form.reset();
            nameAvailability.value = null;
        },
    });
}

function openFullForm(): void {
    isOpen.value = false;
    router.visit(create.url());
}

function resetForm(): void {
    form.reset();
    nameAvailability.value = null;
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Criar Nova Comunidade</DialogTitle>
                <DialogDescription>
                    Crie uma comunidade para pessoas com interesses em comum se reunirem.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <!-- Nome da Comunidade -->
                <div class="space-y-2">
                    <Label for="name">Nome da Comunidade</Label>
                    <div class="relative">
                        <Input
                            id="name"
                            v-model="form.name"
                            placeholder="nome-da-comunidade"
                            class="pr-8"
                            :class="{ 'border-green-500': nameAvailability?.available, 'border-red-500': nameAvailability && !nameAvailability.available }"
                        />
                        <div class="absolute right-3 top-1/2 -translate-y-1/2">
                            <div v-if="isCheckingName" class="h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-blue-600"></div>
                            <Check v-else-if="nameAvailability?.available" class="h-4 w-4 text-green-500" />
                            <AlertCircle v-else-if="nameAvailability && !nameAvailability.available" class="h-4 w-4 text-red-500" />
                        </div>
                    </div>
                    
                    <div v-if="nameAvailability" class="text-sm">
                        <p :class="nameAvailability.available ? 'text-green-600' : 'text-red-600'">
                            {{ nameAvailability.message }}
                        </p>
                        
                        <div v-if="nameAvailability.suggestions?.length" class="mt-2">
                            <p class="text-sm text-muted-foreground mb-1">Sugestões:</p>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="suggestion in nameAvailability.suggestions"
                                    :key="suggestion"
                                    variant="outline"
                                    class="cursor-pointer hover:bg-primary hover:text-primary-foreground text-xs"
                                    @click="useSuggestion(suggestion)"
                                >
                                    {{ suggestion }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="form.errors.name" class="text-sm text-red-600">
                        {{ form.errors.name }}
                    </div>
                </div>

                <!-- Título -->
                <div class="space-y-2">
                    <Label for="title">Título</Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        placeholder="Título da Comunidade"
                        maxlength="100"
                    />
                    <div v-if="form.errors.title" class="text-sm text-red-600">
                        {{ form.errors.title }}
                    </div>
                </div>

                <!-- Descrição -->
                <div class="space-y-2">
                    <Label for="description">Descrição (opcional)</Label>
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Descreva o propósito da sua comunidade..."
                        rows="3"
                        maxlength="500"
                    />
                    <div class="text-xs text-muted-foreground">
                        {{ form.description.length }}/500 caracteres
                    </div>
                    <div v-if="form.errors.description" class="text-sm text-red-600">
                        {{ form.errors.description }}
                    </div>
                </div>

                <!-- Link para formulário completo -->
                <div class="pt-2 border-t">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="text-xs"
                        @click="openFullForm"
                    >
                        <ExternalLink class="mr-2 h-3 w-3" />
                        Mais opções (regras, imagens, configurações)
                    </Button>
                </div>

                <!-- Botões de Ação -->
                <div class="flex justify-end gap-3 pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="resetForm"
                    >
                        Limpar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !nameAvailability?.available"
                        @click="submitForm"
                    >
                        <span v-if="form.processing">Criando...</span>
                        <span v-else>Criar Comunidade</span>
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
