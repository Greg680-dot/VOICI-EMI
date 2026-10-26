<script setup>
import { computed } from 'vue';

const props = defineProps({
  as: {
    type: String,
    default: 'div'
  },
  padding: {
    type: String,
    default: 'md',
    validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v)
  },
  hoverable: {
    type: Boolean,
    default: false
  }
});

const paddingClasses = computed(() => {
  switch (props.padding) {
    case 'none': return 'p-0';
    case 'sm': return 'p-4';
    case 'lg': return 'p-8 sm:p-10';
    case 'md':
    default:
      return 'p-6';
  }
});
</script>

<template>
  <component
    :is="as"
    class="bg-white rounded-2xl border border-surface-border/80 shadow-subtle transition-all duration-200"
    :class="[
      paddingClasses,
      hoverable ? 'hover:shadow-card hover:border-amber-300 transform hover:-translate-y-0.5' : ''
    ]"
  >
    <header v-if="$slots.header" class="border-b border-surface-border/60 pb-4 mb-4">
      <slot name="header" />
    </header>

    <div>
      <slot />
    </div>

    <footer v-if="$slots.footer" class="border-t border-surface-border/60 pt-4 mt-6">
      <slot name="footer" />
    </footer>
  </component>
</template>
