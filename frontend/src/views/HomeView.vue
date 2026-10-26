<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseBadge from '../components/ui/BaseBadge.vue';

const router = useRouter();
const authStore = useAuthStore();

// Live Date & Edition Meta
const currentDate = computed(() => {
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  const d = new Date().toLocaleDateString('fr-FR', options);
  return d.charAt(0).toUpperCase() + d.slice(1);
});

// Quick Interactive Rumor Search Widget
const searchRumorQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

const quickDatabase = [
  {
    title: "Recrutement sans concours de 1 500 agents à la douane par Mobile Money",
    verdict: "faible",
    verdictLabel: "INFIRMÉ / FAUX",
    source: "Direction Générale des Douanes & Trésor Public",
    date: "Il y a 5h",
    link: "/actualites"
  },
  {
    title: "Caravane nationale de formation à l'esprit critique dans les maisons de jeunes",
    verdict: "eleve",
    verdictLabel: "VÉRIFIÉ / EXACT",
    source: "Ministère du Numérique & VoiCI-EMI",
    date: "Il y a 2h",
    link: "/actualites"
  },
  {
    title: "Tisane miracle sur TikTok guérissant définitivement l'hypertension",
    verdict: "faible",
    verdictLabel: "INFIRMÉ / DANGEREUX",
    source: "OMS & Société Béninoise de Cardiologie",
    date: "Hier",
    link: "/actualites"
  },
  {
    title: "Diffusion de capsules audio de vérification en Bariba et Dendi dans le Borgou",
    verdict: "eleve",
    verdictLabel: "VÉRIFIÉ / EN COURS",
    source: "Radios Communautaires du Septentrion",
    date: "Hier",
    link: "/actualites"
  },
  {
    title: "Hausse supposée de 30% des frais d'inscription à l'Université d'Abomey-Calavi",
    verdict: "en_cours",
    verdictLabel: "ENQUÊTE EN COURS",
    source: "Cellule d'Investigation VoiCI-EMI",
    date: "Aujourd'hui",
    link: "/actualites"
  }
];

const handleSearchRumor = () => {
  if (!searchRumorQuery.value.trim()) {
    searchResults.value = [];
    return;
  }
  isSearching.value = true;
  const q = searchRumorQuery.value.toLowerCase();
  searchResults.value = quickDatabase.filter(item => 
    item.title.toLowerCase().includes(q) || 
    item.source.toLowerCase().includes(q)
  );
  isSearching.value = false;
};

// Selected Department for Regional Network
const selectedDept = ref('Littoral');

const departmentsData = {
  'Littoral': {
    name: "Cotonou & Littoral",
    hub: "Maison des Médias Thomas Mègnassan",
    trainers: 85,
    radioPartner: "Radio Tokpa & Cotonou FM",
    recentDebunk: "Démenti sur les faux bons d'achats subventionnés du port."
  },
  'Borgou': {
    name: "Parakou & Borgou",
    hub: "Centre Jeunesse & Culture de Parakou",
    trainers: 62,
    radioPartner: "Radio Deeman FM (Bariba, Dendi)",
    recentDebunk: "Alerte sur les faux SMS de virements Mobile Money aux commerçants."
  },
  'Atlantique': {
    name: "Abomey-Calavi & Atlantique",
    hub: "Campus Universitaire d'Abomey-Calavi",
    trainers: 74,
    radioPartner: "Radio Univers & Radio Sèdohoun",
    recentDebunk: "Clarification officielle sur le calendrier des examens d'État."
  },
  'Ouémé': {
    name: "Porto-Novo & Ouémé-Plateau",
    hub: "Espace Jeunesse Ouando",
    trainers: 48,
    radioPartner: "Radio Wêkê (Goun, Yoruba)",
    recentDebunk: "Démystification des messages viraux sur les recrutements municipaux."
  },
  'Atacora': {
    name: "Natitingou & Atacora-Donga",
    hub: "Maison des Jeunes de Natitingou",
    trainers: 41,
    radioPartner: "Radio Nanto FM (Ditammari, Waama)",
    recentDebunk: "Sensibilisation aux fausses consignes sanitaires sur les marchés."
  }
};

