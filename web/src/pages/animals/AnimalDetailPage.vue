<script setup>
import { computed, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  Activity,
  ArrowLeft,
  CalendarDays,
  Dna,
  Info,
  MapPin,
  Plus,
  Scale,
  Syringe,
  Tag,
} from '@lucide/vue'
import {
  demoEvents,
  demoVaccines,
  demoWeightHistory,
  getDemoAnimal,
} from '@/data/demoAnimals'

const route = useRoute()
const activeTab = ref('resumen')

const animal = computed(() => getDemoAnimal(String(route.params.id)))
const weights = computed(() => (animal.value ? demoWeightHistory(animal.value.id) : []))
const vaccines = computed(() => (animal.value ? demoVaccines(animal.value.id) : []))
const events = computed(() => (animal.value ? demoEvents(animal.value.id) : []))

const maxWeight = computed(() => Math.max(...weights.value.map((item) => item.weight), 1))

const gmd = computed(() => {
  if (weights.value.length < 2) return 0
  const last = weights.value.at(-1)
  const previous = weights.value.at(-2)
  return Math.round(((last.weight - previous.weight) / 30) * 1000)
})

const ageLabel = computed(() => {
  if (!animal.value?.birthDate) return '—'
  const birth = new Date(animal.value.birthDate)
  const now = new Date()
  let months = (now.getFullYear() - birth.getFullYear()) * 12 + now.getMonth() - birth.getMonth()
  if (now.getDate() < birth.getDate()) months -= 1
  if (months < 12) return `${Math.max(months, 0)} meses`
  const years = Math.floor(months / 12)
  const rest = months % 12
  return rest ? `${years} a ${rest} m` : `${years} años`
})

const tabs = [
  { id: 'resumen', label: 'Resumen' },
  { id: 'pesos', label: 'Pesos' },
  { id: 'vacunas', label: 'Vacunas' },
  { id: 'eventos', label: 'Eventos' },
]

function statusClass(status) {
  return {
    Activo: 'badge-success',
    Preñada: 'badge-info',
    Vendido: 'badge-warning',
    Fallecido: 'badge-danger',
  }[status] ?? 'badge-info'
}

function deltaLabel(current, previous) {
  const delta = current - previous
  return `${delta > 0 ? '+' : ''}${delta} kg`
}
</script>

