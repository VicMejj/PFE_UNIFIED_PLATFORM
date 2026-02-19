import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_DJANGO_API_URL
})

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('django_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export const generateReport = async (employeeId) => {
  const { data } = await api.get(`/ia_models/reports/${employeeId}`)
  return data
}

export const getAnalytics = async (filters) => {
  const { data } = await api.post('/ia_models/analytics', filters)
  return data
}

export const predictTrends = async (data) => {
  const { data: response } = await api.post('/ia_models/predict', data)
  return response
}
