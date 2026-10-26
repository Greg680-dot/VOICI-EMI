import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null);
  const user = ref(JSON.parse(localStorage.getItem('user')) || null);

  const isAuthenticated = computed(() => !!token.value);

  const currentRole = computed(() => {
    if (!isAuthenticated.value) return 'Invité';
    return user.value?.current_role || 'Invité';
  });

  const permissions = computed(() => {
    if (!isAuthenticated.value) {
      return [
        'articles_read_published',
        'articles_share_only',
        'lms_view_preview',
        'fact_checking_submit_quota',
        'fact_checking_run_ai_quota',
        'community_read',
        'radio_listen',
        'library_download_public'
      ];
    }
    return user.value?.permissions || [];
  });

  function hasPermission(permission) {
    return permissions.value.includes(permission);
  }

  function hasRole(roleName) {
    if (!isAuthenticated.value) return roleName === 'Invité';
    return user.value?.roles?.includes(roleName) || currentRole.value === roleName;
  }

  function login(accessToken, userData) {
    token.value = accessToken;
    user.value = userData;
    localStorage.setItem('token', accessToken);
    localStorage.setItem('user', JSON.stringify(userData));
  }

  function logout() {
    token.value = null;
    user.value = null;
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  }

  return {
    token,
    user,
    isAuthenticated,
    currentRole,
    permissions,
    hasPermission,
    hasRole,
    login,
    logout
  };
});
