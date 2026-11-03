import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import http from '@/api/http'
import { useAuthStore } from '@/stores/auth'

// Suscripción de la organización del usuario autenticado. El backend es la
// autoridad (responde 402 cuando falta una feature); este store solo mejora la UX.
export const usePlanStore = defineStore('plan', () => {
  const subscription = ref(null)
  const loading = ref(false)
  const loaded = ref(false)

  const plan = computed(() => subscription.value?.plan ?? null)
  const features = computed(() => (plan.value?.features ?? []).map((feature) => feature.code))
  const planName = computed(() => plan.value?.name ?? null)
  const planCode = computed(() => plan.value?.code ?? null)
  const isActive = computed(
    () => ['active', 'trialing'].includes(subscription.value?.status) && (subscription.value?.days_left ?? 0) >= 0,
  )
  const daysLeft = computed(() => subscription.value?.days_left ?? null)
  const isSuperAdmin = computed(() => useAuthStore().isSuperAdmin)

  function hasFeature(code) {
    if (useAuthStore().isSuperAdmin) return true
    return features.value.includes(code)
  }

  async function fetchPlan(force = false) {
    const auth = useAuthStore()
    if (!auth.isAuthenticated) {
      subscription.value = null
      loaded.value = false
      return null
    }
    if (loaded.value && !force) return subscription.value

    loading.value = true
    try {
      const { data } = await http.get('/billing/current')
      subscription.value = data.data ?? null
      return subscription.value
    } catch {
      subscription.value = null
      return null
    } finally {
      loading.value = false
      loaded.value = true
    }
  }

  function reset() {
    subscription.value = null
    loaded.value = false
  }

  return {
    subscription,
    plan,
    loading,
    loaded,
    features,
    planName,
    planCode,
    isActive,
    daysLeft,
    isSuperAdmin,
    hasFeature,
    fetchPlan,
    reset,
  }
})
