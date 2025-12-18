<!-- src/views/OurSolutions.vue -->
<template>
  <div class="bg-gradient-to-b from-gray-50 to-gray-100 min-h-screen flex flex-col justify-between">
    <div class="content">
      <PageHeader>
        <template #title> Наши решения</template>
        <template #subtitle>
            Инновационные технологии и продукты, разработанные для оптимизации ваших
              бизнес-процессов и увеличения эффективности работы.
        </template>
      </PageHeader>
      <Button
        label="Связаться с нами"
        icon="pi pi-envelope"
        class="btn-main mb-3"
        @click="goToSubmitIdea" />

      <div class="search">
        <div class="flex">
          <InputText
            v-model="searchQuery"
            placeholder="Поиск решений..."
            class="flex-1" />
          <Button
            icon="pi pi-filter-fill"
            class="btn-img mx-2"/>
          <Button
            label="Поиск"
            icon="pi pi-search"
            class="p-button-primary btn-main mx-2"/>
        </div>
      </div>
      <!-- Фильтры и поиск -->
      <div class="container mx-auto px-6 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
          <div class="w-full md:w-auto">
            <span class="p-input-icon-left w-full md:w-64">
              <i class="text-gray-400"></i>

            </span>
          </div>
          <div class="flex flex-wrap gap-2">
            <ButtonGroup>
              <Button
                v-for="category in categories"
                :key="category.id"
                :label="category.name"
                :text="true"
                :severity="selectedCategory === category.id ? 'primary' : 'secondary'"
                @click="selectedCategory = category.id" />
            </ButtonGroup>
          </div>
        </div>
      </div>

      <!-- Сетка карточек решений -->
      <div class="container mx-auto mb-5">
        <Divider align="left">
          <div class="flex items-center">
            <i class="pi pi-th-large mr-2"></i>
            <span class="text-lg font-medium">{{ filteredSolutions.length }} доступных решений</span>
          </div>
        </Divider>

        <div
          v-if="filteredSolutions.length"
          class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="solution in filteredSolutions"
            :key="solution.id"
            class="group">
            <Card
              class="h-full transition-all duration-300 hover:shadow-lg border border-gray-200 overflow-hidden">
              <!-- Заголовок карточки с изображением -->
              <template #header>
                <div class="relative overflow-hidden">
                  <img
                    :src="solution.image"
                    :alt="solution.title"
                    class="w-4/5 h-[280px] object-cover mx-auto transform group-hover:scale-105 transition-transform duration-500" />
                  <div
                    class="absolute top-0 right-0 bg-primary px-3 py-1 rounded-bl-lg text-white text-sm">
                    {{ solution.category }}
                  </div>
                </div>
              </template>

              <!-- Основное содержимое карточки -->
              <template #content>
                <div class="p-4">
                  <div class="mb-3 flex items-center gap-2">
                    <i :class="['text-primary text-lg', solution.icon]"></i>
                    <h3 class="text-xl font-bold text-gray-800">{{ solution.title }}</h3>
                  </div>

                  <p class="text-gray-600 mb-4">{{ solution.description }}</p>

                  <!-- Ключевые особенности -->
                  <div
                    v-if="solution.features && solution.features.length"
                    class="mb-4">
                    <h4 class="font-medium text-gray-800 mb-2">Ключевые особенности:</h4>
                    <ul class="space-y-1">
                      <li
                        v-for="(feature, idx) in solution.features"
                        :key="idx"
                        class="flex items-start gap-2">
                        <i class="pi pi-check-circle text-green-500 mt-1"></i>
                        <span class="text-sm text-gray-600">{{ feature }}</span>
                      </li>
                    </ul>
                  </div>

                  <!-- Технические метки -->
                  <div class="flex flex-wrap gap-2 mt-4">
                    <span
                      v-for="(tag, idx) in solution.tags"
                      :key="idx"
                      class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-md">
                      {{ tag }}
                    </span>
                  </div>
                </div>
              </template>

              <!-- Футер карточки -->
              <template #footer>
                <div class="px-4 pb-4 pt-2 flex justify-between items-center">
                  <Tag
                    :value="`${solution.clients}+ клиентов`"
                    severity="info" />

                  <Button
                    label="Подробнее"
                    icon="pi pi-arrow-right"
                    text
                    @click="goToSolution(solution.link)" />
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Состояние пустого результата -->
        <div
          v-else
          class="p-8 text-center bg-white rounded-lg shadow">
          <i class="pi pi-search text-4xl text-gray-300 mb-4"></i>
          <h3 class="text-xl font-medium text-gray-800 mb-2">Решения не найдены</h3>
          <p class="text-gray-600 mb-4">Попробуйте изменить параметры поиска или фильтры</p>
          <Button
            label="Сбросить фильтры"
            icon="pi pi-refresh"
            @click="resetFilters" />
        </div>
      </div>

    </div>
    <div class="container mx-auto text-center">
      <h2 class="text-xl font-bold">Не нашли подходящее решение?</h2>
      <p class="text-base mb-3">
        Наша команда разработает индивидуальное решение под ваши потребности
      </p>
      <div class="flex justify-center gap-4">
        <Button
          label="Обсудить проект"
          icon="pi pi-comments"
          size="large"
          severity="success"
          class="btn-main"
          @click="goToSubmitIdea" />
      </div>
    </div>
  </div>
