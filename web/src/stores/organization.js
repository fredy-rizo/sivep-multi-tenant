import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import http from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const STORAGE_KEY = 'sivep.organization'

// Organización activa para el super_admin. Los usuarios normales siempre
// operan sobre su propia organización y el backend ignora este selector.
export const useOrganizationStore = defineStore('organization', () => {
  const organizations = ref([])
  const selectedId = ref(Number(localStorage.getItem(STORAGE_KEY)) || null)
  const loading = ref(false)

  const selected = computed(
    () => organizations.value.find((organization) => organization.id === selectedId.value) ?? null,
  )

  async function fetchOrganizations() {
    const auth = useAuthStore()

    if (!auth.isSuperAdmin) {
      organizations.value = []
      return []
    }

    loading.value = true
    try {
      const { data } = await http.get('/organizations')
      organizations.value = data.data ?? []
      return organizations.value
    } finally {
      loading.value = false
    }
  }

  function select(id) {
    selectedId.value = id ? Number(id) : null

    if (selectedId.value) {
      localStorage.setItem(STORAGE_KEY, String(selectedId.value))
    } else {
      localStorage.removeItem(STORAGE_KEY)
    }
  }

  function reset() {
    organizations.value = []
    selectedId.value = null
    localStorage.removeItem(STORAGE_KEY)
  }

  /**
   * Garantiza que el super_admin tenga una organización activa antes de
   * consultar las rutas de negocio. Si no hay ninguna seleccionada, elige la
   * primera y recarga para que todas las vistas consulten con el encabezado.
   */
  async function ensureSelected() {
    const auth = useAuthStore()

    if (!auth.isSuperAdmin) {
      localStorage.removeItem(STORAGE_KEY)
      return
    }

    await fetchOrganizations()

    if (organizations.value.length === 0) {
      select(null)
      return
    }

    const stillValid = organizations.value.some((organization) => organization.id === selectedId.value)

    if (!stillValid) {
      select(organizations.value[0].id)
      window.location.reload()
    }
  }

  return {
    organizations,
    selectedId,
    selected,
    loading,
    fetchOrganizations,
    select,
    reset,
    ensureSelected,
  }
})
