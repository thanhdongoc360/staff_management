import apiClient from './api'

export const notificationsService = {
  async list(page = 1) {
    const response = await apiClient.get(`/api/notifications?page=${page}`)
    return response.data
  },

  async unreadCount() {
    const response = await apiClient.get('/api/notifications/unread-count')
    return response.data.unread_count
  },

  async markAsRead(id) {
    const response = await apiClient.post(`/api/notifications/${id}/mark-as-read`)
    return response.data
  },

  async markAllAsRead() {
    const response = await apiClient.post('/api/notifications/mark-all-as-read')
    return response.data
  },
}
