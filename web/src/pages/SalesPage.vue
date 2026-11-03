<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import {
  Ban,
  Eye,
  Loader2,
  Plus,
  Search,
  Trash2,
  X,
} from '@lucide/vue'
import http from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const sales = ref([])
const meta = ref(null)
const report = ref(null)
const loading = ref(true)
const error = ref('')
const search = ref('')
const statusFilter = ref('')
const fromFilter = ref('')
const toFilter = ref('')

const customers = ref([])
const products = ref([])
const animals = ref([])

const modalOpen = ref(false)
const saving = ref(false)
const formError = ref('')
const form = ref(emptySale())

const detailOpen = ref(false)
const detailLoading = ref(false)
const detail = ref(null)

const cancelOpen = ref(false)
const cancelSaving = ref(false)
const cancelError = ref('')
const cancelReason = ref('')
const cancelTarget = ref(null)

function emptySale() {
  return {
    customer_id: '',
    sale_date: new Date().toISOString().slice(0, 10),
    status: 'pagada',
    payment_method: 'efectivo',
    discount: '',
    tax: '',
    notes: '',
    items: [emptyItem()],
  }
}

function emptyItem() {
  return { type: 'product', product_id: '', animal_id: '', quantity: 1, unit_price: '' }
}

const canCreate = computed(
  () => auth.isSuperAdmin || (auth.user?.permissions ?? []).includes('sales.create'),
)
const canCancel = computed(
  () => auth.isSuperAdmin || (auth.user?.permissions ?? []).includes('sales.cancel'),
)

const subtotal = computed(() =>
  form.value.items.reduce((sum, item) => sum + Number(item.unit_price || 0) * Number(item.quantity || 0), 0),
)
const total = computed(() =>
  Math.max(0, subtotal.value - Number(form.value.discount || 0) + Number(form.value.tax || 0)),
)

let timer = null
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(fetchSales, 350)
})
watch([statusFilter, fromFilter, toFilter], fetchSales)

function money(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function firstError(err) {
  const errors = err.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return err.response?.data?.message || 'No se pudo completar la operación.'
}

function statusLabel(status) {
  return { pagada: 'Pagada', pendiente: 'Pendiente', anulada: 'Anulada' }[status] ?? status
}

function statusClass(status) {
  return {
    pagada: 'badge-success',
    pendiente: 'badge-warning',
    anulada: 'badge-danger',
  }[status] ?? 'badge-info'
}

function paymentLabel(method) {
  return {
    efectivo: 'Efectivo',
    transferencia: 'Transferencia',
    nequi: 'Nequi',
    daviplata: 'Daviplata',
    credito: 'Crédito',
  }[method] ?? method
}

async function fetchSales() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/sales', {
      params: {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        from: fromFilter.value || undefined,
        to: toFilter.value || undefined,
        per_page: 50,
      },
    })
    sales.value = data.data
    meta.value = data.meta
  } catch (err) {
    error.value = firstError(err)
  } finally {
    loading.value = false
  }
}

async function fetchReport() {
  try {
    const { data } = await http.get('/reports/farm')
    report.value = data.data
  } catch {
    report.value = null
  }
}

async function fetchOptions() {
  try {
    const [customerResponse, productResponse, animalResponse] = await Promise.all([
      http.get('/customers', { params: { is_active: 1, per_page: 100 } }),
      http.get('/products', { params: { is_active: 1, per_page: 100 } }),
      http.get('/animals', { params: { status: 'activo', per_page: 100 } }),
    ])
    customers.value = customerResponse.data.data
    products.value = productResponse.data.data
    animals.value = animalResponse.data.data
  } catch {
    // Se reintenta al abrir el formulario.
  }
}

async function refresh() {
  await Promise.all([fetchSales(), fetchReport()])
}

function openCreate() {
  form.value = emptySale()
  formError.value = ''
  modalOpen.value = true
  if (customers.value.length === 0 || products.value.length === 0) fetchOptions()
}

function addItem() {
  form.value.items.push(emptyItem())
}

function removeItem(index) {
  if (form.value.items.length === 1) {
    form.value.items = [emptyItem()]
    return
  }
  form.value.items.splice(index, 1)
}

function onTypeChange(item) {
  item.product_id = ''
  item.animal_id = ''
  item.unit_price = ''
  item.quantity = 1
}

