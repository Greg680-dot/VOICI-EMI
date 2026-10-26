<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseBadge from '../components/ui/BaseBadge.vue';
import BaseModal from '../components/ui/BaseModal.vue';
import SkeletonLoader from '../components/ui/SkeletonLoader.vue';
import EmptyState from '../components/ui/EmptyState.vue';

const router = useRouter();

// State
const loading = ref(true);
const articles = ref([]);
const searchQuery = ref('');
const selectedRubrique = ref('all');
const selectedRegion = ref('all');
const displayMode = ref('grid'); // 'grid' | 'wire'
const globalFalcMode = ref(false);

// Active audio dispatch
const activeAudioId = ref(null);
const isAudioPlaying = ref(false);
const audioProgress = ref(0);
let audioTimer = null;

// Selected Article for Full Dossier Modal
const selectedArticle = ref(null);
const isDossierOpen = ref(false);

// Live News Ticker State
const lastSyncTime = ref(new Date());
const isRefreshing = ref(false);
const activeTickerIndex = ref(0);
let tickerTimer = null;

// Fallback high-quality editorial articles
const defaultNews = [
  {
    id: 1,
    title: "Cotonou : Déploiement d'une caravane de médiateurs EMI dans les maisons des jeunes",
    slug: "cotonou-caravane-mediateurs-emi",
    rubrique: "Société & Jeunesse",
    region: "Cotonou & Littoral",
    published_at: new Date(Date.now() - 2 * 3600000).toISOString(),
    author: "Cellule Enquêtes • Rédaction Cotonou",
    reading_time: "3 min",
    lead: "Le Ministère du Numérique et VoiCI-EMI lancent un cycle de 50 ateliers de sensibilisation à l'esprit critique auprès des délégués scolaires et leaders associatifs.",
    content: "Le Ministère de l'Économie Numérique et de la Digitalisation, en synergie avec VoiCI-EMI et les associations de jeunesse, a officiellement lancé ce matin à Cotonou un cycle de 50 ateliers de formation à l'esprit critique. Destiné aux délégués scolaires et aux leaders d'associations de quartier, ce dispositif vise à déconstruire les arnaques financières circulant sur WhatsApp et à enseigner la méthode de vérification d'images inversées. Plus de 1 200 jeunes ont participé à cette première session.",
    summary_falc: "Des formateurs sont venus à Cotonou pour apprendre aux jeunes comment repérer les faux messages sur leur téléphone.\nLes jeunes apprennent à vérifier les photos et à ne pas partager des mensonges sur WhatsApp.",
    reliability_score: "eleve",
    verdict_label: "INFORMATION VÉRIFIÉE",
    source: "Ministère du Numérique & Préfecture du Littoral",
    image: "/images/hero_editorial.jpg",
    is_lead: true
  },
  {
    id: 2,
    title: "Alerte WhatsApp : Démenti formel sur un prétendu recrutement sans concours à la douane béninoise",
    slug: "alerte-recrutement-douane-beninoise",
    rubrique: "Économie & Emploi",
    region: "National (Tout le Bénin)",
    published_at: new Date(Date.now() - 5 * 3600000).toISOString(),
    author: "Service Fact-Checking & Économie",
    reading_time: "4 min",
    lead: "Un fichier audio viral affirmait que 1 500 agents douaniers étaient recrutés contre versement de 25 000 FCFA par Mobile Money. La douane et le trésor démentent formellement.",
    content: "Un message audio viral massivement transféré sur les réseaux sociaux prétend que la Direction Générale des Douanes du Bénin recrute sans condition de diplôme 1 500 agents contre un versement de 25 000 FCFA par Mobile Money. La cellule de communication du Ministère de l'Économie et des Finances et l'ODEM confirment qu'il s'agit d'une escroquerie bien rodée. Aucun recrutement n'est en cours sans arrêté publié au Journal Officiel.",
    summary_falc: "Attention : Un faux message dit que la douane recrute des personnes si elles envoient de l'argent par téléphone.\nC'est un mensonge d'escrocs.\nL'État ne demande jamais d'argent pour un recrutement.",
    reliability_score: "faible",
    verdict_label: "DÉMENTI OFFICIEL / FAUX",
    source: "Direction Générale des Douanes & Trésor Public",
    image: null,
    is_lead: false
  },
  {
    id: 3,
    title: "Parakou : Production et diffusion quotidienne de chroniques d'actualité en Bariba et Dendi",
    slug: "parakou-chroniques-radios-bariba-dendi",
    rubrique: "Médias & Langues",
    region: "Borgou & Septentrion",
    published_at: new Date(Date.now() - 8 * 3600000).toISOString(),
    author: "Bureau Régional Parakou",
    reading_time: "2 min",
    lead: "Les radios communautaires du Borgou et de l'Alibori diffusent désormais les dépêches de vérification dans les langues du terroir pour protéger les commerçants ruraux.",
    content: "Les radios communautaires du Septentrion (Borgou et Alibori) diffusent désormais trois fois par jour des capsules éducatives de deux minutes traduisant les verdicts de vérification dans les langues du terroir. Cette initiative permet d'informer les commerçantes et producteurs ruraux des techniques d'escroquerie par faux SMS bancaires sans barrière de langue.",
    summary_falc: "À Parakou, les radios locales expliquent à la population en langue Bariba et Dendi comment éviter les arnaques sur téléphone.\nTout le monde peut écouter et comprendre facilement.",
    reliability_score: "eleve",
    verdict_label: "ACTUALITÉ CONFIRMÉE",
    source: "Réseau des Radios Communautaires du Septentrion",
    image: null,
    is_lead: false
  },
  {
    id: 4,
    title: "Afrique de l'Ouest : Concertation régionale contre la prolifération des deepfakes électoraux",
    slug: "afrique-ouest-concertation-deepfakes",
    rubrique: "Gouvernance & Numérique",
    region: "Régional CEDEAO",
    published_at: new Date(Date.now() - 24 * 3600000).toISOString(),
    author: "Desk International & CEDEAO",
    reading_time: "5 min",
    lead: "Les rédactions de vérification du Bénin, du Sénégal, de Côte d'Ivoire et du Togo mutualisent leurs cellules d'investigation numérique pour identifier les manipulations en moins de 15 minutes.",
    content: "Des rédactions de vérification du Bénin, du Togo, de Côte d'Ivoire et du Sénégal ont signé une convention de mutualisation de leurs cellules d'investigation numérique. Ce réseau transfrontalier permet d'analyser en moins de 15 minutes les vidéos manipulées par intelligence artificielle lors des scrutins électoraux régionaux.",
    summary_falc: "Les journalistes de plusieurs pays d'Afrique de l'Ouest travaillent ensemble pour trouver rapidement les fausses vidéos créées par ordinateur.",
    reliability_score: "eleve",
    verdict_label: "RAPPORT D'ENQUÊTE",
    source: "Consortium Ouest-Africain de Fact-Checking",
    image: null,
    is_lead: false
  },
  {
    id: 5,
    title: "Santé Publique : L'OMS et les cardiologues alertent sur les fausses décoctions contre l'hypertension",
    slug: "sante-publique-fausses-infusions-hypertension",
    rubrique: "Santé & Société",
    region: "National (Tout le Bénin)",
    published_at: new Date(Date.now() - 36 * 3600000).toISOString(),
    author: "Pôle Santé & Sciences",
    reading_time: "3 min",
    lead: "Des vidéos trompeuses font la promotion de tisanes affirmant guérir l'hypertension. Les spécialistes rappellent que l'abandon du traitement médical expose à de graves AVC.",
    content: "Des publications sponsorisées sur TikTok font la promotion d'une décoction de plantes locales affirmant guérir définitivement l'hypertension artérielle. La Société Béninoise de Cardiologie et l'OMS rappellent que l'hypertension nécessite un suivi médical strict et que l'abandon des traitements prescrits est une cause majeure d'AVC.",
    summary_falc: "Faux remède : Des vidéos disent qu'une tisane guérit totalement l'hypertension.\nLes médecins rappellent qu'il ne faut jamais arrêter ses médicaments sans voir un docteur.",
    reliability_score: "faible",
    verdict_label: "ALERTE SANTÉ / DÉMENTI",
    source: "Organisation Mondiale de la Santé (Bénin)",
    image: null,
    is_lead: false
  },
  {
    id: 6,
    title: "Enquête : Rumeur de révision des frais d'inscription universitaire à Abomey-Calavi",
    slug: "uac-rumeur-frais-inscription-enquete",
    rubrique: "Éducation & Campus",
    region: "Atlantique & Calavi",
    published_at: new Date(Date.now() - 48 * 3600000).toISOString(),
    author: "Cellule Investigation Universités",
    reading_time: "3 min",
    lead: "Des tracts anonymes évoquent une augmentation de 30% des droits universitaires pour l'année prochaine. Les autorités rectorales apportent des précisions.",
    content: "Des tracts anonymes circulant sur Telegram annoncent une hausse de 30% des droits d'inscription à l'Université d'Abomey-Calavi pour la rentrée prochaine. La cellule de vérification VoiCI-EMI a contacté le rectorat et les syndicats étudiants pour authentifier le document officiel. Aucun acte réglementaire ne confirme cette allégation à ce jour.",
    summary_falc: "Une rumeur dit que le prix de l'université va augmenter.\nNos journalistes posent des questions aux responsables pour savoir si c'est vrai.\nLa réponse arrive bientôt.",
    reliability_score: "en_cours",
    verdict_label: "INVESTIGATION EN COURS",
    source: "Rectorat de l'UAC & Ministère de l'Enseignement Supérieur",
    image: null,
    is_lead: false
  }
];

