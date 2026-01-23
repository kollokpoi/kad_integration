import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  // State
  const accessToken = ref(null);
  const refreshToken = ref(null);
  const subscription = ref(null);
  const currentTariff = ref(null);
  const domain = ref(null);
  const appId = ref(null);
  const isLoading = ref(false);
  const isInitialized = ref(false);
  const error = ref(null);
  const api = ref(null)

  // Computed
  const isAuthenticated = computed(() => {
    return !!accessToken.value && !!subscription.value;
  });

  const isSubscriptionActive = computed(() => {
    if (!subscription.value) return false;
    const status = subscription.value.status;
    const validUntil = new Date(subscription.value.valid_until);
    const now = new Date();
    return (status === 'active' || status === 'trial') && validUntil > now;
  });

  const daysLeft = computed(() => {
    if (!subscription.value?.valid_until) return 0;
    const now = new Date();
    const until = new Date(subscription.value.valid_until);
    const diff = until - now;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
  });

  const availablePages = computed(() => {
    if (!currentTariff.value?.limits?.availablePages) return [];

    const pages = currentTariff.value.limits.availablePages;
    return Array.isArray(pages) ? pages : [pages];
  });

  // Actions
  async function initialize(config, apiClient) {
    if (!config.appId) throw new Error('appId is required');
    if (!config.domain) throw new Error('domain is required');
    if (!apiClient) throw new Error('apiClient is required');

    isLoading.value = true;
    error.value = null;

    // Сохраняем конфиг и API
    appId.value = config.appId;
    domain.value = config.domain;
    api.value = apiClient;

    try {
      // Загружаем сохраненные токены
      loadTokens();
      isInitialized.value = true
      // Если есть токены, устанавливаем их в API и загружаем подписку
      if (accessToken.value && refreshToken.value) {
        api.value.setTokens(accessToken.value, refreshToken.value);
        await loadSubscriptionAndTariff();
      }
    } catch (err) {
      console.error('Auth initialization failed:', err);
      error.value = err.message;
      throw err;
    } finally {
      isLoading.value = false;
    }
  }

  function loadTokens() {
    try {
      if (!domain.value) return;

      const storageKey = `b24app_${domain.value}`;
      const saved = localStorage.getItem(storageKey);

      if (saved) {
        const tokens = JSON.parse(saved);
        accessToken.value = tokens.accessToken;
        refreshToken.value = tokens.refreshToken;
      }
    } catch (err) {
      console.error('Failed to load tokens:', err);
    }
  }

  async function loadSubscriptionAndTariff() {
    if (!api.value) return;

    try {
      const subscriptionResponse = await api.value.subscriptions.getCurrent();
      const tariffResponse = await api.value.tariffs.getCurrent();

      if (subscriptionResponse.success && subscriptionResponse.data) {
        subscription.value = subscriptionResponse.data;
      }
      if (tariffResponse.success && tariffResponse.data) {
        currentTariff.value = tariffResponse.data;
      }
    } catch (err) {
      console.error('Failed to load subscription:', err);
    }
  }

  async function login() {
    if (!api.value || !appId.value || !domain.value) {
      throw new Error('Store not properly initialized');
    }

    isLoading.value = true;
    error.value = null;

    try {
      const loginResponse = await api.value.auth.login(domain.value, appId.value);

      if (loginResponse.success) {
        const { subscription: subData, tokens } = loginResponse.data;

        // Сохраняем токены
        saveTokens(tokens.accessToken, tokens.refreshToken);
        api.value.setTokens(tokens.accessToken, tokens.refreshToken);

        subscription.value = subData;
        currentTariff.value = subData.tariff;

        return { success: true, data: subData };
      }

      error.value = loginResponse.message;
      return { success: false, message: loginResponse.message };
    } catch (err) {
      console.error('Login failed:', err);
      error.value = err.message;
      return { success: false, message: err.message };
    } finally {
      isLoading.value = false;
    }
  }

  async function register() {
    if (!api.value || !appId.value || !domain.value) {
      throw new Error('Store not properly initialized');
    }

    isLoading.value = true;
    error.value = null;

    try {
      const registerResponse = await api.value.auth.register(domain.value, appId.value);

      if (registerResponse.success) {
        const { subscription: subData, tokens } = registerResponse.data;

        // Сохраняем токены
        saveTokens(tokens.accessToken, tokens.refreshToken);
        api.value.setTokens(tokens.accessToken, tokens.refreshToken);

        subscription.value = subData;
        currentTariff.value = subData.tariff;

        return { success: true, data: subData };
      }

      error.value = registerResponse.message;
      return { success: false, message: registerResponse.message };
    } catch (err) {
      console.error('Login failed:', err);
      error.value = err.message;
      return { success: false, message: err.message };
    } finally {
      isLoading.value = false;
    }
  }

  const canAccessPage = (pageName) => {
    if (!currentTariff.value) return false;

    const pages = currentTariff.value.limits?.availablePages;
    if (!pages) return true; // Если ограничений нет - доступ есть

    return Array.isArray(pages)
      ? pages.includes(pageName)
      : pages === pageName;
  };

  function saveTokens(access, refresh) {
    try {
      if (!domain.value) return;

      const storageKey = `b24app_${domain.value}`;
      const tokens = {
        accessToken: access,
        refreshToken: refresh,
        savedAt: Date.now(),
      };

      localStorage.setItem(storageKey, JSON.stringify(tokens));
      accessToken.value = access;
      refreshToken.value = refresh;
    } catch (err) {
      console.error('Failed to save tokens:', err);
    }
  }

  async function logout() {
    try {
      if (refreshToken.value && api.value) {
        await api.value.auth.logout(refreshToken.value);
      }
    } catch (err) {
      console.warn('Logout API call failed:', err);
    } finally {
      clearAuth();
    }
  }

  function clearAuth() {
    try {
      if (domain.value) {
        const storageKey = `b24app_${domain.value}`;
        localStorage.removeItem(storageKey);
      }

      accessToken.value = null;
      refreshToken.value = null;
      subscription.value = null;
      currentTariff.value = null;
      error.value = null;

      if (api.value) {
        api.value.clearTokens();
      }
    } catch (err) {
      console.error('Failed to clear auth:', err);
    }
  }


  async function isActionAvailable(action) {
    try {
      const response = await api.value.subscriptions.checkActionAvalible(action)
      if (response.success) {
        return response.data
      } else {
        return { available: false, message: 'Действие недоступно' };
      }
    } catch (err) {
      console.error('Failed to clear auth:', err);
      return { available: false, message: 'Действие недоступно' };
    }
  }

  return {
    // State
    accessToken,
    refreshToken,
    subscription,
    currentTariff,
    domain,
    appId,
    isLoading,
    error,
    isInitialized,
    api,

    // Computed
    isAuthenticated,
    isSubscriptionActive,
    daysLeft,
    availablePages,

    // Actions
    initialize,
    login,
    logout,
    clearAuth,
    canAccessPage,
    isActionAvailable,
    register
  };
});