<script setup>
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ChevronRight,
  Info,
  Plus,
  Search,
  SlidersHorizontal,
  X,
} from '@lucide/vue'
import { demoAnimals, demoSpecies, demoStatuses } from '@/data/demoAnimals'

const animals = ref([...demoAnimals])
const search = ref('')
const speciesFilter = ref('')
const statusFilter = ref('')
const modalOpen = ref(false)
const formError = ref('')

const emptyForm = () => ({
  code: '',
  name: '',
  species: 'Bovino',
  breed: '',
  sex: 'Hembra',
  birthDate: '',
  weight: '',
  lot: '',
  status: 'Activo',
})

const form = ref(emptyForm())

const filteredAnimals = computed(() => {
  const term = search.value.trim().toLowerCase()
  return animals.value.filter((animal) => {
    const matchesTerm =
      !term ||
      [animal.code, animal.name, animal.breed, animal.lot]
        .join(' ')
        .toLowerCase()
        .includes(term)
    const matchesSpecies = !speciesFilter.value || animal.species === speciesFilter.value
    const matchesStatus = !statusFilter.value || animal.status === statusFilter.value
    return matchesTerm && matchesSpecies && matchesStatus
  })
})

const summary = computed(() => {
  const active = animals.value.filter((animal) => animal.status !== 'Fallecido' && animal.status !== 'Vendido')
  const avgWeight = active.length
    ? Math.round(active.reduce((sum, animal) => sum + Number(animal.weight), 0) / active.length)
    : 0
  const pregnant = animals.value.filter((animal) => animal.status === 'Preñada').length
  return {
    total: animals.value.length,
    active: active.length,
    avgWeight,
    pregnant,
  }
})

function ageLabel(birthDate) {
  if (!birthDate) return '—'
  const birth = new Date(birthDate)
  const now = new Date()
  let months = (now.getFullYear() - birth.getFullYear()) * 12 + now.getMonth() - birth.getMonth()
  if (now.getDate() < birth.getDate()) months -= 1
  if (months < 12) return `${Math.max(months, 0)} meses`
  const years = Math.floor(months / 12)
  const rest = months % 12
  return rest ? `${years} a ${rest} m` : `${years} años`
}

function statusClass(status) {
  return {
    Activo: 'badge-success',
    Preñada: 'badge-info',
    Vendido: 'badge-warning',
    Fallecido: 'badge-danger',
  }[status] ?? 'badge-info'
}

function openModal() {
  form.value = emptyForm()
  formError.value = ''
  modalOpen.value = true
}

