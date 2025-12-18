import { createApp } from 'vue';
import App from './App.vue';
import router from './router/index.js';
import './style.css';

// Импорт PrimeVue и нашего пресета

import PrimeVuePlugin from './plugins/primevue.js';
// Импорт компонентов PrimeVue

const app = createApp(App);

// Глобальная константа с ID продукта из таблицы SOLUTIONS (замени на нужный ID)
app.provide('productId', '9'); // передаём через provide

app.use(router);
app.use(PrimeVuePlugin);

// Дополнительно: зарегистрируйте глобальный обработчик ошибок
// Добавьте это в main.js или другой файл инициализации Vue
app.config.errorHandler = (err, instance, info) => {
  console.error('Vue глобальная ошибка:', err);
  console.info('Компонент:', instance);
  console.info('Информация:', info);

  // Дополнительно можно показать уведомление пользователю
  // если у вас есть доступ к toast из этого контекста
};

router.isReady().then(() => {
  router.push('/'); // Принудительный переход на главную страницу
});

app.mount('#app');
