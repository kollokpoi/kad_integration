import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
  // State
  const accessToken = ref(null);
  const refreshToken = ref(null);
  const subscription = ref(null);
  const currentTariff = ref(null);
  const application = ref(null)
  const domain = ref(null);
  const appId = ref(null);
  const isLoading = ref(false);
  const isInitialized = ref(false);
  const error = ref(null);
  const api = ref(null)
  const auth = ref(null)

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

  const canAccessPage = (pageName) => {
    if (!currentTariff.value) return false;

    const pages = currentTariff.value.limits?.availablePages;
    if (!pages) return true; // Если ограничений нет - доступ есть

    return Array.isArray(pages)
      ? pages.includes(pageName)
      : pages === pageName;
  };

  const canSyncEntity = (entityName) => {
    if (!currentTariff.value) return false;

    const entities = availableEntities.value
    if (!entities) return true;

    return Array.isArray(entities)
      ? entities.includes(entityName)
      : entities === entityName;
  };

  const availableEntities = computed(() => {
    if (!currentTariff.value?.limits?.avalibleEntities) return [];

    const entities = currentTariff.value.limits.avalibleEntities;
    return Array.isArray(entities) ? entities : [entities];
  });

  // Actions
  async function initialize(config, apiClient) {
    if (!config.appId) throw new Error('appId is required');
    if (!config.domain) throw new Error('domain is required');
    if (!apiClient) throw new Error('apiClient is required');

    isLoading.value = true;
    error.value = null;
    console.log(config)
    // Сохраняем конфиг и API
    appId.value = config.appId;
    domain.value = config.domain;
    api.value = apiClient;

    if (config.auth)
      auth.value = config.auth;

    try {
      loadTokens();
      isInitialized.value = true

      if (accessToken.value && refreshToken.value) {
        api.value.setTokens(accessToken.value, refreshToken.value);
        await loadSubscriptionAndTariff();
        await loadApplicaion()
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
        await updateToken()
      }
      if (tariffResponse.success && tariffResponse.data) {
        currentTariff.value = tariffResponse.data;
      }
    } catch (err) {
      console.error('Failed to load subscription:', err);
    }
  }

  async function loadApplicaion() {
    if (!api.value) return;
    try {
      const applicaiton = await api.value.applications.get(appId.value);

      if (applicaiton.success && applicaiton.data) {
        application.value = applicaiton.data;
      }
    } catch (err) {
      console.error('Failed to load application:', err);
    }
  }

  async function login() {
    if (!api.value || !appId.value || !domain.value) {
      throw new Error('Store not properly initialized');
    }

    isLoading.value = true;
    error.value = null;

    try {
      const loginResponse = await api.value.auth.login(domain.value, appId.value, auth.value);

      if (loginResponse.success) {
        const { subscription: subData, tokens } = loginResponse.data;

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
      const registerResponse = await api.value.auth.register(domain.value, appId.value, auth.value);

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

  async function updateToken() {
    if (!api.value || !subscription.value) {
      throw new Error('Store not properly initialized');
    }
    isLoading.value = true;
    error.value = null;
    try {
      if (auth.value)
        await api.value.subscriptions.updateToken(auth.value);
    } catch (err) {
      console.error('update token failed:', err);
      error.value = err.message;
    } finally {
      isLoading.value = false;
    }
  }

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

  async function updateMetadata(newMetadata) {
    try {
      const response = await api.value.subscriptions.updateMetadata(newMetadata)
      if (response.success) {
        return response
      } else {
        return { success: false, message: 'Сохранение не выполнено' };
      }
    } catch (err) {
      console.error('Failed to clear auth:', err);
      return { success: false, message: err.message };
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
    application,
    api,

    // Computed
    isAuthenticated,
    isSubscriptionActive,
    daysLeft,
    availablePages,
    availableEntities,

    // Actions
    initialize,
    login,
    logout,
    clearAuth,
    canAccessPage,
    canSyncEntity,
    isActionAvailable,
    register,
    updateMetadata
  };
});