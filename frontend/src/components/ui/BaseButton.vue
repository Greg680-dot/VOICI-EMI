<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'accent', 'outline', 'ghost', 'danger'].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v)
  },
  type: {
    type: String,
    default: 'button'
  },
  to: {
    type: [String, Object],
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  ariaLabel: {
    type: String,
    default: null
  }
});

const isLink = computed(() => !!props.to && !props.disabled && !props.loading);

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'accent':
      return 'bg-amber text-white hover:bg-amber-600 active:bg-amber-700 shadow-subtle border border-transparent focus:ring-amber-500';
    case 'outline':
      return 'bg-transparent text-navy-700 hover:bg-navy-50 active:bg-navy-100 border border-navy-700 focus:ring-navy-600';
    case 'ghost':
      return 'bg-transparent text-navy-700 hover:bg-surface-muted active:bg-surface-border border border-transparent focus:ring-navy-600';
    case 'danger':
      return 'bg-rose-600 text-white hover:bg-rose-700 active:bg-rose-800 shadow-subtle border border-transparent focus:ring-rose-500';
    case 'primary':
    default:
      return 'bg-navy text-white hover:bg-navy-800 active:bg-navy-900 shadow-subtle border border-transparent focus:ring-navy-700';
  }
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'text-xs px-3 py-1.5 rounded-lg font-medium gap-1.5';
    case 'lg':
      return 'text-base px-6 py-3 rounded-xl font-semibold gap-2.5';
    case 'md':
    default:
      return 'text-sm px-4 py-2.5 rounded-lg font-semibold gap-2';
  }
});
</script>

<template>
  <component
    :is="isLink ? RouterLink : 'button'"
    :to="isLink ? to : undefined"
    :type="!isLink ? type : undefined"
    :disabled="(!isLink && (disabled || loading)) ? true : undefined"
    :aria-disabled="disabled || loading ? 'true' : undefined"
    :aria-label="ariaLabel"
    class="inline-flex items-center justify-center transition-all duration-150 select-none focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
    :class="[variantClasses, sizeClasses]"
  >
    <svg 
      v-if="loading" 
      class="animate-spin -ml-0.5 w-4 h-4 text-current" 
      xmlns="http://www.w3.org/2000/svg" 
      fill="none" 
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
    </svg>
    <slot />
  </component>
</template>
