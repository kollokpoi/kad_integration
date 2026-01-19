<!-- src/views/Registration.vue -->
<template>
  <div class="registration-page">
    <Card class="w-full h-full p-5">
      <template #title>
        <div class="flex align-items-center gap-3">
          <i class="pi pi-plug text-primary" style="font-size: 1.5rem"></i>
          <span>{{ application ? application.name : "" }}</span>
        </div>
      </template>
      <template #subtitle>
        Заполните данные для регистрации приложения
      </template>
      <template #content>
        <div class="form">
          <div class="mb-4">
            <label for="domain" class="block font-medium mb-2"
              >Домен портала</label
            >
            <div class="flex items-center">
              <InputText
                id="domain"
                v-model="form.domain"
                placeholder="ваша-компания"
                class="flex-grow-1"
                :class="{ 'p-invalid': errors.domain }"
                autofocus
              />
              <span class="mx-2 text-600">.bitrix24.ru</span>
            </div>
            <small v-if="errors.domain" class="p-error">
              {{ errors.domain }}
            </small>
            <small class="text-600 block mt-2">
              Пример: для портала "company.bitrix24.ru" введите "company"
            </small>
          </div>

          <div class="mb-4">
            <label for="companyName" class="block font-medium mb-2">
              <i class="pi pi-building mr-2"></i>Название компании
            </label>
            <InputText
              id="companyName"
              v-model="form.companyName"
              placeholder="ООО 'Ваша Компания'"
              class="w-full"
            />
          </div>

          <div class="mb-6">
            <label for="email" class="block font-medium mb-2">
              <i class="pi pi-envelope mr-2"></i>Email администратора
            </label>
            <InputText
              id="email"
              v-model="form.adminEmail"
              type="email"
              placeholder="admin@company.com"
              class="w-full"
            />
            <small class="text-600"
              >Для уведомлений и восстановления доступа</small
            >
          </div>

          <Card class="mb-4 p-3" v-if="application">
            <template #title>
              <div class="flex items-center gap-2">
                <i class="pi pi-info-circle"></i>
                <span>Информация о приложении</span>
              </div>
            </template>

            <template #content>
              <p>Описание:</p>
              <p class="font-medium">{{ application.desctiprion }}</p>
              <p>Текущая версия</p>
              <p class="font-medium">{{ application.version }}</p>
            </template>
          </Card>

          <Button
            label="Подключить"
            icon="pi pi-check"
            class="w-full"
            @click="handleSubmit"
          />

          <Message v-if="error" severity="error" class="mt-3">
            {{ error }}
          </Message>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth.store";
import apiService from "../services/subscriptionApi";
import { useToast } from "primevue";
import { showError } from "../utils/toastUtils";

const toast = useToast();
const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  domain: "",
  companyName: "",
  adminEmail: "",
});
const errors = ref({});
const isLoading = ref(false);
const error = ref(null);
const appId = import.meta.env.VITE_APP_ID;
const application = ref(null);

const getApplication = async () => {
  try {
    const response = await apiService.applications.get(appId);
    if (response.success) {
      application.value = response.data;
      console.log(app.value);
    } else {
      showError(toast, "Не удалось загрузить приложение");
    }
  } catch (error) {
    showError(toast, "Не удалось загрузить приложение");
  }
};

onMounted(() => {
  if (authStore.isAuthenticated) {
    router.push("/");
  }

  if (typeof BX24 !== "undefined") {
    try {
      const b24Domain = BX24.getDomain();
      if (b24Domain) {
        form.value.domain = b24Domain
          .replace(".bitrix24.ru", "")
          .replace(".bitrix24.com", "");
      }

      const auth = BX24.getAuth();
      if (auth.user && auth.user.email) {
        form.value.adminEmail = auth.user.email;
      }
    } catch (err) {
      console.log("Cannot get data from BX24:", err);
    }
  }
  getApplication();
});

// Валидация
function validate() {
  errors.value = {};

  if (!form.value.domain.trim()) {
    errors.value.domain = "Введите домен портала";
    return false;
  }

  const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9]$/;
  if (!domainRegex.test(form.value.domain)) {
    errors.value.domain = "Некорректный формат домена";
    return false;
  }

  if (form.value.adminEmail && !isValidEmail(form.value.adminEmail)) {
    errors.value.email = "Некорректный email";
    return false;
  }

  return true;
}

function isValidEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}

// Отправка
async function handleSubmit() {
  if (!validate()) return;

  isLoading.value = true;
  error.value = null;

  try {
    const response = await apiService.auth.register(form.value.domain, appId, {
      companyName: form.value.companyName,
      adminEmail: form.value.adminEmail,
    });

    if (response.success) {
      authStore.saveTokens(
        response.data.tokens.accessToken,
        response.data.tokens.refreshToken,
      );
      
      authStore.subscription = response.data.subscription;
      router.push("/");
    } else {
      error.value = response.message || "Ошибка регистрации";
    }
  } catch (err) {
    console.error("Registration error:", err);
    error.value = err.message || "Произошла ошибка при регистрации";
  } finally {
    isLoading.value = false;
  }
}
</script>

<style scoped>
.registration-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

:deep(.p-card) {
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
</style>
