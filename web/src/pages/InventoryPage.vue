<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import {
  ArrowDownCircle,
  ArrowUpCircle,
  Boxes,
  CircleDollarSign,
  Loader2,
  Package,
  Plus,
  Search,
  SlidersHorizontal,
  X,
} from '@lucide/vue'
import http from '@/api/http'

const products = ref([])
const movements = ref([])
const meta = ref(null)
const summary = ref(null)
const loading = ref(true)
const movementsLoading = ref(true)
const listError = ref('')
const search = ref('')
const categoryFilter = ref('')
const lowStockOnly = ref(false)

const modalOpen = ref(false)
const saving = ref(false)
const formError = ref('')
const form = ref(emptyProduct())

const movementOpen = ref(false)
const movementSaving = ref(false)
const movementError = ref('')
const movementForm = ref(emptyMovement())

const categories = computed(() => {
  const values = products.value.map((product) => product.category).filter(Boolean)
  return [...new Set(values)].sort()
})

const stockTone = (product) =>
  product.is_low_stock ? 'badge-danger' : Number(product.stock) === 0 ? 'badge-warning' : 'badge-success'

function emptyProduct() {
  return {
    sku: '',
    name: '',
    category: '',
    unit: 'unidad',
    price: '',
    cost: '',
    stock: '',
    min_stock: '',
    description: '',
  }
}

function emptyMovement() {
  return {
    product_id: '',
    type: 'entrada',
    quantity: '',
    unit_cost: '',
    reason: '',
    movement_date: new Date().toISOString().slice(0, 10),
  }
}

let searchTimer = null
watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => fetchProducts(), 350)
})
watch([categoryFilter, lowStockOnly], () => fetchProducts())

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

function firstError(err) {
  const errors = err.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return err.response?.data?.message || 'No se pudo completar la operación.'
}

async function fetchProducts() {
  loading.value = true
  listError.value = ''
  try {
    const { data } = await http.get('/products', {
      params: {
        search: search.value || undefined,
        category: categoryFilter.value || undefined,
        low_stock: lowStockOnly.value ? 1 : undefined,
        is_active: 1,
        per_page: 100,
      },
    })
    products.value = data.data
    meta.value = data.meta
  } catch (err) {
    listError.value = firstError(err)
  } finally {
    loading.value = false
  }
}

async function fetchSummary() {
  try {
    const { data } = await http.get('/inventory/summary')
    summary.value = data.data
  } catch {
    summary.value = null
  }
}

async function fetchMovements() {
  movementsLoading.value = true
  try {
    const { data } = await http.get('/inventory/movements', { params: { per_page: 10 } })
    movements.value = data.data
  } catch {
    movements.value = []
  } finally {
    movementsLoading.value = false
  }
}

async function refresh() {
  await Promise.all([fetchProducts(), fetchSummary(), fetchMovements()])
}

function openProductModal() {
  form.value = emptyProduct()
  formError.value = ''
  modalOpen.value = true
}

async function saveProduct() {
  formError.value = ''
  if (!form.value.sku.trim() || !form.value.name.trim()) {
    formError.value = 'El SKU y el nombre son obligatorios.'
    return
  }

  saving.value = true
  try {
    await http.post('/products', {
      sku: form.value.sku.trim(),
      name: form.value.name.trim(),
      category: form.value.category.trim() || null,
      unit: form.value.unit || 'unidad',
      price: Number(form.value.price || 0),
      cost: form.value.cost === '' ? null : Number(form.value.cost),
      stock: Number(form.value.stock || 0),
      min_stock: Number(form.value.min_stock || 0),
      description: form.value.description || null,
    })
    modalOpen.value = false
    await refresh()
  } catch (err) {
    formError.value = firstError(err)
  } finally {
    saving.value = false
  }
}

function openMovement(product = null, type = 'entrada') {
  movementForm.value = { ...emptyMovement(), type, product_id: product?.id ?? '' }
  movementError.value = ''
  movementOpen.value = true
  if (!product) return
  if (type === 'entrada' && product.cost) movementForm.value.unit_cost = product.cost
}

