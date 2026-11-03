import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import http from '@/api/http'
import { useAuthStore } from '@/stores/auth'

// Plan de la organización del usuario autenticado. El backend es la autoridad
// (responde 402 cuando falta una feature); este store solo mejora la UX.
export const usePlanStore = defineStore('plan', () => {
  const plan = ref(null)
  const loading = ref(false)
  const loaded = ref(false)

  const features = computed(() => plan.value?.features ?? [])
  const planName = computed(() => plan.value?.plan?.name ?? null)
  const planCode = computed(() => plan.value?.plan?.code ?? null)
  const isActive = computed(() => plan.value?.isActive ?? false)
  const isSuperAdmin = computed(() => Boolean(plan.value?.isSuperAdmin))

  function hasFeature(code) {
    if (!plan.value) return false
    if (plan.value.isSuperAdmin) return true
    return features.value.includes(code)
  }

  async function fetchPlan(force = false) {
    const auth = useAuthStore()
    if (!auth.isAuthenticated) {
      plan.value = null
      loaded.value = false
      return null
    }
    if (loaded.value && !force) return plan.value

    loading.value = true
    try {
      const { data } = await http.get('/billing/me')
      plan.value = data
      return data
    } catch {
      plan.value = null
      return null
    } finally {
      loading.value = false
      loaded.value = true
    }
  }

  function reset() {
    plan.value = null
    loaded.value = false
  }

  return {
    plan,
    loading,
    loaded,
    features,
    planName,
    planCode,
    isActive,
    isSuperAdmin,
    hasFeature,
    fetchPlan,
    reset,
  }
})
