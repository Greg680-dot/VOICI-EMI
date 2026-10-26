<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from './stores/auth';
import BaseToast from './components/ui/BaseToast.vue';
import LoadingOverlay from './components/ui/LoadingOverlay.vue';

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();

const selectedLanguage = ref('fr');
const simplifiedReading = ref(false);
const highContrast = ref(false);

const languages = {
  fr: { 
    navHome: 'Accueil', 
    navNews: 'Actualités',
    navFc: 'Vérification', 
    navLms: 'Formations', 
    navDashboard: 'Mon Espace', 
    logout: 'Déconnexion', 
    login: 'Connexion',
    roleActive: 'Rôle' 
  },
  fon: { 
    navHome: 'Akwaba', 
    navNews: 'Wɛn lɛ',
    navFc: 'Nugbó kplɔ́n', 
    navLms: 'Wèmè kplɔnkplɔn', 
    navDashboard: 'Nyi tɛn', 
    logout: 'Tɔ́n', 
    login: 'Byɔ mɛ',
    roleActive: 'Azɔ̌' 
  },
  yo: { 
    navHome: 'Kaabo', 
    navNews: 'Ìròyìn',
    navFc: 'Ayẹwo', 
    navLms: 'Ẹ̀kọ́', 
    navDashboard: 'Ojúewé mi', 
    logout: 'Jáde', 
    login: 'Wọlé',
    roleActive: 'Iṣẹ́' 
  },
  bar: { 
    navHome: 'Alafia', 
    navNews: 'Labari',
    navFc: 'Geesia', 
    navLms: 'Kplɔnkplɔn', 
    navDashboard: 'Nɛ san', 
    logout: 'Yasin', 
    login: 'Duru',
    roleActive: 'Kowo' 
  }
};

const handleLogout = () => {
  authStore.logout();
  router.push({ name: 'login' });
};
</script>

