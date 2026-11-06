<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
  AlertTriangle,
  ArrowRight,
  Loader2,
  Pencil,
  Plus,
  Search,
  ShieldCheck,
  UserCheck,
  UserX,
  Users,
  X,
} from '@lucide/vue'
import http from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const users = ref([])
const meta = ref(null)
const summary = ref(null)
const loading = ref(true)
const error = ref('')
const gated = ref(false)
const search = ref('')
const roleFilter = ref('')
const activeFilter = ref('')

const modalOpen = ref(false)
const editingId = ref(null)
const saving = ref(false)
const formError = ref('')
const form = ref(emptyUser())

const roleOptions = [
  { value: 'admin_granja', label: 'Administrador de granja' },
  { value: 'veterinario', label: 'Veterinario' },
  { value: 'operario', label: 'Operario' },
]

const roleLabels = Object.fromEntries(roleOptions.map((role) => [role.value, role.label]))

function emptyUser() {
  return {
    name: '',
    email: '',
    password: '',
    role: 'operario',
    is_active: true,
  }
}

let timer = null
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(fetchUsers, 350)
})
watch([roleFilter, activeFilter], fetchUsers)

const editingSelf = computed(() => editingId.value === auth.user?.id)

function firstError(err) {
  const errors = err.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return err.response?.data?.message || 'No se pudo completar la operación.'
}

function roleLabel(role) {
  return roleLabels[role] ?? role ?? 'Sin rol'
}

function roleClass(role) {
  return {
    admin_granja: 'badge-info',
    veterinario: 'badge-success',
    operario: 'badge-warning',
  }[role] ?? 'badge-info'
}

