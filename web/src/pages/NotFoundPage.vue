<script setup>
import { RouterLink } from 'vue-router'
import { Compass } from '@lucide/vue'
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-unal-background px-4">
    <div class="card max-w-md p-8 text-center">
      <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-unal-primary/10 text-unal-primary">
        <Compass class="h-7 w-7" />
      </div>
      <p class="mt-5 text-sm font-semibold uppercase tracking-widest text-unal-primary">404</p>
      <h1 class="mt-1 text-2xl font-semibold text-unal-secondary">Página no encontrada</h1>
      <p class="mt-2 text-sm text-unal-secondary-light">
        La ruta que buscas no existe o fue movida durante la reescritura.
      </p>
      <RouterLink to="/dashboard" class="btn-primary mt-6">Ir al dashboard</RouterLink>
    </div>
  </div>
</template>
