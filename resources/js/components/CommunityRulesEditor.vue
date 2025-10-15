<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pencil, Check, X, Plus, Trash2 } from 'lucide-vue-next';

interface Props {
    communityId: number;
    initialRules: string[];
    canEdit: boolean;
}

const props = defineProps<Props>();

const isEditing = ref(false);
const localRules = ref([...props.initialRules]);

const form = useForm({
    rules: props.initialRules,
});

const startEditing = () => {
    isEditing.value = true;
    localRules.value = [...props.initialRules];
};

const cancelEditing = () => {
    isEditing.value = false;
    localRules.value = [...props.initialRules];
    form.reset();
};

const addRule = () => {
    localRules.value.push('');
};

const removeRule = (index: number) => {
    localRules.value.splice(index, 1);
};

const updateRule = (index: number, value: string) => {
    localRules.value[index] = value;
};

const saveRules = () => {
    // Filtrar regras vazias
    const validRules = localRules.value.filter(rule => rule.trim().length > 0);
    
    if (validRules.length === 0) {
        alert('Por favor, adicione pelo menos uma regra válida.');
        return;
    }

    form.rules = validRules;
    
    form.patch(`/communities/${props.communityId}/rules`, {
        onSuccess: () => {
            isEditing.value = false;
            // Atualizar as regras iniciais para refletir as mudanças
            props.initialRules.splice(0, props.initialRules.length, ...validRules);
        },
        onError: (errors) => {
            console.error('Erro ao salvar regras:', errors);
            alert('Erro ao salvar as regras. Tente novamente.');
        },
    });
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium">Regras da comunidade</h3>
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
            <div class="space-y-2">
                <div
                    v-for="(rule, index) in localRules"
                    :key="index"
                    class="flex items-center gap-2"
                >
                    <span class="text-sm text-muted-foreground w-6">{{ index + 1 }}.</span>
                    <Input
                        :model-value="rule"
                        @update:model-value="(value: string | number) => updateRule(index, String(value))"
                        placeholder="Digite uma regra..."
                        class="flex-1"
                        maxlength="500"
                    />
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="removeRule(index)"
                        class="h-8 w-8 p-0 text-destructive hover:text-destructive"
                    >
                        <Trash2 class="h-4 w-4" />
                    </Button>
                </div>
            </div>

            <Button
                variant="outline"
                size="sm"
                @click="addRule"
                class="w-full"
            >
                <Plus class="h-4 w-4 mr-2" />
                Adicionar regra
            </Button>

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
                    @click="saveRules"
                    :disabled="form.processing"
                    class="flex-1"
                >
                    <Check class="h-4 w-4 mr-1" />
                    {{ form.processing ? 'Salvando...' : 'Salvar' }}
                </Button>
            </div>
        </div>

        <div v-else>
            <ol v-if="initialRules.length > 0" class="list-decimal space-y-2 pl-5 text-sm text-muted-foreground">
                <li v-for="(rule, index) in initialRules" :key="index">
                    {{ rule }}
                </li>
            </ol>
            <p v-else class="text-sm text-muted-foreground italic">
                Nenhuma regra definida.
            </p>
        </div>
    </div>
</template>
