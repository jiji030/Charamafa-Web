import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    getUser: (state) => state.user
  },

  actions: {
    async login(credentials) {
      const config = useRuntimeConfig()
      try {
        const response = await $fetch(`${config.public.apiBase}/login`, {
          method: 'POST',
          body: credentials
        })
        
        this.user = response.user
        this.token = response.token
        
        // Store in localStorage
        if (process.client) {
          localStorage.setItem('token', response.token)
          localStorage.setItem('user', JSON.stringify(response.user))
        }
        
        return response
      } catch (error) {
        throw error
      }
    },

    async logout() {
      const config = useRuntimeConfig()
      try {
        await $fetch(`${config.public.apiBase}/logout`, {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${this.token}`
          }
        })
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        this.user = null
        this.token = null
        
        if (process.client) {
          localStorage.removeItem('token')
          localStorage.removeItem('user')
        }
      }
    },

    initAuth() {
      if (process.client) {
        try {
          const token = localStorage.getItem('token')
          const user = localStorage.getItem('user')
          
          if (token && user) {
            this.token = token
            this.user = JSON.parse(user)
            // console.log('Auth initialized:', this.user)
          }
        } catch (error) {
          console.error('Error initializing auth:', error)
          // Clear invalid data
          localStorage.removeItem('token')
          localStorage.removeItem('user')
        }
      }
    }
  }
})