// src/utils/toastWrapper.js
import { globalSettings } from '../globalSettings.js'; // Добавляем импорт

// Создаем объект, который будет служить публичным интерфейсом
export const toastService = {
  add: null, // Будет заполнено позже

  // Метод, который можно вызывать откуда угодно
  showToast(options) {
    if (!globalSettings.toastEnabled) return;

    if (this.add) {
      this.add(options);
    } else {
      console.error('Toast service not initialized');
    }
  },
};

// Экспортируем функцию для установки метода add
export function initToastService(toastInstance) {
  toastService.add = toastInstance.add;
}
