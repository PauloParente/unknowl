<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { computed } from 'vue';

interface Props {
  type: string;
  total: number;
  query: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:type': [value: string];
  'type-change': [value: string];
}>();

const filterOptions = [
  { value: 'all', label: 'Todos', icon: '🔍' },
  { value: 'communities', label: 'Comunidades', icon: '🏘️' },
  { value: 'posts', label: 'Posts', icon: '📝' },
  { value: 'users', label: 'Usuários', icon: '👤' },
];

const counts = computed(() => {
  // Aqui você pode calcular os counts específicos se necessário
  // Por enquanto, vamos usar valores mockados baseados no total
  const total = props.total;
  return {
    all: total,
    communities: Math.floor(total * 0.4),
    posts: Math.floor(total * 0.5),
    users: Math.floor(total * 0.1),
  };
});

function handleFilterChange(newType: string) {
  emit('update:type', newType);
  emit('type-change', newType);
}
</script>

<template>
  <Card v-if="query">
    <CardContent class="p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            Filtrar por:
          </span>
          <div class="flex gap-1">
            <Button
              v-for="option in filterOptions"
              :key="option.value"
              :variant="type === option.value ? 'default' : 'outline'"
              size="sm"
              class="h-8"
              @click="handleFilterChange(option.value)"
            >
              <span class="mr-1">{{ option.icon }}</span>
              {{ option.label }}
              <Badge
                v-if="counts[option.value as keyof typeof counts] > 0"
                variant="secondary"
                class="ml-2 h-4 px-1 text-xs"
              >
                {{ counts[option.value as keyof typeof counts] }}
              </Badge>
            </Button>
          </div>
        </div>
        
        <div class="text-sm text-gray-600 dark:text-gray-400">
          {{ total }} resultado{{ total !== 1 ? 's' : '' }} encontrado{{ total !== 1 ? 's' : '' }}
        </div>
      </div>
    </CardContent>
  </Card>
</template>
