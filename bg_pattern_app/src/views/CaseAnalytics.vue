<template>
  <div class="p-4 max-w-6xl mx-auto">
    <div
      v-if="loading"
      class="mb-4">
      <div class="flex justify-center items-center py-12">
        <ProgressBar
          mode="indeterminate"
          style="height: 6px; max-width: 400px"
          class="w-full" />
        <span class="ml-4 text-gray-600">Загрузка данных...</span>
      </div>
    </div>
    <div v-else>
      <!-- Панель с общей статистикой (Вынесена вверх для быстрого обзора) -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div
          class="bg-blue-50 rounded-lg p-4 border border-blue-100 shadow-sm flex flex-col items-center justify-center">
          <span class="text-blue-600 text-3xl font-bold">{{ statistics.totalEvents || 0 }}</span>
          <span class="text-sm text-gray-600">Всего событий</span>
        </div>
        <div
          class="bg-green-50 rounded-lg p-4 border border-green-100 shadow-sm flex flex-col items-center justify-center">
          <span class="text-green-600 text-3xl font-bold">{{ statistics.totalDays || 0 }}</span>
          <span class="text-sm text-gray-600">Дней в производстве</span>
        </div>
        <div
          class="bg-purple-50 rounded-lg p-4 border border-purple-100 shadow-sm flex flex-col items-center justify-center">
          <span class="text-purple-600 text-3xl font-bold">{{ statistics.hearings || 0 }}</span>
          <span class="text-sm text-gray-600">Заседаний</span>
        </div>
        <div
          class="bg-amber-50 rounded-lg p-4 border border-amber-100 shadow-sm flex flex-col items-center justify-center">
          <span class="text-amber-600 text-3xl font-bold">{{
            statistics.documentsCount || 0
          }}</span>
          <span class="text-sm text-gray-600">Документов</span>
        </div>
      </div>

      <!-- Панель с настройками пользователя - сделана компактнее -->
      <Card class="shadow-sm mb-6">
        <template #title>
          <div class="flex justify-between items-center">
            <span class="text-lg font-medium">Параметры анализа</span>
            <Button
              icon="pi pi-cog"
              size="small"
              text
              rounded
              severity="secondary"
              @click="showSettings = !showSettings"
              title="Настроить параметры анализа" />
          </div>
        </template>
        <template #content>
          <div
            v-if="showSettings"
            class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <h3 class="text-lg font-medium text-blue-800 mb-3">Настройка параметров анализа</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Среднее время рассмотрения дела (дни)</label
                >
                <InputNumber
                  v-model="userSettings.averageCaseDuration"
                  placeholder="90"
                  :min="1"
                  :max="1000"
                  class="w-full" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Показатель для определения сложности дела</label
                >
                <InputNumber
                  v-model="userSettings.complexityThreshold"
                  placeholder="5"
                  :min="1"
                  :max="100"
                  class="w-full" />
                <small class="text-gray-500"
                  >Количество событий за месяц для определения сложности</small
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Среднее время для аналогичных дел (дни)</label
                >
                <InputNumber
                  v-model="userSettings.avgSimilarCasesDuration"
                  placeholder="120"
                  :min="1"
                  :max="1000"
                  class="w-full" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"
                  >Медиана для аналогичных дел (дни)</label
                >
                <InputNumber
                  v-model="userSettings.medianSimilarCasesDuration"
                  placeholder="105"
                  :min="1"
                  :max="1000"
                  class="w-full" />
              </div>
            </div>
            <div class="flex justify-end mt-4">
              <Button
                label="Применить"
                icon="pi pi-check"
                size="small"
                @click="applyUserSettings" />
            </div>
          </div>
        </template>
      </Card>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <!-- Карточка со статусом дела -->
        <Card class="shadow-sm md:col-span-1">
          <template #title>
            <div class="flex items-center">
              <i class="pi pi-chart-line mr-2 text-blue-600"></i>
              <span>Статус и прогноз</span>
            </div>
          </template>
          <template #content>
            <div class="mb-3">
              <div class="flex justify-between mb-1">
                <span class="font-medium">Прогресс рассмотрения</span>
                <span class="font-medium">{{ statistics.progressPercentage }}%</span>
              </div>
              <ProgressBar
                :value="statistics.progressPercentage"
                class="h-2" />
            </div>
            <div class="flex justify-between mb-3">
              <span class="font-medium">Текущий статус:</span>
              <Tag
                :severity="getStatusSeverity(statistics.currentStatus)"
                rounded
                >{{ statistics.currentStatus }}</Tag
              >
            </div>
            <div class="flex justify-between mb-3">
              <span class="font-medium">Следующее заседание:</span>
              <span
                v-if="statistics.nextHearingDate"
                class="text-blue-600 font-semibold"
                >{{ formatDate(statistics.nextHearingDate) }}</span
              >
              <div
                v-else
                class="flex items-center">
                <span class="text-gray-500 mr-2">Не назначено</span>
                <Button
                  icon="pi pi-calendar-plus"
                  text
                  rounded
                  size="small"
                  severity="info"
                  @click="showHearingDialog = true"
                  title="Добавить заседание" />
              </div>
            </div>
            <div class="flex justify-between mb-3">
              <span class="font-medium">Сложность дела:</span>
              <Tag
                :severity="
                  statistics.complexity === 'Высокая'
                    ? 'danger'
                    : statistics.complexity === 'Средняя'
                    ? 'warning'
                    : 'success'
                "
                rounded>
                {{ statistics.complexity }}
              </Tag>
            </div>
            <div class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
              <h4 class="text-sm font-medium text-gray-700 mb-2">Прогноз завершения</h4>
              <div
                v-if="statistics.estimatedCompletionDate"
                class="space-y-2">
                <p class="text-sm">
                  При текущей динамике процесса, предполагаемая дата завершения:
                </p>
                <p class="text-lg font-semibold text-blue-700">
                  {{ formatDate(statistics.estimatedCompletionDate) }}
                </p>
              </div>
              <div
                v-else
                class="flex flex-col space-y-3">
                <p class="text-sm text-gray-600">
                  Недостаточно данных для автоматического прогноза
                </p>
                <Button
                  label="Задать прогноз вручную"
                  size="small"
                  icon="pi pi-calendar"
                  outlined
                  @click="showEstimateDialog = true" />
              </div>
            </div>
          </template>
        </Card>

        <!-- График распределения событий по типам -->
        <Card class="shadow-sm md:col-span-2">
          <template #title>
            <div class="flex items-center">
              <i class="pi pi-chart-pie mr-2 text-blue-600"></i>
              <span>Распределение событий по типам</span>
            </div>
          </template>
          <template #content>
            <div class="min-h-[200px] w-full">
              <Chart
                v-if="eventTypeChartData.labels.length > 0"
                type="pie"
                :data="eventTypeChartData"
                :options="chartOptions.pie"
                class="w-full" />
              <div
                v-else
                class="flex flex-col justify-center items-center text-center p-4 h-full min-h-[200px]">
                <i class="pi pi-chart-pie text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-3">Недостаточно данных для отображения графика</p>
                <Button
                  label="Добавить типы событий"
                  size="small"
                  outlined
                  @click="showEventsDialog = true" />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- График хронологии событий -->
      <Card class="shadow-sm mb-6">
        <template #title>
          <div class="flex items-center">
            <i class="pi pi-calendar mr-2 text-blue-600"></i>
            <span>Хронология активности по делу</span>
          </div>
        </template>
        <template #content>
          <div class="min-h-[250px] w-full">
            <Chart
              v-if="timelineChartData.labels.length > 0"
              type="line"
              :data="timelineChartData"
              :options="chartOptions.line"
              class="w-full" />
            <div
              v-else
              class="flex flex-col justify-center items-center text-center p-4 h-full min-h-[250px]">
              <i class="pi pi-chart-line text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-500 mb-3">Недостаточно данных для отображения графика</p>
              <Button
                label="Добавить данные хронологии"
                size="small"
                outlined
                @click="showTimelineDialog = true" />
            </div>
          </div>
        </template>
      </Card>

      <!-- Сравнительный анализ -->
      <Card class="shadow-sm mb-6">
        <template #title>
          <div class="flex justify-between items-center">
            <div class="flex items-center">
              <i class="pi pi-chart-bar mr-2 text-blue-600"></i>
              <span>Сравнение с аналогичными делами</span>
            </div>
            <Button
              icon="pi pi-info-circle"
              text
              rounded
              size="small"
              severity="info"
              @click="showBenchmarkInfo = !showBenchmarkInfo" />
          </div>
        </template>
        <template #content>
          <div
            v-if="showBenchmarkInfo"
            class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
            <h3 class="text-lg font-medium text-blue-800 mb-2">Информация о сравнении</h3>
            <p class="text-sm mb-3">
              Данные для сравнения можно настроить в разделе "Параметры анализа". По умолчанию
              используются средние значения по категории дел.
            </p>
            <p class="text-sm italic">
              * Вы можете изменить значения для сравнения, если располагаете более точными данными.
            </p>
          </div>

          <div class="min-h-[250px] w-full">
            <Chart
              type="bar"
              :data="comparisonChartData"
              :options="chartOptions.bar"
              class="w-full" />
          </div>
          <div class="flex justify-between text-sm text-gray-600 italic mt-2">
            <span>* Данные основаны на статистике аналогичных дел за последние 12 месяцев</span>
          </div>
        </template>
      </Card>

      <!-- Расшифровка показателей -->
      <Card class="shadow-sm">
        <template #title>
          <div class="flex items-center">
            <i class="pi pi-info-circle mr-2 text-blue-600"></i>
            <span>Расшифровка показателей</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
              <h3 class="text-lg font-medium mb-2">Сложность дела</h3>
              <ul class="list-disc pl-5 text-sm space-y-2">
                <li>
                  <span class="font-medium text-green-600">Низкая</span> - менее
                  {{ userSettings.complexityThreshold }} событий в месяц
                </li>
                <li>
                  <span class="font-medium text-yellow-600">Средняя</span> - от
                  {{ userSettings.complexityThreshold }} до
                  {{ userSettings.complexityThreshold * 2 }} событий в месяц
                </li>
                <li>
                  <span class="font-medium text-red-600">Высокая</span> - более
                  {{ userSettings.complexityThreshold * 2 }} событий в месяц
                </li>
              </ul>
            </div>
            <div class="p-3 border border-gray-200 rounded-lg bg-gray-50">
              <h3 class="text-lg font-medium mb-2">Прогресс рассмотрения</h3>
              <p class="text-sm">
                Расчет основан на вашем значении среднего времени рассмотрения дела ({{
                  userSettings.averageCaseDuration
                }}
                дней). Текущее дело продолжается {{ statistics.totalDays || 0 }} дней, что
                составляет {{ statistics.progressPercentage || 0 }}% от среднего.
              </p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Диалоговые окна для добавления данных вручную -->
    <!-- Диалог для добавления прогноза завершения -->
    <Dialog
      v-model:visible="showEstimateDialog"
      modal
      header="Задать прогноз завершения дела"
      :style="{ width: '450px' }"
      :closable="true">
      <div class="p-4">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1"
            >Предполагаемая дата завершения</label
          >
          <Calendar
            v-model="manualEstimatedDate"
            dateFormat="dd.mm.yy"
            class="w-full"
            :minDate="new Date()"
            showIcon />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Основание для прогноза</label>
          <Dropdown
            v-model="estimateBasis"
            :options="[
              { label: 'Мнение эксперта', value: 'expert' },
              { label: 'Аналогичные дела', value: 'similar_cases' },
              { label: 'График суда', value: 'court_schedule' },
              { label: 'Иное', value: 'other' },
            ]"
            optionLabel="label"
            class="w-full" />
        </div>
        <div class="flex justify-end">
          <Button
            label="Отмена"
            icon="pi pi-times"
            text
            @click="showEstimateDialog = false"
            class="mr-2" />
          <Button
            label="Сохранить"
            icon="pi pi-check"
            @click="saveManualEstimate" />
        </div>
      </div>
    </Dialog>

    <!-- Диалог для добавления заседания -->
    <Dialog
      v-model:visible="showHearingDialog"
      modal
      header="Добавить заседание"
      :style="{ width: '450px' }"
      :closable="true">
      <div class="p-4">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Дата заседания</label>
          <Calendar
            v-model="newHearingDate"
            dateFormat="dd.mm.yy"
            class="w-full"
            :minDate="new Date()"
            showIcon />
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Тип заседания</label>
          <Dropdown
            v-model="hearingType"
            :options="[
              { label: 'Предварительное', value: 'preliminary' },
              { label: 'Основное', value: 'main' },
              { label: 'Апелляционное', value: 'appeal' },
              { label: 'Иное', value: 'other' },
            ]"
            optionLabel="label"
            class="w-full" />
        </div>
        <div class="flex justify-end">
          <Button
            label="Отмена"
            icon="pi pi-times"
            text
            @click="showHearingDialog = false"
            class="mr-2" />
          <Button
            label="Сохранить"
            icon="pi pi-check"
            @click="saveNewHearing" />
        </div>
      </div>
    </Dialog>

    <!-- Диалог для добавления данных о типах событий -->
    <Dialog
      v-model:visible="showEventsDialog"
      modal
      header="Добавить типы событий"
      :style="{ width: '500px' }"
      :closable="true">
      <div class="p-4">
        <div class="mb-4">
          <p class="text-sm text-gray-600 mb-2">
            Добавьте типы событий и их количество для визуализации на графике
          </p>
          <div
            v-for="(event, index) in manualEvents"
            :key="index"
            class="flex items-center mb-2">
            <InputText
              v-model="event.type"
              placeholder="Тип события"
              class="w-full mr-2" />
            <InputNumber
              v-model="event.count"
              placeholder="Количество"
              :min="1"
              class="w-32 mr-2" />
            <Button
              icon="pi pi-trash"
              text
              rounded
              severity="danger"
              @click="removeEvent(index)" />
          </div>
          <Button
            label="Добавить тип"
            icon="pi pi-plus"
            text
            @click="addEvent"
            class="mt-2" />
        </div>
        <div class="flex justify-end">
          <Button
            label="Отмена"
            icon="pi pi-times"
            text
            @click="showEventsDialog = false"
            class="mr-2" />
          <Button
            label="Применить"
            icon="pi pi-check"
            @click="applyManualEvents" />
        </div>
      </div>
    </Dialog>

    <!-- Диалог для добавления данных хронологии -->
    <Dialog
      v-model:visible="showTimelineDialog"
      modal
      header="Добавить данные хронологии"
      :style="{ width: '500px' }"
      :closable="true">
      <div class="p-4">
        <div class="mb-4">
          <p class="text-sm text-gray-600 mb-2">
            Добавьте месяцы и количество событий для визуализации на графике хронологии
          </p>
          <div
            v-for="(point, index) in manualTimelinePoints"
            :key="index"
            class="flex items-center mb-2">
            <Calendar
              v-model="point.date"
              view="month"
              dateFormat="mm.yy"
              placeholder="Месяц"
              class="w-full mr-2" />
            <InputNumber
              v-model="point.count"
              placeholder="Кол-во событий"
              :min="0"
              class="w-32 mr-2" />
            <Button
              icon="pi pi-trash"
              text
              rounded
              severity="danger"
              @click="removeTimelinePoint(index)" />
          </div>
          <Button
            label="Добавить точку"
            icon="pi pi-plus"
            text
            @click="addTimelinePoint"
            class="mt-2" />
        </div>
        <div class="flex justify-end">
          <Button
            label="Отмена"
            icon="pi pi-times"
            text
            @click="showTimelineDialog = false"
            class="mr-2" />
          <Button
            label="Применить"
            icon="pi pi-check"
            @click="applyManualTimelineData" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
  caseData: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

