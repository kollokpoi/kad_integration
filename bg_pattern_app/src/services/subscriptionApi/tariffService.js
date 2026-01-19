
class TariffService {
  constructor(apiClient) {
    this.api = apiClient;
  }

  async getById(tariffId) {
    const response = await this.api.get(`/api/tariffs/${tariffId}`);
    return response;
  }
    async getCurrent() {
    const response = await this.api.get(`/api/tariffs/`);
    return response;
  }

  async update(tariffId, data) {
    const response = await this.api.put(`/api/tariffs/${tariffId}`, data);
    return response;
  }

  async getForApp(appId) {
    const response = await this.api.get(`/api/application/${appId}/tariffs`);
    return response;
  }

  async getActiveForApp(appId) {
    const response = await this.api.get(`/api/applications/${appId}/tariffs/active`);
    return response;
  }

  async getDefaultForApp(appId) {
    const response = await this.api.get(`/api/applications/${appId}/tariffs/default`);
    return response;
  }

  async toggleActive(tariffId) {
    const response = await this.api.post(`/api/tariffs/${tariffId}/toggle-active`);
    return response;
  }
}

export { TariffService };