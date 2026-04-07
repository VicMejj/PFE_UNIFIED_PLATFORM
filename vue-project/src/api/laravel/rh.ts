import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_LARAVEL_API_URL + '/v1',
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' }
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('laravel_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (r) => r,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('laravel_token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// ── Employees ─────────────────────────────────────────
export const getEmployees = () => api.get('/employees').then(r => r.data)
export const getEmployee = (id: number) => api.get(`/employees/${id}`).then(r => r.data)
export const createEmployee = (data: object) => api.post('/employees', data).then(r => r.data)
export const updateEmployee = (id: number, data: object) => api.put(`/employees/${id}`, data).then(r => r.data)
export const deleteEmployee = (id: number) => api.delete(`/employees/${id}`).then(r => r.data)

// ── Contracts ─────────────────────────────────────────
export const getContracts = () => api.get('/contracts').then(r => r.data)
export const getContract = (id: number) => api.get(`/contracts/${id}`).then(r => r.data)
export const createContract = (data: object) => api.post('/contracts', data).then(r => r.data)
export const updateContract = (id: number, data: object) => api.put(`/contracts/${id}`, data).then(r => r.data)
export const deleteContract = (id: number) => api.delete(`/contracts/${id}`).then(r => r.data)

// ── Payslips ──────────────────────────────────────────
export const getPayslips = () => api.get('/payslips').then(r => r.data)
export const getPayslip = (id: number) => api.get(`/payslips/${id}`).then(r => r.data)

// ── Leaves ────────────────────────────────────────────
export const getLeaves = () => api.get('/leaves').then(r => r.data)
export const createLeave = (data: object) => api.post('/leaves', data).then(r => r.data)
export const approveLeave = (id: number) => api.put(`/leaves/${id}/approve`, {}).then(r => r.data)
export const rejectLeave = (id: number) => api.put(`/leaves/${id}/reject`, {}).then(r => r.data)

// ── Dashboard stats ───────────────────────────────────
export const getDashboardStats = () => api.get('/dashboard/stats').then(r => r.data)