// Fetch articles from backend API
const fetchArticles = async () => {
  isRefreshing.value = true;
  try {
    const response = await fetch('http://127.0.0.1:8000/api/articles');
    if (response.ok) {
      const data = await response.json();
      if (Array.isArray(data) && data.length > 0) {
        articles.value = data.map((item, index) => {
          const fallback = defaultNews[index % defaultNews.length];
          return {
            id: item.id,
            title: item.title,
            slug: item.slug || fallback.slug,
            rubrique: fallback.rubrique,
            region: fallback.region,
            published_at: item.published_at || fallback.published_at,
            author: fallback.author,
            reading_time: fallback.reading_time,
            lead: item.content ? item.content.substring(0, 160) + '...' : fallback.lead,
            content: item.content || fallback.content,
            summary_falc: item.summary_falc || fallback.summary_falc,
            reliability_score: item.reliability_score || fallback.reliability_score,
            verdict_label: item.reliability_score === 'eleve' ? 'INFORMATION VÉRIFIÉE' : item.reliability_score === 'faible' ? 'DÉMENTI OFFICIEL / FAUX' : 'INVESTIGATION EN COURS',
            source: fallback.source,
            image: index === 0 ? '/images/hero_editorial.jpg' : null,
            is_lead: index === 0,
            showIndividualFalc: false
          };
        });
      } else {
        articles.value = defaultNews.map(n => ({ ...n, showIndividualFalc: false }));
      }
    } else {
      articles.value = defaultNews.map(n => ({ ...n, showIndividualFalc: false }));
    }
  } catch (err) {
    console.warn("Backend API indisponible, utilisation du fil d'actualités local:", err);
    articles.value = defaultNews.map(n => ({ ...n, showIndividualFalc: false }));
  } finally {
    loading.value = false;
    isRefreshing.value = false;
    lastSyncTime.value = new Date();
  }
};

