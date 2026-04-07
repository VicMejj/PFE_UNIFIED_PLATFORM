import { laravelApi, unwrapItems, unwrapResponse } from '@/api/http'

export interface NotificationItem {
  id: string | number
  type: 'info' | 'success' | 'warning' | 'destructive' | string
  title?: string
  message: string
  read?: boolean
  created_at: string
  action?: string
}

export interface EmployeeStatistics {
  employee_id: number
  name: string
  email: string
  is_active: boolean
  tenure_years: number
  documents_count: number
  awards_count: number
  leaves_count: number
  insurance_enrollments_count: number
}

export interface DashboardHomeResponse {
  title: string
  statistics: {
    total_employees: number
    active_employees: number
    on_leave_employees: number
    new_employees_this_month: number
  }
  modules: Array<{
    name: string
    icon: string
    description: string
  }>
}

export interface EventItem {
  id: number
  title?: string
  name?: string
  description?: string | null
  event_date?: string | null
  start_time?: string | null
  end_time?: string | null
  location?: string | null
  is_active?: boolean
}

export interface LeaveTypeItem {
  id: number
  name?: string
  leave_code?: string | null
  description?: string | null
  maximum_days?: number | null
  is_active?: boolean
}

export interface ContractItem {
  id: number
  title?: string | null
  status?: string | null
  signed_at?: string | null
  updated_at?: string | null
  employee_id?: number
  [key: string]: any
}

