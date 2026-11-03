<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ChevronLeft,
  ChevronRight,
  Loader2,
  Plus,
  Search,
  SlidersHorizontal,
  X,
} from '@lucide/vue'
import http from '@/api/http'

const animals = ref([])
const meta = ref(null)
const summaryData = ref(null)
const loading = ref(true)
const listError = ref('')
const search = ref('')
const speciesFilter = ref('')
const statusFilter = ref('')

const catalogs = ref({ species: [], breeds: [], lots: [], eventTypes: [] })
const modalOpen = ref(false)
const saving = ref(false)
const formError = ref('')

const statusLabels = {
  activo: 'Activo',
  'preñada': 'Preñada',
  lactando: 'Lactando',
  vendido: 'Vendido',
  fallecido: 'Fallecido',
  trasladado: 'Trasladado',
}

const emptyForm = () => ({
  code: '',
  name: '',
  species_id: '',
  breed_id: '',
  lot_id: '',
  sex: 'hembra',
  birth_date: '',
  weight: '',
  status: 'activo',
  notes: '',
})

const form = ref(emptyForm())

const formBreeds = computed(() =>
  catalogs.value.breeds.filter(
    (breed) => !form.value.species_id || breed.species_id === Number(form.value.species_id),
  ),
)

const summary = computed(() => ({
  total: summaryData.value?.total_animals ?? meta.value?.total ?? animals.value.length,
  active: summaryData.value?.active_animals ?? 0,
  avgWeight: Math.round(Number(summaryData.value?.average_weight_kg ?? 0)),
  pregnant: summaryData.value?.by_status?.['preñada'] ?? 0,
}))

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchAnimals(1), 350)
})
watch([speciesFilter, statusFilter], () => fetchAnimals(1))

async function fetchAnimals(page = 1) {
  loading.value = true
  listError.value = ''
  try {
    const { data } = await http.get('/animals', {
      params: {
        page,
        per_page: 25,
        search: search.value || undefined,
        species_id: speciesFilter.value || undefined,
        status: statusFilter.value || undefined,
      },
    })
    animals.value = data.data
    meta.value = data.meta
  } catch (err) {
    listError.value = err.response?.data?.message || 'No se pudo cargar el inventario animal.'
  } finally {
    loading.value = false
  }
}

async function fetchSummary() {
  try {
    const { data } = await http.get('/reports/summary')
    summaryData.value = data.data
  } catch {
    summaryData.value = null
  }
}

async function fetchCatalogs() {
  try {
    const [species, breeds, lots, eventTypes] = await Promise.all([
      http.get('/catalogs/species'),
      http.get('/catalogs/breeds'),
      http.get('/catalogs/lots'),
      http.get('/catalogs/event-types'),
    ])
    catalogs.value = {
      species: species.data.data,
      breeds: breeds.data.data,
      lots: lots.data.data,
      eventTypes: eventTypes.data.data,
    }
  } catch {
    // Los catálogos se reintentan al abrir el formulario.
  }
}

async function refresh() {
  await Promise.all([fetchAnimals(meta.value?.current_page ?? 1), fetchSummary()])
}

function openModal() {
  form.value = emptyForm()
  formError.value = ''
  modalOpen.value = true
  if (catalogs.value.species.length === 0) fetchCatalogs()
}

function ageLabel(animal) {
  if (animal.birth_date && animal.age_months != null) {
    const months = animal.age_months
    if (months < 12) return `${months} meses`
    const years = Math.floor(months / 12)
    const rest = months % 12
    return rest ? `${years} a ${rest} m` : `${years} años`
  }
  return '—'
}

function statusLabel(status) {
  return statusLabels[status] ?? status ?? '—'
}

function statusClass(status) {
  return {
    activo: 'badge-success',
    lactando: 'badge-success',
    'preñada': 'badge-info',
    vendido: 'badge-warning',
    trasladado: 'badge-warning',
    fallecido: 'badge-danger',
  }[status] ?? 'badge-info'
}

function firstError(err) {
  const errors = err.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return err.response?.data?.message || 'No se pudo guardar el animal.'
}