function onProductChange(item) {
  const product = products.value.find((entry) => entry.id === Number(item.product_id))
  if (product) item.unit_price = Number(product.price)
}

function onAnimalChange(item) {
  item.quantity = 1
}

function itemLabel(item) {
  if (item.type === 'animal') {
    const animal = animals.value.find((entry) => entry.id === Number(item.animal_id))
    return animal ? `${animal.code} · ${animal.name || 'Sin nombre'}` : 'Selecciona un animal'
  }
  const product = products.value.find((entry) => entry.id === Number(item.product_id))
  return product ? `${product.name} (${product.stock} ${product.unit})` : 'Selecciona un producto'
}

async function saveSale() {
  formError.value = ''

  const items = form.value.items.filter((item) =>
    item.type === 'product' ? item.product_id : item.animal_id,
  )

  if (items.length === 0) {
    formError.value = 'Agrega al menos un producto o animal a la venta.'
    return
  }

  if (items.some((item) => Number(item.quantity) <= 0 || item.unit_price === '')) {
    formError.value = 'Revisa la cantidad y el precio de cada línea.'
    return
  }

  saving.value = true
  try {
    await http.post('/sales', {
      customer_id: form.value.customer_id || null,
      sale_date: form.value.sale_date,
      status: form.value.status,
      payment_method: form.value.payment_method,
      discount: Number(form.value.discount || 0),
      tax: Number(form.value.tax || 0),
      notes: form.value.notes || null,
      items: items.map((item) => ({
        product_id: item.type === 'product' ? Number(item.product_id) : null,
        animal_id: item.type === 'animal' ? Number(item.animal_id) : null,
        quantity: Number(item.quantity),
        unit_price: Number(item.unit_price),
        description: itemLabel(item),
      })),
    })
    modalOpen.value = false
    await Promise.all([refresh(), fetchOptions()])
  } catch (err) {
    formError.value = firstError(err)
  } finally {
    saving.value = false
  }
}

async function openDetail(sale) {
  detailOpen.value = true
  detailLoading.value = true
  detail.value = sale
  try {
    const { data } = await http.get(`/sales/${sale.id}`)
    detail.value = data.data
  } catch (err) {
    error.value = firstError(err)
  } finally {
    detailLoading.value = false
  }
}

function askCancel(sale) {
  cancelTarget.value = sale
  cancelReason.value = ''
  cancelError.value = ''
  cancelOpen.value = true
}

async function confirmCancel() {
  cancelSaving.value = true
  cancelError.value = ''
  try {
    await http.post(`/sales/${cancelTarget.value.id}/cancel`, {
      reason: cancelReason.value || null,
    })
    cancelOpen.value = false
    detailOpen.value = false
    await refresh()
  } catch (err) {
    cancelError.value = firstError(err)
  } finally {
    cancelSaving.value = false
  }
}

