<!-- src/views/Tariffs.vue -->
<template>
  <div class="p-6 bg-gradient-to-b from-gray-50 to-gray-100 min-h-screen">
    <div class="max-w-7xl mx-auto">
      <!-- Заголовок секции -->
      <PageHeader>
        <template #title> Выберите свой тариф</template>
        <template #subtitle>
          Найдите оптимальное решение для ваших задач. Все тарифы включают
          бесплатную техническую поддержку.
        </template>
      </PageHeader>

      <div v-if="authStore.isAuthenticated" class="subscription-status">
        <div v-if="authStore.isSubscriptionActive" class="active-subscription">
          <Card class="mb-3 p-3">
            <template #content>
              <div class="flex items-center justify-between gap-2">
                <div>
                  <i class="pi pi-check-circle text-green-500"></i>
                  <span>Тариф:
                    {{ authStore.currentTariff?.name || "Не указан" }}</span>
                </div>
                <div v-if="authStore.isTrial">
                  <div class="text-600 text-sm mb-1">Триальный период</div>
                  <div class="flex items-center gap-2">
                    <i class="pi pi-clock text-orange-500"></i>
                    <span class="font-semibold">
                      Осталось {{ authStore.trialDaysLeft }} дней
                    </span>
                  </div>
                  <small class="text-600">
                    Заканчивается
                    {{ formatDate(authStore.subscription?.trial_end_date) }}
                  </small>
                </div>

                <div v-else>
                  <div class="text-600 text-sm mb-1">Срок действия</div>
                  <div class="flex items-center gap-2">
                    <i class="pi pi-calendar text-blue-500"></i>
                    <span class="font-semibold">
                      Осталось {{ authStore.daysLeft }} дней
                    </span>
                  </div>
                  <small class="text-600">
                    Заканчивается
                    {{ formatDate(authStore.subscription?.valid_until) }}
                  </small>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <div v-else>
          <Message severity="warn" class="mb-3">
            <div class="flex items-center gap-2">
              <i class="pi pi-exclamation-triangle"></i>
              <span>Подписка неактивна</span>
            </div>
          </Message>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <div v-for="(plan, index) in plans" :key="plan.name" class="relative group"
          :class="{ 'lg:-mt-4': plan.popular }">
          <div class="transition-all duration-300 h-full overflow-hidden border flex flex-col" :class="[
            'shadow-md border-gray-200 hover:shadow-xl',
            'group-hover:transform group-hover:scale-[1.02]',
          ]">
            <div class="p-4 border-b bg-gray-50">
              <div class="flex flex-col items-center text-center">
                <h3 class="text-2xl font-bold text-gray-800">
                  {{ plan.name }}
                </h3>
                <div class="flex items-baseline mt-2">
                  <span class="text-3xl font-bold text-primary">{{
                    plan.price
                  }}</span>
                  <span class="text-lg text-gray-600 ml-1">₽/{{ getPeriodLabel(plan.period) }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1" :class="{ 'text-white': plan.popular }">
                  {{ plan.description }}
                </p>
              </div>
            </div>
            <div class="p-4 flex-1">
              <ul class="space-y-3 mb-8">
                <li v-for="(feature, idx) in plan.features" :key="idx" class="flex items-start gap-3">
                  <i class="pi pi-check-circle mt-1 text-primary"></i>
                  <span class="text-gray-700">{{ feature }}</span>
                </li>
              </ul>
            </div>
            <div class="p-4 mt-auto">
              <Button v-if="
                authStore.currentTariff &&
                authStore.currentTariff.id === plan.id
              " class="w-full" outlined="" label="Текущий" />
              <Button v-else class="w-full" label="Выбрать" @click="goToTelegram(plan)" />
            </div>
          </div>
        </div>
      </div>
      <div class="mt-16 text-center">
        <p class="text-gray-600">
          Есть вопросы по тарифам?
          <a href="https://vedernikov.bitrix24.ru/online/oninechat" target="_blank" rel="noopener noreferrer"
            class="text-primary font-medium hover:underline">Свяжитесь с нами</a>
          или посмотрите
          <a href="https://www.bitrix24.ru/partners/partner/13927904/" target="_blank" rel="noopener noreferrer"
            class="text-primary font-medium hover:underline">ответы на частые вопросы</a>.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { showError } from "../utils/toastUtils";
import { useAuthStore } from '@payment-app/authSdk'

const authStore = useAuthStore();
const toast = useToast();
const plans = ref([]);
const api = authStore.api

function formatDate(dateString) {
  if (!dateString) return "—";
  return new Date(dateString).toLocaleDateString("ru-RU");
}
const loadTariffs = async () => {
  try {
    const response = await api.tariffs.getForApp(
      import.meta.env.VITE_APP_ID,
    );
    if (response.success) {
      plans.value = response.data.filter(x => x.showInList);
      plans.value.sort((a, b) => a.sortOrder - b.sortOrder)
    } else {
      showError(toast, `Ошибка загрузки тарифов`);
    }
  } catch (error) {
    showError(toast, `Ошибка загрузки тарифов`);
  }
}

const goToTelegram = (plan) => {
  const userData = {
    domain: authStore.domain,
    tariff: plan.name,
    application:authStore.application.name, 
  };

  const message = `
*Домен портала:* ${userData.domain}
*Приложение:* ${userData.application}
*Тариф:* ${userData.tariff}

  `.trim();

  const encodedMessage = encodeURIComponent(message);
  window.open(`https://t.me/bg_1812?text=${encodedMessage}`, '_blank');
}

onMounted(async () => {
  await loadTariffs();
});

const getPeriodLabel = (period) => {
  const periodLabels = {
    day: 'день',
    week: 'неделя',
    month: 'месяц',
    year: 'год'
  }
  return periodLabels[period] || period
}
</script>