function selectedProduct() {
  return products.value.find((product) => product.id === Number(movementForm.value.product_id))
}

async function saveMovement() {
  movementError.value = ''
  if (!movementForm.value.product_id || !movementForm.value.quantity) {
    movementError.value = 'Selecciona un producto e indica la cantidad.'
    return
  }

  movementSaving.value = true
  try {
    await http.post('/inventory/movements', {
      product_id: Number(movementForm.value.product_id),
      type: movementForm.value.type,
      quantity: Number(movementForm.value.quantity),
      unit_cost:
        movementForm.value.type === 'entrada' && movementForm.value.unit_cost !== ''
          ? Number(movementForm.value.unit_cost)
          : null,
      reason: movementForm.value.reason || null,
      movement_date: movementForm.value.movement_date,
    })
    movementOpen.value = false
    await refresh()
  } catch (err) {
    movementError.value = firstError(err)
  } finally {
    movementSaving.value = false
  }
}

function movementLabel(type) {
  return { entrada: 'Entrada', salida: 'Salida', ajuste: 'Ajuste' }[type] ?? type
}

function movementClass(type) {
  return { entrada: 'badge-success', salida: 'badge-danger', ajuste: 'badge-info' }[type] ?? 'badge-info'
}

onMounted(refresh)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <h2 class="text-xl font-semibold text-unal-secondary">Inventario</h2>
        <p class="mt-1 text-sm text-unal-secondary-light">
          Stock, movimientos y alertas de reposición de tu organización.
        </p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="btn-secondary" @click="openMovement()">
          <Boxes class="h-4 w-4" />
          Movimiento
        </button>
        <button type="button" class="btn-primary" @click="openProductModal">
          <Plus class="h-4 w-4" />
          Nuevo producto
        </button>
      </div>
    </div>

    <div v-if="listError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
      {{ listError }}
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Productos activos</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary?.products ?? '—' }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Catálogo disponible</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Unidades en stock</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ number(summary?.units) }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Suma de existencias</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Valor del inventario</p>
        <p class="mt-2 text-2xl font-semibold text-unal-accent">{{ money(summary?.inventory_value) }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Costo o precio de referencia</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Alertas de stock</p>
        <p class="mt-2 text-2xl font-semibold" :class="summary?.low_stock_count ? 'text-red-600' : 'text-unal-secondary'">
          {{ summary?.low_stock_count ?? '—' }}
        </p>
        <p class="mt-1 text-xs text-unal-secondary-light">En o por debajo del mínimo</p>
      </article>
    </section>

    <section class="card">
      <div class="flex flex-col gap-3 border-b border-unal-border p-4 lg:flex-row lg:items-center">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-unal-secondary-light" />
          <input v-model="search" type="search" class="input pl-9" placeholder="Buscar por SKU, nombre o categoría…" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <SlidersHorizontal class="hidden h-4 w-4 text-unal-secondary-light sm:block" />
          <select v-model="categoryFilter" class="input w-auto min-w-40">
            <option value="">Todas las categorías</option>
            <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
          </select>
          <label class="inline-flex items-center gap-2 rounded-lg border border-unal-border px-3 py-2 text-sm">
            <input v-model="lowStockOnly" type="checkbox" class="h-4 w-4 accent-unal-primary" />
            Solo alertas
          </label>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="table-base min-w-[820px]">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Categoría</th>
              <th>Stock</th>
              <th>Mínimo</th>
              <th>Precio</th>
              <th>Costo</th>
              <th>Estado</th>
              <th class="w-32"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="py-12 text-center text-sm text-unal-secondary-light">
                <span class="inline-flex items-center gap-2"><Loader2 class="h-4 w-4 animate-spin" /> Cargando inventario…</span>
              </td>
            </tr>
            <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
              <td>
                <div class="flex items-center gap-3">
                  <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-unal-primary/10 text-unal-primary">
                    <Package class="h-4 w-4" />
                  </div>
                  <div>
                    <p class="font-medium text-unal-secondary">{{ product.name }}</p>
                    <p class="text-xs text-unal-secondary-light">{{ product.sku }}</p>
                  </div>
                </div>
              </td>
              <td>{{ product.category || '—' }}</td>
              <td>
                <span :class="stockTone(product)">{{ number(product.stock) }} {{ product.unit }}</span>
              </td>
              <td class="text-unal-secondary-light">{{ number(product.min_stock) }}</td>
              <td class="font-medium text-unal-secondary">{{ money(product.price) }}</td>
              <td class="text-unal-secondary-light">{{ product.cost ? money(product.cost) : '—' }}</td>
              <td>
                <span :class="product.is_active ? 'badge-success' : 'badge-warning'">
                  {{ product.is_active ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td>
                <div class="flex justify-end gap-1">
                  <button type="button" class="rounded p-1.5 text-unal-accent hover:bg-gray-100" title="Entrada" @click="openMovement(product, 'entrada')">
                    <ArrowDownCircle class="h-4 w-4" />
                  </button>
                  <button type="button" class="rounded p-1.5 text-red-600 hover:bg-gray-100" title="Salida" @click="openMovement(product, 'salida')">
                    <ArrowUpCircle class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && products.length === 0">
              <td colspan="8" class="py-12 text-center text-sm text-unal-secondary-light">
                No hay productos que coincidan con los filtros.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="border-t border-unal-border px-4 py-3 text-xs text-unal-secondary-light">
        {{ meta?.from ?? 0 }}–{{ meta?.to ?? 0 }} de {{ meta?.total ?? 0 }} productos
      </div>
    </section>

    <section class="card">
      <div class="flex items-center justify-between border-b border-unal-border px-4 py-3">
        <div>
          <h3 class="font-semibold text-unal-secondary">Últimos movimientos</h3>
          <p class="text-xs text-unal-secondary-light">Trazabilidad de entradas, salidas y ajustes.</p>
        </div>
        <CircleDollarSign class="h-5 w-5 text-unal-secondary-light" />
      </div>
      <div class="overflow-x-auto">
        <table class="table-base min-w-[720px]">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Producto</th>
              <th>Tipo</th>
              <th>Cantidad</th>
              <th>Stock después</th>
              <th>Motivo</th>
              <th>Usuario</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="movementsLoading">
              <td colspan="7" class="py-8 text-center text-sm text-unal-secondary-light">
                <Loader2 class="inline h-4 w-4 animate-spin" /> Cargando movimientos…
              </td>
            </tr>
            <tr v-for="movement in movements" :key="movement.id">
              <td>{{ movement.movement_date }}</td>
              <td>
                <p class="font-medium text-unal-secondary">{{ movement.product?.name }}</p>
                <p class="text-xs text-unal-secondary-light">{{ movement.product?.sku }}</p>
              </td>
              <td><span :class="movementClass(movement.type)">{{ movementLabel(movement.type) }}</span></td>
              <td :class="Number(movement.quantity) >= 0 ? 'text-unal-accent' : 'text-red-600'">
                {{ Number(movement.quantity) > 0 ? '+' : '' }}{{ number(movement.quantity) }}
              </td>
              <td>{{ number(movement.stock_after) }}</td>
              <td class="text-unal-secondary-light">{{ movement.reason || '—' }}</td>
              <td class="text-unal-secondary-light">{{ movement.creator?.name || 'Sistema' }}</td>
            </tr>
            <tr v-if="!movementsLoading && movements.length === 0">
              <td colspan="7" class="py-8 text-center text-sm text-unal-secondary-light">Sin movimientos registrados.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Modal producto -->
    <Teleport to="body">
      <div v-if="modalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @click.self="modalOpen = false">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">Nuevo producto</h3>
              <p class="text-xs text-unal-secondary-light">El SKU debe ser único en tu organización.</p>
            </div>
            <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="modalOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>
          <form class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="saveProduct">
            <div>
              <label class="label">SKU *</label>
              <input v-model="form.sku" class="input" placeholder="LECHE-01" required />
            </div>
            <div>
              <label class="label">Nombre *</label>
              <input v-model="form.name" class="input" placeholder="Leche cruda" required />
            </div>
            <div>
              <label class="label">Categoría</label>
              <input v-model="form.category" class="input" placeholder="Lácteos" />
            </div>
            <div>
              <label class="label">Unidad</label>
              <input v-model="form.unit" class="input" placeholder="litro, kg, unidad…" />
            </div>
            <div>
              <label class="label">Precio de venta *</label>
              <input v-model="form.price" type="number" min="0" step="0.01" class="input" required />
            </div>
            <div>
              <label class="label">Costo</label>
              <input v-model="form.cost" type="number" min="0" step="0.01" class="input" />
            </div>
            <div>
              <label class="label">Stock inicial</label>
              <input v-model="form.stock" type="number" min="0" step="0.01" class="input" />
              <p class="mt-1 text-xs text-unal-secondary-light">Se registra como entrada "Stock inicial".</p>
            </div>
            <div>
              <label class="label">Stock mínimo</label>
              <input v-model="form.min_stock" type="number" min="0" step="0.01" class="input" />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Descripción</label>
              <textarea v-model="form.description" rows="2" class="input" />
            </div>
            <p v-if="formError" class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>
            <div class="flex justify-end gap-2 sm:col-span-2">
              <button type="button" class="btn-secondary" @click="modalOpen = false">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                {{ saving ? 'Guardando…' : 'Guardar producto' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal movimiento -->
    <Teleport to="body">
      <div v-if="movementOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @click.self="movementOpen = false">
        <div class="w-full max-w-lg rounded-xl bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">Movimiento de inventario</h3>
              <p class="text-xs text-unal-secondary-light">El stock se actualiza de forma atómica.</p>
            </div>
            <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="movementOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>
          <form class="grid gap-4 p-5" @submit.prevent="saveMovement">
            <div>
              <label class="label">Producto *</label>
              <select v-model="movementForm.product_id" class="input" required>
                <option value="" disabled>Selecciona un producto</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }} ({{ number(product.stock) }} {{ product.unit }})
                </option>
              </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="label">Tipo *</label>
                <select v-model="movementForm.type" class="input">
                  <option value="entrada">Entrada</option>
                  <option value="salida">Salida</option>
                  <option value="ajuste">Ajuste (+/-)</option>
                </select>
              </div>
              <div>
                <label class="label">Cantidad *</label>
                <input v-model="movementForm.quantity" type="number" step="0.01" class="input" :min="movementForm.type === 'ajuste' ? undefined : 0.01" required />
                <p v-if="selectedProduct()" class="mt-1 text-xs text-unal-secondary-light">
                  Stock actual: {{ number(selectedProduct().stock) }} {{ selectedProduct().unit }}
                </p>
              </div>
            </div>
            <div v-if="movementForm.type === 'entrada'">
              <label class="label">Costo unitario</label>
              <input v-model="movementForm.unit_cost" type="number" min="0" step="0.01" class="input" />
            </div>
            <div>
              <label class="label">Fecha</label>
              <input v-model="movementForm.movement_date" type="date" class="input" />
            </div>
            <div>
              <label class="label">Motivo</label>
              <input v-model="movementForm.reason" class="input" placeholder="Compra, consumo, merma…" />
            </div>
            <p v-if="movementError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ movementError }}</p>
            <div class="flex justify-end gap-2">
              <button type="button" class="btn-secondary" @click="movementOpen = false">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="movementSaving">
                <Loader2 v-if="movementSaving" class="h-4 w-4 animate-spin" />
                {{ movementSaving ? 'Registrando…' : 'Registrar movimiento' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