// Filtered articles
const filteredArticles = computed(() => {
  return articles.value.filter(article => {
    // Search
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase();
      const matchTitle = (article.title || '').toLowerCase().includes(q);
      const matchContent = (article.content || '').toLowerCase().includes(q);
      const matchLead = (article.lead || '').toLowerCase().includes(q);
      const matchRegion = (article.region || '').toLowerCase().includes(q);
      const matchRubrique = (article.rubrique || '').toLowerCase().includes(q);
      if (!matchTitle && !matchContent && !matchLead && !matchRegion && !matchRubrique) {
        return false;
      }
    }

    // Rubrique Filter
    if (selectedRubrique.value !== 'all') {
      if (selectedRubrique.value === 'gouvernance' && !article.rubrique.toLowerCase().includes('gouvernance') && !article.rubrique.toLowerCase().includes('international')) return false;
      if (selectedRubrique.value === 'economie' && !article.rubrique.toLowerCase().includes('économie') && !article.rubrique.toLowerCase().includes('emploi')) return false;
      if (selectedRubrique.value === 'sante' && !article.rubrique.toLowerCase().includes('santé')) return false;
      if (selectedRubrique.value === 'societe' && !article.rubrique.toLowerCase().includes('société') && !article.rubrique.toLowerCase().includes('jeunesse')) return false;
      if (selectedRubrique.value === 'education' && !article.rubrique.toLowerCase().includes('éducation') && !article.rubrique.toLowerCase().includes('médias')) return false;
    }

    // Region Filter
    if (selectedRegion.value !== 'all') {
      if (!article.region || !article.region.toLowerCase().includes(selectedRegion.value.toLowerCase())) {
        return false;
      }
    }

    return true;
  });
});

// Lead story and regular articles
const leadArticle = computed(() => {
  if (filteredArticles.value.length === 0) return null;
  return filteredArticles.value[0];
});

const regularArticles = computed(() => {
  if (filteredArticles.value.length <= 1) return [];
  return filteredArticles.value.slice(1);
});