// Настраиваемые пользователем параметры
const userSettings = ref({
  averageCaseDuration: 90, // Среднее время рассмотрения дела (дни)
  complexityThreshold: 5, // Количество событий для определения сложности
  avgSimilarCasesDuration: 120, // Среднее время для аналогичных дел (дни)
  medianSimilarCasesDuration: 105, // Медиана для аналогичных дел (дни)
});

// Состояние UI
const showSettings = ref(false);
const showBenchmarkInfo = ref(false);
const showEstimateDialog = ref(false);
const showHearingDialog = ref(false);
const showEventsDialog = ref(false);
const showTimelineDialog = ref(false);

// Данные для ручного добавления
const manualEstimatedDate = ref(null);
const estimateBasis = ref({ label: 'Мнение эксперта', value: 'expert' });
const newHearingDate = ref(null);
const hearingType = ref({ label: 'Предварительное', value: 'preliminary' });
const manualEvents = ref([
  { type: 'Определение', count: 0 },
  { type: 'Прочие судебные документы', count: 0 },
  { type: 'Ходатайства', count: 0 },
]);
const manualTimelinePoints = ref([{ date: null, count: 0 }]);

// Статистика
const statistics = ref({
  totalEvents: 0,
  totalDays: 0,
  hearings: 0,
  documentsCount: 0,
  progressPercentage: 0,
  currentStatus: 'В процессе',
  nextHearingDate: null,
  complexity: 'Средняя',
  estimatedCompletionDate: null,
});

