// src/stores/auth.store.js
import { defineStore } from "pinia";
import { ref, computed } from "vue";
import apiService from "../services/subscriptionApi";

export const useAuthStore = defineStore("auth", () => {
  const accessToken = ref(null);
  const refreshToken = ref(null);
  const subscription = ref(null);
  const currentTariff = ref(null);
  const isInitialized = ref(false);
  const isLoading = ref(false);
  const error = ref(null);
  const domain = ref("b24-77kjwv.bitrix24.ru");
  const appId = ref(import.meta.env.VITE_APP_ID);

  const isAuthenticated = computed(() => {
    return !!accessToken.value && !!subscription.value;
  });

  const isSubscriptionActive = computed(() => {
    if (!subscription.value) return false;
    const status = subscription.value.status;
    const validUntil = new Date(subscription.value.valid_until);
    const now = new Date();
    return (status === "active" || status === "trial") && validUntil > now;
  });

  const isTrial = computed(() => {
    return subscription.value?.status === "trial";
  });

  const daysLeft = computed(() => {
    if (!subscription.value?.valid_until) return 0;
    const now = new Date();
    const until = new Date(subscription.value.valid_until);
    const diff = until - now;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
  });

  const trialDaysLeft = computed(() => {
    if (!subscription.value?.trial_end_date) return 0;
    const now = new Date();
    const trialEnd = new Date(subscription.value.trial_end_date);
    const diff = trialEnd - now;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
  });

  async function initialize() {
    if (isInitialized.value) return;

    isLoading.value = true;
    error.value = null;

    try {
      if (typeof BX24 !== "undefined") {
        try {
          domain.value = BX24.getDomain();
        } catch (error) {
          console.warn("Cannot get domain from BX24:", error);
        }
      }

      if (!domain.value) {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);
        if (params.has("DOMAIN")) {
          domain.value = params.get("DOMAIN");
        } else if (document.referrer) {
          try {
            const referrerUrl = new URL(document.referrer);
            domain.value = referrerUrl.hostname;
          } catch (e) {
            domain.value = window.location.hostname;
          }
        }
      }

      loadTokens();

      if (accessToken.value && refreshToken.value) {
        await loadSubscriptionAndTariff();
      }
    } catch (err) {
      console.error("Auth initialization failed:", err);
      error.value = err.message;
    } finally {
      isLoading.value = false;
      isInitialized.value = true;
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

        if (accessToken.value && refreshToken.value) {
          apiService.setTokens(accessToken.value, refreshToken.value);
        }
      }
    } catch (err) {
      console.error("Failed to load tokens:", err);
    }
  }

  async function loadSubscriptionAndTariff() {
    try {
      const subscriptionResponse = await apiService.subscriptions.getCurrent();
      const tariffResponse = await apiService.tariffs.getCurrent();

      if (subscriptionResponse.success && subscriptionResponse.data) {
        subscription.value = subscriptionResponse.data;
      }
      if (tariffResponse.success && tariffResponse.data) {
        currentTariff.value = tariffResponse.data;
      }

      return subscription.value && currentTariff.value;
    } catch (err) {
      console.error("Failed to load subscription:", err);
      return false;
    }
  }

  async function loadTariff() {
    try {
      const tariffResponse = await apiService.tariffs.getCurrent();

      if (tariffResponse.success && tariffResponse.data) {
        currentTariff.value = tariffResponse.data;
        return true;
      }

      return false;
    } catch (err) {
      console.error("Failed to load tariff:", err);
      return false;
    }
  }

  async function login(force = false) {
    isLoading.value = true;
    error.value = null;

    try {
      if (!force && accessToken.value && refreshToken.value) {
        const loaded = await loadSubscriptionAndTariff();
        if (loaded) {
          return { success: true, data: subscription.value };
        }
      }

      if (!domain.value || !appId.value) {
        throw new Error("Domain or App ID not available");
      }

      const loginResponse = await apiService.auth.login(
        domain.value,
        appId.value,
      );

      if (loginResponse.success) {
        const { subscription: subData, tokens } = loginResponse.data;

        saveTokens(tokens.accessToken, tokens.refreshToken);

        subscription.value = subData;
        currentTariff.value = subData.tariff;

        return { success: true, data: subData };
      }

      error.value = loginResponse.message;
      return { success: false, message: loginResponse.message };
    } catch (err) {
      console.error("Login failed:", err);
      error.value = err.message;
      return { success: false, message: err.message };
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
      apiService.setTokens(access, refresh);

      // Сохраняем в BX24 options если доступно
      if (typeof BX24 !== "undefined") {
        try {
          const options = BX24.getOptions() || {};
          options.accessToken = access;
          options.refreshToken = refresh;
          options.tokenExpires = Date.now() + 3600000;
          BX24.setOptions(options);
        } catch (err) {
          console.warn("Failed to save tokens to BX24:", err);
        }
      }
    } catch (err) {
      console.error("Failed to save tokens:", err);
    }
  }

  async function refreshSubscription() {
    try {
      const loaded = await loadSubscriptionAndTariff();
      return { success: loaded };
    } catch (err) {
      console.error("Failed to refresh subscription:", err);
      return { success: false, message: err.message };
    }
  }

  async function logout() {
    try {
      if (refreshToken.value) {
        await apiService.auth.logout(refreshToken.value);
      }
    } catch (err) {
      console.warn("Logout API call failed:", err);
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
      apiService.clearTokens();

      if (typeof BX24 !== "undefined") {
        try {
          const options = BX24.getOptions() || {};
          delete options.accessToken;
          delete options.refreshToken;
          delete options.tokenExpires;
          BX24.setOptions(options);
        } catch (err) {
          console.warn("Failed to clear tokens from BX24:", err);
        }
      }
    } catch (err) {
      console.error("Failed to clear auth:", err);
    }
  }

  
  return {
    accessToken,
    refreshToken,
    subscription,
    currentTariff,
    isInitialized,
    isLoading,
    error,
    domain,
    appId,

    isAuthenticated,
    isSubscriptionActive,
    isTrial,
    daysLeft,
    trialDaysLeft,

    initialize,
    login,
    logout,
    saveTokens,
    refreshSubscription,
    loadTariff,
    clearAuth,
  };
});
