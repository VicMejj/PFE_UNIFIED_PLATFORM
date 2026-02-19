import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
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

export const useAssuranceStore = defineStore('assurance', () => {
  const insurances = ref([])
  const policies = ref([])
  const claims = ref([])
  const loading = ref(false)
  const error = ref(null)

  const insuranceCount = computed(() => insurances.value.length)

  async function fetchInsurances() {
    loading.value = true
    try {
      const { data } = await api.get('/assurance/insurances')
      insurances.value = Array.isArray(data) ? data : data.results || []
      error.value = null
    } catch (err) {
      error.value = err.message
      console.error('Error fetching insurances:', err)
    } finally {
      loading.value = false
    }
  }

  async function fetchInsurance(id) {
    try {
      const { data } = await api.get(`/assurance/insurances/${id}`)
      return data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function createInsurance(insuranceData) {
    try {
      const { data } = await api.post('/assurance/insurances', insuranceData)
      insurances.value.push(data)
      return data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function updateInsurance(id, insuranceData) {
    try {
      const { data } = await api.put(`/assurance/insurances/${id}`, insuranceData)
      const index = insurances.value.findIndex(i => i.id === id)
      if (index !== -1) {
        insurances.value[index] = data
      }
      return data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function deleteInsurance(id) {
    try {
      await api.delete(`/assurance/insurances/${id}`)
      insurances.value = insurances.value.filter(i => i.id !== id)
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function fetchEmployeeInsurances(employeeId) {
    try {
      const { data } = await api.get(`/assurance/employees/${employeeId}/insurances`)
      return data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function fetchClaims() {
    try {
      const { data } = await api.get('/assurance/claims')
      claims.value = Array.isArray(data) ? data : data.results || []
      return claims.value
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  async function createClaim(claimData) {
    try {
      const { data } = await api.post('/assurance/claims', claimData)
      claims.value.push(data)
      return data
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  return {
    insurances,
    policies,
    claims,
    loading,
    error,
    insuranceCount,
    fetchInsurances,
    fetchInsurance,
    createInsurance,
    updateInsurance,
    deleteInsurance,
    fetchEmployeeInsurances,
    fetchClaims,
    createClaim
  }
})
