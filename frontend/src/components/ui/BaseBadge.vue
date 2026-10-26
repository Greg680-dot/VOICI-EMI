<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) => [
      'neutral', 'primary', 'accent', 'success', 'danger',
      'stamp-verified', 'stamp-debunked', 'stamp-pending'
    ].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  }
});

const isStamp = computed(() => props.variant.startsWith('stamp-'));

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'stamp-verified':
      return 'border-2 border-dashed border-emerald-700 text-emerald-900 bg-emerald-50/95 shadow-xs -rotate-1 font-serif tracking-widest uppercase font-bold transition-transform hover:rotate-0 inline-flex items-center gap-1.5';
    case 'stamp-debunked':
      return 'border-2 border-dashed border-rose-700 text-rose-900 bg-rose-50/95 shadow-xs rotate-1 font-serif tracking-widest uppercase font-bold transition-transform hover:rotate-0 inline-flex items-center gap-1.5';
    case 'stamp-pending':
      return 'border-2 border-dashed border-amber-700 text-amber-900 bg-amber-50/95 shadow-xs -rotate-1 font-serif tracking-widest uppercase font-bold transition-transform hover:rotate-0 inline-flex items-center gap-1.5';
    case 'primary':
      return 'bg-navy-50 text-navy-800 border border-navy-200 font-semibold';
    case 'accent':
      return 'bg-amber-50 text-amber-900 border border-amber-200 font-semibold';
    case 'success':
      return 'bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold';
    case 'danger':
      return 'bg-rose-50 text-rose-800 border border-rose-200 font-semibold';
    case 'neutral':
    default:
      return 'bg-surface-muted text-ink-base border border-surface-border font-medium';
  }
});

const sizeClasses = computed(() => {
  if (isStamp.value) {
    switch (props.size) {
      case 'sm': return 'text-[10px] px-2 py-0.5 rounded';
      case 'lg': return 'text-sm px-4 py-1.5 rounded-md';
      case 'md':
      default:
        return 'text-xs px-3 py-1 rounded-md';
    }
  }

  switch (props.size) {
    case 'sm': return 'text-[11px] px-2 py-0.5 rounded-full';
    case 'lg': return 'text-sm px-3.5 py-1.5 rounded-full';
    case 'md':
    default:
      return 'text-xs px-2.5 py-1 rounded-full';
  }
});
</script>

<template>
  <span 
    class="inline-flex items-center justify-center transition-transform select-none"
    :class="[variantClasses, sizeClasses]"
  >
    <slot />
  </span>
</template>
