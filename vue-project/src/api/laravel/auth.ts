import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_LARAVEL_API_URL + '/v1',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('laravel_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// Handle response errors
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('laravel_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default {
  async login(email: string, password: string) {
    const response = await api.post('/login', {
      email,
      password
    })
    if (response.data.token) {
      localStorage.setItem('laravel_token', response.data.token)
    }
    return response.data
  },

  async register(email: string, password: string, firstName: string, lastName: string) {
    const response = await api.post('/register', {
      email,
      password,
      firstName,
      lastName
    })
    if (response.data.token) {
      localStorage.setItem('laravel_token', response.data.token)
    }
    return response.data
  },

  async logout() {
    localStorage.removeItem('laravel_token')
    return api.post('/logout')
  },

  async me() {
    const response = await api.get('/me')
    return response.data
  }
}