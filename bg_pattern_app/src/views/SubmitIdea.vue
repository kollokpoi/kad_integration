<!-- src/views/SubmitIdea.vue -->
<template>
  <div class="bg-gradient-to-b from-gray-50 to-gray-100 min-h-screen">
    <PageHeader>
      <template #title> Оставить идею </template>
      <template #subtitle>
          Поделитесь своими идеями и предложениями, которые помогут нам улучшить наши продукты и
          сервисы. Мы ценим ваше мнение и стремимся создавать решения, отвечающие вашим
          потребностям.
      </template>
    </PageHeader>
    <Button
      label="Открытая линия"
      icon="pi pi-comments"
      class="btn-main"
      @click="goToOpenLine" />
    <!-- Основное содержимое -->
    <div class="container mx-auto  py-3">
      <!-- Уведомление -->
      <div
        v-if="notification.show"
        class="mb-6 flex items-start p-4 rounded-lg shadow-md border-l-4 transition-all duration-300"
        :class="{
          'bg-red-50 border-red-500': notification.type === 'error',
          'bg-blue-50 border-blue-500': notification.type === 'info',
          'bg-green-50 border-green-500': notification.type === 'success',
        }">
        <div class="flex-shrink-0 mr-3">
          <i
            class="text-2xl"
            :class="{
              'pi pi-times-circle text-red-500': notification.type === 'error',
              'pi pi-info-circle text-blue-500': notification.type === 'info',
              'pi pi-check-circle text-green-500': notification.type === 'success',
            }"></i>
        </div>
        <div class="flex-grow">
          <h3
            class="font-medium text-lg"
            :class="{
              'text-red-700': notification.type === 'error',
              'text-blue-700': notification.type === 'info',
              'text-green-700': notification.type === 'success',
            }">
            {{ notification.title }}
          </h3>
          <p
            class="text-sm"
            :class="{
              'text-red-600': notification.type === 'error',
              'text-blue-600': notification.type === 'info',
              'text-green-600': notification.type === 'success',
            }">
            {{ notification.message }}
          </p>
        </div>
        <button
          @click="hideNotification"
          class="ml-auto flex-shrink-0 text-gray-400 hover:text-gray-500">
          <i class="pi pi-times"></i>
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Левая колонка - Информация -->
        <div class="lg:col-span-1">
          <Card class="h-full shadow-md border-0">
            <template #header>
              <div class="bg-blue-50 mt-2">
                <div class="flex items-center mb-4">
                  <i class="pi pi-lightbulb text-3xl text-primary mr-2 text-blue"></i>
                  <h2 class="text-2xl font-bold text-gray-800">Ваши идеи имеют значение</h2>
                </div>
                <p>
                  Мы постоянно совершенствуем наши продукты и услуги, и ваш вклад неоценим для нас.
                </p>
              </div>
            </template>

            <template #content>
              <div class="mt-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                  Почему стоит поделиться идеей:
                </h3>

                <!-- Блок с цветными «шариками» (пример из предыдущего ответа) -->
                <div class="space-y-4 ml-2 mb-6">
                  <div class="flex items-start">
                    <div
                      class="flex-shrink-0 h-10 w-10 bg-indigo-500 rounded-md flex items-center justify-center mr-3">
                      <i class="pi pi-check-circle text-white"></i>
                    </div>
                    <div>
                      <h4 class="text-base font-medium text-gray-800">Улучшаем продукты</h4>
                      <p class="text-sm text-gray-600">
                        Ваши предложения помогают нам создавать лучшие решения
                      </p>
                    </div>
                  </div>

                  <div class="flex items-start">
                    <div
                      class="flex-shrink-0 h-10 w-10 bg-green-500 rounded-md flex items-center justify-center mr-3">
                      <i class="pi pi-clock text-white"></i>
                    </div>
                    <div>
                      <h4 class="text-base font-medium text-gray-800">Быстрая обратная связь</h4>
                      <p class="text-sm text-gray-600">
                        Мы ответим на ваше предложение в течение 48 часов
                      </p>
                    </div>
                  </div>

                  <div class="flex items-start">
                    <div
                      class="flex-shrink-0 h-10 w-10 bg-yellow-500 rounded-md flex items-center justify-center mr-3">
                      <i class="pi pi-star text-white"></i>
                    </div>
                    <div>
                      <h4 class="text-base font-medium text-gray-800">Реализуем лучшие идеи</h4>
                      <p class="text-sm text-gray-600">
                        Лучшие предложения становятся частью наших продуктов
                      </p>
                    </div>
                  </div>
                </div>

                <Divider />

                <!-- Блок "Связаться с нами напрямую" -->
                <div class="space-y-3">
                  <h3 class="text-lg font-bold text-gray-800 mb-2">
                    Связаться с нами напрямую:
                  </h3>

                  <!-- Сетка для контактов -->
                  <div class="grid gap-2 text-sm md:text-base">
                    <div class="flex items-center gap-2">
                      <i class="pi pi-envelope text-blue-600"></i>
                      <a
                        href="mailto:it@bg59.ru"
                        class="hover:underline transition-colors duration-200">
                        it@bg59.ru
                      </a>
                    </div>
                    <div class="flex items-center gap-2">
                      <i class="pi pi-phone text-blue-600"></i>
                      <span class="text-gray-700">+7 (342) 257-6-257</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i class="pi pi-map-marker text-blue-600"></i>
                      <span class="text-gray-700">г. Пермь, ул. Чкалова 9Д, 321 офис</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <i class="pi pi-globe text-blue-600"></i>
                      <a
                        href="https://bg59.ru/"
                        target="_blank"
                        class="hover:underline transition-colors duration-200">
                        bg59.ru - Поддержка 24 часа
                      </a>
                    </div>
                    <div class="flex items-center gap-2">
                      <i class="pi pi-telegram text-blue-600"></i>
                      <a
                        href="https://t.me/bg_1812"
                        target="_blank"
                        class="hover:underline transition-colors duration-200">
                        Telegram
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Правая колонка - Форма -->
        <div
          class="lg:col-span-2"
          ref="formSection">
          <Card class="shadow-md border-0">
            <template #header>
                <h2 class="text-2xl font-bold mb-2">Форма обратной связи</h2>
                <p class="opacity-90">Заполните форму ниже, чтобы поделиться своей идеей</p>
            </template>

            <template #content>
                <form @submit.prevent="submitIdea">
                  <div class="form-group my-4">
                    <InputText
                      id="name"
                      v-model="form.name"
                      placeholder="Имя"
                      class="w-full"
                      :class="{ 'border-red-500': v$.name.$error }"
                      aria-describedby="name-error" />
                    <small
                      id="name-error"
                      class="text-red-500"
                      v-if="v$.name.$error">
                      {{ v$.name.$errors[0].$message }}
                    </small>
                  </div>
                  <div class="form-group my-4">
                    <InputMask
                      id="phone"
                      v-model="form.phone"
                      mask="+7 (999) 999-99-99"
                      placeholder="Телефон"
                      class="w-full" />
                  </div>
                  <div class="form-group my-4">
                    <InputText
                      id="email"
                      v-model="form.email"
                      class="w-full"
                      placeholder="Email"
                      :class="{ 'border-red-500': v$.email.$error }"
                      aria-describedby="email-error" />
                    <small
                      id="email-error"
                      class="text-red-500"
                      v-if="v$.email.$error">
                      {{ v$.email.$errors[0].$message }}
                    </small>
                  </div>
                  <div class="form-group my-4">
                    <InputText
                      id="company"
                      v-model="form.company"
                      placeholder="Название компании"
                      class="w-full" />
                  </div>

                  <div class="form-group mb-6">
                    <label
                      for="ideaType"
                      class="block text-gray-700 font-medium mb-1"
                      >Тип идеи</label
                    >
                    <Dropdown
                      id="ideaType"
                      v-model="form.ideaType"
                      :options="ideaTypes"
                      optionLabel="name"
                      placeholder="Выберите тип идеи"
                      class="w-full"
                      :class="{ 'border-red-500': v$.ideaType.$error }"
                      aria-describedby="ideaType-error" />
                    <small
                      id="ideaType-error"
                      class="text-red-500"
                      v-if="v$.ideaType.$error">
                      {{ v$.ideaType.$errors[0].$message }}
                    </small>
                  </div>

                  <div class="form-group mb-6">
                    <label
                      for="subject"
                      class="block text-gray-700 font-medium mb-1"
                      >Тема</label
                    >
                    <InputText
                      id="subject"
                      v-model="form.subject"
                      class="w-full"
                      :class="{ 'border-red-500': v$.subject.$error }"
                      aria-describedby="subject-error" />
                    <small
                      id="subject-error"
                      class="text-red-500"
                      v-if="v$.subject.$error">
                      {{ v$.subject.$errors[0].$message }}
                    </small>
                  </div>

                  <div class="form-group mb-6">
                    <label
                      for="description"
                      class="block text-gray-700 font-medium mb-1"
                      >Описание идеи</label
                    >
                    <Textarea
                      id="description"
                      v-model="form.description"
                      rows="5"
                      class="w-full"
                      :class="{ 'border-red-500': v$.description.$error }"
                      aria-describedby="description-error" />
                    <small
                      id="description-error"
                      class="text-red-500"
                      v-if="v$.description.$error">
                      {{ v$.description.$errors[0].$message }}
                    </small>
                  </div>

                  <div class="form-group mb-6">
                    <label
                      for="file"
                      class="block text-gray-700 font-medium mb-1"
                      >Прикрепить файл</label
                    >
                    <div class="flex justify-between">
                      <span class="text-gray-500 block mt-1">Максимальный размер файла: 5MB</span>
                      <FileUpload
                        id="file"
                        mode="basic"
                        :multiple="true"
                        accept="image/*,application/pdf"
                        :maxFileSize="5000000"
                        customUpload
                        class="btn-main"
                        @uploader="onFileUpload"
                        chooseLabel="Выбрать файлы" />
                    </div>

                    
                  </div>

                  <div class="form-group mb-6">
                    <div class="flex items-center">
                      <Checkbox
                        id="consent"
                        v-model="form.consent"
                        :binary="true"
                        :class="{ 'border-red-500': v$.consent.$error }" />
                      <label
                        for="consent"
                        class="ml-2 text-gray-700">
                        Я согласен на обработку персональных данных *
                      </label>
                    </div>
                    <small
                      class="text-red-500"
                      v-if="v$.consent.$error">
                      {{ v$.consent.$errors[0].$message }}
                    </small>
                  </div>
                  <Button
                    type="submit"
                    label="Отправить"
                    class="btn-main w-full"
                    :loading="loading" />
                </form>
            </template>
          </Card>
        </div>
      </div>
    </div>

    <div class="container mx-auto px-6 text-center mt-15">
      <h2 class="text-2xl font-bold mb-2">Присоединяйтесь к нашему сообществу</h2>
      <p class="mx-auto mb-8 text-base opacity-90">
        Следите за обновлениями, делитесь мнениями и участвуйте в развитии наших продуктов
      </p>
      <div class="flex justify-center gap-4">
        <Button
          label="Telegram"
          icon="pi pi-telegram"
          size="large"
          severity="info"
          class="btn-main"
          @click="goToSocial('Telegram')" />
        <Button
          label="Вконтакте"
          icon="pi pi-vimeo"
          size="large"
          severity="info"
          class="btn-main"
          @click="goToSocial('Вконтакте')" />
      </div>
    </div>
  </div>
  <!-- Диалог успешной отправки -->
  <Dialog
    v-model:visible="successDialog"
    modal
    header="Спасибо за вашу идею!"
    :style="{ width: '450px' }"
    :closable="false">
    <div class="flex flex-col items-center text-center p-4">
      <i class="pi pi-check-circle text-green-500 text-5xl mb-4"></i>
      <h3 class="text-xl font-semibold text-gray-800 mb-2">Ваша идея успешно отправлена</h3>
      <p class="text-gray-600 mb-4">
        Мы рассмотрим ваше предложение в ближайшее время и свяжемся с вами по указанным контактным
        данным.
      </p>
      <p class="text-gray-600 mb-6">
        Номер вашей заявки: <span class="font-semibold">{{ ideaNumber }}</span>
      </p>
      <Button
        label="Вернуться на главную"
        icon="pi pi-home"
        @click="goToHome" />
    </div>
  </Dialog>
