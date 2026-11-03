<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  ArrowLeft,
  CalendarDays,
  Dna,
  Loader2,
  MapPin,
  Plus,
  Scale,
  Syringe,
  Tag,
  X,
} from '@lucide/vue'
import http from '@/api/http'

const route = useRoute()
const activeTab = ref('resumen')
const animal = ref(null)
const events = ref([])
const summaries = ref([])
const loading = ref(true)
const loadError = ref('')

const catalogs = ref({ eventTypes: [], vaccines: [] })
const eventModalOpen = ref(false)
const saving = ref(false)
const formError = ref('')

const monthNames = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic']

const statusLabels = {
  activo: 'Activo',
  'preñada': 'Preñada',
  lactando: 'Lactando',
  vendido: 'Vendido',
  fallecido: 'Fallecido',
  trasladado: 'Trasladado',
}

const emptyEventForm = () => ({
  event_type_id: '',
  event_date: new Date().toISOString().slice(0, 10),
  weight_kg: '',
  vaccine_id: '',
  dose_ml: '',
  batch_number: '',
  next_due_date: '',
  diagnosis: '',
  treatment: '',
  cost: '',
  notes: '',
})

const eventForm = ref(emptyEventForm())

const selectedType = computed(() =>
  catalogs.value.eventTypes.find((type) => type.id === Number(eventForm.value.event_type_id)),
)

const weights = computed(() =>
  [...summaries.value]
    .reverse()
    .filter((summary) => summary.last_weight_kg != null)
    .map((summary) => {
      const [year, month] = summary.period.split('-')
      return {
        period: summary.period,
        month: `${monthNames[Number(month) - 1]} ${year.slice(2)}`,
        weight: Number(summary.last_weight_kg),
        gain: summary.weight_gain_kg != null ? Number(summary.weight_gain_kg) : null,
        dailyGain: summary.daily_gain_g != null ? Math.round(Number(summary.daily_gain_g)) : null,
        eventsCount: summary.events_count,
        vaccinesCount: summary.vaccines_count,
      }
    }),
)

const vaccines = computed(() =>
  events.value
    .filter((event) => event.vaccine)
    .map((event) => ({
      id: event.id,
      vaccine: event.vaccine.name,
      disease: event.vaccine.disease,
      appliedAt: event.event_date,
      nextAt: event.next_due_date,
      batch: event.batch_number || '—',
      veterinarian: event.created_by?.name || '—',
    })),
)

const maxWeight = computed(() => Math.max(...weights.value.map((item) => item.weight), 1))

const gmd = computed(() => {
  const latest = weights.value.at(-1)
  return latest?.dailyGain ?? 0
})

const ageLabel = computed(() => {
  const months = animal.value?.age_months
  if (months == null) return '—'
  if (months < 12) return `${months} meses`
  const years = Math.floor(months / 12)
  const rest = months % 12
  return rest ? `${years} a ${rest} m` : `${years} años`
})

const lastWeightAt = computed(() => animal.value?.last_weight_at || weights.value.at(-1)?.period || '—')

const tabs = [
  { id: 'resumen', label: 'Resumen' },
  { id: 'pesos', label: 'Pesos' },
  { id: 'vacunas', label: 'Vacunas' },
  { id: 'eventos', label: 'Eventos' },
]

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

function eventDetail(event) {
  const parts = []
  if (event.weight_kg) parts.push(`Peso: ${event.weight_kg} kg`)
  if (event.vaccine) parts.push(`${event.vaccine.name} · dosis ${event.dose_ml ?? '—'} ml`)
  if (event.diagnosis) parts.push(event.diagnosis)
  if (event.treatment) parts.push(event.treatment)
  if (event.notes) parts.push(event.notes)
  return parts.join(' · ') || 'Sin detalle adicional'
}

async function fetchAnimal() {
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await http.get(`/animals/${route.params.id}`)
    animal.value = data.data
    events.value = data.events ?? []
    summaries.value = data.monthly_summaries ?? []
  } catch (err) {
    animal.value = null
    loadError.value =
      err.response?.status === 404
        ? 'El animal no existe o no pertenece a tu organización.'
        : err.response?.data?.message || 'No se pudo cargar el animal.'
  } finally {
    loading.value = false
  }
}

async function fetchCatalogs() {
  try {
    const [eventTypes, vaccinesCatalog] = await Promise.all([
      http.get('/catalogs/event-types'),
      http.get('/catalogs/vaccines'),
    ])
    catalogs.value = {
      eventTypes: eventTypes.data.data,
      vaccines: vaccinesCatalog.data.data,
    }
  } catch {
    // Se reintenta al abrir el formulario.
  }
}

