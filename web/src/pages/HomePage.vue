<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ArrowRight,
  BarChart3,
  Beef,
  Building2,
  Check,
  ChevronDown,
  ClipboardList,
  Crown,
  Database,
  FileCheck2,
  Headphones,
  Loader2,
  Lock,
  Mail,
  MapPin,
  Menu,
  Package,
  Phone,
  Quote,
  ShieldCheck,
  ShoppingCart,
  Sparkles,
  Store,
  Users,
  X,
} from '@lucide/vue'
import http from '@/api/http'
import LogoShield from '@/components/LogoShield.vue'

const plans = ref([])
const plansLoading = ref(true)
const plansError = ref('')
const mobileOpen = ref(false)

const modules = [
  {
    icon: Beef,
    title: 'Gestión ganadera',
    description:
      'Animales con ID único, pesos mensuales, vacunas, eventos sanitarios y trazabilidad por lotes.',
  },
  {
    icon: Package,
    title: 'Inventario',
    description:
      'Stock en tiempo real, movimientos de entrada y salida, alertas de mínimos y costos.',
  },
  {
    icon: ShoppingCart,
    title: 'Ventas',
    description:
      'Ventas de productos y animales, anulaciones con reposición de stock y control de cartera.',
  },
  {
    icon: Users,
    title: 'Clientes',
    description:
      'Directorio con historial de compras, documentos, contacto y comportamiento comercial.',
  },
  {
    icon: BarChart3,
    title: 'Reportes',
    description:
      'Indicadores de producción, evolución de pesos, vacunas próximas y desempeño de ventas.',
  },
  {
    icon: Store,
    title: 'Portal público',
    description:
      'Catálogo en línea por organización para recibir pedidos con comprobante y aprobarlos.',
  },
]

const clientProfiles = [
  {
    icon: Beef,
    title: 'Ganadería de cría y levante',
    description:
      'Controla el ciclo productivo de bovinos, porcinos, caprinos, ovinos, equinos y aves en una sola plataforma.',
    points: ['Pesajes y ganancia diaria', 'Sanidad y vacunas', 'Lotes y especies configurables'],
  },
  {
    icon: Package,
    title: 'Granjas de producción',
    description:
      'Conecta inventario, producción y ventas para saber qué tienes, qué vendiste y cuánto ganaste.',
    points: ['Stock y alertas', 'Clientes y ventas', 'Reportes operativos'],
  },
  {
    icon: Building2,
    title: 'Comercializadoras locales',
    description:
      'Publica un catálogo, recibe pedidos de tus compradores y valida cada comprobante antes de vender.',
    points: ['Catálogo público', 'Comprobantes privados', 'Aprobación con trazabilidad'],
  },
]

const faqs = [
  {
    question: '¿Qué es SIVEP?',
    answer:
      'SIVEP es una plataforma de gestión integral para organizaciones agropecuarias. Reúne ganado, inventario, clientes, ventas, reportes y un portal público de pedidos.',
  },
  {
    question: '¿Puedo usar SIVEP con varias organizaciones?',
    answer:
      'Sí. El modelo es multi-tenant: cada organización tiene sus propios datos, usuarios, plan y catálogo. El super administrador puede dar soporte seleccionando una organización sin mezclar información.',
  },
  {
    question: '¿Necesito instalar algo?',
    answer:
      'No. SIVEP funciona en el navegador. También ofrecemos licencia on-premise para organizaciones que necesitan instalar la plataforma en sus propios servidores.',
  },
  {
    question: '¿Cómo funcionan los planes?',
    answer:
      'Cada plan habilita un conjunto de funciones. El Básico incluye ganado y pesos; el Intermedio agrega sanidad, reportes y multiusuario; el Avanzado suma inventario, ventas, clientes y portal público. Puedes cambiar de plan cuando lo necesites.',
  },
  {
    question: '¿Los comprobantes de pago son seguros?',
    answer:
      'Sí. Los comprobantes del portal público se guardan en almacenamiento privado y solo se descargan desde la bandeja autenticada de la organización. No se exponen como archivos públicos.',
  },
  {
    question: '¿Puedo migrar mis datos actuales?',
    answer:
      'La plataforma nueva parte de una base limpia para garantizar consistencia. Para migraciones desde el sistema anterior o desde hojas de cálculo, ofrecemos acompañamiento en la implementación.',
  },
  {
    question: '¿Qué soporte incluye?',
    answer:
      'Todos los planes incluyen soporte por correo y documentación. La licencia on-premise incluye mantenimiento, implementación y acompañamiento para la puesta en marcha.',
  },
]

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
    plansError.value = 'No pudimos cargar los planes en este momento.'
  } finally {
    plansLoading.value = false
  }
})
</script>

