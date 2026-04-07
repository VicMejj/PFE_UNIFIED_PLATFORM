import { djangoApi, unwrapResponse } from '@/api/http'

export const djangoAiApi = {
  async predictTurnover(payload) {
    const response = await djangoApi.post('/ai/turnover/predict/', payload)
    return unwrapResponse(response)
  },

  async getOptimalLeaveDates(payload = {}) {
    const response = await djangoApi.post('/ai/leave/optimal-dates/', payload)
    return unwrapResponse(response)
  },

  async sendChatMessage(message, sessionId, signal) {
    const response = await djangoApi.post(
      '/ai/chatbot/message/',
      {
        message,
        session_id: sessionId
      },
      signal ? { signal } : undefined
    )
    return unwrapResponse(response)
  }
}

// Backward-compatible named exports for any stale modules in the dev graph.
export const generateReport = async (employeeId) =>
  djangoAiApi.predictTurnover({ employee_id: employeeId })

export const getAnalytics = async (filters) =>
  djangoAiApi.getOptimalLeaveDates(filters)

export const predictTrends = async (payload) =>
  djangoAiApi.predictTurnover(payload)
