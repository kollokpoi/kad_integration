<template>
  <div class="whats-new-section bg-gray-50 min-h-screen p-4">
    <!-- Табы -->
    <TabView
      v-model:activeIndex="activeTabIndex"
      class="bg-white rounded-lg shadow-sm">
      <TabPanel
        v-for="tab in tabs"
        :key="tab.name"
        :header="tab.title">
        <!-- Вкладка История изменений -->
        <template v-if="tab.name === 'history'">
          <div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">История изменений</h2>
            <!-- Таймлайн -->
            <Timeline
              :value="sortedHistory"
              class="p-4">
              <template #marker="slotProps">
                <div
                  :class="[
                    'flex items-center justify-center w-8 h-8 rounded-full',
                    slotProps.item.statusClass === 'fixed' ? 'bg-green-500' : 'bg-blue-500',
                  ]">
                  <i
                    :class="[
                      slotProps.item.statusClass === 'fixed' ? 'pi pi-check' : 'pi pi-plus',
                      'text-white',
                    ]"></i>
                </div>
              </template>
              <template #content="slotProps">
                <Card
                  class="mb-4 border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300">
                  <template #header>
                    <div
                      class="flex justify-between items-center p-4 bg-gray-50 border-b border-gray-200">
                      <div>
                        <h3 class="text-lg font-medium text-gray-800">
                          {{ formatDate(slotProps.item.date) }}
                        </h3>
                        <div class="text-sm text-gray-600">Версия {{ slotProps.item.version }}</div>
                      </div>
                      <Tag :severity="slotProps.item.statusClass === 'fixed' ? 'success' : 'info'">
                        {{ slotProps.item.statusText }}
                      </Tag>
                    </div>
                  </template>
                  <template #content>
                    <p class="text-gray-700 p-4 leading-relaxed">
                      {{ slotProps.item.description }}
                    </p>
                  </template>
                </Card>
              </template>
            </Timeline>
          </div>
        </template>

        <!-- Вкладка Планируемый функционал -->
        <template v-else-if="tab.name === 'planned'">
          <div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Планируемый функционал</h2>
            <!-- Блок сортировки и поиска -->
            <div class="flex flex-col md:flex-row gap-4 mb-6">
              <div class="w-full md:w-64">
                <Dropdown
                  v-model="sortField"
                  :options="sortOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Сортировать по..."
                  class="w-full" />
              </div>
              <div class="w-full">
                <span class="p-input-icon-left w-full">
                  <i></i>
                  <InputText
                    v-model="searchQuery"
                    placeholder="Поиск по идеям..."
                    class="w-full" />
                </span>
              </div>
            </div>
            <!-- Таблица планируемых функций -->
            <DataTable
              :value="filteredPlanned"
              class="shadow-sm"
              stripedRows
              responsiveLayout="scroll"
              :paginator="true"
              :rows="5"
              :rowsPerPageOptions="[5, 10, 20]"
              paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
              currentPageReportTemplate="{first} - {last} из {totalRecords}">
              <Column>
                <template #header>
                  <div class="flex items-center gap-2">
                    <span>Лайки</span>
                    <i class="pi pi-heart text-pink-500"></i>
                  </div>
                </template>
                <template #body="slotProps">
                  <Button
                    @click="likeFeature(slotProps.data.id)"
                    class="p-button-rounded p-button-outlined p-button-warning"
                    :label="slotProps.data.likes.toString()"
                    icon="pi pi-heart-fill" />
                </template>
              </Column>
              <Column
                field="date"
                header="Дата добавления 📅">
                <template #body="slotProps">
                  {{ formatDate(slotProps.data.date) }}
                </template>
              </Column>
              <Column
                field="idea"
                header="Идея"
                style="min-width: 200px" />
              <Column style="width: 130px">
                <template #body="slotProps">
                  <Button
                    label="Подробнее"
                    icon="pi pi-info-circle"
                    @click="showDetails(slotProps.data)"
                    class="p-button-sm" />
                </template>
              </Column>
            </DataTable>
          </div>
        </template>
      </TabPanel>
    </TabView>

    <!-- Диалог с подробной информацией -->
    <Dialog
      v-model:visible="detailsDialog.visible"
      :header="detailsDialog.title"
      :style="{ width: '450px' }"
      :modal="true"
      :closable="true">
      <div class="p-4">
        <p class="text-gray-700">{{ detailsDialog.content }}</p>
        <div class="mt-4 flex justify-between">
          <Tag severity="info">
            <i class="pi pi-calendar mr-2"></i>
            <span>{{ detailsDialog.date }}</span>
          </Tag>
          <Button
            icon="pi pi-heart"
            :label="detailsDialog.likes?.toString()"
            class="p-button-rounded p-button-warning p-button-outlined" />
        </div>
      </div>
    </Dialog>

    <!-- Диалог для чата -->
    <Dialog
      v-model:visible="chatDialog.visible"
      header="Чат"
      :style="{ width: '400px' }"
      :modal="true"
      :closable="true">
      <div class="p-4">
        <p class="text-gray-700">{{ chatDialog.content }}</p>
        <div class="flex justify-end">
          <Button
            label="Закрыть"
            icon="pi pi-times"
            @click="chatDialog.visible = false"
            class="p-button-text" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { computed, inject, onMounted, ref } from 'vue';
