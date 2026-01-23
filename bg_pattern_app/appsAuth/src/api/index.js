import { ApiClient } from './services/client.js';
import { AuthService } from './services/auth.js';
import { SubscriptionService } from './services/subscription.js';
import { TariffService } from './services/tariff.js';
import { PortalService } from './services/portal.js';
import { ApplicationService } from './services/application.js';

export class ApiService {
  constructor(baseURL) {
    const client = new ApiClient(baseURL);
    
    this.auth = new AuthService(client);
    this.subscriptions = new SubscriptionService(client);
    this.tariffs = new TariffService(client);
    this.portals = new PortalService(client);
    this.applications = new ApplicationService(client);
    
    // Экспортируем клиент для прямого доступа
    this.client = client;
  }

  setTokens(accessToken, refreshToken) {
    this.client.setTokens(accessToken, refreshToken);
  }

  clearTokens() {
    this.client.clearTokens();
  }
}

// Экспорт отдельных сервисов если нужно
export { AuthService, SubscriptionService, TariffService, PortalService, ApplicationService };