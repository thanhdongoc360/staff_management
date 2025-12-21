import apiClient from './api'

export const salaryService = {
  async list(params = {}) {
    const { page = 1, per_page = 10, month = '', year = '', employee_id = '' } = params
    const response = await apiClient.get('/api/salaries', {
      params: { page, per_page, month, year, employee_id }
    })
    return response.data
  },

  async create(payload) {
    const response = await apiClient.post('/api/salaries', payload)
    return response.data
  },

  async update(id, payload) {
    const response = await apiClient.put(`/api/salaries/${id}`, payload)
    return response.data
  },

  async remove(id) {
    const response = await apiClient.delete(`/api/salaries/${id}`)
    return response.data
  },

  async mySalaries(params = {}) {
    const { page = 1, per_page = 10, month = '', year = '' } = params
    const response = await apiClient.get('/api/my-salaries', {
      params: { page, per_page, month, year }
    })
    return response.data
  }
}
