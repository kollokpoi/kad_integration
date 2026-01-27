<!-- App.vue -->
<template>
  <AppErrorBoundary>
    <Suspense>
      <template #default>
        <AppInitializer>
          <RouterView />
        </AppInitializer>
      </template>

      <template #fallback>
        <div class="global-loader">
          <ProgressSpinner />
          <p>Инициализация приложения...</p>
        </div>
      </template>
    </Suspense>
  </AppErrorBoundary>
  <Toast position="top-right" />
</template>

<script setup>
import { Suspense } from 'vue'
import { RouterView } from 'vue-router'
import ProgressSpinner from 'primevue/progressspinner'
import AppErrorBoundary from './components/AppErrorBoundary.vue'
import AppInitializer from './components/AppInitializer.vue'
import { Toast } from 'primevue'
</script>

<style>
.global-loader {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: white;
  z-index: 9999;
}
</style>