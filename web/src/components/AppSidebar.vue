<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import {
  BadgeDollarSign,
  BarChart3,
  Beef,
  CreditCard,
  Crown,
  LayoutDashboard,
  LogOut,
  Package,
  Settings,
  ShoppingCart,
  Users,
  X,
} from '@lucide/vue'
import LogoShield from '@/components/LogoShield.vue'
import { useAuthStore } from '@/stores/auth'
import { usePlanStore } from '@/stores/plan'

defineProps({
  open: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close'])

const auth = useAuthStore()
const plan = usePlanStore()

const navigation = [
  { name: 'Dashboard', to: { name: 'dashboard' }, icon: LayoutDashboard },
  { name: 'Ganado', to: { name: 'animals' }, icon: Beef },
  { name: 'Inventario', to: { name: 'inventory' }, icon: Package },
  { name: 'Ventas', to: { name: 'sales' }, icon: ShoppingCart },
  { name: 'Clientes', to: { name: 'customers' }, icon: Users },
  { name: 'Reportes', to: { name: 'reports' }, icon: BarChart3 },
  { name: 'Mi plan', to: { name: 'my-plan' }, icon: CreditCard },
]

const adminNavigation = [
  { name: 'Usuarios', to: { name: 'users' }, icon: Settings },
]

const superAdminNavigation = [
  { name: 'Monetización', to: { name: 'admin-billing' }, icon: BadgeDollarSign },
]

const canManageUsers = computed(
  () => auth.isSuperAdmin || (auth.isAdminGranja && plan.hasFeature('users.multi')),
)

const items = computed(() => {
  const base = [...navigation]

  if (canManageUsers.value) base.push(adminNavigation[0])
  if (auth.isSuperAdmin) base.push(superAdminNavigation[0])

  return base
})

const initials = computed(() => {
  const name = auth.displayName
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
})

async function handleLogout() {
  await auth.logout()
  plan.reset()
  emit('close')
}
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-50 flex w-64 max-w-[85vw] flex-col border-r border-unal-border bg-white transition-transform duration-300 lg:translate-x-0"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="flex h-16 items-center justify-between border-b border-unal-border px-4">
      <RouterLink :to="{ name: 'dashboard' }" class="flex items-center gap-3">
        <LogoShield class="h-10 w-10" />
        <div class="leading-tight">
          <span class="block text-lg font-bold text-unal-primary">SIVEP</span>
          <span class="block text-[10px] font-medium uppercase tracking-widest text-unal-secondary-light">
            Gestión integral
          </span>
        </div>
      </RouterLink>
      <button
        type="button"
        class="rounded-lg p-2 text-unal-secondary hover:bg-gray-100 lg:hidden"
        aria-label="Cerrar menú"
        @click="emit('close')"
      >
        <X class="h-5 w-5" />
      </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
      <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-unal-secondary-light">
        Operación
      </p>
      <RouterLink
        v-for="item in items"
        :key="item.name"
        :to="item.to"
        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-unal-secondary transition-colors hover:bg-gray-100 hover:text-unal-primary"
        active-class="bg-unal-primary text-white hover:bg-unal-primary-dark hover:text-white"
        @click="emit('close')"
      >
        <component :is="item.icon" class="h-5 w-5 shrink-0" :stroke-width="2.2" />
        {{ item.name }}
      </RouterLink>
    </nav>

    <div class="border-t border-unal-border p-4">
      <div v-if="plan.planName" class="pb-3">
        <span class="badge-info">
          <Crown class="h-3 w-3" />
          Plan {{ plan.planName }}
        </span>
      </div>

      <div class="mb-3 flex items-center gap-3 px-1">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-unal-primary/10 text-sm font-semibold text-unal-primary">
          {{ initials }}
        </div>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium text-unal-secondary">{{ auth.displayName }}</p>
          <p class="truncate text-xs capitalize text-unal-secondary-light">
            {{ auth.user?.role?.replace('_', ' ') }}
          </p>
        </div>
      </div>

      <button
        type="button"
        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-unal-secondary transition-colors hover:bg-gray-100"
        @click="handleLogout"
      >
        <LogOut class="h-4 w-4" />
        Cerrar sesión
      </button>
    </div>
  </aside>
</template>
