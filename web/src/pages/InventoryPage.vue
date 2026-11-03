<script setup>
import { Package } from '@lucide/vue'
import ModulePlaceholder from '@/components/ModulePlaceholder.vue'
</script>

<template>
  <ModulePlaceholder
    title="Inventario"
    description="Stock actual, movimientos y alertas de reposición."
    :icon="Package"
    phase="fase de núcleo de granja"
    :items="[
      'Productos con precio, unidad, categoría e imagen.',
      'Entradas, salidas y ajustes con trazabilidad.',
      'Alertas de stock bajo y mínimo configurable.',
      'Historial de movimientos por producto y usuario.',
      'Integración con ventas y compras públicas.',
    ]"
  />
</template>
