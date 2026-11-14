<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowLeft,
  CheckCircle2,
  Loader2,
  Mail,
  MapPin,
  Minus,
  Package,
  Phone,
  Plus,
  Receipt,
  ShieldCheck,
  ShoppingBasket,
  Upload,
} from '@lucide/vue'
import http, { fetchCsrfCookie } from '@/api/http'

const route = useRoute()

const loading = ref(true)
const error = ref('')
const gated = ref(false)
const catalog = ref(null)
const quantities = ref({})
const receipt = ref(null)
const submitting = ref(false)
const submitError = ref('')
const success = ref(null)
const fileInput = ref(null)

const form = reactive({
  customer_name: '',
  document_type: 'CC',
  document_number: '',
  phone: '',
  email: '',
  address: '',
  city: '',
  payment_method: 'transferencia',
  notes: '',
})

const products = computed(() => catalog.value?.products ?? [])

const selected = computed(() =>
  products.value
    .map((product) => ({
      product,
      quantity: Number(quantities.value[product.id] || 0),
    }))
    .filter((line) => line.quantity > 0),
)

const total = computed(() =>
  selected.value.reduce(
    (sum, line) => sum + Number(line.product.price) * line.quantity,
    0,
  ),
)

function money(value) {
  return new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  }).format(Number(value || 0))
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('es-CO', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

function quantityFor(product) {
  return Number(quantities.value[product.id] || 0)
}

function changeQuantity(product, delta) {
  const next = Math.max(0, Math.min(Number(product.stock), quantityFor(product) + delta))
  quantities.value = { ...quantities.value, [product.id]: next }
}

function setQuantity(product, event) {
  const value = Number(event.target.value || 0)
  const next = Math.max(0, Math.min(Number(product.stock), value))
  quantities.value = { ...quantities.value, [product.id]: next }
}

function onReceiptChange(event) {
  const file = event.target.files?.[0] ?? null
  submitError.value = ''

  if (file && file.size > 10 * 1024 * 1024) {
    submitError.value = 'El comprobante no puede superar 10 MB.'
    receipt.value = null
    if (fileInput.value) fileInput.value.value = ''
    return
  }

  receipt.value = file
}

function firstError(err) {
  const errors = err.response?.data?.errors
  if (errors) return Object.values(errors).flat()[0]
  return err.response?.data?.message || 'No se pudo enviar el pedido.'
}

async function loadCatalog() {
  loading.value = true
  error.value = ''
  gated.value = false

  try {
    const { data } = await http.get(`/public/${route.params.slug}/catalog`)
    catalog.value = data.data
    document.title = `${data.data.organization.name} · Catálogo`
  } catch (err) {
    if (err.response?.status === 402) {
      gated.value = true
    } else if (err.response?.status === 404) {
      error.value = 'El catálogo que buscas no existe o no está disponible.'
    } else {
      error.value = firstError(err)
    }
  } finally {
    loading.value = false
  }
}

async function submitOrder() {
  submitError.value = ''

  if (!form.customer_name.trim()) {
    submitError.value = 'Escribe tu nombre completo.'
    return
  }
  if (!form.document_number.trim()) {
    submitError.value = 'Escribe tu número de documento.'
    return
  }
  if (selected.value.length === 0) {
    submitError.value = 'Selecciona al menos un producto.'
    return
  }
  if (!receipt.value) {
    submitError.value = 'Adjunta el comprobante de pago.'
    return
  }

  submitting.value = true

  try {
    await fetchCsrfCookie()

    const payload = new FormData()
    payload.append('customer_name', form.customer_name)
    payload.append('document_type', form.document_type)
    payload.append('document_number', form.document_number)
    payload.append('phone', form.phone)
    payload.append('email', form.email)
    payload.append('address', form.address)
    payload.append('city', form.city)
    payload.append('payment_method', form.payment_method)
    payload.append('notes', form.notes)
    payload.append('receipt', receipt.value)

    selected.value.forEach((line, index) => {
      payload.append(`items[${index}][product_id]`, line.product.id)
      payload.append(`items[${index}][quantity]`, line.quantity)
    })

    const { data } = await http.post(`/public/${route.params.slug}/orders`, payload)
    success.value = data.data
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (err) {
    submitError.value = firstError(err)
  } finally {
    submitting.value = false
  }
}

function resetOrder() {
  success.value = null
  quantities.value = {}
  receipt.value = null
  submitError.value = ''
  Object.assign(form, {
    customer_name: '',
    document_type: 'CC',
    document_number: '',
    phone: '',
    email: '',
    address: '',
    city: '',
    payment_method: 'transferencia',
    notes: '',
  })
  if (fileInput.value) fileInput.value.value = ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(loadCatalog)
</script>

<template>
  <div class="min-h-screen bg-unal-background">
    <header class="border-b border-unal-border bg-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-unal-primary text-white">
            <ShoppingBasket class="h-5 w-5" />
          </div>
          <div class="leading-tight">
            <p class="text-lg font-bold text-unal-primary">SIVEP Agro</p>
            <p class="text-[11px] font-medium uppercase tracking-widest text-unal-secondary-light">
              Catálogo de pedidos
            </p>
          </div>
        </div>
        <RouterLink :to="{ name: 'login' }" class="text-sm font-medium text-unal-secondary hover:text-unal-primary">
          Acceso administrativo
        </RouterLink>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
      <div v-if="loading" class="flex items-center justify-center py-24 text-unal-secondary-light">
        <Loader2 class="mr-2 h-5 w-5 animate-spin" />
        Cargando catálogo…
      </div>

      <div v-else-if="gated" class="card mx-auto max-w-xl p-8 text-center">
        <ShieldCheck class="mx-auto h-10 w-10 text-unal-secondary-light" />
        <h1 class="mt-4 text-xl font-semibold text-unal-secondary">Catálogo no disponible</h1>
        <p class="mt-2 text-sm text-unal-secondary-light">
          Esta organización no tiene activo el portal público de pedidos. Comunícate
          directamente con la finca para realizar tu compra.
        </p>
      </div>

      <div v-else-if="error" class="card mx-auto max-w-xl p-8 text-center">
        <Package class="mx-auto h-10 w-10 text-unal-secondary-light" />
        <h1 class="mt-4 text-xl font-semibold text-unal-secondary">No encontramos el catálogo</h1>
        <p class="mt-2 text-sm text-unal-secondary-light">{{ error }}</p>
        <RouterLink :to="{ name: 'plans' }" class="btn-secondary mt-5">
          <ArrowLeft class="h-4 w-4" />
          Ver planes
        </RouterLink>
      </div>

      <div v-else-if="success" class="card mx-auto max-w-2xl p-8 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-unal-accent/10">
          <CheckCircle2 class="h-7 w-7 text-unal-accent" />
        </div>
        <h1 class="mt-4 text-2xl font-semibold text-unal-secondary">¡Pedido recibido!</h1>
        <p class="mt-2 text-sm text-unal-secondary-light">
          Guardamos tu pedido con el código
          <span class="font-semibold text-unal-primary">{{ success.code }}</span>.
          La finca revisará tu comprobante y te contactará para confirmar la entrega.
        </p>

        <dl class="mt-6 grid gap-3 rounded-xl border border-unal-border bg-gray-50 p-4 text-left text-sm sm:grid-cols-2">
          <div>
            <dt class="text-xs uppercase tracking-wide text-unal-secondary-light">Total</dt>
            <dd class="mt-1 font-semibold text-unal-secondary">{{ money(success.total) }}</dd>
          </div>
          <div>
            <dt class="text-xs uppercase tracking-wide text-unal-secondary-light">Estado</dt>
            <dd class="mt-1"><span class="badge-warning">Pendiente de aprobación</span></dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs uppercase tracking-wide text-unal-secondary-light">Recibido</dt>
            <dd class="mt-1 text-unal-secondary">{{ formatDate(success.created_at) }}</dd>
          </div>
        </dl>

        <button type="button" class="btn-primary mt-6" @click="resetOrder">
          <ShoppingBasket class="h-4 w-4" />
          Hacer otro pedido
        </button>
      </div>

      <template v-else>
        <section class="mb-8 overflow-hidden rounded-2xl bg-unal-primary px-6 py-10 text-white sm:px-10">
          <div class="max-w-2xl">
            <span class="badge bg-white/15 text-white">Compra directa</span>
            <h1 class="mt-4 text-3xl font-bold sm:text-4xl">
              {{ catalog.organization.name }}
            </h1>
            <p class="mt-3 text-sm text-white/80 sm:text-base">
              Elige tus productos, adjunta el comprobante de pago y la finca confirmará
              tu pedido. Sin registros ni contraseñas.
            </p>
            <div class="mt-5 flex flex-wrap gap-4 text-sm text-white/80">
              <span v-if="catalog.organization.city" class="inline-flex items-center gap-2">
                <MapPin class="h-4 w-4" />
                {{ catalog.organization.city }}
              </span>
              <span v-if="catalog.organization.contact_phone" class="inline-flex items-center gap-2">
                <Phone class="h-4 w-4" />
                {{ catalog.organization.contact_phone }}
              </span>
              <span v-if="catalog.organization.contact_email" class="inline-flex items-center gap-2">
                <Mail class="h-4 w-4" />
                {{ catalog.organization.contact_email }}
              </span>
            </div>
          </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-[1fr_380px]">
          <section>
            <div class="mb-4 flex items-end justify-between">
              <div>
                <h2 class="text-lg font-semibold text-unal-secondary">Productos disponibles</h2>
                <p class="text-sm text-unal-secondary-light">
                  Indica las cantidades que necesitas.
                </p>
              </div>
              <span class="text-sm text-unal-secondary-light">{{ products.length }} productos</span>
            </div>

            <div v-if="products.length === 0" class="card p-8 text-center text-sm text-unal-secondary-light">
              La finca aún no tiene productos publicados.
            </div>

            <div v-else class="grid gap-3 sm:grid-cols-2">
              <article
                v-for="product in products"
                :key="product.id"
                class="card flex flex-col p-4 transition-shadow hover:shadow-md"
                :class="{ 'opacity-60': !product.is_available }"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-wide text-unal-secondary-light">
                      {{ product.category || 'General' }}
                    </p>
                    <h3 class="mt-1 font-semibold text-unal-secondary">{{ product.name }}</h3>
                    <p class="mt-1 text-xs text-unal-secondary-light">
                      {{ product.sku }} · {{ product.unit }}
                    </p>
                  </div>
                  <span v-if="!product.is_available" class="badge-danger">Agotado</span>
                </div>

                <p v-if="product.description" class="mt-2 line-clamp-2 text-sm text-unal-secondary-light">
                  {{ product.description }}
                </p>

                <div class="mt-4 flex items-end justify-between">
                  <div>
                    <p class="text-lg font-semibold text-unal-primary">{{ money(product.price) }}</p>
                    <p class="text-xs text-unal-secondary-light">
                      Disponible: {{ Number(product.stock) }} {{ product.unit }}
                    </p>
                  </div>

                  <div v-if="product.is_available" class="flex items-center gap-2">
                    <button
                      type="button"
                      class="flex h-8 w-8 items-center justify-center rounded-lg border border-unal-border text-unal-secondary hover:bg-gray-50"
                      aria-label="Quitar una unidad"
                      @click="changeQuantity(product, -1)"
                    >
                      <Minus class="h-4 w-4" />
                    </button>
                    <input
                      :value="quantityFor(product)"
                      type="number"
                      min="0"
                      :max="Number(product.stock)"
                      class="w-14 rounded-lg border border-unal-border px-2 py-1 text-center text-sm focus:border-unal-primary focus:outline-none"
                      @input="setQuantity(product, $event)"
                    />
                    <button
                      type="button"
                      class="flex h-8 w-8 items-center justify-center rounded-lg border border-unal-border text-unal-secondary hover:bg-gray-50"
                      aria-label="Agregar una unidad"
                      @click="changeQuantity(product, 1)"
                    >
                      <Plus class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </article>
            </div>
          </section>

          <aside class="lg:sticky lg:top-6 lg:self-start">
            <form class="card p-5" @submit.prevent="submitOrder">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-unal-secondary">Tu pedido</h2>
                <span class="badge-info">{{ selected.length }} ítems</span>
              </div>

              <div v-if="selected.length === 0" class="mt-4 rounded-lg border border-dashed border-unal-border p-4 text-center text-sm text-unal-secondary-light">
                Selecciona productos para ver el resumen.
              </div>

              <ul v-else class="mt-4 space-y-2 border-b border-unal-border pb-4">
                <li v-for="line in selected" :key="line.product.id" class="flex items-start justify-between gap-3 text-sm">
                  <span class="min-w-0">
                    <span class="font-medium text-unal-secondary">{{ line.product.name }}</span>
                    <span class="block text-xs text-unal-secondary-light">
                      {{ line.quantity }} × {{ money(line.product.price) }}
                    </span>
                  </span>
                  <span class="shrink-0 font-medium text-unal-secondary">
                    {{ money(Number(line.product.price) * line.quantity) }}
                  </span>
                </li>
              </ul>

              <div class="mt-4 flex items-center justify-between">
                <span class="text-sm font-medium text-unal-secondary">Total</span>
                <span class="text-xl font-semibold text-unal-primary">{{ money(total) }}</span>
              </div>

              <div class="mt-5 space-y-3">
                <div>
                  <label class="label" for="customer_name">Nombre completo *</label>
                  <input id="customer_name" v-model="form.customer_name" class="input" required />
                </div>

                <div class="grid grid-cols-3 gap-2">
                  <div>
                    <label class="label" for="document_type">Tipo</label>
                    <select id="document_type" v-model="form.document_type" class="input">
                      <option value="CC">CC</option>
                      <option value="CE">CE</option>
                      <option value="TI">TI</option>
                      <option value="NIT">NIT</option>
                      <option value="PA">PA</option>
                    </select>
                  </div>
                  <div class="col-span-2">
                    <label class="label" for="document_number">Documento *</label>
                    <input id="document_number" v-model="form.document_number" class="input" required />
                  </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
                  <div>
                    <label class="label" for="phone">Teléfono</label>
                    <input id="phone" v-model="form.phone" class="input" />
                  </div>
                  <div>
                    <label class="label" for="email">Correo</label>
                    <input id="email" v-model="form.email" type="email" class="input" />
                  </div>
                </div>

                <div>
                  <label class="label" for="address">Dirección de entrega</label>
                  <input id="address" v-model="form.address" class="input" />
                </div>

                <div>
                  <label class="label" for="city">Ciudad</label>
                  <input id="city" v-model="form.city" class="input" />
                </div>

                <div>
                  <label class="label" for="payment_method">Medio de pago</label>
                  <select id="payment_method" v-model="form.payment_method" class="input">
                    <option
                      v-for="method in catalog.payment_methods"
                      :key="method.code"
                      :value="method.code"
                    >
                      {{ method.label }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="label" for="notes">Notas para la finca</label>
                  <textarea id="notes" v-model="form.notes" rows="2" class="input" />
                </div>

                <div>
                  <label class="label" for="receipt">Comprobante de pago *</label>
                  <label
                    class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-dashed border-unal-border px-3 py-3 text-sm hover:border-unal-primary"
                  >
                    <span class="flex min-w-0 items-center gap-2 text-unal-secondary-light">
                      <Upload class="h-4 w-4 shrink-0" />
                      <span class="truncate">{{ receipt ? receipt.name : 'Imagen o PDF (máx. 10 MB)' }}</span>
                    </span>
                    <span class="shrink-0 text-xs font-medium text-unal-primary">Elegir</span>
                    <input
                      id="receipt"
                      ref="fileInput"
                      type="file"
                      accept="image/*,application/pdf"
                      class="hidden"
                      @change="onReceiptChange"
                    />
                  </label>
                </div>
              </div>

              <p v-if="submitError" class="mt-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ submitError }}
              </p>

              <button type="submit" class="btn-primary mt-5 w-full" :disabled="submitting">
                <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                <Receipt v-else class="h-4 w-4" />
                {{ submitting ? 'Enviando pedido…' : 'Enviar pedido' }}
              </button>

              <p class="mt-3 text-center text-xs text-unal-secondary-light">
                El pedido queda pendiente hasta que la finca valide el pago.
              </p>
            </form>
          </aside>
        </div>
      </template>
    </main>

    <footer class="border-t border-unal-border bg-white px-4 py-5 text-center text-xs text-unal-secondary-light">
      SIVEP Agro · Catálogo público · Un producto de SIVEP Tech
    </footer>
  </div>
</template>
