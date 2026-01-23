<!-- components/AppInitializer.vue -->
<template>
  <slot />
</template>

<script setup>
import { useAuthStore } from '@payment-app/authSdk'
import { useAppInit } from '../composables/useAppInit'

const authStore = useAuthStore()
const { initialize } = useAppInit()

try {
  if (!authStore.isInitialized) {
    await initialize()
  }
} catch (error) {
  throw error
}
</script>