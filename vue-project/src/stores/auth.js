import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import laravelAuthApi from '@/api/laravel/auth'
import djangoAuthApi from '@/api/django/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const laravelToken = ref(localStorage.getItem('laravel_token'))
  const djangoToken = ref(localStorage.getItem('django_token'))
  const isAuthenticated = computed(() => !!(laravelToken.value || djangoToken.value))

  async function loginLaravel(email, password) {
    try {
      const response = await laravelAuthApi.login(email, password)
      laravelToken.value = response.token
      user.value = response.user
      localStorage.setItem('laravel_token', response.token)
      return response
    } catch (error) {
      console.error('Laravel login failed:', error)
      throw error
    }
  }

  async function loginDjango(email, password) {
    try {
      const response = await djangoAuthApi.login(email, password)
      djangoToken.value = response.token
      user.value = response.user
      localStorage.setItem('django_token', response.token)
      return response
    } catch (error) {
      console.error('Django login failed:', error)
      throw error
    }
  }

  async function registerLaravel(email, password, firstName, lastName) {
    try {
      const response = await laravelAuthApi.register(email, password, firstName, lastName)
      laravelToken.value = response.token
      user.value = response.user
      localStorage.setItem('laravel_token', response.token)
      return response
    } catch (error) {
      console.error('Laravel register failed:', error)
      throw error
    }
  }

  async function registerDjango(email, password, firstName, lastName) {
    try {
      const response = await djangoAuthApi.register(email, password, firstName, lastName)
      djangoToken.value = response.token
      user.value = response.user
      localStorage.setItem('django_token', response.token)
      return response
    } catch (error) {
      console.error('Django register failed:', error)
      throw error
    }
  }

  async function logout() {
    try {
      if (laravelToken.value) {
        await laravelAuthApi.logout()
      }
      if (djangoToken.value) {
        await djangoAuthApi.logout()
      }
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      laravelToken.value = null
      djangoToken.value = null
      user.value = null
      localStorage.removeItem('laravel_token')
      localStorage.removeItem('django_token')
    }
  }

  async function fetchUser() {
    try {
      if (laravelToken.value) {
        const response = await laravelAuthApi.me()
        user.value = response.user
      } else if (djangoToken.value) {
        const response = await djangoAuthApi.me()
        user.value = response.user
      }
    } catch (error) {
      console.error('Fetch user failed:', error)
      logout()
    }
  }

  return {
    user,
    laravelToken,
    djangoToken,
    isAuthenticated,
    loginLaravel,
    loginDjango,
    registerLaravel,
    registerDjango,
    logout,
    fetchUser
  }
})
