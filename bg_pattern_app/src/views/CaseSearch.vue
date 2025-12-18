<template>
  <div class="h-full w-full p-6">
    <Toast position="top-right" />
    <!-- Заголовок страницы -->
    <PageHeader>
      <template #title> Система интеграции с КАД </template>
      <template #subtitle>
        Получайте актуальную информацию о судебных делах из картотеки арбитражных дел.
      </template>
    </PageHeader>

    <!-- Карточка с поиском дела по номеру -->
    <Card class="shadow-md h-full">
      <template #title>
        <span class="font-bold text-black">Поиск дела по номеру</span>
      </template>
      <template #subtitle>
        <div class="text-sm text-black">Данные обновляются в режиме реального времени</div>
      </template>
      <template #content>
        <div class="mb-4">
          <label
              for="case-number-input"
              class="block text-sm font-medium text-gray-700 mb-3"
              >Номер дела</label
          >
          <div class="flex">
            <InputText
              id="case-number-input"
              v-model="searchParams.caseNumber"
              placeholder="Например: А50-5568/2008"
              class="flex-1 p-2"
              :disabled="loading" />
            <div class="flex">
              <Button
                label="Найти дело"
                icon="pi pi-search"
                class="p-button-primary btn-main mx-2"
                @click="fetchData"
                :loading="loading"
                :disabled="loading || !searchParams.caseNumber" />
              <Button
                label="Синхронизация с Битрикс"
                icon="pi pi-refresh"
                class="p-button-success sync-button btn-gray mx-2"
                @click="syncWithBitrix"
                tooltip="Синхронизировать данные с Битрикс" />
            </div>
          </div>
          <Checkbox
                v-model="searchParams.includeTimeline"
                :binary="true"
                :disabled="loading"
                inputId="include-timeline" />
              <label for="include-timeline" class="ml-2 text-sm font-medium text-gray-700">
                Хронология дела
              </label>
        </div>

        <!-- Индикатор загрузки -->
        <div v-if="loading" class="mb-4">
          <div class="text-sm text-blue-600 mb-2 flex items-center">
            <i class="pi pi-spin pi-spinner mr-2"></i>
            <span>Загрузка данных из КАД...</span>
          </div>
          <ProgressBar mode="indeterminate" style="height: 6px" class="mb-4" />
        </div>

        <!-- Таблица с результатами поиска -->
        <div v-if="!loading && cases.length > 0" class="overflow-x-auto">
          <DataTable
            :value="cases"
            responsiveLayout="scroll"
            class="p-datatable-sm"
            stripedRows
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            currentPageReportTemplate="Показано {first} - {last} из {totalRecords} дел">
            <Column field="date" header="Дата" sortable>
              <template #body="{ data }">
                <span class="font-medium">{{ formatDate(data.date) }}</span>
              </template>
            </Column>
            <Column field="case_number" header="Номер дела" sortable>
              <template #body="{ data }">
                <span class="text-blue-600 font-medium">{{ data.case_number }}</span>
              </template>
            </Column>
            <Column field="case_link" header="Ссылка на дело">
              <template #body="{ data }">
                <a
                  :href="data.case_link"
                  target="_blank"
                  class="text-blue-600 hover:text-blue-800 hover:underline flex items-center">
                  <span>Открыть дело</span>
                  <i class="pi pi-external-link ml-1 text-xs"></i>
                </a>
              </template>
            </Column>
            <Column field="judge" header="Судья" sortable />
            <Column field="court" header="Суд" sortable />
            <Column field="plaintiff" header="Истец" sortable>
              <template #body="{ data }">
                <div class="max-w-xs truncate" :title="data.plaintiff">
                  {{ data.plaintiff }}
                </div>
              </template>
            </Column>
            <Column field="respondent" header="Ответчик" sortable>
              <template #body="{ data }">
                <div class="max-w-xs truncate" :title="data.respondent">
                  {{ data.respondent }}
                </div>
              </template>
            </Column>
            <Column header="Действия">
              <template #body="{ data }">
                <Button
                  v-if="data.case_details && data.case_details.length > 0"
                  icon="pi pi-list"
                  label="Хронология"
                  class="p-button-info"
                  @click="showTimelineDialog(data)"
                  tooltip="Посмотреть хронологию дела" />
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Скелеты для загрузки -->
        <div v-if="!loading && cases.length === 0 && firstLoad" class="overflow-x-auto">
          <DataTable :value="skeletonData" class="p-datatable-sm" responsiveLayout="scroll">
            <Column field="date" header="Дата">
              <template #body>
                <Skeleton height="1.5rem" class="mb-2" />
              </template>
            </Column>
            <Column field="case_number" header="Номер дела">
              <template #body>
                <Skeleton height="1.5rem" class="mb-2" />
              </template>
            </Column>
            <Column field="case_link" header="Ссылка на дело">
              <template #body>
                <Skeleton height="1.5rem" class="mb-2" />
              </template>
            </Column>
            <Column field="judge" header="Судья">
              <template #body>
                <Skeleton height="1.5rem" class="mb-2" />
              </template>
            </Column>
            <Column field="court" header="Суд">
              <template #body>
                <Skeleton height="1.5rem" class="mb-2" />
              </template>
            </Column>
            <Column field="plaintiff" header="Истец">
              <template #body>
                <Skeleton height="1.5rem" class="mb-2" />
              </template>
            </Column>
            <Column field="respondent" header="Ответчик">
              <template #body>
                <Skeleton height="1.5rem" class="mb-2" />
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Сообщение об отсутствии данных -->
        <div v-if="!loading && cases.length === 0 && !firstLoad" class="text-center py-8">
          <i class="pi pi-search text-4xl text-gray-400 mb-4"></i>
          <p class="text-gray-500">Нет доступных данных по судебным делам</p>
          <p class="text-sm text-gray-400 mt-2">
            Попробуйте изменить параметры поиска или обновить данные позже
          </p>
        </div>
      </template>
    </Card>

    <!-- Диалог с хронологией дела -->
    <Dialog
      v-model:visible="timelineDialogVisible"
      :header="`Хронология дела ${selectedCase ? selectedCase.case_number : ''}`"
      :style="{ width: '90vw', maxWidth: '1000px' }"
      :modal="true"
      :closable="true">
      <div v-if="selectedCase && selectedCase.case_details">
        <Timeline :value="timelineEvents" class="w-full">
          <template #marker="slotProps">
            <i :class="getTimelineIcon(slotProps.item.type)"></i>
          </template>
          <template #content="slotProps">
            <div class="p-3 border border-gray-200 rounded-md shadow-sm mb-3 bg-white">
              <div class="flex justify-between mb-2">
                <span class="font-semibold text-lg">{{ slotProps.item.result }}</span>
                <span class="text-blue-600 font-medium">{{ formatDate(slotProps.item.date) }}</span>
              </div>
              <div class="flex flex-col">
                <div class="flex items-start mb-1">
                  <span class="font-medium text-gray-700 mr-2">Тип:</span>
                  <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-sm">
                    {{ slotProps.item.type }}
                  </span>
                </div>
                <div class="flex items-start mb-1" v-if="slotProps.item.subject">
                  <span class="font-medium text-gray-700 mr-2">Субъект:</span>
                  <span>{{ slotProps.item.subject }}</span>
                </div>
                <div class="flex items-start mb-1" v-if="slotProps.item.additional_info">
                  <span class="font-medium text-gray-700 mr-2">Дополнительно:</span>
                  <span>{{ slotProps.item.additional_info }}</span>
                </div>
                <div v-if="slotProps.item.file_link">
                  <a
                    :href="slotProps.item.file_link"
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
    </Dialog>
  </div>
