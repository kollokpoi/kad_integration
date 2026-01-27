import { ApiService } from './api/index.js';
import { useAuthStore } from './store.js';

/**
 * Основной SDK класс
 */
export class B24AuthSDK {
  constructor(config) {
    if (!config.baseURL) throw new Error('baseURL is required');
    if (!config.appId) throw new Error('appId is required');

    this.config = config;
    this.api = new ApiService(config.baseURL);
    this.domain = null;
  }

  /**
   * Определить домен портала
   */
  async determineDomain() {
    // 1. Из BX24
    if (typeof BX24 !== 'undefined' && BX24 !== null) {
      try {
        if (BX24.isAdmin()) {
          this.config.auth = BX24.getAuth();
        }
        this.domain = BX24.getDomain();
        return this.domain;
      } catch (error) {
        console.warn('Cannot get domain from BX24:', error);
      }
    }

    // 2. Из URL параметров
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('DOMAIN')) {
      this.domain = urlParams.get('DOMAIN');
      return this.domain;
    }

    // 3. Из referrer
    if (document.referrer) {
      try {
        const referrerUrl = new URL(document.referrer);
        this.domain = referrerUrl.hostname;
        return this.domain;
      } catch (e) {
        console.warn('Failed to parse domain from referrer:', e);
      }
    }

    // 4. Текущий домен
    this.domain = window.location.hostname;
    return this.domain;
  }

  /**
   * Инициализировать SDK и создать готовый store
   */
  async createStore() {
    // Определяем домен
    await this.determineDomain();

    // Создаем store
    const store = useAuthStore();

    // Инициализируем store с конфигом и доменом
    await store.initialize({
      ...this.config,
      domain: this.domain  // Передаем уже определенный домен!
    }, this.api);

    return store;
  }
}

// Экспорт
export { ApiService } from './api/index.js';
export { useAuthStore } from './store.js';

export default B24AuthSDK;