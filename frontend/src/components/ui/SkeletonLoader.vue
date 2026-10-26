<script setup>
defineProps({
  variant: {
    type: String,
    default: 'rect',
    validator: (v) => ['text', 'avatar', 'card', 'rect', 'table-row'].includes(v)
  },
  count: {
    type: Number,
    default: 1
  },
  height: {
    type: String,
    default: null
  },
  width: {
    type: String,
    default: null
  }
});
</script>

<template>
  <div class="space-y-3 w-full" aria-busy="true" aria-live="polite">
    <div 
      v-for="n in count" 
      :key="n"
      class="animate-pulse bg-surface-muted border border-surface-border/50"
      :class="{
        'h-4 rounded-md w-full': variant === 'text',
        'h-12 w-12 rounded-full': variant === 'avatar',
        'h-40 rounded-2xl w-full p-6': variant === 'card',
        'h-12 rounded-xl w-full': variant === 'rect',
        'h-10 rounded-lg w-full flex items-center px-4': variant === 'table-row'
      }"
      :style="{
        height: height || undefined,
        width: width || undefined
      }"
    >
      <span class="sr-only">Chargement du contenu...</span>
    </div>
  </div>
</template>
