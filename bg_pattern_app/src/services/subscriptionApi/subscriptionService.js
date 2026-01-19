class SubscriptionService {
  constructor(apiClient) {
    this.api = apiClient;
  }

  async getCurrent() {
    const response = await this.api.get('/api/subscription/');
    return response;
  }

  async getById(subscriptionId) {
    const response = await this.api.get(`/api/subscriptions/${subscriptionId}`);
    return response;
  }

  async getAll(params = {}) {
    const query = new URLSearchParams(params).toString();
    const endpoint = query ? `/api/subscriptions?${query}` : '/api/subscriptions';
    const response = await this.api.get(endpoint);
    return response;
  }

  async create(data) {
    const response = await this.api.post('/api/subscriptions', data);
    return response;
  }

  async update(subscriptionId, data) {
    const response = await this.api.put(`/api/subscriptions/${subscriptionId}`, data);
    return response;
  }

  async updateMetadata(subscriptionId, updates) {
    const response = await this.api.patch(`/api/subscriptions/${subscriptionId}/metadata`, { updates });
    return response;
  }

  async extend(subscriptionId, data) {
    const response = await this.api.post(`/api/subscriptions/${subscriptionId}/extend`, data);
    return response;
  }

  async cancel(subscriptionId) {
    const response = await this.api.post(`/api/subscriptions/${subscriptionId}/cancel`);
    return response;
  }

  async activate(subscriptionId) {
    const response = await this.api.post(`/api/subscriptions/${subscriptionId}/activate`);
    return response;
  }

  async suspend(subscriptionId) {
    const response = await this.api.post(`/api/subscriptions/${subscriptionId}/suspend`);
    return response;
  }

  async updateNotes(subscriptionId, notes) {
    const response = await this.api.patch(`/api/subscriptions/${subscriptionId}/notes`, { notes });
    return response;
  }

  async getForPortal(portalId) {
    const response = await this.api.get(`/api/portals/${portalId}/subscriptions`);
    return response;
  }

  async getForApp(appId) {
    const response = await this.api.get(`/api/applications/${appId}/subscriptions`);
    return response;
  }
}

export { SubscriptionService };