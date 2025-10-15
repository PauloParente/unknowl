<script setup lang="ts">
import { cn } from '@/lib/utils'

interface Props {
  class?: string
  value: string | number
}

const props = defineProps<Props>()
</script>

<template>
  <option
    :value="props.value"
    :class="cn(
      'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      props.class
    )"
  >
    <slot />
  </option>
</template>