// Улучшенные опции для графиков с корректными цветами и отзывчивостью
const chartOptions = {
  pie: {
    plugins: {
      legend: {
        position: 'right',
        labels: {
          usePointStyle: true,
          font: {
            size: 11,
          },
        },
      },
      tooltip: {
        callbacks: {
          label: function (context) {
            const label = context.label || '';
            const value = context.raw || 0;
            const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
            const percentage = Math.round((value / total) * 100);
            return `${label}: ${value} (${percentage}%)`;
          },
        },
      },
    },
    responsive: true,
    maintainAspectRatio: false,
  },
  line: {
    plugins: {
      legend: {
        labels: {
          font: {
            size: 11,
          },
        },
      },
      tooltip: {
        mode: 'index',
        intersect: false,
      },
    },
    scales: {
      x: {
        title: {
          display: true,
          text: 'Дата',
          font: {
            size: 11,
            weight: 'bold',
          },
        },
        ticks: {
          font: {
            size: 10,
          },
        },
      },
      y: {
        beginAtZero: true,
        title: {
          display: true,
          text: 'Количество событий',
          font: {
            size: 11,
            weight: 'bold',
          },
        },
        ticks: {
          precision: 0,
          font: {
            size: 10,
          },
        },
      },
    },
    responsive: true,
    maintainAspectRatio: false,
  },
  bar: {
    indexAxis: 'y',
    plugins: {
      legend: {
        display: false,
      },
      tooltip: {
        callbacks: {
          label: function (context) {
            return `${context.raw} дней`;
          },
        },
      },
    },
    scales: {
      x: {
        beginAtZero: true,
        title: {
          display: true,
          text: 'Дни',
          font: {
            size: 11,
            weight: 'bold',
          },
        },
        ticks: {
          precision: 0,
          font: {
            size: 10,
          },
        },
      },
      y: {
        ticks: {
          font: {
            size: 11,
            weight: 'medium',
          },
        },
      },
    },
    responsive: true,
    maintainAspectRatio: false,
  },
};