function saveAnimal() {
  formError.value = ''
  if (!form.value.code.trim() || !form.value.name.trim()) {
    formError.value = 'El código y el nombre son obligatorios.'
    return
  }
  if (animals.value.some((animal) => animal.code.toLowerCase() === form.value.code.trim().toLowerCase())) {
    formError.value = 'Ya existe un animal con ese código en la organización.'
    return
  }

  animals.value.unshift({
    id: form.value.code.trim(),
    code: form.value.code.trim(),
    name: form.value.name.trim(),
    species: form.value.species,
    breed: form.value.breed.trim() || '—',
    sex: form.value.sex,
    birthDate: form.value.birthDate,
    weight: Number(form.value.weight) || 0,
    lot: form.value.lot.trim() || 'Sin asignar',
    status: form.value.status,
    sire: '—',
    dam: '—',
    lastWeightAt: new Date().toISOString().slice(0, 10),
    lastVaccine: '—',
    nextVaccineAt: '',
  })
  modalOpen.value = false
}
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

    <div class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
      <Info class="mt-0.5 h-4 w-4 shrink-0" />
      <p>
        <strong>Datos de ejemplo.</strong> Esta vista es una previsualización de la UX.
        Los animales que agregues aquí son locales y se perderán al recargar. La
        persistencia real llegará con <code>/api/animals</code>.
      </p>
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
            placeholder="Buscar por código, nombre, raza o potrero…"
            aria-label="Buscar animales"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <SlidersHorizontal class="hidden h-4 w-4 text-unal-secondary-light sm:block" />
          <select v-model="speciesFilter" class="input w-auto min-w-36" aria-label="Filtrar por especie">
            <option value="">Todas las especies</option>
            <option v-for="species in demoSpecies" :key="species" :value="species">{{ species }}</option>
          </select>
          <select v-model="statusFilter" class="input w-auto min-w-36" aria-label="Filtrar por estado">
            <option value="">Todos los estados</option>
            <option v-for="status in demoStatuses" :key="status" :value="status">{{ status }}</option>
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
            <tr v-for="animal in filteredAnimals" :key="animal.id" class="hover:bg-gray-50">
              <td>
                <RouterLink :to="{ name: 'animal-detail', params: { id: animal.id } }" class="group flex items-center gap-3">
                  <div class="flex h-9 w-9 items-center justify-center rounded-full bg-unal-primary/10 text-xs font-semibold text-unal-primary">
                    {{ animal.name.slice(0, 1) }}
                  </div>
                  <div>
                    <p class="font-medium text-unal-secondary group-hover:text-unal-primary">{{ animal.name }}</p>
                    <p class="text-xs text-unal-secondary-light">{{ animal.code }}</p>
                  </div>
                </RouterLink>
              </td>
              <td>
                <p class="text-unal-secondary">{{ animal.species }}</p>
                <p class="text-xs text-unal-secondary-light">{{ animal.breed }}</p>
              </td>
              <td>{{ animal.sex }}</td>
              <td>{{ ageLabel(animal.birthDate) }}</td>
              <td class="font-medium text-unal-secondary">{{ animal.weight }} kg</td>
              <td>{{ animal.lot }}</td>
              <td>
                <span :class="statusClass(animal.status)">{{ animal.status }}</span>
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
            <tr v-if="filteredAnimals.length === 0">
              <td colspan="8" class="py-12 text-center text-sm text-unal-secondary-light">
                No se encontraron animales con los filtros seleccionados.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between border-t border-unal-border px-4 py-3 text-xs text-unal-secondary-light">
        <span>{{ filteredAnimals.length }} de {{ animals.length }} animales</span>
        <span>Paginación pendiente de API</span>
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
              <p class="text-xs text-unal-secondary-light">Registro local de demostración</p>
            </div>
            <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="modalOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>

          <form class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="saveAnimal">
            <div>
              <label class="label" for="animal-code">Código / ID único *</label>
              <input id="animal-code" v-model="form.code" class="input" placeholder="A-006" required />
            </div>
            <div>
              <label class="label" for="animal-name">Nombre *</label>
              <input id="animal-name" v-model="form.name" class="input" placeholder="Ej. Lucero" required />
            </div>
            <div>
              <label class="label" for="animal-species">Especie</label>
              <select id="animal-species" v-model="form.species" class="input">
                <option v-for="species in demoSpecies" :key="species" :value="species">{{ species }}</option>
              </select>
            </div>
            <div>
              <label class="label" for="animal-breed">Raza</label>
              <input id="animal-breed" v-model="form.breed" class="input" placeholder="Ej. Brahman" />
            </div>
            <div>
              <label class="label" for="animal-sex">Sexo</label>
              <select id="animal-sex" v-model="form.sex" class="input">
                <option>Hembra</option>
                <option>Macho</option>
              </select>
            </div>
            <div>
              <label class="label" for="animal-birth">Fecha de nacimiento</label>
              <input id="animal-birth" v-model="form.birthDate" type="date" class="input" />
            </div>
            <div>
              <label class="label" for="animal-weight">Peso actual (kg)</label>
              <input id="animal-weight" v-model="form.weight" type="number" min="0" step="0.1" class="input" placeholder="0" />
            </div>
            <div>
              <label class="label" for="animal-lot">Potrero / lote</label>
              <input id="animal-lot" v-model="form.lot" class="input" placeholder="Ej. Potrero Norte" />
            </div>
            <div>
              <label class="label" for="animal-status">Estado</label>
              <select id="animal-status" v-model="form.status" class="input">
                <option v-for="status in demoStatuses" :key="status" :value="status">{{ status }}</option>
              </select>
            </div>

            <p v-if="formError" class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ formError }}
            </p>

            <div class="flex justify-end gap-2 sm:col-span-2">
              <button type="button" class="btn-secondary" @click="modalOpen = false">Cancelar</button>
              <button type="submit" class="btn-primary">Guardar animal</button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
