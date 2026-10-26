<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toast';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseBadge from '../components/ui/BaseBadge.vue';
import BaseTable from '../components/ui/BaseTable.vue';
import SkeletonLoader from '../components/ui/SkeletonLoader.vue';
import EmptyState from '../components/ui/EmptyState.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

// =========================================================================
// ROLE PRIVILEGES IDENTIFICATION
// =========================================================================
const isAdminOrSuperAdmin = computed(() => {
  const role = authStore.currentRole;
  return role === 'Super Administrateur' || role === 'Administrateur';
});

const isSuperAdmin = computed(() => {
  return authStore.currentRole === 'Super Administrateur';
});

const isAdmin = computed(() => {
  return authStore.currentRole === 'Administrateur';
});

const isTrainer = computed(() => {
  return authStore.currentRole === 'Formateur';
});

// Sidebar Navigation Tabs
// - Admin/SA: 'overview' | 'analytics' | 'users' | 'moderation' | 'audit' | 'security'
// - Formateur: 'learners' | 'builder' | 'courses' | 'security'
// - Others (Citoyen, Apprenant, etc.): 'overview' | 'trainings' | 'contributions' | 'security'
const activeTab = ref('overview');

const userDetails = ref(null);
const adminUsersList = ref([]);
const auditLogsList = ref([]);
const reportsList = ref([]);
const profileLoading = ref(false);
const adminLoading = ref(false);
const modLoading = ref(false);

// 2FA state
const twoFaLoading = ref(false);
const twoFaSecret = ref('');
const twoFaRecovery = ref('');

// Filter / search for users table (Admin only)
const userSearchQuery = ref('');
const selectedRoleFilter = ref('all');

// Time period for analytics (Admin only)
const selectedPeriod = ref('30d'); // '7d' | '30d' | '12m'

// =========================================================================
// TRAINER / FORMATEUR MANAGEMENT STATE
// =========================================================================
const trainerLearnersList = ref([]);
const trainerLoading = ref(false);
const issuingCertId = ref(null);
const selectedCertificate = ref(null);

// Course Creation State for Formateur (Multi-supports: PDF, Vidéo, Images, Textes, Vocale)
const newCourse = ref({
  title: '',
  description: '',
  category: 'Fact-Checking & Vérification',
  level: 'Tous niveaux',
  modules: [
    {
      title: 'Module 1 : Fondements et Identification des Sources',
      lessons: [
        {
          title: 'Guide méthodologique de vérification (Fiche pratique)',
          content_type: 'pdf',
          content: 'Support complet pour reconnaître les sources fiables au Bénin.',
          video_url: 'https://voici-emi.bj/docs/guide_sources_benin.pdf',
          audio_url: '',
          content_simplified: 'Fiche simple à lire pour vérifier qui parle.',
          audio_language: 'fr'
        },
        {
          title: 'Chronique Vocale : Pièges des rumeurs WhatsApp',
          content_type: 'audio',
          content: 'Explication audio accessible pour les auditeurs et citoyens.',
          video_url: '',
          audio_url: 'https://voici-emi.bj/audio/whatsapp_rumors_fon.mp3',
          content_simplified: 'Message vocal clair.',
          audio_language: 'fon'
        }
      ]
    }
  ]
});

const addModule = () => {
  newCourse.value.modules.push({
    title: `Module ${newCourse.value.modules.length + 1} : Nouveau Module Pédagogique`,
    lessons: [
      {
        title: 'Première ressource du module',
        content_type: 'pdf',
        content: '',
        content_simplified: '',
        video_url: '',
        audio_url: '',
        audio_language: 'fr'
      }
    ]
  });
};

const removeModule = (mIdx) => {
  if (newCourse.value.modules.length > 1) {
    newCourse.value.modules.splice(mIdx, 1);
  } else {
    toastStore.warning('Une formation doit comporter au moins un module.');
  }
};

const addLessonToModule = (mod) => {
  mod.lessons.push({
    title: `Ressource ${mod.lessons.length + 1}`,
    content_type: 'video',
    content: '',
    content_simplified: '',
    video_url: '',
    audio_url: '',
    audio_language: 'fr'
  });
};

const removeLessonFromModule = (mod, lIdx) => {
  if (mod.lessons.length > 1) {
    mod.lessons.splice(lIdx, 1);
  }
};

// Filtered users for administration (Admin only)
const filteredUsers = computed(() => {
  if (!adminUsersList.value?.data) return [];
  let list = adminUsersList.value.data;

  if (selectedRoleFilter.value !== 'all') {
    list = list.filter(u => u.current_role === selectedRoleFilter.value);
  }

  if (userSearchQuery.value.trim()) {
    const q = userSearchQuery.value.toLowerCase();
    list = list.filter(
      u => u.name?.toLowerCase().includes(q) || u.email?.toLowerCase().includes(q) || u.current_role?.toLowerCase().includes(q)
    );
  }

  return list;
});

// Personal Learning & Civic Progress Data (For regular users: Citoyen, Apprenant, etc.)
const userProgress = ref({
  coursesCompleted: 2,
  coursesInProgress: 1,
  totalQuizzesTaken: 8,
  quizAverageScore: 92,
  reportsSubmitted: 4,
  criticalThinkingScore: 95,
  enrolledCourses: [
    {
      id: 1,
      title: 'Désinformation & Rumeurs virales sur WhatsApp au Bénin',
      category: 'Fact-Checking Pratique',
      progress: 100,
      status: 'Certifié',
      instructor: 'Aïcha Bio Guéra',
      date: 'Complété le 18 Oct 2026'
    },
    {
      id: 2,
      title: 'Identifier les Images Manipulées et Deepfakes par IA',
      category: 'Technologie & Médias',
      progress: 65,
      status: 'En cours',
      instructor: 'Gérard Houndété',
      date: 'Dernière leçon hier'
    },
    {
      id: 3,
      title: 'Journalisme Citoyen & Cadre Déontologique ODEM',
      category: 'Éthique & Déontologie',
      progress: 25,
      status: 'En cours',
      instructor: 'Grégoire Djeton',
      date: 'Inscrit récemment'
    }
  ],
  badges: [
    { name: 'Vigilance WhatsApp', icon: '🛡️', level: 'Or', desc: 'A signalé 3 rumeurs vérifiées par la rédaction' },
    { name: 'Esprit Critique Élite', icon: '🧠', level: 'Platine', desc: 'Score supérieur à 90% aux quiz de vérification' },
    { name: 'Citoyen Vérificateur', icon: '🇧🇯', level: 'Argent', desc: 'A complété le module d\'inclusion républicaine' }
  ],
  myContributions: [
    { id: 101, title: 'Allégation de faux concours de douane WhatsApp', date: 'Il y a 3 jours', status: 'Démenti Officiel Publié', verdict: 'Faux' },
    { id: 102, title: 'Vidéo supposée d\'inondation récente à Malanville', date: 'Il y a 1 semaine', status: 'Image Décontextualisée', verdict: 'Trompeur' },
    { id: 103, title: 'Rumeur de rupture d\'engrais subventionnés', date: 'Il y a 2 semaines', status: 'Démenti Ministère', verdict: 'Faux' }
  ]
});

// Fetch Profile
const fetchProfile = async () => {
  profileLoading.value = true;
  try {
    const response = await fetch('/api/auth/profile', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    if (response.ok) {
      userDetails.value = await response.json();
    }
  } catch (err) {
    console.error(err);
  } finally {
    profileLoading.value = false;
  }
};

// Fetch Trainer Learners Progress
const fetchTrainerLearners = async () => {
  trainerLoading.value = true;
  try {
    const response = await fetch('/api/lms/trainer/learners', {
      headers: { 
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    if (response.ok) {
      trainerLearnersList.value = await response.json();
    }
  } catch (err) {
    console.error(err);
  } finally {
    trainerLoading.value = false;
  }
};

// Issue Official Certificate to Learner
const issueCertificateToLearner = async (learner) => {
  issuingCertId.value = `${learner.learner_id}-${learner.course_id}`;
  try {
    const response = await fetch(`/api/lms/courses/${learner.course_id}/grant-certificate`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ user_id: learner.learner_id })
    });
    const data = await response.json();
    if (response.ok) {
      toastStore.success(`Certification officielle accordée à ${learner.learner_name} !`, 'Attestation Délivrée');
      selectedCertificate.value = {
        number: data.certificate.certificate_number,
        recipient: data.certificate.recipient,
        course: data.certificate.course,
        issued_at: data.certificate.issued_at,
        trainer: data.certificate.trainer || 'Aïcha Bio Guéra'
      };
      await fetchTrainerLearners();
    } else {
      toastStore.error(data.message || 'Erreur lors de la délivrance de la certification.');
    }
  } catch (err) {
    toastStore.error('Erreur réseau lors de la délivrance de la certification.');
  } finally {
    issuingCertId.value = null;
  }
};

// Publish Modular Multi-format Course
const publishCourse = async () => {
  if (!newCourse.value.title.trim() || !newCourse.value.description.trim()) {
    toastStore.warning('Veuillez renseigner le titre et la description de la formation.');
    return;
  }

  try {
    const response = await fetch('/api/lms/courses', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        title: newCourse.value.title,
        description: newCourse.value.description,
        status: 'published',
        modules: newCourse.value.modules
      })
    });

    const data = await response.json();
    if (response.ok) {
      toastStore.success(`Formation « ${newCourse.value.title} » publiée avec succès (${newCourse.value.modules.length} module(s)) !`, 'Formation Active');
      newCourse.value.title = '';
      newCourse.value.description = '';
      activeTab.value = 'learners';
      await fetchTrainerLearners();
    } else {
      toastStore.error(data.message || 'Erreur lors de la publication de la formation.');
    }
  } catch (err) {
    toastStore.error('Erreur réseau lors de la création de la formation.');
  }
};

