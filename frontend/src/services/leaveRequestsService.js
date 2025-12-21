import apiClient from './api'

export const leaveRequestsService = {
  async list(params = {}) {
    const { page = 1, per_page = 10, status = '', type = '' } = params
    const response = await apiClient.get('/api/leave-requests', { params: { page, per_page, status, type } })
    return response.data
  },

  async approve(id) {
    const response = await apiClient.post(`/api/leave-requests/${id}/status`, { status: 'Đã duyệt' })
    return response.data
  },

  async reject(id) {
    const response = await apiClient.post(`/api/leave-requests/${id}/status`, { status: 'Từ chối' })
    return response.data
  },

  async remove(id) {
    const response = await apiClient.delete(`/api/leave-requests/${id}`)
    return response.data
  },

  async myList(params = {}) {
    const { page = 1, per_page = 10 } = params
    const response = await apiClient.get('/api/my-leaves', { params: { page, per_page } })
    return response.data
  },

  async create(payload) {
    const response = await apiClient.post('/api/my-leaves', payload)
    return response.data
  },
}
