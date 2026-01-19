class PortalService {
  constructor(apiClient) {
    this.api = apiClient;
  }

  async getAll(params = {}) {
    const query = new URLSearchParams(params).toString();
    const endpoint = query ? `/api/portals?${query}` : '/api/portals';
    const response = await this.api.get(endpoint);
    return response;
  }

  async getById(portalId) {
    const response = await this.api.get(`/api/portals/${portalId}`);
    return response;
  }

  async getByDomain(domain) {
    const response = await this.api.get(`/api/portals/by-domain/${domain}`);
    return response;
  }

  async create(data) {
    const response = await this.api.post('/api/portals', data);
    return response;
  }

  async update(portalId, data) {
    const response = await this.api.put(`/api/portals/${portalId}`, data);
    return response;
  }

  async delete(portalId) {
    const response = await this.api.delete(`/api/portals/${portalId}`);
    return response;
  }

  async toggleActive(portalId) {
    const response = await this.api.post(`/api/portals/${portalId}/toggle-active`);
    return response;
  }

  async updateB24Tokens(portalId, accessToken, refreshToken) {
    const response = await this.api.patch(`/api/portals/${portalId}/b24-tokens`, {
      b24_access_token: accessToken,
      b24_refresh_token: refreshToken
    });
    return response;
  }

  async getStatistics(portalId) {
    const response = await this.api.get(`/api/portals/${portalId}/statistics`);
    return response;
  }
}

export { PortalService };