function openEventModal(typeCode = null) {
  eventForm.value = emptyEventForm()
  formError.value = ''

  const type = typeCode
    ? catalogs.value.eventTypes.find((item) => item.code === typeCode)
    : catalogs.value.eventTypes[0]

  eventForm.value.event_type_id = type?.id ?? ''
  eventModalOpen.value = true

  if (catalogs.value.eventTypes.length === 0) fetchCatalogs()
}

function firstError(err) {
  const errors = err.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return err.response?.data?.message || 'No se pudo registrar el evento.'
}

async function saveEvent() {
  formError.value = ''

  if (!eventForm.value.event_type_id) {
    formError.value = 'Selecciona el tipo de evento.'
    return
  }

  saving.value = true

  try {
    await http.post(`/animals/${animal.value.id}/events`, {
      event_type_id: Number(eventForm.value.event_type_id),
      event_date: eventForm.value.event_date,
      weight_kg: eventForm.value.weight_kg !== '' ? Number(eventForm.value.weight_kg) : null,
      vaccine_id: eventForm.value.vaccine_id ? Number(eventForm.value.vaccine_id) : null,
      dose_ml: eventForm.value.dose_ml !== '' ? Number(eventForm.value.dose_ml) : null,
      batch_number: eventForm.value.batch_number || null,
      next_due_date: eventForm.value.next_due_date || null,
      diagnosis: eventForm.value.diagnosis || null,
      treatment: eventForm.value.treatment || null,
      cost: eventForm.value.cost !== '' ? Number(eventForm.value.cost) : null,
      notes: eventForm.value.notes || null,
    })

    eventModalOpen.value = false
    await fetchAnimal()
  } catch (err) {
    formError.value = firstError(err)
  } finally {
    saving.value = false
  }
}

function deltaLabel(current, previous) {
  if (previous == null) return '—'
  const delta = current - previous
  return `${delta > 0 ? '+' : ''}${delta.toFixed(2)} kg`
}

watch(() => route.params.id, fetchAnimal)

onMounted(() => {
  fetchAnimal()
  fetchCatalogs()
})
</script>