function formatDate(value) {
  if (!value) return 'Nunca'
  return new Date(value).toLocaleDateString('es-CO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

async function fetchUsers() {
  loading.value = true
  error.value = ''
  gated.value = false
  try {
    const { data } = await http.get('/users', {
      params: {
        search: search.value || undefined,
        role: roleFilter.value || undefined,
        is_active: activeFilter.value === '' ? undefined : activeFilter.value,
        per_page: 100,
      },
    })
    users.value = data.data
    meta.value = data.meta
  } catch (err) {
    users.value = []
    if (err.response?.status === 402) {
      gated.value = true
    } else {
      error.value = firstError(err)
    }
  } finally {
    loading.value = false
  }
}

async function fetchSummary() {
  try {
    const { data } = await http.get('/users/summary')
    summary.value = data.data
  } catch {
    summary.value = null
  }
}

async function refresh() {
  await Promise.all([fetchUsers(), fetchSummary()])
}

function openCreate() {
  editingId.value = null
  form.value = emptyUser()
  formError.value = ''
  modalOpen.value = true
}

function openEdit(user) {
  editingId.value = user.id
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.role || 'operario',
    is_active: user.is_active,
  }
  formError.value = ''
  modalOpen.value = true
}

async function saveUser() {
  formError.value = ''

  if (!form.value.name.trim() || !form.value.email.trim()) {
    formError.value = 'El nombre y el correo son obligatorios.'
    return
  }

  if (!editingId.value && form.value.password.length < 8) {
    formError.value = 'La contraseña debe tener al menos 8 caracteres.'
    return
  }

  const payload = {
    name: form.value.name.trim(),
    email: form.value.email.trim(),
    role: form.value.role,
    is_active: form.value.is_active,
  }

  if (form.value.password) payload.password = form.value.password

  saving.value = true
  try {
    if (editingId.value) {
      await http.put(`/users/${editingId.value}`, payload)
    } else {
      await http.post('/users', payload)
    }
    modalOpen.value = false
    await refresh()
  } catch (err) {
    formError.value = firstError(err)
  } finally {
    saving.value = false
  }
}

async function deactivate(user) {
  if (!window.confirm(`¿Desactivar la cuenta de ${user.name}? No podrá iniciar sesión.`)) return

  error.value = ''
  try {
    await http.delete(`/users/${user.id}`)
    await refresh()
  } catch (err) {
    error.value = firstError(err)
  }
}

onMounted(refresh)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <h2 class="text-xl font-semibold text-unal-secondary">Usuarios</h2>
        <p class="mt-1 text-sm text-unal-secondary-light">
          Cuentas, roles y acceso del equipo de tu organización.
        </p>
      </div>
      <button v-if="!gated" type="button" class="btn-primary" @click="openCreate">
        <Plus class="h-4 w-4" />
        Nuevo usuario
      </button>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ error }}
    </div>

    <div
      v-if="gated"
      class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between"
    >
      <span class="inline-flex items-center gap-2">
        <AlertTriangle class="h-4 w-4" />
        Tu plan no incluye varios usuarios. Cambia a Intermedio o Avanzado para gestionar el equipo.
      </span>
      <RouterLink :to="{ name: 'plans' }" class="inline-flex items-center gap-1 font-medium text-amber-900 hover:underline">
        Ver planes <ArrowRight class="h-3.5 w-3.5" />
      </RouterLink>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article class="card p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-unal-secondary-light">Usuarios</p>
            <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary?.total ?? '—' }}</p>
            <p class="mt-1 text-xs text-unal-secondary-light">Cuentas de la organización</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-unal-primary/10 text-unal-primary">
            <Users class="h-5 w-5" />
          </div>
        </div>
      </article>
      <article class="card p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-unal-secondary-light">Activos</p>
            <p class="mt-2 text-2xl font-semibold text-unal-accent">{{ summary?.active ?? '—' }}</p>
            <p class="mt-1 text-xs text-unal-secondary-light">Pueden iniciar sesión</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-unal-accent/10 text-unal-accent">
            <UserCheck class="h-5 w-5" />
          </div>
        </div>
      </article>
      <article class="card p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-unal-secondary-light">Administradores</p>
            <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary?.admins ?? '—' }}</p>
            <p class="mt-1 text-xs text-unal-secondary-light">Con acceso completo</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-unal-primary/10 text-unal-primary">
            <ShieldCheck class="h-5 w-5" />
          </div>
        </div>
      </article>
      <article class="card p-5">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm text-unal-secondary-light">Campo y sanidad</p>
            <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary?.field_staff ?? '—' }}</p>
            <p class="mt-1 text-xs text-unal-secondary-light">Veterinarios y operarios</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
            <UserX class="h-5 w-5" />
          </div>
        </div>
      </article>
    </section>

    <section v-if="!gated" class="card">
      <div class="flex flex-col gap-3 border-b border-unal-border p-4 lg:flex-row lg:items-center">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-unal-secondary-light" />
          <input v-model="search" type="search" class="input pl-9" placeholder="Buscar por nombre o correo…" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <select v-model="roleFilter" class="input w-auto min-w-44">
            <option value="">Todos los roles</option>
            <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
          </select>
          <select v-model="activeFilter" class="input w-auto min-w-36">
            <option value="">Todos</option>
            <option value="1">Solo activos</option>
            <option value="0">Solo inactivos</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="table-base min-w-[780px]">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>Rol</th>
              <th>Estado</th>
              <th>Último acceso</th>
              <th>Creado</th>
              <th class="w-24"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="py-12 text-center text-sm text-unal-secondary-light">
                <Loader2 class="inline h-4 w-4 animate-spin" /> Cargando usuarios…
              </td>
            </tr>
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
              <td>
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 items-center justify-center rounded-full bg-unal-primary/10 text-sm font-semibold text-unal-primary">
                    {{ user.name.slice(0, 1).toUpperCase() }}
                  </div>
                  <div>
                    <p class="font-medium text-unal-secondary">
                      {{ user.name }}
                      <span v-if="user.id === auth.user?.id" class="text-xs font-normal text-unal-secondary-light">(tú)</span>
                    </p>
                    <p class="text-xs text-unal-secondary-light">{{ user.email }}</p>
                  </div>
                </div>
              </td>
              <td><span :class="roleClass(user.role)">{{ roleLabel(user.role) }}</span></td>
              <td>
                <span :class="user.is_active ? 'badge-success' : 'badge-danger'">
                  {{ user.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="text-unal-secondary-light">{{ formatDate(user.last_login_at) }}</td>
              <td class="text-unal-secondary-light">{{ formatDate(user.created_at) }}</td>
              <td>
                <div class="flex justify-end gap-1">
                  <button
                    type="button"
                    class="rounded p-1.5 text-unal-secondary-light hover:bg-gray-100 hover:text-unal-primary"
                    title="Editar"
                    @click="openEdit(user)"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button
                    v-if="user.is_active && user.id !== auth.user?.id"
                    type="button"
                    class="rounded p-1.5 text-red-600 hover:bg-gray-100"
                    title="Desactivar"
                    @click="deactivate(user)"
                  >
                    <UserX class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && users.length === 0">
              <td colspan="6" class="py-12 text-center text-sm text-unal-secondary-light">
                No hay usuarios que coincidan con los filtros.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="border-t border-unal-border px-4 py-3 text-xs text-unal-secondary-light">
        {{ meta?.from ?? 0 }}–{{ meta?.to ?? 0 }} de {{ meta?.total ?? 0 }} usuarios
      </div>
    </section>

    <Teleport to="body">
      <div v-if="modalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @click.self="modalOpen = false">
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-xl bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">{{ editingId ? 'Editar usuario' : 'Nuevo usuario' }}</h3>
              <p class="text-xs text-unal-secondary-light">
                {{ editingId ? 'Actualiza los datos y permisos del equipo.' : 'La cuenta se crea dentro de tu organización.' }}
              </p>
            </div>
            <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="modalOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>
          <form class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="saveUser">
            <div class="sm:col-span-2">
              <label class="label">Nombre completo *</label>
              <input v-model="form.name" class="input" required />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Correo electrónico *</label>
              <input v-model="form.email" type="email" class="input" required />
            </div>
            <div>
              <label class="label">Rol</label>
              <select v-model="form.role" class="input" :disabled="editingSelf">
                <option v-for="role in roleOptions" :key="role.value" :value="role.value">{{ role.label }}</option>
              </select>
              <p v-if="editingSelf" class="mt-1 text-xs text-unal-secondary-light">No puedes cambiar tu propio rol.</p>
            </div>
            <div>
              <label class="label">{{ editingId ? 'Nueva contraseña' : 'Contraseña *' }}</label>
              <input
                v-model="form.password"
                type="password"
                class="input"
                :required="!editingId"
                minlength="8"
                autocomplete="new-password"
              />
              <p v-if="editingId" class="mt-1 text-xs text-unal-secondary-light">Déjala vacía para conservarla.</p>
            </div>
            <label class="inline-flex items-center gap-2 text-sm sm:col-span-2">
              <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-unal-primary" :disabled="editingSelf" />
              Cuenta activa
            </label>
            <p v-if="formError" class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ formError }}
            </p>
            <div class="flex justify-end gap-2 sm:col-span-2">
              <button type="button" class="btn-secondary" @click="modalOpen = false">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                {{ saving ? 'Guardando…' : 'Guardar usuario' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
