<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Building2, LogOut, Menu } from '@lucide/vue'
import { useAuthStore } from '@/stores/auth'
import { useOrganizationStore } from '@/stores/organization'
import { usePlanStore } from '@/stores/plan'

defineEmits(['toggle-sidebar'])

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const organization = useOrganizationStore()
const plan = usePlanStore()

const title = computed(() => route.meta.title ?? 'SIVEP Agro')
const description = computed(() => route.meta.description ?? '')

const initials = computed(() =>
  auth.displayName
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase(),
)

function changeOrganization(event) {
  organization.select(event.target.value)
  plan.reset()
  window.location.reload()
}

async function logout() {
  await auth.logout()
  organization.reset()
  plan.reset()
  await router.replace({ name: 'login' })
}

onMounted(() => {
  if (auth.isSuperAdmin) organization.ensureSelected()
})
</script>

<template>
  <header class="sticky top-0 z-30 border-b border-unal-border bg-white/90 backdrop-blur">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
      <button
        type="button"
        class="rounded-lg p-2 text-unal-secondary hover:bg-gray-100 lg:hidden"
        aria-label="Abrir menú"
        @click="$emit('toggle-sidebar')"
      >
        <Menu class="h-5 w-5" />
      </button>

      <div class="min-w-0 flex-1">
        <h1 class="truncate text-base font-semibold text-unal-secondary sm:text-lg">{{ title }}</h1>
        <p v-if="description" class="hidden truncate text-xs text-unal-secondary-light sm:block">
          {{ description }}
        </p>
      </div>

      <label
        v-if="auth.isSuperAdmin"
        class="hidden items-center gap-2 rounded-lg border border-unal-border px-2 py-1.5 text-xs text-unal-secondary md:flex"
        title="Organización activa"
      >
        <Building2 class="h-4 w-4 text-unal-primary" />
        <select
          class="max-w-52 bg-transparent text-sm font-medium text-unal-secondary focus:outline-none"
          :value="organization.selectedId ?? ''"
          aria-label="Organización activa"
          @change="changeOrganization"
        >
          <option v-if="!organization.selectedId" value="" disabled>Selecciona organización</option>
          <option
            v-for="item in organization.organizations"
            :key="item.id"
            :value="item.id"
          >
            {{ item.name }}
          </option>
        </select>
      </label>

      <div class="hidden items-center gap-3 sm:flex">
        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-unal-primary/10 text-sm font-semibold text-unal-primary">
          {{ initials }}
        </div>
        <div class="leading-tight">
          <p class="text-sm font-medium text-unal-secondary">{{ auth.displayName }}</p>
          <p class="text-xs capitalize text-unal-secondary-light">
            {{ auth.user?.role?.replace('_', ' ') }}
          </p>
        </div>
      </div>

      <button
        type="button"
        class="rounded-lg p-2 text-unal-secondary hover:bg-gray-100"
        aria-label="Cerrar sesión"
        title="Cerrar sesión"
        @click="logout"
      >
        <LogOut class="h-5 w-5" />
      </button>
    </div>
  </header>
</template>
