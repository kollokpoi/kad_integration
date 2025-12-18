<!-- src/views/Tariffs.vue -->
<template>
  <div class="p-6 bg-gradient-to-b from-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto">
      <!-- Заголовок секции -->
      <PageHeader>
        <template #title> Выберите свой тариф</template>
        <template #subtitle>
          Найдите оптимальное решение для ваших задач. Все тарифы включают бесплатную техническую
          поддержку.
        </template>
      </PageHeader>

      <!-- Карточки тарифов -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <div
          v-for="(plan, index) in plans"
          :key="plan.name"
          class="relative group"
          :class="{ 'lg:-mt-4': plan.popular }">
          <!-- Бейдж "Популярный" -->
          <div
            v-if="plan.popular"
            class="absolute -top-4 inset-x-0 mx-auto w-32 bg-primary text-white rounded-full py-1 font-medium text-sm text-center shadow-md z-10">
            Популярный
          </div>

          <!-- Карточка тарифа -->
          <Card
            class="transition-all duration-300 h-full overflow-hidden border"
            :class="[
              plan.popular
                ? 'shadow-lg border-primary'
                : 'shadow-md border-gray-200 hover:shadow-xl',
              'group-hover:transform group-hover:scale-[1.02]',
            ]">
            <!-- Заголовок карточки -->
            <template #header>
              <div
                class="p-4 border-b bg-gray-50"
                :class="{ 'bg-primary bg-opacity-5': plan.popular }">
                <div class="flex flex-col items-center text-center">
                  <h3 class="text-2xl font-bold text-gray-800">{{ plan.name }}</h3>
                  <div class="flex items-baseline mt-2">
                    <span class="text-3xl font-bold text-primary">{{ plan.priceValue }}</span>
                    <span class="text-lg text-gray-600 ml-1">₽/мес</span>
                  </div>
                  <p
                    class="text-sm text-gray-500 mt-1"
                    :class="{ 'text-white': plan.popular }">
                    {{ plan.billing }}
                  </p>
                </div>
              </div>
            </template>

            <!-- Содержимое карточки -->
            <template #content>
              <div class="p-4">
                <ul class="space-y-3 mb-8">
                  <li
                    v-for="(feature, idx) in plan.features"
                    :key="idx"
                    class="flex items-start gap-3">
                    <i class="pi pi-check-circle mt-1 text-primary"></i>
                    <span class="text-gray-700">{{ feature }}</span>
                  </li>
                </ul>
              </div>
            </template>

            <!-- Футер карточки -->
            <template #footer>
              <div class="p-4 pt-0">
                <Button
                  v-if="plan.cta"
                  :label="plan.cta"
                  :class="[
                    'w-full mb-3 p-button-lg',
                    plan.popular ? 'p-button-raised' : 'p-button-outlined',
                  ]"
                  @click="contactTelegram(plan.name)" />

                <div class="flex justify-center gap-4 mt-4">
                  <a
                    @click.stop="contactTelegram(plan.name)"
                    class="flex items-center text-gray-600 hover:text-primary cursor-pointer">
                    <i class="pi pi-send mr-1"></i>
                    <span class="text-sm">Telegram</span>
                  </a>

                  <a
                    @click.stop="contactEmail(plan.name)"
                    class="flex items-center text-gray-600 hover:text-primary cursor-pointer">
                    <i class="pi pi-envelope mr-1"></i>
                    <span class="text-sm">Email</span>
                  </a>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>
      <!-- Дополнительная информация -->

      <div class="mt-16 text-center">
        <p class="text-gray-600">
          Есть вопросы по тарифам?
          <a
            href="https://vedernikov.bitrix24.ru/online/oninechat"
            target="_blank"
            rel="noopener noreferrer"
            class="text-primary font-medium hover:underline"
            >Свяжитесь с нами</a
          >
          или посмотрите
          <a
            href="https://www.bitrix24.ru/partners/partner/13927904/"
            target="_blank"
            rel="noopener noreferrer"
            class="text-primary font-medium hover:underline"
            >ответы на частые вопросы</a
          >.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import bitrixService from '../services/bitrixService.js';
const plans = ref([]);

onMounted(async () => {
  try {
    const response = await fetch('https://bg59.online/Apps/bg_pattern_app/api/get_tariffs.php');
    const result = await response.json();

    if (result.status === 'success' && result.plans) {
      plans.value = result.plans;
    } else {
      console.error('Ошибка получения тарифов:', result.message || 'Неизвестная ошибка', result);
    }
  } catch (error) {
    console.error('Ошибка загрузки тарифов:', error);
  }
});

onMounted(() => {
  bitrixService.initSubscriptionCheck();
});

const selectPlan = (plan) => {
  console.log('Выбран тариф:', plan.name);
  // Убрано использование Toast
};

const contactTelegram = () => {
  BX24.init(() => {
    window.open('https://t.me/Background59_bot', '_blank');
  });
};

const contactEmail = (planName) => {
  const subject = encodeURIComponent(`Покупка тарифа: ${planName}`);
  const body = encodeURIComponent('Здравствуйте! Интересует подробная информация по тарифу...');
  window.location.href = `mailto:it@bg59.ru?subject=${subject}&body=${body}`;
};
</script>

<style scoped>
/* Дополнительные стили для карточек */
:deep(.p-card) {
  border-radius: 12px;
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

:deep(.p-card .p-card-footer) {
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

/* Стили для бейджа популярности */
.bg-primary {
  background-color: #3b82f6 !important; /* Цвет primary из Tailwind */
  color: white !important;
}

/* Анимации при наведении */
.transition-all {
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
