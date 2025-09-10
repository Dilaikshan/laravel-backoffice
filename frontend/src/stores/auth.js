import { defineStore } from 'pinia'
import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

// Configure Axios to send cookies and with credentials for Sanctum
axios.defaults.withCredentials = true
axios.defaults.baseURL = API_BASE_URL

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('authToken') || null,
    loading: false,
    error: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
  },
  actions: {
    async getToken() {
      try {
        await axios.get('/sanctum/csrf-cookie')
        return true
      } catch (error) {
        console.error('CSRF cookie error:', error)
        this.error = 'Failed to get CSRF token.'
        return false
      }
    },
    async login(credentials) {
      this.loading = true
      this.error = null
      try {
        await this.getToken() // Get CSRF token first

        const response = await axios.post('/login', credentials)
        this.user = response.data.user
        this.token = response.data.token
        localStorage.setItem('authToken', this.token)
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
        return true
      } catch (error) {
        if (error.response && error.response.data && error.response.data.errors) {
          this.error = Object.values(error.response.data.errors).flat().join(' ')
        } else if (error.response && error.response.data && error.response.data.message) {
          this.error = error.response.data.message
        } else {
          this.error = 'Login failed. Please check your credentials.'
        }
        this.user = null
        this.token = null
        localStorage.removeItem('authToken')
        delete axios.defaults.headers.common['Authorization']
        return false
      } finally {
        this.loading = false
      }
    },
    async logout() {
      this.loading = true
      this.error = null
      try {
        await axios.post('/logout')
        this.user = null
        this.token = null
        localStorage.removeItem('authToken')
        delete axios.defaults.headers.common['Authorization']
        return true
      } catch (error) {
        console.error('Logout error:', error)
        this.error = 'Logout failed.'
        return false
      } finally {
        this.loading = false
      }
    },
    async fetchUser() {
      if (!this.token) {
        this.user = null
        return
      }

      this.loading = true
      this.error = null
      axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
      try {
        const response = await axios.get('/user')
        this.user = response.data.user
      } catch (error) {
        console.error('Fetch user error:', error)
        this.user = null
        this.token = null
        localStorage.removeItem('authToken')
        delete axios.defaults.headers.common['Authorization']
      } finally {
        this.loading = false
      }
    }
  }
})

// Set default Authorization header if token exists on app load
const initialToken = localStorage.getItem('authToken');
if (initialToken) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${initialToken}`;
}