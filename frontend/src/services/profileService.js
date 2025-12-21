import apiClient from './api'

export const profileService = {
  async getProfile() {
    const response = await apiClient.get('/api/profile')
    return response.data.data
  },

  async updateProfile(payload) {
    const response = await apiClient.put('/api/profile', payload)
    return response.data
  },

  async changePassword(payload) {
    const response = await apiClient.post('/api/profile/change-password', payload)
    return response.data
  }
}