// Данные для графика типов событий
const eventTypeChartData = ref({
  labels: [],
  datasets: [
    {
      data: [],
      backgroundColor: [
        '#42A5F5', // blue
        '#66BB6A', // green
        '#FFA726', // orange
        '#EF5350', // red
        '#AB47BC', // purple
        '#26C6DA', // cyan
        '#78909C', // blue-grey
      ],
    },
  ],
});

// Данные для графика хронологии
const timelineChartData = ref({
  labels: [],
  datasets: [
    {
      label: 'Количество событий',
      data: [],
      borderColor: '#3B82F6',
      backgroundColor: 'rgba(59, 130, 246, 0.2)',
      tension: 0.4,
      fill: true,
    },
  ],
});

// Данные для сравнения с другими делами
const comparisonChartData = ref({
  labels: ['Данное дело', 'Среднее значение', 'Медиана'],
  datasets: [
    {
      label: 'Продолжительность (дни)',
      data: [0, 0, 0],
      backgroundColor: ['#3B82F6', '#64748B', '#94A3B8'],
    },
  ],
});

// Функция форматирования даты
const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date);
};

// Получение класса severity для статуса
const getStatusSeverity = (status) => {
  const statusMap = {
    'В процессе': 'info',
    Приостановлено: 'warning',
    Завершено: 'success',
    Прекращено: 'danger',
  };
  return statusMap[status] || 'info';
};

