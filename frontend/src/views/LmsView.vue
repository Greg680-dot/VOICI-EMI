<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToastStore } from '../stores/toast';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseBadge from '../components/ui/BaseBadge.vue';
import SkeletonLoader from '../components/ui/SkeletonLoader.vue';
import EmptyState from '../components/ui/EmptyState.vue';

const authStore = useAuthStore();
const toastStore = useToastStore();

// Main view mode: 'catalog' (Coursera Catalog) | 'my_courses' (Inscriptions) | 'classroom' (Salle de cours)
const activeView = ref('catalog');

// Data states
const coursesList = ref([]);
const myCoursesList = ref([]);
const selectedCourse = ref(null);
const activeLesson = ref(null);
const userAnswers = ref({});
const quizResults = ref(null);

// Loading indicators
const coursesLoading = ref(false);
const courseLoading = ref(false);
const lessonLoading = ref(false);
const enrollingId = ref(null);
const submittingQuiz = ref(false);
const markingComplete = ref(false);

// Filter & Search states
const searchQuery = ref('');
const selectedCategory = ref('Tous');
const selectedLevel = ref('Tous');
const selectedFormat = ref('Tous');

// Accessibility & Gamification states
const showFalcMode = ref(false);
const showMobileSyllabus = ref(false);
const userStreak = ref(5);
const citizenXp = ref(420);
const masteryLevel = ref('Éclaireur Citoyen • Niveau 3');
const progressMessage = ref('');

// Syllable preview modal
const previewCourse = ref(null);

// Certificate modal state
const viewCertificate = ref(null);

// Audio language selector
const selectedAudioLanguage = ref('Français');

const categories = [
  'Tous',
  'Fact-Checking & Vérification',
  'Hygiène & Sécurité Numérique',
  'Éducation aux Médias',
  'Technologies & IA',
  'Journalisme & Enquête',
  'Droit & Citoyenneté'
];

const levels = ['Tous', 'Débutant', 'Intermédiaire', 'Avancé', 'Tous niveaux'];
const formatsList = [
  { label: 'Tous formats', value: 'Tous' },
  { label: '📄 Documents PDF', value: 'pdf' },
  { label: '🎥 Vidéos', value: 'video' },
  { label: '🎙️ Podcasts Vocaux', value: 'audio' },
  { label: '🖼️ Infographies', value: 'image' },
  { label: '📝 Articles & Textes', value: 'text' }
];

// Khan Academy mastery calculation
const courseMasteryPercentage = computed(() => {
  if (!selectedCourse.value?.chapters) return 0;
  const totalLessons = selectedCourse.value.chapters.reduce(
    (sum, ch) => sum + (ch.lessons?.length || 0), 0
  );
  if (totalLessons === 0) return 0;
  if (quizResults.value?.passed || selectedCourse.value?.user_enrollment?.certificate) return 100;
  const completedCount = selectedCourse.value.user_enrollment?.completed_lesson_ids?.length || 0;
  return Math.min(100, Math.round((completedCount / totalLessons) * 100));
});

// Filtered Coursera catalog
const filteredCourses = computed(() => {
  return coursesList.value.filter(course => {
    // Search query
    const query = searchQuery.value.toLowerCase().trim();
    const matchesSearch = !query || 
      course.title.toLowerCase().includes(query) ||
      course.description.toLowerCase().includes(query) ||
      (course.author?.name && course.author.name.toLowerCase().includes(query)) ||
      (course.category && course.category.toLowerCase().includes(query));

    // Category filter
    const matchesCat = selectedCategory.value === 'Tous' || course.category === selectedCategory.value;

    // Level filter
    const matchesLevel = selectedLevel.value === 'Tous' || course.level === selectedLevel.value;

    // Format filter
    const matchesFormat = selectedFormat.value === 'Tous' || (course.formats && course.formats.includes(selectedFormat.value));

    return matchesSearch && matchesCat && matchesLevel && matchesFormat;
  });
});

// My enrolled courses computed
const enrolledCourses = computed(() => {
  return coursesList.value.filter(c => c.is_enrolled || c.progress_percentage > 0 || c.has_certificate);
});

// Fetch all courses for Coursera catalog
const fetchCourses = async () => {
  coursesLoading.value = true;
  try {
    const headers = {};
    if (authStore.isAuthenticated) {
      headers['Authorization'] = `Bearer ${authStore.token}`;
      headers['Accept'] = 'application/json';
    }

    const response = await fetch('/api/lms/courses', { headers });
    if (response.ok) {
      coursesList.value = await response.json();
    }
  } catch (err) {
    console.error(err);
    toastStore.error('Impossible de charger le catalogue des formations.');
  } finally {
    coursesLoading.value = false;
  }
};

// Fetch personal enrolled courses
const fetchMyCourses = async () => {
  if (!authStore.isAuthenticated) return;
  try {
    const response = await fetch('/api/lms/my-courses', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    if (response.ok) {
      myCoursesList.value = await response.json();
    }
  } catch (err) {
    console.error(err);
  }
};

// Enroll in course (Coursera 1-click enroll)
const enrollInCourse = async (course) => {
  if (!authStore.isAuthenticated) {
    toastStore.warning('Veuillez vous connecter pour vous inscrire à cette formation.');
    return;
  }

  enrollingId.value = course.id;
  try {
    const response = await fetch(`/api/lms/courses/${course.id}/enroll`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });

    const data = await response.json();
    if (response.ok) {
      course.is_enrolled = true;
      course.progress_percentage = course.progress_percentage || 0;
      toastStore.success(data.message || `Inscription réussie au cours « ${course.title} » !`, 'Inscription Confirmée 🎓');
      
      // Auto open classroom for immediate start
      await selectCourse(course);
      activeView.value = 'classroom';
    } else {
      toastStore.error(data.message || 'Impossible de s\'inscrire à cette formation.');
    }
  } catch (err) {
    console.error(err);
    toastStore.error('Erreur réseau lors de l\'inscription.');
  } finally {
    enrollingId.value = null;
  }
};

// Select course and open classroom
const selectCourse = async (course) => {
  courseLoading.value = true;
  selectedCourse.value = null;
  activeLesson.value = null;
  quizResults.value = null;
  userAnswers.value = {};

  try {
    const headers = {};
    if (authStore.isAuthenticated) {
      headers['Authorization'] = `Bearer ${authStore.token}`;
      headers['Accept'] = 'application/json';
    }

    const response = await fetch(`/api/lms/courses/${course.id}`, { headers });
    if (response.ok) {
      selectedCourse.value = await response.json();
      activeView.value = 'classroom';
      
      // Auto-select first lesson of first chapter
      if (selectedCourse.value.chapters?.[0]?.lessons?.[0]) {
        await selectLesson(selectedCourse.value.chapters[0].lessons[0]);
      }
    }
  } catch (err) {
    console.error(err);
    toastStore.error('Erreur lors du chargement des modules de la formation.');
  } finally {
    courseLoading.value = false;
  }
};

