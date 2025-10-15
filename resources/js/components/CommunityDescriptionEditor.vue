<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Pencil, Check, X } from 'lucide-vue-next';

interface Props {
    communityId: number;
    initialDescription: string;
    canEdit: boolean;
}

const props = defineProps<Props>();

const isEditing = ref(false);
const localDescription = ref(props.initialDescription);

const form = useForm({
    description: props.initialDescription,
});

const startEditing = () => {
    isEditing.value = true;
    localDescription.value = props.initialDescription;
};

const cancelEditing = () => {
    isEditing.value = false;
    localDescription.value = props.initialDescription;
    form.reset();
};

const saveDescription = () => {
    form.description = localDescription.value;
    form.patch(route('communities.update-settings', props.communityId), {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium">Descrição da comunidade</h3>
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
            <Textarea
                v-model="localDescription"
                placeholder="Descreva sua comunidade..."
                class="min-h-[100px] resize-none"
                maxlength="1000"
            />
            <div class="flex items-center justify-between">
                <span class="text-xs text-muted-foreground">
                    {{ localDescription.length }}/1000 caracteres
                </span>
                <div class="flex gap-2">
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
                        @click="saveDescription"
                        :disabled="form.processing"
                    >
                        <Check class="h-4 w-4 mr-1" />
                        Salvar
                    </Button>
                </div>
            </div>
        </div>

        <div v-else>
            <p v-if="initialDescription" class="text-sm text-muted-foreground leading-relaxed">
                {{ initialDescription }}
            </p>
            <p v-else class="text-sm text-muted-foreground italic">
                Nenhuma descrição fornecida.
            </p>
        </div>
    </div>
</template>
