<script setup>
import { Users } from '@lucide/vue'
import ModulePlaceholder from '@/components/ModulePlaceholder.vue'
</script>

<template>
  <ModulePlaceholder
    title="Clientes"
    description="Directorio de compradores, documentos y comprobantes de pago."
    :icon="Users"
    phase="fase de núcleo de granja"
    :items="[
      'Documento único por organización (CC, CE, TI, NIT, pasaporte).',
      'Historial de compras y última fecha de compra.',
      'Carga y revisión de comprobantes de pago.',
      'Origen manual o formulario público.',
      'Búsqueda y filtros por nombre, documento y estado.',
    ]"
  />
</template>
