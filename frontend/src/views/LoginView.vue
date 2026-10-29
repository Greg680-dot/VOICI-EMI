<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toast';
import { useLoadingStore } from '../stores/loading';
import BaseInput from '../components/ui/BaseInput.vue';
import BaseButton from '../components/ui/BaseButton.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();
const loadingStore = useLoadingStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

const handleLogin = async () => {
  error.value = '';
  loading.value = true;

  // Lancement immédiat de l'animation chic de transition
  loadingStore.startAuthTransition({
    title: 'Connexion en cours...',
    subtitle: 'Vérification chiffrée de vos accès sécurisés...'
  });

  try {
    loadingStore.setAuthProgress(35, 'Contrôle des identifiants...', 'Interrogation du serveur d\'authentification VoiCI-EMI...');

    const response = await fetch('/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        email: email.value,
        password: password.value
      })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Identifiants incorrects ou compte suspendu.');
    }

    // Animation de succès et de chargement vers l'espace
    loadingStore.setAuthProgress(75, 'Authentification validée !', `Compte vérifié • Rôle : ${data.user?.current_role || 'Citoyen'}`);
    
    // Léger délai pour apprécier la fluidité de l'animation
    await new Promise(resolve => setTimeout(resolve, 400));

    loadingStore.completeAuthTransition({
      title: `Bienvenue, ${data.user?.name || 'Citoyen'} !`,
      subtitle: 'Préparation et ouverture de votre espace de travail...'
    });

    authStore.login(data.access_token, data.user);
    toastStore.success(`Bienvenue sur VoiCI-EMI, ${data.user?.name || 'Citoyen'} !`);

    await new Promise(resolve => setTimeout(resolve, 550));
    router.push({ name: 'dashboard' });
    loadingStore.closeAuthTransition(400);

  } catch (err) {
    loadingStore.closeAuthTransition(0);
    const msg = err.message || 'Une erreur de réseau s\'est produite. Veuillez réessayer.';
    error.value = msg;
    toastStore.error(msg);
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="max-w-4xl mx-auto my-4 sm:my-8">
    <div class="bg-white rounded-3xl border border-surface-border shadow-floating overflow-hidden grid md:grid-cols-12 min-h-[540px]">
      
      <!-- Institutional Reassurance Left Panel (Desktop) -->
      <div class="md:col-span-5 bg-navy p-6 sm:p-8 md:p-10 text-white flex flex-col justify-between relative overflow-hidden">
        <!-- Subtle background texture -->
        <div class="absolute -right-16 -bottom-16 w-56 h-56 rounded-full bg-navy-800/60 pointer-events-none" aria-hidden="true"></div>
        <div class="absolute -left-10 -top-10 w-36 h-36 rounded-full bg-amber-500/10 pointer-events-none" aria-hidden="true"></div>

        <div class="space-y-6 relative z-10">
          <div class="inline-flex items-center gap-2 bg-navy-800/80 border border-navy-600/80 px-3 py-1 rounded-full text-xs text-amber-300">
            <span class="w-1.5 h-1.5 rounded-full bg-amber" aria-hidden="true"></span>
            <span>Portail Citoyen & Institutionnel</span>
          </div>

          <div class="space-y-2">
            <h2 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-white leading-tight">
              L'éducation aux médias pour tous au Bénin
            </h2>
            <p class="text-xs sm:text-sm text-navy-200 leading-relaxed font-light">
              Rejoignez la communauté VoiCI-EMI pour apprendre à déceler la désinformation, valider vos compétences et certifier l'information d'intérêt public.
            </p>
          </div>
        </div>

        <!-- Pillar highlights -->
        <div class="my-8 space-y-3 relative z-10">
          <div class="flex items-center gap-3 text-xs text-navy-100">
            <div class="w-5 h-5 rounded-full bg-navy-800 border border-navy-600 flex items-center justify-center text-amber-300 font-bold">✓</div>
            <span>Formations certifiantes gratuites</span>
          </div>
          <div class="flex items-center gap-3 text-xs text-navy-100">
            <div class="w-5 h-5 rounded-full bg-navy-800 border border-navy-600 flex items-center justify-center text-amber-300 font-bold">✓</div>
            <span>Vérification IA assistée par des journalistes</span>
          </div>
          <div class="flex items-center gap-3 text-xs text-navy-100">
            <div class="w-5 h-5 rounded-full bg-navy-800 border border-navy-600 flex items-center justify-center text-amber-300 font-bold">✓</div>
            <span>Contenu accessible en langues nationales</span>
          </div>
        </div>

        <!-- Trust badge -->
        <div class="pt-4 border-t border-navy-800/80 text-[11px] text-navy-300 flex items-center justify-between relative z-10">
          <span>Plateforme sécurisée VoiCI-EMI</span>
          <span class="font-medium text-amber-400">WCAG 2.1 AA</span>
        </div>
      </div>

      <!-- Right Form Panel -->
      <div class="md:col-span-7 p-6 sm:p-8 md:p-12 flex flex-col justify-center">
        <div class="max-w-md w-full mx-auto space-y-8">
          
          <div class="space-y-2 text-left">
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink-base tracking-tight">
              Connexion à votre espace
            </h1>
            <p class="text-xs sm:text-sm text-ink-muted leading-relaxed">
              Saisissez vos identifiants pour accéder à vos cours, certificats et aux outils de vérification.
            </p>
          </div>

          <!-- Error Alert Banner (Screen reader friendly) -->
          <div 
            v-if="error" 
            role="alert" 
            class="rounded-xl p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-3"
          >
            <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div class="flex-1 font-medium leading-relaxed">{{ error }}</div>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleLogin" class="space-y-5" novalidate>
            <BaseInput
              id="email-field"
              v-model="email"
              type="email"
              label="Adresse email"
              placeholder="ex: nom@domaine.bj"
              autocomplete="email"
              required
              :disabled="loading"
            >
              <template #prefix>
                <svg class="w-4 h-4 text-ink-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                </svg>
              </template>
            </BaseInput>

            <BaseInput
              id="password-field"
              v-model="password"
              type="password"
              label="Mot de passe"
              placeholder="••••••••••••"
              autocomplete="current-password"
              required
              :disabled="loading"
            >
              <template #prefix>
                <svg class="w-4 h-4 text-ink-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
              </template>
            </BaseInput>

            <div class="pt-2">
              <BaseButton
                type="submit"
                variant="primary"
                size="lg"
                class="w-full shadow-card hover:bg-navy-800"
                :loading="loading"
              >
                Se connecter
              </BaseButton>
            </div>
          </form>

          <!-- Footer registration link -->
          <div class="pt-4 border-t border-surface-border text-center">
            <p class="text-xs text-ink-muted">
              Pas encore de compte ?
              <router-link 
                :to="{ name: 'register' }" 
                class="font-semibold text-amber-600 hover:text-amber-700 hover:underline ml-1"
              >
                Créer un compte citoyen ou apprenant
              </router-link>
            </p>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>
