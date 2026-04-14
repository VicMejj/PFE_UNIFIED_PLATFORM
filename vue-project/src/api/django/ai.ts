import { djangoApi, unwrapResponse } from '@/api/http'

function isDjangoOffline(error: any) {
  const message = String(error?.message || '').toLowerCase()
  return error?.code === 'ERR_NETWORK' || error?.code === 'ECONNABORTED' || message.includes('connection refused')
}

function fallbackChatResponse(message: string) {
  const lower = message.toLowerCase()
  if (/hi|hello|hey|good morning|good afternoon/.test(lower)) {
    return 'Hello. I’m in offline fallback mode (the AI server is not reachable), but I can still point you around the app. For full smart answers, keep Django running on port 8001.'
  }
  if (/branch|department|designation|organiz|structure/.test(lower)) {
    return 'To add branches, departments, or job titles: open Organization in the sidebar. Admins: Admin → Organization at /admin/organization. HR: Organization under RH at /rh/organization. Then open Employees to assign them when you create or edit staff.'
  }
  if (/employee|staff|hire|talent|directory/.test(lower)) {
    return 'Manage people under Employees at /rh/employees: create profiles, assign branch, department, designation, and roles. Admins also have Talent Pool in the sidebar for the same screen.'
  }
  if (lower.includes('turnover')) {
    return 'Turnover insights need the AI service online. Meanwhile, open Employees, pick someone, and use the turnover tools on that page when available.'
  }
  if (lower.includes('leave')) {
    return 'Use Leave Requests in the sidebar to submit or (as HR) review leave. Smart optimal-date suggestions need Django running.'
  }
  if (/pay|salary|payslip|payroll/.test(lower)) {
    return 'Payroll is under Payroll / Payroll Engine in the RH or admin sidebar at /rh/payroll.'
  }
  if (/contract|agreement/.test(lower)) {
    return 'Contracts are under Agreements / Compliance at /rh/contracts.'
  }
  if (/insurance|claim|policy/.test(lower)) {
    return 'Insurance: open Insurance Hub at /insurance for policies and claims.'
  }
  if (/attendance|timesheet/.test(lower)) {
    return 'Attendance and timesheets: Attendance in the sidebar at /attendance.'
  }
  return 'The AI backend is offline, so I can’t run the full model. Check that Django is running (e.g. `python manage.py runserver 0.0.0.0:8001`) and that the app proxies `/django-api` to it. You can still use Organization, Employees, Leave, and Payroll from the sidebar.'
}

function fallbackTurnoverResponse(payload: Record<string, unknown>) {
  const tenure = Number(payload.tenure_years ?? 0)
  const salary = Number(payload.salary ?? 0)
  const complaints = Number(payload.complaints_count ?? 0)
  const performance = Number(payload.performance_score ?? payload.performance ?? 0)
  const leaves = Number(payload.leaves_taken ?? 0)
  let risk = 0.18
  risk += Math.max(0, 0.28 - Math.min(0.28, tenure / 40))
  risk += Math.max(0, 0.22 - Math.min(0.22, performance / 20))
  risk += Math.min(0.18, complaints * 0.05)
  risk += Math.min(0.12, leaves * 0.01)
  if (salary < 50000) risk += 0.05
  const score = Math.max(0.05, Math.min(0.95, risk))
  return {
    prediction_score: Number(score.toFixed(3)),
    risk_level: score >= 0.7 ? 'high' : score >= 0.45 ? 'medium' : 'low',
    factors: [
      tenure < 12 ? 'Low tenure' : 'Stable tenure',
      performance < 3.5 ? 'Lower performance score' : 'Healthy performance',
      complaints > 0 ? 'Complaint history present' : 'No complaint history',
    ],
    source: 'local-fallback'
  }
}

function fallbackLeaveDates(payload: Record<string, unknown> = {}) {
  const today = new Date()
  const start = new Date(String(payload.start_date || today.toISOString().slice(0, 10)))
  const end = new Date(start)
  end.setDate(start.getDate() + 2)
  return {
    recommended_single_days: [
      {
        date: start.toISOString().slice(0, 10),
        weekday: start.toLocaleDateString('en-US', { weekday: 'long' }),
        suitability_score: 0.82
      }
    ],
    recommended_windows: [
      {
        start_date: start.toISOString().slice(0, 10),
        end_date: end.toISOString().slice(0, 10),
        suitability_score: 0.78
      }
    ],
    source: 'local-fallback'
  }
}

export const djangoAiApi = {
  async getChatHistory(sessionId?: string) {
    const response = await djangoApi.get('/ai/chatbot/history/', {
      params: sessionId ? { session_id: sessionId } : undefined
    })
    return unwrapResponse(response)
  },

  // ===== CHATBOT =====
  async sendChatMessage(message: string, sessionId: string, signal?: AbortSignal) {
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
      if (isDjangoOffline(error)) {
        return { response: fallbackChatResponse(message), intent: 'fallback', entities: [], source: 'local-fallback' }
      }
      throw error
    }
  },

  // ===== TURNOVER PREDICTION =====
  async predictTurnover(payload: Record<string, unknown>) {
    try {
      const response = await djangoApi.post('/ai/turnover/predict/', payload)
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) {
        return fallbackTurnoverResponse(payload)
      }
      throw error
    }
  },

  async getTurnoverHistory() {
    const response = await djangoApi.get('/ai/turnover/history/')
    return unwrapResponse(response)
  },

  async trainTurnoverModel(trainingData: Record<string, unknown> = {}) {
    const response = await djangoApi.post('/ai/turnover/train/', trainingData)
    return unwrapResponse(response)
  },

  // ===== LEAVE OPTIMIZATION =====
  async getOptimalLeaveDates(payload: Record<string, unknown> = {}) {
    try {
      const response = await djangoApi.post('/ai/leave/optimal-dates/', payload)
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) {
        return fallbackLeaveDates(payload)
      }
      throw error
    }
  },

  async getDashboardInsights() {
    try {
      const response = await djangoApi.get('/ai/insights/dashboard/')
      return unwrapResponse(response)
    } catch (error) {
      if (isDjangoOffline(error)) {
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
      throw error
    }
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
  ,

  async getLoanHistory() {
    const response = await djangoApi.get('/ai/loan/history/')
    return unwrapResponse(response)
  }
}