</template>

<script setup>
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref, watch } from 'vue';
import bitrixService from '../services/bitrixService.js';
import { fetchArbitrationCases } from '../services/kad-api';

const dailyLimit = ref(10);
const usedClicks = ref(0);
const remainingClicks = ref(10);

const toast = useToast();
const loading = ref(false);
const cases = ref([]);
const firstLoad = ref(true);
const skeletonData = Array(5).fill({});
const timelineDialogVisible = ref(false);
const selectedCase = ref(null);

// Параметры поиска
const searchParams = ref({
  caseNumber: '',
  includeTimeline: true,
});

// Функция форматирования даты
const formatDate = (dateString) => {
  if (!dateString) return 'Нет даты';
  if (/^\d{2}\.\d{2}\.\d{4}$/.test(dateString)) {
    const [day, month, year] = dateString.split('.');
    const isoDate = `${year}-${month}-${day}`;
    const date = new Date(isoDate);
    if (isNaN(date.getTime())) {
      console.error('Некорректная дата после преобразования:', isoDate);
      return 'Неверная дата';
    }
    return date.toLocaleDateString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  } else {
    const date = new Date(dateString);
    return isNaN(date.getTime())
      ? dateString
      : date.toLocaleDateString('ru-RU', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
        });
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

// Вычисляемое свойство для формирования хронологии (с сортировкой от новых к старым)
const timelineEvents = computed(() => {
  if (!selectedCase.value || !selectedCase.value.case_details) {
    console.log('timelineEvents: Нет выбранного дела или case_details отсутствуют');
    return [];
  }
  const sorted = [...selectedCase.value.case_details].sort((a, b) => {
    const dateA = new Date(a.date.split('.').reverse().join('-'));
    const dateB = new Date(b.date.split('.').reverse().join('-'));
    return dateB - dateA;
  });
  console.log('timelineEvents: Отсортированные события', sorted);
  return sorted;
});

// Следим за изменением выбранного дела для отладки
watch(
  selectedCase,
  (newVal) => {
    console.log('selectedCase обновлено:', newVal);
  },
  { deep: true },
);
var fetch_data;
// Функция получения данных из API по номеру дела
const fetchData = async () => {
  try {
    // 1. Проверяем лимит перед запросом
    const initialClick = await bitrixService.addClick();
    console.log('Результат добавления клика:', initialClick);

    if (initialClick.isLimitReached) {
      toast.add({
        severity: 'warn',
        summary: 'Лимит',
        detail: initialClick.message,
        life: 5000,
      });
      loading.value = false;
      return;
    }

    // 2. Обновляем UI
    dailyLimit.value = initialClick.limit;
    usedClicks.value = initialClick.count;
    remainingClicks.value = initialClick.limit - initialClick.count;

    toast.add({
      severity: 'success',
      summary: 'Успешно',
      detail: initialClick.message,
      life: 3000,
    });
  } catch (error) {
    console.error('Ошибка при получении данных:', error);
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Ошибка при получении данных из КАД',
      life: 3000,
    });
  }
  console.log('fetchData: Начало запроса с параметрами', searchParams.value);
  loading.value = true;
  firstLoad.value = false;
  cases.value = [];

  if (!searchParams.value.caseNumber) {
    toast.add({
      severity: 'info',
      summary: 'Информация',
      detail: 'Введите номер дела для поиска',
      life: 3000,
    });
    console.log('fetchData: Номер дела не введён');
    loading.value = false;
    return;
  }

  try {
    const result = await fetchArbitrationCases({
      type: 'byId',
      caseNumber: searchParams.value.caseNumber,
      includeTimeline: searchParams.value.includeTimeline,
    });
    fetch_data = result;
    console.log('fetchData: Получен результат', result);

    // ПРИМЕР 1: если точно знаете, что result — это массив
    cases.value = result;
    console.log('fetchData: cases.value', cases.value);

    // ПРИМЕР 2: если точно знаете, что приходит { results: [] }
    // cases.value = result.results || [];

    // ПРИМЕР 3: если может быть и так, и так
    // cases.value = Array.isArray(result) ? result : (result.results || []);

    if (cases.value.length === 0) {
      toast.add({
        severity: 'info',
        summary: 'Информация',
        detail: 'По вашему запросу ничего не найдено',
        life: 3000,
      });
      console.log('fetchData: Дел не найдено');
    } else {
      toast.add({
        severity: 'success',
        summary: 'Успешно',
        detail: `Найдено дел: ${cases.value.length}`,
        life: 3000,
      });
      console.log('fetchData: Найдено дел:', cases.value.length);
    }
  } catch (error) {
    console.error('fetchData: Ошибка при получении данных:', error);
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Ошибка при получении данных из КАД',
      life: 3000,
    });
  } finally {
    loading.value = false;
    console.log('fetchData: Завершение запроса, loading =', loading.value);
  }
};

