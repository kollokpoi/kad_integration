<!-- src/views/Settings.vue -->
<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Заголовок и кнопка сохранения -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Настройки приложения</h1>
        <p class="text-gray-600 text-sm">
          Настройте параметры вашего приложения для оптимального опыта работы.
        </p>
      </div>
      <div class="mt-4 md:mt-0"></div>
    </div>

    <!-- Вкладки настроек - новый компонент табов -->
    <div class="bg-white shadow-md rounded-lg p-4">
      <Tabs v-model:value="activeTab">
        <TabList>
          <Tab value="0">Общие</Tab>
          <!-- <Tab value="1">Аккаунт</Tab>
          <Tab value="2">Оповещения</Tab>
          <Tab value="3">Конфиденциальность</Tab>
          <Tab value="4">Дополнительно</Tab> -->
        </TabList>
        <TabPanels class="p-0">
          <TabPanel value="0" class="p-0">
            <div class="p-0">
              <GeneralSettings @change="settingsChanged = true" />
            </div>
          </TabPanel>
          <TabPanel value="1">
            <div class="p-0">
              <AccountSettings @change="settingsChanged = true" />
            </div>
          </TabPanel>
          <TabPanel value="2">
            <div class="p-0">
              <NotificationSettings @change="settingsChanged = true" />
            </div>
          </TabPanel>
          <TabPanel value="3">
            <div class="p-0">
              <PrivacySettings @change="settingsChanged = true" />
            </div>
          </TabPanel>
          <TabPanel value="4">
            <div class="p-0">
              <AdvancedSettings @change="settingsChanged = true" />
            </div>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <!-- Уведомление о статусе сохранения -->
    <div
      v-if="saveStatus"
      class="mt-4 p-3 rounded-lg"
      :class="saveStatusClass">
      <p class="text-sm">{{ saveStatus }}</p>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import AccountSettings from '../components/AccountSettings.vue';
import AdvancedSettings from '../components/AdvancedSettings.vue';
import GeneralSettings from '../components/GeneralSettings.vue';
import NotificationSettings from '../components/NotificationSettings.vue';
import PrivacySettings from '../components/PrivacySettings.vue';

export default {
  name: 'Settings',
  components: {
    GeneralSettings,
    AccountSettings,
    NotificationSettings,
    PrivacySettings,
    AdvancedSettings,
  },
  setup() {
    const isSaving = ref(false);
    const settingsChanged = ref(false);
    const saveStatus = ref('');
    const saveStatusClass = ref('');
    const activeTab = ref('0');

    const saveSettings = () => {
      if (!settingsChanged.value) {
        saveStatus.value = 'Нет изменений для сохранения';
        saveStatusClass.value = 'bg-blue-100 text-blue-800';

        // Автоматически скрыть уведомление через 3 секунды
        setTimeout(() => {
          saveStatus.value = '';
        }, 3000);

        return;
      }

      isSaving.value = true;

      // Имитация сохранения настроек
      setTimeout(() => {
        isSaving.value = false;
        settingsChanged.value = false;

        saveStatus.value = 'Настройки успешно сохранены';
        saveStatusClass.value = 'bg-green-100 text-green-800';

        // Автоматически скрыть уведомление через 3 секунды
        setTimeout(() => {
          saveStatus.value = '';
        }, 3000);
      }, 800);
    };

    return {
      isSaving,
      settingsChanged,
      saveSettings,
      saveStatus,
      saveStatusClass,
      activeTab,
    };
  },
};
</script>

<style scoped>
/* Стили для нового компонента табов можно добавить по необходимости */
</style>
