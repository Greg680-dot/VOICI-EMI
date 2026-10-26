import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useLoadingStore = defineStore('loading', () => {
  // Page navigation loader
  const isPageLoading = ref(false);
  const pageTitle = ref('Chargement...');

  // Auth transition loader (Login & Register)
  const isAuthTransition = ref(false);
  const authTitle = ref('');
  const authSubtitle = ref('');
  const authProgress = ref(0);
  const authStatus = ref('loading'); // 'loading' | 'success' | 'error'

  let pageLoadingTimeout = null;

  function startPageLoading(title = 'Chargement de la page...') {
    if (pageLoadingTimeout) clearTimeout(pageLoadingTimeout);
    pageTitle.value = title;
    isPageLoading.value = true;
  }

  function stopPageLoading(delay = 350) {
    if (pageLoadingTimeout) clearTimeout(pageLoadingTimeout);
    pageLoadingTimeout = setTimeout(() => {
      isPageLoading.value = false;
    }, delay);
  }

  function startAuthTransition({ title = 'Connexion en cours...', subtitle = 'Vérification sécurisée de vos accès...' }) {
    isAuthTransition.value = true;
    authStatus.value = 'loading';
    authTitle.value = title;
    authSubtitle.value = subtitle;
    authProgress.value = 15;
  }

  function setAuthProgress(progress, title, subtitle) {
    authProgress.value = Math.min(100, Math.max(0, progress));
    if (title) authTitle.value = title;
    if (subtitle) authSubtitle.value = subtitle;
  }

  function completeAuthTransition({ title = 'Authentification réussie !', subtitle = 'Redirection vers votre espace...' }) {
    authStatus.value = 'success';
    authProgress.value = 100;
    if (title) authTitle.value = title;
    if (subtitle) authSubtitle.value = subtitle;
  }

  function closeAuthTransition(delay = 300) {
    setTimeout(() => {
      isAuthTransition.value = false;
      authProgress.value = 0;
      authStatus.value = 'loading';
    }, delay);
  }

  return {
    isPageLoading,
    pageTitle,
    startPageLoading,
    stopPageLoading,

    isAuthTransition,
    authTitle,
    authSubtitle,
    authProgress,
    authStatus,
    startAuthTransition,
    setAuthProgress,
    completeAuthTransition,
    closeAuthTransition
  };
});
