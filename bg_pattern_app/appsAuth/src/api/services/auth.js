export class AuthService {
  constructor(apiClient) {
    this.api = apiClient;
  }

  async login(domain, applicationId) {
    const response = await this.api.post("/api/auth/login", {
      domain,
      applicationId,
    });
    return response;
  }

  async register(domain, applicationId) {
    const response = await this.api.post("/api/auth/register", {
      domain,
      applicationId,
    });
    return response;
  }

  async refresh(refreshToken) {
    const response = await this.api.post("/api/auth/refresh", {
      refreshToken,
    });
    return response;
  }

  async logout(refreshToken) {
    const response = await this.api.post("/api/auth/logout", {
      refreshToken,
    });
    return response;
  }

  async validateToken() {
    const response = await this.api.get("/api/auth/validate");
    return response;
  }
}