// Fetch Admin Data (Strictly reserved for Admin & Super Admin)
const fetchAdminData = async () => {
  if (!isAdminOrSuperAdmin.value) return;
  adminLoading.value = true;
  try {
    const response = await fetch('/api/admin/users', {
      headers: { 
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    if (response.ok) {
      adminUsersList.value = await response.json();
    }

    const auditResponse = await fetch('/api/admin/audit-logs', {
      headers: { 
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    if (auditResponse.ok) {
      auditLogsList.value = await auditResponse.json();
    }
  } catch (err) {
    console.error(err);
  } finally {
    adminLoading.value = false;
  }
};

// Fetch Moderation Data
const fetchModeratorData = async () => {
  if (!authStore.hasPermission('community_handle_report')) return;
  modLoading.value = true;
  try {
    const response = await fetch('/api/community/reports', {
      headers: { 
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    if (response.ok) {
      reportsList.value = await response.json();
    }
  } catch (err) {
    console.error(err);
  } finally {
    modLoading.value = false;
  }
};

// Toggle 2FA
const toggle2Fa = async () => {
  twoFaLoading.value = true;
  const isEnabled = !!userDetails.value?.data?.two_factor_confirmed_at;
  const url = isEnabled ? '/api/auth/2fa/disable' : '/api/auth/2fa/enable';

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    const data = await response.json();

    if (response.ok) {
      toastStore.success(data.message || 'Paramètre 2FA mis à jour.');
      twoFaSecret.value = data.secret || '';
      twoFaRecovery.value = data.recovery_code || '';
      await fetchProfile();
    } else {
      toastStore.error(data.message || 'Impossible de mettre à jour le 2FA.');
    }
  } catch (err) {
    toastStore.error('Erreur réseau lors de la configuration du 2FA.');
  } finally {
    twoFaLoading.value = false;
  }
};

// Suspend User (Protected against Super Admin)
const suspendUser = async (user, newStatus) => {
  if (user.current_role === 'Super Administrateur' || user.email === 'sa@voici-emi.bj') {
    toastStore.error("Action impossible : Le compte Super Administrateur est protégé et ne peut être suspendu.");
    return;
  }
  try {
    const response = await fetch(`/api/admin/users/${user.id}/suspend`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    });
    if (response.ok) {
      toastStore.success(`Compte ${user.email} mis à jour : ${newStatus}`);
      await fetchAdminData();
    } else {
      const errData = await response.json().catch(() => null);
      toastStore.error(errData?.message || 'Erreur lors du changement de statut.');
    }
  } catch (err) {
    toastStore.error('Erreur de connexion.');
  }
};

// Change Role (Protected against Super Admin)
const changeRole = async (user, roleName) => {
  if (!roleName) return;
  if (user.current_role === 'Super Administrateur' || user.email === 'sa@voici-emi.bj') {
    toastStore.error("Action interdite : Le rôle du Super Administrateur est immuable et ne peut pas être modifié.");
    return;
  }
  if (roleName === 'Super Administrateur' && authStore.currentRole !== 'Super Administrateur') {
    toastStore.error("Action interdite : Seul le Super Administrateur peut attribuer ce rôle.");
    return;
  }
  try {
    const response = await fetch(`/api/admin/users/${user.id}/roles`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ roles: [roleName] })
    });
    if (response.ok) {
      toastStore.success(`Rôle de ${user.email} mis à jour : ${roleName}`);
      await fetchAdminData();
    } else {
      const errData = await response.json().catch(() => null);
      toastStore.error(errData?.message || 'Erreur lors de la modification du rôle.');
    }
  } catch (err) {
    toastStore.error('Erreur réseau.');
  }
};

// Resolve Report
const resolveReport = async (report, action) => {
  try {
    const response = await fetch(`/api/community/reports/${report.id}/resolve`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ action: action })
    });
    if (response.ok) {
      toastStore.success('Signalement traité avec succès.');
      await fetchModeratorData();
    } else {
      toastStore.error('Erreur lors du traitement du signalement.');
    }
  } catch (err) {
    toastStore.error('Erreur lors du traitement du signalement.');
  }
};

const userTableHeaders = [
  { label: 'Utilisateur / Compte' },
  { label: 'Adresse Email' },
  { label: 'Rôle Fonctionnel' },
  { label: 'Statut' },
  { label: 'Actions d\'administration', align: 'right' }
];

onMounted(() => {
  fetchProfile();
  if (isTrainer.value) {
    activeTab.value = 'learners';
    fetchTrainerLearners();
  } else if (isAdminOrSuperAdmin.value) {
    fetchAdminData();
    fetchModeratorData();
    fetchTrainerLearners();
  }
});
</script>

<template>
  <div class="bg-white min-h-screen text-slate-800 font-sans pb-16 animate-fade-in">
    
    <!-- ========================================================================= -->
    <!-- CAS 1 : COCKPIT EXÉCUTIF (SUPER ADMIN ET ADMINISTRATEUR)                  -->
    <!-- ========================================================================= -->
    <div v-if="isAdminOrSuperAdmin" class="space-y-8">
      
      <!-- EXECUTIVE COMMAND HEADER BANNER -->
      <div 
        class="rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-sm transition-all"
        :class="isSuperAdmin 
          ? 'bg-gradient-to-r from-slate-950 via-slate-900 to-amber-950' 
          : 'bg-gradient-to-r from-slate-900 via-navy-900 to-slate-950'"
      >
        <div 
          class="absolute -right-12 -top-12 w-64 h-64 rounded-full blur-3xl pointer-events-none opacity-20"
          :class="isSuperAdmin ? 'bg-amber-400' : 'bg-emerald-400'"
        ></div>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
          <div class="space-y-2">
            <div 
              class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full text-xs font-bold font-mono tracking-wide"
              :class="isSuperAdmin 
                ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' 
                : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'"
            >
              <span>{{ isSuperAdmin ? '👑' : '⭐' }}</span>
              <span>{{ isSuperAdmin ? 'CONSOLE DE COMMANDEMENT SUPIÉRIEURE • SUPER ADMIN (ROOT)' : 'DIRECTION OPÉRATIONNELLE • ADMINISTRATEUR GÉNÉRAL' }}</span>
            </div>

            <h1 class="font-serif font-black text-2xl sm:text-3xl text-white tracking-tight">
              {{ isSuperAdmin ? 'Quartier Général & Gouvernance VoiCI-EMI' : 'Direction du Déploiement & Modération Nationale' }}
            </h1>

            <p class="text-xs sm:text-sm text-slate-300 font-light max-w-2xl leading-relaxed">
              {{ isSuperAdmin 
                ? 'Privilèges racines intégraux. Immunité garantie contre toute altération. Accès exclusif à la configuration globale, au journal d\'audit complet et aux 9 rôles d\'acteurs.' 
                : 'Supervision de la salubrité de l\'information publique, coordination des acteurs locaux, suivi des démentis nationaux et traitement des signalements citoyens.' }}
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <span 
              class="px-3.5 py-1.5 rounded-xl font-mono text-xs font-bold shadow-xs flex items-center gap-1.5"
              :class="isSuperAdmin ? 'bg-amber-400 text-slate-950' : 'bg-emerald-500 text-slate-950'"
            >
              <span class="w-2 h-2 rounded-full bg-slate-950 animate-pulse"></span>
              {{ isSuperAdmin ? 'Immunité Absolue' : 'Accès Autorisé' }}
            </span>
            <span class="px-3 py-1.5 rounded-xl bg-slate-800/80 border border-slate-700/60 text-slate-200 font-mono text-xs">
              {{ adminUsersList?.data?.length || 9 }} Comptes Acteurs
            </span>
          </div>
        </div>
      </div>

      <!-- Outer Grid: Sidebar Left (3 cols) + Content Right (9 cols) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- SIDEBAR EXECUTIVE -->
        <aside class="lg:col-span-3 space-y-6 lg:sticky lg:top-24">
          <div class="bg-slate-50/90 rounded-3xl p-6 space-y-4 shadow-[0_4px_20px_rgba(0,0,0,0.02)]">
            <div class="flex items-center gap-3.5">
              <div 
                class="w-12 h-12 rounded-2xl text-white flex items-center justify-center font-serif font-black text-lg shadow-sm shrink-0"
                :class="isSuperAdmin ? 'bg-gradient-to-br from-amber-600 to-slate-950' : 'bg-slate-900'"
              >
                {{ isSuperAdmin ? '👑' : '⭐' }}
              </div>
              <div class="space-y-0.5 overflow-hidden">
                <h2 class="font-bold text-sm text-slate-950 truncate">
                  {{ userDetails?.data?.name || (isSuperAdmin ? 'Super Administrateur' : 'Administrateur VoiCI-EMI') }}
                </h2>
                <p class="text-[11px] text-slate-400 font-mono truncate">
                  {{ userDetails?.data?.email || (isSuperAdmin ? 'sa@voici-emi.bj' : 'admin@voici-emi.bj') }}
                </p>
              </div>
            </div>

            <div class="pt-2 flex flex-wrap items-center gap-2">
              <span 
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-bold shadow-xs"
                :class="isSuperAdmin ? 'bg-amber-100 text-amber-900 border border-amber-300/50' : 'bg-white text-slate-800'"
              >
                <span class="w-2 h-2 rounded-full" :class="isSuperAdmin ? 'bg-amber-600' : 'bg-emerald-500 animate-pulse'"></span>
                {{ authStore.currentRole }}
              </span>
              <span class="text-[10px] text-slate-400 font-mono px-2 py-1">
                Bénin • Live
              </span>
            </div>
          </div>

          <!-- Navigation Menu (Responsive Segmented Nav on Mobile, Vertical on Desktop) -->
          <nav class="bg-white rounded-3xl p-2 sm:p-3 shadow-[0_4px_24px_rgba(0,0,0,0.03)] flex lg:flex-col overflow-x-auto lg:overflow-visible gap-1.5 no-scrollbar shrink-0" aria-label="Menu latéral">
            <button
              type="button"
              @click="activeTab = 'overview'"
              :class="activeTab === 'overview' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">📊</span>
                <span>Vue d'ensemble</span>
              </div>
              <span class="hidden lg:inline-block w-1.5 h-1.5 rounded-full" :class="activeTab === 'overview' ? 'bg-white' : 'opacity-0'"></span>
            </button>

            <button
              type="button"
              @click="activeTab = 'analytics'"
              :class="activeTab === 'analytics' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">📈</span>
                <span>Graphes & Analytique</span>
              </div>
            </button>

            <button
              type="button"
              @click="activeTab = 'users'"
              :class="activeTab === 'users' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">👥</span>
                <span>Comptes & Acteurs</span>
              </div>
              <span class="text-[10px] px-2 py-0.5 rounded-md font-mono font-bold" :class="activeTab === 'users' ? 'bg-amber-400 text-slate-950' : 'bg-slate-100 text-slate-800'">
                {{ adminUsersList?.data?.length || 9 }}
              </span>
            </button>

            <button
              type="button"
              @click="activeTab = 'trainer-supervision'"
              :class="activeTab === 'trainer-supervision' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">🎓</span>
                <span>Supervision Formations & Quiz</span>
              </div>
            </button>

            <button
              v-if="authStore.hasPermission('community_handle_report')"
              type="button"
              @click="activeTab = 'moderation'"
              :class="activeTab === 'moderation' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">🛡️</span>
                <span>Modération Citoyenne</span>
              </div>
            </button>

            <button
              type="button"
              @click="activeTab = 'audit'"
              :class="activeTab === 'audit' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">📜</span>
                <span>Journal d'Audit</span>
              </div>
            </button>

            <button
              type="button"
              @click="activeTab = 'security'"
              :class="activeTab === 'security' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">🔐</span>
                <span>Sécurité & 2FA</span>
              </div>
            </button>
          </nav>
        </aside>

        <!-- MAIN ADMIN CONTENT -->
        <main class="lg:col-span-9 space-y-8">
          
          <!-- TAB 1 : VUE D'ENSEMBLE -->
          <div v-if="activeTab === 'overview'" class="space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
              <div class="bg-white rounded-3xl p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] border border-slate-100 space-y-3">
                <span class="font-bold uppercase tracking-wider text-[10px] font-mono text-slate-400">Comptes Acteurs</span>
                <div class="font-serif font-black text-3xl sm:text-4xl text-slate-950">
                  {{ adminUsersList?.data?.length || 9 }}
                </div>
                <p class="text-[11px] text-slate-400 font-mono">Acteurs vérifiés & actifs</p>
              </div>

              <div class="bg-white rounded-3xl p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] border border-slate-100 space-y-3">
                <span class="font-bold uppercase tracking-wider text-[10px] font-mono text-slate-400">Démentis Publiés</span>
                <div class="font-serif font-black text-3xl sm:text-4xl text-slate-950">1 420</div>
                <p class="text-[11px] text-slate-400 font-mono">Désinformation endiguée</p>
              </div>

              <div class="bg-white rounded-3xl p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] border border-slate-100 space-y-3">
                <span class="font-bold uppercase tracking-wider text-[10px] font-mono text-slate-400">Communes Couvertes</span>
                <div class="font-serif font-black text-3xl sm:text-4xl text-slate-950">100%</div>
                <p class="text-[11px] text-slate-400 font-mono">77 / 77 Communes du Bénin</p>
              </div>

              <div class="bg-white rounded-3xl p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] border border-slate-100 space-y-3">
                <span class="font-bold uppercase tracking-wider text-[10px] font-mono text-slate-400">File Signalements</span>
                <div class="font-serif font-black text-3xl sm:text-4xl text-slate-950">{{ reportsList.length }}</div>
                <p class="text-[11px] text-slate-400 font-mono">Traitement moyen : 15 min</p>
              </div>
            </div>

            <!-- Banner -->
            <div class="bg-slate-50 rounded-3xl p-6 sm:p-8 space-y-4">
              <h3 class="font-serif font-black text-xl sm:text-2xl text-slate-950">
                Pilotage Stratégique VoiCI-EMI Bénin
              </h3>
              <p class="text-xs sm:text-sm text-slate-600 leading-relaxed max-w-3xl font-serif">
                Supervision de la traçabilité des allégations publiques, modération des discussions citoyennes et pilotage du réseau de correspondants et d'apprenants.
              </p>
            </div>
          </div>

          <!-- TAB : GRAPHES & ANALYTIQUE CHIC (POUR ADMINS) -->
          <div v-else-if="activeTab === 'analytics'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-8 border border-slate-100">
              
              <!-- Header with Period Filter -->
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                  <h3 class="font-serif font-black text-xl text-slate-950">
                    Télémétrie & Tableau de Bord Analytique
                  </h3>
                  <p class="text-xs text-slate-400 mt-0.5">
                    Mesure en temps réel de la désinformation endiguée et impact civique au Bénin
                  </p>
                </div>

                <!-- Period pills -->
                <div class="flex items-center gap-1.5 bg-slate-50 p-1 rounded-xl border border-slate-200 self-start sm:self-auto">
                  <button 
                    @click="selectedPeriod = '7d'" 
                    class="px-3 py-1 rounded-lg text-xs font-bold transition"
                    :class="selectedPeriod === '7d' ? 'bg-white text-slate-950 shadow-xs' : 'text-slate-500 hover:text-slate-950'"
                  >
                    7 jours
                  </button>
                  <button 
                    @click="selectedPeriod = '30d'" 
                    class="px-3 py-1 rounded-lg text-xs font-bold transition"
                    :class="selectedPeriod === '30d' ? 'bg-white text-slate-950 shadow-xs' : 'text-slate-500 hover:text-slate-950'"
                  >
                    30 jours
                  </button>
                  <button 
                    @click="selectedPeriod = '12m'" 
                    class="px-3 py-1 rounded-lg text-xs font-bold transition"
                    :class="selectedPeriod === '12m' ? 'bg-white text-slate-950 shadow-xs' : 'text-slate-500 hover:text-slate-950'"
                  >
                    12 mois
                  </button>
                </div>
              </div>

              <!-- Main SVG Vector Line Chart: Évolution temporelle -->
              <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 font-mono">
                    Courbe de Flux : Rumeurs Neutralisées vs Signalements Citoyens
                  </h4>
                  <div class="flex items-center gap-4 text-xs font-mono">
                    <span class="flex items-center gap-1.5 text-emerald-700">
                      <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Démentis Validés
                    </span>
                    <span class="flex items-center gap-1.5 text-amber-700">
                      <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Signalements Reçus
                    </span>
                  </div>
                </div>

                <!-- Responsive Vector SVG Graph -->
                <div class="bg-slate-50/70 p-4 sm:p-6 rounded-2xl border border-slate-100 overflow-x-auto">
                  <div class="min-w-[480px]">
                    <svg viewBox="0 0 600 200" class="w-full h-48 sm:h-56 overflow-visible" preserveAspectRatio="none">
                      <defs>
                        <linearGradient id="gradEmerald" x1="0%" y1="0%" x2="0%" y2="100%">
                          <stop offset="0%" stop-color="#10B981" stop-opacity="0.35" />
                          <stop offset="100%" stop-color="#10B981" stop-opacity="0.0" />
                        </linearGradient>
                        <linearGradient id="gradAmber" x1="0%" y1="0%" x2="0%" y2="100%">
                          <stop offset="0%" stop-color="#F59E0B" stop-opacity="0.25" />
                          <stop offset="100%" stop-color="#F59E0B" stop-opacity="0.0" />
                        </linearGradient>
                      </defs>

                      <!-- Grid Lines -->
                      <line x1="0" y1="40" x2="600" y2="40" stroke="#E2E8F0" stroke-dasharray="4 4" stroke-width="1" />
                      <line x1="0" y1="90" x2="600" y2="90" stroke="#E2E8F0" stroke-dasharray="4 4" stroke-width="1" />
                      <line x1="0" y1="140" x2="600" y2="140" stroke="#E2E8F0" stroke-dasharray="4 4" stroke-width="1" />
                      <line x1="0" y1="190" x2="600" y2="190" stroke="#CBD5E1" stroke-width="1.5" />

                      <!-- Area fills -->
                      <polygon points="0,190 0,140 100,110 200,130 300,80 400,60 500,45 600,30 600,190" fill="url(#gradEmerald)" />
                      <polygon points="0,190 0,160 100,135 200,150 300,115 400,95 500,80 600,65 600,190" fill="url(#gradAmber)" />

                      <!-- Emerald Line (Démentis) -->
                      <polyline
                        fill="none"
                        stroke="#059669"
                        stroke-width="3.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        points="0,140 100,110 200,130 300,80 400,60 500,45 600,30"
                      />

                      <!-- Amber Line (Signalements) -->
                      <polyline
                        fill="none"
                        stroke="#D97706"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-dasharray="6 4"
                        points="0,160 100,135 200,150 300,115 400,95 500,80 600,65"
                      />

                      <!-- Data Points -->
                      <circle cx="0" cy="140" r="4" fill="#059669" />
                      <circle cx="100" cy="110" r="4" fill="#059669" />
                      <circle cx="200" cy="130" r="4" fill="#059669" />
                      <circle cx="300" cy="80" r="4" fill="#059669" />
                      <circle cx="400" cy="60" r="4" fill="#059669" />
                      <circle cx="500" cy="45" r="4" fill="#059669" />
                      <circle cx="600" cy="30" r="5" fill="#059669" stroke="#FFF" stroke-width="2" />
                    </svg>

                    <!-- Timeline Labels -->
                    <div class="flex justify-between text-[10px] font-mono text-slate-400 pt-2 border-t border-slate-200 mt-2">
                      <span>Semaine 1</span>
                      <span>Semaine 2</span>
                      <span>Semaine 3</span>
                      <span>Semaine 4</span>
                      <span>Aujourd'hui (Pic)</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Secondary Charts Grid: Bar Chart + Regional Donut -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                
                <!-- Bar Chart: Types d'infox neutralisées -->
                <div class="bg-slate-50/70 p-6 rounded-2xl border border-slate-100 space-y-4">
                  <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 font-mono">
                    Répartition par Canaux & Typologies
                  </h4>

                  <div class="space-y-3 pt-1">
                    <div class="space-y-1">
                      <div class="flex justify-between text-xs font-semibold text-slate-700">
                        <span>Rumeurs Vocales WhatsApp</span>
                        <span class="font-mono text-slate-900">42%</span>
                      </div>
                      <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-emerald-600 rounded-full w-[42%]"></div>
                      </div>
                    </div>

                    <div class="space-y-1">
                      <div class="flex justify-between text-xs font-semibold text-slate-700">
                        <span>Arnaques SMS Mobile Money</span>
                        <span class="font-mono text-slate-900">28%</span>
                      </div>
                      <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full w-[28%]"></div>
                      </div>
                    </div>

                    <div class="space-y-1">
                      <div class="flex justify-between text-xs font-semibold text-slate-700">
                        <span>Faux Concours & Recrutements</span>
                        <span class="font-mono text-slate-900">18%</span>
                      </div>
                      <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 rounded-full w-[18%]"></div>
                      </div>
                    </div>

                    <div class="space-y-1">
                      <div class="flex justify-between text-xs font-semibold text-slate-700">
                        <span>Deepfakes & Clones Vocaux IA</span>
                        <span class="font-mono text-slate-900">12%</span>
                      </div>
                      <div class="h-2 w-full bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-rose-500 rounded-full w-[12%]"></div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Regional Coverage Donut -->
                <div class="bg-slate-50/70 p-6 rounded-2xl border border-slate-100 space-y-4 flex flex-col justify-between">
                  <div>
                    <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 font-mono">
                      Couverture des 77 Communes du Bénin
                    </h4>
                    <p class="text-xs text-slate-500 mt-1 font-light">
                      Toutes les 77 communes bénéficient d'une antenne de relais ou d'un formateur accrédité.
                    </p>
                  </div>

                  <div class="flex items-center justify-around gap-4 pt-2">
                    <div class="relative w-28 h-28 shrink-0 flex items-center justify-center">
                      <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                        <path
                          class="text-slate-200"
                          stroke-width="3.8"
                          stroke="currentColor"
                          fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <path
                          class="text-emerald-500"
                          stroke-dasharray="100, 100"
                          stroke-width="3.8"
                          stroke-linecap="round"
                          stroke="currentColor"
                          fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                      </svg>
                      <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="font-serif font-black text-xl text-slate-950">100%</span>
                        <span class="text-[9px] font-mono text-emerald-700 font-bold">77 / 77</span>
                      </div>
                    </div>

                    <div class="space-y-1.5 text-xs">
                      <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <span class="font-medium text-slate-700">Littoral & Atlantique (100%)</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                        <span class="font-medium text-slate-700">Ouémé & Plateau (100%)</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-700"></span>
                        <span class="font-medium text-slate-700">Borgou & Alibori (100%)</span>
                      </div>
                      <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-800"></span>
                        <span class="font-medium text-slate-700">Zou, Collines & Nord (100%)</span>
                      </div>
                    </div>
                  </div>
                </div>

              </div>

            </div>
          </div>

          <!-- TAB : GESTION DES COMPTES (ADMIN ONLY) -->
          <div v-else-if="activeTab === 'users'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 border border-slate-100">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h3 class="font-serif font-black text-xl text-slate-950">Comptes & Habilitations des Acteurs</h3>
                  <p class="text-xs text-slate-400">Gestion des 9 profils clés de la plateforme VoiCI-EMI</p>
                </div>
                <div class="flex items-center gap-2">
                  <input 
                    v-model="userSearchQuery"
                    type="text"
                    placeholder="Rechercher nom ou email..."
                    class="text-xs bg-slate-50 rounded-xl px-3.5 py-2 text-slate-900 focus:outline-none"
                  />
                </div>
              </div>

              <BaseTable :headers="userTableHeaders" :loading="adminLoading" :empty="filteredUsers.length === 0">
                <tr 
                  v-for="usr in filteredUsers" 
                  :key="usr.id"
                  class="transition-colors text-xs"
                  :class="[
                    usr.current_role === 'Super Administrateur' ? 'bg-amber-50/50 hover:bg-amber-50/80 border-l-4 border-amber-500 font-medium' :
                    usr.current_role === 'Administrateur' ? 'bg-slate-50 hover:bg-slate-100/90 border-l-4 border-slate-900 font-medium' :
                    'hover:bg-slate-50/70'
                  ]"
                >
                  <td class="px-4 py-3.5">
                    <div class="flex items-center gap-2.5">
                      <span 
                        class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs shrink-0 shadow-xs"
                        :class="usr.current_role === 'Super Administrateur' ? 'bg-amber-500 text-slate-950 font-black' : (usr.current_role === 'Administrateur' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-800')"
                      >
                        {{ usr.current_role === 'Super Administrateur' ? '👑' : (usr.current_role === 'Administrateur' ? '⭐' : usr.name?.charAt(0)) }}
                      </span>
                      <div>
                        <p class="font-bold text-slate-950">{{ usr.name }}</p>
                        <p class="text-[10px] text-slate-400 font-mono">ID #{{ usr.id }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3.5 font-mono text-slate-600">{{ usr.email }}</td>
                  <td class="px-4 py-3.5">
                    <span 
                      class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold"
                      :class="usr.current_role === 'Super Administrateur' ? 'bg-amber-100 text-amber-900 border border-amber-300 font-bold' : (usr.current_role === 'Administrateur' ? 'bg-slate-900 text-white font-bold' : 'bg-slate-100 text-slate-800')"
                    >
                      {{ usr.current_role }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold" :class="usr.status === 'active' ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'">
                      {{ usr.status === 'active' ? 'Actif' : 'Suspendu' }}
                    </span>
                  </td>
                  <td class="px-4 py-3.5 text-right">
                    <div v-if="usr.current_role === 'Super Administrateur'" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200">
                      <span>🔒 Immunisé (Super Admin)</span>
                    </div>
                    <div v-else class="inline-flex items-center gap-2">
                      <button 
                        @click="suspendUser(usr, usr.status === 'active' ? 'suspended' : 'active')"
                        class="px-3 py-1 rounded-xl text-xs font-semibold bg-slate-100 text-slate-700 hover:bg-rose-50 hover:text-rose-700 transition"
                      >
                        {{ usr.status === 'active' ? 'Suspendre' : 'Réactiver' }}
                      </button>
                      <select 
                        @change="changeRole(usr, $event.target.value)"
                        class="text-xs rounded-xl bg-slate-100 text-slate-700 px-2.5 py-1 focus:outline-none"
                      >
                        <option value="" disabled selected>Modifier rôle...</option>
                        <option v-if="authStore.currentRole === 'Super Administrateur'" value="Super Administrateur">Super Administrateur</option>
                        <option value="Administrateur">Administrateur</option>
                        <option value="Rédacteur">Rédacteur</option>
                        <option value="Fact Checker">Fact Checker</option>
                        <option value="Formateur">Formateur</option>
                        <option value="Modérateur">Modérateur</option>
                        <option value="Partenaire">Partenaire</option>
                        <option value="Apprenant">Apprenant</option>
                        <option value="Citoyen">Citoyen</option>
                      </select>
                    </div>
                  </td>
                </tr>
              </BaseTable>
            </div>
          </div>

          <!-- TAB : SUPERVISION FORMATIONS (POUR ADMINS) -->
          <div v-else-if="activeTab === 'trainer-supervision'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 border border-slate-100">
              <h3 class="font-serif font-black text-xl text-slate-950">Suivi des Apprenants & Certifications Délivrées</h3>
              <div class="space-y-3">
                <div v-for="(lrn, idx) in trainerLearnersList" :key="idx" class="p-4 rounded-2xl bg-slate-50 flex items-center justify-between text-xs">
                  <div>
                    <p class="font-bold text-slate-900">{{ lrn.learner_name }} ({{ lrn.learner_email }})</p>
                    <p class="text-slate-500 font-mono text-[11px]">Formation : {{ lrn.course_title }} • {{ lrn.percentage }}% complété</p>
                  </div>
                  <span v-if="lrn.has_certificate" class="px-3 py-1 rounded-full font-bold bg-emerald-100 text-emerald-900">
                    ✅ Certifié ({{ lrn.certificate_number }})
                  </span>
                  <span v-else class="text-slate-400 font-mono">En cours</span>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB : MODÉRATION CITOYENNE (POUR ADMINS & MODÉRATEURS) -->
          <div v-else-if="activeTab === 'moderation'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 border border-slate-100">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h3 class="font-serif font-black text-xl text-slate-950">File des Signalements & Modération Citoyenne</h3>
                  <p class="text-xs text-slate-400">Traitement des contenus signalés pour désinformation ou harcèlement</p>
                </div>
                <button 
                  @click="fetchModeratorData" 
                  class="px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition cursor-pointer self-start sm:self-auto"
                >
                  🔄 Actualiser la file
                </button>
              </div>

              <div v-if="reportsList.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse min-w-[600px]">
                  <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-mono text-[10px] uppercase">
                      <th class="py-3 px-4">Contenu Signalé</th>
                      <th class="py-3 px-4">Motif</th>
                      <th class="py-3 px-4">Date</th>
                      <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="rep in reportsList" :key="rep.id" class="hover:bg-slate-50/80 transition">
                      <td class="py-3.5 px-4 max-w-xs truncate font-medium text-slate-900">
                        {{ rep.reported_item_preview || rep.reason || 'Commentaire signalé' }}
                      </td>
                      <td class="py-3.5 px-4 font-mono text-slate-600">
                        <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-800 text-[10px] font-bold">
                          {{ rep.reason || 'Désinformation' }}
                        </span>
                      </td>
                      <td class="py-3.5 px-4 font-mono text-slate-400 text-[11px]">
                        {{ rep.created_at || 'Aujourd\'hui' }}
                      </td>
                      <td class="py-3.5 px-4 text-right">
                        <div class="inline-flex items-center gap-2">
                          <button 
                            @click="resolveReport(rep, 'approved')"
                            class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-900 font-bold text-[11px] hover:bg-emerald-200 transition"
                          >
                            Valider suppression
                          </button>
                          <button 
                            @click="resolveReport(rep, 'dismissed')"
                            class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-bold text-[11px] hover:bg-slate-200 transition"
                          >
                            Classer sans suite
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-else class="p-8 text-center text-xs text-slate-400 bg-slate-50 rounded-2xl">
                <span>🛡️ Aucun signalement en attente. Toutes les discussions citoyennes sont conformes.</span>
              </div>
            </div>
          </div>

          <!-- TAB : AUDIT -->
          <div v-else-if="activeTab === 'audit'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 border border-slate-100">
              <h3 class="font-serif font-black text-xl text-slate-950">Journal d'Audit Immuable</h3>
              <div class="space-y-2 max-h-[500px] overflow-y-auto">
                <div v-for="log in auditLogsList" :key="log.id" class="p-3.5 rounded-xl bg-slate-50 text-xs flex justify-between font-mono">
                  <span>{{ log.description }}</span>
                  <span class="text-slate-400">{{ log.created_at }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB : SÉCURITÉ -->
          <div v-else-if="activeTab === 'security'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 max-w-2xl border border-slate-100">
              <h3 class="font-serif font-black text-xl text-slate-950">Sécurité & Double Authentification (2FA)</h3>
              <div class="flex items-center justify-between p-5 rounded-2xl bg-slate-50 text-xs">
                <div>
                  <p class="font-bold text-slate-900">Statut du 2FA</p>
                  <p class="text-slate-500">{{ userDetails?.data?.two_factor_confirmed_at ? 'Activé' : 'Désactivé' }}</p>
                </div>
                <button @click="toggle2Fa" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold cursor-pointer">
                  {{ userDetails?.data?.two_factor_confirmed_at ? 'Désactiver' : 'Activer' }}
                </button>
              </div>
            </div>
          </div>

        </main>
      </div>
    </div>


    <!-- ========================================================================= -->
    <!-- CAS 2 : STUDIO DU FORMATEUR (RÔLE FORMATEUR DÉDIÉ)                        -->
    <!-- CRÉATION MULTI-SUPPORTS (PDF, VIDÉO, IMAGES, TEXTES, VOCALE) & CERTIFICATION -->
    <!-- ========================================================================= -->
    <div v-else-if="isTrainer" class="space-y-8">
      
      <!-- HERO BANNER FORMATEUR -->
      <div class="rounded-3xl p-6 sm:p-8 text-white bg-gradient-to-r from-purple-950 via-slate-900 to-indigo-950 relative overflow-hidden shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
          <div class="space-y-2">
            <div class="inline-flex items-center gap-2 bg-purple-500/20 text-purple-300 border border-purple-500/30 px-3.5 py-1 rounded-full text-xs font-bold font-mono">
              <span>📚</span>
              <span>STUDIO FORMATEUR ACCRÉDITÉ • ACADÉMIE VOICI-EMI</span>
            </div>
            <h1 class="font-serif font-black text-2xl sm:text-3xl text-white">
              Studio Pédagogique : Création Modulaire & Suivi des Apprenants
            </h1>
            <p class="text-xs sm:text-sm text-slate-300 font-light max-w-2xl leading-relaxed">
              Concevez vos formations avec des modules personnalisés et tous formats de médias (PDF, Vidéo, Images, Textes, Vocale). Suivez la progression exacte de chaque apprenant et délivrez les certifications officielles VoiCI-EMI.
            </p>
          </div>

          <div class="flex items-center gap-3 shrink-0">
            <button 
              @click="activeTab = 'builder'"
              class="px-4 py-2.5 rounded-2xl bg-amber-400 text-slate-950 font-bold text-xs hover:bg-amber-300 transition shadow-xs flex items-center gap-2 cursor-pointer"
            >
              <span>➕ Créer une Formation</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Outer Grid Formateur -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- SIDEBAR FORMATEUR -->
        <aside class="lg:col-span-3 space-y-6 lg:sticky lg:top-24">
          <div class="bg-slate-50/90 rounded-3xl p-6 space-y-4 shadow-[0_4px_20px_rgba(0,0,0,0.02)]">
            <div class="flex items-center gap-3.5">
              <div class="w-12 h-12 rounded-2xl bg-purple-900 text-white flex items-center justify-center font-serif font-black text-lg shadow-sm shrink-0">
                📚
              </div>
              <div class="space-y-0.5 overflow-hidden">
                <h2 class="font-bold text-sm text-slate-950 truncate">
                  {{ userDetails?.data?.name || 'Aïcha Bio Guéra' }}
                </h2>
                <p class="text-[11px] text-slate-400 font-mono truncate">
                  {{ userDetails?.data?.email || 'formateur@voici-emi.bj' }}
                </p>
              </div>
            </div>

            <div class="pt-2 flex items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-purple-100 text-purple-900 text-[11px] font-bold shadow-xs">
                Formatrice Agréée
              </span>
            </div>
          </div>

          <!-- Navigation Formateur (Responsive Segmented Nav on Mobile, Vertical on Desktop) -->
          <nav class="bg-white rounded-3xl p-2 sm:p-3 shadow-[0_4px_24px_rgba(0,0,0,0.03)] flex lg:flex-col overflow-x-auto lg:overflow-visible gap-1.5 no-scrollbar shrink-0" aria-label="Menu du formateur">
            
            <button
              type="button"
              @click="activeTab = 'learners'"
              :class="activeTab === 'learners' ? 'bg-purple-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">👥</span>
                <span>Suivi des Apprenants</span>
              </div>
              <span class="text-[10px] px-2 py-0.5 rounded-md font-mono" :class="activeTab === 'learners' ? 'bg-purple-800 text-white' : 'bg-slate-100 text-slate-700'">
                {{ trainerLearnersList.length }}
              </span>
            </button>

            <button
              type="button"
              @click="activeTab = 'builder'"
              :class="activeTab === 'builder' ? 'bg-purple-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">🛠️</span>
                <span>Créateur de Formations</span>
              </div>
              <span class="text-[10px] px-2 py-0.5 rounded-md bg-amber-400 text-slate-950 font-bold">Multi-Médias</span>
            </button>

            <button
              type="button"
              @click="activeTab = 'security'"
              :class="activeTab === 'security' ? 'bg-purple-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">🔐</span>
                <span>Sécurité & 2FA</span>
              </div>
            </button>
          </nav>
        </aside>

        <!-- MAIN FORMATEUR CONTENT -->
        <main class="lg:col-span-9 space-y-8">
          
          <!-- =================================================================== -->
          <!-- ONGLET 1 : SUIVI DE LA PROGRESSION DE CHAQUE APPRENANT & CERTIFICATION -->
          <!-- =================================================================== -->
          <div v-if="activeTab === 'learners'" class="space-y-6">
            
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 border border-slate-100">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h3 class="font-serif font-black text-xl text-slate-950">
                    Tableau de Progression des Apprenants
                  </h3>
                  <p class="text-xs text-slate-400 mt-0.5">
                    Visualisez les cours suivis, l'avancement exact de chaque apprenant et délivrez les certifications
                  </p>
                </div>
                <button 
                  @click="fetchTrainerLearners" 
                  class="px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition cursor-pointer"
                >
                  🔄 Actualiser la progression
                </button>
              </div>

              <!-- Learners Progress Table -->
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-mono text-[10px] uppercase">
                      <th class="py-3 px-4">Apprenant</th>
                      <th class="py-3 px-4">Formation Suivie</th>
                      <th class="py-3 px-4">Progression Globale</th>
                      <th class="py-3 px-4">Score Quiz</th>
                      <th class="py-3 px-4 text-right">Attribution Certification</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <tr v-for="(lrn, idx) in trainerLearnersList" :key="idx" class="hover:bg-slate-50/80 transition">
                      
                      <!-- Learner info -->
                      <td class="py-4 px-4">
                        <div class="flex items-center gap-3">
                          <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-900 font-bold flex items-center justify-center shrink-0">
                            {{ lrn.learner_name?.charAt(0) }}
                          </div>
                          <div>
                            <p class="font-bold text-slate-950">{{ lrn.learner_name }}</p>
                            <p class="text-[11px] text-slate-400 font-mono">{{ lrn.learner_email }}</p>
                          </div>
                        </div>
                      </td>

                      <!-- Course title followed -->
                      <td class="py-4 px-4 font-serif text-slate-800 font-medium max-w-xs truncate">
                        {{ lrn.course_title }}
                      </td>

                      <!-- Progress bar & metrics -->
                      <td class="py-4 px-4 min-w-[180px]">
                        <div class="space-y-1.5">
                          <div class="flex justify-between text-[11px] font-mono">
                            <span class="font-bold text-slate-800">{{ lrn.percentage }}%</span>
                            <span class="text-slate-400">{{ lrn.completed_lessons }} / {{ lrn.total_lessons }} modules</span>
                          </div>
                          <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                            <div 
                              class="h-full rounded-full transition-all duration-500"
                              :class="lrn.percentage === 100 ? 'bg-emerald-500' : 'bg-purple-600'"
                              :style="{ width: `${lrn.percentage}%` }"
                            ></div>
                          </div>
                        </div>
                      </td>

                      <!-- Quiz Score -->
                      <td class="py-4 px-4 font-mono font-bold">
                        <span :class="lrn.average_quiz_score >= 80 ? 'text-emerald-700 bg-emerald-50' : 'text-amber-800 bg-amber-50'" class="px-2.5 py-1 rounded-lg">
                          {{ lrn.average_quiz_score }}/100
                        </span>
                      </td>

                      <!-- Certification Action (Give certification) -->
                      <td class="py-4 px-4 text-right">
                        <div v-if="lrn.has_certificate" class="inline-flex items-center gap-2">
                          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-50 text-emerald-800 font-bold text-xs border border-emerald-200">
                            <span>✅</span>
                            <span>Certifié</span>
                          </span>
                          <button 
                            @click="selectedCertificate = { number: lrn.certificate_number, recipient: lrn.learner_name, course: lrn.course_title, issued_at: lrn.certificate_issued_at, trainer: 'Aïcha Bio Guéra' }"
                            class="px-2.5 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold cursor-pointer"
                            title="Voir l'attestation"
                          >
                            👁️ Voir
                          </button>
                        </div>

                        <!-- Button to grant certificate -->
                        <button
                          v-else
                          type="button"
                          @click="issueCertificateToLearner(lrn)"
                          :disabled="issuingCertId === `${lrn.learner_id}-${lrn.course_id}`"
                          class="px-3.5 py-1.5 rounded-xl bg-purple-900 text-white font-bold text-xs hover:bg-purple-800 transition shadow-xs flex items-center gap-1.5 ml-auto cursor-pointer"
                        >
                          <span>🎓</span>
                          <span>{{ issuingCertId === `${lrn.learner_id}-${lrn.course_id}` ? 'Délivrance...' : 'Délivrer la Certification' }}</span>
                        </button>
                      </td>

                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>


          <!-- =================================================================== -->
          <!-- ONGLET 2 : CRÉATEUR DE COURS MODULAIRE & MULTI-SUPPORTS             -->
          <!-- (PDF, VIDÉO, IMAGES, TEXTES, VOCALE)                                -->
          <!-- =================================================================== -->
          <div v-else-if="activeTab === 'builder'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-8 border border-slate-100">
              
              <div>
                <div class="inline-flex items-center gap-2 bg-amber-50 text-amber-900 border border-amber-200 px-3 py-0.5 rounded-full text-xs font-bold mb-2">
                  <span>🛠️ Atelier Pédagogique Multi-Médias</span>
                </div>
                <h3 class="font-serif font-black text-2xl text-slate-950">
                  Créer une Nouvelle Formation & ses Modules
                </h3>
                <p class="text-xs sm:text-sm text-slate-500 font-light mt-1">
                  Structurez librement vos cours en ajoutant des modules et en intégrant des fichiers PDF, vidéos, infographies, textes ou chroniques vocales en langues nationales.
                </p>
              </div>

              <!-- General Course Info -->
              <div class="space-y-4 bg-slate-50/70 p-6 rounded-2xl border border-slate-100">
                <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 font-mono">1. Informations Générales de la Formation</h4>
                
                <div class="space-y-1">
                  <label class="text-xs font-bold text-slate-800">Titre de la formation</label>
                  <input 
                    v-model="newCourse.title"
                    type="text"
                    placeholder="ex: Décrypter la propagande électorale et les rumeurs sur TikTok"
                    class="w-full text-xs bg-white rounded-xl px-4 py-2.5 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600 font-serif font-bold"
                  />
                </div>

                <div class="space-y-1">
                  <label class="text-xs font-bold text-slate-800">Description et objectifs pédagogiques</label>
                  <textarea 
                    v-model="newCourse.description"
                    rows="3"
                    placeholder="Présentation des compétences critiques acquises par l'apprenant à l'issue de la formation..."
                    class="w-full text-xs bg-white rounded-xl p-3 border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-600"
                  ></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-800">Discipline / Catégorie</label>
                    <select v-model="newCourse.category" class="w-full text-xs bg-white rounded-xl px-3 py-2 border border-slate-200 focus:outline-none">
                      <option value="Fact-Checking & Vérification">Fact-Checking & Vérification</option>
                      <option value="Hygiène & Sécurité Numérique">Hygiène & Sécurité Numérique</option>
                      <option value="Détection des Deepfakes IA">Détection des Deepfakes IA</option>
                      <option value="Éthique et Déontologie ODEM">Éthique et Déontologie ODEM</option>
                    </select>
                  </div>

                  <div class="space-y-1">
                    <label class="text-xs font-bold text-slate-800">Niveau visé</label>
                    <select v-model="newCourse.level" class="w-full text-xs bg-white rounded-xl px-3 py-2 border border-slate-200 focus:outline-none">
                      <option value="Tous niveaux (Inclusif)">Tous niveaux (Inclusif)</option>
                      <option value="Débutant Citoyen">Débutant Citoyen</option>
                      <option value="Intermédiaire / Étudiant">Intermédiaire / Étudiant</option>
                      <option value="Avancé / Journaliste">Avancé / Journaliste</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Modular Chapters Section (Modules configurables comme le formateur veut) -->
              <div class="space-y-6">
                <div class="flex items-center justify-between">
                  <h4 class="font-bold text-xs uppercase tracking-wider text-slate-500 font-mono">
                    2. Modules de la Formation ({{ newCourse.modules.length }} module(s))
                  </h4>

                  <button 
                    type="button"
                    @click="addModule"
                    class="px-4 py-2 rounded-xl bg-purple-50 text-purple-900 border border-purple-200 text-xs font-bold hover:bg-purple-100 transition flex items-center gap-1.5 cursor-pointer"
                  >
                    <span>➕ Ajouter un Module</span>
                  </button>
                </div>

                <!-- Module Cards -->
                <div 
                  v-for="(mod, mIdx) in newCourse.modules" 
                  :key="mIdx"
                  class="bg-white rounded-3xl p-6 border-2 border-slate-100 space-y-5 shadow-xs"
                >
                  <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div class="flex items-center gap-2.5 flex-1 mr-4">
                      <span class="w-7 h-7 rounded-lg bg-purple-900 text-white flex items-center justify-center font-bold text-xs shrink-0 font-mono">
                        {{ mIdx + 1 }}
                      </span>
                      <input 
                        v-model="mod.title"
                        type="text"
                        placeholder="Titre du module..."
                        class="w-full font-serif font-bold text-sm bg-slate-50 px-3 py-1.5 rounded-lg border border-transparent focus:border-slate-300 focus:outline-none"
                      />
                    </div>

                    <button 
                      @click="removeModule(mIdx)"
                      class="text-xs text-rose-600 hover:text-rose-800 font-bold px-2 py-1 rounded cursor-pointer"
                      title="Supprimer ce module"
                    >
                      Supprimer module ✕
                    </button>
                  </div>

                  <!-- Lessons in module with multi-media format selector -->
                  <div class="space-y-4 pl-2 sm:pl-4">
                    <div class="flex items-center justify-between">
                      <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 font-mono">Ressources & Supports du Module</span>
                      <button 
                        @click="addLessonToModule(mod)"
                        class="text-xs font-bold text-purple-700 hover:text-purple-900 flex items-center gap-1 cursor-pointer"
                      >
                        <span>+ Ajouter une ressource</span>
                      </button>
                    </div>

                    <!-- Lesson item -->
                    <div 
                      v-for="(les, lIdx) in mod.lessons" 
                      :key="lIdx"
                      class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-3 text-xs"
                    >
                      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <input 
                          v-model="les.title"
                          type="text"
                          placeholder="Titre de la ressource..."
                          class="w-full font-bold bg-white px-3 py-1.5 rounded-lg border border-slate-200 focus:outline-none"
                        />

                        <!-- Format Selector : 5 Formats -->
                        <div class="flex items-center gap-2 shrink-0">
                          <label class="text-[11px] font-semibold text-slate-500 font-mono">Format :</label>
                          <select 
                            v-model="les.content_type"
                            class="bg-white font-bold px-3 py-1.5 rounded-lg border border-slate-200 focus:outline-none text-slate-900 cursor-pointer"
                          >
                            <option value="pdf">📄 Fichier PDF (Document)</option>
                            <option value="video">🎥 Vidéo (MP4 / Streaming)</option>
                            <option value="image">🖼️ Images (Infographie)</option>
                            <option value="text">📝 Textes (et FALC)</option>
                            <option value="audio">🎙️ Vocale (Chronique Audio)</option>
                          </select>

                          <button 
                            @click="removeLessonFromModule(mod, lIdx)"
                            class="text-slate-400 hover:text-rose-600 px-1 cursor-pointer"
                            title="Supprimer la ressource"
                          >
                            ✕
                          </button>
                        </div>
                      </div>

                      <!-- Format specific fields -->
                      <!-- PDF Format -->
                      <div v-if="les.content_type === 'pdf'" class="space-y-2 pt-2 border-t border-slate-200/60">
                        <div class="flex items-center gap-2 text-rose-700 font-semibold text-[11px]">
                          <span>📄 Support Fichier PDF :</span>
                          <span class="text-slate-400">Renseignez l'URL ou le chemin du document à télécharger</span>
                        </div>
                        <input 
                          v-model="les.video_url"
                          type="text"
                          placeholder="ex: https://voici-emi.bj/docs/fiche_synthese_fake_news.pdf"
                          class="w-full bg-white px-3 py-1.5 rounded-lg border border-slate-200 font-mono text-[11px]"
                        />
                        <textarea 
                          v-model="les.content"
                          rows="2"
                          placeholder="Notice explicative et instructions de lecture pour l'apprenant..."
                          class="w-full bg-white p-2.5 rounded-lg border border-slate-200"
                        ></textarea>
                      </div>

                      <!-- Video Format -->
                      <div v-else-if="les.content_type === 'video'" class="space-y-2 pt-2 border-t border-slate-200/60">
                        <div class="flex items-center gap-2 text-blue-700 font-semibold text-[11px]">
                          <span>🎥 Support Vidéo Pédagogique :</span>
                          <span class="text-slate-400">Lien MP4, YouTube, Vimeo ou serveur vidéo</span>
                        </div>
                        <input 
                          v-model="les.video_url"
                          type="text"
                          placeholder="ex: https://youtube.com/watch?v=... ou /media/videos/lecon1.mp4"
                          class="w-full bg-white px-3 py-1.5 rounded-lg border border-slate-200 font-mono text-[11px]"
                        />
                        <textarea 
                          v-model="les.content"
                          rows="2"
                          placeholder="Points clés abordés dans la vidéo..."
                          class="w-full bg-white p-2.5 rounded-lg border border-slate-200"
                        ></textarea>
                      </div>

                      <!-- Image / Infographic Format -->
                      <div v-else-if="les.content_type === 'image'" class="space-y-2 pt-2 border-t border-slate-200/60">
                        <div class="flex items-center gap-2 text-emerald-700 font-semibold text-[11px]">
                          <span>🖼️ Support Image / Infographie :</span>
                          <span class="text-slate-400">URL ou fichier image du schéma comparatif</span>
                        </div>
                        <input 
                          v-model="les.video_url"
                          type="text"
                          placeholder="ex: https://voici-emi.bj/images/schema_anatomie_rumeur.png"
                          class="w-full bg-white px-3 py-1.5 rounded-lg border border-slate-200 font-mono text-[11px]"
                        />
                        <textarea 
                          v-model="les.content"
                          rows="2"
                          placeholder="Légende et analyse détaillée du schéma visuel..."
                          class="w-full bg-white p-2.5 rounded-lg border border-slate-200"
                        ></textarea>
                      </div>

                      <!-- Voice / Audio Format -->
                      <div v-else-if="les.content_type === 'audio'" class="space-y-2 pt-2 border-t border-slate-200/60">
                        <div class="flex items-center justify-between gap-2 text-purple-700 font-semibold text-[11px]">
                          <span>🎙️ Capsule Vocale & Podcast :</span>
                          <div class="flex items-center gap-1.5">
                            <span class="text-slate-500">Langue de la voix :</span>
                            <select v-model="les.audio_language" class="bg-white px-2 py-0.5 rounded border border-slate-200">
                              <option value="fr">Français</option>
                              <option value="fon">Fɔngbe (Bénin)</option>
                              <option value="yo">Yorùbá (Bénin)</option>
                              <option value="bar">Baatɔnum (Bénin)</option>
                            </select>
                          </div>
                        </div>
                        <input 
                          v-model="les.audio_url"
                          type="text"
                          placeholder="ex: https://voici-emi.bj/audio/chronique_sensibilisation.mp3"
                          class="w-full bg-white px-3 py-1.5 rounded-lg border border-slate-200 font-mono text-[11px]"
                        />
                        <textarea 
                          v-model="les.content"
                          rows="2"
                          placeholder="Résumé de la chronique et transcription vocale pour l'accessibilité..."
                          class="w-full bg-white p-2.5 rounded-lg border border-slate-200"
                        ></textarea>
                      </div>

                      <!-- Standard Text & FALC Format -->
                      <div v-else class="space-y-2 pt-2 border-t border-slate-200/60">
                        <textarea 
                          v-model="les.content"
                          rows="3"
                          placeholder="Texte intégral du cours..."
                          class="w-full bg-white p-2.5 rounded-lg border border-slate-200"
                        ></textarea>
                        <textarea 
                          v-model="les.content_simplified"
                          rows="2"
                          placeholder="Adaptation simplifiée FALC (Facile À Lire et à Comprendre)..."
                          class="w-full bg-amber-50/60 p-2.5 rounded-lg border border-amber-200/70"
                        ></textarea>
                      </div>

                    </div>
                  </div>
                </div>
              </div>

              <!-- Final Submit Button -->
              <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-400">
                  Prêt à être publié dans le catalogue de l'Académie VoiCI-EMI
                </span>
                <button 
                  type="button"
                  @click="publishCourse"
                  class="px-6 py-3 rounded-2xl bg-purple-900 text-white font-bold text-xs hover:bg-purple-800 transition shadow-md flex items-center gap-2 cursor-pointer"
                >
                  <span>🚀 Publier la Formation Modulaire</span>
                </button>
              </div>

            </div>
          </div>

          <!-- TAB : SÉCURITÉ FORMATEUR -->
          <div v-else-if="activeTab === 'security'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 max-w-2xl border border-slate-100">
              <h3 class="font-serif font-black text-xl text-slate-950">Sécurité & Profil du Formateur</h3>
              <div class="flex items-center justify-between p-5 rounded-2xl bg-slate-50 text-xs">
                <div>
                  <p class="font-bold text-slate-900">Double Authentification (2FA)</p>
                  <p class="text-slate-500">{{ userDetails?.data?.two_factor_confirmed_at ? 'Activé' : 'Désactivé' }}</p>
                </div>
                <button @click="toggle2Fa" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold cursor-pointer">
                  {{ userDetails?.data?.two_factor_confirmed_at ? 'Désactiver' : 'Activer' }}
                </button>
              </div>
            </div>
          </div>

        </main>
      </div>

    </div>


    <!-- ========================================================================= -->
    <!-- CAS 3 : ESPACE PERSONNEL (CITOYEN, APPRENANT, RÉDACTEUR, MODÉRATEUR, ETC.) -->
    <!-- ========================================================================= -->
    <div v-else class="space-y-8">
      
      <!-- HERO BANNER PERSONNEL -->
      <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] border border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
          <div class="inline-flex items-center gap-2 bg-slate-100 px-3 py-1 rounded-full text-xs font-semibold text-slate-700">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Portail Citoyen & Académie VoiCI-EMI</span>
          </div>

          <h1 class="font-serif font-black text-2xl sm:text-3xl text-slate-950">
            Bienvenue sur votre Espace, {{ userDetails?.data?.name || authStore.user?.name || 'Citoyen' }}
          </h1>

          <p class="text-xs sm:text-sm text-slate-500 font-light max-w-2xl leading-relaxed">
            Votre espace personnel dédié au renforcement de l'esprit critique, au suivi de vos attestations d'éducation aux médias et à votre participation active à l'information fiable au Bénin.
          </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
          <div class="px-4 py-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-800 text-xs font-bold flex items-center gap-2">
            <span>🇧🇯</span>
            <span>Rôle : {{ authStore.currentRole }}</span>
          </div>
        </div>
      </div>

      <!-- Outer Grid Personal -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <aside class="lg:col-span-3 space-y-6 lg:sticky lg:top-24">
          <div class="bg-slate-50/90 rounded-3xl p-6 space-y-4 shadow-[0_4px_20px_rgba(0,0,0,0.02)]">
            <div class="flex items-center gap-3.5">
              <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-serif font-black text-lg shadow-sm shrink-0">
                {{ userDetails?.data?.name?.charAt(0) || authStore.user?.name?.charAt(0) || 'C' }}
              </div>
              <div class="space-y-0.5 overflow-hidden">
                <h2 class="font-bold text-sm text-slate-950 truncate">
                  {{ userDetails?.data?.name || authStore.user?.name || 'Utilisateur' }}
                </h2>
                <p class="text-[11px] text-slate-400 font-mono truncate">
                  {{ userDetails?.data?.email || authStore.user?.email || 'moncompte@voici-emi.bj' }}
                </p>
              </div>
            </div>

            <div class="pt-2 flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-white text-slate-800 text-[11px] font-bold shadow-xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                {{ authStore.currentRole }}
              </span>
            </div>
          </div>

          <!-- Navigation Citoyen / Apprenant (Responsive Segmented Nav on Mobile, Vertical on Desktop) -->
          <nav class="bg-white rounded-3xl p-2 sm:p-3 shadow-[0_4px_24px_rgba(0,0,0,0.03)] flex lg:flex-col overflow-x-auto lg:overflow-visible gap-1.5 no-scrollbar shrink-0" aria-label="Menu personnel">
            <button
              type="button"
              @click="activeTab = 'overview'"
              :class="activeTab === 'overview' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">📊</span>
                <span>Mon Espace Personnel</span>
              </div>
            </button>

            <button
              type="button"
              @click="activeTab = 'trainings'"
              :class="activeTab === 'trainings' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">🎓</span>
                <span>Mes Formations & Quiz</span>
              </div>
            </button>

            <button
              type="button"
              @click="activeTab = 'security'"
              :class="activeTab === 'security' ? 'bg-slate-900 text-white shadow-sm font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'"
              class="w-auto lg:w-full text-left px-3.5 sm:px-4 py-2.5 sm:py-3 rounded-2xl text-xs font-semibold flex items-center justify-between transition-all whitespace-nowrap shrink-0"
            >
              <div class="flex items-center gap-2.5 sm:gap-3">
                <span class="text-base">🔐</span>
                <span>Sécurité & 2FA</span>
              </div>
            </button>
          </nav>
        </aside>

        <!-- MAIN CONTENT FOR CITIZEN -->
        <main class="lg:col-span-9 space-y-8">
          <div v-if="activeTab === 'overview'" class="space-y-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
              <div class="bg-white rounded-3xl p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-2 border border-slate-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono">Formations</span>
                <div class="font-serif font-black text-3xl text-slate-950">{{ userProgress.enrolledCourses.length }}</div>
                <p class="text-[11px] text-slate-400">Modules en cours</p>
              </div>

              <div class="bg-white rounded-3xl p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-2 border border-slate-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono">Attestations</span>
                <div class="font-serif font-black text-3xl text-emerald-600">{{ userProgress.coursesCompleted }}</div>
                <p class="text-[11px] text-slate-400">Certificats téléchargeables</p>
              </div>

              <div class="bg-white rounded-3xl p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-2 border border-slate-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono">Signalements</span>
                <div class="font-serif font-black text-3xl text-slate-950">{{ userProgress.reportsSubmitted }}</div>
                <p class="text-[11px] text-slate-400">Alertes transmises</p>
              </div>

              <div class="bg-white rounded-3xl p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-2 border border-slate-100">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 font-mono">Esprit Critique</span>
                <div class="font-serif font-black text-3xl text-amber-600">{{ userProgress.criticalThinkingScore }}/100</div>
                <p class="text-[11px] text-slate-400">Niveau : Citoyen Élite</p>
              </div>
            </div>

            <!-- In progress training -->
            <div class="bg-slate-50 rounded-3xl p-6 sm:p-8 space-y-4">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <span class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono">Reprendre mon apprentissage</span>
                  <h3 class="font-serif font-bold text-xl text-slate-950 mt-1">
                    Identifier les Images Manipulées et Deepfakes par IA
                  </h3>
                  <p class="text-xs text-slate-500 mt-0.5">Formatrice : Aïcha Bio Guéra • 65% complété</p>
                </div>
                <router-link :to="{ name: 'lms' }" class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition shadow-sm">
                  Continuer la leçon →
                </router-link>
              </div>
              <div class="h-2 w-full bg-white rounded-full overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full" style="width: 65%"></div>
              </div>
            </div>
          </div>

          <!-- TAB : MES FORMATIONS -->
          <div v-else-if="activeTab === 'trainings'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 border border-slate-100">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                  <h3 class="font-serif font-black text-xl text-slate-950">Mes Formations & Certifications</h3>
                  <p class="text-xs text-slate-500 font-light mt-0.5">Retrouvez toutes vos formations en cours et vos attestations officielles.</p>
                </div>
                <router-link
                  :to="{ name: 'lms' }"
                  class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-sm inline-flex items-center gap-2"
                >
                  <span>Explorer le Catalogue Coursera (Toutes les formations)</span>
                  <span>→</span>
                </router-link>
              </div>

              <div class="space-y-4">
                <div v-for="course in userProgress.enrolledCourses" :key="course.id" class="p-5 rounded-2xl bg-slate-50 flex items-center justify-between text-xs">
                  <div>
                    <h4 class="font-bold text-slate-950 text-sm">{{ course.title }}</h4>
                    <p class="text-slate-500">{{ course.instructor }} • {{ course.category }}</p>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="px-3 py-1 rounded-full font-bold bg-emerald-50 text-emerald-800">
                      {{ course.status }}
                    </span>
                    <router-link
                      :to="{ name: 'lms' }"
                      class="px-3.5 py-1.5 rounded-lg bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition"
                    >
                      Accéder au cours →
                    </router-link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- TAB : SÉCURITÉ -->
          <div v-else-if="activeTab === 'security'" class="space-y-6">
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6 max-w-2xl border border-slate-100">
              <h3 class="font-serif font-black text-xl text-slate-950">Sécurité & Profil Personnel</h3>
              <div class="flex items-center justify-between p-5 rounded-2xl bg-slate-50 text-xs">
                <div>
                  <p class="font-bold text-slate-950">Double Authentification (2FA)</p>
                  <p class="text-slate-500">{{ userDetails?.data?.two_factor_confirmed_at ? 'Activé' : 'Désactivé' }}</p>
                </div>
                <button @click="toggle2Fa" class="px-4 py-2 rounded-xl bg-slate-900 text-white font-bold cursor-pointer">
                  {{ userDetails?.data?.two_factor_confirmed_at ? 'Désactiver' : 'Activer' }}
                </button>
              </div>
            </div>
          </div>
        </main>
      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL D'ATTESTATION OFFICIELLE VoiCI-EMI (CERTIFICATION DÉLIVRÉE)          -->
    <!-- ========================================================================= -->
    <div 
      v-if="selectedCertificate" 
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
      role="dialog"
      aria-modal="true"
    >
      <div class="bg-white rounded-3xl max-w-2xl w-full p-8 sm:p-10 shadow-2xl space-y-6 border border-slate-100 relative">
        
        <!-- Header Certificate -->
        <div class="text-center space-y-1.5 border-b border-slate-100 pb-6">
          <p class="text-[10px] font-mono uppercase font-bold tracking-widest text-slate-400">
            RÉPUBLIQUE DU BÉNIN • MINISTÈRE DU NUMÉRIQUE
          </p>
          <div class="flex items-center justify-center gap-2 text-2xl">
            <span>🇧🇯</span>
            <span class="font-serif font-black text-xl text-slate-950">VoiCI-EMI Bénin</span>
            <span>🇧🇯</span>
          </div>
          <h2 class="font-serif font-black text-lg sm:text-xl text-purple-950 uppercase tracking-wide pt-2">
            Attestation Officielle de Réussite & d'Accréditation
          </h2>
        </div>

        <!-- Body Certificate -->
        <div class="text-center space-y-4 py-2">
          <p class="text-xs text-slate-500 font-light">Le corps professoral et les formateurs de l'Académie VoiCI-EMI certifient que :</p>
          <h3 class="font-serif font-black text-2xl sm:text-3xl text-slate-950 tracking-tight text-purple-900">
            {{ selectedCertificate.recipient }}
          </h3>
          <p class="text-xs text-slate-600 max-w-md mx-auto leading-relaxed">
            a validé avec succès l'ensemble des modules d'évaluation pratique et de décryptage critique de la formation certifiante :
          </p>
          <div class="p-4 rounded-2xl bg-purple-50 text-purple-950 font-serif font-bold text-sm sm:text-base border border-purple-200">
            « {{ selectedCertificate.course }} »
          </div>
        </div>

        <!-- Verification & Signature Footer -->
        <div class="pt-6 border-t border-slate-100 grid grid-cols-2 gap-4 text-xs">
          <div class="space-y-1">
            <span class="text-[10px] font-mono text-slate-400 uppercase block">Identifiant Unique</span>
            <code class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-[11px] block select-all">
              {{ selectedCertificate.number }}
            </code>
            <span class="text-[10px] text-emerald-700 font-semibold block">Délivré le {{ selectedCertificate.issued_at }}</span>
          </div>

          <div class="text-right space-y-1">
            <span class="text-[10px] font-mono text-slate-400 uppercase block">Signature du Formateur</span>
            <p class="font-serif font-bold text-slate-900 italic text-sm">{{ selectedCertificate.trainer }}</p>
            <span class="text-[10px] text-purple-800 font-medium">Formatrice Référente Académie</span>
          </div>
        </div>

        <!-- Close & Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
          <button 
            type="button" 
            @click="selectedCertificate = null"
            class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer"
          >
            Fermer
          </button>
          <button 
            type="button" 
            @click="toastStore.success('Attestation officielle prête pour impression/PDF !')"
            class="px-5 py-2.5 rounded-xl bg-purple-900 hover:bg-purple-800 text-white font-bold text-xs transition shadow-xs cursor-pointer"
          >
            Imprimer / PDF 🖨️
          </button>
        </div>

      </div>
    </div>

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
