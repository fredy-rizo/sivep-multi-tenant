<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Eye, EyeOff, Loader2, LogIn, ShieldCheck } from '@lucide/vue'
import LogoShield from '@/components/LogoShield.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const form = ref({
  email: '',
  password: '',
})
const showPassword = ref(false)
const localError = ref('')

async function handleSubmit() {
  localError.value = ''

  if (!form.value.email || !form.value.password) {
    localError.value = 'Ingresa tu correo y contraseña.'
    return
  }

  try {
    await auth.login(form.value)
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null
    await router.push(redirect || { name: 'dashboard' })
  } catch {
    // El store ya normalizó el mensaje en auth.error.
  }
}
</script>

<template>
  <div class="grid min-h-screen bg-unal-background lg:grid-cols-2">
    <!-- Panel institucional -->
    <section class="relative hidden overflow-hidden bg-unal-primary p-10 text-white lg:flex lg:flex-col lg:justify-between">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,.18),transparent_45%)]" />
      <div class="relative">
        <div class="flex items-center gap-3">
          <LogoShield class="h-12 w-12" />
          <div>
            <p class="text-2xl font-bold tracking-tight">SIVEP</p>
            <p class="text-xs uppercase tracking-[0.25em] text-white/70">Agro</p>
          </div>
        </div>

        <h1 class="mt-16 max-w-md text-4xl font-semibold leading-tight">
          Todo tu campo, organizado en un solo lugar.
        </h1>
        <p class="mt-4 max-w-md text-sm leading-relaxed text-white/80">
          SIVEP Agro reúne el ganado, el inventario, las ventas y los pedidos en
          línea de tu organización en una sola plataforma multi-tenant.
        </p>
      </div>

      <div class="relative grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-xl border border-white/15 bg-white/10 p-4 backdrop-blur">
          <p class="font-semibold">Trazabilidad animal</p>
          <p class="mt-1 text-xs text-white/70">ID único, genealogía y eventos por mes.</p>
        </div>
        <div class="rounded-xl border border-white/15 bg-white/10 p-4 backdrop-blur">
          <p class="font-semibold">Multi-tenant</p>
          <p class="mt-1 text-xs text-white/70">Datos aislados por organización.</p>
        </div>
      </div>

      <p class="relative text-xs text-white/60">
        Un producto de SIVEP Tech
      </p>
    </section>

    <!-- Formulario -->
    <section class="flex items-center justify-center px-4 py-10 sm:px-8">
      <div class="w-full max-w-md">
        <div class="mb-8 flex items-center gap-3 lg:hidden">
          <LogoShield class="h-11 w-11" />
          <div>
            <p class="text-xl font-bold text-unal-primary">SIVEP</p>
            <p class="text-[10px] uppercase tracking-[0.2em] text-unal-secondary-light">
              Agro
            </p>
          </div>
        </div>

        <div class="card p-6 sm:p-8">
          <div class="mb-6">
            <h2 class="text-2xl font-semibold text-unal-secondary">Iniciar sesión</h2>
            <p class="mt-1 text-sm text-unal-secondary-light">
              Accede con tu cuenta de la organización.
            </p>
          </div>

          <form class="space-y-4" @submit.prevent="handleSubmit">
            <div>
              <label for="email" class="label">Correo electrónico</label>
              <input
                id="email"
                v-model.trim="form.email"
                type="email"
                autocomplete="username"
                class="input"
                placeholder="usuario@organizacion.com"
                required
              />
            </div>

            <div>
              <label for="password" class="label">Contraseña</label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="current-password"
                  class="input pr-11"
                  placeholder="••••••••"
                  required
                />
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-unal-secondary-light hover:text-unal-secondary"
                  :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                  @click="showPassword = !showPassword"
                >
                  <EyeOff v-if="showPassword" class="h-4 w-4" />
                  <Eye v-else class="h-4 w-4" />
                </button>
              </div>
            </div>

            <p
              v-if="localError || auth.error"
              class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
              role="alert"
            >
              {{ localError || auth.error }}
            </p>

            <button type="submit" class="btn-primary w-full" :disabled="auth.loading">
              <Loader2 v-if="auth.loading" class="h-4 w-4 animate-spin" />
              <LogIn v-else class="h-4 w-4" />
              {{ auth.loading ? 'Ingresando…' : 'Ingresar' }}
            </button>
          </form>

          <div class="mt-6 flex items-start gap-2 rounded-lg bg-gray-50 p-3 text-xs text-unal-secondary-light">
            <ShieldCheck class="mt-0.5 h-4 w-4 shrink-0 text-unal-accent" />
            <p>
              Sesión protegida con cookies HttpOnly y CSRF. No almacenamos tu
              contraseña ni tokens en el navegador.
            </p>
          </div>
        </div>

        <p class="mt-6 text-center text-xs text-unal-secondary-light">
          ¿Problemas para ingresar? Contacta al administrador de tu organización.
        </p>
      </div>
    </section>
  </div>
</template>
