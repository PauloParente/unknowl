<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { store, create } from '@/routes/communities';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Plus, X, Upload, Check, AlertCircle } from 'lucide-vue-next';

interface Rule {
    id: string;
    text: string;
}

const form = useForm({
    name: '',
    title: '',
    description: '',
    rules: [] as Rule[],
    avatar: null as File | null,
    cover: null as File | null,
    is_public: true,
    requires_approval: false,
});

const nameAvailability = ref<{
    available: boolean;
    message: string;
    suggestions?: string[];
} | null>(null);

const isCheckingName = ref(false);
const avatarPreview = ref<string | null>(null);
const coverPreview = ref<string | null>(null);
const nextRuleId = ref(1);

const rules = computed(() => form.rules);

const breadcrumbs = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Criar Comunidade', href: '#', current: true },
];

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

function addRule(): void {
    form.rules.push({
        id: `rule-${nextRuleId.value++}`,
        text: '',
    });
}

function removeRule(ruleId: string): void {
    const index = form.rules.findIndex(rule => rule.id === ruleId);
    if (index > -1) {
        form.rules.splice(index, 1);
    }
}

function handleAvatarChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.avatar = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function handleCoverChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    
    if (file) {
        form.cover = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            coverPreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function submitForm(): void {
    form.post(store.url(), {
        forceFormData: true,
        onSuccess: () => {
            // Redirecionamento será feito pelo controller
        },
    });
}

function useSuggestion(suggestion: string): void {
    form.name = suggestion;
    checkNameAvailability(suggestion);
}
</script>

<template>
    <Head title="Criar Comunidade" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="space-y-2">
                <h1 class="text-2xl font-bold">Criar Nova Comunidade</h1>
                <p class="text-muted-foreground">
                    Crie uma comunidade para pessoas com interesses em comum se reunirem.
                </p>
            </div>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Nome da Comunidade -->
                <Card>
                    <CardHeader>
                        <CardTitle>Nome da Comunidade</CardTitle>
                        <CardDescription>
                            Escolha um nome único para sua comunidade. Pode conter letras, números, underscore e hífen.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="name">Nome</Label>
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
                                            class="cursor-pointer hover:bg-primary hover:text-primary-foreground"
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
                    </CardContent>
                </Card>

                <!-- Informações Básicas -->
                <Card>
                    <CardHeader>
                        <CardTitle>Informações Básicas</CardTitle>
                        <CardDescription>
                            Forneça informações sobre sua comunidade.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
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

                        <div class="space-y-2">
                            <Label for="description">Descrição</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Descreva o propósito da sua comunidade..."
                                rows="4"
                                maxlength="500"
                            />
                            <div class="text-xs text-muted-foreground">
                                {{ form.description.length }}/500 caracteres
                            </div>
                            <div v-if="form.errors.description" class="text-sm text-red-600">
                                {{ form.errors.description }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Regras -->
                <Card>
                    <CardHeader>
                        <CardTitle>Regras da Comunidade</CardTitle>
                        <CardDescription>
                            Defina as regras que os membros devem seguir (opcional).
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="rules.length === 0" class="text-center py-8 text-muted-foreground">
                            <p>Nenhuma regra definida ainda.</p>
                            <p class="text-sm">Adicione regras para manter a comunidade organizada.</p>
                        </div>
                        
                        <div v-else class="space-y-3">
                            <div
                                v-for="(rule, index) in rules"
                                :key="rule.id"
                                class="flex items-start gap-3"
                            >
                                <span class="mt-2 text-sm text-muted-foreground">{{ index + 1 }}.</span>
                                <Textarea
                                    v-model="rule.text"
                                    placeholder="Digite uma regra..."
                                    class="flex-1"
                                    rows="2"
                                    maxlength="200"
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="mt-1"
                                    @click="removeRule(rule.id)"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="outline"
                            class="w-full"
                            @click="addRule"
                            :disabled="rules.length >= 10"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Adicionar Regra
                        </Button>
                        
                        <div v-if="form.errors.rules" class="text-sm text-red-600">
                            {{ form.errors.rules }}
                        </div>
                    </CardContent>
                </Card>

                <!-- Imagens -->
                <Card>
                    <CardHeader>
                        <CardTitle>Imagens</CardTitle>
                        <CardDescription>
                            Adicione um avatar e uma imagem de capa para sua comunidade (opcional).
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Avatar -->
                        <div class="space-y-3">
                            <Label>Avatar</Label>
                            <div class="flex items-center gap-4">
                                <div class="h-16 w-16 rounded-lg border-2 border-dashed border-muted-foreground/25 flex items-center justify-center overflow-hidden">
                                    <img
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        alt="Preview do avatar"
                                        class="h-full w-full object-cover"
                                    />
                                    <Upload v-else class="h-6 w-6 text-muted-foreground" />
                                </div>
                                <div class="flex-1">
                                    <Input
                                        type="file"
                                        accept="image/*"
                                        @change="handleAvatarChange"
                                        class="cursor-pointer"
                                    />
                                    <p class="text-xs text-muted-foreground mt-1">
                                        PNG, JPG, GIF até 2MB. Recomendado: 256x256px
                                    </p>
                                </div>
                            </div>
                            <div v-if="form.errors.avatar" class="text-sm text-red-600">
                                {{ form.errors.avatar }}
                            </div>
                        </div>

                        <!-- Imagem de Capa -->
                        <div class="space-y-3">
                            <Label>Imagem de Capa</Label>
                            <div class="space-y-3">
                                <div class="h-32 w-full rounded-lg border-2 border-dashed border-muted-foreground/25 flex items-center justify-center overflow-hidden">
                                    <img
                                        v-if="coverPreview"
                                        :src="coverPreview"
                                        alt="Preview da capa"
                                        class="h-full w-full object-cover"
                                    />
                                    <Upload v-else class="h-8 w-8 text-muted-foreground" />
                                </div>
                                <Input
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
                        </div>
                    </CardContent>
                </Card>

                <!-- Configurações -->
                <Card>
                    <CardHeader>
                        <CardTitle>Configurações</CardTitle>
                        <CardDescription>
                            Configure as opções de privacidade e moderação da sua comunidade.
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="is_public"
                                v-model:checked="form.is_public"
                            />
                            <Label for="is_public" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Comunidade pública
                            </Label>
                        </div>
                        <p class="text-xs text-muted-foreground ml-6">
                            Comunidades públicas aparecem nos resultados de busca e qualquer pessoa pode ver o conteúdo.
                        </p>

                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="requires_approval"
                                v-model:checked="form.requires_approval"
                            />
                            <Label for="requires_approval" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Requer aprovação para posts
                            </Label>
                        </div>
                        <p class="text-xs text-muted-foreground ml-6">
                            Posts precisarão ser aprovados por moderadores antes de serem publicados.
                        </p>
                    </CardContent>
                </Card>

                <!-- Botões de Ação -->
                <div class="flex justify-end gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit('/dashboard')"
                    >
                        Cancelar
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing || !nameAvailability?.available"
                    >
                        <span v-if="form.processing">Criando...</span>
                        <span v-else>Criar Comunidade</span>
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
