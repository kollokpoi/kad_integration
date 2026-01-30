<template>
  <div class="bg-white rounded-lg shadow-sm mt-2 p-2">
    <div class="mb-8">
      <h3 class="text-lg font-semibold text-gray-700 mb-4">
        Настройки синхронизации
      </h3>
      <p class="text-gray-600 mb-4">
        Выберите сущности Битрикс24, к которым следует привязать приложение:
      </p>

      <div class="mb-6">
        <div class="mb-3">
          <label class="block text-sm font-medium mb-2">
            Частота синхронизации
          </label>
          <InputNumber v-model="settings.frequency_days" />
        </div>

        <label class="block text-sm font-medium mb-2">
          Сохранять результаты в таймлайн:
          <Checkbox v-model="settings.save_to_timeline" :binary="true" class="ml-2" />
        </label>

        <label class="block text-sm font-medium mb-2">
          Сохранять результаты в чат:
          <Checkbox v-model="settings.save_to_chat" :binary="true" class="ml-2" />
        </label>

        <label class="block text-sm font-medium mb-2">
          Использовать глобальные настройки:
          <Checkbox v-model="settings.global_settings" :binary="true" class="ml-2" />
        </label>

        <label class="block text-sm font-medium mb-2">
          добавлять заседания в календарь:
          <Checkbox v-model="settings.save_to_calendar" :binary="true" class="ml-2" />
        </label>

        <label class="block text-sm font-medium mb-2">
          Последняя синхронизация: {{ settings.last_sync }}
        </label>
      </div>
    </div>

    <div class="flex gap-4 justify-end">
      <Button label="Отмена" text @click="resetSettings" />
      <Button label="Сохранить настройки" icon="pi pi-save" severity="success" class="btn-main" @click="saveSettings"
        :loading="saving" />
    </div>

    <Dialog v-model:visible="showConnectionDialog" header="Подключение к Битрикс24" :modal="true" :closable="false"
      :style="{ width: '450px' }">
      <div class="flex flex-col items-center p-4">
        <i class="pi pi-spin pi-spinner text-4xl text-primary mb-4" v-if="connecting"></i>
        <i class="pi pi-check-circle text-4xl text-green-500 mb-4" v-else-if="connected"></i>
        <i class="pi pi-exclamation-circle text-4xl text-amber-500 mb-4" v-else></i>

        <h3 class="text-lg font-semibold text-gray-800 mb-2">
          {{ connectionStatus.title }}
        </h3>
        <p class="text-center text-gray-600 mb-4">
          {{ connectionStatus.message }}
        </p>
      </div>

      <template #footer>
        <Button label="Закрыть" text @click="showConnectionDialog = false" :disabled="connecting" />
        <Button v-if="!connected" label="Повторить" icon="pi pi-refresh" @click="connectToBitrix24"
          :disabled="connecting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { useToast } from "primevue/usetoast";
import { computed, onMounted, reactive, ref } from "vue";
import bitrixService from "../services/bitrixService.js";
import { useAuthStore } from "@payment-app/authSdk";

const authStore = useAuthStore();
const emit = defineEmits(["change"]);

const toast = useToast();

const emitChange = () => {
  emit("change");
};

const settings = reactive({
  last_sync: null,
  global_settings: false,
  frequency_days: 7,
  save_to_chat: false,
  save_to_timeline: true,
  save_to_calendar: false,
});

const saving = ref(false);

const showConnectionDialog = ref(false);
const connecting = ref(false);
const connected = ref(false);

const connectionStatus = computed(() => {
  if (connecting.value) {
    return {
      title: "Подключение...",
      message: "Устанавливаем соединение с Битрикс24, пожалуйста, подождите.",
    };
  } else if (connected.value) {
    return {
      title: "Подключено",
      message: "Соединение с Битрикс24 успешно установлено.",
    };
  } else {
    return {
      title: "Ошибка подключения",
      message:
        "Не удалось установить соединение с Битрикс24. Проверьте настройки и попробуйте снова.",
    };
  }
});

const showNotification = (severity, summary, detail, life = 3000) => {
  toast.add({
    severity,
    summary,
    detail,
    life,
  });
};

const saveSettings = async () => {
  try {
    const sync_settings = {
      last_sync: settings.last_sync,
      global_settings: settings.global_settings,
      frequency_days: settings.frequency_days,
      save_to_chat: settings.save_to_chat,
      save_to_timeline: settings.save_to_timeline,
      save_to_calendar: settings.save_to_calendar
    };
    const response = await authStore.updateMetadata({ sync_settings })
    if (response.success) {
      showNotification(
        "success",
        "Настройки сохранены",
        "Настройки приложения успешно сохранены",
      );
    }
    emitChange();
  } catch (error) {
    console.error("Ошибка при сохранении настроек:", error);
    showNotification("error", "Ошибка", "Не удалось сохранить настройки");
  } finally {
    saving.value = false;
  }
};

const connectToBitrix24 = async () => {
  try {
    connecting.value = true;

    await new Promise((resolve) => setTimeout(resolve, 2000));

    const result = await bitrixService.checkConnection();
    connected.value = result.success;

    if (connected.value) {
      showNotification(
        "success",
        "Подключено",
        "Соединение с Битрикс24 успешно установлено",
      );
    }
  } catch (error) {
    console.error("Ошибка при подключении к Битрикс24:", error);
    connected.value = false;
    showNotification("error", "Ошибка", "Не удалось подключиться к Битрикс24");
  } finally {
    connecting.value = false;
  }
};

// Сброс настроек
const resetSettings = async () => {
  settings.last_sync = null;
  settings.global_settings = false;
  settings.frequency_days = 7;
  settings.save_to_chat = false;
  settings.save_to_timeline = true;
  settings.save_to_calendar = false;

  showNotification(
    "info",
    "Сброс",
    "Настройки сброшены до значений по умолчанию",
  );
  await saveSettings()
  emitChange();
};

onMounted(async () => {
  try {
    const metadata = authStore.subscription.metadata;

    if (metadata.sync_settings) {
      Object.assign(settings, metadata.sync_settings);
      connected.value = await bitrixService.isConnected();
    }
  } catch (error) {
    console.error("Ошибка при загрузке настроек:", error);
    showNotification(
      "error",
      "Ошибка загрузки",
      "Не удалось загрузить сохраненные настройки",
    );
  }
});

import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import Dialog from "primevue/dialog";
import InputNumber from "primevue/inputnumber";
</script>
