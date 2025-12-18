<template>
  <div class="p-6">
    <Toast position="top-right" />
    <PageHeader>
      <template #title>Детальный просмотр дела</template>
      <template #subtitle>Номер дела: {{ caseNumber }}</template>
    </PageHeader>

    <!-- Карточка с основной информацией -->
    <Card class="shadow-md mb-4">
      <template #title>Основная информация</template>
      <template #content>
        <div
          v-if="loading"
          class="mb-4">
          <ProgressBar
            mode="indeterminate"
            style="height: 6px"
            class="mb-4" />
        </div>
        <div v-else-if="caseData">
          <p><strong>Суд:</strong> {{ caseData.court }}</p>
          <p><strong>Судья:</strong> {{ caseData.judge }}</p>
          <p><strong>Истец:</strong> {{ caseData.plaintiff }}</p>
          <p><strong>Ответчик:</strong> {{ caseData.respondent }}</p>
          <p><strong>Дата:</strong> {{ formatDate(caseData.date) }}</p>
        </div>
        <div v-else>
          <p>Дело не найдено.</p>
        </div>
      </template>
    </Card>

    <!-- Вкладки с хронологией, комментариями и аналитикой -->
    <TabView>
      <!-- Вкладка с хронологией -->
      <TabPanel header="Хронология">
        <div
          v-if="loading"
          class="mb-4">
          <ProgressBar
            mode="indeterminate"
            style="height: 6px"
            class="mb-4" />
        </div>
        <div v-else-if="caseData && caseData.case_details && caseData.case_details.length > 0">
          <Timeline
            :value="timelineEvents"
            class="w-full">
            <template #marker="{ item }">
              <i :class="getTimelineIcon(item.type)"></i>
            </template>
            <template #content="{ item }">
              <div class="p-3 border border-gray-200 rounded-md shadow-sm mb-3 bg-white">
                <div class="flex justify-between mb-2">
                  <span class="font-semibold text-lg">{{ item.result }}</span>
                  <span class="text-blue-600 font-medium">{{ formatDate(item.date) }}</span>
                </div>
                <div class="flex flex-col">
                  <div class="flex items-start mb-1">
                    <span class="font-medium text-gray-700 mr-2">Тип:</span>
                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-sm">
                      {{ item.type }}
                    </span>
                  </div>
                  <div
                    class="flex items-start mb-1"
                    v-if="item.subject">
                    <span class="font-medium text-gray-700 mr-2">Субъект:</span>
                    <span>{{ item.subject }}</span>
                  </div>
                  <div
                    class="flex items-start mb-1"
                    v-if="item.additional_info">
                    <span class="font-medium text-gray-700 mr-2">Дополнительно:</span>
                    <span>{{ item.additional_info }}</span>
                  </div>
                  <div v-if="item.file_link">
                    <a
                      :href="item.file_link"
                      target="_blank"
                      class="inline-flex items-center mt-2 text-blue-600 hover:text-blue-800 hover:underline">
                      <i class="pi pi-file-pdf mr-1"></i>
                      <span>Просмотреть документ</span>
                    </a>
                  </div>
                </div>
              </div>
            </template>
          </Timeline>
        </div>
        <div v-else>
          <p>Хронология отсутствует.</p>
        </div>
      </TabPanel>

      <!-- Вкладка с комментариями -->
      <TabPanel header="Комментарии">
        <CommentsTimeline
          :caseNumber="caseNumber"
          @updateComments="updateComments" />
      </TabPanel>

      <!-- Новая вкладка с аналитикой -->
      <TabPanel header="Аналитика">
        <CaseAnalytics
          :caseData="caseData"
          :loading="loading" />
      </TabPanel>
    </TabView>
  </div>
</template>

<script setup>
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import { fetchArbitrationCases } from '../services/kad-api';
import CaseAnalytics from '../views/CaseAnalytics.vue'; // Импорт нового компонента
import CommentsTimeline from '../views/CommentsTimeline.vue';

// Извлекаем номер дела из параметров маршрута
const route = useRoute();
const toast = useToast();
const caseNumber = route.params.caseNumber;
const loading = ref(false);
const caseData = ref(null);

// Для комментариев
const caseComments = ref([]);

// Функция форматирования даты
const formatDate = (dateString) => {
  if (!dateString) return 'Нет даты';
  if (/^\d{2}\.\d{2}\.\d{4}$/.test(dateString)) {
    const [day, month, year] = dateString.split('.');
    const isoDate = `${year}-${month}-${day}`;
    const date = new Date(isoDate);
    return isNaN(date.getTime())
      ? 'Неверная дата'
      : date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
  } else {
    const date = new Date(dateString);
    return isNaN(date.getTime())
      ? dateString
      : date.toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' });
  }
};

// Функция для получения иконки по типу события
const getTimelineIcon = (type) => {
  const iconMap = {
    'Решения и постановления': 'pi pi-check-circle text-green-600',
    Определение: 'pi pi-file-edit text-blue-600',
    Заявление: 'pi pi-pencil text-purple-600',
    Ходатайства: 'pi pi-bookmark text-orange-600',
    'Переписка по делу': 'pi pi-envelope text-teal-600',
    'Дополнение к делу': 'pi pi-folder-open text-indigo-600',
    'Прочие судебные документы': 'pi pi-paperclip text-gray-600',
  };
  if (!iconMap[type]) {
    console.warn('Неизвестный тип события для хронологии:', type);
  }
  return iconMap[type] || 'pi pi-file text-blue-600';
};

// Вычисляемое свойство для формирования хронологии (от новых к старым)
const timelineEvents = computed(() => {
  if (!caseData.value || !caseData.value.case_details) return [];
  return [...caseData.value.case_details].sort((a, b) => {
    const dateA = new Date(a.date.split('.').reverse().join('-'));
    const dateB = new Date(b.date.split('.').reverse().join('-'));
    return dateB - dateA;
  });
});

onMounted(async () => {
  loading.value = true;
  try {
    const result = await fetchArbitrationCases({
      type: 'byId',
      caseNumber: caseNumber,
      includeTimeline: true,
    });
    if (Array.isArray(result) && result.length > 0) {
      caseData.value = result[0];
      // Если комментарии входят в данные, их можно инициализировать так:
      caseComments.value = caseData.value.comments || [];
    } else {
      toast.add({
        severity: 'info',
        summary: 'Информация',
        detail: 'Дело не найдено',
        life: 3000,
      });
    }
  } catch (error) {
    console.error('Ошибка при загрузке данных:', error);
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось загрузить данные дела',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
});

// Функция для обновления комментариев, если компонент CommentsTimeline передаст обновлённый список
const updateComments = (newComments) => {
  caseComments.value = newComments;
};
</script>

<style scoped>
:deep(.p-progressbar) {
  border-radius: 0.25rem;
  background: #e2e8f0;
  height: 6px;
}
:deep(.p-progressbar .p-progressbar-value) {
  background: #3b82f6;
}
</style>
