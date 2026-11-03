import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import http, { fetchCsrfCookie } from '@/api/http'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const loading = ref(false)
  const initializing = ref(true)
  const error = ref(null)

  const isAuthenticated = computed(() => Boolean(user.value))
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')
  const isAdminGranja = computed(() => user.value?.role === 'admin_granja')
  const roles = computed(() => user.value?.roles ?? (user.value?.role ? [user.value.role] : []))
  const displayName = computed(() => user.value?.name || user.value?.full_name || 'Usuario')

  function hasRole(...roleNames) {
    return roleNames.some((role) => roles.value.includes(role))
  }

  async function fetchUser() {
    try {
      const { data } = await http.get('/auth/me')
      user.value = data.user ?? data
    } catch {
      user.value = null
    } finally {
      initializing.value = false
    }
  }

  async function login(credentials) {
    loading.value = true
    error.value = null

    try {
      await fetchCsrfCookie()
      const { data } = await http.post('/auth/login', credentials)
      user.value = data.user ?? data
      return data
    } catch (err) {
      error.value =
        err.response?.data?.message ||
        err.response?.data?.error ||
        'No se pudo iniciar sesión. Verifica tus credenciales.'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    loading.value = true
    try {
      await http.post('/auth/logout')
    } catch {
      // Aunque el backend no responda, la sesión local debe cerrarse.
    } finally {
      user.value = null
      loading.value = false
    }
  }

  async function initialize() {
    if (!initializing.value) return
    await fetchUser()
  }

  return {
    user,
    loading,
    initializing,
    error,
    isAuthenticated,
    isSuperAdmin,
    isAdminGranja,
    roles,
    displayName,
    hasRole,
    fetchUser,
    login,
    logout,
    initialize,
  }
})
