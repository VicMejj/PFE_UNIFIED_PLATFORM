import { djangoApi, unwrapResponse } from '@/api/http'

export const djangoAiApi = {
  // ===== CHATBOT =====
  async sendChatMessage(message: string, sessionId: string, signal?: AbortSignal) {
    const response = await djangoApi.post(
      '/ai/chatbot/message/',
      {
        message,
        session_id: sessionId
      },
      signal ? { signal } : undefined
    )
    return unwrapResponse(response)
  },

  // ===== TURNOVER PREDICTION =====
  async predictTurnover(payload: Record<string, unknown>) {
    const response = await djangoApi.post('/ai/turnover/predict/', payload)
    return unwrapResponse(response)
  },

  async trainTurnoverModel(trainingData: Record<string, unknown> = {}) {
    const response = await djangoApi.post('/ai/turnover/train/', trainingData)
    return unwrapResponse(response)
  },

  // ===== LEAVE OPTIMIZATION =====
  async getOptimalLeaveDates(payload: Record<string, unknown> = {}) {
    const response = await djangoApi.post('/ai/leave/optimal-dates/', payload)
    return unwrapResponse(response)
  },

  async getDashboardInsights() {
    const response = await djangoApi.get('/ai/insights/dashboard/')
    return unwrapResponse(response)
  },

  // ===== LOAN RISK ASSESSMENT =====
  async assessLoanRisk(payload: Record<string, unknown>) {
    const response = await djangoApi.post('/ai/loan/assess-risk/', payload)
    return unwrapResponse(response)
  },

  // ===== DOCUMENT PROCESSING =====
  async processOCR(file: File) {
    const formData = new FormData()
    formData.append('document', file)

    const response = await djangoApi.post('/ai/ocr/process/', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return unwrapResponse(response)
  },

  async classifyDocument(file: File) {
    const formData = new FormData()
    formData.append('document', file)

    const response = await djangoApi.post('/ai/document/classify/', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return unwrapResponse(response)
  },

  async detectFraud(payload: Record<string, unknown>) {
    const response = await djangoApi.post('/ai/fraud/detect/', payload)
    return unwrapResponse(response)
  },

  // ===== NOTIFICATIONS =====
  async getNotifications() {
    const response = await djangoApi.get('/notifications/')
    return unwrapResponse(response)
  },

  // ===== UTILITIES =====
  async readPDF(file: File) {
    const formData = new FormData()
    formData.append('file', file)

    const response = await djangoApi.post('/ai/maint/read-pdf/', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return unwrapResponse(response)
  }
}
