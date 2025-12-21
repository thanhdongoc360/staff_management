import apiClient from './api';

const authService = {
  // Login
  async login(credentials) {
    const response = await apiClient.post('/api/login', credentials);
    return response.data;
  },

  // Logout
  async logout() {
    const response = await apiClient.post('/api/logout');
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    return response.data;
  },

  // Get current user
  async getUser() {
    const response = await apiClient.get('/api/user');
    return response.data;
  },

  // Register (if needed)
  async register(userData) {
    await this.getCsrfCookie();
    const response = await apiClient.post('/api/register', userData);
    return response.data;
  }
};

export default authService;
