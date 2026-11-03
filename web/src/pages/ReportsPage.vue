<script setup>
import { BarChart3 } from '@lucide/vue'
import ModulePlaceholder from '@/components/ModulePlaceholder.vue'
</script>

<template>
  <ModulePlaceholder
    title="Reportes"
    description="Indicadores operativos, exportaciones y analítica avanzada."
    :icon="BarChart3"
    phase="fase de reportes y analítica"
    :items="[
      'Reportes de ventas, inventario y clientes con filtros.',
      'Exportación CSV según el plan contratado.',
      'Pronóstico de ventas y afinidad de productos (álgebra lineal).',
      'Velocidad/aceleración de ventas y EOQ (cálculo diferencial).',
      'Reportes ganaderos: peso, GMD, sanidad y reproducción.',
    ]"
  />
</template>
