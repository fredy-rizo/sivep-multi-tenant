<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowLeft, Check, Crown, Loader2, Sparkles } from '@lucide/vue'
import http from '@/api/http'
import LogoShield from '@/components/LogoShield.vue'

const plans = ref([])
const loading = ref(true)
const error = ref('')

function formatCOP(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value) || 0)
}

onMounted(async () => {
  try {
    const { data } = await http.get('/plans')
    plans.value = (data.data ?? []).map((plan) => ({
      ...plan,
      highlighted: plan.code === 'intermedio',
    }))
  } catch {
    error.value = 'No se pudieron cargar los planes. Intenta de nuevo más tarde.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-unal-background">
    <header class="border-b border-unal-border bg-white">
      <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
        <RouterLink to="/" class="flex items-center gap-3">
          <LogoShield class="h-10 w-10" />
          <div class="leading-tight">
            <span class="block text-lg font-bold text-unal-primary">SIVEP</span>
            <span class="block text-[10px] font-medium uppercase tracking-widest text-unal-secondary-light">
              Gestión integral
            </span>
          </div>
        </RouterLink>
        <RouterLink to="/login" class="btn-secondary">
          <ArrowLeft class="h-4 w-4" />
          Iniciar sesión
        </RouterLink>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
      <div class="mx-auto max-w-2xl text-center">
        <span class="badge-info">
          <Sparkles class="h-3.5 w-3.5" />
          Planes y precios
        </span>
        <h1 class="mt-4 text-3xl font-semibold text-unal-secondary sm:text-4xl">
          Una plataforma que crece con tu organización
        </h1>
        <p class="mt-3 text-sm leading-relaxed text-unal-secondary-light sm:text-base">
          Inventario, ventas, gestión ganadera y analítica avanzada. Elige el plan
          que se ajuste a tu operación y cambia cuando lo necesites.
        </p>
      </div>

      <div v-if="loading" class="mt-10 flex items-center justify-center gap-2 text-sm text-unal-secondary-light">
        <Loader2 class="h-4 w-4 animate-spin" />
        Cargando planes…
      </div>

      <div v-else-if="error" class="mt-10 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-center text-sm text-red-700">
        {{ error }}
      </div>

      <div v-else class="mt-10 grid gap-6 lg:grid-cols-3">
        <article
          v-for="plan in plans"
          :key="plan.code"
          class="card relative flex flex-col p-6"
          :class="plan.highlighted ? 'border-unal-primary ring-2 ring-unal-primary/20' : ''"
        >
          <div v-if="plan.highlighted" class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="inline-flex items-center gap-1 rounded-full bg-unal-primary px-3 py-1 text-xs font-semibold text-white">
              <Crown class="h-3.5 w-3.5" /> Recomendado
            </span>
          </div>

          <h2 class="text-xl font-semibold text-unal-secondary">{{ plan.name }}</h2>
          <p class="mt-1 text-sm text-unal-secondary-light">{{ plan.description }}</p>

          <div class="mt-5">
            <p class="text-3xl font-bold text-unal-primary">{{ formatCOP(plan.price_monthly) }}</p>
            <p class="text-xs text-unal-secondary-light">por mes · COP</p>
            <p class="mt-1 text-xs text-unal-secondary-light">
              o {{ formatCOP(plan.price_yearly) }} al año
            </p>
          </div>

          <ul class="mt-6 flex-1 space-y-2.5 text-sm">
            <li v-for="feature in plan.features" :key="feature.code" class="flex items-start gap-2 text-unal-secondary">
              <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-unal-accent/10 text-unal-accent">
                <Check class="h-3 w-3" />
              </span>
              {{ feature.name }}
            </li>
          </ul>

          <RouterLink
            to="/login"
            class="mt-6 w-full"
            :class="plan.highlighted ? 'btn-primary' : 'btn-secondary'"
          >
            Comenzar con {{ plan.name }}
          </RouterLink>
          <p class="mt-2 text-center text-xs text-unal-secondary-light">
            {{ plan.trial_days ? `${plan.trial_days} días de prueba` : 'Sin periodo de prueba' }}
          </p>
        </article>
      </div>

      <div class="mt-10 rounded-xl border border-unal-border bg-white p-6 text-center">
        <h2 class="text-lg font-semibold text-unal-secondary">¿Necesitas una licencia on-premise?</h2>
        <p class="mx-auto mt-2 max-w-2xl text-sm text-unal-secondary-light">
          Ofrecemos licencia perpetua con mantenimiento, implementación en sitio y
          migración de datos. Escríbenos para preparar una propuesta a la medida.
        </p>
      </div>
    </main>
  </div>
</template>