</template>

<script setup>
import Button from 'primevue/button';
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const goToSubmitIdea = () => {
  router.push('/submit-idea'); // убедитесь, что этот путь совпадает с настройками вашего роутинга
};

// Поля для поиска и выбранной категории
const searchQuery = ref('');
const selectedCategory = ref(null);

// Список категорий (обновлён под новые решения)
const categories = [
  { id: null, name: 'Все' },
  { id: 'crm', name: 'CRM' },
  { id: 'automation', name: 'Автоматизация' },
  { id: 'analytics', name: 'Аналитика' },
  { id: 'task-management', name: 'Управление' },
];

// Реактивное хранилище решений (изначально пустое)
const solutions = ref([]);

// При монтировании компонента — загружаем данные с бэкенда (PHP)
onMounted(async () => {
  try {
    // Укажите реальный путь к вашему PHP-скрипту, который отдаёт JSON
    const response = await fetch('https://bg59.online/Apps/bg_pattern_app/api/get_solutions.php');
    const data = await response.json();
    solutions.value = data;
  } catch (error) {
    console.error('Ошибка при загрузке решений:', error);
  }
});

// Вычисляемое свойство для фильтрации решений по поиску и категории
const filteredSolutions = computed(() => {
  return solutions.value.filter((solution) => {
    // Поиск по названию и описанию
    const matchesSearch =
      searchQuery.value === '' ||
      solution.title?.toLowerCase()?.includes(searchQuery.value.toLowerCase()) ||
      solution.description?.toLowerCase()?.includes(searchQuery.value.toLowerCase());

    // Фильтрация по выбранной категории
    const matchesCategory =
      selectedCategory.value === null || solution.categoryId === selectedCategory.value;

    return matchesSearch && matchesCategory;
  });
});

// Переход по ссылке (при нажатии "Подробнее")
const goToSolution = (link) => {
  window.open(link, '_blank');
};

// Сброс поиска и выбранной категории
const resetFilters = () => {
  searchQuery.value = '';
  selectedCategory.value = null;
};
</script>

<style scoped>
/* Стили для карточек */
:deep(.p-card) {
  border-radius: 12px;
  height: 100%;
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

:deep(.p-card .p-card-header) {
  padding: 0;
}

:deep(.p-card .p-card-body) {
  display: flex;
  flex-direction: column;
  height: 100%;
}

:deep(.p-card .p-card-content) {
  flex: 1;
}

/* Стили для героя */
.bg-primary {
  background-color: #3b82f6;
}

/* Анимации при наведении */
.transition-all {
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Стили для скругленных углов изображений */
:deep(.p-card .p-card-header img) {
  border-top-left-radius: 12px;
  border-top-right-radius: 12px;
}
</style>