<template>
  <div v-if="animal" class="space-y-6">
    <RouterLink :to="{ name: 'animals' }" class="inline-flex items-center gap-2 text-sm font-medium text-unal-secondary-light hover:text-unal-primary">
      <ArrowLeft class="h-4 w-4" />
      Volver al inventario animal
    </RouterLink>

    <!-- Cabecera del animal -->
    <section class="card overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div class="flex items-center gap-4">
          <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-unal-primary/10 text-2xl font-bold text-unal-primary">
            {{ animal.name.slice(0, 1) }}
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-2xl font-semibold text-unal-secondary">{{ animal.name }}</h2>
              <span :class="statusClass(animal.status)">{{ animal.status }}</span>
            </div>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-unal-secondary-light">
              <span class="inline-flex items-center gap-1"><Tag class="h-3.5 w-3.5" /> {{ animal.code }}</span>
              <span class="inline-flex items-center gap-1"><MapPin class="h-3.5 w-3.5" /> {{ animal.lot }}</span>
              <span class="inline-flex items-center gap-1"><Dna class="h-3.5 w-3.5" /> {{ animal.breed }}</span>
            </p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button type="button" class="btn-secondary" disabled title="Disponible al conectar la API">
            <Activity class="h-4 w-4" />
            Registrar evento
          </button>
          <button type="button" class="btn-primary" disabled title="Disponible al conectar la API">
            <Plus class="h-4 w-4" />
            Registrar pesaje
          </button>
        </div>
      </div>

      <div class="flex items-start gap-2 border-t border-unal-border bg-amber-50 px-5 py-3 text-xs text-amber-800">
        <Info class="mt-0.5 h-4 w-4 shrink-0" />
        <p>
          <strong>Vista de demostración.</strong> Animal de ejemplo. Los datos reales
          se cargarán desde <code>/api/animals/{{ animal.id }}</code>.
        </p>
      </div>
    </section>

    <!-- KPIs -->
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Peso actual</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ animal.weight }} kg</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Último pesaje: {{ animal.lastWeightAt }}</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Ganancia diaria</p>
        <p class="mt-2 text-2xl font-semibold text-unal-accent">{{ gmd }} g/día</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Estimada del último mes</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Edad</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ ageLabel }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Nacimiento: {{ animal.birthDate }}</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Próxima vacuna</p>
        <p class="mt-2 text-2xl font-semibold text-unal-primary">{{ animal.nextVaccineAt || '—' }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">{{ animal.lastVaccine }}</p>
      </article>
    </section>

    <!-- Tabs -->
    <section class="card">
      <div class="flex gap-1 overflow-x-auto border-b border-unal-border px-3 pt-2">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="whitespace-nowrap rounded-t-lg px-4 py-2.5 text-sm font-medium transition-colors"
          :class="activeTab === tab.id ? 'border-b-2 border-unal-primary text-unal-primary' : 'text-unal-secondary-light hover:bg-gray-50 hover:text-unal-secondary'"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Resumen -->
      <div v-if="activeTab === 'resumen'" class="grid gap-6 p-5 lg:grid-cols-2">
        <div>
          <h3 class="mb-3 font-semibold text-unal-secondary">Ficha del animal</h3>
          <dl class="grid grid-cols-2 gap-3 text-sm">
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Código único</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.code }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Especie</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.species }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Raza</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.breed }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Sexo</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.sex }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Padre</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.sire }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Madre</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.dam }}</dd>
            </div>
          </dl>
        </div>

        <div>
          <h3 class="mb-3 font-semibold text-unal-secondary">Evolución de peso</h3>
          <div class="space-y-3">
            <div v-for="entry in weights" :key="entry.month" class="flex items-center gap-3">
              <span class="w-10 text-xs uppercase text-unal-secondary-light">{{ entry.month }}</span>
              <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100">
                <div
                  class="h-full rounded-full bg-unal-accent transition-all"
                  :style="{ width: `${Math.max((entry.weight / maxWeight) * 100, 4)}%` }"
                />
              </div>
              <span class="w-16 text-right text-sm font-medium text-unal-secondary">{{ entry.weight }} kg</span>
            </div>
          </div>
          <p class="mt-4 flex items-center gap-1 text-xs text-unal-secondary-light">
            <Scale class="h-3.5 w-3.5" />
            Últimos 6 meses de registro
          </p>
        </div>
      </div>

      <!-- Pesos -->
      <div v-else-if="activeTab === 'pesos'" class="p-5">
        <div class="overflow-x-auto">
          <table class="table-base min-w-[480px]">
            <thead>
              <tr>
                <th>Mes</th>
                <th>Peso</th>
                <th>Variación</th>
                <th>GMD promedio</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(entry, index) in weights" :key="entry.month">
                <td class="capitalize">{{ entry.month }}</td>
                <td class="font-medium text-unal-secondary">{{ entry.weight }} kg</td>
                <td>
                  <span v-if="index === 0" class="text-unal-secondary-light">—</span>
                  <span v-else :class="entry.weight >= weights[index - 1].weight ? 'text-unal-accent' : 'text-red-600'">
                    {{ deltaLabel(entry.weight, weights[index - 1].weight) }}
                  </span>
                </td>
                <td class="text-unal-secondary-light">
                  <span v-if="index === 0">—</span>
                  <span v-else>{{ Math.round(((entry.weight - weights[index - 1].weight) / 30) * 1000) }} g/día</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Vacunas -->
      <div v-else-if="activeTab === 'vacunas'" class="p-5">
        <div class="overflow-x-auto">
          <table class="table-base min-w-[640px]">
            <thead>
              <tr>
                <th>Vacuna</th>
                <th>Aplicada</th>
                <th>Próxima dosis</th>
                <th>Lote</th>
                <th>Veterinario</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="vaccine in vaccines" :key="vaccine.id">
                <td class="font-medium text-unal-secondary">
                  <span class="inline-flex items-center gap-2"><Syringe class="h-4 w-4 text-unal-primary" /> {{ vaccine.vaccine }}</span>
                </td>
                <td>{{ vaccine.appliedAt }}</td>
                <td>{{ vaccine.nextAt }}</td>
                <td>{{ vaccine.batch }}</td>
                <td class="text-unal-secondary-light">{{ vaccine.veterinarian }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Eventos -->
      <div v-else class="p-5">
        <ol class="relative space-y-4 border-l border-unal-border pl-5">
          <li v-for="event in events" :key="event.id" class="relative">
            <span class="absolute -left-[27px] top-1 flex h-4 w-4 items-center justify-center rounded-full border-2 border-white bg-unal-primary" />
            <div class="flex flex-wrap items-center gap-2">
              <span class="badge-info">{{ event.type }}</span>
              <span class="inline-flex items-center gap-1 text-xs text-unal-secondary-light">
                <CalendarDays class="h-3.5 w-3.5" /> {{ event.date }}
              </span>
            </div>
            <p class="mt-1 text-sm text-unal-secondary">{{ event.detail }}</p>
            <p class="text-xs text-unal-secondary-light">Registrado por {{ event.user }}</p>
          </li>
        </ol>
      </div>
    </section>
  </div>

  <div v-else class="card flex flex-col items-center justify-center gap-3 p-12 text-center">
    <Scale class="h-10 w-10 text-unal-secondary-light" />
    <h2 class="text-lg font-semibold text-unal-secondary">Animal no encontrado</h2>
    <p class="max-w-md text-sm text-unal-secondary-light">
      El código {{ route.params.id }} no existe en los datos de ejemplo.
    </p>
    <RouterLink :to="{ name: 'animals' }" class="btn-primary mt-2">Volver al inventario</RouterLink>
  </div>
</template>
