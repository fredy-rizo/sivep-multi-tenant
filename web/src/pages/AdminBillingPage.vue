<script setup>
import { BadgeDollarSign } from '@lucide/vue'
import ModulePlaceholder from '@/components/ModulePlaceholder.vue'
</script>

<template>
  <ModulePlaceholder
    title="Monetización"
    description="Back office de organizaciones, planes, pagos y licencias."
    :icon="BadgeDollarSign"
    phase="fase de suscripciones"
    :items="[
      'Organizaciones y sus suscripciones vigentes.',
      'Confirmación y rechazo de pagos manuales.',
      'Cupones porcentuales o de valor fijo.',
      'Licencias on-premise y servicios de valor agregado.',
      'Métricas SaaS: MRR, ARR, ARPU, churn y trials.',
    ]"
  />
</template>