// Format relative date
const formatPublishDate = (isoString) => {
  if (!isoString) return "Récemment";
  const date = new Date(isoString);
  const now = new Date();
  const diffHours = Math.floor((now - date) / (1000 * 3600));

  if (diffHours < 1) {
    const diffMin = Math.floor((now - date) / (1000 * 60));
    return `Il y a ${Math.max(1, diffMin)} min`;
  }
  if (diffHours < 24) {
    return `Il y a ${diffHours} h`;
  }
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

// Format exact time for wire
const formatWireTime = (isoString) => {
  if (!isoString) return "--:--";
  return new Date(isoString).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

// Open article modal
const openDossier = (article) => {
  selectedArticle.value = article;
  isDossierOpen.value = true;
};

// WhatsApp share
const shareOnWhatsApp = (article) => {
  const text = encodeURIComponent(
    `🗞️ *VoiCI-EMI Bénin • Dépêche d'actualité*\n\n` +
    `*${article.title}*\n\n` +
    `Résumé : ${article.summary_falc ? article.summary_falc.split('\n')[0] : article.lead}\n\n` +
    `Consulter le dossier d'information complet : https://voici-emi.bj/actualites`
  );
  window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
};

// Reset filters
const resetAllFilters = () => {
  searchQuery.value = '';
  selectedRubrique.value = 'all';
  selectedRegion.value = 'all';
};

onMounted(() => {
  fetchArticles();

  tickerTimer = setInterval(() => {
    activeTickerIndex.value = (activeTickerIndex.value + 1) % 4;
  }, 6000);
});

onUnmounted(() => {
  clearInterval(audioTimer);
  clearInterval(tickerTimer);
});
</script>

<template>
  <!-- PURE WHITE DOMINANCE: Clean, Frameless, Pure White Editorial Luxury -->
  <div class="bg-white min-h-screen text-slate-800 font-sans space-y-10 animate-fade-in pb-20">
    
    <!-- ========================================================================= -->
    <!-- 1. PRESS MASTHEAD & LIVE NEWS WIRE (SANS TRAITS NOIRS, PLEIN BLANC ÉPURÉ) -->
    <!-- ========================================================================= -->
    <header class="space-y-4">
      
      <!-- Top meta edition line -->
      <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
        <div class="flex items-center gap-3">
          <span class="font-bold text-slate-900 uppercase tracking-widest text-[11px]">
            Fil d'actualité & Dépêches
          </span>
          <span>•</span>
          <span>Rédaction Nationale de Cotonou & Correspondants Régionaux</span>
          <span class="hidden md:inline">•</span>
          <span class="hidden md:inline">Actualisation continue</span>
        </div>

        <div>
          <button 
            @click="fetchArticles" 
            :disabled="isRefreshing"
            class="text-xs text-slate-600 hover:text-slate-950 flex items-center gap-1.5 transition font-medium"
            title="Rafraîchir les dépêches"
          >
            <svg class="w-3.5 h-3.5" :class="{ 'animate-spin text-slate-900': isRefreshing }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Dernière synchro : {{ lastSyncTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
          </button>
        </div>
      </div>

      <!-- Main Newspaper Section Title -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 pt-2">
        <div>
          <h1 class="font-serif font-black text-3xl sm:text-5xl text-slate-950 tracking-tight leading-none">
            Les Actualités
          </h1>
          <p class="font-serif italic text-sm sm:text-base text-slate-500 mt-2">
            L'information générale, les faits vérifiés et les reportages de terrain au Bénin et en Afrique de l'Ouest.
          </p>
        </div>

        <!-- Layout View Toggle & Universal FALC Switch -->
        <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
          <!-- Universal FALC Switch -->
          <button 
            @click="globalFalcMode = !globalFalcMode"
            :class="globalFalcMode ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
            class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-1.5 transition font-semibold"
            title="Basculer toutes les dépêches en français Facile à Lire et à Comprendre"
          >
            <span>♿</span>
            <span>Version FALC</span>
          </button>

          <!-- Toggle: Cards Grid vs Continuous Wire -->
          <div class="inline-flex rounded-xl bg-slate-100 p-1">
            <button 
              @click="displayMode = 'grid'" 
              :class="displayMode === 'grid' ? 'bg-white text-slate-950 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-900'"
              class="px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 transition"
              title="Affichage en cartes de presse"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              <span>Grille</span>
            </button>
            <button 
              @click="displayMode = 'wire'" 
              :class="displayMode === 'wire' ? 'bg-white text-slate-950 shadow-sm font-bold' : 'text-slate-500 hover:text-slate-900'"
              class="px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 transition"
              title="Affichage en fil télégraphique d'agences"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
              </svg>
              <span>Fil Continu</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Live Wire Ticker Tape (Plein Blanc, Doux, Sans Contour Noir) -->
      <div class="bg-slate-50/90 rounded-2xl px-5 py-3 flex items-center gap-4 text-xs">
        <div class="flex items-center gap-2 shrink-0">
          <span class="w-2 h-2 rounded-full bg-rose-600 animate-ping"></span>
          <span class="w-2 h-2 rounded-full bg-rose-600 -ml-4"></span>
          <span class="font-bold uppercase tracking-wider text-slate-900 text-[11px]">En direct</span>
        </div>

        <div class="overflow-hidden relative h-5 flex-1">
          <transition name="page" mode="out-in">
            <p :key="activeTickerIndex" class="truncate font-medium text-slate-700">
              <span v-if="activeTickerIndex === 0">🚨 <strong>Recrutement Douane :</strong> Démenti formel du Ministère des Finances sur les faux recrutements par transfert d'argent.</span>
              <span v-else-if="activeTickerIndex === 1">📚 <strong>Éducation aux Médias :</strong> 50 ateliers de sensibilisation déployés dans les collèges et lycées du Bénin.</span>
              <span v-else-if="activeTickerIndex === 2">🎙️ <strong>Langues Nationales :</strong> Diffusion des capsules de vérification en Bariba, Fɔn et Dendi dans le Septentrion.</span>
              <span v-else>⚠️ <strong>Santé Publique :</strong> L'OMS rappelle la dangerosité de l'arrêt des traitements de l'hypertension pour des tisanes non prouvées.</span>
            </p>
          </transition>
        </div>

        <span class="text-[11px] text-slate-400 font-mono hidden sm:inline">
          Agence VoiCI-EMI
        </span>
      </div>

    </header>


    <!-- ========================================================================= -->
    <!-- 2. RUBRIQUES & RECHERCHE (SANS CONTOUR NOIR NI TRAITS) -->
    <!-- ========================================================================= -->
    <nav class="space-y-4" aria-label="Navigation des rubriques d'actualité">
      
      <!-- Rubriques Pill Tabs -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        
        <div class="flex items-center gap-2 text-xs font-semibold overflow-x-auto no-scrollbar pb-1 max-w-full sm:flex-wrap">
          <button 
            @click="selectedRubrique = 'all'"
            :class="selectedRubrique === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-4 py-2 rounded-xl transition whitespace-nowrap shrink-0 sm:shrink"
          >
            Toutes les dépêches ({{ articles.length }})
          </button>
          
          <button 
            @click="selectedRubrique = 'gouvernance'"
            :class="selectedRubrique === 'gouvernance' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-4 py-2 rounded-xl transition whitespace-nowrap shrink-0 sm:shrink"
          >
            Gouvernance & État
          </button>

          <button 
            @click="selectedRubrique = 'economie'"
            :class="selectedRubrique === 'economie' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-4 py-2 rounded-xl transition whitespace-nowrap shrink-0 sm:shrink"
          >
            Économie & Emploi
          </button>

          <button 
            @click="selectedRubrique = 'sante'"
            :class="selectedRubrique === 'sante' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-4 py-2 rounded-xl transition whitespace-nowrap shrink-0 sm:shrink"
          >
            Santé & Sciences
          </button>

          <button 
            @click="selectedRubrique = 'societe'"
            :class="selectedRubrique === 'societe' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-4 py-2 rounded-xl transition whitespace-nowrap shrink-0 sm:shrink"
          >
            Société & Régions
          </button>

          <button 
            @click="selectedRubrique = 'education'"
            :class="selectedRubrique === 'education' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
            class="px-4 py-2 rounded-xl transition whitespace-nowrap shrink-0 sm:shrink"
          >
            Éducation & Campus
          </button>
        </div>

        <!-- Geographic dropdown selector -->
        <div class="flex items-center gap-2">
          <label for="region-select" class="sr-only">Région</label>
          <select 
            id="region-select"
            v-model="selectedRegion" 
            class="text-xs bg-slate-100 rounded-xl px-4 py-2 text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-slate-300 transition"
          >
            <option value="all">📍 Tout le Bénin & Régions</option>
            <option value="Cotonou">Cotonou & Littoral</option>
            <option value="Borgou">Borgou & Parakou</option>
            <option value="Atlantique">Atlantique & Calavi</option>
            <option value="CEDEAO">Régional CEDEAO</option>
          </select>
        </div>

      </div>

      <!-- Quick Search Bar (Soft & Frameless) -->
      <div class="relative">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Rechercher une dépêche par mot-clé, sujet ou organisme (ex: Douane, Cotonou, OMS, Université)..."
          class="w-full bg-slate-50 hover:bg-slate-100/70 focus:bg-white rounded-2xl pl-11 pr-10 py-3 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200 transition shadow-xs"
        />
        <svg class="w-4 h-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <button 
          v-if="searchQuery"
          @click="searchQuery = ''"
          class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 text-xs font-semibold"
        >
          Effacer
        </button>
      </div>

    </nav>


    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div v-for="i in 4" :key="i" class="p-6 rounded-3xl bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
        <SkeletonLoader width="w-24" height="h-4" />
        <SkeletonLoader width="w-3/4" height="h-6" />
        <SkeletonLoader :lines="3" height="h-4" />
      </div>
    </div>


    <!-- Empty State -->
    <div v-else-if="filteredArticles.length === 0" class="rounded-3xl p-12 text-center bg-white shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
      <EmptyState 
        title="Aucune dépêche trouvée" 
        message="Aucun article d'actualité ne correspond aux critères de recherche actuels. Essayez de réinitialiser vos filtres."
      >
        <template #action>
          <button 
            @click="resetAllFilters"
            class="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow-sm"
          >
            Réinitialiser les filtres
          </button>
        </template>
      </EmptyState>
    </div>


    <!-- ========================================================================= -->
    <!-- 3. ACTUALITÉS : VUE EN GRILLE (PLEIN BLANC, SANS CONTOURS NOIRS) -->
    <!-- ========================================================================= -->
    <div v-else-if="displayMode === 'grid'" class="space-y-10">
      
      <!-- ARTICLE À LA UNE (LEAD STORY SANS BORDURES NOIRES) -->
      <section v-if="leadArticle" class="rounded-3xl p-6 sm:p-10 bg-white shadow-[0_8px_30px_rgba(0,0,0,0.04)] hover:shadow-[0_12px_40px_rgba(0,0,0,0.06)] transition-all group">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
          
          <!-- Text details (7 cols) -->
          <div class="lg:col-span-7 space-y-4">
            <div class="flex flex-wrap items-center gap-2 text-xs">
              <span class="font-bold text-rose-700 uppercase tracking-widest text-[11px] font-mono">
                À la Une • Grand Format
              </span>
              <span class="text-slate-300">•</span>
              <span class="text-slate-500 font-medium">{{ leadArticle.rubrique }}</span>
              <span class="text-slate-300">•</span>
              <span class="text-slate-400 font-mono">{{ formatPublishDate(leadArticle.published_at) }}</span>
            </div>

            <h2 class="font-serif font-black text-2xl sm:text-3xl lg:text-4xl text-slate-950 leading-tight group-hover:text-slate-700 transition cursor-pointer" @click="openDossier(leadArticle)">
              {{ leadArticle.title }}
            </h2>

            <!-- Lead / Chapeau ou Version FALC -->
            <div class="text-sm sm:text-base text-slate-600 leading-relaxed font-serif">
              <div v-if="globalFalcMode || leadArticle.showIndividualFalc" class="p-5 bg-slate-50 rounded-2xl space-y-2">
                <span class="text-xs font-bold text-slate-900 flex items-center gap-1.5 uppercase tracking-wider font-sans">
                  <span>♿</span> Résumé Facile à Lire et à Comprendre (FALC)
                </span>
                <p 
                  v-for="(sentence, sIdx) in (leadArticle.summary_falc || leadArticle.lead).split('\n')" 
                  :key="sIdx"
                  class="text-xs sm:text-sm font-medium text-slate-800 flex items-start gap-2 font-sans"
                >
                  <span class="text-slate-400 font-bold">•</span>
                  <span>{{ sentence }}</span>
                </p>
              </div>
              <p v-else>
                {{ leadArticle.lead }}
              </p>
            </div>

            <!-- Author & Source -->
            <div class="text-xs text-slate-400 font-mono flex items-center gap-2 pt-1">
              <span>{{ leadArticle.author }}</span>
              <span>•</span>
              <span>{{ leadArticle.reading_time }} de lecture</span>
            </div>

            <!-- Actions Bar -->
            <div class="pt-2 flex flex-wrap items-center gap-3">
              <button 
                @click="openDossier(leadArticle)"
                class="px-5 py-2.5 bg-slate-950 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow-sm"
              >
                Lire la dépêche complète
              </button>

              <button 
                @click="leadArticle.showIndividualFalc = !leadArticle.showIndividualFalc"
                v-if="!globalFalcMode"
                class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition"
              >
                {{ leadArticle.showIndividualFalc ? '↩︎ Version standard' : '♿ Lire en FALC' }}
              </button>

              <button 
                @click="shareOnWhatsApp(leadArticle)"
                class="px-4 py-2.5 bg-slate-100 hover:bg-emerald-50 hover:text-emerald-800 text-slate-700 rounded-xl text-xs font-semibold flex items-center gap-1.5 transition"
                title="Partager cette dépêche sur WhatsApp"
              >
                <span>Partager</span>
              </button>
            </div>

          </div>

          <!-- Editorial Visual (5 cols) -->
          <div class="lg:col-span-5">
            <div class="rounded-2xl overflow-hidden bg-slate-100 aspect-video relative group-hover:shadow-md transition duration-300">
              <img 
                :src="leadArticle.image || '/images/hero_editorial.jpg'" 
                :alt="leadArticle.title" 
                class="w-full h-full object-cover group-hover:scale-102 transition-transform duration-500"
              />
              <div class="absolute bottom-3 left-3 bg-slate-900/80 backdrop-blur-xs text-white text-[10px] font-medium px-2.5 py-1 rounded-lg">
                Photo : Rédaction VoiCI-EMI
              </div>
            </div>
          </div>

        </div>
      </section>

      <!-- GRILLE DES AUTRES ARTICLES (CARDS 100% BLANCHES, SANS CONTOURS NOIRS) -->
      <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <article 
          v-for="article in regularArticles" 
          :key="article.id"
          class="bg-white rounded-3xl p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] hover:-translate-y-0.5 transition-all duration-300 flex flex-col justify-between group"
        >
          <div class="space-y-3">
            
            <!-- Metadata & Rubrique -->
            <div class="flex items-center justify-between gap-2 text-xs">
              <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider font-mono">
                {{ article.rubrique }}
              </span>
              <span class="text-[11px] font-mono text-slate-400">
                {{ formatPublishDate(article.published_at) }}
              </span>
            </div>

            <!-- Title -->
            <h3 
              class="font-serif font-bold text-lg text-slate-950 group-hover:text-slate-700 transition leading-snug cursor-pointer pt-1"
              @click="openDossier(article)"
            >
              {{ article.title }}
            </h3>

            <!-- Body Excerpt / FALC View -->
            <div class="text-xs sm:text-sm text-slate-600 leading-relaxed font-serif">
              <div v-if="globalFalcMode || article.showIndividualFalc" class="p-4 bg-slate-50 rounded-2xl space-y-1 font-sans">
                <span class="text-[10px] font-bold text-slate-900 uppercase block mb-1">♿ Version Simplifiée</span>
                <p 
                  v-for="(line, lIdx) in (article.summary_falc || article.lead).split('\n')" 
                  :key="lIdx"
                  class="text-xs text-slate-800"
                >
                  • {{ line }}
                </p>
              </div>
              <p v-else class="line-clamp-3">
                {{ article.content }}
              </p>
            </div>

            <!-- Provenance / Source -->
            <div class="text-[11px] text-slate-400 font-mono flex items-center justify-between pt-2">
              <span>📍 {{ article.region }}</span>
              <span>{{ article.reading_time }}</span>
            </div>

          </div>

          <!-- Card Footer Actions -->
          <div class="pt-5 mt-4 flex items-center justify-between gap-2">
            
            <button 
              @click="openDossier(article)"
              class="text-xs font-bold text-slate-900 hover:text-slate-600 flex items-center gap-1 transition"
            >
              <span>Lire la dépêche</span>
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <div class="flex items-center gap-2">
              <button 
                @click="article.showIndividualFalc = !article.showIndividualFalc"
                v-if="!globalFalcMode"
                class="text-[11px] text-slate-500 hover:text-slate-950 font-medium px-2 py-1 rounded-lg hover:bg-slate-50 transition"
                title="Bascule FALC"
              >
                {{ article.showIndividualFalc ? 'Standard' : '♿ FALC' }}
              </button>

              <button 
                @click="shareOnWhatsApp(article)"
                class="p-1.5 text-slate-400 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition"
                title="Partager sur WhatsApp"
              >
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                  <path d="M12.031 2C6.496 2 2 6.505 2 12.046c0 1.97.57 3.805 1.554 5.358L2 22l4.773-1.524A9.973 9.973 0 0012.031 22C17.568 22 22 17.587 22 12.046 22 6.505 17.568 2 12.031 2zm5.728 14.167c-.24.673-1.393 1.285-1.93 1.348-.537.063-1.074.127-3.414-.792-2.34-1.012-3.83-3.376-3.95-3.535-.12-.159-.95-1.266-.95-2.417 0-1.15.603-1.716.817-1.946.214-.23.468-.288.625-.288.156 0 .313.003.45.01.144.006.337-.055.526.4.19.455.65 1.585.707 1.7.057.115.095.25.02.4-.076.15-.114.244-.228.378-.114.134-.24.3-.342.403-.114.115-.233.24-.1.47.133.23.593.978 1.272 1.583.874.778 1.61.02 1.838 1.15.228.13.362.065.495-.088.133-.153.57-1.03.722-1.385.152-.355.304-.298.513-.22.209.078 1.332.628 1.56.742.228.114.38.17.437.266.057.095.057.553-.183 1.226z"/>
                </svg>
              </button>
            </div>

          </div>

        </article>

      </section>

    </div>


    <!-- ========================================================================= -->
    <!-- 4. ACTUALITÉS : VUE EN FIL CONTINU (WIRE FORMAT DOUX, SANS TRAITS NOIRS) -->
    <!-- ========================================================================= -->
    <div v-else-if="displayMode === 'wire'" class="bg-white rounded-3xl p-4 sm:p-6 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-3">
      
      <div 
        v-for="article in filteredArticles" 
        :key="article.id"
        class="p-5 hover:bg-slate-50/80 rounded-2xl transition-all flex flex-col sm:flex-row items-start gap-4 sm:gap-6 group cursor-pointer"
        @click="openDossier(article)"
      >
        <!-- Time Marker -->
        <div class="shrink-0 flex items-center sm:flex-col items-baseline sm:items-start gap-2 sm:gap-0.5 w-24">
          <span class="font-mono font-bold text-slate-900 text-sm">
            {{ formatWireTime(article.published_at) }}
          </span>
          <span class="text-[11px] font-mono text-slate-400">
            {{ formatPublishDate(article.published_at) }}
          </span>
        </div>

        <!-- Content details -->
        <div class="flex-1 space-y-1.5">
          <div class="flex items-center gap-2 text-xs">
            <span class="font-bold text-slate-600 uppercase tracking-wider text-[10px] font-mono bg-slate-100 px-2.5 py-0.5 rounded-lg">
              {{ article.rubrique }}
            </span>
            <span class="text-slate-400 text-[11px]">📍 {{ article.region }}</span>
            <span class="text-slate-300">•</span>
            <span class="text-slate-500 text-[11px]">Source : {{ article.source }}</span>
          </div>

          <h3 class="font-serif font-bold text-base sm:text-lg text-slate-950 group-hover:text-slate-700 transition leading-snug">
            {{ article.title }}
          </h3>

          <p class="text-xs sm:text-sm text-slate-600 line-clamp-2 font-serif">
            {{ article.content }}
          </p>
        </div>

        <!-- Quick actions button -->
        <div class="shrink-0 self-center sm:self-auto flex items-center gap-2" @click.stop>
          <button 
            @click="shareOnWhatsApp(article)"
            class="px-3.5 py-1.5 bg-slate-100 rounded-xl text-xs font-semibold hover:bg-emerald-50 hover:text-emerald-800 text-slate-700 transition"
          >
            WhatsApp
          </button>
          <button 
            @click="openDossier(article)"
            class="px-4 py-1.5 bg-slate-950 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition shadow-sm"
          >
            Consulter
          </button>
        </div>

      </div>

    </div>


    <!-- ========================================================================= -->
    <!-- 5. MODAL DE LECTURE (PLEIN BLANC, FLUIDE, SANS TRAITS NOIRS) -->
    <!-- ========================================================================= -->
    <BaseModal 
      v-model="isDossierOpen" 
      :title="selectedArticle ? selectedArticle.title : 'Dépêche d\'information'" 
      maxWidth="max-w-3xl"
    >
      <div v-if="selectedArticle" class="space-y-6 text-slate-800 bg-white">
        
        <!-- Header Metadata -->
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400 font-mono">
          <div class="flex items-center gap-2">
            <span class="font-bold text-slate-900 uppercase bg-slate-100 px-2 py-0.5 rounded-md">{{ selectedArticle.rubrique }}</span>
            <span>📍 {{ selectedArticle.region }}</span>
          </div>
          <div>
            Publié {{ formatPublishDate(selectedArticle.published_at) }} • {{ selectedArticle.reading_time }}
          </div>
        </div>

        <!-- Full article body -->
        <div class="space-y-4">
          <p class="font-serif text-base sm:text-lg text-slate-900 font-bold leading-relaxed bg-slate-50 p-4 rounded-2xl">
            {{ selectedArticle.lead }}
          </p>

          <p class="font-serif text-sm sm:text-base text-slate-700 leading-relaxed text-justify">
            {{ selectedArticle.content }}
          </p>
        </div>

        <!-- FALC Box inside Modal -->
        <div v-if="selectedArticle.summary_falc" class="p-5 rounded-2xl bg-slate-50 space-y-2">
          <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5 font-sans">
            <span>♿</span> Synthèse Facile à Lire et à Comprendre (FALC)
          </h4>
          <p 
            v-for="(sentence, fIdx) in selectedArticle.summary_falc.split('\n')" 
            :key="fIdx"
            class="text-xs sm:text-sm font-medium text-slate-800 flex items-start gap-2 font-sans"
          >
            <span class="text-slate-400 font-bold">•</span>
            <span>{{ sentence }}</span>
          </p>
        </div>

        <!-- Journalistic Source & Verification Protocols -->
        <div class="p-5 rounded-2xl bg-slate-50/80 space-y-2 text-xs">
          <div class="font-bold text-slate-900 uppercase tracking-wider font-sans">
            Informations de traçabilité & Sources
          </div>
          <div class="text-slate-600 space-y-1">
            <p>• <strong>Source principale :</strong> {{ selectedArticle.source }}</p>
            <p>• <strong>Supervision déontologique :</strong> Observatoire de la Déontologie et de l'Éthique dans les Médias (ODEM Bénin)</p>
            <p>• <strong>Rédaction :</strong> {{ selectedArticle.author }}</p>
          </div>
        </div>

        <!-- Footer actions in Modal -->
        <div class="pt-2 flex flex-wrap items-center justify-between gap-3">
          <button 
            @click="shareOnWhatsApp(selectedArticle)"
            class="px-5 py-2.5 bg-slate-950 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition flex items-center gap-2 shadow-sm"
          >
            <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 24 24">
              <path d="M12.031 2C6.496 2 2 6.505 2 12.046c0 1.97.57 3.805 1.554 5.358L2 22l4.773-1.524A9.973 9.973 0 0012.031 22C17.568 22 22 17.587 22 12.046 22 6.505 17.568 2 12.031 2zm5.728 14.167c-.24.673-1.393 1.285-1.93 1.348-.537.063-1.074.127-3.414-.792-2.34-1.012-3.83-3.376-3.95-3.535-.12-.159-.95-1.266-.95-2.417 0-1.15.603-1.716.817-1.946.214-.23.468-.288.625-.288.156 0 .313.003.45.01.144.006.337-.055.526.4.19.455.65 1.585.707 1.7.057.115.095.25.02.4-.076.15-.114.244-.228.378-.114.134-.24.3-.342.403-.114.115-.233.24-.1.47.133.23.593.978 1.272 1.583.874.778 1.61.02 1.838 1.15.228.13.362.065.495-.088.133-.153.57-1.03.722-1.385.152-.355.304-.298.513-.22.209.078 1.332.628 1.56.742.228.114.38.17.437.266.057.095.057.553-.183 1.226z"/>
            </svg>
            <span>Diffuser sur WhatsApp</span>
          </button>

          <button 
            @click="isDossierOpen = false"
            class="px-5 py-2.5 bg-slate-100 rounded-xl text-xs font-semibold hover:bg-slate-200 transition"
          >
            Fermer la dépêche
          </button>
        </div>

      </div>
    </BaseModal>

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
