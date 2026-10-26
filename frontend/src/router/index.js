import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useLoadingStore } from '../stores/loading';

const routes = [
  { 
    path: '/', 
    name: 'home', 
    component: () => import('../views/HomeView.vue'),
    meta: { title: 'Accueil Institutionnel' }
  },
  { 
    path: '/login', 
    name: 'login', 
    component: () => import('../views/LoginView.vue'),
    meta: { title: 'Portail de Connexion' }
  },
  { 
    path: '/register', 
    name: 'register', 
    component: () => import('../views/RegisterView.vue'),
    meta: { title: 'Inscription Citoyenne' }
  },
  { 
    path: '/dashboard', 
    name: 'dashboard', 
    component: () => import('../views/DashboardView.vue'),
    meta: { requiresAuth: true, title: 'Espace & Tableau de Bord' }
  },
  { 
    path: '/actualites', 
    name: 'news', 
    component: () => import('../views/NewsFeedView.vue'),
    meta: { title: 'Fil d\'Actualités' }
  },
  { 
    path: '/fact-checking', 
    name: 'fact-checking', 
    component: () => import('../views/FactCheckingView.vue'),
    meta: { title: 'Vérification Assistée par IA' }
  },
  { 
    path: '/lms', 
    name: 'lms', 
    component: () => import('../views/LmsView.vue'),
    meta: { title: 'Académie & Formations' }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    } else {
      return { top: 0 };
    }
  }
});

router.beforeEach((to, from) => {
  const authStore = useAuthStore();
  const loadingStore = useLoadingStore();

  // If already in auth transition, let auth transition manage the screen
  if (!loadingStore.isAuthTransition) {
    const pageLabel = to.meta?.title ? `Chargement • ${to.meta.title}` : 'Chargement de la page...';
    loadingStore.startPageLoading(pageLabel);
  }

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login' };
  }
});

router.afterEach(() => {
  const loadingStore = useLoadingStore();
  loadingStore.stopPageLoading(350);
});

export default router;
