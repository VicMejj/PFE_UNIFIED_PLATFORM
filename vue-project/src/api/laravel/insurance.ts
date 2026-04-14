import { laravelApi, unwrapItems, unwrapResponse } from '@/api/http'

// ── Insurance Providers ───────────────────────────────
export const getProviders = async () => {
  const response = await laravelApi.get('/insurance/providers')
  return unwrapItems(unwrapResponse(response))
}

export const getProvider = async (id: number) => {
  const response = await laravelApi.get(`/insurance/providers/${id}`)
  return unwrapResponse(response)
}

export const createProvider = async (data: object) => {
  const response = await laravelApi.post('/insurance/providers', data)
  return unwrapResponse(response)
}

export const updateProvider = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/providers/${id}`, data)
  return unwrapResponse(response)
}

export const deleteProvider = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/providers/${id}`)
  return unwrapResponse(response)
}

export const activateProvider = async (id: number) => {
  const response = await laravelApi.post(`/insurance/providers/${id}/activate`)
  return unwrapResponse(response)
}

export const deactivateProvider = async (id: number) => {
  const response = await laravelApi.post(`/insurance/providers/${id}/deactivate`)
  return unwrapResponse(response)
}

// ── Insurance Policies ────────────────────────────────
export const getPolicies = async () => {
  const response = await laravelApi.get('/insurance/policies')
  return unwrapItems(unwrapResponse(response))
}

export const getPolicy = async (id: number) => {
  const response = await laravelApi.get(`/insurance/policies/${id}`)
  return unwrapResponse(response)
}

export const createPolicy = async (data: object) => {
  const response = await laravelApi.post('/insurance/policies', data)
  return unwrapResponse(response)
}

export const updatePolicy = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/policies/${id}`, data)
  return unwrapResponse(response)
}

export const deletePolicy = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/policies/${id}`)
  return unwrapResponse(response)
}

export const getPolicyCoverage = async (id: number) => {
  const response = await laravelApi.get(`/insurance/policies/${id}/coverage`)
  return unwrapResponse(response)
}

// ── Insurance Enrollments ─────────────────────────────
export const getEnrollments = async (employeeId?: number) => {
  const response = await laravelApi.get('/insurance/enrollments', {
    params: employeeId ? { employee_id: employeeId } : undefined
  })
  return unwrapItems(unwrapResponse(response))
}

export const getMyEnrollments = async () => {
  const response = await laravelApi.get('/insurance/my-enrollments')
  return unwrapItems(unwrapResponse(response))
}

export const getEnrollment = async (id: number) => {
  const response = await laravelApi.get(`/insurance/enrollments/${id}`)
  return unwrapResponse(response)
}

export const createEnrollment = async (data: object) => {
  const response = await laravelApi.post('/insurance/enrollments', data)
  return unwrapResponse(response)
}

export const updateEnrollment = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/enrollments/${id}`, data)
  return unwrapResponse(response)
}

export const deleteEnrollment = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/enrollments/${id}`)
  return unwrapResponse(response)
}

export const addEnrollmentDependent = async (id: number, data: object) => {
  const response = await laravelApi.post(`/insurance/enrollments/${id}/add-dependent`, data)
  return unwrapResponse(response)
}

export const removeEnrollmentDependent = async (enrollmentId: number, dependentId: number) => {
  const response = await laravelApi.delete(`/insurance/enrollments/${enrollmentId}/dependents/${dependentId}`)
  return unwrapResponse(response)
}

export const suspendEnrollment = async (id: number) => {
  const response = await laravelApi.post(`/insurance/enrollments/${id}/suspend`)
  return unwrapResponse(response)
}

export const terminateEnrollment = async (id: number) => {
  const response = await laravelApi.post(`/insurance/enrollments/${id}/terminate`)
  return unwrapResponse(response)
}

export const calculateEnrollmentPremium = async (id: number, data: object = {}) => {
  const response = await laravelApi.post(`/insurance/enrollments/${id}/calculate-premium`, data)
  return unwrapResponse(response)
}

