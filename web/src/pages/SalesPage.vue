<script setup>
import { ShoppingCart } from '@lucide/vue'
import ModulePlaceholder from '@/components/ModulePlaceholder.vue'
</script>

<template>
  <ModulePlaceholder
    title="Ventas"
    description="Registro, cobro y seguimiento de ventas de productos y animales."
    :icon="ShoppingCart"
    phase="fase de núcleo de granja"
    :items="[
      'Venta con múltiples ítems y cálculo automático de totales.',
      'Descuento de stock atómico y trazable.',
      'Estados de pago y entrega con código de reclamo.',
      'Filtros por fecha, cliente, estado y usuario.',
      'Venta de animales con baja automática del inventario ganadero.',
    ]"
  />
</template>