// Audio Player State for Lead Story
const isPlayingLeadAudio = ref(false);
const leadAudioProgress = ref(0);
let leadAudioTimer = null;

const toggleLeadAudio = () => {
  if (isPlayingLeadAudio.value) {
    isPlayingLeadAudio.value = false;
    clearInterval(leadAudioTimer);
  } else {
    isPlayingLeadAudio.value = true;
    leadAudioTimer = setInterval(() => {
      leadAudioProgress.value += 2;
      if (leadAudioProgress.value >= 100) {
        isPlayingLeadAudio.value = false;
        leadAudioProgress.value = 0;
        clearInterval(leadAudioTimer);
      }
    }, 200);
  }
};

// Live Flash Ticker index
const activeFlashIndex = ref(0);
const flashItems = [
  "URGENT • La Direction Générale des Douanes dément formellement tout recrutement direct sans concours par WhatsApp.",
  "SANTÉ • L'OMS met en garde contre les publications trompeuses sur les traitements miracles de l'hypertension.",
  "RÉSEAU • 50 ateliers d'esprit critique déployés cette semaine dans les établissements scolaires du Grand Nokoué.",
  "RADIO • Nouvelles capsules audio de fact-checking diffusées quotidiennement en Fɔngbe, Yorùbá et Bariba."
];

onMounted(() => {
  setInterval(() => {
    activeFlashIndex.value = (activeFlashIndex.value + 1) % flashItems.length;
  }, 6000);
});
</script>