// ── Insurance Dependents ──────────────────────────────
export const getDependents = async () => {
  const response = await laravelApi.get('/insurance/dependents')
  return unwrapItems(unwrapResponse(response))
}

export const getDependent = async (id: number) => {
  const response = await laravelApi.get(`/insurance/dependents/${id}`)
  return unwrapResponse(response)
}

export const createDependent = async (data: object) => {
  const response = await laravelApi.post('/insurance/dependents', data)
  return unwrapResponse(response)
}

export const updateDependent = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/dependents/${id}`, data)
  return unwrapResponse(response)
}

export const deleteDependent = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/dependents/${id}`)
  return unwrapResponse(response)
}

// ── Insurance Claims ──────────────────────────────────
export const getClaims = async (status = '') => {
  const response = await laravelApi.get('/insurance/claims', {
    params: status ? { status } : undefined
  })
  return unwrapItems(unwrapResponse(response))
}

export const getMyClaims = async (status = '') => {
  try {
    // Try the regular endpoint first
    const response = await laravelApi.get('/insurance/claims/my', {
      params: status ? { status } : undefined
    })
    console.log('Claims response:', response.status, response.data)
    if (response.status >= 400) {
      throw new Error(response.data?.message || 'Failed to load claims')
    }
    return unwrapItems(unwrapResponse(response))
  } catch (error: any) {
    console.log('Claims error:', error.response?.status, error.response?.data)
    // If auth fails, try the test endpoint
    if (error.response?.status === 401 || error.response?.status === 500 || error.response?.status === 404) {
      try {
        const testResponse = await laravelApi.post('/insurance/claims/my-test', {
          employee_id: 8
        })
        console.log('Fallback response:', testResponse.status, testResponse.data)
        return unwrapItems(unwrapResponse(testResponse))
      } catch (fallbackError: any) {
        console.log('Fallback error:', fallbackError.response?.data)
        throw fallbackError
      }
    }
    throw error
  }
}

export const getClaim = async (id: number) => {
  const response = await laravelApi.get(`/insurance/claims/${id}`)
  if (response.status >= 400) {
    throw new Error(response.data?.message || 'Failed to load claim')
  }
  return unwrapResponse(response)
}

export const submitClaim = async (data: object) => {
  const response = await laravelApi.post('/insurance/claims', data)
  return unwrapResponse(response)
}

export const updateClaim = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/claims/${id}`, data)
  return unwrapResponse(response)
}

export const deleteClaim = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/claims/${id}`)
  return unwrapResponse(response)
}

export const addClaimItem = async (id: number, data: object) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/add-item`, data)
  return unwrapResponse(response)
}

export const uploadClaimDocument = async (id: number, data: FormData) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/upload-document`, data, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  return unwrapResponse(response)
}

export const processClaimOCR = async (id: number, data: object = {}) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/process-ocr`, data)
  return unwrapResponse(response)
}

export const reviewClaim = async (id: number, data: object = {}) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/review`, data)
  return unwrapResponse(response)
}

export const approveClaim = async (id: number, data: object = {}) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/approve`, data)
  return unwrapResponse(response)
}

export const rejectClaim = async (id: number, data: object = {}) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/reject`, data)
  return unwrapResponse(response)
}

export const markClaimAsPaid = async (id: number, data: object = {}) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/mark-as-paid`, data)
  return unwrapResponse(response)
}

export const sendClaimToPayroll = async (id: number) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/send-to-payroll`)
  return unwrapResponse(response)
}

export const getClaimHistory = async (id: number) => {
  const response = await laravelApi.get(`/insurance/claims/${id}/history`)
  return unwrapItems(unwrapResponse(response))
}

export const detectClaimAnomalies = async (id: number) => {
  const response = await laravelApi.post(`/insurance/claims/${id}/detect-anomalies`)
  return unwrapResponse(response)
}

// ── Insurance Claim Items ─────────────────────────────
export const getClaimItems = async () => {
  const response = await laravelApi.get('/insurance/claim-items')
  return unwrapItems(unwrapResponse(response))
}

export const getClaimItem = async (id: number) => {
  const response = await laravelApi.get(`/insurance/claim-items/${id}`)
  return unwrapResponse(response)
}

