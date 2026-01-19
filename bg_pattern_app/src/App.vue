<template>
  <div v-if="isInitializing" class="app-initializing">
    <div class="loader">
      <div class="spinner"></div>
      <p>Инициализация приложения...</p>
    </div>
  </div>

  <router-view v-else />
  <Toast />
</template>
<script setup>
import { ref, onMounted, watch } from "vue";
import { useAuthStore } from "./stores/auth.store";
import { useRouter } from "vue-router";
import { Toast } from "primevue";

const router = useRouter();
const authStore = useAuthStore();
const isInitializing = ref(true);

onMounted(async () => {
  try {
    await authStore.initialize();
    if (authStore.accessToken) {
      await authStore.login();
    }
  } catch (error) {
    console.error("App initialization failed:", error);
  } finally {
    isInitializing.value = false;
  }
});
watch(
  () => authStore.isAuthenticated,
  (isAuth) => {
    if (!isAuth && router.currentRoute.value.meta.requiresAuth) {
      router.push("/tariffs");
    }
  },
);

watch(
  () => authStore.error,
  (error) => {
    if (
      error &&
      (error.includes("subscription") || error.includes("expired"))
    ) {
      router.push("/tariffs");
    }
  },
);
</script>
<style>
.app-initializing {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  z-index: 9999;
}

.loader {
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 20px;
  border: 3px solid #f3f3f3;
  border-top: 3px solid #3498db;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
