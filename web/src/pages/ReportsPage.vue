<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  AlertTriangle,
  ArrowRight,
  BarChart3,
  Beef,
  CircleDollarSign,
  Crown,
  Loader2,
  Package,
  TrendingUp,
} from '@lucide/vue'
import http from '@/api/http'

const farm = ref(null)
const livestock = ref(null)
const loading = ref(true)
const farmError = ref('')

const maxTrend = computed(() => {
  const values = (farm.value?.sales_trend ?? []).map((item) => Number(item.total || 0))
  return Math.max(...values, 1)
})

const speciesBreakdown = computed(() => {
  const rows = livestock.value?.by_species ?? []
  const total = rows.reduce((sum, row) => sum + Number(row.total || 0), 0) || 1
  return rows.map((row) => ({ ...row, share: Math.round((Number(row.total) / total) * 100) }))
})

function money(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function number(value) {
  return new Intl.NumberFormat('es-CO', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

function monthLabel(period) {
  const [year, month] = period.split('-')
  const date = new Date(Number(year), Number(month) - 1, 1)
  return date.toLocaleDateString('es-CO', { month: 'short', year: '2-digit' })
}

function barHeight(value) {
  return `${Math.max(6, Math.round((Number(value || 0) / maxTrend.value) * 100))}%`
}

onMounted(async () => {
  try {
    const [farmResponse, livestockResponse] = await Promise.allSettled([
      http.get('/reports/farm'),
      http.get('/reports/summary'),
    ])

    if (farmResponse.status === 'fulfilled') {
      farm.value = farmResponse.value.data.data
    } else {
      farmError.value =
        farmResponse.reason?.response?.status === 402
          ? 'Tu plan no incluye los reportes comerciales.'
          : 'No se pudieron cargar los reportes comerciales.'
    }

    if (livestockResponse.status === 'fulfilled') {
      livestock.value = livestockResponse.value.data.data
    }
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <h2 class="text-xl font-semibold text-unal-secondary">Reportes</h2>
        <p class="mt-1 text-sm text-unal-secondary-light">
          Indicadores de ventas, inventario y ganado de tu organización.
        </p>
      </div>
      <RouterLink :to="{ name: 'my-plan' }" class="btn-secondary">
        <Crown class="h-4 w-4" />
        Ver mi plan
      </RouterLink>
    </div>

    <div v-if="loading" class="card flex items-center justify-center gap-2 py-16 text-sm text-unal-secondary-light">
      <Loader2 class="h-4 w-4 animate-spin" /> Cargando reportes…
    </div>

    <template v-else>
      <div v-if="farmError" class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between">
        <span class="inline-flex items-center gap-2"><AlertTriangle class="h-4 w-4" /> {{ farmError }}</span>
        <RouterLink :to="{ name: 'plans' }" class="inline-flex items-center gap-1 font-medium text-amber-900 hover:underline">
          Ver planes <ArrowRight class="h-3.5 w-3.5" />
        </RouterLink>
      </div>

      <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="card p-5">
          <p class="text-sm text-unal-secondary-light">Ventas del mes</p>
          <p class="mt-2 text-2xl font-semibold text-unal-accent">{{ money(farm?.sales?.month_total) }}</p>
          <p class="mt-1 text-xs text-unal-secondary-light">{{ farm?.sales?.month_count ?? 0 }} ventas</p>
        </article>
        <article class="card p-5">
          <p class="text-sm text-unal-secondary-light">Valor del inventario</p>
          <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ money(farm?.inventory?.value) }}</p>
          <p class="mt-1 text-xs text-unal-secondary-light">{{ farm?.inventory?.products ?? 0 }} productos activos</p>
        </article>
        <article class="card p-5">
          <p class="text-sm text-unal-secondary-light">Animales registrados</p>
          <p class="mt-2 text-2xl font-semibold text-unal-primary">{{ livestock?.total_animals ?? '—' }}</p>
          <p class="mt-1 text-xs text-unal-secondary-light">{{ livestock?.active_animals ?? 0 }} activos</p>
        </article>
        <article class="card p-5">
          <p class="text-sm text-unal-secondary-light">Alertas de stock</p>
          <p class="mt-2 text-2xl font-semibold" :class="farm?.inventory?.low_stock_count ? 'text-red-600' : 'text-unal-secondary'">
            {{ farm?.inventory?.low_stock_count ?? '—' }}
          </p>
          <p class="mt-1 text-xs text-unal-secondary-light">Productos en mínimo</p>
        </article>
      </section>

      <div class="grid gap-6 xl:grid-cols-5">
        <section class="card xl:col-span-3">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">Tendencia de ventas</h3>
              <p class="text-xs text-unal-secondary-light">Últimos 6 meses</p>
            </div>
            <TrendingUp class="h-5 w-5 text-unal-accent" />
          </div>
          <div class="flex h-64 items-end gap-4 px-5 py-6">
            <div v-for="item in farm?.sales_trend ?? []" :key="item.period" class="flex h-full flex-1 flex-col items-center justify-end gap-2">
              <span class="text-xs font-medium text-unal-secondary">{{ money(item.total) }}</span>
              <div class="flex w-full max-w-16 flex-1 items-end">
                <div class="w-full rounded-t-lg bg-unal-primary/80 transition-all" :style="{ height: barHeight(item.total) }" />
              </div>
              <span class="text-xs capitalize text-unal-secondary-light">{{ monthLabel(item.period) }}</span>
            </div>
            <div v-if="(farm?.sales_trend ?? []).length === 0" class="flex h-full w-full items-center justify-center text-sm text-unal-secondary-light">
              Sin ventas registradas en el periodo.
            </div>
          </div>
        </section>

        <section class="card xl:col-span-2">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">Productos más vendidos</h3>
              <p class="text-xs text-unal-secondary-light">Últimos 90 días</p>
            </div>
            <BarChart3 class="h-5 w-5 text-unal-secondary-light" />
          </div>
          <div class="divide-y divide-unal-border">
            <div v-for="(product, index) in farm?.top_products ?? []" :key="product.id" class="flex items-center gap-3 px-5 py-3">
              <span class="flex h-7 w-7 items-center justify-center rounded-full bg-unal-primary/10 text-xs font-semibold text-unal-primary">
                {{ index + 1 }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-unal-secondary">{{ product.name }}</p>
                <p class="text-xs text-unal-secondary-light">{{ number(product.quantity) }} unidades</p>
              </div>
              <span class="text-sm font-semibold text-unal-secondary">{{ money(product.total) }}</span>
            </div>
            <div v-if="(farm?.top_products ?? []).length === 0" class="px-5 py-10 text-center text-sm text-unal-secondary-light">
              Sin ventas de productos todavía.
            </div>
          </div>
        </section>
      </div>

      <div class="grid gap-6 xl:grid-cols-3">
        <section class="card">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <h3 class="font-semibold text-unal-secondary">Ganado por especie</h3>
            <Beef class="h-5 w-5 text-unal-primary" />
          </div>
          <div class="space-y-4 p-5">
            <div v-for="row in speciesBreakdown" :key="row.name">
              <div class="mb-1 flex items-center justify-between text-sm">
                <span class="text-unal-secondary">{{ row.name }}</span>
                <span class="font-medium text-unal-secondary">{{ row.total }} · {{ row.share }}%</span>
              </div>
              <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-unal-primary" :style="{ width: `${row.share}%` }" />
              </div>
            </div>
            <p v-if="speciesBreakdown.length === 0" class="text-center text-sm text-unal-secondary-light">Sin datos de ganado.</p>
          </div>
        </section>

        <section class="card">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <h3 class="font-semibold text-unal-secondary">Productos en alerta</h3>
            <AlertTriangle class="h-5 w-5 text-amber-600" />
          </div>
          <div class="divide-y divide-unal-border">
            <div v-for="product in farm?.low_stock ?? []" :key="product.id" class="flex items-center justify-between gap-3 px-5 py-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-unal-secondary">{{ product.name }}</p>
                <p class="text-xs text-unal-secondary-light">{{ product.sku }}</p>
              </div>
              <span class="badge-danger">{{ number(product.stock) }} / {{ number(product.min_stock) }}</span>
            </div>
            <div v-if="(farm?.low_stock ?? []).length === 0" class="px-5 py-10 text-center text-sm text-unal-secondary-light">
              Sin alertas de stock.
            </div>
          </div>
        </section>

        <section class="card">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <h3 class="font-semibold text-unal-secondary">Últimas ventas</h3>
            <CircleDollarSign class="h-5 w-5 text-unal-secondary-light" />
          </div>
          <div class="divide-y divide-unal-border">
            <div v-for="sale in farm?.recent_sales ?? []" :key="sale.id" class="flex items-center justify-between gap-3 px-5 py-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-unal-secondary">{{ sale.code }}</p>
                <p class="truncate text-xs text-unal-secondary-light">{{ sale.customer?.name || 'Consumidor final' }} · {{ sale.sale_date }}</p>
              </div>
              <span class="text-sm font-semibold text-unal-secondary">{{ money(sale.total) }}</span>
            </div>
            <div v-if="(farm?.recent_sales ?? []).length === 0" class="px-5 py-10 text-center text-sm text-unal-secondary-light">
              Sin ventas registradas.
            </div>
          </div>
        </section>
      </div>

      <section class="card">
        <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
          <div>
            <h3 class="font-semibold text-unal-secondary">Resumen sanitario</h3>
            <p class="text-xs text-unal-secondary-light">Indicadores del módulo ganadero</p>
          </div>
          <Package class="h-5 w-5 text-unal-secondary-light" />
        </div>
        <div class="grid gap-4 p-5 sm:grid-cols-3">
          <div class="rounded-lg bg-gray-50 p-4">
            <p class="text-xs text-unal-secondary-light">Peso promedio</p>
            <p class="mt-1 text-xl font-semibold text-unal-secondary">
              {{ livestock?.average_weight_kg ? `${number(livestock.average_weight_kg)} kg` : '—' }}
            </p>
          </div>
          <div class="rounded-lg bg-gray-50 p-4">
            <p class="text-xs text-unal-secondary-light">Vacunas próximas (30 días)</p>
            <p class="mt-1 text-xl font-semibold text-unal-secondary">{{ livestock?.upcoming_vaccines_30d ?? '—' }}</p>
          </div>
          <div class="rounded-lg bg-gray-50 p-4">
            <p class="text-xs text-unal-secondary-light">Animales activos</p>
            <p class="mt-1 text-xl font-semibold text-unal-secondary">{{ livestock?.active_animals ?? '—' }}</p>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>
