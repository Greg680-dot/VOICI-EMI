<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toast';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseBadge from '../components/ui/BaseBadge.vue';
import BaseInput from '../components/ui/BaseInput.vue';
import SkeletonLoader from '../components/ui/SkeletonLoader.vue';
import EmptyState from '../components/ui/EmptyState.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();
const route = useRoute();

const contentType = ref('text');
const contentValue = ref('');
const loading = ref(false);
const error = ref('');

const currentSubmission = ref(null);
const historyList = ref([]);
const historyLoading = ref(false);

// Validator inputs (for Fact Checkers)
const finalVerdict = ref('');
const reliabilityRating = ref('faux');
const validatingVerdict = ref(false);

// Snopes rating system taxonomy
const snopesRatings = {
  vrai: {
    label: 'VRAI',
    tagline: 'L\'affirmation est matériellement exacte et corroborée.',
    bg: 'bg-emerald-600',
    border: 'border-emerald-700',
    text: 'text-emerald-950',
    lightBg: 'bg-emerald-50',
    icon: '✓',
    stamp: 'stamp-verified'
  },
  faux: {
    label: 'FAUX',
    tagline: 'L\'affirmation est mensongère, falsifiée ou dénuée de fondement.',
    bg: 'bg-rose-600',
    border: 'border-rose-700',
    text: 'text-rose-950',
    lightBg: 'bg-rose-50',
    icon: '✕',
    stamp: 'stamp-debunked'
  },
  melange: {
    label: 'MÉLANGE',
    tagline: 'Contient des éléments véridiques mêlés à des faits inexacts ou déformés.',
    bg: 'bg-amber-500',
    border: 'border-amber-600',
    text: 'text-amber-950',
    lightBg: 'bg-amber-50',
    icon: '◒',
    stamp: 'stamp-pending'
  },
  trompeur: {
    label: 'HORS CONTEXTE',
    tagline: 'Information ou image authentique détournée de son contexte original.',
    bg: 'bg-amber-600',
    border: 'border-amber-700',
    text: 'text-amber-950',
    lightBg: 'bg-amber-50',
    icon: '⚠',
    stamp: 'stamp-debunked'
  },
  non_verifie: {
    label: 'ENQUÊTE EN COURS',
    tagline: 'Les éléments probants sont actuellement en cours de recoupement.',
    bg: 'bg-navy-600',
    border: 'border-navy-700',
    text: 'text-navy-950',
    lightBg: 'bg-navy-50',
    icon: '⏳',
    stamp: 'stamp-pending'
  }
};

const getRatingConfig = (rating) => {
  return snopesRatings[rating] || snopesRatings.non_verifie;
};

