<template>
  <slot v-if="!hasError" />
  <div v-else class="error-boundary">
    <div class="error-content">
      <h1>Ошибка загрузки приложения</h1>
      <p>{{ errorMessage }}</p>
      <button @click="retry" class="retry-btn">
        Попробовать снова
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onErrorCaptured } from 'vue'

const hasError = ref(false)
const errorMessage = ref('')

onErrorCaptured((err) => {
  console.error('Поймана ошибка:', err)
  
  if (err.message.includes('авторизации') || err.message.includes('инициализации')) {
    hasError.value = true
    errorMessage.value = err.message || 'Не удалось загрузить приложение'
    
    return false
  }
  
  return true
})

const retry = () => {
  hasError.value = false
  window.location.reload()
}
</script>

<style scoped>
.error-boundary {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
}

.error-content {
  text-align: center;
  max-width: 400px;
  padding: 2rem;
}

.retry-btn {
  margin-top: 1rem;
  padding: 0.5rem 1.5rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style>