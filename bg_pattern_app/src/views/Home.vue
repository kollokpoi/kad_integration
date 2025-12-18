<template>
  <div class="h-full w-full p-6">
    <Toast position="top-right" />
    <!-- Подключаем шапку с нужным контентом -->
    <PageHeader>
      <template #title> Система интеграции с КАД </template>
      <template #subtitle>
        Получайте актуальную информацию о судебных делах из картотеки арбитражных дел.
      </template>
    </PageHeader>

    <Card class="shadow-md h-full">
      <template #title>
        <span>Картотека арбитражных дел</span>
      </template>
      <template #subtitle>
        <div class="text-sm text-black">Данные обновляются в режиме реального времени</div>
      </template>
      <template #content>
        <div class="mb-4">
          <label
              for="inn-input"
              class="block text-sm font-medium text-gray-700 mb-1"
              >ИНН организации</label
          >

          <div class="flex">
            <InputText
              id="inn-input"
              v-model="searchParams.inn"
              placeholder="Введите ИНН организации"
              class="flex-1 p-2"
              :disabled="loading" />
            <div class="flex">
              <Button
                label="Получить список дел"
                icon="pi pi-search"
                class="p-button-primary btn-main mx-2"
                @click="fetchData"
                :loading="loading"
                :disabled="loading || !searchParams.inn" />
              <Button
                label="Синхронизация с Битрикс"
                icon="pi pi-refresh"
                class="p-button-success sync-button btn-gray mx-2"
                @click="syncWithBitrix"
                tooltip="Синхронизировать данные с Битрикс" />
            </div>
          </div>
        </div>

        <!-- Индикатор загрузки -->
        <div
          v-if="loading"
          class="mb-4">
          <div class="text-sm text-blue-600 mb-2 flex items-center">
            <i class="pi pi-spin pi-spinner mr-2"></i>
            <span>Загрузка данных из КАД...</span>
          </div>
          <ProgressBar
            mode="indeterminate"
            style="height: 6px"
            class="mb-4" />
        </div>

        <!-- Таблица с данными -->
        <div
          v-if="!loading && cases.length > 0"
          class="overflow-x-auto">
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
            <Column
              field="date"
              header="Дата"
              sortable>
              <template #body="{ data }">
                <span class="font-medium">{{ formatDate(data.date) }}</span>
              </template>
            </Column>

            <Column
              field="case_number"
              header="Номер дела"
              sortable>
              <template #body="{ data }">
                <router-link
                  custom
                  :to="{ name: 'caseDetails', params: { caseNumber: data.case_number } }">
                  <template #default="{ navigate }">
                    <a
                      href="#"
                      class="text-blue-600 font-medium hover:underline"
                      @click.prevent="handleCaseClick(data, navigate)">
                      {{ data.case_number }}
                    </a>
                  </template>
                </router-link>
              </template>
            </Column>

            <Column
              field="case_link"
              header="Ссылка на дело">
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

            <Column
              field="judge"
              header="Судья"
              sortable />
            <Column
              field="court"
              header="Суд"
              sortable />

            <Column
              field="plaintiff"
              header="Истец"
              sortable>
              <template #body="{ data }">
                <div
                  class="max-w-xs truncate"
                  :title="data.plaintiff">
                  {{ data.plaintiff }}
                </div>
              </template>
            </Column>

            <Column
              field="respondent"
              header="Ответчик"
              sortable>
              <template #body="{ data }">
                <div
                  class="max-w-xs truncate"
                  :title="data.respondent">
                  {{ data.respondent }}
                </div>
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Скелеты для загрузки данных -->
        <div
          v-if="!loading && cases.length === 0 && firstLoad"
          class="overflow-x-auto">
          <DataTable
            :value="skeletonData"
            class="p-datatable-sm"
            responsiveLayout="scroll">
            <Column
              field="date"
              header="Дата">
              <template #body>
                <Skeleton
                  height="1.5rem"
                  class="mb-2" />
              </template>
            </Column>
            <Column
              field="case_number"
              header="Номер дела">
              <template #body>
                <Skeleton
                  height="1.5rem"
                  class="mb-2" />
              </template>
            </Column>
            <Column
              field="case_link"
              header="Ссылка на дело">
              <template #body>
                <Skeleton
                  height="1.5rem"
                  class="mb-2" />
              </template>
            </Column>
            <Column
              field="judge"
              header="Судья">
              <template #body>
                <Skeleton
                  height="1.5rem"
                  class="mb-2" />
              </template>
            </Column>
            <Column
              field="court"
              header="Суд">
              <template #body>
                <Skeleton
                  height="1.5rem"
                  class="mb-2" />
              </template>
            </Column>
            <Column
              field="plaintiff"
              header="Истец">
              <template #body>
                <Skeleton
                  height="1.5rem"
                  class="mb-2" />
              </template>
            </Column>
            <Column
              field="respondent"
              header="Ответчик">
              <template #body>
                <Skeleton
                  height="1.5rem"
                  class="mb-2" />
              </template>
            </Column>
          </DataTable>
        </div>

        <!-- Сообщение об отсутствии данных -->
        <div
          v-if="!loading && cases.length === 0 && !firstLoad"
          class="text-center py-8">
          <i class="pi pi-search text-4xl text-gray-400 mb-4"></i>
          <p class="text-gray-500">Нет доступных данных по судебным делам</p>
          <p class="text-sm text-gray-400 mt-2">
            Попробуйте изменить параметры поиска или обновить данные позже
          </p>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';
