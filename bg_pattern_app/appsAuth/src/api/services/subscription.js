export class SubscriptionService {
  constructor(apiClient) {
    this.api = apiClient;
  }

  async getCurrent() {
    const response = await this.api.get('/api/subscription/');
    return response;
  }

  async getById(subscriptionId) {
    const response = await this.api.get(`/api/subscription/${subscriptionId}`);
    return response;
  }

  async checkActionAvalible(action){
    const response = await this.api.post('/api/subscription/checkavalible',action)
    return response;
  }
}