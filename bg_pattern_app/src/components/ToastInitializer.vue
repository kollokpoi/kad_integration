// src/components/ToastInitializer.vue
<template>
  <!-- Toast компонент должен быть в верхнем уровне приложения -->
  <Toast />
</template>

<script>
import { useToast } from 'primevue/usetoast';
import { onMounted } from 'vue';
import { initToastService } from '../utils/toastWrapper';

export default {
  setup() {
    const toast = useToast();

    onMounted(() => {
      // Инициализируем сервис
      initToastService(toast);
    });

    return {};
  },
};
</script>