export const platformApi = {
  async getNotifications(): Promise<NotificationItem[]> {
    const response = await laravelApi.get('/notifications')
    return unwrapItems<NotificationItem>(unwrapResponse(response))
  },

  async markNotificationRead(notificationId: string | number) {
    const response = await laravelApi.post(`/notifications/${notificationId}/mark-read`)
    return unwrapResponse(response)
  },

  async markAllNotificationsRead() {
    const response = await laravelApi.post('/notifications/mark-all-read')
    return unwrapResponse(response)
  },

  async getUnreadNotificationCount() {
    const response = await laravelApi.get('/notifications/unread-count')
    return unwrapResponse<{ unread_count: number }>(response)
  },

  async getHomeDashboard(): Promise<DashboardHomeResponse> {
    const response = await laravelApi.get('/web/homepage')
    return unwrapResponse<DashboardHomeResponse>(response)
  },

  async getSettings() {
    const response = await laravelApi.get('/core/settings')
    return unwrapResponse(response)
  },

  async getEmployees(search = '') {
    const response = await laravelApi.get('/employees', { params: search ? { search } : undefined })
    return unwrapResponse(response)
  },

  async getUsers(search = '') {
    const response = await laravelApi.get('/core/users', { params: search ? { search } : undefined })
    return unwrapResponse(response)
  },

  async createEmployee(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees', payload)
    return unwrapResponse(response)
  },

  async getEmployeeTurnoverPrediction(id: number) {
    const response = await laravelApi.get(`/employees/${id}/turnover-prediction`)
    return unwrapResponse(response)
  },

  async getEmployeeStatistics(id: number): Promise<EmployeeStatistics> {
    const response = await laravelApi.get(`/employees/${id}/statistics`)
    return unwrapResponse<EmployeeStatistics>(response)
  },

  async getBenefitRecommendations(employeeId: number) {
    const response = await laravelApi.get(`/employees/${employeeId}/benefit-recommendations`)
    return unwrapResponse(response)
  },

  async updateEmployee(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/${id}`, payload)
    return unwrapResponse(response)
  },

  async getLeaves() {
    const response = await laravelApi.get('/leaves')
    return unwrapResponse(response)
  },

  async getLeaveTypes() {
    const response = await laravelApi.get('/leaves/types')
    return unwrapResponse<LeaveTypeItem[] | { data?: LeaveTypeItem[] }>(response)
  },

  async createLeave(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/leaves', payload)
    return unwrapResponse(response)
  },

  async previewLeaveRequest(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/leaves/request-insights', payload)
    return unwrapResponse(response)
  },

  async approveLeaveByManager(id: number) {
    const response = await laravelApi.post(`/leaves/${id}/approve-by-manager`)
    return unwrapResponse(response)
  },

  async approveLeaveByHr(id: number) {
    const response = await laravelApi.post(`/leaves/${id}/approve-by-hr`)
    return unwrapResponse(response)
  },

  async rejectLeave(id: number, reason = '') {
    const response = await laravelApi.post(`/leaves/${id}/reject`, { reason })
    return unwrapResponse(response)
  },

  async getOptimalLeaveDates(employeeId?: number) {
    const response = await laravelApi.get('/leaves/optimal-dates', {
      params: employeeId ? { employee_id: employeeId } : undefined
    })
    return unwrapResponse(response)
  },

  async getPaySlips() {
    const response = await laravelApi.get('/payroll/pay-slips')
    return unwrapResponse(response)
  },

  async getEvents(search = '') {
    const response = await laravelApi.get('/communication/events', { params: search ? { search } : undefined })
    return unwrapResponse<{ data?: EventItem[] } | EventItem[]>(response)
  },

  async createPaySlip(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/payroll/pay-slips', payload)
    return unwrapResponse(response)
  },

  async generatePaySlip(id: number) {
    const response = await laravelApi.post(`/payroll/pay-slips/${id}/generate`)
    return unwrapResponse(response)
  },

  async sendPaySlip(id: number) {
    const response = await laravelApi.post(`/payroll/pay-slips/${id}/send`)
    return unwrapResponse(response)
  },

  async getContracts(): Promise<ContractItem[]> {
    const response = await laravelApi.get('/contracts')
    return unwrapItems<ContractItem>(unwrapResponse(response))
  },

  async createContract(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/contracts', payload)
    return unwrapResponse(response)
  },

  async getContractTypes() {
    const response = await laravelApi.get('/contracts/types')
    return unwrapResponse(response)
  },

  async createContractType(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/contracts/types', payload)
    return unwrapResponse(response)
  },

  async updateContractType(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/contracts/types/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteContractType(id: number) {
    const response = await laravelApi.delete(`/contracts/types/${id}`)
    return unwrapResponse(response)
  },

  async getContract(id: number) {
    const response = await laravelApi.get(`/contracts/${id}`)
    return unwrapResponse(response)
  },

  async updateContract(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/contracts/${id}`, payload)
    return unwrapResponse(response)
  },

  async assignContract(id: number, payload: Record<string, unknown> = {}) {
    const response = await laravelApi.post(`/contracts/${id}/assign`, payload)
    return unwrapResponse(response)
  },

  async markContractViewed(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.post(`/contracts/${id}/view`, payload)
    return unwrapResponse(response)
  },

  async signContract(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/contracts/sign-with-token', payload)
    return unwrapResponse(response)
  },

  async rejectContract(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.post(`/contracts/${id}/reject`, payload)
    return unwrapResponse(response)
  },

  async downloadContract(id: number, params: Record<string, unknown> = {}) {
    const response = await laravelApi.get(`/contracts/${id}/download`, {
      responseType: 'blob',
      params,
    })
    return response.data as Blob
  },

  async getContractAudit(id: number) {
    const response = await laravelApi.get(`/contracts/${id}/audit`)
    return unwrapResponse(response)
  },

  async deleteContract(id: number) {
    const response = await laravelApi.delete(`/contracts/${id}`)
    return unwrapResponse(response)
  },

  async getContractAttachments() {
    const response = await laravelApi.get('/contracts/attachments')
    return unwrapItems(unwrapResponse(response))
  },

  async getContractAttachment(id: number) {
    const response = await laravelApi.get(`/contracts/attachments/${id}`)
    return unwrapResponse(response)
  },

  async uploadContractAttachment(data: FormData) {
    const response = await laravelApi.post('/contracts/attachments', data)
    return unwrapResponse(response)
  },

  async updateContractAttachment(id: number, data: object) {
    const response = await laravelApi.put(`/contracts/attachments/${id}`, data)
    return unwrapResponse(response)
  },

  async deleteContractAttachment(id: number) {
    const response = await laravelApi.delete(`/contracts/attachments/${id}`)
    return unwrapResponse(response)
  },

  async getContractComments() {
    const response = await laravelApi.get('/contracts/comments')
    return unwrapItems(unwrapResponse(response))
  },

  async getContractComment(id: number) {
    const response = await laravelApi.get(`/contracts/comments/${id}`)
    return unwrapResponse(response)
  },

  async addContractComment(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/contracts/comments', payload)
    return unwrapResponse(response)
  },

  async updateContractComment(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/contracts/comments/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteContractComment(id: number) {
    const response = await laravelApi.delete(`/contracts/comments/${id}`)
    return unwrapResponse(response)
  },

  async getContractNotes() {
    const response = await laravelApi.get('/contracts/notes')
    return unwrapItems(unwrapResponse(response))
  },

  async getContractNote(id: number) {
    const response = await laravelApi.get(`/contracts/notes/${id}`)
    return unwrapResponse(response)
  },

  async addContractNote(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/contracts/notes', payload)
    return unwrapResponse(response)
  },

  async updateContractNote(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/contracts/notes/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteContractNote(id: number) {
    const response = await laravelApi.delete(`/contracts/notes/${id}`)
    return unwrapResponse(response)
  },

  async getBranches() {
    const response = await laravelApi.get('/organization/branches')
    return unwrapResponse(response)
  },

  async getDepartments() {
    const response = await laravelApi.get('/organization/departments')
    return unwrapResponse(response)
  },

  async getDesignations() {
    const response = await laravelApi.get('/organization/designations')
    return unwrapResponse(response)
  },

  async getAllowanceOptions() {
    const response = await laravelApi.get('/payroll/allowance-options')
    return unwrapResponse(response)
  },

  async getMyAllowances() {
    const response = await laravelApi.get('/payroll/allowances')
    return unwrapResponse(response)
  },

  async createAllowanceOption(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/payroll/allowance-options', payload)
    return unwrapResponse(response)
  },

  async getEmployeeAllowances(employeeId: number) {
    const response = await laravelApi.get(`/payroll/allowances?employee_id=${employeeId}`)
    return unwrapResponse(response)
  },

  async assignAllowance(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/payroll/allowances', payload)
    return unwrapResponse(response)
  },

  async removeAllowance(allowanceId: number) {
    const response = await laravelApi.delete(`/payroll/allowances/${allowanceId}`)
    return unwrapResponse(response)
  },

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

  async updateUserStatus(id: number, status: 'active' | 'suspended' | 'banned') {
    const response = await laravelApi.post(`/core/users/${id}/update-status`, { status })
    return unwrapResponse(response)
  }
}
