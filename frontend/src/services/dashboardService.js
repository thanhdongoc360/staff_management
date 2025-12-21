import apiClient from './api'

export const dashboardService = {
  // Admin Dashboard
  async getAdminStats() {
    try {
      const response = await apiClient.get('/api/admin/dashboard/stats')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getRecentEmployees() {
    try {
      const response = await apiClient.get('/api/admin/dashboard/recent-employees')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getPendingLeaves() {
    try {
      const response = await apiClient.get('/api/admin/dashboard/pending-leaves')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getEmployeeStats() {
    try {
      const response = await apiClient.get('/api/admin/dashboard/employee-stats')
      return response.data
    } catch (error) {
      throw error
    }
  },

  // Employee Dashboard
  async getEmployeeStats() {
    try {
      const response = await apiClient.get('/api/employee/dashboard/stats')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getMyLeaves() {
    try {
      const response = await apiClient.get('/api/employee/dashboard/my-leaves')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getLeaveStats() {
    try {
      const response = await apiClient.get('/api/employee/dashboard/leave-stats')
      return response.data
    } catch (error) {
      throw error
    }
  },
}

export const notificationService = {
  async getNotifications(page = 1) {
    try {
      const response = await apiClient.get(`/api/notifications?page=${page}`)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getRecentNotifications() {
    try {
      const response = await apiClient.get('/api/notifications/recent')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getUnreadCount() {
    try {
      const response = await apiClient.get('/api/notifications/unread-count')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async markAsRead(notificationId) {
    try {
      const response = await apiClient.post(`/api/notifications/${notificationId}/mark-as-read`)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async markAllAsRead() {
    try {
      const response = await apiClient.post('/api/notifications/mark-all-as-read')
      return response.data
    } catch (error) {
      throw error
    }
  },
}

export const scheduleService = {
  async getSchedules(page = 1) {
    try {
      const response = await apiClient.get(`/api/schedules?page=${page}`)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getTodaySchedules() {
    try {
      const response = await apiClient.get('/api/schedules/today')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getUpcomingSchedules() {
    try {
      const response = await apiClient.get('/api/schedules/upcoming')
      return response.data
    } catch (error) {
      throw error
    }
  },

  async getScheduleById(id) {
    try {
      const response = await apiClient.get(`/api/schedules/${id}`)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async createSchedule(data) {
    try {
      const response = await apiClient.post('/api/schedules', data)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async updateSchedule(id, data) {
    try {
      const response = await apiClient.put(`/api/schedules/${id}`, data)
      return response.data
    } catch (error) {
      throw error
    }
  },

  async deleteSchedule(id) {
    try {
      const response = await apiClient.delete(`/api/schedules/${id}`)
      return response.data
    } catch (error) {
      throw error
    }
  },
}
