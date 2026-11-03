<script setup>
import { Settings } from '@lucide/vue'
import ModulePlaceholder from '@/components/ModulePlaceholder.vue'
</script>

<template>
  <ModulePlaceholder
    title="Usuarios"
    description="Administración de cuentas y roles de la organización."
    :icon="Settings"
    phase="fase de fundaciones"
    :items="[
      'Invitación y creación de usuarios por organización.',
      'Roles: super_admin, admin_granja, veterinario y operario.',
      'Activación/desactivación sin borrado físico.',
      'Límite de usuarios según el plan contratado.',
      'Auditoría de último acceso y cambios.',
    ]"
  />
</template>
