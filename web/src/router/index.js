import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/pages/HomePage.vue'),
    meta: { title: 'Gestión ganadera y de granja', publicPage: true },
  },
  {
    path: '/planes',
    name: 'plans',
    component: () => import('@/pages/PlansPage.vue'),
    meta: { title: 'Planes y precios', publicPage: true },
  },
  {
    path: '/catalogo/:slug',
    name: 'public-catalog',
    component: () => import('@/pages/PublicCatalogPage.vue'),
    meta: { title: 'Catálogo de pedidos', publicPage: true },
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/LoginPage.vue'),
    meta: { guestOnly: true, title: 'Iniciar sesión' },
  },
  {
    path: '/app',
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
        path: 'pedidos',
        name: 'public-orders',
        component: () => import('@/pages/PublicOrdersPage.vue'),
        meta: {
          title: 'Pedidos públicos',
          description: 'Pedidos del catálogo en línea y comprobantes',
          roles: ['super_admin', 'admin_granja', 'veterinario'],
        },
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
  // Compatibilidad con enlaces antiguos del panel.
  { path: '/dashboard', redirect: '/app/dashboard' },
  { path: '/ganado', redirect: '/app/ganado' },
  { path: '/ganado/:id', redirect: (to) => ({ name: 'animal-detail', params: { id: to.params.id } }) },
  { path: '/inventario', redirect: '/app/inventario' },
  { path: '/ventas', redirect: '/app/ventas' },
  { path: '/clientes', redirect: '/app/clientes' },
  { path: '/pedidos', redirect: '/app/pedidos' },
  { path: '/reportes', redirect: '/app/reportes' },
  { path: '/mi-plan', redirect: '/app/mi-plan' },
  { path: '/usuarios', redirect: '/app/usuarios' },
  { path: '/admin/monetizacion', redirect: '/app/admin/monetizacion' },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
    meta: { title: 'Página no encontrada', publicPage: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!to.meta.publicPage) {
    await auth.initialize()
  }

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
