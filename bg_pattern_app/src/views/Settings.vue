<!-- src/views/Settings.vue -->
<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Заголовок и кнопка сохранения -->
    <div
      class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
          Настройки приложения
        </h1>
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
          <Tab value="1">Синхронизация</Tab>
          <!--<Tab value="2">Оповещения</Tab>
          <Tab value="3">Конфиденциальность</Tab>
          <Tab value="4">Дополнительно</Tab> -->
        </TabList>
        <TabPanels class="p-0">
          <TabPanel value="0">
            <GeneralSettings @change="settingsChanged = true" />
          </TabPanel>
          <TabPanel value="1">
            <SyncSettings @change="settingsChanged = true" />
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>

    <div v-if="saveStatus" class="mt-4 p-3 rounded-lg" :class="saveStatusClass">
      <p class="text-sm">{{ saveStatus }}</p>
    </div>
  </div>
</template>

<script>
import { ref } from "vue";
import GeneralSettings from "../components/GeneralSettings.vue";
import SyncSettings from "../components/SyncSettings.vue";

export default {
  name: "Settings",
  components: {
    GeneralSettings,
    SyncSettings
  },
  setup() {
    const isSaving = ref(false);
    const settingsChanged = ref(false);
    const saveStatus = ref("");
    const saveStatusClass = ref("");
    const activeTab = ref("0");

    return {
      isSaving,
      settingsChanged,
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
