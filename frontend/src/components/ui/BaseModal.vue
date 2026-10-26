<script setup>
import { watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  maxWidth: {
    type: String,
    default: 'max-w-lg'
  }
});

const emit = defineEmits(['update:modelValue', 'close']);

const close = () => {
  emit('update:modelValue', false);
  emit('close');
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape' && props.modelValue) {
    close();
  }
};

watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown);
  document.body.style.overflow = '';
});
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="modelValue" 
        class="fixed inset-0 z-50 overflow-y-auto bg-navy-950/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="close"
        role="dialog"
        aria-modal="true"
        :aria-label="title || 'Fenêtre modale'"
      >
        <div 
          class="relative w-full rounded-2xl bg-white border border-surface-border shadow-floating p-5 sm:p-8 transform transition-all max-h-[90vh] overflow-y-auto"
          :class="maxWidth"
        >
          <!-- Header -->
          <div class="flex items-center justify-between border-b border-surface-border/60 pb-4 mb-4">
            <h3 class="font-serif font-bold text-lg text-ink-base">
              <slot name="title">{{ title }}</slot>
            </h3>
            <button 
              @click="close"
              class="text-ink-light hover:text-ink-base p-1.5 rounded-lg focus:ring-2 focus:ring-amber-500"
              aria-label="Fermer la boîte de dialogue"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body -->
          <div class="text-sm text-ink-muted">
            <slot />
          </div>

          <!-- Footer -->
          <div v-if="$slots.footer" class="border-t border-surface-border/60 pt-4 mt-6 flex justify-end gap-3">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
