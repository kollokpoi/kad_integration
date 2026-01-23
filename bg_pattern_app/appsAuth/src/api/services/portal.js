export class PortalService {
  constructor(apiClient) {
    this.api = apiClient;
  }

  async getById(portalId) {
    const response = await this.api.get(`/api/portals/${portalId}`);
    return response;
  }

  async getByDomain(domain) {
    const response = await this.api.get(`/api/portals/by-domain/${domain}`);
    return response;
  }

  async update(portalId, data) {
    const response = await this.api.put(`/api/portals/${portalId}`, data);
    return response;
  }
}