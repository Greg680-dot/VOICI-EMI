<script setup>
defineProps({
  headers: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: false
  },
  empty: {
    type: Boolean,
    default: false
  },
  emptyTitle: {
    type: String,
    default: 'Aucune donnée disponible'
  }
});
</script>

<template>
  <div class="w-full overflow-hidden rounded-2xl border border-surface-border bg-white shadow-subtle">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm border-collapse" role="table">
        <thead class="bg-surface-muted/60 border-b border-surface-border">
          <tr>
            <th 
              v-for="(header, i) in headers" 
              :key="i"
              scope="col"
              class="px-4 py-3.5 text-xs font-semibold text-ink-muted uppercase tracking-wider"
              :class="header.align === 'right' ? 'text-right' : header.align === 'center' ? 'text-center' : 'text-left'"
            >
              {{ typeof header === 'string' ? header : header.label }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-surface-border/70 text-ink-base">
          <tr v-if="loading">
            <td :colspan="headers.length || 1" class="p-8 text-center">
              <div class="inline-flex items-center gap-2 text-sm text-ink-muted">
                <svg class="animate-spin w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Chargement des données...</span>
              </div>
            </td>
          </tr>

          <tr v-else-if="empty">
            <td :colspan="headers.length || 1" class="p-8 text-center text-sm text-ink-muted">
              {{ emptyTitle }}
            </td>
          </tr>

          <slot v-else />
        </tbody>
      </table>
    </div>
  </div>
</template>
