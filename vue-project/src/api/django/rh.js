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

export const getEmployees = async () => {
  const { data } = await api.get('/gestion_rh/employees')
  return data
}

export const getEmployee = async (id) => {
  const { data } = await api.get(`/gestion_rh/employees/${id}`)
  return data
}

export const createEmployee = async (employeeData) => {
  const { data } = await api.post('/gestion_rh/employees', employeeData)
  return data
}

export const updateEmployee = async (id, employeeData) => {
  const { data } = await api.put(`/gestion_rh/employees/${id}`, employeeData)
  return data
}

export const deleteEmployee = async (id) => {
  const { data } = await api.delete(`/gestion_rh/employees/${id}`)
  return data
}

// Contracts
export const getContracts = async () => {
  const { data } = await api.get('/gestion_rh/contracts')
  return data
}

export const getContract = async (id) => {
  const { data } = await api.get(`/gestion_rh/contracts/${id}`)
  return data
}

export const createContract = async (contractData) => {
  const { data } = await api.post('/gestion_rh/contracts', contractData)
  return data
}

export const updateContract = async (id, contractData) => {
  const { data } = await api.put(`/gestion_rh/contracts/${id}`, contractData)
  return data
}
