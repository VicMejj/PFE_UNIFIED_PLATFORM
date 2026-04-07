import { laravelApi } from '@/api/http'
import { unwrapResponse } from '@/api/http'

export const employeeApi = {
  // ===== DOCUMENTS =====
  async getDocuments() {
    const response = await laravelApi.get('/employees/documents')
    return unwrapResponse(response)
  },

  async createDocument(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/documents', payload)
    return unwrapResponse(response)
  },

  async getDocument(id: number) {
    const response = await laravelApi.get(`/employees/documents/${id}`)
    return unwrapResponse(response)
  },

  async updateDocument(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/documents/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteDocument(id: number) {
    const response = await laravelApi.delete(`/employees/documents/${id}`)
    return unwrapResponse(response)
  },

  // ===== EMPLOYEE DOCUMENTS =====
  async getEmployeeDocuments() {
    const response = await laravelApi.get('/employees/employee-documents')
    return unwrapResponse(response)
  },

  async createEmployeeDocument(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/employee-documents', payload)
    return unwrapResponse(response)
  },

  async getEmployeeDocument(id: number) {
    const response = await laravelApi.get(`/employees/employee-documents/${id}`)
    return unwrapResponse(response)
  },

  async updateEmployeeDocument(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/employee-documents/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteEmployeeDocument(id: number) {
    const response = await laravelApi.delete(`/employees/employee-documents/${id}`)
    return unwrapResponse(response)
  },

  // ===== AWARDS =====
  async getAwards() {
    const response = await laravelApi.get('/employees/awards')
    return unwrapResponse(response)
  },

  async createAward(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/awards', payload)
    return unwrapResponse(response)
  },

  async getAward(id: number) {
    const response = await laravelApi.get(`/employees/awards/${id}`)
    return unwrapResponse(response)
  },

  async updateAward(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/awards/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteAward(id: number) {
    const response = await laravelApi.delete(`/employees/awards/${id}`)
    return unwrapResponse(response)
  },

  // ===== AWARD TYPES =====
  async getAwardTypes() {
    const response = await laravelApi.get('/employees/award-types')
    return unwrapResponse(response)
  },

  async createAwardType(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/award-types', payload)
    return unwrapResponse(response)
  },

  async getAwardType(id: number) {
    const response = await laravelApi.get(`/employees/award-types/${id}`)
    return unwrapResponse(response)
  },

  async updateAwardType(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/award-types/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteAwardType(id: number) {
    const response = await laravelApi.delete(`/employees/award-types/${id}`)
    return unwrapResponse(response)
  },

  // ===== TERMINATIONS =====
  async getTerminations() {
    const response = await laravelApi.get('/employees/terminations')
    return unwrapResponse(response)
  },

  async createTermination(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/terminations', payload)
    return unwrapResponse(response)
  },

  async getTermination(id: number) {
    const response = await laravelApi.get(`/employees/terminations/${id}`)
    return unwrapResponse(response)
  },

  async updateTermination(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/terminations/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTermination(id: number) {
    const response = await laravelApi.delete(`/employees/terminations/${id}`)
    return unwrapResponse(response)
  },

  // ===== TERMINATION TYPES =====
  async getTerminationTypes() {
    const response = await laravelApi.get('/employees/termination-types')
    return unwrapResponse(response)
  },

  async createTerminationType(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/termination-types', payload)
    return unwrapResponse(response)
  },

  async getTerminationType(id: number) {
    const response = await laravelApi.get(`/employees/termination-types/${id}`)
    return unwrapResponse(response)
  },

  async updateTerminationType(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/termination-types/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTerminationType(id: number) {
    const response = await laravelApi.delete(`/employees/termination-types/${id}`)
    return unwrapResponse(response)
  },

  // ===== RESIGNATIONS =====
  async getResignations() {
    const response = await laravelApi.get('/employees/resignations')
    return unwrapResponse(response)
  },

  async createResignation(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/resignations', payload)
    return unwrapResponse(response)
  },

  async getResignation(id: number) {
    const response = await laravelApi.get(`/employees/resignations/${id}`)
    return unwrapResponse(response)
  },

  async updateResignation(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/resignations/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteResignation(id: number) {
    const response = await laravelApi.delete(`/employees/resignations/${id}`)
    return unwrapResponse(response)
  },

  // ===== TRANSFERS =====
  async getTransfers() {
    const response = await laravelApi.get('/employees/transfers')
    return unwrapResponse(response)
  },

  async createTransfer(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/transfers', payload)
    return unwrapResponse(response)
  },

  async getTransfer(id: number) {
    const response = await laravelApi.get(`/employees/transfers/${id}`)
    return unwrapResponse(response)
  },

  async updateTransfer(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/transfers/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTransfer(id: number) {
    const response = await laravelApi.delete(`/employees/transfers/${id}`)
    return unwrapResponse(response)
  },

  // ===== PROMOTIONS =====
  async getPromotions() {
    const response = await laravelApi.get('/employees/promotions')
    return unwrapResponse(response)
  },

  async createPromotion(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/promotions', payload)
    return unwrapResponse(response)
  },

  async getPromotion(id: number) {
    const response = await laravelApi.get(`/employees/promotions/${id}`)
    return unwrapResponse(response)
  },

  async updatePromotion(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/promotions/${id}`, payload)
    return unwrapResponse(response)
  },

  async deletePromotion(id: number) {
    const response = await laravelApi.delete(`/employees/promotions/${id}`)
    return unwrapResponse(response)
  },

  // ===== TRAVELS =====
  async getTravels() {
    const response = await laravelApi.get('/employees/travels')
    return unwrapResponse(response)
  },

  async createTravel(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/travels', payload)
    return unwrapResponse(response)
  },

  async getTravel(id: number) {
    const response = await laravelApi.get(`/employees/travels/${id}`)
    return unwrapResponse(response)
  },

  async updateTravel(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/travels/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTravel(id: number) {
    const response = await laravelApi.delete(`/employees/travels/${id}`)
    return unwrapResponse(response)
  },

  // ===== WARNINGS =====
  async getWarnings() {
    const response = await laravelApi.get('/employees/warnings')
    return unwrapResponse(response)
  },

  async createWarning(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/warnings', payload)
    return unwrapResponse(response)
  },

  async getWarning(id: number) {
    const response = await laravelApi.get(`/employees/warnings/${id}`)
    return unwrapResponse(response)
  },

  async updateWarning(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/warnings/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteWarning(id: number) {
    const response = await laravelApi.delete(`/employees/warnings/${id}`)
    return unwrapResponse(response)
  },

  // ===== COMPLAINTS =====
  async getComplaints() {
    const response = await laravelApi.get('/employees/complaints')
    return unwrapResponse(response)
  },

  async createComplaint(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/complaints', payload)
    return unwrapResponse(response)
  },

  async getComplaint(id: number) {
    const response = await laravelApi.get(`/employees/complaints/${id}`)
    return unwrapResponse(response)
  },

  async updateComplaint(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/complaints/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteComplaint(id: number) {
    const response = await laravelApi.delete(`/employees/complaints/${id}`)
    return unwrapResponse(response)
  },

  // ===== COMPETENCIES =====
  async getCompetencies() {
    const response = await laravelApi.get('/employees/competencies')
    return unwrapResponse(response)
  },

  async createCompetency(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/competencies', payload)
    return unwrapResponse(response)
  },

  async getCompetency(id: number) {
    const response = await laravelApi.get(`/employees/competencies/${id}`)
    return unwrapResponse(response)
  },

  async updateCompetency(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/competencies/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteCompetency(id: number) {
    const response = await laravelApi.delete(`/employees/competencies/${id}`)
    return unwrapResponse(response)
  },

  // ===== TRAINING TYPES =====
  async getTrainingTypes() {
    const response = await laravelApi.get('/employees/training-types')
    return unwrapResponse(response)
  },

  async createTrainingType(payload: Record<string, unknown>) {
    const response = await laravelApi.post('/employees/training-types', payload)
    return unwrapResponse(response)
  },

  async getTrainingType(id: number) {
    const response = await laravelApi.get(`/employees/training-types/${id}`)
    return unwrapResponse(response)
  },

  async updateTrainingType(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/training-types/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteTrainingType(id: number) {
    const response = await laravelApi.delete(`/employees/training-types/${id}`)
    return unwrapResponse(response)
  },

  // ===== DOCUMENT UPLOADS =====
  async getDocumentUploads() {
    const response = await laravelApi.get('/employees/document-uploads')
    return unwrapResponse(response)
  },

  async uploadDocument(payload: FormData) {
    const response = await laravelApi.post('/employees/document-uploads', payload, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return unwrapResponse(response)
  },

  async getDocumentUpload(id: number) {
    const response = await laravelApi.get(`/employees/document-uploads/${id}`)
    return unwrapResponse(response)
  },

  async updateDocumentUpload(id: number, payload: Record<string, unknown>) {
    const response = await laravelApi.put(`/employees/document-uploads/${id}`, payload)
    return unwrapResponse(response)
  },

  async deleteDocumentUpload(id: number) {
    const response = await laravelApi.delete(`/employees/document-uploads/${id}`)
    return unwrapResponse(response)
  }
}
