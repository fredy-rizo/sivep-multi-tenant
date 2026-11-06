import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/planes',
    name: 'plans',
    component: () => import('@/pages/PlansPage.vue'),
    meta: { title: 'Planes y precios' },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { guestOnly: true, title: 'Iniciar sesión' },
  },
  {
    path: '/',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: { name: 'dashboard' },
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('@/pages/DashboardPage.vue'),
        meta: { title: 'Dashboard', description: 'Resumen operativo de la organización' },
      },
      {
        path: 'ganado',
        name: 'animals',
        component: () => import('@/pages/animals/AnimalsPage.vue'),
        meta: { title: 'Ganado', description: 'Inventario animal, pesos, vacunas y eventos' },
      },
      {
        path: 'ganado/:id',
        name: 'animal-detail',
        component: () => import('@/pages/animals/AnimalDetailPage.vue'),
        meta: { title: 'Detalle del animal', description: 'Historial productivo y sanitario' },
      },
      {
        path: 'inventario',
        name: 'inventory',
        component: () => import('@/pages/InventoryPage.vue'),
        meta: { title: 'Inventario', description: 'Stock, movimientos y alertas' },
      },
      {
        path: 'ventas',
        name: 'sales',
        component: () => import('@/pages/SalesPage.vue'),
        meta: { title: 'Ventas', description: 'Registro y seguimiento de ventas' },
      },
      {
        path: 'clientes',
        name: 'customers',
        component: () => import('@/pages/CustomersPage.vue'),
        meta: { title: 'Clientes', description: 'Directorio y comprobantes de pago' },
      },
      {
        path: 'reportes',
        name: 'reports',
        component: () => import('@/pages/ReportsPage.vue'),
        meta: { title: 'Reportes', description: 'Indicadores operativos y exportaciones' },
      },
      {
        path: 'mi-plan',
        name: 'my-plan',
        component: () => import('@/pages/MyPlanPage.vue'),
        meta: { title: 'Mi plan', description: 'Suscripción, límites y consumo' },
      },
      {
        path: 'usuarios',
        name: 'users',
        component: () => import('@/pages/UsersPage.vue'),
        meta: { title: 'Usuarios', roles: ['super_admin', 'admin_granja'] },
      },
      {
        path: 'admin/monetizacion',
        name: 'admin-billing',
        component: () => import('@/pages/AdminBillingPage.vue'),
        meta: { title: 'Monetización', roles: ['super_admin'] },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
    meta: { title: 'Página no encontrada' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  await auth.initialize()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return {
      name: 'login',
      query: to.fullPath !== '/' ? { redirect: to.fullPath } : {},
    }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.roles && !auth.hasRole(...to.meta.roles)) {
    return { name: 'dashboard' }
  }

  return true
})

router.afterEach((to) => {
  const base = 'SIVEP'
  document.title = to.meta.title ? `${to.meta.title} · ${base}` : base
})

export default router
