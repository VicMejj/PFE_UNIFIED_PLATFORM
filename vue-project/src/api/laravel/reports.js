import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_LARAVEL_API_URL
})

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('laravel_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export const generatePayrollReport = async (filters) => {
  const { data } = await api.post('/reports/payroll', filters)
  return data
}

export const generateSocialReport = async (filters) => {
  const { data } = await api.post('/reports/social', filters)
  return data
}

export const getReports = async () => {
  const { data } = await api.get('/reports')
  return data
}

export const exportReport = async (reportId, format) => {
  return api.get(`/reports/${reportId}/export?format=${format}`, {
    responseType: 'blob'
  })
}
