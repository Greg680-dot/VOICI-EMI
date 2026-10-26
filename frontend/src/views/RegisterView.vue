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

const name = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const accountType = ref('citoyen');
const error = ref('');
const success = ref('');
const loading = ref(false);

const handleRegister = async () => {
  error.value = '';
  success.value = '';

  if (password.value !== passwordConfirmation.value) {
    error.value = 'Les mots de passe ne correspondent pas.';
    toastStore.warning(error.value);
    return;
  }

  loading.value = true;

  // Lancement de l'animation de transition
  loadingStore.startAuthTransition({
    title: 'Création de votre compte...',
    subtitle: 'Enregistrement sécurisé de votre profil sur VoiCI-EMI...'
  });

  try {
    loadingStore.setAuthProgress(40, 'Création du profil citoyen...', 'Initialisation des accès aux cours et à la communauté...');

    const response = await fetch('/api/auth/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name: name.value,
        email: email.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value,
        account_type: accountType.value
      })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Échec de l\'inscription.');
    }

    if (accountType.value === 'partenaire') {
      loadingStore.completeAuthTransition({
        title: 'Dossier Partenaire Transmis !',
        subtitle: 'Votre demande sera examinée par l\'administration.'
      });
      await new Promise(resolve => setTimeout(resolve, 800));
      loadingStore.closeAuthTransition(300);

      const msg = 'Votre compte partenaire a été créé. Il sera activé après vérification manuelle par notre administration.';
      success.value = msg;
      toastStore.success(msg, 'Demande transmise');
      name.value = '';
      email.value = '';
      password.value = '';
      passwordConfirmation.value = '';
    } else {
      loadingStore.setAuthProgress(80, 'Compte activé !', 'Finalisation de la configuration...');
      await new Promise(resolve => setTimeout(resolve, 400));

      loadingStore.completeAuthTransition({
        title: `Bienvenue, ${data.user?.name || 'Citoyen'} !`,
        subtitle: 'Ouverture de votre espace d\'apprentissage...'
      });

      authStore.login(data.access_token, data.user);
      toastStore.success(`Bienvenue sur VoiCI-EMI, ${data.user?.name || 'Citoyen'} !`);

      await new Promise(resolve => setTimeout(resolve, 550));
      router.push({ name: 'dashboard' });
      loadingStore.closeAuthTransition(400);
    }
  } catch (err) {
    loadingStore.closeAuthTransition(0);
    const msg = err.message || 'Une erreur s\'est produite.';
    error.value = msg;
    toastStore.error(msg);
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="max-w-4xl mx-auto my-4 sm:my-8 text-left">
    <div class="bg-white rounded-3xl border border-surface-border shadow-floating overflow-hidden grid md:grid-cols-12 min-h-[580px]">
      
      <!-- Institutional Reassurance Left Panel -->
      <div class="md:col-span-5 bg-navy p-6 sm:p-8 md:p-10 text-white flex flex-col justify-between relative overflow-hidden">
        <div class="absolute -right-16 -bottom-16 w-56 h-56 rounded-full bg-navy-800/60 pointer-events-none" aria-hidden="true"></div>
        <div class="absolute -left-10 -top-10 w-36 h-36 rounded-full bg-amber-500/10 pointer-events-none" aria-hidden="true"></div>

        <div class="space-y-6 relative z-10">
          <div class="inline-flex items-center gap-2 bg-navy-800/80 border border-navy-600/80 px-3 py-1 rounded-full text-xs text-amber-300">
            <span class="w-1.5 h-1.5 rounded-full bg-amber" aria-hidden="true"></span>
            <span>Rejoindre la Communauté VoiCI-EMI</span>
          </div>

          <div class="space-y-2">
            <h2 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-white leading-tight">
              Prenez part à la défense de la vérité publique
            </h2>
            <p class="text-xs sm:text-sm text-navy-200 leading-relaxed font-light">
              Créez un compte gratuit pour enregistrer vos attestations de formation, participer aux débats et signaler les intox en circulation au Bénin.
            </p>
          </div>
        </div>

        <div class="my-6 space-y-3 relative z-10 text-xs text-navy-100">
          <div class="flex items-center gap-3">
            <div class="w-5 h-5 rounded-full bg-navy-800 border border-navy-600 flex items-center justify-center text-amber-300 font-bold">✓</div>
            <span>Suivi d'apprentissage personnalisé</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-5 h-5 rounded-full bg-navy-800 border border-navy-600 flex items-center justify-center text-amber-300 font-bold">✓</div>
            <span>Historique de vos fact-checks</span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-5 h-5 rounded-full bg-navy-800 border border-navy-600 flex items-center justify-center text-amber-300 font-bold">✓</div>
            <span>Contribution aux forums et blogs</span>
          </div>
        </div>

        <div class="pt-4 border-t border-navy-800/80 text-[11px] text-navy-300 flex items-center justify-between relative z-10">
          <span>Données protégées • Bénin</span>
          <span class="font-medium text-amber-400">100% Gratuit</span>
        </div>
      </div>

      <!-- Right Form Panel -->
      <div class="md:col-span-7 p-6 sm:p-8 md:p-12 flex flex-col justify-center">
        <div class="max-w-md w-full mx-auto space-y-6">
          
          <div class="space-y-1 text-left">
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-ink-base tracking-tight">
              Créer votre compte
            </h1>
            <p class="text-xs sm:text-sm text-ink-muted leading-relaxed">
              Sélectionnez votre profil d'usage et complétez les informations requises.
            </p>
          </div>

          <!-- Success Alert -->
          <div 
            v-if="success" 
            role="status" 
            class="rounded-xl p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-start gap-3"
          >
            <span class="text-emerald-600 font-bold">✓</span>
            <div class="flex-1 font-medium leading-relaxed">{{ success }}</div>
          </div>

          <!-- Error Alert -->
          <div 
            v-if="error" 
            role="alert" 
            class="rounded-xl p-4 bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-start gap-3"
          >
            <span class="text-rose-600 font-bold">!</span>
            <div class="flex-1 font-medium leading-relaxed">{{ error }}</div>
          </div>

          <!-- Form -->
          <form @submit.prevent="handleRegister" class="space-y-4" novalidate>
            <BaseInput
              id="register-name"
              v-model="name"
              label="Nom complet ou pseudonyme"
              placeholder="ex: Amina Kpodékon"
              autocomplete="name"
              required
              :disabled="loading"
            />

            <BaseInput
              id="register-email"
              v-model="email"
              type="email"
              label="Adresse email"
              placeholder="ex: amina@domaine.bj"
              autocomplete="email"
              required
              :disabled="loading"
            />

            <div class="space-y-1.5">
              <label for="register-account-type" class="block text-xs font-semibold uppercase tracking-wider text-ink-base">
                Profil d'utilisation
              </label>
              <select
                id="register-account-type"
                v-model="accountType"
                class="w-full rounded-xl border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink-base focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer"
              >
                <option value="citoyen">Citoyen (Commenter, s'exprimer, signaler)</option>
                <option value="apprenant">Apprenant (Suivre des cours, obtenir des certificats)</option>
                <option value="partenaire">Partenaire (Média, ONG, Institution — Soumis à validation)</option>
              </select>
            </div>

            <BaseInput
              id="register-password"
              v-model="password"
              type="password"
              label="Mot de passe"
              placeholder="••••••••••••"
              autocomplete="new-password"
              required
              :disabled="loading"
            />

            <BaseInput
              id="register-password-confirm"
              v-model="passwordConfirmation"
              type="password"
              label="Confirmer le mot de passe"
              placeholder="••••••••••••"
              autocomplete="new-password"
              required
              :disabled="loading"
            />

            <div class="pt-2">
              <BaseButton
                type="submit"
                variant="accent"
                size="lg"
                class="w-full shadow-card"
                :loading="loading"
              >
                Créer mon compte
              </BaseButton>
            </div>
          </form>

          <!-- Footer link to Login -->
          <div class="pt-4 border-t border-surface-border text-center">
            <p class="text-xs text-ink-muted">
              Déjà inscrit sur la plateforme ?
              <router-link 
                :to="{ name: 'login' }" 
                class="font-semibold text-navy-700 hover:text-amber-600 hover:underline ml-1"
              >
                Se connecter
              </router-link>
            </p>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>
