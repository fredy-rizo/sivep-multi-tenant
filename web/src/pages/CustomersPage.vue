<script setup>
import { onMounted, ref, watch } from 'vue'
import {
  Loader2,
  Pencil,
  Plus,
  Search,
  ShoppingBag,
  X,
} from '@lucide/vue'
import http from '@/api/http'

const customers = ref([])
const meta = ref(null)
const summary = ref(null)
const loading = ref(true)
const error = ref('')
const search = ref('')
const activeFilter = ref('1')

const modalOpen = ref(false)
const editingId = ref(null)
const saving = ref(false)
const formError = ref('')
const form = ref(emptyCustomer())

const detailOpen = ref(false)
const detailLoading = ref(false)
const detail = ref(null)
const detailSales = ref([])

function emptyCustomer() {
  return {
    document_type: 'CC',
    document_number: '',
    name: '',
    phone: '',
    email: '',
    address: '',
    city: '',
    notes: '',
    is_active: true,
  }
}

let timer = null
watch(search, () => {
  clearTimeout(timer)
  timer = setTimeout(fetchCustomers, 350)
})
watch(activeFilter, fetchCustomers)

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

async function fetchCustomers() {
  loading.value = true
  error.value = ''
  try {
    const { data } = await http.get('/customers', {
      params: {
        search: search.value || undefined,
        is_active: activeFilter.value || undefined,
        per_page: 100,
      },
    })
    customers.value = data.data
    meta.value = data.meta
  } catch (err) {
    error.value = firstError(err)
  } finally {
    loading.value = false
  }
}

async function fetchSummary() {
  try {
    const { data } = await http.get('/customers/summary')
    summary.value = data.data
  } catch {
    summary.value = null
  }
}

async function refresh() {
  await Promise.all([fetchCustomers(), fetchSummary()])
}

function openCreate() {
  editingId.value = null
  form.value = emptyCustomer()
  formError.value = ''
  modalOpen.value = true
}

function openEdit(customer) {
  editingId.value = customer.id
  form.value = {
    document_type: customer.document_type || 'CC',
    document_number: customer.document_number,
    name: customer.name,
    phone: customer.phone || '',
    email: customer.email || '',
    address: customer.address || '',
    city: customer.city || '',
    notes: customer.notes || '',
    is_active: customer.is_active,
  }
  formError.value = ''
  modalOpen.value = true
}

async function saveCustomer() {
  formError.value = ''
  if (!form.value.document_number.trim() || !form.value.name.trim()) {
    formError.value = 'El documento y el nombre son obligatorios.'
    return
  }

  const payload = {
    ...form.value,
    document_number: form.value.document_number.trim(),
    name: form.value.name.trim(),
    email: form.value.email || null,
    phone: form.value.phone || null,
    address: form.value.address || null,
    city: form.value.city || null,
    notes: form.value.notes || null,
  }

  saving.value = true
  try {
    if (editingId.value) {
      await http.put(`/customers/${editingId.value}`, payload)
    } else {
      await http.post('/customers', payload)
    }
    modalOpen.value = false
    await refresh()
  } catch (err) {
    formError.value = firstError(err)
  } finally {
    saving.value = false
  }
}

async function openDetail(customer) {
  detailOpen.value = true
  detailLoading.value = true
  detail.value = customer
  detailSales.value = []
  try {
    const { data } = await http.get(`/customers/${customer.id}`)
    detail.value = data.data
    detailSales.value = data.sales?.data ?? data.sales ?? []
  } catch (err) {
    error.value = firstError(err)
  } finally {
    detailLoading.value = false
  }
}

function statusClass(customer) {
  return customer.is_active ? 'badge-success' : 'badge-warning'
}