<template>
  <div v-if="loading" class="card flex items-center justify-center gap-2 p-12 text-sm text-unal-secondary-light">
    <Loader2 class="h-4 w-4 animate-spin" />
    Cargando animal…
  </div>

  <div v-else-if="animal" class="space-y-6">
    <RouterLink :to="{ name: 'animals' }" class="inline-flex items-center gap-2 text-sm font-medium text-unal-secondary-light hover:text-unal-primary">
      <ArrowLeft class="h-4 w-4" />
      Volver al inventario animal
    </RouterLink>

    <!-- Cabecera del animal -->
    <section class="card overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
        <div class="flex items-center gap-4">
          <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-unal-primary/10 text-2xl font-bold text-unal-primary">
            {{ (animal.name || animal.code).slice(0, 1) }}
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-2xl font-semibold text-unal-secondary">{{ animal.name || animal.code }}</h2>
              <span :class="statusClass(animal.status)">{{ statusLabel(animal.status) }}</span>
            </div>
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-unal-secondary-light">
              <span class="inline-flex items-center gap-1"><Tag class="h-3.5 w-3.5" /> {{ animal.code }}</span>
              <span class="inline-flex items-center gap-1"><MapPin class="h-3.5 w-3.5" /> {{ animal.lot?.name ?? 'Sin lote' }}</span>
              <span class="inline-flex items-center gap-1"><Dna class="h-3.5 w-3.5" /> {{ animal.breed?.name ?? animal.species?.name }}</span>
            </p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <button type="button" class="btn-secondary" @click="openEventModal()">
            <CalendarDays class="h-4 w-4" />
            Registrar evento
          </button>
          <button type="button" class="btn-primary" @click="openEventModal('pesaje')">
            <Plus class="h-4 w-4" />
            Registrar pesaje
          </button>
        </div>
      </div>
    </section>

    <!-- KPIs -->
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Peso actual</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">
          {{ animal.current_weight_kg ? `${animal.current_weight_kg} kg` : '—' }}
        </p>
        <p class="mt-1 text-xs text-unal-secondary-light">Último pesaje: {{ lastWeightAt }}</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Ganancia diaria</p>
        <p class="mt-2 text-2xl font-semibold text-unal-accent">{{ gmd }} g/día</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Según el último resumen mensual</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Edad</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ ageLabel }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Nacimiento: {{ animal.birth_date || '—' }}</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Próxima vacuna</p>
        <p class="mt-2 text-2xl font-semibold text-unal-primary">{{ animal.next_vaccine_at || '—' }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">{{ vaccines.at(-1)?.vaccine ?? 'Sin vacunas programadas' }}</p>
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
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.species?.name ?? '—' }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Raza</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.breed?.name ?? '—' }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Sexo</dt>
              <dd class="mt-1 font-medium capitalize text-unal-secondary">{{ animal.sex }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Padre</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.sire?.code ?? '—' }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Madre</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.dam?.code ?? '—' }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Arete / RFID</dt>
              <dd class="mt-1 font-medium text-unal-secondary">{{ animal.tag || animal.rfid || '—' }}</dd>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <dt class="text-xs text-unal-secondary-light">Peso al nacer</dt>
              <dd class="mt-1 font-medium text-unal-secondary">
                {{ animal.birth_weight_kg ? `${animal.birth_weight_kg} kg` : '—' }}
              </dd>
            </div>
          </dl>
          <p v-if="animal.notes" class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-unal-secondary-light">
            {{ animal.notes }}
          </p>
        </div>

        <div>
          <h3 class="mb-3 font-semibold text-unal-secondary">Evolución de peso</h3>
          <div v-if="weights.length" class="space-y-3">
            <div v-for="entry in weights" :key="entry.period" class="flex items-center gap-3">
              <span class="w-14 text-xs uppercase text-unal-secondary-light">{{ entry.month }}</span>
              <div class="h-2.5 flex-1 overflow-hidden rounded-full bg-gray-100">
                <div
                  class="h-full rounded-full bg-unal-accent transition-all"
                  :style="{ width: `${Math.max((entry.weight / maxWeight) * 100, 4)}%` }"
                />
              </div>
              <span class="w-20 text-right text-sm font-medium text-unal-secondary">{{ entry.weight }} kg</span>
            </div>
          </div>
          <p v-else class="text-sm text-unal-secondary-light">Aún no hay pesajes registrados.</p>
          <p class="mt-4 flex items-center gap-1 text-xs text-unal-secondary-light">
            <Scale class="h-3.5 w-3.5" />
            Últimos {{ weights.length }} meses de registro
          </p>
        </div>
      </div>

      <!-- Pesos -->
      <div v-else-if="activeTab === 'pesos'" class="p-5">
        <div class="overflow-x-auto">
          <table class="table-base min-w-[520px]">
            <thead>
              <tr>
                <th>Mes</th>
                <th>Peso</th>
                <th>Variación</th>
                <th>GMD promedio</th>
                <th>Eventos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(entry, index) in weights" :key="entry.period">
                <td class="capitalize">{{ entry.month }}</td>
                <td class="font-medium text-unal-secondary">{{ entry.weight }} kg</td>
                <td>
                  <span v-if="entry.gain == null" class="text-unal-secondary-light">—</span>
                  <span v-else :class="entry.gain >= 0 ? 'text-unal-accent' : 'text-red-600'">
                    {{ entry.gain > 0 ? '+' : '' }}{{ entry.gain }} kg
                  </span>
                </td>
                <td class="text-unal-secondary-light">{{ entry.dailyGain != null ? `${entry.dailyGain} g/día` : '—' }}</td>
                <td class="text-unal-secondary-light">{{ entry.eventsCount }}</td>
              </tr>
              <tr v-if="weights.length === 0">
                <td colspan="5" class="py-8 text-center text-sm text-unal-secondary-light">
                  Sin pesajes registrados.
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
                <th>Registrado por</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="vaccine in vaccines" :key="vaccine.id">
                <td class="font-medium text-unal-secondary">
                  <span class="inline-flex items-center gap-2"><Syringe class="h-4 w-4 text-unal-primary" /> {{ vaccine.vaccine }}</span>
                  <p v-if="vaccine.disease" class="text-xs text-unal-secondary-light">{{ vaccine.disease }}</p>
                </td>
                <td>{{ vaccine.appliedAt }}</td>
                <td>{{ vaccine.nextAt || '—' }}</td>
                <td>{{ vaccine.batch }}</td>
                <td class="text-unal-secondary-light">{{ vaccine.veterinarian }}</td>
              </tr>
              <tr v-if="vaccines.length === 0">
                <td colspan="5" class="py-8 text-center text-sm text-unal-secondary-light">
                  Sin vacunas registradas.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Eventos -->
      <div v-else class="p-5">
        <ol v-if="events.length" class="relative space-y-4 border-l border-unal-border pl-5">
          <li v-for="event in events" :key="event.id" class="relative">
            <span class="absolute -left-[27px] top-1 flex h-4 w-4 items-center justify-center rounded-full border-2 border-white bg-unal-primary" />
            <div class="flex flex-wrap items-center gap-2">
              <span class="badge-info">{{ event.type?.name ?? 'Evento' }}</span>
              <span class="inline-flex items-center gap-1 text-xs text-unal-secondary-light">
                <CalendarDays class="h-3.5 w-3.5" /> {{ event.event_date }}
              </span>
            </div>
            <p class="mt-1 text-sm text-unal-secondary">{{ eventDetail(event) }}</p>
            <p class="text-xs text-unal-secondary-light">Registrado por {{ event.created_by?.name ?? '—' }}</p>
          </li>
        </ol>
        <p v-else class="text-sm text-unal-secondary-light">Sin eventos registrados.</p>
      </div>
    </section>
  </div>

  <div v-else class="card flex flex-col items-center justify-center gap-3 p-12 text-center">
    <Scale class="h-10 w-10 text-unal-secondary-light" />
    <h2 class="text-lg font-semibold text-unal-secondary">Animal no encontrado</h2>
    <p class="max-w-md text-sm text-unal-secondary-light">
      {{ loadError }}
    </p>
    <RouterLink :to="{ name: 'animals' }" class="btn-primary mt-2">Volver al inventario</RouterLink>
  </div>

  <!-- Modal registrar evento -->
  <Teleport to="body">
    <div
      v-if="eventModalOpen"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="new-event-title"
      @click.self="eventModalOpen = false"
    >
      <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
          <div>
            <h3 id="new-event-title" class="font-semibold text-unal-secondary">Registrar evento</h3>
            <p class="text-xs text-unal-secondary-light">Pesos, vacunas, sanidad y reproducción</p>
          </div>
          <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="eventModalOpen = false">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="saveEvent">
          <div>
            <label class="label" for="event-type">Tipo de evento *</label>
            <select id="event-type" v-model="eventForm.event_type_id" class="input" required>
              <option value="" disabled>Selecciona un tipo</option>
              <option v-for="type in catalogs.eventTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
            </select>
          </div>
          <div>
            <label class="label" for="event-date">Fecha *</label>
            <input id="event-date" v-model="eventForm.event_date" type="date" class="input" required />
          </div>

          <div>
            <label class="label" for="event-weight">
              Peso (kg) {{ selectedType?.requires_weight ? '*' : '' }}
            </label>
            <input id="event-weight" v-model="eventForm.weight_kg" type="number" min="0" step="0.01" class="input" />
          </div>

          <div>
            <label class="label" for="event-vaccine">
              Vacuna {{ selectedType?.requires_vaccine ? '*' : '' }}
            </label>
            <select id="event-vaccine" v-model="eventForm.vaccine_id" class="input">
              <option value="">Sin vacuna</option>
              <option v-for="vaccine in catalogs.vaccines" :key="vaccine.id" :value="vaccine.id">
                {{ vaccine.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="label" for="event-dose">Dosis (ml)</label>
            <input id="event-dose" v-model="eventForm.dose_ml" type="number" min="0" step="0.01" class="input" />
          </div>
          <div>
            <label class="label" for="event-batch">Lote de vacuna</label>
            <input id="event-batch" v-model="eventForm.batch_number" class="input" placeholder="Ej. AF-2026-09" />
          </div>
          <div>
            <label class="label" for="event-next">Próxima dosis</label>
            <input id="event-next" v-model="eventForm.next_due_date" type="date" class="input" />
          </div>
          <div>
            <label class="label" for="event-cost">Costo</label>
            <input id="event-cost" v-model="eventForm.cost" type="number" min="0" step="1" class="input" placeholder="0" />
          </div>
          <div>
            <label class="label" for="event-diagnosis">Diagnóstico</label>
            <input id="event-diagnosis" v-model="eventForm.diagnosis" class="input" placeholder="Opcional" />
          </div>
          <div>
            <label class="label" for="event-treatment">Tratamiento</label>
            <input id="event-treatment" v-model="eventForm.treatment" class="input" placeholder="Opcional" />
          </div>
          <div class="sm:col-span-2">
            <label class="label" for="event-notes">Notas</label>
            <input id="event-notes" v-model="eventForm.notes" class="input" placeholder="Observaciones del evento" />
          </div>

          <p v-if="formError" class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ formError }}
          </p>

          <div class="flex justify-end gap-2 sm:col-span-2">
            <button type="button" class="btn-secondary" @click="eventModalOpen = false">Cancelar</button>
            <button type="submit" class="btn-primary" :disabled="saving">
              <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
              {{ saving ? 'Guardando…' : 'Guardar evento' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