// Select lesson in classroom
const selectLesson = async (lesson) => {
  if (!authStore.isAuthenticated) {
    toastStore.warning('Veuillez vous connecter pour suivre les leçons et enregistrer votre progression.');
    return;
  }

  lessonLoading.value = true;
  if (typeof window !== 'undefined' && window.innerWidth < 1024) {
    showMobileSyllabus.value = false;
  }
  try {
    const response = await fetch(`/api/lms/lessons/${lesson.id}`, {
      headers: { 
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    });
    if (response.ok) {
      activeLesson.value = await response.json();
      quizResults.value = null;
      userAnswers.value = {};
      progressMessage.value = '';
    }
  } catch (err) {
    console.error(err);
    toastStore.error('Impossible de charger le contenu de la leçon.');
  } finally {
    lessonLoading.value = false;
  }
};

// Mark lesson complete
const markLessonComplete = async () => {
  if (!activeLesson.value) return;
  markingComplete.value = true;
  progressMessage.value = '';

  try {
    const response = await fetch(`/api/lms/lessons/${activeLesson.value.id}/progress`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });
    const data = await response.json();
    if (response.ok) {
      citizenXp.value += 50;
      progressMessage.value = 'Leçon validée ! +50 XP Citoyen accordés.';
      toastStore.success('Leçon validée avec succès ! +50 XP Citoyen');
      
      // Update local completed ids
      if (!selectedCourse.value.user_enrollment) {
        selectedCourse.value.user_enrollment = { completed_lesson_ids: [] };
      }
      if (!selectedCourse.value.user_enrollment.completed_lesson_ids.includes(activeLesson.value.id)) {
        selectedCourse.value.user_enrollment.completed_lesson_ids.push(activeLesson.value.id);
      }

      if (data.course_completed) {
        progressMessage.value += ' Félicitations ! Certificat débloqué.';
        toastStore.success('Diplôme officiel VoiCI-EMI obtenu !', 'Félicitations 🎉');
        if (data.certificate) {
          viewCertificate.value = {
            recipient: authStore.user?.name || 'Apprenant Béninois',
            course: selectedCourse.value.title,
            number: data.certificate.certificate_number,
            date: new Date().toLocaleDateString('fr-FR'),
            trainer: selectedCourse.value.author?.name || 'Aïcha Bio Guéra'
          };
        }
      }

      // Refresh catalog list to update progress bars
      fetchCourses();
    }
  } catch (err) {
    console.error(err);
    toastStore.error('Erreur lors de l\'enregistrement de la progression.');
  } finally {
    markingComplete.value = false;
  }
};

// Submit Quiz
const submitQuizAnswers = async (quiz) => {
  if (!quiz?.questions?.length) return;
  submittingQuiz.value = true;
  quizResults.value = null;

  let correctCount = 0;
  const totalQuestions = quiz.questions.length;

  quiz.questions.forEach((question) => {
    const selectedAnswerId = userAnswers.value[question.id];
    const correctAnswer = question.answers.find(a => a.is_correct);
    if (correctAnswer && correctAnswer.id == selectedAnswerId) {
      correctCount++;
    }
  });

  const percent = Math.round((correctCount / totalQuestions) * 100);
  const passed = percent >= 75;

  try {
    const response = await fetch(`/api/lms/quizzes/${quiz.id}/submit`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        score: percent,
        passed: passed
      })
    });

    if (response.ok) {
      quizResults.value = {
        score: percent,
        correctCount,
        totalQuestions,
        passed
      };

      if (passed) {
        citizenXp.value += 100;
        toastStore.success(`Quiz réussi avec ${percent}% ! +100 XP Citoyen`, 'Maîtrise Validée 🎉');
      } else {
        toastStore.warning(`Score obtenu : ${percent}%. Seuil de passage : 75%. Vous pouvez retenter !`, 'À retenter');
      }
    }
  } catch (err) {
    console.error(err);
    toastStore.error('Erreur lors de la validation du quiz.');
  } finally {
    submittingQuiz.value = false;
  }
};

// Open Certificate
const openCertificateModal = (course) => {
  viewCertificate.value = {
    recipient: authStore.user?.name || 'Apprenant VoiCI-EMI',
    course: course.title,
    number: course.certificate_number || 'CERT-EMI-2026-' + Math.random().toString(36).substring(2, 9).toUpperCase(),
    date: new Date().toLocaleDateString('fr-FR'),
    trainer: course.author?.name || 'Aïcha Bio Guéra'
  };
};

// Helper for Category badge colors
const getCategoryColor = (category) => {
  switch (category) {
    case 'Fact-Checking & Vérification': return 'bg-emerald-50 text-emerald-800 border-emerald-200';
    case 'Hygiène & Sécurité Numérique': return 'bg-blue-50 text-blue-800 border-blue-200';
    case 'Éducation aux Médias': return 'bg-purple-50 text-purple-800 border-purple-200';
    case 'Technologies & IA': return 'bg-rose-50 text-rose-800 border-rose-200';
    case 'Journalisme & Enquête': return 'bg-amber-50 text-amber-800 border-amber-200';
    case 'Droit & Citoyenneté': return 'bg-indigo-50 text-indigo-800 border-indigo-200';
    default: return 'bg-slate-50 text-slate-800 border-slate-200';
  }
};

// Helper for format icons
const getFormatIcon = (format) => {
  switch (format) {
    case 'pdf': return '📄 PDF';
    case 'video': return '🎥 Vidéo';
    case 'audio': return '🎙️ Vocale';
    case 'image': return '🖼️ Image';
    case 'text': return '📝 Texte';
    default: return '📚 Cours';
  }
};

onMounted(() => {
  fetchCourses();
  fetchMyCourses();
});
</script>

