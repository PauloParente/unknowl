<template>
  <div class="relative">
    <slot />
    
    <!-- Badge de notificação -->
    <span
      v-if="count > 0"
      class="absolute -top-2 -right-2 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full min-w-[1.25rem] h-5"
      :class="{
        'animate-pulse': pulse,
        'bg-red-500': variant === 'danger',
        'bg-blue-500': variant === 'primary',
        'bg-green-500': variant === 'success',
        'bg-yellow-500': variant === 'warning',
      }"
    >
      {{ count > 99 ? '99+' : count }}
    </span>
    
    <!-- Indicador de ponto (sem número) -->
    <span
      v-else-if="showDot"
      class="absolute -top-1 -right-1 inline-block w-3 h-3 rounded-full"
      :class="{
        'bg-red-500': variant === 'danger',
        'bg-blue-500': variant === 'primary',
        'bg-green-500': variant === 'success',
        'bg-yellow-500': variant === 'warning',
      }"
    />
  </div>
</template>

<script setup lang="ts">
interface Props {
  count?: number
  showDot?: boolean
  pulse?: boolean
  variant?: 'danger' | 'primary' | 'success' | 'warning'
}

withDefaults(defineProps<Props>(), {
  count: 0,
  showDot: false,
  pulse: false,
  variant: 'danger',
})
</script>
