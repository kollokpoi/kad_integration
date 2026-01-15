import bitrixService from "./bitrixService";

class ApiService {
  basePath = "https://bg59.online/Apps/bg_kad_integration/api/";
  portalDomain = null;

  constructor() {
    this.initFromBitrix();
  }

  /**
   * Инициализация из Bitrix
   */
  async initFromBitrix() {
    if (await bitrixService.isConnected() && bitrixService.appData) {
      this.portalDomain = bitrixService.appData.auth.domain;
      this.syncTokenFromBitrix();
    }
  }

  /**
   * Отправляет запрос к API
   */
  async sendRequest(endpoint, method = "GET", data = null) {
    try {
      const url = this.basePath + endpoint;
      const options = {
        method,
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        mode: "cors",
      };

      if (data) {
        options.body = JSON.stringify(data);
      }

      const response = await fetch(url, options);

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.error || `HTTP ${response.status}`);
      }

      return await response.json();
    } catch (error) {
      console.error("API Request Error:", error);
      throw error;
    }
  }

  /**
   * Синхронизирует токен из Bitrix в нашу API
   */
  async syncTokenFromBitrix() {
    if (!this.portalDomain || !bitrixService.appData) {
      console.warn(
        "Cannot sync token: portal domain or Bitrix data not available"
      );
      return;
    }

    const bitrixAuth = bitrixService.appData.auth;

    try {
      const response = await this.sendRequest("token/update", "POST", {
        domain: this.portalDomain,
        token: {
          access_token: bitrixAuth.access_token,
          refresh_token: bitrixAuth.refresh_token || "",
          member_id: bitrixAuth.member_id || "",
          user_id: bitrixAuth.user_id || 0,
        },
      });

      console.log("Token synchronized successfully:", response);
      return {
        success: true,
        data: response,
      };
    } catch (error) {
      console.error("Failed to sync token:", error);
      return {
        success: false,
        error: error.message,
      };
    }
  }

  /**
   * Получает настройки портала
   */
  async loadSettings() {
    if (!this.portalDomain) {
      throw new Error("Portal domain not set");
    }

    try {
      const response = await this.sendRequest(
        `portal/settings?domain=${encodeURIComponent(this.portalDomain)}`,
        "GET"
      );

      return {
        success: true,
        data: response,
        portal: this.portalDomain,
      };
    } catch (error) {
      return {
        success: false,
        error: error.message,
        portal: this.portalDomain,
      };
    }
  }

  /**
   * Обновляет настройки портала
   */
  async updateSettings(settings) {
    if (!this.portalDomain) {
      throw new Error("Portal domain not set");
    }

    try {
      const response = await this.sendRequest("portal/settings", "POST", {
        domain: this.portalDomain,
        settings: settings,
      });

      return {
        success: true,
        data: response,
        portal: this.portalDomain,
      };
    } catch (error) {
      return {
        success: false,
        error: error.message,
        portal: this.portalDomain,
      };
    }
  }

  /**
   * Проверяет здоровье API
   */
  async checkHealth() {
    try {
      const response = await this.sendRequest("health", "GET");
      return {
        success: true,
        status: response.status,
        timestamp: response.timestamp,
      };
    } catch (error) {
      return {
        success: false,
        error: error.message,
      };
    }
  }

  /**
   * Устанавливает портал вручную
   */
  setPortalDomain(domain) {
    this.portalDomain = domain;
    return this;
  }

  /**
   * Получает текущий портал
   */
  getPortalDomain() {
    return this.portalDomain;
  }

  /**
   * Проверяет, инициализирован ли сервис
   */
  isInitialized() {
    return !!this.portalDomain;
  }

  /**
   * Получает данные из Bitrix (для отладки)
   */
  getBitrixData() {
    return bitrixService.appData || null;
  }
}

const apiService = new ApiService();

export default apiService;
