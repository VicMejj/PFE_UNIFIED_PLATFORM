import { djangoApi, unwrapResponse } from '@/api/http'

function isDjangoOffline(error) {
  const message = String(error?.message || '').toLowerCase()
  return error?.code === 'ERR_NETWORK' || error?.code === 'ECONNABORTED' || message.includes('connection refused')
}

function fallbackChatResponse(message) {
  const lower = String(message || '').toLowerCase()
  if (/hi|hello|hey|good morning|good afternoon/.test(lower)) {
    return 'Hello. I’m online in fallback mode, so I can still help while the Django AI service is unavailable.'
  }
  return 'The Django AI service is temporarily unavailable. Please try again shortly.'
}

function fallbackTurnoverResponse(payload) {
  const tenure = Number(payload?.tenure_years ?? 0)
  const performance = Number(payload?.performance ?? 0)
  const score = Math.max(0.05, Math.min(0.95, 0.35 + (10 - Math.min(10, tenure)) * 0.03 + Math.max(0, 5 - performance) * 0.08))
  return {
    prediction_score: Number(score.toFixed(3)),
    risk_level: score >= 0.7 ? 'high' : score >= 0.45 ? 'medium' : 'low',
    source: 'local-fallback'
  }
}

function fallbackFraudResponse(payload) {
  const amount = Number(payload?.claim_amount ?? payload?.amount ?? 0)
  const score = Math.max(0.05, Math.min(0.95, amount > 10000 ? 0.78 : amount > 5000 ? 0.52 : 0.18))
  return {
    fraud_score: score,
    risk_tier: score >= 0.7 ? 'high' : score >= 0.3 ? 'medium' : 'low',
    flags: amount > 10000 ? ['High claim amount'] : [],
    source: 'local-fallback'
  }
}

function fallbackDocumentClassification(payload) {
  const text = String(payload?.document?.name || payload?.document_name || '').toLowerCase()
  const medical = /(invoice|claim|medical|health|hospital|insurance)/.test(text)
  return {
    category: medical ? 'Insurance Claim' : 'General Document',
    confidence: 0.72,
    medical_specialty: medical ? 'Insurance' : undefined,
    source: 'local-fallback'
  }
}

function fallbackDashboardInsights() {
  return {
    burnout_risk_employees: [],
    high_turnover_risk_count: 0,
    anomaly_alerts: [],
    low_workload_windows: [],
    benefit_utilization_rate: 0,
    sentiment_trend: 'stable',
    source: 'local-fallback'
  }
}

export const djangoAiApi = {
  async predictTurnover(payload) {
    try {
      const response = await djangoApi.post('/ai/turnover/predict/', payload)
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) return fallbackTurnoverResponse(payload)
      throw error
    }
  },

  async getOptimalLeaveDates(payload = {}) {
    try {
      const response = await djangoApi.post('/ai/leave/optimal-dates/', payload)
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) {
        return { recommended_single_days: [], recommended_windows: [], source: 'local-fallback' }
      }
      throw error
    }
  },

  async sendChatMessage(message, sessionId, signal) {
    try {
      const response = await djangoApi.post(
        '/ai/chatbot/message/',
        {
          message,
          session_id: sessionId
        },
        signal ? { signal } : undefined
      )
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) return { response: fallbackChatResponse(message), intent: 'fallback', entities: [], source: 'local-fallback' }
      throw error
    }
  },

  async detectFraud(payload) {
    try {
      const response = await djangoApi.post('/ai/fraud/detect/', payload)
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) return fallbackFraudResponse(payload)
      throw error
    }
  },

  async processOCR(file) {
    const formData = new FormData()
    formData.append('document', file)

    try {
      const response = await djangoApi.post('/ai/ocr/process/', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) {
        return { extracted_data: {}, raw_text: '', source: 'local-fallback' }
      }
      throw error
    }
  },

  async classifyDocument(file) {
    const formData = new FormData()
    formData.append('document', file)

    try {
      const response = await djangoApi.post('/ai/document/classify/', formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) return fallbackDocumentClassification({ document: file })
      throw error
    }
  },

  async getDashboardInsights() {
    try {
      const response = await djangoApi.get('/ai/insights/dashboard/')
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) return fallbackDashboardInsights()
      throw error
    }
  },

  async trainTurnoverModel(trainingData = {}) {
    try {
      const response = await djangoApi.post('/ai/turnover/train/', trainingData)
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) {
        return { status: 'offline', source: 'local-fallback' }
      }
      throw error
    }
  },

  async assessLoanRisk(payload) {
    try {
      const response = await djangoApi.post('/ai/loan/assess-risk/', payload)
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) {
        return { safety_score: 0.5, risk_level: 'moderate', source: 'local-fallback' }
      }
      throw error
    }
  },

  async getTurnoverHistory() {
    const response = await djangoApi.get('/ai/turnover/history/')
    return unwrapResponse(response)
  },

  async getLoanHistory() {
    const response = await djangoApi.get('/ai/loan/history/')
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
