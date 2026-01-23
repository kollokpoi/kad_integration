export class TariffService {
  constructor(apiClient) {
    this.api = apiClient;
  }

  async getCurrent() {
    const response = await this.api.get('/api/tariffs/');
    return response;
  }

  async getById(tariffId) {
    const response = await this.api.get(`/api/tariffs/${tariffId}`);
    return response;
  }

  async getForApp(appId) {
    const response = await this.api.get(`/api/application/${appId}/tariffs`);
    return response;
  }
}