// Функции для работы с диалоговыми окнами
const addEvent = () => {
  manualEvents.value.push({ type: '', count: 0 });
};

const removeEvent = (index) => {
  manualEvents.value.splice(index, 1);
};

const addTimelinePoint = () => {
  manualTimelinePoints.value.push({ date: null, count: 0 });
};

const removeTimelinePoint = (index) => {
  manualTimelinePoints.value.splice(index, 1);
};

// Применение пользовательских настроек
const applyUserSettings = () => {
  showSettings.value = false;
  updateComparisonChart();
  calculateProgress();

  // Пересчёт сложности дела на основе новых настроек
  if (
    statistics.value.totalEvents / (statistics.value.totalDays / 30) <
    userSettings.value.complexityThreshold
  ) {
    statistics.value.complexity = 'Низкая';
  } else if (
    statistics.value.totalEvents / (statistics.value.totalDays / 30) <
    userSettings.value.complexityThreshold * 2
  ) {
    statistics.value.complexity = 'Средняя';
  } else {
    statistics.value.complexity = 'Высокая';
  }
};

// Сохранение прогноза по делу
const saveManualEstimate = () => {
  if (manualEstimatedDate.value) {
    statistics.value.estimatedCompletionDate = manualEstimatedDate.value;
    showEstimateDialog.value = false;
  }
};

