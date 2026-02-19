import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_DJANGO_API_URL,
  headers: {
    'Content-Type': 'application/json'
  }
})

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('django_token')
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
      localStorage.removeItem('django_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default {
  async login(email, password) {
    const response = await api.post('/auth/login', {
      email,
      password
    })
    if (response.data.token) {
      localStorage.setItem('django_token', response.data.token)
    }
    return response.data
  },

  async register(email, password, firstName, lastName) {
    const response = await api.post('/auth/register', {
      email,
      password,
      first_name: firstName,
      last_name: lastName
    })
    if (response.data.token) {
      localStorage.setItem('django_token', response.data.token)
    }
    return response.data
  },

  async logout() {
    localStorage.removeItem('django_token')
    return api.post('/auth/logout')
  },

  async me() {
    const response = await api.get('/auth/me')
    return response.data
  }
}
