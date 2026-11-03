<script setup>
import { Clock } from '@lucide/vue'

defineProps({
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, Function],
    required: true,
  },
  items: {
    type: Array,
    default: () => [],
  },
  phase: {
    type: String,
    default: 'próxima fase',
  },
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-xl font-semibold text-unal-secondary">{{ title }}</h2>
      <p v-if="description" class="mt-1 text-sm text-unal-secondary-light">{{ description }}</p>
    </div>

    <section class="card overflow-hidden">
      <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-unal-primary/10 text-unal-primary">
          <component :is="icon" class="h-8 w-8" />
        </div>
        <h3 class="mt-5 text-lg font-semibold text-unal-secondary">Módulo en preparación</h3>
        <p class="mt-2 max-w-xl text-sm text-unal-secondary-light">
          La estructura de navegación ya está lista. La lógica y los endpoints se
          implementarán en la <strong>{{ phase }}</strong> del plan de reescritura.
        </p>
        <span class="badge-warning mt-4">
          <Clock class="h-3.5 w-3.5" />
          Pendiente de backend
        </span>
      </div>

      <div v-if="items.length" class="border-t border-unal-border bg-gray-50 px-6 py-5">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-unal-secondary-light">
          Alcance previsto
        </p>
        <ul class="grid gap-2 text-sm text-unal-secondary sm:grid-cols-2">
          <li v-for="item in items" :key="item" class="flex items-start gap-2">
            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-unal-accent" />
            {{ item }}
          </li>
        </ul>
      </div>
    </section>
  </div>
</template>
