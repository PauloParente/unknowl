<script setup lang="ts">
import { cn } from '@/lib/utils'
import { computed } from 'vue'

interface Props {
  class?: string
  modelValue?: string
  placeholder?: string
  rows?: number
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  rows: 3,
  disabled: false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
}>()

const modelValue = computed({
  get: () => props.modelValue || '',
  set: (value: string) => emit('update:modelValue', value)
})
</script>

<template>
  <textarea
    v-model="modelValue"
    :class="cn(
      'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
      props.class
    )"
    :placeholder="placeholder"
    :rows="rows"
    :disabled="disabled"
  />
</template>