<template>
  <div 
    :class="{ 
      'reading-mode': simplifiedReading, 
      'high-contrast': highContrast 
    }"
    class="min-h-screen bg-white text-ink-base flex flex-col font-sans selection:bg-slate-200 selection:text-slate-900 pb-16 md:pb-0"
  >
    <!-- Accessible skip link for screen readers -->
    <a 
      href="#main-content" 
      class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 bg-amber text-white font-bold py-2 px-4 rounded-lg z-50 shadow-floating focus:outline-none"
    >
      Passer directement au contenu principal
    </a>

    <!-- Global Toast Container -->
    <BaseToast />

    <!-- Global Chic Page & Auth Loading Overlay -->
    <LoadingOverlay />

    <!-- Top Institutional Header -->
    <header class="bg-navy text-white sticky top-0 z-40 border-b border-navy-800 shadow-subtle">
      <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2 sm:gap-4">
        
        <!-- Brand identity -->
        <router-link :to="{ name: 'home' }" class="flex items-center gap-2 sm:gap-3 focus:outline-none focus:ring-2 focus:ring-amber rounded-lg py-1 shrink-0">
          <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber flex items-center justify-center text-white font-serif font-black text-base sm:text-lg shadow-xs">
            V
          </div>
          <div class="flex flex-col">
            <span class="font-serif font-bold text-base sm:text-lg tracking-tight leading-none text-white">VoiCI-EMI</span>
            <span class="hidden sm:inline text-[10px] text-amber-300 font-medium tracking-widest uppercase mt-0.5">Éducation aux Médias</span>
          </div>
        </router-link>

        <!-- Desktop Navigation Links (>= 768px) -->
        <nav class="hidden md:flex items-center gap-1 text-sm font-medium" aria-label="Navigation principale">
          <router-link 
            :to="{ name: 'home' }" 
            class="px-3 py-2 rounded-lg text-navy-100 hover:text-white hover:bg-navy-600 transition"
            :class="route.name === 'home' ? 'text-white bg-navy-800 font-semibold shadow-xs' : ''"
          >
            {{ languages[selectedLanguage].navHome }}
          </router-link>

          <router-link 
            :to="{ name: 'news' }" 
            class="px-3 py-2 rounded-lg text-navy-100 hover:text-white hover:bg-navy-600 transition flex items-center gap-1.5"
            :class="route.name === 'news' ? 'text-white bg-navy-800 font-semibold shadow-xs' : ''"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            {{ languages[selectedLanguage].navNews }}
          </router-link>

          <router-link 
            :to="{ name: 'fact-checking' }" 
            class="px-3 py-2 rounded-lg text-navy-100 hover:text-white hover:bg-navy-600 transition"
            :class="route.name === 'fact-checking' ? 'text-white bg-navy-800 font-semibold shadow-xs' : ''"
          >
            {{ languages[selectedLanguage].navFc }}
          </router-link>

          <router-link 
            :to="{ name: 'lms' }" 
            class="px-3 py-2 rounded-lg text-navy-100 hover:text-white hover:bg-navy-600 transition"
            :class="route.name === 'lms' ? 'text-white bg-navy-800 font-semibold shadow-xs' : ''"
          >
            {{ languages[selectedLanguage].navLms }}
          </router-link>

          <router-link 
            :to="{ name: 'dashboard' }" 
            class="px-3 py-2 rounded-lg text-navy-100 hover:text-white hover:bg-navy-600 transition"
            :class="route.name === 'dashboard' ? 'text-white bg-navy-800 font-semibold shadow-xs' : ''"
          >
            {{ languages[selectedLanguage].navDashboard }}
          </router-link>
        </nav>

        <!-- Right utility controls (Accessibility & User) -->
        <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
          
          <!-- Language Selector -->
          <div class="relative">
            <label for="lang-select" class="sr-only">Langue</label>
            <select 
              id="lang-select" 
              v-model="selectedLanguage"
              class="bg-navy-800 hover:bg-navy-600 text-white text-[11px] sm:text-xs border border-navy-600 rounded-lg px-2 sm:px-2.5 py-1.5 focus:outline-none focus:ring-2 focus:ring-amber cursor-pointer"
            >
              <option value="fr">Français</option>
              <option value="fon">Fɔngbe</option>
              <option value="yo">Yorùbá</option>
              <option value="bar">Baatɔnum</option>
            </select>
          </div>

          <!-- Simplified Reading Mode Toggle -->
          <button 
            type="button"
            @click="simplifiedReading = !simplifiedReading"
            class="p-1 sm:p-1.5 rounded-lg border text-[11px] sm:text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber"
            :class="simplifiedReading ? 'bg-amber text-navy-900 border-amber font-bold' : 'bg-navy-800 text-navy-100 border-navy-600 hover:bg-navy-600'"
            :title="simplifiedReading ? 'Désactiver la lecture simplifiée' : 'Activer la lecture simplifiée (FALC)'"
            :aria-pressed="simplifiedReading"
          >
            <span class="sr-only">Mode FALC :</span>
            <span aria-hidden="true" class="px-0.5 sm:px-1 font-mono">FALC</span>
          </button>

          <!-- High contrast toggle -->
          <button 
            type="button"
            @click="highContrast = !highContrast" 
            class="p-1 sm:p-1.5 rounded-lg border border-navy-600 bg-navy-800 hover:bg-navy-600 text-navy-100 text-[11px] sm:text-xs transition focus:outline-none focus:ring-2 focus:ring-amber"
            title="Activer/Désactiver le contraste élevé"
            :aria-pressed="highContrast"
          >
            <span class="sr-only">Contraste</span>
            <span aria-hidden="true" class="px-0.5">◐</span>
          </button>

          <!-- User auth pill / status (Desktop & mobile) -->
          <div v-if="authStore.isAuthenticated" class="flex items-center gap-1.5 sm:gap-2 text-xs">
            <span class="hidden xl:inline-flex items-center px-2 py-0.5 rounded-full bg-navy-800 border border-navy-600 text-amber-300 font-medium text-[11px]">
              {{ authStore.currentRole }}
            </span>
            <button 
              @click="handleLogout"
              class="bg-navy-800 hover:bg-rose-700 text-white px-2 sm:px-2.5 py-1.5 rounded-lg border border-navy-600 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber flex items-center gap-1"
              title="Déconnexion"
            >
              <span class="hidden sm:inline">{{ languages[selectedLanguage].logout }}</span>
              <span class="sm:hidden text-xs">🚪</span>
            </button>
          </div>

          <div v-else class="flex items-center">
            <router-link 
              :to="{ name: 'login' }" 
              class="bg-amber hover:bg-amber-600 text-white font-semibold text-xs px-2.5 sm:px-3.5 py-1.5 rounded-lg shadow-xs transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber"
            >
              {{ languages[selectedLanguage].login }}
            </router-link>
          </div>
        </div>

      </div>
    </header>

    <!-- Main View Content Area -->
    <main id="main-content" class="flex-1 max-w-7xl w-full mx-auto px-3 sm:px-6 lg:px-8 py-5 sm:py-8 pb-24 md:pb-10 focus:outline-none" tabindex="-1">
      <router-view v-slot="{ Component }">
        <transition name="page" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Footer institutionnel responsive -->
    <footer class="bg-navy-900 text-navy-200 border-t border-navy-800 py-8 md:py-10 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8">
          <div class="space-y-3">
            <div class="flex items-center gap-2 text-white font-serif font-bold text-lg">
              <span class="w-6 h-6 rounded bg-amber text-white inline-flex items-center justify-center text-xs font-sans font-black">V</span>
              VoiCI-EMI Bénin
            </div>
            <p class="text-xs text-navy-300 leading-relaxed font-light">
              Plateforme d'éducation aux médias et à l'information pour le renforcement de l'esprit critique et l'inclusion numérique en République du Bénin.
            </p>
          </div>

          <div>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-400 mb-3 font-sans">Piliers</h4>
            <ul class="space-y-2 text-xs">
              <li><router-link :to="{ name: 'lms' }" class="hover:text-white transition">Formations certifiantes</router-link></li>
              <li><router-link :to="{ name: 'fact-checking' }" class="hover:text-white transition">Vérification assistée par IA</router-link></li>
              <li><router-link :to="{ name: 'dashboard' }" class="hover:text-white transition">Débats et expression citoyenne</router-link></li>
              <li><router-link :to="{ name: 'dashboard' }" class="hover:text-white transition">Web-radio & podcasts locaux</router-link></li>
            </ul>
          </div>

          <div>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-400 mb-3 font-sans">Inclusion & Langues</h4>
            <ul class="space-y-2 text-xs">
              <li>Français (Langue officielle)</li>
              <li>Fɔngbe (Langue nationale)</li>
              <li>Yorùbá (Langue nationale)</li>
              <li>Baatɔnum / Bariba (Langue nationale)</li>
              <li>Conformité WCAG 2.1 niveau AA</li>
            </ul>
          </div>

          <div>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-amber-400 mb-3 font-sans">Cadre & Éthique</h4>
            <p class="text-xs text-navy-300 leading-relaxed font-light">
              L'IA n'émet qu'une estimation préliminaire. Toute publication officielle est soumise à la double validation obligatoire de journalistes humains assermentés.
            </p>
          </div>
        </div>

        <div class="mt-8 pt-6 border-t border-navy-800 text-center text-[11px] sm:text-xs text-navy-400">
          <p>© 2026 VoiCI-EMI Bénin. Plateforme publique inclusive d'Éducation aux Médias et à l'Information.</p>
        </div>
      </div>
    </footer>

    <!-- Mobile-First Bottom Navigation Bar (< 768px) -->
    <nav 
      class="md:hidden fixed bottom-0 inset-x-0 bg-white/95 backdrop-blur-md border-t border-surface-border z-40 px-2 py-1.5 flex items-center justify-around shadow-floating"
      aria-label="Navigation mobile"
    >
      <router-link 
        :to="{ name: 'home' }" 
        class="flex flex-col items-center justify-center p-1.5 rounded-lg text-ink-muted hover:text-navy transition min-w-[56px]"
        :class="route.name === 'home' ? 'text-navy font-bold' : ''"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <span class="text-[10px] mt-0.5">{{ languages[selectedLanguage].navHome }}</span>
      </router-link>

      <router-link 
        :to="{ name: 'news' }" 
        class="flex flex-col items-center justify-center p-1.5 rounded-lg text-ink-muted hover:text-navy transition min-w-[56px] relative"
        :class="route.name === 'news' ? 'text-navy font-bold' : ''"
      >
        <span class="absolute top-1 right-2.5 w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
        </svg>
        <span class="text-[10px] mt-0.5">{{ languages[selectedLanguage].navNews }}</span>
      </router-link>

      <router-link 
        :to="{ name: 'fact-checking' }" 
        class="flex flex-col items-center justify-center p-1.5 rounded-lg text-ink-muted hover:text-navy transition min-w-[56px]"
        :class="route.name === 'fact-checking' ? 'text-navy font-bold' : ''"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="text-[10px] mt-0.5">{{ languages[selectedLanguage].navFc }}</span>
      </router-link>

      <router-link 
        :to="{ name: 'lms' }" 
        class="flex flex-col items-center justify-center p-1.5 rounded-lg text-ink-muted hover:text-navy transition min-w-[56px]"
        :class="route.name === 'lms' ? 'text-navy font-bold' : ''"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <span class="text-[10px] mt-0.5">{{ languages[selectedLanguage].navLms }}</span>
      </router-link>

      <router-link 
        :to="{ name: authStore.isAuthenticated ? 'dashboard' : 'login' }" 
        class="flex flex-col items-center justify-center p-1.5 rounded-lg text-ink-muted hover:text-navy transition min-w-[56px]"
        :class="route.name === 'dashboard' || route.name === 'login' ? 'text-navy font-bold' : ''"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span class="text-[10px] mt-0.5">{{ authStore.isAuthenticated ? languages[selectedLanguage].navDashboard : languages[selectedLanguage].login }}</span>
      </router-link>
    </nav>
  </div>
</template>

<style>
/* High contrast utility */
.high-contrast {
  background-color: #000000 !important;
  color: #FFFFFF !important;
}

.high-contrast header {
  background-color: #0a0a0a !important;
  border-bottom: 2px solid #E08E28 !important;
}

.high-contrast button, 
.high-contrast select,
.high-contrast input,
.high-contrast textarea {
  background-color: #000000 !important;
  color: #FFFFFF !important;
  border: 2px solid #E08E28 !important;
}

.high-contrast a {
  color: #F7D6B3 !important;
  text-decoration: underline !important;
}

.high-contrast .bg-white,
.high-contrast .bg-surface-warm,
.high-contrast .bg-surface-muted {
  background-color: #000000 !important;
  border: 1px solid #FFFFFF !important;
  color: #FFFFFF !important;
}
</style>
