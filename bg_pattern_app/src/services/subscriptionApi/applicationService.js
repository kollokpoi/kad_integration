class ApplicationService {
  constructor(apiClient) {
    this.api = apiClient;
  }

  async get(id) {
    const response = await this.api.get(`/api/application/${id}`);
    return response;
  }
}

export { ApplicationService };