async function saveAnimal() {
  formError.value = ''

  if (!form.value.code.trim() || !form.value.species_id) {
    formError.value = 'El código y la especie son obligatorios.'
    return
  }

  saving.value = true

  try {
    const { data } = await http.post('/animals', {
      code: form.value.code.trim(),
      name: form.value.name.trim() || null,
      species_id: Number(form.value.species_id),
      breed_id: form.value.breed_id ? Number(form.value.breed_id) : null,
      lot_id: form.value.lot_id ? Number(form.value.lot_id) : null,
      sex: form.value.sex,
      birth_date: form.value.birth_date || null,
      status: form.value.status,
      notes: form.value.notes || null,
    })

    const animal = data.data
    const weight = Number(form.value.weight)

    if (weight > 0) {
      const weighingType = catalogs.value.eventTypes.find((type) => type.code === 'pesaje')

      if (weighingType) {
        await http.post(`/animals/${animal.id}/events`, {
          event_type_id: weighingType.id,
          event_date: new Date().toISOString().slice(0, 10),
          weight_kg: weight,
          notes: 'Peso inicial de registro.',
        })
      }
    }

    modalOpen.value = false
    await refresh()
  } catch (err) {
    formError.value = firstError(err)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchAnimals(), fetchSummary(), fetchCatalogs()])
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <h2 class="text-xl font-semibold text-unal-secondary">Inventario animal</h2>
        <p class="mt-1 text-sm text-unal-secondary-light">
          ID único por organización, trazabilidad y control sanitario.
        </p>
      </div>
      <button type="button" class="btn-primary" @click="openModal">
        <Plus class="h-4 w-4" />
        Nuevo animal
      </button>
    </div>

    <div
      v-if="listError"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    >
      {{ listError }}
    </div>

    <!-- Resumen -->
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Animales registrados</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary.total }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">En la organización</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Activos</p>
        <p class="mt-2 text-2xl font-semibold text-unal-accent">{{ summary.active }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Disponibles para producción</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Peso promedio</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary.avgWeight }} kg</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Último pesaje registrado</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Hembras preñadas</p>
        <p class="mt-2 text-2xl font-semibold text-unal-primary">{{ summary.pregnant }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Seguimiento reproductivo</p>
      </article>
    </section>

    <!-- Filtros -->
    <section class="card">
      <div class="flex flex-col gap-3 border-b border-unal-border p-4 lg:flex-row lg:items-center">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-unal-secondary-light" />
          <input
            v-model="search"
            type="search"
            class="input pl-9"
            placeholder="Buscar por código, nombre, arete o RFID…"
            aria-label="Buscar animales"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <SlidersHorizontal class="hidden h-4 w-4 text-unal-secondary-light sm:block" />
          <select v-model="speciesFilter" class="input w-auto min-w-36" aria-label="Filtrar por especie">
            <option value="">Todas las especies</option>
            <option v-for="species in catalogs.species" :key="species.id" :value="species.id">
              {{ species.name }}
            </option>
          </select>
          <select v-model="statusFilter" class="input w-auto min-w-36" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
          </select>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="table-base min-w-[820px]">
          <thead>
            <tr>
              <th>Animal</th>
              <th>Especie / raza</th>
              <th>Sexo</th>
              <th>Edad</th>
              <th>Peso</th>
              <th>Potrero</th>
              <th>Estado</th>
              <th class="w-10"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="py-12 text-center text-sm text-unal-secondary-light">
                <span class="inline-flex items-center gap-2">
                  <Loader2 class="h-4 w-4 animate-spin" /> Cargando animales…
                </span>
              </td>
            </tr>
            <tr v-for="animal in animals" :key="animal.id" class="hover:bg-gray-50">
              <td>
                <RouterLink :to="{ name: 'animal-detail', params: { id: animal.id } }" class="group flex items-center gap-3">
                  <div class="flex h-9 w-9 items-center justify-center rounded-full bg-unal-primary/10 text-xs font-semibold text-unal-primary">
                    {{ (animal.name || animal.code).slice(0, 1) }}
                  </div>
                  <div>
                    <p class="font-medium text-unal-secondary group-hover:text-unal-primary">{{ animal.name || 'Sin nombre' }}</p>
                    <p class="text-xs text-unal-secondary-light">{{ animal.code }}</p>
                  </div>
                </RouterLink>
              </td>
              <td>
                <p class="text-unal-secondary">{{ animal.species?.name ?? '—' }}</p>
                <p class="text-xs text-unal-secondary-light">{{ animal.breed?.name ?? 'Sin raza' }}</p>
              </td>
              <td class="capitalize">{{ animal.sex }}</td>
              <td>{{ ageLabel(animal) }}</td>
              <td class="font-medium text-unal-secondary">
                {{ animal.current_weight_kg ? `${animal.current_weight_kg} kg` : '—' }}
              </td>
              <td>{{ animal.lot?.name ?? 'Sin asignar' }}</td>
              <td>
                <span :class="statusClass(animal.status)">{{ statusLabel(animal.status) }}</span>
              </td>
              <td>
                <RouterLink
                  :to="{ name: 'animal-detail', params: { id: animal.id } }"
                  class="inline-flex rounded p-1.5 text-unal-secondary-light hover:bg-gray-100 hover:text-unal-primary"
                  aria-label="Ver detalle"
                >
                  <ChevronRight class="h-4 w-4" />
                </RouterLink>
              </td>
            </tr>
            <tr v-if="!loading && animals.length === 0">
              <td colspan="8" class="py-12 text-center text-sm text-unal-secondary-light">
                No se encontraron animales con los filtros seleccionados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between border-t border-unal-border px-4 py-3 text-xs text-unal-secondary-light">
        <span>
          {{ meta?.from ?? 0 }}–{{ meta?.to ?? 0 }} de {{ meta?.total ?? 0 }} animales
        </span>
        <div class="flex items-center gap-1">
          <button
            type="button"
            class="rounded p-1.5 hover:bg-gray-100 disabled:opacity-40"
            :disabled="!meta || meta.current_page <= 1"
            aria-label="Página anterior"
            @click="fetchAnimals(meta.current_page - 1)"
          >
            <ChevronLeft class="h-4 w-4" />
          </button>
          <span>Página {{ meta?.current_page ?? 1 }} de {{ meta?.last_page ?? 1 }}</span>
          <button
            type="button"
            class="rounded p-1.5 hover:bg-gray-100 disabled:opacity-40"
            :disabled="!meta || meta.current_page >= meta.last_page"
            aria-label="Página siguiente"
            @click="fetchAnimals(meta.current_page + 1)"
          >
            <ChevronRight class="h-4 w-4" />
          </button>
        </div>
      </div>
    </section>

    <!-- Modal nuevo animal -->
    <Teleport to="body">
      <div
        v-if="modalOpen"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="new-animal-title"
        @click.self="modalOpen = false"
      >
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 id="new-animal-title" class="font-semibold text-unal-secondary">Nuevo animal</h3>
              <p class="text-xs text-unal-secondary-light">El código debe ser único en tu organización</p>
            </div>
            <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="modalOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>

          <form class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="saveAnimal">
            <div>
              <label class="label" for="animal-code">Código / ID único *</label>
              <input id="animal-code" v-model="form.code" class="input" placeholder="BOV-006" required />
            </div>
            <div>
              <label class="label" for="animal-name">Nombre</label>
              <input id="animal-name" v-model="form.name" class="input" placeholder="Ej. Lucero" />
            </div>
            <div>
              <label class="label" for="animal-species">Especie *</label>
              <select id="animal-species" v-model="form.species_id" class="input" required @change="form.breed_id = ''">
                <option value="" disabled>Selecciona una especie</option>
                <option v-for="species in catalogs.species" :key="species.id" :value="species.id">
                  {{ species.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="label" for="animal-breed">Raza</label>
              <select id="animal-breed" v-model="form.breed_id" class="input">
                <option value="">Sin raza</option>
                <option v-for="breed in formBreeds" :key="breed.id" :value="breed.id">
                  {{ breed.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="label" for="animal-sex">Sexo *</label>
              <select id="animal-sex" v-model="form.sex" class="input">
                <option value="hembra">Hembra</option>
                <option value="macho">Macho</option>
              </select>
            </div>
            <div>
              <label class="label" for="animal-birth">Fecha de nacimiento</label>
              <input id="animal-birth" v-model="form.birth_date" type="date" class="input" />
            </div>
            <div>
              <label class="label" for="animal-weight">Peso inicial (kg)</label>
              <input id="animal-weight" v-model="form.weight" type="number" min="0" step="0.1" class="input" placeholder="0" />
              <p class="mt-1 text-xs text-unal-secondary-light">Se registra como evento de pesaje.</p>
            </div>
            <div>
              <label class="label" for="animal-lot">Potrero / lote</label>
              <select id="animal-lot" v-model="form.lot_id" class="input">
                <option value="">Sin asignar</option>
                <option v-for="lot in catalogs.lots" :key="lot.id" :value="lot.id">{{ lot.name }}</option>
              </select>
            </div>
            <div>
              <label class="label" for="animal-status">Estado</label>
              <select id="animal-status" v-model="form.status" class="input">
                <option v-for="(label, value) in statusLabels" :key="value" :value="value">{{ label }}</option>
              </select>
            </div>
            <div>
              <label class="label" for="animal-notes">Notas</label>
              <input id="animal-notes" v-model="form.notes" class="input" placeholder="Observaciones" />
            </div>

            <p v-if="formError" class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ formError }}
            </p>

            <div class="flex justify-end gap-2 sm:col-span-2">
              <button type="button" class="btn-secondary" @click="modalOpen = false">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                {{ saving ? 'Guardando…' : 'Guardar animal' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