export const createClaimItem = async (data: object) => {
  const response = await laravelApi.post('/insurance/claim-items', data)
  return unwrapResponse(response)
}

export const updateClaimItem = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/claim-items/${id}`, data)
  return unwrapResponse(response)
}

export const deleteClaimItem = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/claim-items/${id}`)
  return unwrapResponse(response)
}

// ── Insurance Claim Documents ─────────────────────────
export const getClaimDocuments = async (claimId?: number) => {
  if (claimId) {
    const response = await laravelApi.get('/insurance/claim-documents', {
      params: { claim_id: claimId }
    })
    console.log('Documents response:', response.status, response.data)
    return unwrapItems(unwrapResponse(response))
  }
  const response = await laravelApi.get('/insurance/claim-documents')
  return unwrapItems(unwrapResponse(response))
}

export const getClaimDocument = async (id: number) => {
  const response = await laravelApi.get(`/insurance/claim-documents/${id}`)
  return unwrapResponse(response)
}

export const createClaimDocument = async (data: FormData) => {
  const response = await laravelApi.post('/insurance/claim-documents', data, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
  return unwrapResponse(response)
}

export const updateClaimDocument = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/claim-documents/${id}`, data)
  return unwrapResponse(response)
}

export const deleteClaimDocument = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/claim-documents/${id}`)
  return unwrapResponse(response)
}

// ── Insurance Claim History ──────────────────────────
export const getClaimHistoryEntries = async () => {
  const response = await laravelApi.get('/insurance/claim-history')
  return unwrapItems(unwrapResponse(response))
}

export const getClaimHistoryEntry = async (id: number) => {
  const response = await laravelApi.get(`/insurance/claim-history/${id}`)
  return unwrapResponse(response)
}

export const createClaimHistoryEntry = async (data: object) => {
  const response = await laravelApi.post('/insurance/claim-history', data)
  return unwrapResponse(response)
}

export const updateClaimHistoryEntry = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/claim-history/${id}`, data)
  return unwrapResponse(response)
}

export const deleteClaimHistoryEntry = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/claim-history/${id}`)
  return unwrapResponse(response)
}

// ── Insurance Bordereaux ─────────────────────────────
export const getBordereaux = async () => {
  const response = await laravelApi.get('/insurance/bordereaux')
  return unwrapItems(unwrapResponse(response))
}

export const getBordereau = async (id: number) => {
  const response = await laravelApi.get(`/insurance/bordereaux/${id}`)
  return unwrapResponse(response)
}

export const createBordereau = async (data: object) => {
  const response = await laravelApi.post('/insurance/bordereaux', data)
  return unwrapResponse(response)
}

export const updateBordereau = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/bordereaux/${id}`, data)
  return unwrapResponse(response)
}

export const deleteBordereau = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/bordereaux/${id}`)
  return unwrapResponse(response)
}

export const addClaimsToBordereau = async (id: number, data: object) => {
  const response = await laravelApi.post(`/insurance/bordereaux/${id}/add-claims`, data)
  return unwrapResponse(response)
}

export const submitBordereau = async (id: number) => {
  const response = await laravelApi.post(`/insurance/bordereaux/${id}/submit`)
  return unwrapResponse(response)
}

export const validateBordereau = async (id: number) => {
  const response = await laravelApi.post(`/insurance/bordereaux/${id}/validate`)
  return unwrapResponse(response)
}

export const markBordereauAsPaid = async (id: number) => {
  const response = await laravelApi.post(`/insurance/bordereaux/${id}/mark-as-paid`)
  return unwrapResponse(response)
}

export const downloadBordereauPDF = async (id: number) => {
  const response = await laravelApi.get(`/insurance/bordereaux/${id}/download-pdf`, {
    responseType: 'blob'
  })
  return response.data
}

// ── Insurance Bordereau Claims ───────────────────────
export const getBordereauClaims = async () => {
  const response = await laravelApi.get('/insurance/bordereau-claims')
  return unwrapItems(unwrapResponse(response))
}