// Функция показа диалога с хронологией
const showTimelineDialog = (caseData) => {
  console.log('showTimelineDialog: Открытие диалога для дела', caseData.case_number);
  selectedCase.value = caseData;
  timelineDialogVisible.value = true;
};

var mapping = {
  CRM_COMPANY_DETAIL_TAB: 'company',
  CRM_CONTACT_DETAIL_TAB: 'contact',
  CRM_DEAL_DETAIL_TAB: 'deal',
  CRM_LEAD_DETAIL_TAB: 'lead',
};
const syncWithBitrix = async () => {
  loading.value = true;
  try {
    // Сначала проверяем подписку
    const subscriptionInfo = await bitrixService.checkSubscription();
    if (!subscriptionInfo.subscribed) {
      toast.add({
        severity: 'warn',
        summary: 'Ограничение доступа',
        detail:
          'Синхронизация с Битрикс доступна только при активной подписке. Оформите подписку для использования этой функции во вкладке Тарифы.',
        life: 5000,
        escape: false,
      });
      loading.value = false;
      return;
    }

    const placementInfoResult = await bitrixService.placementInfo();
    console.log('Результат placementInfo():', placementInfoResult);

    console.log('Массив элементов:', fetch_data);
    // Подготавливаем массив активностей для пакетной отправки
    const activities = fetch_data.map((item) => {
      const commentParts = [];
      if (item.date) commentParts.push(`Дата: ${item.date}`);
      if (item.case_number) commentParts.push(`Результат: ${item.case_number}`);
      if (item.court) commentParts.push(`Тип: ${item.court}`);
      if (item.judge) commentParts.push(`Субъект: ${item.judge}`);
      if (item.plaintiff) commentParts.push(`Доп. информация: ${item.plaintiff}`);
      if (item.case_link) commentParts.push(`Ссылка на дело: ${item.case_link}`);

      return {
        ENTITY_ID: placementInfoResult.options.ID,
        ENTITY_TYPE: mapping[placementInfoResult.placement],
        COMMENT: commentParts.join('\n'), // или ', ' для одной строки
        AUTHOR_ID: 1,
      };
    });

    // Добавляем ВСЕ активности одним запросом
    await bitrixService.addActivities(activities);

    toast.add({
      severity: 'success',
      summary: 'Синхронизация',
      detail: 'Успешная синхронизация с Битрикс',
      life: 3000,
    });
  } catch (error) {
    console.error('Ошибка синхронизации с Битрикс:', error);
    toast.add({
      severity: 'error',
      summary: 'Ошибка',
      detail: 'Не удалось синхронизироваться с Битрикс',
      life: 3000,
    });
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  console.log('Компонент загружен');

  try {
    // Получаем информацию о размещении
    const placementInfoResult = await bitrixService.placementInfo();

    const mapping = {
      CRM_COMPANY_DETAIL_TAB: 'company',
      CRM_CONTACT_DETAIL_TAB: 'contact',
      CRM_DEAL_DETAIL_TAB: 'deal',
      CRM_LEAD_DETAIL_TAB: 'lead',
    };

    // Определяем тип сущности
    const entityType = mapping[placementInfoResult.placement];
    if (!entityType) {
      console.warn('Unknown placement type:', placementInfoResult.placement);
      return;
    }

    // Получаем ID сущности
    const entityId = placementInfoResult.options.ID;
    if (!entityId) {
      console.warn('Entity ID not found in placement info');
      return;
    }

    // Получаем данные сущности
    const userField = await bitrixService.GetUserfield(entityType, entityId);

    // Устанавливаем номер дела
    if (userField?.UF_CRM_NUMBERCASE) {
      searchParams.value.caseNumber = userField.UF_CRM_NUMBERCASE.toString();
    } else {
      console.warn('No ID in userField, using placement ID instead');
      searchParams.value.caseNumber = entityId.toString();
    }
  } catch (error) {
    console.error('Initialization error:', {
      error: error.message,
      stack: error.stack,
    });

    // Fallback: используем ID из placementInfo если доступен
    if (placementInfoResult?.options?.UF_CRM_NUMBERCASE) {
      searchParams.value.caseNumber = placementInfoResult.options.UF_CRM_NUMBERCASE.toString();
    }
  }
});
</script>

