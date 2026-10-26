<script setup>
import { computed, useId } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  id: {
    type: String,
    default: null
  },
  label: {
    type: String,
    default: ''
  },
  type: {
    type: String,
    default: 'text'
  },
  placeholder: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  autocomplete: {
    type: String,
    default: 'off'
  }
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const generatedId = useId ? useId() : `input-${Math.random().toString(36).substring(2, 9)}`;
const inputId = computed(() => props.id || generatedId);
const errorId = computed(() => `${inputId.value}-error`);
const hintId = computed(() => `${inputId.value}-hint`);

const describedBy = computed(() => {
  const ids = [];
  if (props.error) ids.push(errorId.value);
  if (props.hint) ids.push(hintId.value);
  return ids.length ? ids.join(' ') : undefined;
});

const onInput = (event) => {
  emit('update:modelValue', event.target.value);
};
</script>

<template>
  <div class="space-y-1.5 w-full text-left">
    <div class="flex items-center justify-between">
      <label 
        v-if="label" 
        :for="inputId" 
        class="block text-xs font-semibold uppercase tracking-wider text-ink-base"
      >
        {{ label }}
        <span v-if="required" class="text-amber-600" aria-hidden="true">*</span>
      </label>
      <slot name="label-right" />
    </div>

    <div class="relative rounded-xl shadow-xs">
      <div 
        v-if="$slots.prefix" 
        class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-ink-light"
      >
        <slot name="prefix" />
      </div>

      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :aria-invalid="!!error ? 'true' : undefined"
        :aria-describedby="describedBy"
        @input="onInput"
        @blur="$emit('blur', $event)"
        @focus="$emit('focus', $event)"
        class="w-full rounded-xl border bg-white px-3.5 py-2.5 text-sm text-ink-base transition placeholder:text-ink-light focus:outline-none focus:ring-2 disabled:bg-surface-muted disabled:cursor-not-allowed"
        :class="[
          $slots.prefix ? 'pl-10' : '',
          $slots.suffix ? 'pr-10' : '',
          error 
            ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-200' 
            : 'border-surface-border hover:border-navy-300 focus:border-navy-700 focus:ring-navy-100'
        ]"
      />

      <div 
        v-if="$slots.suffix" 
        class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-ink-light"
      >
        <slot name="suffix" />
      </div>
    </div>

    <p 
      v-if="error" 
      :id="errorId" 
      role="alert" 
      class="text-xs text-rose-600 font-medium flex items-center gap-1 mt-1"
    >
      <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 11-18 0z" />
      </svg>
      {{ error }}
    </p>

    <p 
      v-else-if="hint" 
      :id="hintId" 
      class="text-xs text-ink-muted mt-1"
    >
      {{ hint }}
    </p>
  </div>
</template>
