<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowRight,
  Beef,
  CalendarClock,
  CircleDollarSign,
  Info,
  Package,
  PawPrint,
  Plus,
  Scale,
  Syringe,
  TrendingUp,
} from '@lucide/vue'
import http from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { usePlanStore } from '@/stores/plan'

const auth = useAuthStore()
const plan = usePlanStore()
const summary = ref(null)
const farm = ref(null)

const firstName = computed(() => auth.displayName.split(' ')[0])

const kpis = computed(() => [
  {
    label: 'Animales activos',
    value: summary.value?.active_animals ?? '—',
    hint: `${summary.value?.total_animals ?? 0} animales registrados`,
    icon: Beef,
    tone: 'text-unal-primary bg-unal-primary/10',
  },
  {
    label: 'Peso promedio',
    value: summary.value?.average_weight_kg ? `${Math.round(summary.value.average_weight_kg)} kg` : '—',
    hint: 'Último pesaje por animal',
    icon: Scale,
    tone: 'text-unal-accent bg-unal-accent/10',
  },
  {
    label: 'Vacunas próximas',
    value: summary.value?.upcoming_vaccines_30d ?? '—',
    hint: 'Próximos 30 días',
    icon: Syringe,
    tone: 'text-amber-700 bg-amber-100',
  },
  {
    label: 'Ventas del mes',
    value: farm.value?.sales?.month_total != null
      ? new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(farm.value.sales.month_total)
      : '—',
    hint: `${farm.value?.sales?.month_count ?? 0} ventas registradas`,
    icon: CircleDollarSign,
    tone: 'text-sky-700 bg-sky-100',
  },
])

onMounted(async () => {
  const [livestockResponse, farmResponse] = await Promise.allSettled([
    http.get('/reports/summary'),
    http.get('/reports/farm'),
  ])

  if (livestockResponse.status === 'fulfilled') {
    summary.value = livestockResponse.value.data.data
  }

  if (farmResponse.status === 'fulfilled') {
    farm.value = farmResponse.value.data.data
  }
})

const quickActions = [
  {
    label: 'Registrar animal',
    description: 'Alta con ID único y datos productivos',
    to: { name: 'animals' },
    icon: PawPrint,
  },
  {
    label: 'Registrar pesaje',
    description: 'Peso del mes y ganancia diaria',
    to: { name: 'animals' },
    icon: Scale,
  },
  {
    label: 'Nueva venta',
    description: 'Venta de productos o animales',
    to: { name: 'sales' },
    icon: TrendingUp,
  },
  {
    label: 'Movimiento de stock',
    description: 'Entrada, salida o ajuste de inventario',
    to: { name: 'inventory' },
    icon: Package,
  },
]
</script>