// Сохранение нового заседания
const saveNewHearing = () => {
  if (newHearingDate.value) {
    statistics.value.nextHearingDate = newHearingDate.value;
    statistics.value.hearings++;
    showHearingDialog.value = false;
  }
};

// Применение данных о типах событий
const applyManualEvents = () => {
  // Фильтруем только заполненные события
  const validEvents = manualEvents.value.filter((event) => event.type && event.count > 0);

  if (validEvents.length > 0) {
    eventTypeChartData.value.labels = validEvents.map((event) => event.type);
    eventTypeChartData.value.datasets[0].data = validEvents.map((event) => event.count);

    // Обновляем общую статистику
    statistics.value.totalEvents = validEvents.reduce((sum, event) => sum + event.count, 0);

    showEventsDialog.value = false;
  }
};

// Применение данных хронологии
const applyManualTimelineData = () => {
  // Фильтруем только заполненные точки хронологии
  const validPoints = manualTimelinePoints.value.filter((point) => point.date && point.count >= 0);

  if (validPoints.length > 0) {
    // Сортируем по дате
    validPoints.sort((a, b) => a.date - b.date);

    timelineChartData.value.labels = validPoints.map((point) => {
      return new Intl.DateTimeFormat('ru', { month: 'short', year: 'numeric' }).format(point.date);
    });
    timelineChartData.value.datasets[0].data = validPoints.map((point) => point.count);

    showTimelineDialog.value = false;
  }
};

