import { ApiClient } from './apiClient.js';
import { AuthService } from './authService.js';
import { SubscriptionService } from './subscriptionService.js';
import { TariffService } from './tariffService.js';
import { PortalService } from './portalService.js';
import { ApplicationService } from './applicationService.js';

class ApiService {
  constructor(baseURL) {
    this.apiClient = new ApiClient(baseURL);
    
    this.auth = new AuthService(this.apiClient);
    this.subscriptions = new SubscriptionService(this.apiClient);
    this.tariffs = new TariffService(this.apiClient);
    this.portals = new PortalService(this.apiClient);
    this.applications = new ApplicationService(this.apiClient);
  }

  setTokens(accessToken, refreshToken) {
    this.apiClient.setTokens(accessToken, refreshToken);
  }

  clearTokens() {
    this.apiClient.clearTokens();
  }
}

const apiBaseURL = import.meta.env.VITE_API_URL;
const apiService = new ApiService(apiBaseURL);

export default apiService;