import { djangoApi, unwrapResponse } from '@/api/http'

function isDjangoOffline(error) {
  const message = String(error?.message || '').toLowerCase()
  return error?.code === 'ERR_NETWORK' || error?.code === 'ECONNABORTED' || message.includes('connection refused')
}

function isApiError(error) {
  // Check if Django returned an error response (not network issue)
  if (error?.response?.status) {
    return error.response.status >= 400
  }
  // Check for specific error messages that indicate backend issues
  const msg = String(error?.message || '').toLowerCase()
  return msg.includes('500') || msg.includes('service unavailable') || msg.includes('backend error')
}

function fallbackChatResponse(message) {
  const lower = String(message || '').toLowerCase()
  const trimmed = message.trim()
  
  console.log('[Chatbot Fallback] Processing:', message.substring(0, 50))
  
  // Check for two-word names (like Montassar Monta, John Doe)
  if (/^[A-Z][a-z]+\s+[A-Z][a-z]+$/.test(trimmed)) {
    console.log('[Chatbot Fallback] Detected as name query')
    return `I can look up "${trimmed}" in our employee database. Please sign in and try again.`
  }
  
  // Greetings - MUST come first
  if (/^(hi|hello|hey|good morning|good afternoon|how are|what's up|whats up|howdy|sup)$/.test(lower) || 
      lower.startsWith('hello ') || lower.startsWith('hi ') || lower.startsWith('hey ')) {
    console.log('[Chatbot Fallback] Detected as greeting')
    return "I'm doing great, thank you for asking! I'm Mejj, your HR & platform assistant. How can I help you today?"
  }
  
  // Thanks
  if (/^(thank|thanks|thx)$/.test(lower) || lower.startsWith('thank ') || lower.startsWith('thanks ')) {
    console.log('[Chatbot Fallback] Detected as thanks')
    return "You're welcome! Is there anything else I can help you with?"
  }
  
  // Goodbyes
  if (/^(bye|goodbye|see you|talk later|later)$/.test(lower) || lower.startsWith('bye ') || lower.startsWith('goodbye ')) {
    console.log('[Chatbot Fallback] Detected as farewell')
    return "Goodbye! Feel free to come back anytime you need help. Have a great day!"
  }
  
  // Help requests - before generic matches
  if (/^(help|help me|what can you do|can you help|need help|help with)/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as help request')
    return "I can help you with: leave requests & balances, payroll inquiries, HR policies, insurance questions, employee information, and general company questions. What would you like to know?"
  }
  
  // Policy questions - MUST check BEFORE leave queries
  // "Explain sick leave policy", "what's the policy on...", "sick leave policy"
  if (/\bpolicy|policies\b/.test(lower) || /explain\s+\w+\s+policy/.test(lower) || 
      (/\bsick\b/.test(lower) && /\bleave\b/.test(lower)) || 
      (/\bannual\b/.test(lower) && /\bvacation\b/.test(lower)) ||
      /\bremote\s+work\b/.test(lower) || /\bwork\s+from\s+home\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as policy query')
    return "I can explain our company policies! For detailed policies, please check the Employee Handbook in your dashboard or contact HR for specific questions."
  }
  
  // Performance/Scores - before generic employee
  if (/\bperformance\b/.test(lower) || /\bscore\b/.test(lower) || /\brating\b/.test(lower) ||
      /\bappraisal\b/.test(lower) || /\bevaluation\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as performance query')
    return "For performance reviews and scores, please check your employee profile in the dashboard or contact HR for details."
  }
  
  // Employee searches - specific patterns before generic
  if (/\bemployee\s+(named|called)\b/.test(lower) || /\bdo\s+we\s+have\b/.test(lower) ||
      /\bis\s+there\s+an?\s+employee\b/.test(lower) || /\bfind\s+employee\b/.test(lower) ||
      /\bsearch\s+for\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as employee search')
    return "I can help you find employee information! To search for specific employees, please make sure you're signed in and try again."
  }
  
  // Insurance/Benefits - before generic
  if (/\binsurance\b/.test(lower) || /\bbenefits\b/.test(lower) || /\bcoverage\b/.test(lower) ||
      /\bclaim\b/.test(lower) || /\bmedical\b/.test(lower) || /\bhealth\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as insurance/benefits query')
    return "For insurance and benefits information, please check the Insurance section in your dashboard where you can view coverage and submit claims."
  }
  
  // Payroll/Salary - before generic
  if (/\bsalary\b/.test(lower) || /\bpayroll\b/.test(lower) || /\bpay\s+stub\b/.test(lower) ||
      /\bcompensation\b/.test(lower) || /\bincome\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as payroll query')
    return "For salary and payroll information, please check the Payroll section in your dashboard or contact HR directly."
  }
  
  // Leave queries - ONLY if NOT policy-related
  // "my leave balance", "how many days left", "request leave"
  // Must exclude: "sick leave policy", "leave policy", "explain leave"
  if (/\bleave\b/.test(lower) && !/\bpolicy\b/.test(lower) && !/\bexplain\b/.test(lower)) {
    if (/\bbalance\b/.test(lower) || /\bhow\s+many\b/.test(lower) || /\bremaining\b/.test(lower) ||
        /\bavailable\b/.test(lower) || /\bmy\s+leaves?\b/.test(lower)) {
      console.log('[Chatbot Fallback] Detected as leave balance query')
      return "To check your leave balance, please go to the Leave section in your dashboard where you can see your available days."
    }
    if (/\brequest\b/.test(lower) || /\bapply\b/.test(lower) || /\bbook\b/.test(lower)) {
      console.log('[Chatbot Fallback] Detected as leave request query')
      return "To request leave, please go to the Leave section in your dashboard to submit a leave request."
    }
    if (/\btypes\b/.test(lower) || /\boptions\b/.test(lower) || /\bpossible\s+days\b/.test(lower)) {
      console.log('[Chatbot Fallback] Detected as leave types query')
      return "To see available leave types and options, please check the Leave section in your dashboard."
    }
  }
  
  // Attendance
  if (/\battendance\b/.test(lower) || /\babsent\b/.test(lower) || /\bpresent\b/.test(lower) ||
      (/\bwho\s+is\b/.test(lower) && /\b(absent|late)\b/.test(lower))) {
    console.log('[Chatbot Fallback] Detected as attendance query')
    return "For attendance information, please check the Attendance section in your dashboard."
  }
  
  // Department stats
  if (/\bdepartment\b/.test(lower) || (/\bhow\s+many\b/.test(lower) && /\b(employees?|staff)\b/.test(lower))) {
    console.log('[Chatbot Fallback] Detected as department query')
    return "For department statistics, please check the HR dashboard or contact your administrator."
  }
  
  // Role stats (admin only)
  if (/\busers?\s+stats?\b/.test(lower) || /\busers?\s+by\s+role\b/.test(lower) ||
      /\bhow\s+many\s+(admins?|managers?)\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as role stats query')
    return "User role statistics are available for administrators. Please sign in with an admin account."
  }
  
  // Employee count
  if (/\bemployee\s+count\b/.test(lower) || /\btotal\s+employees?\b/.test(lower) ||
      /\bhow\s+many\s+employees?\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as employee count query')
    return "For employee count information, please check the HR dashboard or homepage."
  }
  
  // Generic employee query - only if nothing else matched
  if (/\bemployee\b/.test(lower) || /\bstaff\b/.test(lower) || /\bteam\s+member\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as generic employee query')
    return "I can help you find employee information! Please sign in and try again, or contact HR."
  }
  
  // Who is query
  if (/\bwho\s+is\b/.test(lower) || /\bwhois\b/.test(lower)) {
    console.log('[Chatbot Fallback] Detected as who-is query')
    return "I can look up employee details! Please provide the employee's name or sign in to search."
  }
  
  // Default - helpful response
  console.log('[Chatbot Fallback] Using default fallback')
  return "I'm here to help! You can ask me about leave requests, payroll, insurance, policies, employee data, and more. What would you like to know?"
}

function fallbackTurnoverResponse(payload) {
  console.log('[Chatbot Fallback] Turnover prediction (offline)')
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
  console.log('[Chatbot Fallback] Fraud detection (offline)')
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
  console.log('[Chatbot Fallback] Document classification (offline)')
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
  console.log('[Chatbot Fallback] Dashboard insights (offline)')
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
      console.log('[API] Turnover predict error:', error.message)
      if (isDjangoOffline(error)) return fallbackTurnoverResponse(payload)
      throw error
    }
  },

  async getOptimalLeaveDates(payload = {}) {
    try {
      const response = await djangoApi.post('/ai/leave/optimal-dates/', payload)
      return unwrapResponse(response)
    } catch (error) {
      console.log('[API] Optimal leave dates error:', error.message)
      if (isDjangoOffline(error)) {
        return { recommended_single_days: [], recommended_windows: [], source: 'local-fallback' }
      }
      throw error
    }
  },

  async sendChatMessage(message, sessionId, signal) {
    console.log('[API] Sending chat message:', message.substring(0, 50))
    try {
      const response = await djangoApi.post(
        '/ai/chatbot/message/',
        {
          message,
          session_id: sessionId
        },
        signal ? { signal } : undefined
      )
      const data = unwrapResponse(response)
      console.log('[API] Chat response received, intent:', data.intent)
      return data
    } catch (error) {
      console.log('[API] Chat error:', error.message, 'Is offline:', isDjangoOffline(error))
      if (isDjangoOffline(error)) {
        return { 
          response: fallbackChatResponse(message), 
          intent: 'fallback', 
          entities: [], 
          source: 'local-fallback' 
        }
      }
      // For API errors (not network), try fallback but don't override if response seems valid
      if (isApiError(error)) {
        console.log('[API] API error detected, using fallback')
        return { 
          response: fallbackChatResponse(message), 
          intent: 'fallback', 
          entities: [], 
          source: 'api-error-fallback' 
        }
      }
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