// Вычисление прогресса рассмотрения дела
const calculateProgress = () => {
  if (statistics.value.totalDays && userSettings.value.averageCaseDuration) {
    statistics.value.progressPercentage = Math.min(
      100,
      Math.round((statistics.value.totalDays / userSettings.value.averageCaseDuration) * 100),
    );
  } else {
    statistics.value.progressPercentage = 0;
  }
};

// Обновление графика сравнения с аналогичными делами
const updateComparisonChart = () => {
  comparisonChartData.value.datasets[0].data = [
    statistics.value.totalDays || 0,
    userSettings.value.avgSimilarCasesDuration || 0,
    userSettings.value.medianSimilarCasesDuration || 0,
  ];
};

// Инициализация данных при монтировании компонента
onMounted(async () => {
  try {
    // Если имеются данные из caseData, используем их
    if (props.caseData) {
      // Здесь можно добавить инициализацию на основе полученных данных
      // Например:
      statistics.value = {
        totalEvents: props.caseData.totalEvents || 0,
        totalDays: props.caseData.totalDays || 0,
        hearings: props.caseData.hearings || 0,
        documentsCount: props.caseData.documentsCount || 0,
        progressPercentage: 0,
        currentStatus: props.caseData.status || 'В процессе',
        nextHearingDate: props.caseData.nextHearingDate || null,
        complexity: 'Средняя',
        estimatedCompletionDate: props.caseData.estimatedCompletionDate || null,
      };

      // Если есть данные по типам событий
      if (props.caseData.eventTypes && props.caseData.eventTypes.length > 0) {
        eventTypeChartData.value.labels = props.caseData.eventTypes.map((et) => et.type);
        eventTypeChartData.value.datasets[0].data = props.caseData.eventTypes.map((et) => et.count);
      }

      // Если есть данные хронологии
      if (props.caseData.timeline && props.caseData.timeline.length > 0) {
        timelineChartData.value.labels = props.caseData.timeline.map((t) => t.month);
        timelineChartData.value.datasets[0].data = props.caseData.timeline.map((t) => t.count);
      }
    } else {
      // Инициализация демо-данных, если нет данных
      statistics.value = {
        totalEvents: 24,
        totalDays: 45,
        hearings: 2,
        documentsCount: 15,
        progressPercentage: 0,
        currentStatus: 'В процессе',
        nextHearingDate: new Date(Date.now() + 14 * 24 * 60 * 60 * 1000), // +14 дней
        complexity: 'Средняя',
        estimatedCompletionDate: new Date(Date.now() + 60 * 24 * 60 * 60 * 1000), // +60 дней
      };

      // Демо-данные для графика типов событий
      eventTypeChartData.value.labels = ['Определения', 'Ходатайства', 'Заседания', 'Документы'];
      eventTypeChartData.value.datasets[0].data = [8, 6, 3, 7];

      // Демо-данные для графика хронологии
      const now = new Date();
      const monthNames = [];
      const timelineData = [];

      for (let i = 5; i >= 0; i--) {
        const d = new Date(now);
        d.setMonth(d.getMonth() - i);
        monthNames.push(
          new Intl.DateTimeFormat('ru', { month: 'short', year: 'numeric' }).format(d),
        );
        // Генерация случайных данных для демо
        timelineData.push(Math.floor(Math.random() * 8) + 1);
      }

      timelineChartData.value.labels = monthNames;
      timelineChartData.value.datasets[0].data = timelineData;
    }

    // Расчёт процента прогресса и обновление графика сравнения
    calculateProgress();
    updateComparisonChart();
  } catch (error) {
    console.error('Ошибка при инициализации данных:', error);
  }
});

// Следим за изменением свойства caseData
watch(
  () => props.caseData,
  () => {
    // Обновление данных при изменении переданных данных
    if (props.caseData) {
      // Здесь можно добавить обновление на основе новых данных
    }
  },
  { deep: true },
);
</script>
