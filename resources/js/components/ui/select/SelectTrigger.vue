<script setup lang="ts">
import { cn } from '@/lib/utils'
import { computed } from 'vue'

interface Props {
  class?: string
  modelValue?: string | number
  placeholder?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number): void
}>()

const modelValue = computed({
  get: () => props.modelValue || '',
  set: (value: string | number) => emit('update:modelValue', value)
})
</script>

<template>
  <select
    v-model="modelValue"
    :class="cn(
      'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
      props.class
    )"
    :disabled="disabled"
  >
    <option value="" disabled>{{ placeholder }}</option>
    <slot />
  </select>
</template>
