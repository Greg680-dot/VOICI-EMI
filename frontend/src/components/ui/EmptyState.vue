<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  }
});
</script>

<template>
  <div class="text-center py-12 px-4 rounded-2xl border border-dashed border-surface-border bg-white/60">
    <div class="mx-auto w-12 h-12 rounded-xl bg-surface-muted text-ink-muted flex items-center justify-center mb-4">
      <slot name="icon">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
      </slot>
    </div>

    <h3 class="text-base font-serif font-bold text-ink-base">
      {{ title }}
    </h3>

    <p v-if="description" class="text-xs text-ink-muted max-w-sm mx-auto mt-1 leading-relaxed">
      {{ description }}
    </p>

    <div v-if="$slots.action" class="mt-5 flex justify-center">
      <slot name="action" />
    </div>
  </div>
</template>
