<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import {
  Ban,
  CheckCircle2,
  ClipboardList,
  Clock,
  Copy,
  Download,
  Eye,
  Loader2,
  Search,
  ShoppingBag,
  Store,
  X,
} from '@lucide/vue'
import http from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const orders = ref([])
const meta = ref(null)
const summary = ref(null)
const loading = ref(true)
const error = ref('')
const gated = ref(false)
const search = ref('')
const statusFilter = ref('')

const detailOpen = ref(false)
const detailLoading = ref(false)
const detail = ref(null)
const detailError = ref('')
const reviewing = ref(false)
const rejectMode = ref(false)
const rejectReason = ref('')
const copied = ref(false)

const statusOptions = [
  { value: 'pendiente', label: 'Pendientes' },
  { value: 'aprobado', label: 'Aprobados' },
  { value: 'rechazado', label: 'Rechazados' },
]

const canManage = computed(
  () => auth.isSuperAdmin || (auth.user?.permissions ?? []).includes('orders.manage'),
)

let timer = null
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(fetchOrders, 350)
})
watch(statusFilter, fetchOrders)

function money(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function formatDateTime(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString('es-CO', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function firstError(err) {
  const errors = err.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return err.response?.data?.message || 'No se pudo completar la operación.'
}

function statusLabel(status) {
  return { pendiente: 'Pendiente', aprobado: 'Aprobado', rechazado: 'Rechazado' }[status] ?? status
}

function statusClass(status) {
  return {
    pendiente: 'badge-warning',
    aprobado: 'badge-success',
    rechazado: 'badge-danger',
  }[status] ?? 'badge-info'
}

function paymentLabel(method) {
  return {
    efectivo: 'Efectivo',
    transferencia: 'Transferencia',
    nequi: 'Nequi',
    daviplata: 'Daviplata',
  }[method] ?? method
}

async function fetchOrders() {
  loading.value = true
  error.value = ''
  gated.value = false

  try {
    const { data } = await http.get('/public-orders', {
      params: {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        per_page: 50,
      },
    })
    orders.value = data.data
    meta.value = data.meta
  } catch (err) {
    orders.value = []
    if (err.response?.status === 402) {
      gated.value = true
    } else {
      error.value = firstError(err)
    }
  } finally {
    loading.value = false
  }
}

async function fetchSummary() {
  try {
    const { data } = await http.get('/public-orders/summary')
    summary.value = data.data
  } catch {
    summary.value = null
  }
}

async function refresh() {
  await Promise.all([fetchOrders(), fetchSummary()])
}

async function openDetail(order) {
  detailOpen.value = true
  detailLoading.value = true
  detail.value = null
  detailError.value = ''
  rejectMode.value = false
  rejectReason.value = ''

  try {
    const { data } = await http.get(`/public-orders/${order.id}`)
    detail.value = data.data
  } catch (err) {
    detailError.value = firstError(err)
  } finally {
    detailLoading.value = false
  }
}

function closeDetail() {
  detailOpen.value = false
  detail.value = null
  rejectMode.value = false
  rejectReason.value = ''
}

async function approveOrder() {
  if (!detail.value) return

  reviewing.value = true
  detailError.value = ''

  try {
    const { data } = await http.post(`/public-orders/${detail.value.id}/approve`)
    detail.value = data.data
    await refresh()
  } catch (err) {
    detailError.value = firstError(err)
  } finally {
    reviewing.value = false
  }
}

async function rejectOrder() {
  if (!detail.value) return

  if (!rejectReason.value.trim()) {
    detailError.value = 'Indica el motivo del rechazo.'
    return
  }

  reviewing.value = true
  detailError.value = ''

  try {
    const { data } = await http.post(`/public-orders/${detail.value.id}/reject`, {
      reason: rejectReason.value,
    })
    detail.value = data.data
    rejectMode.value = false
    await refresh()
  } catch (err) {
    detailError.value = firstError(err)
  } finally {
    reviewing.value = false
  }
}

async function downloadReceipt() {
  if (!detail.value) return

  detailError.value = ''

  try {
    const response = await http.get(`/public-orders/${detail.value.id}/receipt`, {
      responseType: 'blob',
    })
    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')
    link.href = url
    link.download = detail.value.receipt_name || `comprobante-${detail.value.code}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
  } catch (err) {
    detailError.value = err.response?.status === 404
      ? 'El pedido no tiene un comprobante disponible.'
      : firstError(err)
  }
}

async function copyPublicLink() {
  if (!summary.value?.public_url) return

  try {
    await navigator.clipboard.writeText(`${window.location.origin}${summary.value.public_url}`)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch {
    copied.value = false
  }
}

onMounted(refresh)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-unal-secondary">Pedidos públicos</h1>
        <p class="mt-1 text-sm text-unal-secondary-light">
          Pedidos recibidos desde el catálogo en línea, con su comprobante de pago.
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <button type="button" class="btn-secondary" @click="copyPublicLink">
          <Copy class="h-4 w-4" />
          {{ copied ? 'Enlace copiado' : 'Copiar enlace público' }}
        </button>
        <RouterLink
          v-if="summary?.public_url"
          :to="summary.public_url"
          target="_blank"
          class="btn-primary"
        >
          <Store class="h-4 w-4" />
          Abrir catálogo
        </RouterLink>
      </div>
    </div>

    <div v-if="gated" class="card p-8 text-center">
      <ClipboardList class="mx-auto h-10 w-10 text-unal-secondary-light" />
      <h2 class="mt-4 text-lg font-semibold text-unal-secondary">Portal público no incluido</h2>
      <p class="mt-2 text-sm text-unal-secondary-light">
        Los pedidos en línea forman parte del plan Avanzado. Actualiza tu plan para
        publicar el catálogo de tu finca y recibir pedidos con comprobante.
      </p>
      <RouterLink :to="{ name: 'my-plan' }" class="btn-primary mt-5">
        Ver mi plan
      </RouterLink>
    </div>

    <template v-else>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4">
          <div class="flex items-center gap-2 text-unal-secondary-light">
            <Clock class="h-4 w-4" />
            <span class="text-xs font-medium uppercase tracking-wide">Pendientes</span>
          </div>
          <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary?.pending ?? '—' }}</p>
          <p class="text-xs text-unal-secondary-light">Por revisar y aprobar</p>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-2 text-unal-secondary-light">
            <ShoppingBag class="h-4 w-4" />
            <span class="text-xs font-medium uppercase tracking-wide">Monto pendiente</span>
          </div>
          <p class="mt-2 text-2xl font-semibold text-unal-primary">
            {{ summary ? money(summary.pending_amount) : '—' }}
          </p>
          <p class="text-xs text-unal-secondary-light">Valor de pedidos sin revisar</p>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-2 text-unal-secondary-light">
            <CheckCircle2 class="h-4 w-4" />
            <span class="text-xs font-medium uppercase tracking-wide">Aprobados</span>
          </div>
          <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary?.approved ?? '—' }}</p>
          <p class="text-xs text-unal-secondary-light">Convertidos en venta</p>
        </div>
        <div class="card p-4">
          <div class="flex items-center gap-2 text-unal-secondary-light">
            <Ban class="h-4 w-4" />
            <span class="text-xs font-medium uppercase tracking-wide">Rechazados</span>
          </div>
          <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary?.rejected ?? '—' }}</p>
          <p class="text-xs text-unal-secondary-light">Con motivo registrado</p>
        </div>
      </div>

      <div class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-unal-border p-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="relative w-full sm:max-w-sm">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
            <input
              v-model="search"
              type="search"
              class="input pl-9"
              placeholder="Buscar por código, cliente o documento…"
            />
          </div>
          <select v-model="statusFilter" class="input sm:w-48">
            <option value="">Todos los estados</option>
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-16 text-sm text-unal-secondary-light">
          <Loader2 class="mr-2 h-4 w-4 animate-spin" />
          Cargando pedidos…
        </div>

        <div v-else-if="error" class="p-6">
          <p class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</p>
        </div>

        <div v-else-if="orders.length === 0" class="p-10 text-center text-sm text-unal-secondary-light">
          <ClipboardList class="mx-auto mb-3 h-8 w-8 text-gray-300" />
          No hay pedidos que coincidan con el filtro.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="table-base">
            <thead>
              <tr>
                <th>Pedido</th>
                <th>Cliente</th>
                <th>Recibido</th>
                <th>Ítems</th>
                <th>Total</th>
                <th>Estado</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50">
                <td>
                  <p class="font-medium text-unal-secondary">{{ order.code }}</p>
                  <p class="text-xs text-unal-secondary-light">{{ paymentLabel(order.payment_method) }}</p>
                </td>
                <td>
                  <p class="font-medium text-unal-secondary">{{ order.customer_name }}</p>
                  <p class="text-xs text-unal-secondary-light">
                    {{ order.document_type }} {{ order.document_number }}
                  </p>
                </td>
                <td class="text-sm text-unal-secondary-light">{{ formatDateTime(order.created_at) }}</td>
                <td class="text-sm text-unal-secondary">{{ order.items_count }}</td>
                <td class="font-medium text-unal-secondary">{{ money(order.total) }}</td>
                <td><span :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span></td>
                <td class="text-right">
                  <button type="button" class="btn-ghost px-3 py-1.5" @click="openDetail(order)">
                    <Eye class="h-4 w-4" />
                    Revisar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="meta && meta.total > 0" class="border-t border-unal-border px-4 py-3 text-xs text-unal-secondary-light">
          {{ meta.from }}–{{ meta.to }} de {{ meta.total }} pedidos
        </div>
      </div>
    </template>

    <div
      v-if="detailOpen"
      class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-0 sm:items-center sm:p-4"
      @click.self="closeDetail"
    >
      <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-xl sm:rounded-2xl">
        <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
          <div>
            <h2 class="text-lg font-semibold text-unal-secondary">
              {{ detail?.code || 'Pedido' }}
            </h2>
            <p class="text-xs text-unal-secondary-light">Detalle y revisión del pedido público</p>
          </div>
          <button type="button" class="rounded-lg p-2 text-unal-secondary-light hover:bg-gray-100" @click="closeDetail">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4">
          <div v-if="detailLoading" class="flex items-center justify-center py-12 text-sm text-unal-secondary-light">
            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
            Cargando pedido…
          </div>

          <template v-else-if="detail">
            <div class="flex flex-wrap items-center gap-2">
              <span :class="statusClass(detail.status)">{{ statusLabel(detail.status) }}</span>
              <span class="badge-info">{{ paymentLabel(detail.payment_method) }}</span>
              <span v-if="detail.has_receipt" class="badge-success">Comprobante adjunto</span>
              <span v-else class="badge-warning">Sin comprobante</span>
            </div>

            <div class="mt-4 grid gap-4 rounded-xl border border-unal-border bg-gray-50 p-4 sm:grid-cols-2">
              <div>
                <p class="text-xs uppercase tracking-wide text-unal-secondary-light">Cliente</p>
                <p class="mt-1 font-medium text-unal-secondary">{{ detail.customer_name }}</p>
                <p class="text-sm text-unal-secondary-light">
                  {{ detail.document_type }} {{ detail.document_number }}
                </p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-wide text-unal-secondary-light">Contacto</p>
                <p class="mt-1 text-sm text-unal-secondary">{{ detail.phone || 'Sin teléfono' }}</p>
                <p class="text-sm text-unal-secondary-light">{{ detail.email || 'Sin correo' }}</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-wide text-unal-secondary-light">Entrega</p>
                <p class="mt-1 text-sm text-unal-secondary">{{ detail.address || 'Sin dirección' }}</p>
                <p class="text-sm text-unal-secondary-light">{{ detail.city || 'Sin ciudad' }}</p>
              </div>
              <div>
                <p class="text-xs uppercase tracking-wide text-unal-secondary-light">Recibido</p>
                <p class="mt-1 text-sm text-unal-secondary">{{ formatDateTime(detail.created_at) }}</p>
                <p v-if="detail.reviewer" class="text-sm text-unal-secondary-light">
                  Revisado por {{ detail.reviewer.name }}
                </p>
                <p v-if="detail.reviewed_at" class="text-xs text-unal-secondary-light">
                  {{ formatDateTime(detail.reviewed_at) }}
                </p>
              </div>
            </div>

            <div class="mt-4 overflow-hidden rounded-xl border border-unal-border">
              <table class="table-base">
                <thead>
                  <tr>
                    <th>Producto</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Precio</th>
                    <th class="text-right">Subtotal</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in detail.items" :key="item.id">
                    <td class="text-unal-secondary">{{ item.description }}</td>
                    <td class="text-right">{{ Number(item.quantity) }}</td>
                    <td class="text-right">{{ money(item.unit_price) }}</td>
                    <td class="text-right font-medium">{{ money(item.line_total) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-3 flex items-center justify-between">
              <span class="text-sm text-unal-secondary-light">Total del pedido</span>
              <span class="text-xl font-semibold text-unal-primary">{{ money(detail.total) }}</span>
            </div>

            <div v-if="detail.notes" class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-unal-secondary">
              <p class="text-xs font-medium uppercase tracking-wide text-unal-secondary-light">Notas del cliente</p>
              <p class="mt-1">{{ detail.notes }}</p>
            </div>

            <div v-if="detail.rejection_reason" class="mt-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
              <p class="text-xs font-medium uppercase tracking-wide">Motivo del rechazo</p>
              <p class="mt-1">{{ detail.rejection_reason }}</p>
            </div>

            <div v-if="detail.sale" class="mt-3 rounded-lg bg-unal-accent/10 px-4 py-3 text-sm text-unal-accent">
              Venta generada: <span class="font-semibold">{{ detail.sale.code }}</span>
              · {{ money(detail.sale.total) }}
            </div>

            <div v-if="rejectMode" class="mt-4">
              <label class="label" for="reject_reason">Motivo del rechazo</label>
              <textarea
                id="reject_reason"
                v-model="rejectReason"
                rows="2"
                class="input"
                placeholder="Ej. el comprobante no coincide con el valor del pedido"
              />
            </div>

            <div v-if="detailError" class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
              {{ detailError }}
            </div>
          </template>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2 border-t border-unal-border px-5 py-4">
          <button
            v-if="detail?.has_receipt"
            type="button"
            class="btn-secondary"
            @click="downloadReceipt"
          >
            <Download class="h-4 w-4" />
            Comprobante
          </button>

          <template v-if="detail?.status === 'pendiente' && canManage">
            <template v-if="rejectMode">
              <button type="button" class="btn-secondary" :disabled="reviewing" @click="rejectMode = false">
                Cancelar
              </button>
              <button type="button" class="btn-primary" :disabled="reviewing" @click="rejectOrder">
                <Loader2 v-if="reviewing" class="h-4 w-4 animate-spin" />
                <Ban v-else class="h-4 w-4" />
                Confirmar rechazo
              </button>
            </template>
            <template v-else>
              <button type="button" class="btn-secondary" :disabled="reviewing" @click="rejectMode = true">
                <Ban class="h-4 w-4" />
                Rechazar
              </button>
              <button type="button" class="btn-primary" :disabled="reviewing" @click="approveOrder">
                <Loader2 v-if="reviewing" class="h-4 w-4 animate-spin" />
                <CheckCircle2 v-else class="h-4 w-4" />
                Aprobar y vender
              </button>
            </template>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>