<template>
  <div class="space-y-8 text-left max-w-7xl mx-auto px-4 sm:px-6">
    
    <!-- ======================================================== -->
    <!-- COURSERA TOP NAVIGATION BAR (Catalogue vs Mes Cours vs Salle) -->
    <!-- ======================================================== -->
    <div class="bg-white rounded-3xl border border-slate-100 p-4 sm:p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] flex flex-col md:flex-row md:items-center justify-between gap-4">
      
      <!-- Logo / Portal branding -->
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-serif text-xl shadow-sm">
          🎓
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">
              Plateforme Ouverte
            </span>
            <span class="text-xs text-slate-400 font-mono">Style Coursera Bénin</span>
          </div>
          <h1 class="font-serif font-black text-xl sm:text-2xl text-slate-950 tracking-tight">
            Académie d'Éducation aux Médias & Esprit Critique
          </h1>
        </div>
      </div>

      <!-- Mode Selector Tabs -->
      <div class="flex items-center gap-1.5 sm:gap-2 bg-slate-50 p-1 sm:p-1.5 rounded-2xl border border-slate-200/80 overflow-x-auto max-w-full no-scrollbar shrink-0">
        <button
          type="button"
          @click="activeView = 'catalog'"
          class="px-3.5 sm:px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
          :class="activeView === 'catalog' 
            ? 'bg-white text-slate-950 shadow-sm border border-slate-100' 
            : 'text-slate-600 hover:text-slate-950'"
        >
          <span>🌐</span>
          <span>Catalogue de Formations</span>
          <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-slate-100 text-slate-700">
            {{ coursesList.length }}
          </span>
        </button>

        <button
          type="button"
          @click="activeView = 'my_courses'"
          class="px-3.5 sm:px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
          :class="activeView === 'my_courses' 
            ? 'bg-white text-slate-950 shadow-sm border border-slate-100' 
            : 'text-slate-600 hover:text-slate-950'"
        >
          <span>📚</span>
          <span>Mes Formations</span>
          <span v-if="enrolledCourses.length > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-emerald-100 text-emerald-800 font-bold">
            {{ enrolledCourses.length }}
          </span>
        </button>

        <button
          v-if="selectedCourse"
          type="button"
          @click="activeView = 'classroom'"
          class="px-3.5 sm:px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 cursor-pointer whitespace-nowrap shrink-0"
          :class="activeView === 'classroom' 
            ? 'bg-white text-emerald-950 shadow-sm border border-emerald-200 font-extrabold' 
            : 'text-slate-600 hover:text-slate-950'"
        >
          <span>📖</span>
          <span>Salle de Cours Actuelle</span>
        </button>
      </div>

    </div>

    <!-- Trainer Quick Access Banner (Only for Formateur / Admins) -->
    <div 
      v-if="authStore.hasRole('Formateur') || authStore.hasRole('Super Administrateur') || authStore.hasRole('Administrateur')" 
      class="bg-purple-50/70 border border-purple-200/80 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
    >
      <div class="flex items-center gap-3.5">
        <span class="text-3xl p-2 rounded-xl bg-white border border-purple-200 shadow-xs">🛠️</span>
        <div>
          <div class="flex items-center gap-2">
            <h3 class="font-bold text-xs sm:text-sm text-purple-950">Studio Formateur VoiCI-EMI</h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-200 text-purple-900 font-mono">Habilité</span>
          </div>
          <p class="text-[11px] text-purple-800 font-light mt-0.5">
            Créez des cours modulaires (PDF, Vidéo, Images, Textes, Vocale), suivez la progression de chaque apprenant et délivrez les certifications officielles.
          </p>
        </div>
      </div>
      <router-link 
        :to="{ name: 'dashboard' }"
        class="px-4 py-2 rounded-xl bg-purple-900 text-white font-bold text-xs hover:bg-purple-800 transition shadow-xs inline-flex items-center gap-2 self-start sm:self-auto shrink-0"
      >
        <span>Ouvrir le Studio Formateur</span>
        <span>→</span>
      </router-link>
    </div>

    <!-- ========================================================================= -->
    <!-- VUE 1 : CATALOGUE DE FORMATIONS STYLE COURSERA                            -->
    <!-- ========================================================================= -->
    <div v-if="activeView === 'catalog'" class="space-y-8 animate-fade-in">
      
      <!-- COURSERA HERO BANNER & SEARCH -->
      <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-950 text-white p-5 sm:p-8 md:p-12 shadow-xl border border-slate-800">
        
        <div class="max-w-3xl space-y-4 relative z-10">
          <div class="inline-flex items-center gap-2 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-3.5 py-1.5 rounded-full text-xs font-semibold backdrop-blur-md">
            <span>🇧🇯</span>
            <span>Accréditation Républicaine • Cursus 100% Gratuit</span>
            <span>•</span>
            <span class="font-mono text-white">Certificat Inclus</span>
          </div>

          <h2 class="font-serif text-2xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight">
            Explorez toutes nos formations. Choisissez ce que vous voulez apprendre.
          </h2>

          <p class="text-xs sm:text-sm md:text-base text-slate-300 font-light leading-relaxed">
            Comme sur Coursera, choisissez librement parmi nos cursus certifiants conçus par des journalistes, juristes et experts béninois. Formats interactifs : documents PDF, vidéos explicatives, cours audio en langues nationales, infographies et défis de maîtrise.
          </p>

          <!-- Search Input Bar -->
          <div class="pt-2">
            <div class="relative max-w-xl">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher une formation, un formateur, un sujet (ex: WhatsApp, IA, Mobile Money)..."
                class="w-full bg-white/10 hover:bg-white/15 focus:bg-white text-white focus:text-slate-950 placeholder-slate-400 pl-11 pr-4 py-3.5 rounded-2xl border border-white/20 focus:border-emerald-400 focus:outline-none focus:ring-4 focus:ring-emerald-500/20 transition-all text-xs sm:text-sm font-medium backdrop-blur-md"
              />
              <span class="absolute left-4 top-3.5 text-base opacity-70">🔍</span>
              <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                class="absolute right-3.5 top-3 text-xs bg-white/20 hover:bg-white/30 text-white px-2 py-1 rounded-lg"
              >
                Effacer
              </button>
            </div>
          </div>
        </div>

        <!-- Decorative background shapes -->
        <div class="absolute -right-12 -bottom-12 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/4 -top-12 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
      </div>

      <!-- COURSERA FILTERS BAR -->
      <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-4">
        
        <!-- Category Pills -->
        <div class="space-y-2">
          <span class="text-xs font-mono font-bold uppercase tracking-wider text-slate-400 block">
            Thématiques & Domaines d'Apprentissage
          </span>
          <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 max-w-full sm:flex-wrap">
            <button
              v-for="cat in categories"
              :key="cat"
              type="button"
              @click="selectedCategory = cat"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all border cursor-pointer whitespace-nowrap shrink-0 sm:shrink"
              :class="selectedCategory === cat 
                ? 'bg-slate-900 text-white border-slate-900 shadow-xs' 
                : 'bg-slate-50 text-slate-700 border-slate-200/80 hover:bg-white hover:border-slate-300'"
            >
              {{ cat }}
            </button>
          </div>
        </div>

        <!-- Secondary Filters (Niveau & Format) -->
        <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-4">
            
            <!-- Format Filter -->
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold text-slate-500">Support :</span>
              <select
                v-model="selectedFormat"
                class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              >
                <option v-for="fmt in formatsList" :key="fmt.value" :value="fmt.value">
                  {{ fmt.label }}
                </option>
              </select>
            </div>

            <!-- Level Filter -->
            <div class="flex items-center gap-2">
              <span class="text-xs font-semibold text-slate-500">Niveau :</span>
              <select
                v-model="selectedLevel"
                class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"
              >
                <option v-for="lvl in levels" :key="lvl" :value="lvl">
                  {{ lvl }}
                </option>
              </select>
            </div>

          </div>

          <div class="text-xs font-mono text-slate-500">
            <span>{{ filteredCourses.length }} formation{{ filteredCourses.length > 1 ? 's' : '' }} trouvée{{ filteredCourses.length > 1 ? 's' : '' }}</span>
          </div>
        </div>

      </div>

      <!-- COURSES GRID (COURSERA CARDS) -->
      <div v-if="coursesLoading" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <SkeletonLoader v-for="i in 6" :key="i" variant="card" height="340px" />
      </div>

      <div v-else-if="filteredCourses.length > 0" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <div 
          v-for="course in filteredCourses" 
          :key="course.id"
          class="bg-white rounded-3xl border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.03)] hover:shadow-[0_12px_36px_rgba(0,0,0,0.08)] transition-all duration-300 flex flex-col justify-between overflow-hidden group border-t-4"
          :class="{
            'border-t-emerald-500': course.badge_color === 'emerald',
            'border-t-blue-500': course.badge_color === 'blue',
            'border-t-purple-500': course.badge_color === 'purple',
            'border-t-rose-500': course.badge_color === 'rose',
            'border-t-amber-500': course.badge_color === 'amber',
            'border-t-indigo-500': course.badge_color === 'indigo',
          }"
        >
          <!-- Card Body -->
          <div class="p-6 sm:p-7 space-y-4">
            
            <!-- Top Badges: Category & Certifiant -->
            <div class="flex items-center justify-between gap-2">
              <span 
                class="text-[11px] font-bold px-2.5 py-1 rounded-lg border font-sans truncate max-w-[200px]"
                :class="getCategoryColor(course.category)"
              >
                {{ course.icon }} {{ course.category }}
              </span>

              <span class="inline-flex items-center gap-1 text-[10px] font-bold font-mono px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200 shrink-0">
                <span>🏅</span>
                <span>Certifiant</span>
              </span>
            </div>

            <!-- Title & Description -->
            <div class="space-y-2">
              <h3 class="font-serif font-black text-lg sm:text-xl text-slate-950 group-hover:text-emerald-900 transition-colors leading-snug">
                {{ course.title }}
              </h3>
              <p class="text-xs text-slate-500 font-light line-clamp-3 leading-relaxed">
                {{ course.description }}
              </p>
            </div>

            <!-- Formateur Tag -->
            <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
              <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0">
                {{ course.author?.name ? course.author.name.substring(0, 2).toUpperCase() : 'FE' }}
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-900 truncate">
                  {{ course.author?.name || 'Aïcha Bio Guéra' }}
                </p>
                <p class="text-[10px] text-slate-400 truncate">Formatrice Accréditée VoiCI-EMI</p>
              </div>
            </div>

            <!-- Course Metadata Strip -->
            <div class="grid grid-cols-2 gap-2 pt-2 text-[11px] text-slate-600 font-medium">
              <div class="flex items-center gap-1.5">
                <span>⏱️</span>
                <span>{{ course.duration || '2h 30min' }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span>📊</span>
                <span>{{ course.level || 'Tous niveaux' }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span>📁</span>
                <span>{{ course.chapters_count || 1 }} module{{ (course.chapters_count || 1) > 1 ? 's' : '' }}</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span>📚</span>
                <span>{{ course.lessons_count || 2 }} leçon{{ (course.lessons_count || 2) > 1 ? 's' : '' }}</span>
              </div>
            </div>

            <!-- Multi-format pills -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1">
              <span 
                v-for="fmt in course.formats" 
                :key="fmt"
                class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-slate-100 text-slate-700 font-semibold"
              >
                {{ getFormatIcon(fmt) }}
              </span>
            </div>

            <!-- Progress bar if enrolled -->
            <div v-if="course.is_enrolled || course.progress_percentage > 0" class="pt-3 space-y-1.5">
              <div class="flex items-center justify-between text-xs font-bold">
                <span class="text-emerald-800">Votre progression</span>
                <span class="font-mono text-emerald-700">{{ course.progress_percentage }}%</span>
              </div>
              <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-emerald-500 rounded-full transition-all duration-500" 
                  :style="{ width: `${course.progress_percentage}%` }"
                ></div>
              </div>
            </div>

          </div>

          <!-- Card Actions Footer -->
          <div class="p-6 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between gap-3">
            
            <!-- CAS 1 : Déjà certifié -->
            <template v-if="course.has_certificate || course.progress_percentage === 100">
              <button
                type="button"
                @click="openCertificateModal(course)"
                class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs transition shadow-sm flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>🏆</span>
                <span>Voir mon Attestation</span>
              </button>
            </template>

            <!-- CAS 2 : Déjà inscrit, en cours d'apprentissage -->
            <template v-else-if="course.is_enrolled">
              <button
                type="button"
                @click="selectCourse(course)"
                class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shadow-sm flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>Continuer l'apprentissage</span>
                <span>→</span>
              </button>
            </template>

            <!-- CAS 3 : Non inscrit (Coursera 1-click enroll) -->
            <template v-else>
              <button
                type="button"
                @click="previewCourse = course"
                class="py-2.5 px-3 rounded-xl bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs transition border border-slate-200 cursor-pointer"
                title="Aperçu du syllabus"
              >
                Syllabus
              </button>

              <button
                type="button"
                @click="enrollInCourse(course)"
                :disabled="enrollingId === course.id"
                class="flex-1 py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-sm flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <span v-if="enrollingId === course.id" class="animate-spin text-sm">⏳</span>
                <span v-else>S'inscrire (Gratuit)</span>
                <span>→</span>
              </button>
            </template>

          </div>

        </div>

      </div>

      <EmptyState
        v-else
        title="Aucune formation correspondante"
        description="Aucune formation ne correspond à vos filtres de recherche. Essayez de réinitialiser la recherche ou de changer de thématique."
      />

    </div>

    <!-- ========================================================================= -->
    <!-- VUE 2 : MES FORMATIONS EN COURS & COMPLÉTÉES (LEARNER DASHBOARD)          -->
    <!-- ========================================================================= -->
    <div v-else-if="activeView === 'my_courses'" class="space-y-8 animate-fade-in">
      
      <div class="bg-white rounded-3xl border border-slate-100 p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
          <div class="space-y-1">
            <span class="text-xs font-mono font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">
              Espace Apprenant
            </span>
            <h2 class="font-serif font-black text-2xl text-slate-950">
              Mes Formations & Parcours Actifs
            </h2>
            <p class="text-xs text-slate-500 font-light">
              Suivez votre progression continue, reprenez vos cours en un clic et téléchargez vos diplômes certifiants.
            </p>
          </div>

          <button
            type="button"
            @click="activeView = 'catalog'"
            class="px-5 py-2.5 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 transition shadow-sm inline-flex items-center gap-2 self-start sm:self-auto cursor-pointer"
          >
            <span>+ Explorer d'autres cours</span>
          </button>
        </div>

        <!-- Metric badges -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
            <span class="text-xs text-slate-400 font-medium">Formations inscrites</span>
            <p class="font-serif font-black text-2xl text-slate-950">{{ enrolledCourses.length }}</p>
          </div>
          <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-100 space-y-1">
            <span class="text-xs text-emerald-800 font-medium">Certificats obtenus</span>
            <p class="font-serif font-black text-2xl text-emerald-900">
              {{ enrolledCourses.filter(c => c.has_certificate || c.progress_percentage === 100).length }}
            </p>
          </div>
          <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100 space-y-1">
            <span class="text-xs text-amber-800 font-medium">Points XP Citoyen</span>
            <p class="font-serif font-black text-2xl text-amber-900">{{ citizenXp }} XP</p>
          </div>
          <div class="p-4 rounded-2xl bg-purple-50 border border-purple-100 space-y-1">
            <span class="text-xs text-purple-800 font-medium">Série quotidienne</span>
            <p class="font-serif font-black text-2xl text-purple-900">🔥 {{ userStreak }} jours</p>
          </div>
        </div>

        <!-- List of enrolled courses -->
        <div v-if="enrolledCourses.length > 0" class="space-y-4 pt-4">
          <div 
            v-for="course in enrolledCourses" 
            :key="course.id"
            class="p-6 rounded-2xl border border-slate-100 bg-white shadow-xs hover:border-slate-300 transition flex flex-col md:flex-row md:items-center justify-between gap-6"
          >
            <div class="space-y-2 max-w-xl">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold px-2 py-0.5 rounded border" :class="getCategoryColor(course.category)">
                  {{ course.icon }} {{ course.category }}
                </span>
                <span v-if="course.has_certificate || course.progress_percentage === 100" class="text-xs font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                  🏆 Diplôme Obtenu
                </span>
                <span v-else class="text-xs font-bold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                  En cours
                </span>
              </div>

              <h3 class="font-serif font-bold text-lg text-slate-950">{{ course.title }}</h3>
              <p class="text-xs text-slate-500 font-light">{{ course.description }}</p>

              <!-- Progress bar -->
              <div class="space-y-1 pt-1">
                <div class="flex items-center justify-between text-xs font-semibold text-slate-600">
                  <span>Progression globale</span>
                  <span class="font-mono text-emerald-700">{{ course.progress_percentage }}%</span>
                </div>
                <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full" :style="{ width: `${course.progress_percentage}%` }"></div>
                </div>
              </div>
            </div>

            <div class="flex items-center gap-3 shrink-0">
              <button
                v-if="course.has_certificate || course.progress_percentage === 100"
                type="button"
                @click="openCertificateModal(course)"
                class="px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs transition shadow-sm flex items-center gap-2 cursor-pointer"
              >
                <span>🏆</span>
                <span>Certificat Officiel</span>
              </button>

              <button
                type="button"
                @click="selectCourse(course)"
                class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shadow-sm flex items-center gap-2 cursor-pointer"
              >
                <span>{{ course.progress_percentage === 100 ? 'Revoir le cours' : 'Continuer la formation' }}</span>
                <span>→</span>
              </button>
            </div>
          </div>
        </div>

        <div v-else class="p-12 text-center space-y-4">
          <span class="text-4xl">📚</span>
          <h3 class="font-serif font-bold text-lg text-slate-950">Vous n'êtes inscrit à aucune formation pour le moment</h3>
          <p class="text-xs text-slate-500 max-w-md mx-auto">
            Découvrez notre catalogue complet d'Éducation aux Médias et inscrivez-vous en 1 clic aux cours de votre choix.
          </p>
          <button
            @click="activeView = 'catalog'"
            class="px-5 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition cursor-pointer"
          >
            Explorer le Catalogue
          </button>
        </div>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- VUE 3 : SALLE DE COURS INTERACTIVE (LECTEUR MULTI-FORMATS & QUIZ)         -->
    <!-- ========================================================================= -->
    <div v-else-if="activeView === 'classroom'" class="space-y-8 animate-fade-in">
      
      <!-- Back to Catalog Header Strip -->
      <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <button
          type="button"
          @click="activeView = 'catalog'"
          class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-900 font-bold text-xs transition flex items-center gap-2 cursor-pointer"
        >
          <span>←</span>
          <span>Retourner au Catalogue des Formations</span>
        </button>

        <div class="flex items-center gap-3">
          <span class="text-xs text-slate-500 font-medium">Changer de cours :</span>
          <select
            v-if="coursesList.length > 0"
            :value="selectedCourse?.id"
            @change="selectCourse(coursesList.find(c => c.id == $event.target.value))"
            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 max-w-xs truncate"
          >
            <option v-for="c in coursesList" :key="c.id" :value="c.id">
              {{ c.title }}
            </option>
          </select>
        </div>
      </div>

      <!-- Course Studio Header (Khan Academy Style) -->
      <div class="bg-white rounded-3xl border border-slate-100 p-6 sm:p-8 shadow-[0_4px_24px_rgba(0,0,0,0.03)] flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        
        <div class="space-y-2 max-w-xl">
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 bg-emerald-50 text-emerald-800 border border-emerald-200 px-3 py-1 rounded-full text-xs font-semibold">
              <span>📚 Formation Accréditée</span>
              <span class="text-emerald-400">•</span>
              <span>{{ selectedCourse?.category || 'Éducation aux Médias' }}</span>
            </span>
            <span class="text-xs text-slate-400 font-mono">Diplôme Officiel</span>
          </div>
          
          <h2 class="font-serif text-2xl sm:text-3xl font-black text-slate-950 tracking-tight">
            {{ selectedCourse?.title || 'Formation VoiCI-EMI' }}
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-light">
            {{ selectedCourse?.description }}
          </p>
        </div>

        <!-- Duolingo Streak, XP & Mastery Badges Widget -->
        <div class="flex flex-wrap items-center gap-3 sm:gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
          
          <!-- Streak -->
          <div class="flex items-center gap-2 bg-white px-3.5 py-2 rounded-xl border border-slate-100 shadow-xs">
            <span class="text-xl">🔥</span>
            <div class="text-left leading-tight">
              <p class="text-xs font-bold text-slate-900">{{ userStreak }} jours</p>
              <p class="text-[10px] text-amber-700 font-semibold uppercase tracking-wider">Série active</p>
            </div>
          </div>

          <!-- Citizen XP -->
          <div class="flex items-center gap-2 bg-white px-3.5 py-2 rounded-xl border border-slate-100 shadow-xs">
            <span class="text-xl">⚡</span>
            <div class="text-left leading-tight">
              <p class="text-xs font-bold text-slate-900">{{ citizenXp }} XP</p>
              <p class="text-[10px] text-emerald-700 font-semibold uppercase tracking-wider">Points Citoyen</p>
            </div>
          </div>

          <!-- Mastery Badge -->
          <div class="flex items-center gap-2 bg-white px-3.5 py-2 rounded-xl border border-slate-100 shadow-xs">
            <span class="text-xl">🛡️</span>
            <div class="text-left leading-tight">
              <p class="text-xs font-bold text-slate-900 truncate max-w-[130px]">{{ masteryLevel }}</p>
              <p class="text-[10px] text-purple-700 font-semibold uppercase tracking-wider">Rang validé</p>
            </div>
          </div>

        </div>
      </div>

      <!-- MAIN WORKSPACE: SIDEBAR CURRICULUM + LESSON VIEWPORT -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
        
        <!-- Mobile syllabus toggle bar -->
        <div class="lg:hidden col-span-1">
          <button
            type="button"
            @click="showMobileSyllabus = !showMobileSyllabus"
            class="w-full py-3 px-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center justify-between text-xs font-bold text-slate-800 hover:bg-slate-50 transition cursor-pointer"
          >
            <div class="flex items-center gap-2">
              <span>📋</span>
              <span>Sommaire du cours ({{ selectedCourse?.chapters?.length || 0 }} modules)</span>
            </div>
            <span class="text-xs font-mono text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
              {{ showMobileSyllabus ? '▲ Masquer' : '▼ Voir les leçons' }}
            </span>
          </button>
        </div>

        <!-- LEFT: SYLLABUS UNIT TREE & MODULES (4 cols on desktop, toggleable on mobile) -->
        <aside 
          class="lg:col-span-4 space-y-6"
          :class="showMobileSyllabus ? 'block' : 'hidden lg:block'"
        >
          
          <!-- DUOLINGO-STYLE PROGRESSION PATH -->
          <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6">
            
            <div class="space-y-1.5 border-b border-slate-100 pb-4">
              <div class="flex items-center justify-between text-xs font-bold text-slate-600">
                <span>Maîtrise de la formation</span>
                <span class="font-mono text-emerald-700 font-bold">{{ courseMasteryPercentage }}%</span>
              </div>
              <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                <div 
                  class="bg-emerald-500 h-full rounded-full transition-all duration-500"
                  :style="{ width: `${courseMasteryPercentage}%` }"
                ></div>
              </div>
            </div>

            <!-- Stepped Modules & Lessons -->
            <div v-if="selectedCourse?.chapters && selectedCourse.chapters.length > 0" class="space-y-6">
              
              <div 
                v-for="(chapter, chIdx) in selectedCourse.chapters" 
                :key="chapter.id"
                class="space-y-3"
              >
                <!-- Chapter Milestone Title -->
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                    0{{ chIdx + 1 }}
                  </div>
                  <div class="text-left">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Module {{ chIdx + 1 }}</p>
                    <h3 class="font-serif font-bold text-xs text-slate-900 leading-tight">{{ chapter.title }}</h3>
                  </div>
                </div>

                <!-- Lessons Nodes -->
                <div class="space-y-2 pl-5 border-l-2 border-dashed border-slate-200 ml-5">
                  <button
                    v-for="(lesson, lIdx) in chapter.lessons"
                    :key="lesson.id"
                    @click="selectLesson(lesson)"
                    class="w-full text-left p-3 rounded-2xl border transition-all duration-200 flex items-center gap-3 group focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer"
                    :class="activeLesson?.id === lesson.id 
                      ? 'bg-emerald-50/90 border-emerald-400 text-emerald-950 font-bold shadow-xs' 
                      : 'bg-white border-slate-100 text-slate-800 hover:border-emerald-300 hover:bg-slate-50'"
                  >
                    <!-- Node Status Icon -->
                    <div 
                      class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-transform group-hover:scale-105"
                      :class="activeLesson?.id === lesson.id 
                        ? 'bg-emerald-600 text-white shadow-xs' 
                        : (selectedCourse?.user_enrollment?.completed_lesson_ids?.includes(lesson.id) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500')"
                    >
                      <span v-if="selectedCourse?.user_enrollment?.completed_lesson_ids?.includes(lesson.id)">✓</span>
                      <span v-else-if="activeLesson?.id === lesson.id">▶</span>
                      <span v-else>{{ lIdx + 1 }}</span>
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="text-xs font-serif font-semibold truncate">{{ lesson.title }}</p>
                      <div class="flex items-center gap-2 mt-0.5 text-[10px] text-slate-400 font-mono">
                        <span class="uppercase font-bold">{{ getFormatIcon(lesson.content_type) }}</span>
                        <span>•</span>
                        <span>+50 XP</span>
                      </div>
                    </div>

                    <span 
                      class="text-xs shrink-0" 
                      :class="activeLesson?.id === lesson.id ? 'text-emerald-700 font-bold' : 'text-slate-300'"
                    >
                      {{ activeLesson?.id === lesson.id ? 'En cours' : '→' }}
                    </span>
                  </button>
                </div>

              </div>

            </div>

            <div v-else class="text-xs text-slate-400 italic text-center py-4">
              Ce cours ne contient aucun module pour l'instant.
            </div>

          </div>

        </aside>

        <!-- RIGHT: LESSON VIEWPORT & QUIZ ENGINE (8 cols) -->
        <main class="lg:col-span-8 space-y-6">
          
          <!-- Loading state -->
          <div v-if="courseLoading || lessonLoading" class="bg-white rounded-3xl p-8 border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6">
            <SkeletonLoader variant="text" :count="2" />
            <SkeletonLoader variant="card" height="280px" />
          </div>

          <!-- Active Lesson Container -->
          <div v-else-if="activeLesson" class="space-y-6">
            
            <!-- Lesson Main Card -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6">
              
              <!-- Breadcrumbs & Accessibility Bar -->
              <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2 text-xs text-slate-400 font-mono">
                  <span>{{ selectedCourse?.title }}</span>
                  <span>/</span>
                  <span class="text-slate-900 font-bold">{{ activeLesson.title }}</span>
                </div>

                <!-- Media pill and FALC toggle button -->
                <div class="flex items-center gap-2">
                  <span class="px-2.5 py-1 rounded-lg text-xs font-bold font-mono bg-slate-100 text-slate-800 border border-slate-200">
                    Format : {{ activeLesson.content_type.toUpperCase() }}
                  </span>

                  <!-- FALC Mode Toggle -->
                  <button
                    type="button"
                    @click="showFalcMode = !showFalcMode"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all flex items-center gap-1.5 focus:outline-none focus:ring-2 focus:ring-amber-500 cursor-pointer"
                    :class="showFalcMode 
                      ? 'bg-amber-400 text-slate-950 border-amber-500 font-bold shadow-xs' 
                      : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-white'"
                  >
                    <span>♿</span>
                    <span>{{ showFalcMode ? 'Mode complet' : 'Version simplifiée (FALC)' }}</span>
                  </button>
                </div>
              </div>

              <!-- Lesson Headline -->
              <div class="space-y-2">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200 inline-block font-mono">
                  Leçon Pédagogique VoiCI-EMI
                </span>
                <h2 class="font-serif text-2xl sm:text-3xl font-black text-slate-950 leading-snug">
                  {{ activeLesson.title }}
                </h2>
              </div>

              <!-- ======================================================== -->
              <!-- MULTI-FORMAT MEDIA RENDERING                             -->
              <!-- ======================================================== -->

              <!-- 1. VIDEO PLAYER -->
              <div v-if="activeLesson.content_type === 'video'" class="space-y-2">
                <div class="aspect-video bg-slate-950 rounded-2xl flex flex-col items-center justify-center text-white p-6 border border-slate-800 shadow-md">
                  <span class="text-4xl mb-2">🎥</span>
                  <p class="text-xs font-semibold text-slate-200">Lecteur Vidéo Pédagogique VoiCI-EMI</p>
                  <p class="text-[11px] text-slate-400 mt-1 font-mono truncate max-w-sm">{{ activeLesson.video_url || 'Support vidéo interactif' }}</p>
                </div>
              </div>

              <!-- 2. AUDIO PLAYER WITH BENINESE LANGUAGES -->
              <div v-if="activeLesson.content_type === 'audio'" class="bg-purple-50/70 rounded-2xl p-6 border border-purple-200 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-900 flex items-center justify-center font-bold text-xl">
                      🎙️
                    </div>
                    <div>
                      <p class="text-sm font-bold text-purple-950">Chronique Vocale & Podcast Pédagogique</p>
                      <p class="text-xs text-purple-700">Accessible hors-ligne • Explications en langues nationales</p>
                    </div>
                  </div>

                  <!-- Language selector -->
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-purple-900">Langue :</span>
                    <select
                      v-model="selectedAudioLanguage"
                      class="bg-white border border-purple-200 rounded-xl px-3 py-1.5 text-xs font-bold text-purple-950 focus:outline-none"
                    >
                      <option value="Français">Français (Officiel)</option>
                      <option value="Fɔngbe">Fɔngbe (Bénin Sud & Centre)</option>
                      <option value="Yorùbá">Yorùbá (Bénin Est)</option>
                      <option value="Baatɔnum">Baatɔnum (Bénin Nord)</option>
                    </select>
                  </div>
                </div>

                <div class="bg-white rounded-xl p-3 border border-purple-100 flex items-center gap-3">
                  <button class="w-10 h-10 rounded-full bg-purple-900 text-white flex items-center justify-center text-xs font-bold">
                    ▶
                  </button>
                  <div class="flex-1">
                    <div class="h-2 w-full bg-purple-100 rounded-full overflow-hidden">
                      <div class="h-full bg-purple-600 rounded-full w-1/3"></div>
                    </div>
                    <div class="flex items-center justify-between text-[10px] text-purple-600 font-mono mt-1">
                      <span>01:15</span>
                      <span>03:45 • Version {{ selectedAudioLanguage }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 3. PDF DOCUMENT CARD -->
              <div v-if="activeLesson.content_type === 'pdf'" class="bg-slate-50 border border-slate-200 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                  <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xl shrink-0">
                    📄
                  </div>
                  <div class="space-y-0.5">
                    <h4 class="font-bold text-sm text-slate-900">Support de Cours & Fiche PDF Officielle</h4>
                    <p class="text-xs text-slate-500 font-mono">Guide pratique téléchargeable pour consultation hors-ligne</p>
                  </div>
                </div>
                <button 
                  @click="toastStore.success('Téléchargement du support PDF en cours...', 'Fiche Pratique PDF')"
                  class="px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800 transition inline-flex items-center gap-2 shrink-0 shadow-xs cursor-pointer"
                >
                  <span>Télécharger le PDF</span>
                  <span>⬇️</span>
                </button>
              </div>

              <!-- 4. INFOGRAPHIC / IMAGE -->
              <div v-if="activeLesson.content_type === 'image'" class="space-y-3">
                <div class="rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 p-2">
                  <img 
                    :src="activeLesson.video_url || 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1200&q=80'" 
                    alt="Infographie pédagogique"
                    class="w-full max-h-96 object-contain rounded-xl"
                  />
                </div>
                <p class="text-xs text-slate-500 italic text-center font-serif">Infographie et schéma comparatif d'analyse critique</p>
              </div>

              <!-- 5. TEXT CONTENT: STANDARD VS FALC -->
              <div class="space-y-4">
                <!-- FALC Mode Box -->
                <div 
                  v-if="showFalcMode"
                  class="bg-amber-50/80 border-2 border-amber-300 rounded-2xl p-6 sm:p-8 space-y-4"
                >
                  <div class="flex items-center gap-2 text-amber-950 font-serif font-bold text-sm">
                    <span class="text-lg">♿</span>
                    <span>Version Facile à Lire et à Comprendre (Norme FALC)</span>
                  </div>
                  <div class="text-sm sm:text-base text-slate-900 leading-loose whitespace-pre-wrap font-medium">
                    {{ activeLesson.content_simplified || 'Version FALC simplifiée pour accessibilité universelle.' }}
                  </div>
                </div>

                <!-- Standard Lesson Text -->
                <div 
                  v-else
                  class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-100 text-sm sm:text-base text-slate-800 leading-relaxed whitespace-pre-wrap font-normal"
                >
                  {{ activeLesson.content }}
                </div>
              </div>

              <!-- Transcription WCAG AA -->
              <div v-if="activeLesson.transcription" class="space-y-2 pt-4 border-t border-slate-100">
                <h4 class="font-serif font-bold text-xs uppercase tracking-wider text-slate-400">
                  Transcription textuelle complète (Conformité WCAG AA)
                </h4>
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs text-slate-600 italic leading-relaxed">
                  {{ activeLesson.transcription }}
                </div>
              </div>

              <!-- Action Bar: Mark Complete -->
              <div class="pt-6 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                <div v-if="progressMessage" class="text-xs font-semibold text-emerald-800 bg-emerald-50 px-3.5 py-2 rounded-xl border border-emerald-200 flex items-center gap-2">
                  <span>✓</span>
                  <span>{{ progressMessage }}</span>
                </div>
                <div v-else class="text-xs text-slate-400 flex items-center gap-1.5">
                  <span>💡</span>
                  <span>Validez chaque leçon pour accumuler vos points XP Citoyen et débloquer votre attestation.</span>
                </div>

                <button 
                  type="button"
                  :disabled="markingComplete"
                  @click="markLessonComplete"
                  class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-sm inline-flex items-center gap-2 cursor-pointer disabled:opacity-50"
                >
                  <span v-if="markingComplete" class="animate-spin text-sm">⏳</span>
                  <span>✓ Valider cette leçon (+50 XP)</span>
                </button>
              </div>

            </div>

            <!-- ======================================================= -->
            <!-- DUOLINGO-STYLE INTERACTIVE QUIZ & MASTERY CHECKPOINT   -->
            <!-- ======================================================= -->
            <div v-if="activeLesson.quizzes && activeLesson.quizzes.length > 0" class="space-y-6">
              
              <div 
                v-for="quiz in activeLesson.quizzes" 
                :key="quiz.id" 
                class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-100 shadow-[0_4px_24px_rgba(0,0,0,0.03)] space-y-6"
              >
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="text-xl">⭐</span>
                      <span class="text-xs font-bold uppercase tracking-wider text-amber-700">Défi de Maîtrise</span>
                    </div>
                    <h3 class="font-serif text-xl sm:text-2xl font-black text-slate-950">
                      {{ quiz.title }}
                    </h3>
                    <p class="text-xs text-slate-500 font-light">
                      Répondez correctement aux questions pour valider ce palier d'apprentissage.
                    </p>
                  </div>

                  <div class="text-right">
                    <span class="text-xs font-bold text-slate-900 bg-slate-100 px-3 py-1 rounded-xl border border-slate-200">
                      Seuil de réussite : 75%
                    </span>
                  </div>
                </div>

                <!-- Questions -->
                <div 
                  v-for="(question, qIndex) in quiz.questions" 
                  :key="question.id" 
                  class="bg-slate-50 p-5 sm:p-6 rounded-2xl border border-slate-100 space-y-4"
                >
                  <div class="flex items-start gap-3">
                    <span class="w-6 h-6 rounded-full bg-slate-900 text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">
                      {{ qIndex + 1 }}
                    </span>
                    <p class="text-sm font-serif font-bold text-slate-950 leading-snug">
                      {{ question.text }}
                    </p>
                  </div>

                  <!-- Answer Options -->
                  <div class="space-y-2.5 pl-9">
                    <label 
                      v-for="answer in question.answers" 
                      :key="answer.id" 
                      class="p-3.5 rounded-xl border text-xs sm:text-sm font-medium transition-all flex items-center gap-3 cursor-pointer select-none"
                      :class="userAnswers[question.id] === answer.id 
                        ? 'bg-emerald-50 border-emerald-500 text-emerald-950 font-bold shadow-xs ring-2 ring-emerald-300/60' 
                        : 'bg-white border-slate-200 text-slate-800 hover:bg-slate-100'"
                    >
                      <input 
                        type="radio" 
                        :name="'question-' + question.id" 
                        v-model="userAnswers[question.id]" 
                        :value="answer.id" 
                        class="text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer"
                      />
                      <span class="flex-1">{{ answer.text }}</span>
                    </label>
                  </div>
                </div>

                <!-- Quiz Actions & Result Card -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <button
                    type="button"
                    :disabled="submittingQuiz"
                    @click="submitQuizAnswers(quiz)"
                    class="px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shadow-sm inline-flex items-center gap-2 cursor-pointer disabled:opacity-50"
                  >
                    <span v-if="submittingQuiz" class="animate-spin text-sm">⏳</span>
                    <span>Vérifier mes réponses</span>
                    <span aria-hidden="true">→</span>
                  </button>

                  <div 
                    v-if="quizResults" 
                    class="p-4 rounded-2xl border text-xs sm:text-sm flex items-center gap-3 shadow-xs"
                    :class="quizResults.passed 
                      ? 'bg-emerald-50 text-emerald-950 border-emerald-300' 
                      : 'bg-rose-50 text-rose-950 border-rose-300'"
                  >
                    <span class="text-2xl">{{ quizResults.passed ? '🎉' : '🔄' }}</span>
                    <div class="text-left">
                      <p class="font-bold">
                        Score : {{ quizResults.score }}% ({{ quizResults.correctCount }}/{{ quizResults.totalQuestions }})
                      </p>
                      <p class="text-xs opacity-90 mt-0.5">
                        {{ quizResults.passed 
                          ? 'Félicitations ! Vous avez validé les objectifs de ce palier (+100 XP Citoyen).' 
                          : 'Score en dessous du seuil de 75%. Relisez la leçon et tentez à nouveau le quiz.' }}
                      </p>
                    </div>
                  </div>
                </div>

              </div>
            </div>

          </div>

          <EmptyState
            v-else
            title="Sélectionnez une leçon"
            description="Cliquez sur l'une des leçons dans la colonne de gauche pour débuter votre parcours d'apprentissage."
          />

        </main>

      </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL D'APERÇU DU SYLLABUS COURS                                          -->
    <!-- ========================================================================= -->
    <div 
      v-if="previewCourse" 
      class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
      role="dialog"
      aria-modal="true"
    >
      <div class="bg-white rounded-3xl max-w-xl w-full p-8 shadow-2xl space-y-6 border border-slate-100 max-h-[85vh] overflow-y-auto">
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
          <div class="space-y-1">
            <span class="text-xs font-bold px-2 py-0.5 rounded border" :class="getCategoryColor(previewCourse.category)">
              {{ previewCourse.icon }} {{ previewCourse.category }}
            </span>
            <h3 class="font-serif font-black text-xl text-slate-950 leading-snug">
              {{ previewCourse.title }}
            </h3>
            <p class="text-xs text-slate-500 font-light">
              Durée : {{ previewCourse.duration }} • Niveau : {{ previewCourse.level }} • Formateur : {{ previewCourse.author?.name || 'Aïcha Bio Guéra' }}
            </p>
          </div>
          <button 
            @click="previewCourse = null" 
            class="text-slate-400 hover:text-slate-800 text-lg font-bold p-1 cursor-pointer"
          >
            ✕
          </button>
        </div>

        <div class="space-y-3">
          <h4 class="font-bold text-xs uppercase tracking-wider text-slate-400 font-mono">Programme & Modules</h4>
          <p class="text-xs text-slate-600 leading-relaxed font-light">
            {{ previewCourse.description }}
          </p>

          <div class="space-y-2 pt-2">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
              <span class="font-semibold text-slate-800">Support pédagogiques inclus</span>
              <div class="flex items-center gap-1">
                <span v-for="fmt in previewCourse.formats" :key="fmt" class="px-2 py-0.5 rounded bg-white text-[10px] font-mono border">
                  {{ getFormatIcon(fmt) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button 
            @click="previewCourse = null" 
            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 cursor-pointer"
          >
            Fermer
          </button>
          <button 
            @click="enrollInCourse(previewCourse); previewCourse = null" 
            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-sm cursor-pointer"
          >
            S'inscrire immédiatement
          </button>
        </div>
      </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL DU CERTIFICAT OFFICIEL VoiCI-EMI (STYLE COURSERA / DIPLÔME RÉPUBLIQUE)-->
    <!-- ========================================================================= -->
    <div 
      v-if="viewCertificate" 
      class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
      role="dialog"
      aria-modal="true"
    >
      <div class="bg-white rounded-3xl max-w-2xl w-full p-8 sm:p-10 shadow-2xl space-y-6 border border-slate-100 relative">
        
        <!-- Header Certificate -->
        <div class="text-center space-y-1.5 border-b border-slate-100 pb-6">
          <p class="text-[10px] font-mono uppercase font-bold tracking-widest text-slate-400">
            RÉPUBLIQUE DU BÉNIN • MINISTÈRE DU NUMÉRIQUE ET DE LA DIGITALISATION
          </p>
          <div class="flex items-center justify-center gap-2 text-2xl pt-1">
            <span>🇧🇯</span>
            <span class="font-serif font-black text-xl text-slate-950">Académie VoiCI-EMI Bénin</span>
            <span>🇧🇯</span>
          </div>
          <h2 class="font-serif font-black text-lg sm:text-xl text-emerald-950 uppercase tracking-wide pt-2">
            Attestation Officielle de Réussite & d'Accréditation Citoyenne
          </h2>
        </div>

        <!-- Body Certificate -->
        <div class="text-center space-y-4 py-2">
          <p class="text-xs text-slate-500 font-light">Le conseil pédagogique et le collège des formateurs certifient que :</p>
          <h3 class="font-serif font-black text-2xl sm:text-3xl text-emerald-900 tracking-tight">
            {{ viewCertificate.recipient }}
          </h3>
          <p class="text-xs text-slate-600 max-w-md mx-auto leading-relaxed">
            a validé avec succès l'ensemble des modules d'évaluation théorique et pratique de la formation certifiante :
          </p>
          <div class="p-4 rounded-2xl bg-emerald-50 text-emerald-950 font-serif font-bold text-sm sm:text-base border border-emerald-200">
            « {{ viewCertificate.course }} »
          </div>
        </div>

        <!-- Verification & Signature Footer -->
        <div class="pt-6 border-t border-slate-100 grid grid-cols-2 gap-4 text-xs">
          <div class="space-y-1">
            <span class="text-[10px] font-mono text-slate-400 uppercase block">Numéro d'Attestation</span>
            <code class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded text-[11px] block select-all">
              {{ viewCertificate.number }}
            </code>
            <span class="text-[10px] text-slate-400 block pt-1">Délivré le {{ viewCertificate.date }}</span>
          </div>

          <div class="space-y-1 text-right">
            <span class="text-[10px] font-mono text-slate-400 uppercase block">Formateur Accrédité</span>
            <span class="font-bold text-slate-900 block">{{ viewCertificate.trainer }}</span>
            <span class="text-[10px] text-emerald-700 font-mono block">Signature Numérique Certifiée ✓</span>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2 text-xs text-slate-400 font-mono">
            <span>🛡️ Vérification cryptographique active</span>
          </div>
          <div class="flex items-center gap-3">
            <button 
              @click="viewCertificate = null"
              class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer"
            >
              Fermer
            </button>
            <button 
              @click="toastStore.success('Téléchargement du certificat officiel au format PDF haute définition...', 'Certificat Téléchargé 🎓')"
              class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition shadow-sm inline-flex items-center gap-2 cursor-pointer"
            >
              <span>Télécharger le Diplôme (PDF)</span>
              <span>⬇️</span>
            </button>
          </div>
        </div>

      </div>
    </div>

  </div>
</template>
