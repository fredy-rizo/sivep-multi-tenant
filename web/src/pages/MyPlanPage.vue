<script setup>
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Check, Crown, Info, Loader2, X } from '@lucide/vue'
import { usePlanStore } from '@/stores/plan'

const plan = usePlanStore()

const features = computed(() => plan.features)
const limits = computed(() => ({}))

const featureLabels = {
  'livestock.core': 'Gestión de ganado',
  'livestock.weights': 'Control de pesos',
  'livestock.vaccines': 'Vacunas y sanidad',
  'livestock.reports': 'Reportes ganaderos',
  'inventory.core': 'Inventario',
  'sales.core': 'Ventas',
  'clients.core': 'Clientes',
  'users.multi': 'Usuarios ilimitados',
}

onMounted(() => {
  plan.fetchPlan(true)
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-semibold text-unal-secondary">Mi plan</h2>
      <p class="mt-1 text-sm text-unal-secondary-light">
        Suscripción, límites y funciones incluidas para tu organización.
      </p>
    </div>

    <div v-if="plan.loading" class="card flex items-center justify-center gap-2 p-10 text-sm text-unal-secondary-light">
      <Loader2 class="h-4 w-4 animate-spin" />
      Cargando plan…
    </div>

    <template v-else-if="plan.plan">
      <section class="card overflow-hidden">
        <div class="flex flex-col justify-between gap-4 bg-unal-primary px-6 py-6 text-white sm:flex-row sm:items-center">
          <div>
            <p class="flex items-center gap-2 text-xs uppercase tracking-[0.2em] text-white/70">
              <Crown class="h-4 w-4" /> Plan actual
            </p>
            <h3 class="mt-2 text-2xl font-semibold">{{ plan.planName || 'Sin plan' }}</h3>
            <p class="mt-1 text-sm text-white/80">
              {{ plan.isActive ? 'Suscripción activa' : 'Sin suscripción vigente' }}
            </p>
          </div>
          <RouterLink to="/planes" class="btn bg-white text-unal-primary hover:bg-gray-100">
            Ver planes disponibles
          </RouterLink>
        </div>

        <div class="grid gap-6 p-6 lg:grid-cols-2">
          <div>
            <h4 class="mb-3 text-sm font-semibold text-unal-secondary">Funciones incluidas</h4>
            <ul class="space-y-2 text-sm">
              <li v-for="feature in features" :key="feature" class="flex items-center gap-2 text-unal-secondary">
                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-unal-accent/10 text-unal-accent">
                  <Check class="h-3 w-3" />
                </span>
                {{ featureLabels[feature] || feature }}
              </li>
              <li v-if="features.length === 0" class="flex items-center gap-2 text-unal-secondary-light">
                <X class="h-4 w-4" /> No hay funciones activas.
              </li>
            </ul>
          </div>

          <div>
            <h4 class="mb-3 text-sm font-semibold text-unal-secondary">Límites</h4>
            <dl class="space-y-2 text-sm">
              <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-unal-secondary-light">Plan</dt>
                <dd class="font-medium text-unal-secondary">{{ plan.planName || '—' }}</dd>
              </div>
              <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-unal-secondary-light">Días restantes</dt>
                <dd class="font-medium text-unal-secondary">{{ plan.daysLeft ?? '—' }}</dd>
              </div>
              <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                <dt class="text-unal-secondary-light">Acceso API</dt>
                <dd class="font-medium text-unal-secondary">Sí</dd>
              </div>
            </dl>
          </div>
        </div>
      </section>

      <div class="flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-800">
        <Info class="mt-0.5 h-4 w-4 shrink-0" />
        <p>
          El plan se consulta a <code>/api/v1/billing/current</code>. El backend
          valida las funciones incluidas en cada petición.
        </p>
      </div>
    </template>

    <section v-else class="card flex flex-col items-center justify-center gap-3 p-12 text-center">
      <Crown class="h-10 w-10 text-unal-secondary-light" />
      <h3 class="text-lg font-semibold text-unal-secondary">Sin plan cargado</h3>
      <p class="max-w-md text-sm text-unal-secondary-light">
        No hay una suscripción disponible para mostrar o el backend aún no está
        conectado. Cuando el API esté listo, aquí verás el plan de tu organización.
      </p>
      <RouterLink to="/planes" class="btn-primary mt-2">Explorar planes</RouterLink>
    </section>
  </div>
</template>
