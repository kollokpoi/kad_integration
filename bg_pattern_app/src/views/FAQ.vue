<!-- src/views/FAQ.vue -->
<template>
  <div class="w-full h-full">
    <!-- Подключаем шапку с нужным контентом -->
    <PageHeader>
      <template #title> Часто задаваемые вопросы </template>
      <template #subtitle>
        Найдите ответы на наиболее распространенные вопросы о нашем приложении.
      </template>
    </PageHeader>

    <!-- Содержимое FAQ -->
    <div class="container mx-auto px-4 w-full">
      <!-- Поиск по FAQ -->
      <div class="mb-8 w-full">
        <span class="p-input-icon-left w-full">
          <i class="pi pi-search" />
          <InputText
            v-model="searchQuery"
            placeholder="Поиск по вопросам..."
            class="w-full p-inputtext-sm" />
        </span>
      </div>

      <!-- Категории FAQ -->
      <div class="bg-white shadow-md rounded-lg p-4 mb-8 w-full">
        <Tabs
          v-model:value="activeCategory"
          class="w-full">
          <TabList class="w-full flex flex-wrap">
            <Tab
              v-for="(category, index) in categories"
              :key="index"
              :value="index.toString()"
              class="flex-grow text-center">
              {{ category.name }}
            </Tab>
          </TabList>
          <TabPanels class="w-full">
            <TabPanel
              v-for="(category, index) in categories"
              :key="index"
              :value="index.toString()"
              class="w-full">
              <div class="p-4 w-full">
                <!-- Аккордеон с вопросами и ответами -->
                <Accordion
                  :multiple="true"
                  class="w-full">
                  <AccordionTab
                    v-for="(item, itemIndex) in filteredQuestions(category.questions)"
                    :key="itemIndex"
                    :header="item.question">
                    <p class="text-gray-700 leading-relaxed">
                      {{ item.answer }}
                    </p>
                    <div
                      v-if="item.links && item.links.length > 0"
                      class="mt-4">
                      <h4 class="text-sm font-semibold text-gray-700 mb-2">Полезные ссылки:</h4>
                      <ul class="list-disc list-inside text-indigo-600">
                        <li
                          v-for="(link, linkIndex) in item.links"
                          :key="linkIndex"
                          class="mb-1">
                          <a
                            :href="link.url"
                            class="hover:underline"
                            >{{ link.text }}</a
                          >
                        </li>
                      </ul>
                    </div>
                  </AccordionTab>
                </Accordion>
              </div>
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>

      <!-- Блок "Не нашли ответ?" -->
      <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-6 text-center w-full">
        <h2 class="text-xl font-semibold text-indigo-800 mb-2">Не нашли ответ на свой вопрос?</h2>
        <p class="text-indigo-700 mb-4">Свяжитесь с нашей службой поддержки, и мы поможем вам.</p>
        <Button
          label="Связаться с поддержкой"
          icon="pi pi-envelope"
          class="p-button-outlined p-button-primary" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const searchQuery = ref('');
const activeCategory = ref('0');