export const getBordereauClaim = async (id: number) => {
  const response = await laravelApi.get(`/insurance/bordereau-claims/${id}`)
  return unwrapResponse(response)
}

export const createBordereauClaim = async (data: object) => {
  const response = await laravelApi.post('/insurance/bordereau-claims', data)
  return unwrapResponse(response)
}

export const updateBordereauClaim = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/bordereau-claims/${id}`, data)
  return unwrapResponse(response)
}

export const deleteBordereauClaim = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/bordereau-claims/${id}`)
  return unwrapResponse(response)
}

// ── Insurance Premiums ───────────────────────────────
export const getPremiums = async () => {
  const response = await laravelApi.get('/insurance/premiums')
  return unwrapItems(unwrapResponse(response))
}

export const getPremium = async (id: number) => {
  const response = await laravelApi.get(`/insurance/premiums/${id}`)
  return unwrapResponse(response)
}

export const createPremium = async (data: object) => {
  const response = await laravelApi.post('/insurance/premiums', data)
  return unwrapResponse(response)
}

export const updatePremium = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/premiums/${id}`, data)
  return unwrapResponse(response)
}

export const deletePremium = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/premiums/${id}`)
  return unwrapResponse(response)
}

export const calculatePremium = async (enrollmentId: number, data: object = {}) => {
  const response = await laravelApi.post(`/insurance/premiums/${enrollmentId}/calculate`, data)
  return unwrapResponse(response)
}

export const recordPayment = async (data: object) => {
  const response = await laravelApi.post('/insurance/premiums/record-payment', data)
  return unwrapResponse(response)
}

export const getPaymentHistory = async (enrollmentId: number) => {
  const response = await laravelApi.get(`/insurance/premiums/${enrollmentId}/payment-history`)
  return unwrapItems(unwrapResponse(response))
}

export const getPremiumSummary = async () => {
  const response = await laravelApi.get('/insurance/premiums/summary')
  return unwrapResponse(response)
}

// ── Insurance Plans (alias for Policies) ──────────────
export const getPlans = async () => {
  const response = await laravelApi.get('/insurance/policies')
  return unwrapItems(unwrapResponse(response))
}

export const getPlan = async (id: number) => {
  const response = await laravelApi.get(`/insurance/policies/${id}`)
  return unwrapResponse(response)
}

export const createPlan = async (data: object) => {
  const response = await laravelApi.post('/insurance/policies', data)
  return unwrapResponse(response)
}

export const updatePlan = async (id: number, data: object) => {
  const response = await laravelApi.put(`/insurance/policies/${id}`, data)
  return unwrapResponse(response)
}

export const deletePlan = async (id: number) => {
  const response = await laravelApi.delete(`/insurance/policies/${id}`)
  return unwrapResponse(response)
}

export const assignPlanToEmployees = async (data: { insurance_plan_id: number; employee_ids: number[] }) => {
  const response = await laravelApi.post('/insurance/policies/assign', data)
  return unwrapResponse(response)
}

export const assignPlanToDepartment = async (data: { insurance_plan_id: number; department_id: number }) => {
  const response = await laravelApi.post('/insurance/plans/assign-department', data)
  return unwrapResponse(response)
}

// ── Insurance Statistics ────────────────────────────
export const getInsuranceOverview = async () => {
  const response = await laravelApi.get('/insurance/statistics/overview')
  return unwrapResponse(response)
}

export const getClaimsTrends = async () => {
  const response = await laravelApi.get('/insurance/statistics/claims-trends')
  return unwrapResponse(response)
}

export const getTopProviders = async () => {
  const response = await laravelApi.get('/insurance/statistics/top-providers')
  return unwrapResponse(response)
}

export const getEmployeeStats = async (employeeId: number) => {
  const response = await laravelApi.get(`/insurance/statistics/employee/${employeeId}`)
  return unwrapResponse(response)
}

export const getCoverageAnalysis = async () => {
  const response = await laravelApi.get('/insurance/statistics/coverage-analysis')
  return unwrapResponse(response)
}

export const getComplianceReport = async () => {
  const response = await laravelApi.get('/insurance/statistics/compliance-report')
  return unwrapResponse(response)
}
