import apiClient from './api'

const employeesService = {
  async getEmployees(params = {}) {
    const { page = 1, per_page = 10, status = '', search = '' } = params
    const response = await apiClient.get('/api/employees', {
      params: { page, per_page, status, search }
    })
    return response.data
  },

  async getEmployee(id) {
    const response = await apiClient.get(`/api/employees/${id}`)
    return response.data
  },

  async createEmployee(payload) {
    const response = await apiClient.post('/api/employees', payload)
    return response.data
  },

  async updateEmployee(id, payload) {
    const response = await apiClient.put(`/api/employees/${id}`, payload)
    return response.data
  },

  async deleteEmployee(id) {
    const response = await apiClient.delete(`/api/employees/${id}`)
    return response.data
  }
}

export default employeesService
