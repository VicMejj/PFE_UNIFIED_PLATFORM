import { laravelApi } from '@/api/http'
import { unwrapResponse } from '@/api/http'

export const communicationApi = {
  // ===== EVENTS =====
  async getEvents() {
    const response = await laravelApi.get('/communication/events')
    return unwrapResponse(response)
  },

  async createEvent(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/events', payload)
    return unwrapResponse(response)
  },

  async getEvent(id: number) {
    const response = await laravelApi.get(`/communication/events/${id}`)
    return unwrapResponse(response)
  },

  async updateEvent(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/events/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteEvent(id: number) {
    const response = await laravelApi.delete(`/communication/events/${id}`)
    return unwrapResponse(response)
  },

  // ===== EVENT EMPLOYEES =====
  async getEventEmployees() {
    const response = await laravelApi.get('/communication/event-employees')
    return unwrapResponse(response)
  },

  async addEmployeeToEvent(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/event-employees', payload)
    return unwrapResponse(response)
  },

  async getEventEmployee(id: number) {
    const response = await laravelApi.get(`/communication/event-employees/${id}`)
    return unwrapResponse(response)
  },

  async updateEventEmployee(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/event-employees/${id}`, payload)
    return unwrapResponse(response)
  },

  async removeEmployeeFromEvent(id: number) {
    const response = await laravelApi.delete(`/communication/event-employees/${id}`)
    return unwrapResponse(response)
  },

  // ===== MEETINGS =====
  async getMeetings() {
    const response = await laravelApi.get('/communication/meetings')
    return unwrapResponse(response)
  },

  async createMeeting(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/meetings', payload)
    return unwrapResponse(response)
  },

  async getMeeting(id: number) {
    const response = await laravelApi.get(`/communication/meetings/${id}`)
    return unwrapResponse(response)
  },

  async updateMeeting(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/meetings/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteMeeting(id: number) {
    const response = await laravelApi.delete(`/communication/meetings/${id}`)
    return unwrapResponse(response)
  },

  // ===== MEETING EMPLOYEES =====
  async getMeetingEmployees() {
    const response = await laravelApi.get('/communication/meeting-employees')
    return unwrapResponse(response)
  },

  async addEmployeeToMeeting(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/meeting-employees', payload)
    return unwrapResponse(response)
  },

  async getMeetingEmployee(id: number) {
    const response = await laravelApi.get(`/communication/meeting-employees/${id}`)
    return unwrapResponse(response)
  },

  async updateMeetingEmployee(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/meeting-employees/${id}`, payload)
    return unwrapResponse(response)
  },

  async removeEmployeeFromMeeting(id: number) {
    const response = await laravelApi.delete(`/communication/meeting-employees/${id}`)
    return unwrapResponse(response)
  },

  // ===== ANNOUNCEMENTS =====
  async getAnnouncements() {
    const response = await laravelApi.get('/communication/announcements')
    return unwrapResponse(response)
  },

  async createAnnouncement(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/announcements', payload)
    return unwrapResponse(response)
  },

  async getAnnouncement(id: number) {
    const response = await laravelApi.get(`/communication/announcements/${id}`)
    return unwrapResponse(response)
  },

  async updateAnnouncement(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/announcements/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteAnnouncement(id: number) {
    const response = await laravelApi.delete(`/communication/announcements/${id}`)
    return unwrapResponse(response)
  },

  // ===== ANNOUNCEMENT EMPLOYEES =====
  async getAnnouncementEmployees() {
    const response = await laravelApi.get('/communication/announcement-employees')
    return unwrapResponse(response)
  },

  async addEmployeeToAnnouncement(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/announcement-employees', payload)
    return unwrapResponse(response)
  },

  async getAnnouncementEmployee(id: number) {
    const response = await laravelApi.get(`/communication/announcement-employees/${id}`)
    return unwrapResponse(response)
  },

  async updateAnnouncementEmployee(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/announcement-employees/${id}`, payload)
    return unwrapResponse(response)
  },

  async removeEmployeeFromAnnouncement(id: number) {
    const response = await laravelApi.delete(`/communication/announcement-employees/${id}`)
    return unwrapResponse(response)
  },

  // ===== TICKETS =====
  async getTickets() {
    const response = await laravelApi.get('/communication/tickets')
    return unwrapResponse(response)
  },

  async createTicket(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/tickets', payload)
    return unwrapResponse(response)
  },

  async getTicket(id: number) {
    const response = await laravelApi.get(`/communication/tickets/${id}`)
    return unwrapResponse(response)
  },

  async updateTicket(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/tickets/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTicket(id: number) {
    const response = await laravelApi.delete(`/communication/tickets/${id}`)
    return unwrapResponse(response)
  },

  // ===== TICKET REPLIES =====
  async getTicketReplies() {
    const response = await laravelApi.get('/communication/ticket-replies')
    return unwrapResponse(response)
  },

  async createTicketReply(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/ticket-replies', payload)
    return unwrapResponse(response)
  },

  async getTicketReply(id: number) {
    const response = await laravelApi.get(`/communication/ticket-replies/${id}`)
    return unwrapResponse(response)
  },

  async updateTicketReply(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/ticket-replies/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTicketReply(id: number) {
    const response = await laravelApi.delete(`/communication/ticket-replies/${id}`)
    return unwrapResponse(response)
  },

  // ===== ZOOM MEETINGS =====
  async getZoomMeetings() {
    const response = await laravelApi.get('/communication/zoom-meetings')
    return unwrapResponse(response)
  },

  async createZoomMeeting(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/communication/zoom-meetings', payload)
    return unwrapResponse(response)
  },

  async getZoomMeeting(id: number) {
    const response = await laravelApi.get(`/communication/zoom-meetings/${id}`)
    return unwrapResponse(response)
  },

  async updateZoomMeeting(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/communication/zoom-meetings/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteZoomMeeting(id: number) {
    const response = await laravelApi.delete(`/communication/zoom-meetings/${id}`)
    return unwrapResponse(response)
  }
}
