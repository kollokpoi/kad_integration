<!-- views/AuthErrorView.vue -->
<template>
  <div class="auth-error-page">
    <div class="error-card">
      <h1>Ошибка авторизации</h1>
      <p v-if="$route.query.error">{{ $route.query.error }}</p>
      <p v-else>Не удалось загрузить приложение</p>
      
      <div class="actions">
        <Button @click="retry" label="Попробовать снова" />
        <Button @click="goHome" label="На главную" severity="secondary" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const router = useRouter()

const retry = () => {
  window.location.reload()
}

const goHome = () => {
  router.push('/')
}
</script>

<style scoped>
.auth-error-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.error-card {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  max-width: 500px;
  text-align: center;
}

.actions {
  margin-top: 2rem;
  display: flex;
  gap: 1rem;
  justify-content: center;
}
</style>