onMounted(async () => {
  await Promise.all([fetchSales(), fetchReport(), fetchOptions()])
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <h2 class="text-xl font-semibold text-unal-secondary">Ventas</h2>
        <p class="mt-1 text-sm text-unal-secondary-light">
          Registro de productos y animales con descuento automático de inventario.
        </p>
      </div>
      <button v-if="canCreate" type="button" class="btn-primary" @click="openCreate">
        <Plus class="h-4 w-4" />
        Nueva venta
      </button>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Ventas del mes</p>
        <p class="mt-2 text-2xl font-semibold text-unal-accent">{{ money(report?.sales?.month_total) }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">{{ report?.sales?.month_count ?? 0 }} ventas registradas</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Ventas de hoy</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ money(report?.sales?.today_total) }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Cierre del día</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Ticket promedio</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ money(report?.sales?.average_ticket) }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Promedio del mes</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Pendientes de cobro</p>
        <p class="mt-2 text-2xl font-semibold" :class="report?.sales?.pending_count ? 'text-amber-600' : 'text-unal-secondary'">
          {{ report?.sales?.pending_count ?? '—' }}
        </p>
        <p class="mt-1 text-xs text-unal-secondary-light">Ventas a crédito</p>
      </article>
    </section>

    <section class="card">
      <div class="flex flex-col gap-3 border-b border-unal-border p-4 xl:flex-row xl:items-center">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-unal-secondary-light" />
          <input v-model="search" type="search" class="input pl-9" placeholder="Buscar por código, cliente o documento…" />
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <select v-model="statusFilter" class="input w-auto min-w-36">
            <option value="">Todos los estados</option>
            <option value="pagada">Pagadas</option>
            <option value="pendiente">Pendientes</option>
            <option value="anulada">Anuladas</option>
          </select>
          <input v-model="fromFilter" type="date" class="input w-auto" aria-label="Desde" />
          <input v-model="toFilter" type="date" class="input w-auto" aria-label="Hasta" />
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="table-base min-w-[880px]">
          <thead>
            <tr>
              <th>Venta</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Ítems</th>
              <th>Pago</th>
              <th>Total</th>
              <th>Estado</th>
              <th class="w-24"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="8" class="py-12 text-center text-sm text-unal-secondary-light">
                <Loader2 class="inline h-4 w-4 animate-spin" /> Cargando ventas…
              </td>
            </tr>
            <tr v-for="sale in sales" :key="sale.id" class="hover:bg-gray-50">
              <td>
                <button type="button" class="text-left font-medium text-unal-secondary hover:text-unal-primary" @click="openDetail(sale)">
                  {{ sale.code }}
                </button>
                <p class="text-xs text-unal-secondary-light">{{ sale.creator?.name }}</p>
              </td>
              <td>{{ sale.sale_date }}</td>
              <td>{{ sale.customer?.name || 'Consumidor final' }}</td>
              <td>{{ sale.items_count ?? '—' }}</td>
              <td>{{ paymentLabel(sale.payment_method) }}</td>
              <td class="font-semibold text-unal-secondary">{{ money(sale.total) }}</td>
              <td><span :class="statusClass(sale.status)">{{ statusLabel(sale.status) }}</span></td>
              <td>
                <div class="flex justify-end gap-1">
                  <button type="button" class="rounded p-1.5 text-unal-secondary-light hover:bg-gray-100 hover:text-unal-primary" title="Ver detalle" @click="openDetail(sale)">
                    <Eye class="h-4 w-4" />
                  </button>
                  <button
                    v-if="canCancel && sale.status !== 'anulada'"
                    type="button"
                    class="rounded p-1.5 text-red-600 hover:bg-gray-100"
                    title="Anular"
                    @click="askCancel(sale)"
                  >
                    <Ban class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && sales.length === 0">
              <td colspan="8" class="py-12 text-center text-sm text-unal-secondary-light">
                No hay ventas que coincidan con los filtros.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="border-t border-unal-border px-4 py-3 text-xs text-unal-secondary-light">
        {{ meta?.from ?? 0 }}–{{ meta?.to ?? 0 }} de {{ meta?.total ?? 0 }} ventas
      </div>
    </section>

    <!-- Modal nueva venta -->
    <Teleport to="body">
      <div v-if="modalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @click.self="modalOpen = false">
        <div class="max-h-[92vh] w-full max-w-4xl overflow-y-auto rounded-xl bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">Nueva venta</h3>
              <p class="text-xs text-unal-secondary-light">El stock se descuenta al guardar. Los animales pasan a estado vendido.</p>
            </div>
            <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="modalOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>

          <form class="space-y-5 p-5" @submit.prevent="saveSale">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div class="sm:col-span-2">
                <label class="label">Cliente</label>
                <select v-model="form.customer_id" class="input">
                  <option value="">Consumidor final</option>
                  <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                    {{ customer.name }} · {{ customer.document_number }}
                  </option>
                </select>
              </div>
              <div>
                <label class="label">Fecha</label>
                <input v-model="form.sale_date" type="date" class="input" />
              </div>
              <div>
                <label class="label">Estado</label>
                <select v-model="form.status" class="input">
                  <option value="pagada">Pagada</option>
                  <option value="pendiente">Pendiente</option>
                </select>
              </div>
              <div>
                <label class="label">Método de pago</label>
                <select v-model="form.payment_method" class="input">
                  <option value="efectivo">Efectivo</option>
                  <option value="transferencia">Transferencia</option>
                  <option value="nequi">Nequi</option>
                  <option value="daviplata">Daviplata</option>
                  <option value="credito">Crédito</option>
                </select>
              </div>
              <div>
                <label class="label">Descuento</label>
                <input v-model="form.discount" type="number" min="0" step="0.01" class="input" placeholder="0" />
              </div>
              <div>
                <label class="label">Impuestos</label>
                <input v-model="form.tax" type="number" min="0" step="0.01" class="input" placeholder="0" />
              </div>
              <div>
                <label class="label">Total</label>
                <div class="flex h-[38px] items-center rounded-lg bg-gray-50 px-3 text-sm font-semibold text-unal-secondary">
                  {{ money(total) }}
                </div>
              </div>
            </div>

            <div class="rounded-xl border border-unal-border">
              <div class="flex items-center justify-between border-b border-unal-border px-4 py-3">
                <h4 class="text-sm font-semibold text-unal-secondary">Líneas de la venta</h4>
                <button type="button" class="btn-secondary !px-3 !py-1.5" @click="addItem">
                  <Plus class="h-3.5 w-3.5" /> Agregar
                </button>
              </div>
              <div class="space-y-3 p-4">
                <div v-for="(item, index) in form.items" :key="index" class="grid gap-3 rounded-lg bg-gray-50 p-3 lg:grid-cols-12">
                  <div class="lg:col-span-2">
                    <label class="label">Tipo</label>
                    <select v-model="item.type" class="input" @change="onTypeChange(item)">
                      <option value="product">Producto</option>
                      <option value="animal">Animal</option>
                    </select>
                  </div>
                  <div class="lg:col-span-4">
                    <label class="label">{{ item.type === 'animal' ? 'Animal' : 'Producto' }}</label>
                    <select
                      v-if="item.type === 'product'"
                      v-model="item.product_id"
                      class="input"
                      @change="onProductChange(item)"
                    >
                      <option value="" disabled>Selecciona</option>
                      <option v-for="product in products" :key="product.id" :value="product.id">
                        {{ product.name }} ({{ product.stock }} {{ product.unit }})
                      </option>
                    </select>
                    <select v-else v-model="item.animal_id" class="input" @change="onAnimalChange(item)">
                      <option value="" disabled>Selecciona</option>
                      <option v-for="animal in animals" :key="animal.id" :value="animal.id">
                        {{ animal.code }} · {{ animal.name || 'Sin nombre' }} · {{ animal.species?.name }}
                      </option>
                    </select>
                  </div>
                  <div class="lg:col-span-1">
                    <label class="label">Cant.</label>
                    <input v-model="item.quantity" type="number" min="0.01" step="0.01" class="input" :disabled="item.type === 'animal'" />
                  </div>
                  <div class="lg:col-span-2">
                    <label class="label">Precio unitario</label>
                    <input v-model="item.unit_price" type="number" min="0" step="0.01" class="input" />
                  </div>
                  <div class="flex items-end justify-between gap-2 lg:col-span-2">
                    <div class="flex-1">
                      <p class="label">Subtotal</p>
                      <p class="py-2 text-sm font-semibold text-unal-secondary">
                        {{ money(Number(item.unit_price || 0) * Number(item.quantity || 0)) }}
                      </p>
                    </div>
                    <button type="button" class="rounded p-2 text-red-600 hover:bg-red-50" title="Quitar" @click="removeItem(index)">
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="label">Notas</label>
              <textarea v-model="form.notes" rows="2" class="input" placeholder="Observaciones de la venta" />
            </div>

            <div class="flex flex-col items-end gap-1 rounded-lg bg-gray-50 px-4 py-3 text-sm">
              <div class="flex w-full max-w-xs justify-between"><span class="text-unal-secondary-light">Subtotal</span><span>{{ money(subtotal) }}</span></div>
              <div class="flex w-full max-w-xs justify-between"><span class="text-unal-secondary-light">Descuento</span><span>- {{ money(form.discount) }}</span></div>
              <div class="flex w-full max-w-xs justify-between"><span class="text-unal-secondary-light">Impuestos</span><span>+ {{ money(form.tax) }}</span></div>
              <div class="flex w-full max-w-xs justify-between border-t border-unal-border pt-1 text-base font-semibold"><span>Total</span><span>{{ money(total) }}</span></div>
            </div>

            <p v-if="formError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>

            <div class="flex justify-end gap-2">
              <button type="button" class="btn-secondary" @click="modalOpen = false">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                {{ saving ? 'Registrando…' : 'Registrar venta' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Detalle de venta -->
    <Teleport to="body">
      <div v-if="detailOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @click.self="detailOpen = false">
        <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-xl">
          <div class="flex items-start justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">{{ detail?.code }}</h3>
              <p class="text-xs text-unal-secondary-light">
                {{ detail?.sale_date }} · {{ detail?.customer?.name || 'Consumidor final' }} · {{ paymentLabel(detail?.payment_method) }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span :class="statusClass(detail?.status)">{{ statusLabel(detail?.status) }}</span>
              <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="detailOpen = false">
                <X class="h-5 w-5" />
              </button>
            </div>
          </div>

          <div v-if="detailLoading" class="py-12 text-center text-sm text-unal-secondary-light">
            <Loader2 class="inline h-4 w-4 animate-spin" /> Cargando venta…
          </div>
          <div v-else class="space-y-5 p-5">
            <table class="table-base">
              <thead>
                <tr>
                  <th>Descripción</th>
                  <th>Cantidad</th>
                  <th>Precio</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in detail?.items ?? []" :key="item.id">
                  <td>
                    <p class="font-medium text-unal-secondary">{{ item.description }}</p>
                    <p class="text-xs text-unal-secondary-light">
                      <span v-if="item.product">Producto · {{ item.product.sku }}</span>
                      <span v-else-if="item.animal">Animal · {{ item.animal.code }}</span>
                    </p>
                  </td>
                  <td>{{ item.quantity }}</td>
                  <td>{{ money(item.unit_price) }}</td>
                  <td class="text-right font-medium">{{ money(item.line_total) }}</td>
                </tr>
              </tbody>
            </table>

            <div class="ml-auto max-w-xs space-y-1 rounded-lg bg-gray-50 p-4 text-sm">
              <div class="flex justify-between"><span class="text-unal-secondary-light">Subtotal</span><span>{{ money(detail?.subtotal) }}</span></div>
              <div class="flex justify-between"><span class="text-unal-secondary-light">Descuento</span><span>- {{ money(detail?.discount) }}</span></div>
              <div class="flex justify-between"><span class="text-unal-secondary-light">Impuestos</span><span>+ {{ money(detail?.tax) }}</span></div>
              <div class="flex justify-between border-t border-unal-border pt-1 font-semibold"><span>Total</span><span>{{ money(detail?.total) }}</span></div>
            </div>

            <p v-if="detail?.notes" class="text-sm text-unal-secondary-light">{{ detail.notes }}</p>
            <p v-if="detail?.status === 'anulada'" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              Anulada el {{ detail.cancelled_at }}. Motivo: {{ detail.cancel_reason || 'Sin motivo' }}.
            </p>

            <div class="flex justify-end gap-2">
              <button type="button" class="btn-secondary" @click="detailOpen = false">Cerrar</button>
              <button
                v-if="canCancel && detail?.status !== 'anulada'"
                type="button"
                class="btn-primary !bg-red-600 hover:!bg-red-700"
                @click="askCancel(detail)"
              >
                <Ban class="h-4 w-4" /> Anular venta
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Confirmar anulación -->
    <Teleport to="body">
      <div v-if="cancelOpen" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/40 p-4" @click.self="cancelOpen = false">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
          <div class="border-b border-unal-border px-5 py-4">
            <h3 class="font-semibold text-unal-secondary">Anular venta {{ cancelTarget?.code }}</h3>
            <p class="text-xs text-unal-secondary-light">Se devolverá el stock de productos y los animales volverán a estado activo.</p>
          </div>
          <form class="space-y-4 p-5" @submit.prevent="confirmCancel">
            <div>
              <label class="label">Motivo</label>
              <textarea v-model="cancelReason" rows="2" class="input" placeholder="Motivo de la anulación" />
            </div>
            <p v-if="cancelError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ cancelError }}</p>
            <div class="flex justify-end gap-2">
              <button type="button" class="btn-secondary" @click="cancelOpen = false">Cancelar</button>
              <button type="submit" class="btn-primary !bg-red-600 hover:!bg-red-700" :disabled="cancelSaving">
                <Loader2 v-if="cancelSaving" class="h-4 w-4 animate-spin" />
                {{ cancelSaving ? 'Anulando…' : 'Confirmar anulación' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