const submitVerification = async () => {
  if (!contentValue.value.trim()) return;
  loading.value = true;
  error.value = '';
  currentSubmission.value = null;

  try {
    const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
    if (authStore.isAuthenticated) {
      headers['Authorization'] = `Bearer ${authStore.token}`;
    }

    // 1. Submit content
    const response = await fetch('/api/fact-checking/submit', {
      method: 'POST',
      headers: headers,
      body: JSON.stringify({
        content_type: contentType.value,
        content_value: contentValue.value
      })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Échec de la soumission.');
    }

    currentSubmission.value = data;
    toastStore.info('Contenu enregistré. Lancement du diagnostic Snopes-AI...');

    // 2. Automatically trigger AI Analysis
    await runAiAnalysis(data.id);
    await fetchHistory();

  } catch (err) {
    const msg = err.message || 'Une erreur s\'est produite lors de la vérification.';
    error.value = msg;
    toastStore.error(msg);
  } finally {
    loading.value = false;
  }
};

const runAiAnalysis = async (id) => {
  try {
    const headers = { 'Accept': 'application/json' };
    if (authStore.isAuthenticated) {
      headers['Authorization'] = `Bearer ${authStore.token}`;
    }

    const response = await fetch(`/api/fact-checking/${id}/analyze-ai`, {
      method: 'POST',
      headers: headers
    });
    const data = await response.json();

    if (response.ok) {
      currentSubmission.value = data;
      toastStore.success('Diagnostic préliminaire établi. En attente de certification par nos journalistes.');
    } else {
      error.value = data.message || 'Échec de l\'analyse IA.';
    }
  } catch (err) {
    console.error(err);
    toastStore.error('Erreur lors de l\'estimation IA.');
  }
};

const validateVerdict = async (id) => {
  if (!finalVerdict.value.trim()) {
    toastStore.warning('Veuillez rédiger la motivation officielle du verdict et citer les sources primaires.');
    return;
  }

  validatingVerdict.value = true;
  try {
    const response = await fetch(`/api/fact-checking/${id}/validate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        final_verdict: finalVerdict.value,
        reliability_rating: reliabilityRating.value
      })
    });

    const data = await response.json();
    if (response.ok) {
      toastStore.success('Verdict Snopes certifié et publié publiquement !', 'Attestation Officielle');
      currentSubmission.value = data.fact_check;
      finalVerdict.value = '';
      await fetchHistory();
    } else {
      toastStore.error(data.message || 'Échec de la validation.');
    }
  } catch (err) {
    console.error(err);
    toastStore.error('Erreur lors de la validation officielle.');
  } finally {
    validatingVerdict.value = false;
  }
};

const fetchHistory = async () => {
  if (!authStore.isAuthenticated) return;
  historyLoading.value = true;
  try {
    const response = await fetch('/api/fact-checking/history', {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    });
    if (response.ok) {
      historyList.value = await response.json();
    }
  } catch (err) {
    console.error(err);
  } finally {
    historyLoading.value = false;
  }
};

onMounted(() => {
  if (route.query.prefill) {
    contentValue.value = route.query.prefill;
    contentType.value = 'text';
  }
  fetchHistory();
});
</script>

<template>
  <div class="space-y-8 text-left">
    
    <!-- ======================================================== -->
    <!-- TOP HEADER: SNOPES-STYLE INVESTIGATION BANNER           -->
    <!-- ======================================================== -->
    <div class="bg-white rounded-3xl border border-surface-border p-6 sm:p-10 shadow-subtle flex flex-col md:flex-row md:items-center justify-between gap-6">
      <div class="space-y-2 max-w-2xl">
        <div class="inline-flex items-center gap-2 bg-navy-50 text-navy-800 border border-navy-200 px-3 py-1 rounded-full text-xs font-semibold">
          <span>Plateforme Nationale de Fact-Checking</span>
          <span class="text-navy-400">•</span>
          <span>Protocole de Vérification Snopes</span>
        </div>
        <h1 class="font-serif text-2xl sm:text-4xl font-bold text-ink-base tracking-tight">
          Vérification des Faits & Démystification
        </h1>
        <p class="text-xs sm:text-sm text-ink-muted leading-relaxed font-light">
          Chaque allégation est disséquée avec rigueur : évaluation algorithmique initiale, recherche de source primaire et verdict officiel selon les standards internationaux du fact-checking.
        </p>
      </div>

      <!-- Snopes Double Stamp Seal -->
      <div class="shrink-0 flex items-center gap-3">
        <BaseBadge variant="stamp-verified" size="lg">
          Double Sceau Éthique
        </BaseBadge>
      </div>
    </div>

    <!-- ======================================================== -->
    <!-- MAIN GRID: SUBMISSION FORM (4c) + VERDICT DOSSIER (8c)   -->
    <!-- ======================================================== -->
    <div class="grid lg:grid-cols-12 gap-8 items-start">
      
      <!-- ======================================== -->
      <!-- LEFT COLUMN: SUBMISSION FORM (4 cols)   -->
      <!-- ======================================== -->
      <aside class="lg:col-span-5 space-y-6">
        <BaseCard padding="md" class="space-y-5 shadow-subtle">
          <div class="border-b border-surface-border pb-3">
            <h2 class="font-serif font-bold text-lg text-ink-base">
              Soumettre une rumeur à examiner
            </h2>
            <p class="text-xs text-ink-muted mt-0.5">
              Service public ouvert à tous (3 vérifications/jour pour les invités).
            </p>
          </div>

          <form @submit.prevent="submitVerification" class="space-y-4">
            
            <div class="space-y-1.5">
              <label for="content-type" class="block text-xs font-semibold uppercase tracking-wider text-ink-base">
                Nature du support
              </label>
              <select
                id="content-type"
                v-model="contentType"
                class="w-full rounded-xl border border-surface-border bg-white px-3.5 py-2.5 text-sm text-ink-base focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer"
              >
                <option value="text">Message viral (WhatsApp, SMS, Réseaux)</option>
                <option value="link">Lien internet / Article de presse en ligne</option>
                <option value="image">Image / Capture d'écran suspecte</option>
              </select>
            </div>

            <div class="space-y-1.5">
              <label for="content-value" class="block text-xs font-semibold uppercase tracking-wider text-ink-base">
                Texte ou déclaration exacte
              </label>
              <textarea
                id="content-value"
                v-model="contentValue"
                rows="5"
                required
                :disabled="loading"
                placeholder="Collez ici l'intégralité du texte transféré ou la déclaration à vérifier..."
                class="w-full rounded-xl border border-surface-border bg-white p-3.5 text-sm text-ink-base focus:outline-none focus:ring-2 focus:ring-amber-500 placeholder:text-ink-light disabled:bg-surface-muted"
              ></textarea>
            </div>

            <div class="pt-2">
              <BaseButton
                type="submit"
                variant="accent"
                size="lg"
                class="w-full shadow-card"
                :loading="loading"
              >
                <span>Disséquer l'allégation</span>
                <span aria-hidden="true">→</span>
              </BaseButton>
            </div>
          </form>

          <div class="p-3 bg-surface-warm rounded-xl border border-surface-border text-[11px] text-ink-muted leading-relaxed">
            <span class="font-bold text-navy-800">Éthique VoiCI-EMI :</span> L'évaluation IA n'engage aucune décision finale sans signature humaine assermentée.
          </div>
        </BaseCard>

        <!-- User Verification History -->
        <BaseCard v-if="authStore.isAuthenticated" padding="sm" class="space-y-3">
          <div class="flex items-center justify-between border-b border-surface-border pb-2 px-2">
            <h3 class="font-serif font-bold text-sm text-ink-base">
              Vos dossiers récents
            </h3>
            <span class="text-[11px] text-ink-light font-mono">{{ historyList.length }}</span>
          </div>

          <div v-if="historyLoading" class="p-2 space-y-2">
            <SkeletonLoader variant="table-row" :count="2" />
          </div>

          <ul v-else-if="historyList.length > 0" class="space-y-2 max-h-60 overflow-y-auto px-1" role="list">
            <li 
              v-for="item in historyList" 
              :key="item.id"
              class="p-2.5 rounded-xl border border-surface-border bg-surface-warm/60 flex items-center justify-between gap-3 text-xs"
            >
              <span class="truncate font-medium text-ink-base flex-1">{{ item.content_value }}</span>
              <BaseBadge :variant="getRatingConfig(item.reliability_rating).stamp" size="sm">
                {{ getRatingConfig(item.reliability_rating).label }}
              </BaseBadge>
            </li>
          </ul>

          <div v-else class="p-3 text-center text-xs text-ink-light">
            Aucun historique de vérification pour le moment.
          </div>
        </BaseCard>
      </aside>

      <!-- ======================================================== -->
      <!-- RIGHT COLUMN: SNOPES-STYLE VERDICT ARTICLE (7 cols)      -->
      <!-- ======================================================== -->
      <main class="lg:col-span-7 space-y-6">
        
        <!-- Loading State Skeleton -->
        <BaseCard v-if="loading" padding="lg" class="space-y-6">
          <SkeletonLoader variant="rect" height="120px" />
          <SkeletonLoader variant="text" :count="4" />
        </BaseCard>

        <!-- SNOPES OFFICIAL VERDICT DOSSIER -->
        <div v-else-if="currentSubmission" class="space-y-6">
          
          <!-- Iconic Snopes Rating Block -->
          <div class="bg-white rounded-3xl border border-surface-border overflow-hidden shadow-floating">
            
            <!-- Top Rating Banner (Snopes-style big verdict badge) -->
            <div 
              class="p-6 sm:p-8 flex flex-col sm:flex-row items-center gap-6 border-b"
              :class="[
                getRatingConfig(currentSubmission.reliability_rating).lightBg,
                getRatingConfig(currentSubmission.reliability_rating).border
              ]"
            >
              <!-- Big Circle Rating Badge -->
              <div 
                class="w-24 h-24 rounded-full text-white flex flex-col items-center justify-center font-black shadow-card shrink-0 select-none"
                :class="getRatingConfig(currentSubmission.reliability_rating).bg"
              >
                <span class="text-3xl leading-none">{{ getRatingConfig(currentSubmission.reliability_rating).icon }}</span>
                <span class="text-xs font-serif uppercase tracking-widest mt-1">
                  {{ getRatingConfig(currentSubmission.reliability_rating).label }}
                </span>
              </div>

              <!-- Snopes Verdict Headline -->
              <div class="space-y-2 text-center sm:text-left flex-1">
                <div class="flex items-center justify-center sm:justify-start gap-2">
                  <span class="text-[11px] font-bold uppercase tracking-widest text-ink-light">
                    Classification Officielle Snopes-EMI
                  </span>
                  <BaseBadge :variant="getRatingConfig(currentSubmission.reliability_rating).stamp" size="sm">
                    Certifié
                  </BaseBadge>
                </div>
                
                <h3 class="font-serif text-2xl sm:text-3xl font-extrabold text-ink-base leading-tight">
                  Verdict : {{ getRatingConfig(currentSubmission.reliability_rating).label }}
                </h3>
                <p class="text-xs sm:text-sm text-ink-muted font-normal leading-relaxed">
                  {{ getRatingConfig(currentSubmission.reliability_rating).tagline }}
                </p>
              </div>
            </div>

            <!-- Snopes Article Body: The Claim, What's True, What's False -->
            <div class="p-6 sm:p-8 space-y-6 text-left">
              
              <!-- 1. The Claim Box (L'Allégation) -->
              <div class="bg-surface-warm p-5 rounded-2xl border-l-4 border-navy-700 space-y-2">
                <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-navy-800">
                  <span>L'allégation en circulation :</span>
                  <span class="text-ink-light font-mono">ID #{{ currentSubmission.id }}</span>
                </div>
                <blockquote class="font-serif italic text-sm sm:text-base text-ink-base leading-relaxed">
                  « {{ currentSubmission.content_value }} »
                </blockquote>
              </div>

              <!-- 2. AI Risk Gauge (Preliminary Analysis) -->
              <div class="bg-surface-muted/60 p-4 rounded-xl border border-surface-border space-y-2">
                <div class="flex items-center justify-between text-xs">
                  <span class="font-semibold text-ink-base">Indice de risque de désinformation (IA) :</span>
                  <span class="font-mono font-bold" :class="currentSubmission.ai_credibility_score > 50 ? 'text-rose-700' : 'text-emerald-700'">
                    {{ currentSubmission.ai_credibility_score || 0 }}%
                  </span>
                </div>
                <div class="w-full bg-surface-border h-2 rounded-full overflow-hidden">
                  <div 
                    class="h-full rounded-full transition-all duration-500"
                    :class="currentSubmission.ai_credibility_score > 50 ? 'bg-rose-600' : 'bg-emerald-600'"
                    :style="{ width: `${currentSubmission.ai_credibility_score || 0}%` }"
                  ></div>
                </div>
                <p v-if="currentSubmission.ai_notes" class="text-xs text-ink-muted italic pt-1">
                  {{ currentSubmission.ai_notes }}
                </p>
              </div>

              <!-- 3. What's True / What's False Snopes Breakdown -->
              <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-emerald-50/60 border border-emerald-200 rounded-2xl p-4 space-y-2">
                  <div class="flex items-center gap-2 text-emerald-900 font-serif font-bold text-xs uppercase tracking-wider">
                    <span>✓</span>
                    <span>Ce qui est exact</span>
                  </div>
                  <p class="text-xs text-ink-base leading-relaxed">
                    Le sujet mentionne une institution ou un contexte public réel faisant l'objet d'un intérêt citoyen légitime.
                  </p>
                </div>

                <div class="bg-rose-50/60 border border-rose-200 rounded-2xl p-4 space-y-2">
                  <div class="flex items-center gap-2 text-rose-900 font-serif font-bold text-xs uppercase tracking-wider">
                    <span>✕</span>
                    <span>Ce qui est faux / déformé</span>
                  </div>
                  <p class="text-xs text-ink-base leading-relaxed">
                    Aucun acte administratif, communiqué officiel ou preuve matérielle n'atteste de la véracité de cette promesse ou allégation.
                  </p>
                </div>
              </div>

              <!-- 4. Journalist Final Investigation Summary -->
              <div class="space-y-2 pt-2 border-t border-surface-border">
                <h4 class="font-serif font-bold text-base text-ink-base">
                  Motivation de l'enquête journalistique
                </h4>
                <p class="text-xs sm:text-sm text-ink-muted leading-relaxed font-light whitespace-pre-wrap">
                  {{ currentSubmission.final_verdict || 'Le dossier est actuellement examiné par les membres de la cellule de vérification de l\'ODEM et VoiCI-EMI.' }}
                </p>
              </div>

              <!-- 5. Byline & Transparency Meta -->
              <div class="pt-4 border-t border-surface-border/80 flex flex-wrap items-center justify-between gap-3 text-[11px] text-ink-light">
                <div class="flex items-center gap-2">
                  <span>Enquêteur référent :</span>
                  <span class="font-semibold text-navy-900">Rédaction VoiCI-EMI Bénin</span>
                </div>
                <div>
                  <span>Protocole Snopes • Code IFCN</span>
                </div>
              </div>

            </div>
          </div>

          <!-- ======================================================= -->
          <!-- FACT-CHECKER CERTIFICATION DRAWER (Role authorized)     -->
          <!-- ======================================================= -->
          <div 
            v-if="authStore.hasPermission('fact_checking_validate_ai')"
            class="bg-navy-900 text-white rounded-3xl p-6 sm:p-8 border border-navy-700 shadow-floating space-y-5"
          >
            <div class="border-b border-navy-700 pb-3">
              <span class="text-[10px] font-bold uppercase tracking-wider text-amber-300">Accès Rédacteur / Fact Checker</span>
              <h3 class="font-serif text-xl font-bold text-white mt-1">
                Certifier le verdict officiel Snopes
              </h3>
            </div>

            <div class="space-y-4">
              <!-- Snopes Rating Selector Pills -->
              <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-navy-200">
                  Sélectionnez l'étiquette de verdict Snopes :
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                  <button
                    v-for="(cfg, key) in snopesRatings"
                    :key="key"
                    type="button"
                    @click="reliabilityRating = key"
                    class="p-2.5 rounded-xl border text-xs font-bold transition flex items-center justify-center gap-1.5 cursor-pointer"
                    :class="reliabilityRating === key 
                      ? 'bg-amber-400 text-slate-950 border-amber-400 shadow-xs' 
                      : 'bg-navy-800 text-navy-200 border-navy-700 hover:bg-navy-700'"
                  >
                    <span>{{ cfg.icon }}</span>
                    <span>{{ cfg.label }}</span>
                  </button>
                </div>
              </div>

              <!-- Explanation Textarea -->
              <div class="space-y-1.5">
                <label for="verdict-notes" class="block text-xs font-semibold text-navy-200">
                  Explication du verdict & citations de sources :
                </label>
                <textarea
                  id="verdict-notes"
                  v-model="finalVerdict"
                  rows="4"
                  placeholder="Expliquez pourquoi ce fait est classé ainsi. Citez les ministères, arrêtés ou déclarations primaires recoupées..."
                  class="w-full rounded-xl border border-navy-700 bg-navy-950 text-white p-3.5 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 placeholder:text-navy-400"
                ></textarea>
              </div>

              <!-- Submit button -->
              <div class="pt-2">
                <BaseButton
                  variant="accent"
                  size="md"
                  :loading="validatingVerdict"
                  @click="validateVerdict(currentSubmission.id)"
                  class="w-full sm:w-auto shadow-card"
                >
                  <span>Apposer le tampon officiel & publier</span>
                </BaseButton>
              </div>
            </div>
          </div>

        </div>

        <!-- Initial Placeholder State -->
        <EmptyState 
          v-else
          title="Prêt pour l'investigation"
          description="Saisissez une rumeur, un lien ou un message WhatsApp dans le formulaire de gauche pour générer son dossier complet selon le protocole Snopes."
        />

      </main>

    </div>
  </div>
</template>
