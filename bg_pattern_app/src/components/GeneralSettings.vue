<template>
  <div class="bg-white rounded-lg shadow-sm mt-2 p-2">
    <div class="mb-8">
      <h3 class="text-lg font-semibold text-gray-700 mb-4">
        Привязка к сущностям Битрикс24
      </h3>
      <p class="text-gray-600 mb-4">
        Выберите сущности Битрикс24, к которым следует привязать приложение:
      </p>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div
          v-for="entity in entities"
          :key="entity.id"
          class="border border-gray-200 rounded-lg p-4 transition-all hover:shadow-md"
        >
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <Avatar
                :icon="entity.icon"
                :style="{ backgroundColor: entity.color }"
                size="large"
                class="mr-3"
              />
              <div>
                <h4 class="font-medium text-gray-800">{{ entity.name }}</h4>
                <p class="text-sm text-gray-500">{{ entity.description }}</p>
              </div>
            </div>
            <Checkbox
              v-model="entity.enabled"
              :binary="true"
              @change="emitChange"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="flex gap-4 justify-end">
      <Button label="Отмена" text @click="resetSettings" />
      <Button
        label="Сохранить настройки"
        icon="pi pi-save"
        severity="success"
        class="btn-main"
        @click="saveSettings"
        :loading="saving"
      />
    </div>

    <Dialog
      v-model:visible="showConnectionDialog"
      header="Подключение к Битрикс24"
      :modal="true"
      :closable="false"
      :style="{ width: '450px' }"
    >
      <div class="flex flex-col items-center p-4">
        <i
          class="pi pi-spin pi-spinner text-4xl text-primary mb-4"
          v-if="connecting"
        ></i>
        <i
          class="pi pi-check-circle text-4xl text-green-500 mb-4"
          v-else-if="connected"
        ></i>
        <i
          class="pi pi-exclamation-circle text-4xl text-amber-500 mb-4"
          v-else
        ></i>

        <h3 class="text-lg font-semibold text-gray-800 mb-2">
          {{ connectionStatus.title }}
        </h3>
        <p class="text-center text-gray-600 mb-4">
          {{ connectionStatus.message }}
        </p>
      </div>

      <template #footer>
        <Button
          label="Закрыть"
          text
          @click="showConnectionDialog = false"
          :disabled="connecting"
        />
        <Button
          v-if="!connected"
          label="Повторить"
          icon="pi pi-refresh"
          @click="connectToBitrix24"
          :disabled="connecting"
        />
      </template>
    </Dialog>

  </div>
</template>

<script setup>
import { useToast } from "primevue/usetoast";
import { computed, onMounted, reactive, ref } from "vue";
import bitrixService from "../services/bitrixService.js";

// Эмиты для родительского компонента
const emit = defineEmits(["change"]);

const toast = useToast();

// Функция для отправки события изменения родительскому компоненту
const emitChange = () => {
  emit("change");
};

// Сущности Битрикс24
const entities = reactive([
  {
    id: "leads",
    name: "Лиды",
    description: "Потенциальные клиенты на начальном этапе",
    icon: "pi pi-user-plus",
    color: "#ff9800",
    enabled: false,
  },
  {
    id: "deals",
    name: "Сделки",
    description: "Текущие и потенциальные продажи",
    icon: "pi pi-dollar",
    color: "#4caf50",
    enabled: false,
  },
  {
    id: "companies",
    name: "Компании",
    description: "Организации, с которыми ведется работа",
    icon: "pi pi-building",
    color: "#2196f3",
    enabled: false,
  },
  {
    id: "contacts",
    name: "Контакты",
    description: "Физические лица, являющиеся клиентами",
    icon: "pi pi-user",
    color: "#9c27b0",
    enabled: false,
  },
]);

// Настройки приложения
const settings = reactive({
  refreshInterval: 15,
  displayMode: "detailed",
  notifications: true,
});

// Состояние для сохранения настроек
const saving = ref(false);

// Состояние подключения к Битрикс24
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

// Функция для отображения уведомлений с учетом настроек
const showNotification = (severity, summary, detail, life = 3000) => {
  toast.add({
    severity,
    summary,
    detail,
    life,
  });
};

// Функция сохранения настроек
const saveSettings = async () => {
  try {
    saving.value = true;

    // Собираем выбранные и невыбранные сущности
    const selectedEntities = entities
      .filter((entity) => entity.enabled)
      .map((entity) => entity.id);
    const unselectedEntities = entities
      .filter((entity) => !entity.enabled)
      .map((entity) => entity.id);

    // Если выбраны сущности, а подключение к Bitrix24 отсутствует – пытаемся подключиться
    if (selectedEntities.length > 0 && !connected.value) {
      showConnectionDialog.value = true;
      await connectToBitrix24();
      if (!connected.value) {
        showNotification(
          "error",
          "Ошибка",
          "Не удалось установить соединение с Bitrix24",
        );
        return;
      }
    }

    // Если подключение установлено и есть выбранные сущности – вызываем привязку
    if (selectedEntities.length > 0 && connected.value) {
      const bindResult =
        await bitrixService.connectToEntities(selectedEntities);
      if (bindResult) {
        showNotification(
          "info",
          "Успешно",
          "Привязка сущностей выполнена успешно",
        );
      } else {
        showNotification("error", "Ошибка", "Ошибка при привязке сущностей");
      }
    }

    // Если подключение установлено и есть невыбранные сущности – вызываем отвязку
    if (unselectedEntities.length > 0 && connected.value) {
      const unbindResult =
        await bitrixService.disconnectFromEntities(unselectedEntities);
      if (unbindResult) {
        showNotification(
          "info",
          "Успешно",
          "Отвязка сущностей выполнена успешно",
        );
      } else {
        showNotification("error", "Ошибка", "Ошибка при отвязке сущностей");
      }
    }

    // Сохраняем настройки через API
    await bitrixService.saveSettings({
      entities: selectedEntities,
      settings: { ...settings },
    });

    showNotification(
      "success",
      "Настройки сохранены",
      "Настройки приложения успешно сохранены",
    );
    emitChange();
  } catch (error) {
    console.error("Ошибка при сохранении настроек:", error);
    showNotification("error", "Ошибка", "Не удалось сохранить настройки");
  } finally {
    saving.value = false;
  }
};

// Функция подключения к Битрикс24
const connectToBitrix24 = async () => {
  try {
    connecting.value = true;

    // Имитация подключения к Битрикс24
    await new Promise((resolve) => setTimeout(resolve, 2000));

    // Вызываем метод из сервиса для проверки подключения
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
const resetSettings = () => {
  entities.forEach((entity) => {
    entity.enabled = false;
  });
  settings.refreshInterval = 15;
  settings.displayMode = "detailed";
  settings.notifications = true;

  showNotification(
    "info",
    "Сброс",
    "Настройки сброшены до значений по умолчанию",
  );
  emitChange();
};

// Загрузка настроек при монтировании компонента
onMounted(async () => {
  try {
    const savedSettings = await bitrixService.getSettings();

    // Устанавливаем настройки из сохраненных данных
    if (savedSettings) {
      // Устанавливаем выбранные сущности
      if (savedSettings.entities) {
        entities.forEach((entity) => {
          entity.enabled = savedSettings.entities.includes(entity.id);
        });
      }

      // Устанавливаем другие настройки
      if (savedSettings.settings) {
        Object.assign(settings, savedSettings.settings);
      }

      // Проверяем состояние подключения
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

import Avatar from "primevue/avatar";
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import Dialog from "primevue/dialog";
</script>