<template>
  <div class="min-h-screen bg-unal-background">
    <header class="sticky top-0 z-40 border-b border-unal-border bg-white/95 backdrop-blur">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
        <RouterLink to="/" class="flex items-center gap-3">
          <LogoShield class="h-10 w-10" />
          <div class="leading-tight">
            <span class="block text-lg font-bold text-unal-primary">SIVEP</span>
            <span class="block text-[10px] font-medium uppercase tracking-widest text-unal-secondary-light">
              Gestión integral
            </span>
          </div>
        </RouterLink>

        <nav class="hidden items-center gap-6 text-sm font-medium text-unal-secondary lg:flex">
          <a href="#modulos" class="hover:text-unal-primary">Módulos</a>
          <a href="#planes" class="hover:text-unal-primary">Planes</a>
          <a href="#clientes" class="hover:text-unal-primary">Clientes</a>
          <a href="#informacion" class="hover:text-unal-primary">Información</a>
          <a href="#faq" class="hover:text-unal-primary">Preguntas</a>
        </nav>

        <div class="hidden items-center gap-2 sm:flex">
          <RouterLink to="/login" class="btn-secondary">Iniciar sesión</RouterLink>
          <RouterLink to="/planes" class="btn-primary">
            Ver planes
            <ArrowRight class="h-4 w-4" />
          </RouterLink>
        </div>

        <button
          type="button"
          class="rounded-lg p-2 text-unal-secondary hover:bg-gray-100 sm:hidden"
          :aria-label="mobileOpen ? 'Cerrar menú' : 'Abrir menú'"
          @click="mobileOpen = !mobileOpen"
        >
          <X v-if="mobileOpen" class="h-5 w-5" />
          <Menu v-else class="h-5 w-5" />
        </button>
      </div>

      <div v-if="mobileOpen" class="border-t border-unal-border bg-white px-4 py-4 sm:hidden">
        <nav class="space-y-1 text-sm font-medium text-unal-secondary">
          <a href="#modulos" class="block rounded-lg px-3 py-2 hover:bg-gray-50" @click="mobileOpen = false">Módulos</a>
          <a href="#planes" class="block rounded-lg px-3 py-2 hover:bg-gray-50" @click="mobileOpen = false">Planes</a>
          <a href="#clientes" class="block rounded-lg px-3 py-2 hover:bg-gray-50" @click="mobileOpen = false">Clientes</a>
          <a href="#informacion" class="block rounded-lg px-3 py-2 hover:bg-gray-50" @click="mobileOpen = false">Información</a>
          <a href="#faq" class="block rounded-lg px-3 py-2 hover:bg-gray-50" @click="mobileOpen = false">Preguntas</a>
        </nav>
        <div class="mt-3 grid grid-cols-2 gap-2">
          <RouterLink to="/login" class="btn-secondary">Iniciar sesión</RouterLink>
          <RouterLink to="/planes" class="btn-primary">Ver planes</RouterLink>
        </div>
      </div>
    </header>

    <main>
      <!-- Hero -->
      <section class="relative overflow-hidden bg-unal-primary text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,.18),transparent_45%)]" />
        <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:py-24">
          <div>
            <span class="badge bg-white/15 text-white">
              <Sparkles class="h-3.5 w-3.5" />
              Plataforma agropecuaria multi-organización
            </span>
            <h1 class="mt-5 max-w-2xl text-4xl font-bold leading-tight sm:text-5xl">
              Todo tu campo, organizado en un solo lugar.
            </h1>
            <p class="mt-5 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg">
              SIVEP conecta el ganado, el inventario, los clientes, las ventas y los
              pedidos en línea de tu organización. Menos planillas, más control para
              tomar mejores decisiones.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
              <RouterLink to="/login" class="btn bg-white text-unal-primary hover:bg-white/90">
                Comenzar ahora
                <ArrowRight class="h-4 w-4" />
              </RouterLink>
              <a href="#planes" class="btn border border-white/25 text-white hover:bg-white/10">
                Explorar planes
              </a>
            </div>

            <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-white/80">
              <li class="inline-flex items-center gap-2">
                <Check class="h-4 w-4 text-white" /> Datos aislados por organización
              </li>
              <li class="inline-flex items-center gap-2">
                <Check class="h-4 w-4 text-white" /> Comprobantes privados
              </li>
              <li class="inline-flex items-center gap-2">
                <Check class="h-4 w-4 text-white" /> Roles y permisos granulares
              </li>
            </ul>
          </div>

          <div class="relative">
            <div class="rounded-2xl bg-white/10 p-4 shadow-2xl ring-1 ring-white/20 backdrop-blur">
              <div class="rounded-xl bg-white p-5 text-unal-secondary">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-unal-secondary-light">Panel de control</p>
                    <p class="mt-1 text-lg font-semibold">Finca La Esperanza</p>
                  </div>
                  <span class="badge-success">Plan Avanzado</span>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                  <div class="rounded-xl border border-unal-border bg-gray-50 p-4">
                    <div class="flex items-center gap-2 text-unal-secondary-light">
                      <Beef class="h-4 w-4" />
                      <span class="text-xs font-medium">Animales activos</span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-unal-secondary">10</p>
                    <p class="text-xs text-unal-secondary-light">6 especies controladas</p>
                  </div>
                  <div class="rounded-xl border border-unal-border bg-gray-50 p-4">
                    <div class="flex items-center gap-2 text-unal-secondary-light">
                      <ShoppingCart class="h-4 w-4" />
                      <span class="text-xs font-medium">Ventas del mes</span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-unal-primary">$ 86.000</p>
                    <p class="text-xs text-unal-secondary-light">Actualizado en tiempo real</p>
                  </div>
                </div>

                <div class="mt-3 space-y-2 rounded-xl border border-unal-border p-4">
                  <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2 text-unal-secondary">
                      <ClipboardList class="h-4 w-4 text-unal-primary" /> Pedidos pendientes
                    </span>
                    <span class="badge-warning">1 por revisar</span>
                  </div>
                  <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2 text-unal-secondary">
                      <Package class="h-4 w-4 text-unal-primary" /> Alertas de inventario
                    </span>
                    <span class="badge-danger">2 productos bajos</span>
                  </div>
                  <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2 text-unal-secondary">
                      <FileCheck2 class="h-4 w-4 text-unal-primary" /> Comprobantes validados
                    </span>
                    <span class="badge-success">Al día</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Módulos -->
      <section id="modulos" class="scroll-mt-24 px-4 py-16 sm:px-6 lg:py-24">
        <div class="mx-auto max-w-7xl">
          <div class="max-w-2xl">
            <span class="badge-info">Módulos</span>
            <h2 class="mt-4 text-3xl font-semibold text-unal-secondary sm:text-4xl">
              Una plataforma para toda la operación
            </h2>
            <p class="mt-3 text-sm leading-relaxed text-unal-secondary-light sm:text-base">
              Cada módulo comparte la misma base de datos y respeta el aislamiento
              por organización. Activa solo lo que tu plan necesita.
            </p>
          </div>

          <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="module in modules" :key="module.title" class="card p-6">
              <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-unal-primary/10 text-unal-primary">
                <component :is="module.icon" class="h-5 w-5" />
              </div>
              <h3 class="mt-4 text-lg font-semibold text-unal-secondary">{{ module.title }}</h3>
              <p class="mt-2 text-sm leading-relaxed text-unal-secondary-light">{{ module.description }}</p>
            </article>
          </div>
        </div>
      </section>

      <!-- Planes -->
      <section id="planes" class="scroll-mt-24 border-y border-unal-border bg-white px-4 py-16 sm:px-6 lg:py-24">
        <div class="mx-auto max-w-7xl">
          <div class="mx-auto max-w-2xl text-center">
            <span class="badge-info">
              <Crown class="h-3.5 w-3.5" />
              Planes y precios
            </span>
            <h2 class="mt-4 text-3xl font-semibold text-unal-secondary sm:text-4xl">
              Crece al ritmo de tu organización
            </h2>
            <p class="mt-3 text-sm leading-relaxed text-unal-secondary-light sm:text-base">
              Empieza con lo esencial y habilita sanidad, reportes, inventario,
              ventas o el portal público cuando lo necesites.
            </p>
          </div>

          <div v-if="plansLoading" class="mt-10 flex items-center justify-center gap-2 text-sm text-unal-secondary-light">
            <Loader2 class="h-4 w-4 animate-spin" />
            Cargando planes…
          </div>

          <div v-else-if="plansError" class="mx-auto mt-10 max-w-xl rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-center text-sm text-red-700">
            {{ plansError }}
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

              <h3 class="text-xl font-semibold text-unal-secondary">{{ plan.name }}</h3>
              <p class="mt-1 min-h-10 text-sm text-unal-secondary-light">{{ plan.description }}</p>

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
            </article>
          </div>

          <p class="mt-8 text-center text-sm text-unal-secondary-light">
            ¿Necesitas una licencia on-premise?
            <RouterLink to="/planes" class="font-medium text-unal-primary hover:underline">
              Ver condiciones
            </RouterLink>
          </p>
        </div>
      </section>

      <!-- Clientes / perfiles -->
      <section id="clientes" class="scroll-mt-24 px-4 py-16 sm:px-6 lg:py-24">
        <div class="mx-auto max-w-7xl">
          <div class="grid gap-10 lg:grid-cols-[1fr_1.4fr] lg:items-start">
            <div>
              <span class="badge-info">Clientes</span>
              <h2 class="mt-4 text-3xl font-semibold text-unal-secondary sm:text-4xl">
                Pensado para quien trabaja el campo
              </h2>
              <p class="mt-3 text-sm leading-relaxed text-unal-secondary-light sm:text-base">
                Desde fincas familiares hasta organizaciones con varios usuarios y
                puntos de venta. SIVEP se adapta al tamaño de tu operación.
              </p>

              <div class="mt-6 rounded-xl border border-unal-border bg-white p-5">
                <Quote class="h-6 w-6 text-unal-primary/40" />
                <p class="mt-2 text-sm italic leading-relaxed text-unal-secondary">
                  “Antes llevábamos pesos, vacunas y ventas en cuadernos separados.
                  Ahora todo el equipo consulta la misma información y los pedidos
                  llegan con su comprobante.”
                </p>
                <p class="mt-3 text-sm font-medium text-unal-secondary">Equipo de Finca La Esperanza</p>
                <p class="text-xs text-unal-secondary-light">Organización de demostración · Montería</p>
              </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <article v-for="profile in clientProfiles" :key="profile.title" class="card p-5">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-unal-accent/10 text-unal-accent">
                  <component :is="profile.icon" class="h-5 w-5" />
                </div>
                <h3 class="mt-3 font-semibold text-unal-secondary">{{ profile.title }}</h3>
                <p class="mt-1 text-sm text-unal-secondary-light">{{ profile.description }}</p>
                <ul class="mt-3 space-y-1.5 text-sm text-unal-secondary">
                  <li v-for="point in profile.points" :key="point" class="flex items-center gap-2">
                    <Check class="h-3.5 w-3.5 text-unal-accent" />
                    {{ point }}
                  </li>
                </ul>
              </article>

              <article class="card flex flex-col justify-center bg-unal-primary p-5 text-white sm:col-span-2">
                <p class="text-sm font-medium uppercase tracking-widest text-white/70">Cobertura</p>
                <p class="mt-2 text-2xl font-semibold">Multiespecie y multi-organización</p>
                <p class="mt-2 text-sm text-white/80">
                  Bovinos, porcinos, caprinos, ovinos, equinos y aves. Cada
                  organización con su propio catálogo, usuarios y plan.
                </p>
              </article>
            </div>
          </div>
        </div>
      </section>

      <!-- Información relacionada -->
      <section id="informacion" class="scroll-mt-24 border-y border-unal-border bg-white px-4 py-16 sm:px-6 lg:py-24">
        <div class="mx-auto max-w-7xl">
          <div class="max-w-2xl">
            <span class="badge-info">Información relacionada</span>
            <h2 class="mt-4 text-3xl font-semibold text-unal-secondary sm:text-4xl">
              Confianza, control y acompañamiento
            </h2>
            <p class="mt-3 text-sm leading-relaxed text-unal-secondary-light sm:text-base">
              La operación de tu organización es sensible. Por eso la seguridad, el
              aislamiento de datos y el soporte hacen parte del producto.
            </p>
          </div>

          <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article class="card p-5">
              <ShieldCheck class="h-6 w-6 text-unal-primary" />
              <h3 class="mt-3 font-semibold text-unal-secondary">Aislamiento multi-tenant</h3>
              <p class="mt-1 text-sm text-unal-secondary-light">
                Cada consulta se filtra por organización. Un usuario nunca ve datos
                de otra finca, aunque existan en la misma base.
              </p>
            </article>
            <article class="card p-5">
              <Lock class="h-6 w-6 text-unal-primary" />
              <h3 class="mt-3 font-semibold text-unal-secondary">Sesión y permisos</h3>
              <p class="mt-1 text-sm text-unal-secondary-light">
                Cookies HttpOnly, protección CSRF y permisos por rol. El backend
                siempre revalida lo que el frontend oculta.
              </p>
            </article>
            <article class="card p-5">
              <Database class="h-6 w-6 text-unal-primary" />
              <h3 class="mt-3 font-semibold text-unal-secondary">Licencia on-premise</h3>
              <p class="mt-1 text-sm text-unal-secondary-light">
                Instala SIVEP en tus servidores, con mantenimiento, implementación en
                sitio y acompañamiento en la migración.
              </p>
            </article>
            <article class="card p-5">
              <Headphones class="h-6 w-6 text-unal-primary" />
              <h3 class="mt-3 font-semibold text-unal-secondary">Soporte cercano</h3>
              <p class="mt-1 text-sm text-unal-secondary-light">
                Documentación, acompañamiento por correo y apoyo para configurar
                catálogos, usuarios y planes.
              </p>
            </article>
          </div>
        </div>
      </section>

      <!-- FAQ -->
      <section id="faq" class="scroll-mt-24 px-4 py-16 sm:px-6 lg:py-24">
        <div class="mx-auto max-w-4xl">
          <div class="text-center">
            <span class="badge-info">Preguntas frecuentes</span>
            <h2 class="mt-4 text-3xl font-semibold text-unal-secondary sm:text-4xl">
              Resolvemos tus dudas
            </h2>
            <p class="mt-3 text-sm text-unal-secondary-light sm:text-base">
              Si necesitas algo más, escríbenos y te ayudamos a elegir el plan
              adecuado.
            </p>
          </div>

          <div class="mt-10 space-y-3">
            <details
              v-for="faq in faqs"
              :key="faq.question"
              class="group card overflow-hidden"
            >
              <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-sm font-medium text-unal-secondary">
                {{ faq.question }}
                <ChevronDown class="h-4 w-4 shrink-0 text-unal-secondary-light transition-transform group-open:rotate-180" />
              </summary>
              <div class="border-t border-unal-border px-5 py-4 text-sm leading-relaxed text-unal-secondary-light">
                {{ faq.answer }}
              </div>
            </details>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="bg-unal-primary px-4 py-14 text-white sm:px-6">
        <div class="mx-auto flex max-w-5xl flex-col items-center justify-between gap-6 text-center lg:flex-row lg:text-left">
          <div>
            <h2 class="text-2xl font-semibold sm:text-3xl">¿Listo para ordenar tu operación?</h2>
            <p class="mt-2 max-w-2xl text-sm text-white/80">
              Ingresa a la plataforma o revisa los planes para encontrar el punto de
              partida de tu organización.
            </p>
          </div>
          <div class="flex flex-wrap justify-center gap-3">
            <RouterLink to="/login" class="btn bg-white text-unal-primary hover:bg-white/90">
              Iniciar sesión
              <ArrowRight class="h-4 w-4" />
            </RouterLink>
            <RouterLink to="/planes" class="btn border border-white/25 text-white hover:bg-white/10">
              Ver planes
            </RouterLink>
          </div>
        </div>
      </section>
    </main>

    <footer class="bg-white px-4 py-10 sm:px-6">
      <div class="mx-auto grid max-w-7xl gap-8 sm:grid-cols-2 lg:grid-cols-4">
        <div>
          <div class="flex items-center gap-3">
            <LogoShield class="h-10 w-10" />
            <div class="leading-tight">
              <span class="block text-lg font-bold text-unal-primary">SIVEP</span>
              <span class="block text-[10px] font-medium uppercase tracking-widest text-unal-secondary-light">
                Gestión integral
              </span>
            </div>
          </div>
          <p class="mt-3 text-sm leading-relaxed text-unal-secondary-light">
            Plataforma multi-organización para la gestión ganadera, inventario,
            ventas y pedidos en línea.
          </p>
        </div>

        <div>
          <p class="text-sm font-semibold text-unal-secondary">Plataforma</p>
          <ul class="mt-3 space-y-2 text-sm text-unal-secondary-light">
            <li><a href="#modulos" class="hover:text-unal-primary">Módulos</a></li>
            <li><a href="#planes" class="hover:text-unal-primary">Planes</a></li>
            <li><a href="#clientes" class="hover:text-unal-primary">Clientes</a></li>
            <li><a href="#faq" class="hover:text-unal-primary">Preguntas frecuentes</a></li>
          </ul>
        </div>

        <div>
          <p class="text-sm font-semibold text-unal-secondary">Acceso</p>
          <ul class="mt-3 space-y-2 text-sm text-unal-secondary-light">
            <li><RouterLink to="/login" class="hover:text-unal-primary">Iniciar sesión</RouterLink></li>
            <li><RouterLink to="/planes" class="hover:text-unal-primary">Planes y precios</RouterLink></li>
            <li><RouterLink to="/catalogo/finca-la-esperanza" class="hover:text-unal-primary">Catálogo de ejemplo</RouterLink></li>
          </ul>
        </div>

        <div>
          <p class="text-sm font-semibold text-unal-secondary">Contacto</p>
          <ul class="mt-3 space-y-2 text-sm text-unal-secondary-light">
            <li class="flex items-center gap-2">
              <MapPin class="h-4 w-4 text-unal-primary" /> Montería, Colombia
            </li>
            <li class="flex items-center gap-2">
              <Mail class="h-4 w-4 text-unal-primary" /> contacto@sivep.local
            </li>
            <li class="flex items-center gap-2">
              <Phone class="h-4 w-4 text-unal-primary" /> +57 300 123 4567
            </li>
          </ul>
        </div>
      </div>

      <div class="mx-auto mt-8 flex max-w-7xl flex-col items-center justify-between gap-2 border-t border-unal-border pt-6 text-xs text-unal-secondary-light sm:flex-row">
        <p>© 2026 SIVEP · Universidad Nacional de Colombia — Sede Orinoquía</p>
        <p>Hecho para organizaciones agropecuarias.</p>
      </div>
    </footer>
  </div>
</template>
