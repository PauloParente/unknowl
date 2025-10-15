<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" :class="className" v-bind="$attrs">
        <!-- Círculo de fundo suave -->
        <circle cx="20" cy="20" r="18" fill="currentColor" opacity="0.08"/>
        
        <!-- Corpo principal da coruja (forma oval) -->
        <ellipse cx="20" cy="22" rx="12" ry="14" fill="currentColor" opacity="0.9"/>
        
        <!-- Orelhas/chifres da coruja -->
        <path d="M12 10 Q 12 6, 16 8 Q 14 10, 16 12" fill="currentColor" opacity="0.9"/>
        <path d="M28 10 Q 28 6, 24 8 Q 26 10, 24 12" fill="currentColor" opacity="0.9"/>
        
        <!-- Máscara facial branca -->
        <ellipse cx="20" cy="20" rx="8" ry="10" fill="currentColor" opacity="0.15"/>
        
        <!-- Olhos da coruja -->
        <ellipse cx="16" cy="18" rx="2.5" ry="4" fill="currentColor" opacity="0.9"/>
        <ellipse cx="24" cy="18" rx="2.5" ry="4" fill="currentColor" opacity="0.9"/>
        
        <!-- Pupilas -->
        <circle cx="16" cy="18" r="1.5" fill="currentColor" opacity="0.3"/>
        <circle cx="24" cy="18" r="1.5" fill="currentColor" opacity="0.3"/>
        
        <!-- Bico -->
        <path d="M20 22 L 18 26 L 22 26 Z" fill="currentColor" opacity="0.8"/>
        
        <!-- Detalhes das orelhas (contorno interno) -->
        <path d="M12 10 Q 12 6, 16 8" fill="none" stroke="currentColor" stroke-width="1" opacity="0.6"/>
        <path d="M28 10 Q 28 6, 24 8" fill="none" stroke="currentColor" stroke-width="1" opacity="0.6"/>
        
        <!-- Pequenos detalhes de textura (opcional) -->
        <circle cx="14" cy="26" r="0.8" fill="currentColor" opacity="0.4"/>
        <circle cx="26" cy="26" r="0.8" fill="currentColor" opacity="0.4"/>
        <circle cx="20" cy="28" r="0.6" fill="currentColor" opacity="0.4"/>
    </svg>
</template>