import BitrixService from '../services/bitrixService.js'; // Импортируем сервис для работы с Bitrix

const activeTabIndex = ref(0);
const detailsDialog = ref({
  visible: false,
  title: '',
  content: '',
  date: '',
  likes: 0,
});
const chatDialog = ref({
  visible: false,
  title: 'Чат',
  content: 'Открываем чат для обсуждения идеи...',
});

// Получаем productId через inject (глобально передан через provide)
const productId = inject('productId', '0');

// Табы
const tabs = [
  { name: 'history', title: 'История изменений' },
  { name: 'planned', title: 'Планируемый функционал' },
];

// Данные, загружаемые с сервера
const historyItems = ref([]);
const plannedFeatures = ref([]);

// Параметры сортировки и поиска
const sortField = ref('likes');
const searchQuery = ref('');

const sortOptions = [
  { label: 'Сортировать по лайкам', value: 'likes' },
  { label: 'Сортировать по дате', value: 'date' },
];

// Загрузка данных с сервера
const loadData = async () => {
  try {
    const response = await fetch(
      `https://bg59.online/Apps/bg_pattern_app/api/features.php?productId=${productId}`,
    );
    const data = await response.json();
    // Ожидается, что сервер вернет объект { historyItems: [...], plannedFeatures: [...] }
    historyItems.value = data.historyItems || [];
    plannedFeatures.value = data.plannedFeatures || [];
  } catch (error) {
    console.error('Ошибка загрузки данных:', error);
  }
};

onMounted(() => {
  loadData();
});

// Сортировка истории (последние сверху)
const sortedHistory = computed(() => {
  return [...historyItems.value].sort((a, b) => new Date(b.date) - new Date(a.date));
});

// Фильтрация и сортировка планируемого функционала
const filteredPlanned = computed(() => {
  let arr = [...plannedFeatures.value];
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    arr = arr.filter((item) => item.idea.toLowerCase().includes(query));
  }
  if (sortField.value === 'likes') {
    arr.sort((a, b) => b.likes - a.likes);
  } else if (sortField.value === 'date') {
    arr.sort((a, b) => new Date(b.date) - new Date(a.date));
  }
  return arr;
});

// Форматирование даты
const formatDate = (dateStr) => {
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return new Date(dateStr).toLocaleDateString('ru-RU', options);
};

const likeFeature = async (featureId) => {
  try {
    console.log('Нажатие на запись с id:', featureId);

    const { liked } = await BitrixService.toggleUserLike(featureId);
    console.log('Новый статус лайка для записи', featureId, ':', liked);

    const params = new URLSearchParams({
      action: 'update_like',
      id: featureId,
      productId,
      liked: liked ? 'true' : 'false',
    });
    console.log('Параметры запроса:', params.toString());

    const response = await fetch('https://bg59.online/Apps/bg_pattern_app/api/features.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params,
    });

    const result = await response.json();
    console.log('Ответ сервера:', result);

    if (result.success) {
      // Ищем именно ту запись, по которой кликнули, по уникальному id
      const featureIndex = plannedFeatures.value.findIndex(
        (item) => Number(item.id) === Number(featureId),
      );
      if (featureIndex !== -1) {
        plannedFeatures.value[featureIndex].likes = result.likes;
      } else {
        console.warn('Не найдена запись с id:', featureId);
      }
    } else {
      console.error('Сервер вернул ошибку:', result.message);
    }
  } catch (error) {
    console.error('Ошибка обновления лайка:', error);
  }
};

// Показать диалог с подробностями идеи
const showDetails = (feature) => {
  detailsDialog.value = {
    visible: true,
    title: 'Детали идеи',
    content: feature.idea,
    date: formatDate(feature.date),
    likes: feature.likes,
  };
};

// Открыть диалог чата
const openChat = () => {
  chatDialog.value.visible = true;
};
</script>

<style>
/* Обычные стили без @apply */
.p-tabview .p-tabview-nav {
  background-color: rgb(249, 250, 251);
  border: 0;
  border-top-left-radius: 0.5rem;
  border-top-right-radius: 0.5rem;
}

.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link {
  border-color: rgb(59, 130, 246);
  color: rgb(29, 78, 216);
}

.p-tabview .p-tabview-nav li .p-tabview-nav-link {
  transition-property: color, background-color, border-color;
  transition-duration: 0.2s;
}

.p-timeline .p-timeline-event-content {
  width: 100%;
}

.p-timeline .p-timeline-event-opposite {
  display: none;
}

.p-card {
  transition-property: box-shadow;
  transition-duration: 0.3s;
}

.p-button {
  transition-property: all;
  transition-duration: 0.2s;
}

.p-inputtext:focus {
  box-shadow: 0 0 0 2px rgb(191, 219, 254);
}

.p-dropdown:focus {
  box-shadow: 0 0 0 2px rgb(191, 219, 254);
}

.p-dropdown-panel .p-dropdown-items .p-dropdown-item.p-highlight {
  background-color: rgb(219, 234, 254);
  color: rgb(29, 78, 216);
}

.p-datatable .p-datatable-tbody > tr:nth-child(even) {
  background-color: rgb(249, 250, 251);
}

.p-datatable .p-datatable-tbody > tr:hover {
  background-color: rgb(239, 246, 255);
}

.p-datatable .p-paginator {
  background-color: white;
  border-top: 1px solid rgb(229, 231, 235);
}
</style>