onMounted(refresh)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
      <div>
        <h2 class="text-xl font-semibold text-unal-secondary">Clientes</h2>
        <p class="mt-1 text-sm text-unal-secondary-light">
          Directorio de compradores, documento único e historial de compras.
        </p>
      </div>
      <button type="button" class="btn-primary" @click="openCreate">
        <Plus class="h-4 w-4" />
        Nuevo cliente
      </button>
    </div>

    <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ error }}</div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Clientes registrados</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ summary?.total ?? '—' }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">En la organización</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Clientes activos</p>
        <p class="mt-2 text-2xl font-semibold text-unal-accent">{{ summary?.active ?? '—' }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Disponibles para venta</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Con compras</p>
        <p class="mt-2 text-2xl font-semibold text-unal-primary">{{ summary?.with_purchases ?? '—' }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Al menos una venta vigente</p>
      </article>
      <article class="card p-5">
        <p class="text-sm text-unal-secondary-light">Total vendido</p>
        <p class="mt-2 text-2xl font-semibold text-unal-secondary">{{ money(summary?.total_sold) }}</p>
        <p class="mt-1 text-xs text-unal-secondary-light">Ventas no anuladas</p>
      </article>
    </section>

    <section class="card">
      <div class="flex flex-col gap-3 border-b border-unal-border p-4 sm:flex-row sm:items-center">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-unal-secondary-light" />
          <input v-model="search" type="search" class="input pl-9" placeholder="Buscar por nombre, documento, teléfono o correo…" />
        </div>
        <select v-model="activeFilter" class="input w-auto min-w-40">
          <option value="">Todos</option>
          <option value="1">Solo activos</option>
          <option value="0">Solo inactivos</option>
        </select>
      </div>

      <div class="overflow-x-auto">
        <table class="table-base min-w-[900px]">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Documento</th>
              <th>Contacto</th>
              <th>Ciudad</th>
              <th>Compras</th>
              <th>Última compra</th>
              <th>Total comprado</th>
              <th>Estado</th>
              <th class="w-20"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="9" class="py-12 text-center text-sm text-unal-secondary-light">
                <Loader2 class="inline h-4 w-4 animate-spin" /> Cargando clientes…
              </td>
            </tr>
            <tr v-for="customer in customers" :key="customer.id" class="hover:bg-gray-50">
              <td>
                <button type="button" class="flex items-center gap-3 text-left" @click="openDetail(customer)">
                  <div class="flex h-9 w-9 items-center justify-center rounded-full bg-unal-primary/10 text-sm font-semibold text-unal-primary">
                    {{ customer.name.slice(0, 1) }}
                  </div>
                  <div>
                    <p class="font-medium text-unal-secondary hover:text-unal-primary">{{ customer.name }}</p>
                    <p class="text-xs text-unal-secondary-light">{{ customer.email || 'Sin correo' }}</p>
                  </div>
                </button>
              </td>
              <td>
                <p class="font-medium text-unal-secondary">{{ customer.document_number }}</p>
                <p class="text-xs text-unal-secondary-light">{{ customer.document_type }}</p>
              </td>
              <td>{{ customer.phone || '—' }}</td>
              <td>{{ customer.city || '—' }}</td>
              <td>{{ customer.sales_count ?? 0 }}</td>
              <td>{{ customer.last_purchase_at || '—' }}</td>
              <td class="font-medium text-unal-secondary">{{ money(customer.total_purchased) }}</td>
              <td><span :class="statusClass(customer)">{{ customer.is_active ? 'Activo' : 'Inactivo' }}</span></td>
              <td>
                <div class="flex justify-end gap-1">
                  <button type="button" class="rounded p-1.5 text-unal-secondary-light hover:bg-gray-100 hover:text-unal-primary" title="Ver compras" @click="openDetail(customer)">
                    <ShoppingBag class="h-4 w-4" />
                  </button>
                  <button type="button" class="rounded p-1.5 text-unal-secondary-light hover:bg-gray-100 hover:text-unal-primary" title="Editar" @click="openEdit(customer)">
                    <Pencil class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!loading && customers.length === 0">
              <td colspan="9" class="py-12 text-center text-sm text-unal-secondary-light">
                No hay clientes que coincidan con los filtros.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="border-t border-unal-border px-4 py-3 text-xs text-unal-secondary-light">
        {{ meta?.from ?? 0 }}–{{ meta?.to ?? 0 }} de {{ meta?.total ?? 0 }} clientes
      </div>
    </section>

    <!-- Modal cliente -->
    <Teleport to="body">
      <div v-if="modalOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @click.self="modalOpen = false">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white shadow-xl">
          <div class="flex items-center justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">{{ editingId ? 'Editar cliente' : 'Nuevo cliente' }}</h3>
              <p class="text-xs text-unal-secondary-light">El documento debe ser único en tu organización.</p>
            </div>
            <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="modalOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>
          <form class="grid gap-4 p-5 sm:grid-cols-2" @submit.prevent="saveCustomer">
            <div>
              <label class="label">Tipo de documento</label>
              <select v-model="form.document_type" class="input">
                <option value="CC">Cédula de ciudadanía</option>
                <option value="CE">Cédula de extranjería</option>
                <option value="TI">Tarjeta de identidad</option>
                <option value="NIT">NIT</option>
                <option value="PA">Pasaporte</option>
              </select>
            </div>
            <div>
              <label class="label">Número de documento *</label>
              <input v-model="form.document_number" class="input" required />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Nombre o razón social *</label>
              <input v-model="form.name" class="input" required />
            </div>
            <div>
              <label class="label">Teléfono</label>
              <input v-model="form.phone" class="input" />
            </div>
            <div>
              <label class="label">Correo electrónico</label>
              <input v-model="form.email" type="email" class="input" />
            </div>
            <div>
              <label class="label">Ciudad</label>
              <input v-model="form.city" class="input" />
            </div>
            <div>
              <label class="label">Dirección</label>
              <input v-model="form.address" class="input" />
            </div>
            <div class="sm:col-span-2">
              <label class="label">Notas</label>
              <textarea v-model="form.notes" rows="2" class="input" />
            </div>
            <label class="inline-flex items-center gap-2 text-sm sm:col-span-2">
              <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-unal-primary" />
              Cliente activo
            </label>
            <p v-if="formError" class="sm:col-span-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ formError }}</p>
            <div class="flex justify-end gap-2 sm:col-span-2">
              <button type="button" class="btn-secondary" @click="modalOpen = false">Cancelar</button>
              <button type="submit" class="btn-primary" :disabled="saving">
                <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                {{ saving ? 'Guardando…' : 'Guardar cliente' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Detalle / historial -->
    <Teleport to="body">
      <div v-if="detailOpen" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @click.self="detailOpen = false">
        <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-xl bg-white shadow-xl">
          <div class="flex items-start justify-between border-b border-unal-border px-5 py-4">
            <div>
              <h3 class="font-semibold text-unal-secondary">{{ detail?.name }}</h3>
              <p class="text-xs text-unal-secondary-light">
                {{ detail?.document_type }} {{ detail?.document_number }}
                <span v-if="detail?.city"> · {{ detail.city }}</span>
              </p>
            </div>
            <button type="button" class="rounded p-2 hover:bg-gray-100" aria-label="Cerrar" @click="detailOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>

          <div class="grid gap-3 p-5 sm:grid-cols-3">
            <div class="rounded-lg bg-gray-50 p-3">
              <p class="text-xs text-unal-secondary-light">Compras</p>
              <p class="mt-1 text-lg font-semibold text-unal-secondary">{{ detail?.sales_count ?? 0 }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <p class="text-xs text-unal-secondary-light">Total comprado</p>
              <p class="mt-1 text-lg font-semibold text-unal-secondary">{{ money(detail?.total_purchased) }}</p>
            </div>
            <div class="rounded-lg bg-gray-50 p-3">
              <p class="text-xs text-unal-secondary-light">Contacto</p>
              <p class="mt-1 truncate text-sm font-medium text-unal-secondary">{{ detail?.phone || detail?.email || '—' }}</p>
            </div>
          </div>

          <div class="px-5 pb-5">
            <h4 class="mb-2 text-sm font-semibold text-unal-secondary">Historial de ventas</h4>
            <div v-if="detailLoading" class="py-6 text-center text-sm text-unal-secondary-light">
              <Loader2 class="inline h-4 w-4 animate-spin" /> Cargando historial…
            </div>
            <table v-else class="table-base">
              <thead>
                <tr>
                  <th>Venta</th>
                  <th>Fecha</th>
                  <th>Estado</th>
                  <th class="text-right">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="sale in detailSales" :key="sale.id">
                  <td class="font-medium text-unal-secondary">{{ sale.code }}</td>
                  <td>{{ sale.sale_date }}</td>
                  <td>
                    <span :class="sale.status === 'anulada' ? 'badge-danger' : sale.status === 'pendiente' ? 'badge-warning' : 'badge-success'">
                      {{ sale.status }}
                    </span>
                  </td>
                  <td class="text-right font-medium">{{ money(sale.total) }}</td>
                </tr>
                <tr v-if="detailSales.length === 0">
                  <td colspan="4" class="py-6 text-center text-sm text-unal-secondary-light">Sin compras registradas.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
