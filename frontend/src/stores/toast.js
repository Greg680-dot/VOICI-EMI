import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toast', () => {
  const toasts = ref([]);

  let counter = 0;

  function addToast({ type = 'info', title = '', message = '', duration = 4000 }) {
    const id = ++counter;
    const toast = { id, type, title, message };
    toasts.value.push(toast);

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
    return id;
  }

  function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }

  function success(message, title = 'Succès') {
    return addToast({ type: 'success', title, message });
  }

  function error(message, title = 'Erreur') {
    return addToast({ type: 'error', title, message, duration: 6000 });
  }

  function warning(message, title = 'Attention') {
    return addToast({ type: 'warning', title, message });
  }

  function info(message, title = 'Information') {
    return addToast({ type: 'info', title, message });
  }

  return {
    toasts,
    addToast,
    removeToast,
    success,
    error,
    warning,
    info
  };
});
