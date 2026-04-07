import { laravelApi } from '@/api/http'
import { unwrapResponse } from '@/api/http'

export const organizationApi = {
  // ===== BRANCHES =====
  async getBranches() {
    const response = await laravelApi.get('/organization/branches')
    return unwrapResponse(response)
  },

  async createBranch(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/organization/branches', payload)
    return unwrapResponse(response)
  },

  async getBranch(id: number) {
    const response = await laravelApi.get(`/organization/branches/${id}`)
    return unwrapResponse(response)
  },

  async updateBranch(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/organization/branches/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteBranch(id: number) {
    const response = await laravelApi.delete(`/organization/branches/${id}`)
    return unwrapResponse(response)
  },

  // ===== DEPARTMENTS =====
  async getDepartments() {
    const response = await laravelApi.get('/organization/departments')
    return unwrapResponse(response)
  },

  async createDepartment(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/organization/departments', payload)
    return unwrapResponse(response)
  },

  async getDepartment(id: number) {
    const response = await laravelApi.get(`/organization/departments/${id}`)
    return unwrapResponse(response)
  },

  async updateDepartment(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/organization/departments/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteDepartment(id: number) {
    const response = await laravelApi.delete(`/organization/departments/${id}`)
    return unwrapResponse(response)
  },

  // ===== DESIGNATIONS =====
  async getDesignations() {
    const response = await laravelApi.get('/organization/designations')
    return unwrapResponse(response)
  },

  async createDesignation(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/organization/designations', payload)
    return unwrapResponse(response)
  },

  async getDesignation(id: number) {
    const response = await laravelApi.get(`/organization/designations/${id}`)
    return unwrapResponse(response)
  },

  async updateDesignation(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/organization/designations/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteDesignation(id: number) {
    const response = await laravelApi.delete(`/organization/designations/${id}`)
    return unwrapResponse(response)
  }
}
