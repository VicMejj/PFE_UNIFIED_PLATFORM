import { laravelApi } from '@/api/http'
import { unwrapResponse } from '@/api/http'

export const coreApi = {
  // ===== USERS =====
  async getUsers() {
    const response = await laravelApi.get('/core/users')
    return unwrapResponse(response)
  },

  async createUser(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/core/users', payload)
    return unwrapResponse(response)
  },

  async getUser(id: number) {
    const response = await laravelApi.get(`/core/users/${id}`)
    return unwrapResponse(response)
  },

  async updateUser(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/core/users/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteUser(id: number) {
    const response = await laravelApi.delete(`/core/users/${id}`)
    return unwrapResponse(response)
  },

  // ===== USER ACTIONS =====
  async suspendUser(id: number) {
    const response = await laravelApi.post(`/core/users/${id}/suspend`)
    return unwrapResponse(response)
  },

  async banUser(id: number) {
    const response = await laravelApi.post(`/core/users/${id}/ban`)
    return unwrapResponse(response)
  },

  async activateUser(id: number) {
    const response = await laravelApi.post(`/core/users/${id}/activate`)
    return unwrapResponse(response)
  },

  async updateUserStatus(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.post(`/core/users/${id}/update-status`, payload)
    return unwrapResponse(response)
  },

  // ===== ROLES =====
  async getRoles() {
    const response = await laravelApi.get('/core/roles')
    return unwrapResponse(response)
  },

  async createRole(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/core/roles', payload)
    return unwrapResponse(response)
  },

  async getRole(id: number) {
    const response = await laravelApi.get(`/core/roles/${id}`)
    return unwrapResponse(response)
  },

  async updateRole(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/core/roles/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteRole(id: number) {
    const response = await laravelApi.delete(`/core/roles/${id}`)
    return unwrapResponse(response)
  },

  // ===== ROLE ASSIGNMENTS =====
  async assignRoleToUser(userId: number, payload: Record<string, unknown>) {
    const response = await laravelApi.post(`/core/users/${userId}/assign-role`, payload)
    return unwrapResponse(response)
  },

  async removeRoleFromUser(userId: number, payload: Record<string, unknown>) {
    const response = await laravelApi.post(`/core/users/${userId}/remove-role`, payload)
    return unwrapResponse(response)
  },

  async syncUserRoles(userId: number, payload: Record<string, unknown>) {
    const response = await laravelApi.post(`/core/users/${userId}/sync-roles`, payload)
    return unwrapResponse(response)
  },

  async getUserRoles(userId: number) {
    const response = await laravelApi.get(`/core/users/${userId}/roles`)
    return unwrapResponse(response)
  },

  async getUsersByRole(roleName: string) {
    const response = await laravelApi.get(`/core/users-by-role/${roleName}`)
    return unwrapResponse(response)
  },

  async getAllUsersWithRoles() {
    const response = await laravelApi.get('/core/users-with-roles')
    return unwrapResponse(response)
  },

  // ===== SETTINGS =====
  async getSettings() {
    const response = await laravelApi.get('/core/settings')
    return unwrapResponse(response)
  },

  async createSetting(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/core/settings', payload)
    return unwrapResponse(response)
  },

  async getSetting(id: number) {
    const response = await laravelApi.get(`/core/settings/${id}`)
    return unwrapResponse(response)
  },

  async updateSetting(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/core/settings/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteSetting(id: number) {
    const response = await laravelApi.delete(`/core/settings/${id}`)
    return unwrapResponse(response)
  },

  // ===== LANGUAGES =====
  async getLanguages() {
    const response = await laravelApi.get('/core/languages')
    return unwrapResponse(response)
  },

  async createLanguage(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/core/languages', payload)
    return unwrapResponse(response)
  },

  async getLanguage(id: number) {
    const response = await laravelApi.get(`/core/languages/${id}`)
    return unwrapResponse(response)
  },

  async updateLanguage(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/core/languages/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteLanguage(id: number) {
    const response = await laravelApi.delete(`/core/languages/${id}`)
    return unwrapResponse(response)
  },

  // ===== ASSETS =====
  async getAssets() {
    const response = await laravelApi.get('/core/assets')
    return unwrapResponse(response)
  },

  async createAsset(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/core/assets', payload)
    return unwrapResponse(response)
  },

  async getAsset(id: number) {
    const response = await laravelApi.get(`/core/assets/${id}`)
    return unwrapResponse(response)
  },

  async updateAsset(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/core/assets/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteAsset(id: number) {
    const response = await laravelApi.delete(`/core/assets/${id}`)
    return unwrapResponse(response)
  }
}
