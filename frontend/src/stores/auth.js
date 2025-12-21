import { defineStore } from 'pinia';
import authService from '../services/authService';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    isAuthenticated: !!localStorage.getItem('token'),
  }),

  getters: {
    currentUser: (state) => state.user,
    isAdmin: (state) => state.user?.role === 'admin',
    isEmployee: (state) => state.user?.role === 'employee',
  },

  actions: {
    async login(credentials) {
      try {
        const data = await authService.login(credentials);
        this.token = data.token;
        this.user = data.user;
        this.isAuthenticated = true;
        
        localStorage.setItem('token', data.token);
        localStorage.setItem('user', JSON.stringify(data.user));
        
        return data;
      } catch (error) {
        throw error;
      }
    },

    async logout() {
      try {
        await authService.logout();
        this.token = null;
        this.user = null;
        this.isAuthenticated = false;
        
        localStorage.removeItem('token');
        localStorage.removeItem('user');
      } catch (error) {
        // Clear local state even if API call fails
        this.token = null;
        this.user = null;
        this.isAuthenticated = false;
        
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        throw error;
      }
    },

    async fetchUser() {
      try {
        const data = await authService.getUser();
        this.user = data;
        localStorage.setItem('user', JSON.stringify(data));
        return data;
      } catch (error) {
        this.logout();
        throw error;
      }
    },

    setUser(user) {
      this.user = user;
      localStorage.setItem('user', JSON.stringify(user));
    }
  }
});