</template>

<script setup>
import { useVuelidate } from '@vuelidate/core';
import { email, minLength, required } from '@vuelidate/validators';
import { computed, inject, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
const productId = inject('productId', '');
console.log('ProductId:', productId);

const router = useRouter();
const formSection = ref(null);
const loading = ref(false);
const successDialog = ref(false);
const ideaNumber = ref('');

const goToSocial = (label) => {
  let url = '';
  if (label === 'Telegram') {
    url = 'https://t.me/bg_1812'; // ссылка для Telegram
  } else if (label === 'Вконтакте') {
    url = 'https://vk.com/bgcrm'; // замените на актуальную ссылку для Вконтакте
  }
  if (url) {
    window.open(url, '_blank', 'noopener,noreferrer');
  }
};

// Кастомное уведомление вместо Toast
const notification = reactive({
  show: false,
  type: 'info', // 'info', 'error', 'success'
  title: '',
  message: '',
  timeout: null,
});

// Показать уведомление
const showNotification = (type, title, message, duration = 5000) => {
  if (notification.timeout) {
    clearTimeout(notification.timeout);
  }
  notification.show = true;
  notification.type = type;
  notification.title = title;
  notification.message = message;
  notification.timeout = setTimeout(() => {
    notification.show = false;
  }, duration);
};

// Скрыть уведомление
const hideNotification = () => {
  notification.show = false;
  if (notification.timeout) {
    clearTimeout(notification.timeout);
  }
};

// Типы идей
const ideaTypes = [
  { id: 1, name: 'Новый функционал' },
  { id: 2, name: 'Улучшение существующего функционала' },
  { id: 3, name: 'Исправление ошибки' },
  { id: 4, name: 'Интеграция' },
  { id: 5, name: 'Другое' },
];

// Форма
const form = reactive({
  name: '',
  email: '',
  company: '',
  phone: '',
  ideaType: null,
  subject: '',
  description: '',
  files: [],
  consent: false,
});

// Правила валидации
const rules = computed(() => {
  return {
    name: { required, minLength: minLength(2) },
    email: { required, email },
    ideaType: { required },
    subject: { required, minLength: minLength(5) },
    description: { required, minLength: minLength(20) },
    consent: { required },
  };
});

const v$ = useVuelidate(rules, form);

// Методы
const scrollToForm = () => {
  formSection.value.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const onFileUpload = (event) => {
  form.files = [...form.files, ...event.files];
  showNotification('info', 'Файлы добавлены', `Добавлено ${event.files.length} файл(ов)`, 3000);
};

const submitIdea = async () => {
  const isValid = await v$.value.$validate();
  if (!isValid) {
    showNotification('error', 'Ошибка', 'Пожалуйста, заполните все обязательные поля', 5000);
    return;
  }

  try {
    loading.value = true;
    // Формируем URL для создания лида через Bitrix24 REST API
    // Важно: для создания лида используется метод crm.lead.add, поэтому добавляем .crm.lead.add.json к базовому URL
    const url = 'https://vedernikov.bitrix24.ru/rest/230/433vrdvrmfsbe0w3/crm.lead.add.json';

    // Выполняем POST-запрос с полями лида
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        fields: {
          // Маппинг полей формы к полям лида Bitrix24
          TITLE: form.subject,
          NAME: form.name,
          EMAIL: [{ VALUE: form.email, VALUE_TYPE: 'WORK' }],
          PHONE: form.phone ? [{ VALUE: form.phone, VALUE_TYPE: 'WORK' }] : [],
          COMPANY_TITLE: form.company,
          // В COMMENTS добавляем описание и тип идеи
          COMMENTS: `${form.description}
Тип идеи: ${form.ideaType ? form.ideaType.name : ''}
ID приложения: ${productId}`,
        },
        params: { REGISTER_SONET_EVENT: 'Y' },
      }),
    });

    if (!response.ok) {
      throw new Error(`Ошибка сервера: ${response.statusText}`);
    }

    const result = await response.json();
    if (result.result) {
      ideaNumber.value = result.result;
      successDialog.value = true;
      resetForm();
    } else {
      throw new Error(result.error_description || 'Ошибка при создании лида');
    }
  } catch (error) {
    showNotification('error', 'Ошибка', error.message, 5000);
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  Object.assign(form, {
    name: '',
    email: '',
    company: '',
    phone: '',
    ideaType: null,
    subject: '',
    description: '',
    files: [],
    consent: false,
  });
  v$.value.$reset();
};

const goToOpenLine = () => {
  window.open('https://vedernikov.bitrix24.ru/online/oninechat', '_blank');
};

const goToHome = () => {
  successDialog.value = false;
  router.push('/');
};
</script>

<style scoped>
/* Стили для карточек */
:deep(.card) {
  border-radius: 12px;
  height: 100%;
  overflow: hidden;
}

/* Стили для героя */
.bg-primary {
  background-color: #3b82f6;
}

/* Анимации при наведении */
.transition-all {
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Стили для полей формы */
:deep(.input-text),
:deep(.input-mask),
:deep(.dropdown),
:deep(.textarea) {
  width: 100%;
  border-radius: 0.375rem;
  transition: all 0.3s ease;
}

:deep(.dropdown-panel) {
  border-radius: 0.375rem;
}

:deep(.file-upload) {
  width: 100%;
}

/* Hover эффекты для кнопок */
:deep(.button):hover {
  transform: translateY(-2px);
  transition: transform 0.2s ease;
}

/* Стили для уведомлений */
@keyframes slideIn {
  from {
    transform: translateY(-20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

[v-if='notification.show'] {
  animation: slideIn 0.3s ease-out forwards;
}
</style>
