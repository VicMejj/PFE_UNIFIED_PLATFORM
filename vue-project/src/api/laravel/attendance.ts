import { laravelApi } from '@/api/http'
import { unwrapResponse } from '@/api/http'

export const attendanceApi = {
  // ===== ATTENDANCE RECORDS =====
  async getAttendanceRecords() {
    const response = await laravelApi.get('/attendance/records')
    return unwrapResponse(response)
  },

  async createAttendanceRecord(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/attendance/records', payload)
    return unwrapResponse(response)
  },

  async getAttendanceRecord(id: number) {
    const response = await laravelApi.get(`/attendance/records/${id}`)
    return unwrapResponse(response)
  },

  async updateAttendanceRecord(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/attendance/records/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteAttendanceRecord(id: number) {
    const response = await laravelApi.delete(`/attendance/records/${id}`)
    return unwrapResponse(response)
  },

  async getAttendanceStatistics() {
    const response = await laravelApi.get('/attendance/statistics')
    return unwrapResponse(response)
  },

  // ===== TIMESHEETS =====
  async getTimesheets() {
    const response = await laravelApi.get('/attendance/timesheets')
    return unwrapResponse(response)
  },

  async createTimesheet(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/attendance/timesheets', payload)
    return unwrapResponse(response)
  },

  async getTimesheet(id: number) {
    const response = await laravelApi.get(`/attendance/timesheets/${id}`)
    return unwrapResponse(response)
  },

  async updateTimesheet(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/attendance/timesheets/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTimesheet(id: number) {
    const response = await laravelApi.delete(`/attendance/timesheets/${id}`)
    return unwrapResponse(response)
  },

  async generateTimesheetSummary(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/attendance/timesheets/summary', payload)
    return unwrapResponse(response)
  }
}
