<script setup>
import { onMounted, ref, watch } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'
import AppSidebar from '@/components/AppSidebar.vue'
import { useAuthStore } from '@/stores/auth'
import { usePlanStore } from '@/stores/plan'

const route = useRoute()
const auth = useAuthStore()
const plan = usePlanStore()

const sidebarOpen = ref(false)

watch(
  () => route.fullPath,
  () => {
    sidebarOpen.value = false
  },
)

watch(
  () => auth.isAuthenticated,
  (authenticated) => {
    if (authenticated) plan.fetchPlan(true)
    else plan.reset()
  },
)

onMounted(() => {
  if (auth.isAuthenticated) plan.fetchPlan()
})
</script>

<template>
  <div class="min-h-screen bg-unal-background">
    <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-40 bg-black/40 lg:hidden"
      aria-hidden="true"
      @click="sidebarOpen = false"
    />

    <div class="flex min-h-screen flex-col lg:pl-64">
      <AppHeader @toggle-sidebar="sidebarOpen = true" />

      <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
        <RouterView />
      </main>

      <footer class="border-t border-unal-border px-6 py-4 text-center text-xs text-unal-secondary-light">
        SIVEP · Granja El Cairo · Universidad Nacional de Colombia — Sede Orinoquía
      </footer>
    </div>
  </div>
</template>