import bitrixService from '../services/bitrixService.js';
import { fetchArbitrationCases } from '../services/kad-api';

// Получаем toast напрямую
const toast = useToast();
const dailyLimit = ref(10);
const usedClicks = ref(0);
const remainingClicks = ref(10);

// Реактивные данные
const loading = ref(false);
const cases = ref([]);
const firstLoad = ref(true);
const skeletonData = Array(5).fill({});

// Параметры поиска
const searchParams = ref({
  inn: '',
});

// Форматирование даты
const formatDate = (dateString) => {
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
    if (isNaN(date.getTime())) {
      console.error('Некорректная дата:', dateString);
      return 'Неверная дата';
    }
    return date.toLocaleDateString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  }
};

// Получение данных из API по ИНН
var fetch_data;
const fetchData = async () => {
  loading.value = true;
  firstLoad.value = false;
  cases.value = [];

  if (!searchParams.value.inn) {
    toast.add({
      severity: 'info',
      summary: 'Информация',
      detail: 'Введите ИНН для поиска',
      life: 3000,
    });
    loading.value = false;
    return;
  }

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

    // 3. Выполняем запрос данных
    const result = await fetchArbitrationCases({
      type: 'byInn',
      inn: searchParams.value.inn,
    });

    cases.value = result;
    fetch_data = cases.value;

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
  } finally {
    loading.value = false;
  }
};
const mapping = {
  CRM_COMPANY_DETAIL_TAB: 'company',
  CRM_CONTACT_DETAIL_TAB: 'contact',
  CRM_DEAL_DETAIL_TAB: 'deal',
  CRM_LEAD_DETAIL_TAB: 'lead',
};

const syncWithBitrix = async () => {
  console.log('Массив элементов:', fetch_data);
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

    // Подготавливаем массив активностей для пакетной отправки
    const activities = fetch_data.map((item) => {
      const commentParts = [];
      if (item.date) commentParts.push(`Дата: ${item.date}`);
      if (item.case_number) commentParts.push(`Результат: ${item.case_number}`);
      if (item.court) commentParts.push(`Тип: ${item.court}`);
      if (item.judge) commentParts.push(`Судья: ${item.judge}`);
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

async function handleCaseClick(data, navigate) {
  try {
    const subscriptionInfo = await bitrixService.checkSubscription();
    const tariffKey = subscriptionInfo.tariffKey ? subscriptionInfo.tariffKey.toLowerCase() : '';

    // Если подписка не активна или тариф не соответствует требованиям
    if (
      !subscriptionInfo.subscribed ||
      (tariffKey !== 'professional' && tariffKey !== 'corporate')
    ) {
      toast.add({
        severity: 'warn',
        summary: 'Доступ ограничен',
        detail:
          'Функционал комментариев и аналитика доступны только в платной версии. Перейдите на страницу тарифов для приобретения подписки.',
        life: 5000,
        escape: false,
      });
      return; // не вызываем navigate, переход не происходит
    }

    // Если все условия выполнены, выполняем навигацию
    navigate();
  } catch (error) {
    console.error('Ошибка проверки подписки:', error);
  }
}

onMounted(async () => {
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
    const entityData = await bitrixService.GetRequisites(entityType, entityId);
    console.log('Entity data:', entityData[0].RQ_INN);
    if (entityType === 'company') {
      if (entityData[0].RQ_INN) {
        searchParams.value.inn = entityData[0].RQ_INN;
      }
    } else if (entityType === 'contact') {
      if (entityData[0].RQ_INN) {
        searchParams.value.inn = entityData[0].RQ_INN;
      }
    }
  } catch (error) {
    console.error('Initialization error:', {
      error: error.message,
      stack: error.stack,
    });

    // Можно показать toast с предупреждением, но не ошибкой
    toast.add({
      severity: 'warn',
      summary: 'Информация',
      detail: 'Не удалось автоматически определить ИНН организации',
      life: 3000,
    });
  }
});
</script>

<style scoped>
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
</style>