<template>
  <div class="space-y-6">
    <!-- Bienvenida -->
    <section class="card overflow-hidden">
      <div class="relative bg-unal-primary px-6 py-6 text-white sm:px-8">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,.2),transparent_40%)]" />
        <div class="relative flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
          <div>
            <p class="text-xs uppercase tracking-[0.2em] text-white/70">Panel de control</p>
            <h2 class="mt-1 text-2xl font-semibold">Hola, {{ firstName }}</h2>
            <p class="mt-1 max-w-xl text-sm text-white/80">
              Resumen operativo de tu organización. Los indicadores se actualizan en
              tiempo real desde la API de Laravel.
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <span v-if="plan.planName" class="rounded-full bg-white/15 px-3 py-1 text-xs font-medium">
              Plan {{ plan.planName }}
            </span>
            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-medium">
              {{ auth.user?.role?.replace('_', ' ') }}
            </span>
          </div>
        </div>
      </div>

      <div class="flex items-start gap-2 border-t border-unal-border bg-unal-primary/5 px-6 py-3 text-xs text-unal-secondary">
        <Info class="mt-0.5 h-4 w-4 shrink-0 text-unal-primary" />
        <p>
          Los indicadores ganaderos, de inventario y de ventas se consultan en
          tiempo real desde la API de Laravel.
        </p>
      </div>
    </section>

    <!-- KPIs -->
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article v-for="kpi in kpis" :key="kpi.label" class="card p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-sm font-medium text-unal-secondary-light">{{ kpi.label }}</p>
            <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ kpi.value }}</p>
          </div>
          <div class="flex h-10 w-10 items-center justify-center rounded-lg" :class="kpi.tone">
            <component :is="kpi.icon" class="h-5 w-5" />
          </div>
        </div>
        <p class="mt-3 text-xs text-unal-secondary-light">{{ kpi.hint }}</p>
      </article>
    </section>

    <div class="grid gap-6 xl:grid-cols-3">
      <!-- Accesos rápidos -->
      <section class="card xl:col-span-2">
        <header class="flex items-center justify-between border-b border-unal-border px-5 py-4">
          <div>
            <h3 class="font-semibold text-unal-secondary">Accesos rápidos</h3>
            <p class="text-xs text-unal-secondary-light">Tareas frecuentes de la operación</p>
          </div>
          <Plus class="h-5 w-5 text-unal-secondary-light" />
        </header>

        <div class="grid gap-3 p-5 sm:grid-cols-2">
          <RouterLink
            v-for="action in quickActions"
            :key="action.label"
            :to="action.to"
            class="group flex items-start gap-3 rounded-xl border border-unal-border p-4 transition hover:border-unal-primary/30 hover:bg-unal-primary/[0.03]"
          >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-unal-primary/10 text-unal-primary">
              <component :is="action.icon" class="h-5 w-5" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-semibold text-unal-secondary">{{ action.label }}</p>
              <p class="mt-0.5 text-xs text-unal-secondary-light">{{ action.description }}</p>
            </div>
            <ArrowRight class="mt-1 h-4 w-4 shrink-0 text-unal-secondary-light transition group-hover:translate-x-0.5 group-hover:text-unal-primary" />
          </RouterLink>
        </div>
      </section>

      <!-- Estado del proyecto -->
      <section class="card">
        <header class="border-b border-unal-border px-5 py-4">
          <h3 class="font-semibold text-unal-secondary">Estado de la reescritura</h3>
          <p class="text-xs text-unal-secondary-light">Avance de la nueva plataforma</p>
        </header>

        <div class="space-y-3 p-5">
          <div class="flex items-start gap-3">
            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-unal-accent" />
            <div>
              <p class="text-sm font-medium text-unal-secondary">Frontend Vue 3</p>
              <p class="text-xs text-unal-secondary-light">Login, ganado, inventario, ventas, clientes y reportes conectados al API.</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-unal-accent" />
            <div>
              <p class="text-sm font-medium text-unal-secondary">Backend Laravel</p>
              <p class="text-xs text-unal-secondary-light">API v1, Sanctum, multi-tenant y permisos.</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-unal-accent" />
            <div>
              <p class="text-sm font-medium text-unal-secondary">Módulo ganadero</p>
              <p class="text-xs text-unal-secondary-light">CRUD, eventos, pesos y resúmenes mensuales.</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-unal-accent" />
            <div>
              <p class="text-sm font-medium text-unal-secondary">Núcleo de granja</p>
              <p class="text-xs text-unal-secondary-light">Productos, stock, clientes, ventas y anulaciones.</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <span class="mt-1 h-2.5 w-2.5 rounded-full bg-unal-accent" />
            <div>
              <p class="text-sm font-medium text-unal-secondary">Suscripciones y planes</p>
              <p class="text-xs text-unal-secondary-light">Pago manual y gating por feature.</p>
            </div>
          </div>
        </div>

        <div class="border-t border-unal-border px-5 py-4">
          <div class="flex items-center gap-2 text-xs text-unal-secondary-light">
            <CalendarClock class="h-4 w-4" />
            Fase actual: portal público, archivos y analítica avanzada
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
