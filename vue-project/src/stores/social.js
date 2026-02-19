import { defineStore } from 'pinia'
import { ref } from 'vue'
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

export const useSocialStore = defineStore('social', () => {
  const employees = ref([])
  const benefits = ref([])
  const loading = ref(false)
  const error = ref(null)

  async function fetchEmployees() {
    loading.value = true
    try {
      const { data } = await api.get('/gestion_sociale/employees')
      employees.value = Array.isArray(data) ? data : data.results || []
      error.value = null
    } catch (err) {
      error.value = err.message
      console.error('Error fetching social employees:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchBenefits(employeeId) {
    try {
      const { data } = await api.get(`/gestion_sociale/benefits/${employeeId}`)
      benefits.value = Array.isArray(data) ? data : data.results || []
      return benefits.value
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function getEmployeeBenefits(employeeId) {
    try {
      const { data } = await api.get(`/gestion_sociale/employees/${employeeId}/benefits`)
      return data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function generateSocialReport(employeeId) {
    try {
      const { data } = await api.get(`/gestion_sociale/reports/${employeeId}`)
      return data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  return {
    employees,
    benefits,
    loading,
    error,
    fetchEmployees,
    fetchBenefits,
    getEmployeeBenefits,
    generateSocialReport
  }
})