<style scoped>
.items-center {
  align-items: unset;
}
:deep(.p-datatable-wrapper) {
  border-radius: 0.5rem;
  overflow: hidden;
}

:deep(.p-datatable .p-datatable-header) {
  background-color: #f8fafc;
  border-color: #e2e8f0;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f1f5f9;
  color: #334155;
  font-weight: 600;
  border-color: #e2e8f0;
}

:deep(.p-datatable .p-datatable-tbody > tr) {
  background-color: #ffffff;
  border-color: #e2e8f0;
}

:deep(.p-datatable .p-datatable-tbody > tr.p-highlight) {
  background-color: #eff6ff;
  color: #1e40af;
}

:deep(.p-datatable .p-datatable-tbody > tr:nth-child(even)) {
  background-color: #f8fafc;
}

:deep(.p-paginator) {
  background-color: #f8fafc;
  border-color: #e2e8f0;
}

:deep(.p-progressbar) {
  border-radius: 0.25rem;
  background: #e2e8f0;
  height: 6px;
}

:deep(.p-progressbar .p-progressbar-value) {
  background: #3b82f6;
}

:deep(.p-timeline-event-marker) {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  background-color: white;
  border: 2px solid #e2e8f0;
}

:deep(.p-timeline-event-marker i) {
  font-size: 1rem;
}
</style>