// Категории и вопросы (заглушки)
const categories = [
  {
    name: 'Общие вопросы',
    questions: [
      {
        question: 'Что такое ваше приложение?',
        answer:
          'Наше приложение — это инновационное решение для [описание функциональности]. Оно позволяет пользователям [описание возможностей] и значительно упрощает процесс [описание процесса].',
        links: [
          { text: 'Подробнее о приложении', url: '#about' },
          { text: 'Начало работы', url: '#getting-started' },
        ],
      },
      {
        question: 'Как начать использовать приложение?',
        answer:
          'Чтобы начать использовать наше приложение, вам нужно зарегистрироваться, создать учетную запись и выполнить несколько простых шагов настройки. Подробную инструкцию вы можете найти в нашем руководстве по началу работы.',
        links: [{ text: 'Руководство пользователя', url: '#guide' }],
      },
      {
        question: 'Является ли приложение бесплатным?',
        answer:
          'Мы предлагаем как бесплатную, так и премиум-версии нашего приложения. Бесплатная версия включает базовые функции, а премиум-версия предоставляет доступ к расширенным возможностям и дополнительным функциям.',
        links: [{ text: 'Сравнение планов', url: '#pricing' }],
      },
    ],
  },
  {
    name: 'Учетная запись',
    questions: [
      {
        question: 'Как изменить пароль?',
        answer:
          'Чтобы изменить пароль, перейдите в раздел "Настройки" > "Аккаунт" и выберите опцию "Изменить пароль". Вам будет предложено ввести текущий пароль, а затем новый пароль дважды для подтверждения.',
        links: [],
      },
      {
        question: 'Как удалить учетную запись?',
        answer:
          'Для удаления учетной записи перейдите в раздел "Настройки" > "Аккаунт" > "Удалить аккаунт". Обратите внимание, что это действие необратимо и приведет к потере всех данных, связанных с вашей учетной записью.',
        links: [],
      },
    ],
  },
  {
    name: 'Оплата',
    questions: [
      {
        question: 'Какие способы оплаты вы принимаете?',
        answer:
          'Мы принимаем различные способы оплаты, включая кредитные карты (Visa, MasterCard, American Express), PayPal и банковские переводы. В некоторых регионах доступны дополнительные локальные методы оплаты.',
        links: [{ text: 'Подробнее о способах оплаты', url: '#payment-methods' }],
      },
      {
        question: 'Как запросить возврат средств?',
        answer:
          'Если вы не удовлетворены нашим сервисом, вы можете запросить возврат средств в течение 30 дней с момента покупки. Для этого обратитесь в нашу службу поддержки и укажите причину возврата.',
        links: [{ text: 'Политика возврата', url: '#refund-policy' }],
      },
    ],
  },
  {
    name: 'Безопасность',
    questions: [
      {
        question: 'Как вы защищаете мои данные?',
        answer:
          'Мы используем современные технологии шифрования и придерживаемся лучших практик безопасности для защиты ваших данных. Вся информация передается по защищенным каналам с использованием протокола SSL/TLS.',
        links: [{ text: 'Политика конфиденциальности', url: '#privacy-policy' }],
      },
      {
        question: 'Что делать, если я заметил подозрительную активность?',
        answer:
          'Если вы заметили подозрительную активность в вашей учетной записи, немедленно измените пароль и свяжитесь с нашей службой поддержки. Мы проверим вашу учетную запись и примем необходимые меры для обеспечения безопасности.',
        links: [],
      },
    ],
  },
  {
    name: 'Техническая поддержка',
    questions: [
      {
        question: 'Как связаться с технической поддержкой?',
        answer:
          'Вы можете связаться с нашей технической поддержкой через форму обратной связи на сайте, по электронной почте support@example.com или по телефону +7 (123) 456-78-90. Наша служба поддержки работает с понедельника по пятницу с 9:00 до 18:00.',
        links: [{ text: 'Контакты', url: '#contacts' }],
      },
      {
        question: 'Каково среднее время ответа службы поддержки?',
        answer:
          'Мы стремимся отвечать на все запросы в течение 24 часов. В большинстве случаев наша команда отвечает в течение нескольких часов в рабочее время.',
        links: [],
      },
    ],
  },
];

// Фильтрация вопросов по поисковому запросу
const filteredQuestions = (questions) => {
  if (!searchQuery.value) return questions;

  return questions.filter(
    (item) =>
      item.question.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.answer.toLowerCase().includes(searchQuery.value.toLowerCase()),
  );
};
</script>

<style scoped>
/* Стили для табов и контейнера */
.bg-primary {
  background-color: #3b82f6;
}

/* Растягиваем контент на всю ширину */
:deep(.tabs) {
  width: 100%;
}

:deep(.tab-list) {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
}

:deep(.tab) {
  flex-grow: 1;
  text-align: center;
}

/* Стили для аккордеона */
:deep(.accordion) {
  width: 100%;
}

:deep(.accordion-tab) {
  margin-bottom: 0.5rem;
  width: 100%;
}

:deep(.accordion-header) {
  padding: 1rem;
  font-weight: 500;
  width: 100%;
}

:deep(.accordion-content) {
  padding: 1rem 1.5rem;
  width: 100%;
}

/* Анимации при наведении */
.transition-all {
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Стили для полной высоты и ширины */
.h-full {
  height: 100%;
}

.w-full {
  width: 100%;
}
</style>
