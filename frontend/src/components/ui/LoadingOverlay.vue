<script setup>
import { useLoadingStore } from '../../stores/loading';

const loadingStore = useLoadingStore();
</script>

<template>
  <div>
    <!-- =================================================================== -->
    <!-- 1. TOP PROGRESS BAR (FOR SMOOTH PAGE NAVIGATION)                    -->
    <!-- =================================================================== -->
    <transition name="fade">
      <div 
        v-if="loadingStore.isPageLoading" 
        class="fixed top-0 left-0 right-0 z-[100] h-1 bg-slate-100 overflow-hidden"
        role="progressbar"
        aria-label="Chargement de la page"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        <div class="h-full bg-gradient-to-r from-amber-500 via-emerald-500 to-navy-600 animate-page-bar"></div>
      </div>
    </transition>

    <!-- =================================================================== -->
    <!-- 2. CHIC MINIMAL PAGE LOADING OVERLAY (DURING ROUTE CHANGE)         -->
    <!-- =================================================================== -->
    <transition name="fade">
      <div 
        v-if="loadingStore.isPageLoading" 
        class="fixed inset-0 z-[90] bg-white/70 backdrop-blur-[2px] flex items-center justify-center pointer-events-none"
      >
        <div class="bg-white rounded-2xl shadow-[0_8px_30px_rgba(0,0,0,0.06)] px-6 py-4 flex items-center gap-3.5 border border-slate-100 animate-scale-up">
          <!-- Animated Double Ring Spinner -->
          <div class="relative w-7 h-7 flex items-center justify-center shrink-0">
            <div class="absolute inset-0 rounded-full border-2 border-slate-100"></div>
            <div class="absolute inset-0 rounded-full border-2 border-amber-500 border-t-transparent animate-spin"></div>
            <span class="font-serif font-black text-xs text-navy-700">V</span>
          </div>

          <div class="text-left">
            <p class="text-xs font-semibold text-slate-800">
              {{ loadingStore.pageTitle }}
            </p>
            <p class="text-[10px] text-slate-400">
              VoiCI-EMI • Plateforme Officielle
            </p>
          </div>
        </div>
      </div>
    </transition>

    <!-- =================================================================== -->
    <!-- 3. IMMERSIVE CHIC AUTH TRANSITION SCREEN (LOGIN & REGISTER)         -->
    <!-- =================================================================== -->
    <transition name="fade">
      <div 
        v-if="loadingStore.isAuthTransition" 
        class="fixed inset-0 z-[150] bg-white/95 backdrop-blur-md flex items-center justify-center p-4 animate-fade-in"
        role="dialog"
        aria-modal="true"
        aria-label="Transition de connexion"
      >
        <!-- Pure white luxury card with smooth soft shadow -->
        <div class="max-w-md w-full bg-white rounded-3xl p-8 sm:p-10 text-center shadow-[0_20px_60px_rgba(0,0,0,0.06)] border border-slate-100 relative overflow-hidden space-y-6 animate-scale-up">
          
          <!-- Subtle top accent glow -->
          <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-48 h-32 bg-amber-400/10 rounded-full blur-2xl pointer-events-none"></div>

          <!-- Central Animated Icon Container -->
          <div class="relative w-20 h-20 mx-auto flex items-center justify-center">
            
            <!-- Pulsing outer ring -->
            <div 
              class="absolute inset-0 rounded-3xl transition-all duration-500"
              :class="loadingStore.authStatus === 'success' 
                ? 'bg-emerald-50 ring-4 ring-emerald-100' 
                : 'bg-amber-50 ring-4 ring-amber-100 animate-pulse'"
            ></div>

            <!-- Spinning border during loading -->
            <div 
              v-if="loadingStore.authStatus === 'loading'" 
              class="absolute -inset-1 rounded-3xl border-2 border-dashed border-amber-500 animate-spin-slow opacity-60"
            ></div>

            <!-- Icon: Checkmark or Monogram -->
            <div class="relative z-10">
              <svg 
                v-if="loadingStore.authStatus === 'success'" 
                class="w-10 h-10 text-emerald-600 animate-check-bounce" 
                fill="none" 
                viewBox="0 0 24 24" 
                stroke="currentColor" 
                stroke-width="2.5"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>

              <div 
                v-else 
                class="w-12 h-12 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-serif font-black text-xl shadow-sm"
              >
                V
              </div>
            </div>
          </div>

          <!-- Dynamic Status Text -->
          <div class="space-y-1.5 text-center">
            <h3 class="font-serif font-black text-xl sm:text-2xl text-slate-900 tracking-tight transition-all duration-300">
              {{ loadingStore.authTitle }}
            </h3>
            <p class="text-xs sm:text-sm text-slate-500 font-light leading-relaxed transition-all duration-300">
              {{ loadingStore.authSubtitle }}
            </p>
          </div>

          <!-- Chic Progress Bar with Percentage -->
          <div class="space-y-2 pt-2">
            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden p-0.5">
              <div 
                class="h-full rounded-full transition-all duration-300 ease-out"
                :class="loadingStore.authStatus === 'success' 
                  ? 'bg-emerald-500' 
                  : 'bg-gradient-to-r from-amber-500 via-emerald-500 to-navy-600'"
                :style="{ width: `${loadingStore.authProgress}%` }"
              ></div>
            </div>
            <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium px-1">
              <span>Sécurité VoiCI-EMI</span>
              <span class="font-mono text-slate-600 font-semibold">{{ loadingStore.authProgress }}%</span>
            </div>
          </div>

          <!-- Reassurance Footer -->
          <div class="pt-4 border-t border-slate-100 flex items-center justify-center gap-2 text-[11px] text-slate-400">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd" />
            </svg>
            <span>Connexion chiffrée SSL / 256-bit</span>
          </div>

        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes pageBar {
  0% {
    transform: translateX(-100%);
  }
  50% {
    transform: translateX(0%);
  }
  100% {
    transform: translateX(100%);
  }
}

.animate-page-bar {
  animation: pageBar 1.2s infinite ease-in-out;
}

@keyframes spinSlow {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin-slow {
  animation: spinSlow 8s linear infinite;
}

@keyframes scaleUp {
  0% {
    opacity: 0;
    transform: scale(0.95);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-scale-up {
  animation: scaleUp 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes checkBounce {
  0% {
    transform: scale(0.5);
    opacity: 0;
  }
  60% {
    transform: scale(1.15);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.animate-check-bounce {
  animation: checkBounce 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