<template>
  <!-- CLEAN WHITE EDITORIAL LUXURY: ZERO BLACK CONTOUR BORDERS, ZERO HARSH BLACK DIVIDERS -->
  <div class="space-y-14 animate-fade-in font-sans text-slate-800 pb-16">

    <!-- ========================================================================= -->
    <!-- 1. EDITORIAL MASTHEAD (SANS TRAITS NOIRS, ÉPURÉ & AÉRÉ) -->
    <!-- ========================================================================= -->
    <header class="space-y-4">
      
      <!-- Top Utility Meta Bar -->
      <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400 font-medium">
        <div class="flex items-center gap-3">
          <span class="font-serif font-bold text-slate-900">{{ currentDate }}</span>
          <span>•</span>
          <span>Édition Nationale • République du Bénin & CEDEAO</span>
          <span class="hidden md:inline">•</span>
          <span class="hidden md:inline">Supervision déontologique ODEM</span>
        </div>

        <div class="flex items-center gap-3">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 font-semibold text-[11px]">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
            Direct Rédaction
          </div>
          <router-link 
            :to="{ name: 'fact-checking' }" 
            class="text-slate-800 hover:text-slate-950 font-semibold transition hidden sm:inline"
          >
            Signaler une allégation
          </router-link>
        </div>
      </div>

      <!-- Grand Masthead Branding Title -->
      <div class="py-4 sm:py-6 flex flex-col md:flex-row items-baseline justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-xl bg-slate-900 text-white font-serif font-black text-2xl flex items-center justify-center shadow-xs">
              V
            </span>
            <h1 class="font-serif font-black text-3xl sm:text-5xl text-slate-950 tracking-tight leading-none">
              VoiCI-EMI
            </h1>
          </div>
          <p class="font-serif italic text-sm sm:text-base text-slate-500 mt-2">
            La Voix Citoyenne de l'Information • Observatoire Public d'Éducation aux Médias et Fact-Checking au Bénin
          </p>
        </div>

        <!-- Metric highlights (Frameless & Soft) -->
        <div class="flex items-center gap-6 sm:gap-8 text-right self-end md:self-auto pt-2 md:pt-0 w-full md:w-auto justify-between md:justify-end">
          <div>
            <div class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Faits analysés</div>
            <div class="text-xl sm:text-2xl font-serif font-black text-slate-900">1 420+</div>
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Communes</div>
            <div class="text-xl sm:text-2xl font-serif font-black text-slate-900">77 / 77</div>
          </div>
          <div>
            <div class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Fiabilité</div>
            <div class="text-xl sm:text-2xl font-serif font-black text-emerald-700">100% Vérifié</div>
          </div>
        </div>
      </div>

      <!-- Live Breaking Ticker Ribbon (Sans bordure noire, fond doux) -->
      <div class="bg-slate-50 rounded-2xl px-5 py-3 flex items-center gap-4 text-xs shadow-xs">
        <span class="bg-rose-600 text-white font-bold px-2.5 py-1 rounded-lg text-[10px] uppercase tracking-wider shrink-0 flex items-center gap-1.5 shadow-xs">
          <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
          Fil d'alerte
        </span>
        <div class="overflow-hidden relative h-5 flex-1">
          <transition name="page" mode="out-in">
            <p :key="activeFlashIndex" class="truncate font-medium text-slate-700">
              {{ flashItems[activeFlashIndex] }}
            </p>
          </transition>
        </div>
        <router-link 
          :to="{ name: 'news' }" 
          class="shrink-0 text-slate-700 hover:text-slate-950 font-semibold flex items-center gap-1 text-[11px]"
        >
          <span>Toutes les dépêches</span>
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </router-link>
      </div>

    </header>


    <!-- ========================================================================= -->
    <!-- 2. LA MANCHETTE & LE BAROMÈTRE (SANS BORDURE NOIRE, CARTE BLANCHE SOUPLE) -->
    <!-- ========================================================================= -->
    <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      
      <!-- COLONNE PRINCIPALE : L'ENQUÊTE À LA UNE (8 colonnes, Carte Blanche Douce) -->
      <article class="lg:col-span-8 bg-white rounded-3xl p-6 sm:p-10 shadow-[0_8px_30px_rgba(0,0,0,0.04)] space-y-5">
        
        <!-- Kicker & Metadata -->
        <div class="flex flex-wrap items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold tracking-widest text-rose-700 uppercase font-mono">
              Enquête de la Rédaction • Grand Angle
            </span>
            <span class="text-slate-300">•</span>
            <span class="text-xs text-slate-400">Publié le 22 Octobre 2026</span>
          </div>

          <!-- Official Stamp -->
          <BaseBadge variant="stamp-debunked" size="sm">
            ✗ INFIRMÉ / FAUX
          </BaseBadge>
        </div>

        <!-- Headline -->
        <h2 class="font-serif font-black text-2xl sm:text-4xl text-slate-950 leading-tight hover:text-slate-700 transition cursor-pointer">
          <router-link :to="{ name: 'news' }">
            Alerte WhatsApp : Enquête sur le faux recrutement sans concours de 1 500 agents à la douane béninoise
          </router-link>
        </h2>

        <!-- Lead Paragraph (Chapeau journalistique) -->
        <p class="font-serif text-base sm:text-lg text-slate-600 leading-relaxed text-justify">
          Un fichier audio massivement partagé dans les groupes familiaux et professionnels promettait une intégration directe dans la fonction publique douanière moyennant le versement de 25 000 FCFA par Mobile Money. Après vérification croisée auprès du Ministère de l'Économie et de la Direction Générale des Douanes, la cellule d'investigation VoiCI-EMI confirme une tentative organisée d'escroquerie financière.
        </p>

        <!-- Editorial Visual with Caption (Sans bordure noire) -->
        <div class="relative rounded-2xl overflow-hidden bg-slate-100 shadow-sm group">
          <img 
            src="/images/hero_editorial.jpg" 
            alt="Salle de rédaction et cellule de vérification de l'information VoiCI-EMI à Cotonou" 
            class="w-full h-64 sm:h-80 md:h-96 object-cover object-center group-hover:scale-102 transition-transform duration-500"
            loading="eager"
          />
          <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent"></div>
          
          <!-- Image caption badge -->
          <div class="absolute bottom-3 left-4 right-4 text-white flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
            <span class="font-medium text-slate-200">
              Cellule de veille et d'analyse numérique VoiCI-EMI • Cotonou, Bénin.
            </span>
            <span class="text-slate-400 font-mono text-[11px]">
              Crédit : Cellule Investigation & ODEM
            </span>
          </div>
        </div>

        <!-- Audio Dispatch Bar & Methodology Pill (Fond doux sans bordures noires) -->
        <div class="bg-slate-50 rounded-2xl p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-3 w-full sm:w-auto">
            <button 
              @click="toggleLeadAudio"
              class="w-10 h-10 rounded-full bg-slate-900 text-white hover:bg-slate-800 flex items-center justify-center transition shadow-sm shrink-0"
              :aria-label="isPlayingLeadAudio ? 'Mettre en pause' : 'Écouter l\'enquête audio'"
            >
              <svg v-if="isPlayingLeadAudio" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z" />
              </svg>
              <svg v-else class="w-5 h-5 ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </button>
            <div>
              <div class="text-xs font-bold text-slate-950 flex items-center gap-2">
                <span>Écouter le démenti audio</span>
                <span class="text-[10px] text-slate-500 bg-white px-2 py-0.5 rounded-md font-mono">2:10 min</span>
              </div>
              <div class="text-[11px] text-slate-500">
                Version vocale disponible en Français, Fɔngbe et Bariba
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <router-link 
              :to="{ name: 'news' }" 
              class="px-4 py-2 rounded-xl bg-slate-950 text-white text-xs font-bold hover:bg-slate-800 transition shadow-sm"
            >
              Lire le dossier complet
            </router-link>
            <router-link 
              :to="{ name: 'fact-checking' }" 
              class="px-4 py-2 rounded-xl bg-white text-slate-700 text-xs font-semibold hover:bg-slate-100 transition shadow-xs"
            >
              Méthodologie d'investigation
            </router-link>
          </div>
        </div>

      </article>

      <!-- COLONNE SECONDAIRE : LE BAROMÈTRE & DERNIÈRES VÉRIFICATIONS (4 colonnes) -->
      <aside class="lg:col-span-4 space-y-6">
        
        <!-- Live Rumor Feed Card (Sans bordure noire) -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-[0_8px_30px_rgba(0,0,0,0.04)] space-y-5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-rose-600"></span>
              <h3 class="font-serif font-black text-lg text-slate-950">
                Le Baromètre de la Vérité
              </h3>
            </div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest font-mono">
              En direct
            </span>
          </div>

          <!-- Feed items -->
          <div class="space-y-4">
            
            <div 
              v-for="(item, idx) in quickDatabase.slice(0, 4)" 
              :key="idx"
              class="p-3.5 rounded-2xl hover:bg-slate-50 transition-colors cursor-pointer group"
              @click="router.push(item.link)"
            >
              <div class="flex items-center justify-between gap-2 mb-1.5">
                <span class="text-[11px] font-mono text-slate-400">{{ item.date }}</span>
                <span 
                  class="text-[10px] font-bold tracking-wider px-2 py-0.5 rounded font-mono uppercase"
                  :class="item.verdict === 'eleve' 
                    ? 'bg-emerald-50 text-emerald-800' 
                    : item.verdict === 'faible' 
                      ? 'bg-rose-50 text-rose-800' 
                      : 'bg-amber-50 text-amber-800'"
                >
                  {{ item.verdictLabel }}
                </span>
              </div>

              <h4 class="font-serif font-bold text-sm text-slate-950 group-hover:text-slate-700 transition leading-snug">
                {{ item.title }}
              </h4>

              <div class="mt-1 text-[11px] text-slate-400">
                Source : {{ item.source }}
              </div>
            </div>

          </div>

          <div class="pt-2">
            <router-link 
              :to="{ name: 'news' }" 
              class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-bold flex items-center justify-center gap-1.5 transition"
            >
              <span>Accéder à toutes les vérifications</span>
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
              </svg>
            </router-link>
          </div>
        </div>

        <!-- WhatsApp Help Box (Sans bordure noire agressive) -->
        <div class="bg-slate-900 text-white rounded-3xl p-6 sm:p-7 shadow-[0_8px_30px_rgba(0,0,0,0.08)] space-y-3">
          <div class="flex items-center gap-2 text-xs font-bold text-amber-300 uppercase tracking-wider">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Désinformation WhatsApp</span>
          </div>

          <h4 class="font-serif font-bold text-base text-white leading-snug">
            Un message vous paraît suspect ou sensationnel ?
          </h4>
          <p class="text-xs text-slate-300 leading-relaxed">
            Avant de le transférer à vos contacts, déposez le texte ou la capture. Nos journalistes partenaires et notre algorithme d'analyse vous fournissent une réponse vérifiée sous 2 heures.
          </p>

          <router-link 
            :to="{ name: 'fact-checking' }" 
            class="inline-block w-full text-center py-2.5 px-4 rounded-xl bg-white text-slate-950 font-bold text-xs hover:bg-slate-100 transition shadow-sm mt-1"
          >
            Soumettre une rumeur à la rédaction
          </router-link>
        </div>

      </aside>

    </section>


    <!-- ========================================================================= -->
    <!-- 3. BANC DE VÉRIFICATION EXPRESS (SANS CONTOUR NOIR 2PX) -->
    <!-- ========================================================================= -->
    <section class="bg-white rounded-3xl p-6 sm:p-10 shadow-[0_8px_30px_rgba(0,0,0,0.04)] space-y-6">
      
      <div class="max-w-3xl">
        <span class="text-xs font-bold tracking-widest text-slate-500 uppercase font-mono">
          Moteur Public d'Interrogation
        </span>
        <h3 class="font-serif font-black text-2xl sm:text-3xl text-slate-950 mt-1">
          Vérifier une information avant de la partager
        </h3>
        <p class="text-sm text-slate-500 mt-2">
          Interrogez les archives de démentis officiels validés par l'Observatoire de la Déontologie et de l'Éthique dans les Médias (ODEM) et nos rédactions régionales.
        </p>
      </div>

      <!-- Search Input Container -->
      <div class="space-y-4">
        <div class="relative flex flex-col sm:flex-row items-stretch gap-2">
          <div class="relative flex-1">
            <input 
              v-model="searchRumorQuery"
              @input="handleSearchRumor"
              type="text" 
              placeholder="Ex: recrutement douane, remède hypertension, calendrier université..."
              class="w-full pl-11 pr-4 py-3.5 rounded-2xl bg-slate-50 hover:bg-slate-100/70 focus:bg-white text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-300 transition shadow-xs"
            />
            <svg class="w-5 h-5 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <button 
            @click="handleSearchRumor"
            class="px-6 py-3.5 rounded-2xl bg-slate-950 text-white font-bold text-sm hover:bg-slate-800 transition shrink-0 shadow-sm"
          >
            Rechercher dans les archives
          </button>
        </div>

        <!-- Quick Suggestions -->
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
          <span class="font-semibold">Recherches fréquentes :</span>
          <button 
            @click="searchRumorQuery = 'Douane'; handleSearchRumor()"
            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
          >
            Concours Douane
          </button>
          <button 
            @click="searchRumorQuery = 'Hypertension'; handleSearchRumor()"
            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
          >
            Tisane Hypertension
          </button>
          <button 
            @click="searchRumorQuery = 'Université'; handleSearchRumor()"
            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
          >
            Frais UAC Calavi
          </button>
          <button 
            @click="searchRumorQuery = 'Borgou'; handleSearchRumor()"
            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
          >
            Radios du Borgou
          </button>
        </div>
      </div>

      <!-- Live Search Results Dropdown -->
      <div v-if="searchRumorQuery && searchResults.length > 0" class="pt-4 space-y-4">
        <div class="text-xs font-bold text-slate-950 uppercase tracking-wider">
          Résultats trouvés dans les enquêtes certifiées ({{ searchResults.length }}) :
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="(res, rIdx) in searchResults" 
            :key="rIdx"
            class="p-5 rounded-2xl bg-slate-50 hover:bg-slate-100 transition-colors cursor-pointer flex flex-col justify-between"
            @click="router.push(res.link)"
          >
            <div>
              <div class="flex items-center justify-between gap-2 mb-2">
                <span class="text-[11px] font-mono text-slate-400">{{ res.date }}</span>
                <span 
                  class="text-[10px] font-bold px-2 py-0.5 rounded uppercase font-mono"
                  :class="res.verdict === 'eleve' ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'"
                >
                  {{ res.verdictLabel }}
                </span>
              </div>
              <h5 class="font-serif font-bold text-sm text-slate-950 leading-snug">
                {{ res.title }}
              </h5>
            </div>
            <div class="mt-4 pt-3 flex items-center justify-between text-xs text-slate-700 font-semibold">
              <span class="text-slate-400">Source : {{ res.source }}</span>
              <span class="text-slate-950">Consulter le dossier →</span>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="searchRumorQuery && searchResults.length === 0" class="pt-4 text-center py-6 text-slate-500 text-sm">
        <p>Aucun démenti officiel n'a encore été publié pour ce terme précis.</p>
        <router-link :to="{ name: 'fact-checking' }" class="text-slate-900 font-bold underline inline-block mt-2">
          Transmettre ce sujet à nos journalistes pour enquête immédiate
        </router-link>
      </div>

    </section>


    <!-- ========================================================================= -->
    <!-- 4. LES 4 PILIERS DE L'ÉCOSYSTÈME (SANS BORDURES NOIRES) -->
    <!-- ========================================================================= -->
    <section class="space-y-6">
      
      <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2">
        <div>
          <span class="text-xs font-bold tracking-widest text-slate-500 uppercase font-mono">
            Écosystème National
          </span>
          <h3 class="font-serif font-black text-2xl sm:text-3xl text-slate-950 mt-1">
            Les Quatre Piliers d'Action VoiCI-EMI
          </h3>
        </div>
        <p class="text-xs text-slate-500 max-w-md text-right hidden sm:block">
          Un dispositif républicain pour démocratiser l'esprit critique et l'inclusion numérique sur l'ensemble du territoire national.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- PILIER 1 : FACT CHECKING -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between group">
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-serif font-black text-2xl text-slate-300 group-hover:text-slate-900 transition">01</span>
              <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-rose-50 text-rose-800">
                Investigation
              </span>
            </div>
            <h4 class="font-serif font-bold text-lg text-slate-950 leading-snug">
              Banc de Fact-Checking & Réfutation
            </h4>
            <p class="text-xs text-slate-500 leading-relaxed">
              Détection assistée par intelligence artificielle et analyse rigoureuse par des journalistes professionnels selon la charte internationale de l'IFCN.
            </p>
          </div>
          <div class="pt-6 mt-6">
            <router-link 
              :to="{ name: 'fact-checking' }" 
              class="text-xs font-bold text-slate-950 hover:text-slate-700 flex items-center justify-between group-hover:translate-x-1 transition-transform"
            >
              <span>Accéder au laboratoire</span>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
          </div>
        </div>

        <!-- PILIER 2 : LMS FORMATIONS -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between group">
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-serif font-black text-2xl text-slate-300 group-hover:text-slate-900 transition">02</span>
              <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-800">
                Pédagogie
              </span>
            </div>
            <h4 class="font-serif font-bold text-lg text-slate-950 leading-snug">
              Académie Citoyenne Certifiante
            </h4>
            <p class="text-xs text-slate-500 leading-relaxed">
              Modules d'apprentissage progressifs (style Duolingo & Khan Academy), quiz de vérification d'images et cours accessibles en FALC pour tous les âges.
            </p>
          </div>
          <div class="pt-6 mt-6">
            <router-link 
              :to="{ name: 'lms' }" 
              class="text-xs font-bold text-slate-950 hover:text-slate-700 flex items-center justify-between group-hover:translate-x-1 transition-transform"
            >
              <span>Commencer un cours</span>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
          </div>
        </div>

        <!-- PILIER 3 : TRIBUNES & DÉBATS -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between group">
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-serif font-black text-2xl text-slate-300 group-hover:text-slate-900 transition">03</span>
              <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-emerald-50 text-emerald-800">
                Dialogue
              </span>
            </div>
            <h4 class="font-serif font-bold text-lg text-slate-950 leading-snug">
              Tribunes Républicaines & Débats
            </h4>
            <p class="text-xs text-slate-500 leading-relaxed">
              Un espace de discussion constructif, protégé contre le harcèlement et les discours de haine, où les citoyens débattent sur des faits prouvés.
            </p>
          </div>
          <div class="pt-6 mt-6">
            <router-link 
              :to="{ name: 'dashboard' }" 
              class="text-xs font-bold text-slate-950 hover:text-slate-700 flex items-center justify-between group-hover:translate-x-1 transition-transform"
            >
              <span>Rejoindre la tribune</span>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
          </div>
        </div>

        <!-- PILIER 4 : RADIOS & LANGUES LOCALES -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between group">
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-serif font-black text-2xl text-slate-300 group-hover:text-slate-900 transition">04</span>
              <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700">
                Inclusion
              </span>
            </div>
            <h4 class="font-serif font-bold text-lg text-slate-950 leading-snug">
              Web-Radio & Langues Nationales
            </h4>
            <p class="text-xs text-slate-500 leading-relaxed">
              Démocratiser l'information sans barrière de lecture grâce aux chroniques radio en Fɔngbe, Yorùbá, Bariba et Dendi diffusées en zone rurale.
            </p>
          </div>
          <div class="pt-6 mt-6">
            <router-link 
              :to="{ name: 'news' }" 
              class="text-xs font-bold text-slate-950 hover:text-slate-700 flex items-center justify-between group-hover:translate-x-1 transition-transform"
            >
              <span>Écouter les chroniques</span>
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </router-link>
          </div>
        </div>

      </div>

    </section>


    <!-- ========================================================================= -->
    <!-- 5. ANCRAGE TERRITORIAL : LE RÉSEAU DES 77 COMMUNES (Sans trait noir) -->
    <!-- ========================================================================= -->
    <section class="bg-slate-900 text-white rounded-3xl p-6 sm:p-10 shadow-[0_8px_30px_rgba(0,0,0,0.08)] space-y-8">
      
      <div class="max-w-3xl space-y-2">
        <span class="text-xs font-bold tracking-widest text-emerald-400 uppercase font-mono">
          Présence Terrain & Médiation de Proximité
        </span>
        <h3 class="font-serif font-black text-2xl sm:text-4xl text-white">
          La vérité s'établit sur le terrain, au plus près des citoyens
        </h3>
        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
          Pour contrer les rumeurs qui se propagent oralement sur les marchés et dans les gares routières, VoiCI-EMI déploie des médiateurs formés dans chaque département du Bénin en partenariat avec les radios locales.
        </p>
      </div>

      <!-- Department Selector Tabs -->
      <div class="flex flex-wrap items-center gap-2">
        <button 
          v-for="(data, key) in departmentsData" 
          :key="key"
          @click="selectedDept = key"
          :class="selectedDept === key ? 'bg-white text-slate-950 font-bold shadow-sm' : 'bg-slate-800 text-slate-300 hover:text-white'"
          class="px-4 py-2 rounded-xl text-xs transition"
        >
          {{ key }}
        </button>
      </div>

      <!-- Department Active Card -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 bg-slate-800/80 rounded-2xl p-6">
        <div class="space-y-1">
          <div class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Pôle d'intervention</div>
          <div class="text-lg font-serif font-bold text-white">{{ departmentsData[selectedDept].name }}</div>
          <div class="text-xs text-slate-300">Siège : {{ departmentsData[selectedDept].hub }}</div>
        </div>

        <div class="space-y-1">
          <div class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Médiateurs certifiés</div>
          <div class="text-2xl font-serif font-bold text-emerald-400">{{ departmentsData[selectedDept].trainers }} animateurs</div>
          <div class="text-xs text-slate-300">Partenaire : {{ departmentsData[selectedDept].radioPartner }}</div>
        </div>

        <div class="space-y-1">
          <div class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Dernière action d'assainissement</div>
          <div class="text-xs text-slate-100 font-medium leading-snug">{{ departmentsData[selectedDept].recentDebunk }}</div>
          <router-link :to="{ name: 'news' }" class="text-[11px] text-emerald-300 hover:text-white underline inline-block mt-2 font-semibold">
            Consulter les rapports de cette région →
          </router-link>
        </div>
      </div>

    </section>


    <!-- ========================================================================= -->
    <!-- 6. ENGAGEMENTS DÉONTOLOGIQUES & TRANSPARENCE INSTITUTIONNELLE -->
    <!-- ========================================================================= -->
    <section class="space-y-6 pt-4">
      
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
          <h4 class="font-serif font-black text-xl text-slate-950">
            Charte de Rigueur & Transparence Éditoriale
          </h4>
          <p class="text-xs text-slate-500 mt-1">
            VoiCI-EMI adhère aux principes fondamentaux de la déclaration de Munich sur les devoirs et droits des journalistes.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <span class="text-xs font-semibold text-slate-400">En partenariat avec :</span>
          <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-xs font-bold text-slate-800">
            ODEM Bénin
          </span>
          <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-xs font-bold text-slate-800">
            CNPA Bénin
          </span>
          <span class="px-3 py-1.5 rounded-xl bg-slate-100 text-xs font-bold text-slate-800">
            Réseau WANEP
          </span>
        </div>
      </div>

      <!-- Trust principles 3-column (Sans bordure noire) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-slate-600">
        <div class="p-6 rounded-3xl bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-2">
          <div class="font-bold text-slate-950 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
            <span>Indépendance et Impartialité</span>
          </div>
          <p class="leading-relaxed text-slate-500">
            Aucun verdict de vérification n'est influencé par des intérêts politiques ou commerciaux. Les critères d'évaluation sont strictement documentés.
          </p>
        </div>

        <div class="p-6 rounded-3xl bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-2">
          <div class="font-bold text-slate-950 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-slate-900"></span>
            <span>Traçabilité des Sources Primaires</span>
          </div>
          <p class="leading-relaxed text-slate-500">
            Chaque allégation vérifiée cite exhaustivement les documents officiels, rapports d'experts et entretiens contradictoires réalisés.
          </p>
        </div>

        <div class="p-6 rounded-3xl bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-2">
          <div class="font-bold text-slate-950 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-rose-600"></span>
            <span>Droit de Rectification Ouvert</span>
          </div>
          <p class="leading-relaxed text-slate-500">
            Toute partie concernée dispose d'une procédure transparente pour contester un verdict et apporter des éléments complémentaires probants.
          </p>
        </div>
      </div>

    </section>

  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.15s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(3px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
