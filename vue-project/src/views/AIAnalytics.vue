<script setup lang="ts">
import { onMounted, reactive, ref, computed } from 'vue'
import { TrendingUp, AlertTriangle, CheckCircle, Clock, BrainCircuit, ShieldAlert, PieChart, RefreshCw } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { djangoAiApi } from '@/api/django/ai'
import { platformApi } from '@/api/laravel/platform'
import { djangoApi, unwrapResponse } from '@/api/http'

const analysts = ref<any[]>([])
const turnoverPredictions = ref<any[]>([])
const loanAssessments = ref<any[]>([])
const leaveRecommendations = ref<any[]>([])
const dashboardInsights = ref<any | null>(null)
const isLoading = ref(true)
const isTraining = ref(false)
const feedback = ref('')
const errorMessage = ref('')

const turnoverForm = reactive({
  employee_id: '',
  tenure_years: '',
  salary: '',
  complaints_count: '',
  performance: '',
  leaves_taken: ''
})

const loanForm = reactive({
  employee_id: '',
  salary: '',
  amount: '',
  duration_months: '',
  tenure_years: ''
})

const getTurnoverRiskColor = (riskScore: number) => {
  if (riskScore < 0.3) return 'success'
  if (riskScore < 0.6) return 'warning'
  return 'destructive'
}

const getTurnoverRiskLabel = (riskScore: number) => {
  if (riskScore < 0.3) return 'Low Risk'
  if (riskScore < 0.6) return 'Medium Risk'
  return 'High Risk'
}

const getRiskTier = (safetyScore: number) => {
  if (safetyScore > 0.8) return 'Safe'
  if (safetyScore > 0.5) return 'Moderate'
  return 'Risky'
}

const getRiskColor = (safetyScore: number) => {
  if (safetyScore > 0.8) return 'success'
  if (safetyScore > 0.5) return 'warning'
  return 'destructive'
}

const turnoverStats = computed(() => {
  const total = turnoverPredictions.value.length
  if (total === 0) return { low: 0, medium: 0, high: 0, average: 0 }
  
  let low = 0, medium = 0, high = 0, sum = 0
  turnoverPredictions.value.forEach(p => {
    const score = Number(p.risk_score ?? p.prediction_score ?? 0)
    sum += score
    if (score < 0.3) low++
    else if (score < 0.6) medium++
    else high++
  })
  
  return {
    low,
    medium,
    high,
    average: Math.round((sum / total) * 100)
  }
})

const turnoverTierDistribution = computed(() => {
  const total = turnoverPredictions.value.length || 1
  return [
    { name: 'Low Risk', count: turnoverStats.value.low, color: 'text-emerald-500', bg: 'bg-emerald-500', percentage: (turnoverStats.value.low / total) * 100 },
    { name: 'Medium Risk', count: turnoverStats.value.medium, color: 'text-amber-500', bg: 'bg-amber-500', percentage: (turnoverStats.value.medium / total) * 100 },
    { name: 'High Risk', count: turnoverStats.value.high, color: 'text-rose-500', bg: 'bg-rose-500', percentage: (turnoverStats.value.high / total) * 100 },
  ]
})

const highRiskEmployees = computed(() => {
  return turnoverPredictions.value
    .filter(p => Number(p.risk_score ?? p.prediction_score ?? 0) >= 0.6)
    .slice(0, 5)
})

function normalizeEmployees(payload: any): any[] {
  const list =
    Array.isArray(payload) ? payload :
    Array.isArray(payload?.data) ? payload.data :
    Array.isArray(payload?.results) ? payload.results :
    []

  return list
    .map((employee: any) => {
      const id = employee?.id ?? employee?.employee_id ?? employee?.user_id
      const name = employee?.name ?? [employee?.first_name, employee?.last_name].filter(Boolean).join(' ').trim()
      if (!id || !name) return null
      return {
        id: String(id),
        name,
        department: employee?.department?.name ?? employee?.department ?? '',
      }
    })
    .filter(Boolean)
}

function normalizeTurnoverResult(result: any) {
  const score = Number(
    result?.risk_score ??
    result?.prediction_score ??
    result?.risk ??
    result?.score ??
    0
  )
  return {
    ...result,
    risk_score: Number.isFinite(score) ? Math.max(0, Math.min(1, score)) : 0,
  }
}

function normalizeLoanResult(result: any) {
  const rawScore = Number(
    result?.safety_score ??
    result?.risk_score ??
    result?.score ??
    result?.probability_of_default ??
    result?.default_probability ??
    0
  )
  const score = Number.isFinite(rawScore)
    ? (rawScore > 1 ? rawScore / 100 : rawScore)
    : 0
  return {
    ...result,
    safety_score: Number.isFinite(score) ? Math.max(0, Math.min(1, score)) : 0,
  }
}

const fetchEmployees = async () => {
  try {
    const platformEmployees = normalizeEmployees(await platformApi.getEmployees())
    if (platformEmployees.length) {
      analysts.value = platformEmployees.slice(0, 20)
      return
    }
  } catch (err) {
    console.error('Failed to fetch employees from platform API', err)
  }

  try {
    const djangoEmployees = normalizeEmployees(unwrapResponse(await djangoApi.get('/gestion_rh/employees')))
    analysts.value = djangoEmployees.slice(0, 20)
  } catch (err) {
    console.error('Failed to fetch employees from Django fallback', err)
  }
}

const fetchLeaveRecommendations = async () => {
  try {
    const response: any = await djangoAiApi.getOptimalLeaveDates()
    leaveRecommendations.value = [
      ...(response.recommended_windows || []).map((item: any) => ({
        start_date: item.start,
        end_date: item.end,
        reason: `AI-selected ${item.duration_days}-day window with average suitability ${item.avg_suitability}.`,
        score: item.avg_suitability
      })),
      ...(response.recommended_single_days || []).slice(0, 3).map((item: any) => ({
        start_date: item.date,
        end_date: item.date,
        reason: `${item.weekday} with low predicted workload.`,
        score: item.suitability_score
      }))
    ]
  } catch (err) {
    console.error('Failed to fetch leave recommendations', err)
    errorMessage.value = 'Unable to load leave recommendations.'
  }
}

const fetchDashboardInsights = async () => {
  try {
    dashboardInsights.value = await djangoAiApi.getDashboardInsights()
  } catch (err) {
    console.error('Failed to fetch dashboard insights', err)
  }
}

const loadHistory = async () => {
  try {
    const [turnoverHistory, loanHistory] = await Promise.all([
      djangoAiApi.getTurnoverHistory(),
      djangoAiApi.getLoanHistory(),
    ])
    turnoverPredictions.value = (Array.isArray(turnoverHistory) ? turnoverHistory : []).map((item: any) => ({
      ...item,
      timestamp: item.created_at || item.timestamp || new Date().toISOString(),
      risk_score: Number(item.prediction_score ?? item.risk_score ?? 0),
    }))
    loanAssessments.value = (Array.isArray(loanHistory) ? loanHistory : []).map((item: any) => ({
      ...item,
      timestamp: item.created_at || item.timestamp || new Date().toISOString(),
      safety_score: normalizeLoanResult(item).safety_score,
      loan_amount: Number(item.input_data?.amount ?? item.loan_amount ?? 0),
    }))
  } catch (err) {
    console.error('Failed to load AI histories from Django', err)
  }
}

const predictTurnover = async () => {
  errorMessage.value = ''
  feedback.value = ''

  if (!turnoverForm.employee_id) {
    errorMessage.value = 'Please select an employee.'
    return
  }

  try {
    const result: any = normalizeTurnoverResult(await djangoAiApi.predictTurnover({
      employee_id: turnoverForm.employee_id,
      tenure_years: parseFloat(turnoverForm.tenure_years) || 0,
      salary: parseFloat(turnoverForm.salary) || 0,
      complaints_count: parseInt(turnoverForm.complaints_count) || 0,
      performance: parseFloat(turnoverForm.performance) || 5,
      leaves_taken: parseInt(turnoverForm.leaves_taken) || 0
    }))

    turnoverPredictions.value.unshift({
      employee_id: turnoverForm.employee_id,
      timestamp: new Date(),
      ...result
    })

    feedback.value = `Turnover prediction complete: ${getTurnoverRiskLabel(result.risk_score)}`

    turnoverForm.employee_id = ''
    turnoverForm.tenure_years = ''
    turnoverForm.salary = ''
    turnoverForm.complaints_count = ''
    turnoverForm.performance = ''
    turnoverForm.leaves_taken = ''
  } catch (err: any) {
    const isConnectionRefused =
      err?.code === 'ERR_NETWORK' ||
      err?.code === 'ECONNABORTED' ||
      String(err?.message || '').toLowerCase().includes('connection refused')

    errorMessage.value = isConnectionRefused
      ? 'Turnover AI is temporarily offline. Your other analytics remain available.'
      : err.response?.data?.detail || 'Unable to predict turnover.'

    if (!isConnectionRefused) {
      console.warn('Failed to predict turnover', err)
    }
  }
}

const assessLoanRisk = async () => {
  errorMessage.value = ''
  feedback.value = ''

  if (!loanForm.employee_id || !loanForm.amount) {
    errorMessage.value = 'Please fill in all required fields.'
    return
  }

  try {
    const result: any = normalizeLoanResult(await djangoAiApi.assessLoanRisk({
      employee_id: loanForm.employee_id,
      salary: parseFloat(loanForm.salary) || 0,
      amount: parseFloat(loanForm.amount) || 0,
      duration: parseInt(loanForm.duration_months) || 12,
      tenure_years: parseFloat(loanForm.tenure_years) || 0
    }))

    loanAssessments.value.unshift({
      employee_id: loanForm.employee_id,
      loan_amount: parseFloat(loanForm.amount),
      timestamp: new Date(),
      ...result
    })

    feedback.value = `Loan assessment complete: ${getRiskTier(result.safety_score)} (${(result.safety_score * 100).toFixed(1)}%)`

    loanForm.employee_id = ''
    loanForm.salary = ''
    loanForm.amount = ''
    loanForm.duration_months = ''
    loanForm.tenure_years = ''
  } catch (err: any) {
    console.error('Failed to assess loan risk', err)
    errorMessage.value = err.response?.data?.detail || 'Unable to assess loan risk.'
  }
}

const trainTurnoverModel = async () => {
  errorMessage.value = ''
  feedback.value = ''
  isTraining.value = true

  try {
    feedback.value = 'Training turnover model... This may take a moment.'
    console.log('Starting model training...')
    
    const result: any = await djangoAiApi.trainTurnoverModel()
    console.log('Training result:', result)
    
    feedback.value = result?.message || 'Turnover model trained successfully!'
  } catch (err: any) {
    console.error('Failed to train model', err)
    const errorData = err.response?.data
    if (errorData?.detail) {
      errorMessage.value = errorData.detail
    } else if (errorData?.error) {
      errorMessage.value = errorData.error
    } else {
      errorMessage.value = err.message || 'Unable to train model. Make sure you are logged in.'
    }
  } finally {
    isTraining.value = false
  }
}

const refreshData = async () => {
  isLoading.value = true
  await Promise.all([loadHistory(), fetchEmployees(), fetchLeaveRecommendations(), fetchDashboardInsights()])
  isLoading.value = false
  feedback.value = 'Data refreshed successfully!'
}

onMounted(async () => {
  await loadHistory()
  await Promise.all([fetchEmployees(), fetchLeaveRecommendations(), fetchDashboardInsights()])
  isLoading.value = false
})
</script>

<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <div class="flex items-center gap-2 text-indigo-600 font-semibold mb-1">
          <BrainCircuit class="h-5 w-5" />
          <span class="text-sm tracking-wider uppercase">AI Analytics</span>
        </div>
        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">Turnover & Risk Predictions</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-2xl">
          Machine learning insights for HR decisions. Predict employee turnover likelihood and assess loan risk.
        </p>
      </div>
      <Button variant="outline" class="group border-slate-200 dark:border-slate-800" :disabled="isLoading" @click="refreshData">
        <RefreshCw class="mr-2 h-4 w-4 transition-transform group-hover:rotate-180" :class="{ 'animate-spin': isLoading }" />
        {{ isLoading ? 'Loading...' : 'Refresh Data' }}
      </Button>
    </div>

    <!-- Feedback Messages -->
    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <!-- Dashboard Insights -->
    <div v-if="dashboardInsights" class="grid gap-4 md:grid-cols-3">
      <Card>
        <CardHeader>
          <CardTitle>Burnout Signals</CardTitle>
          <CardDescription>Employees who may need workload support.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="dashboardInsights.burnout_risk_employees?.length" class="space-y-3">
            <div v-for="employee in dashboardInsights.burnout_risk_employees" :key="employee.employee_id" class="rounded-lg border p-3">
              <div class="font-medium">{{ employee.name }}</div>
              <div class="text-sm text-slate-500">Risk score: {{ Math.round(employee.risk_score * 100) }}%</div>
            </div>
          </div>
          <p v-else class="text-sm text-slate-500">No burnout alerts right now.</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Anomaly Alerts</CardTitle>
          <CardDescription>Operational signals detected by the AI layer.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="dashboardInsights.anomaly_alerts?.length" class="space-y-3">
            <div v-for="(alert, idx) in dashboardInsights.anomaly_alerts" :key="idx" class="rounded-lg border p-3">
              <div class="font-medium capitalize">{{ alert.type || 'Alert' }}</div>
              <div class="text-sm text-slate-500">{{ alert.message }}</div>
            </div>
          </div>
          <p v-else class="text-sm text-slate-500">No anomalies detected.</p>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Workload Windows</CardTitle>
          <CardDescription>Suggested low-impact periods for leave planning.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="dashboardInsights.low_workload_windows?.length" class="space-y-3">
            <div v-for="window in dashboardInsights.low_workload_windows" :key="window" class="rounded-lg border p-3">
              <div class="font-medium">{{ window }}</div>
            </div>
          </div>
          <p v-else class="text-sm text-slate-500">No low-workload windows reported.</p>
        </CardContent>
      </Card>
    </div>

    <!-- Turnover Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <Card class="relative overflow-hidden border-none bg-gradient-to-br from-red-500 to-red-600 text-white shadow-xl shadow-red-200 dark:shadow-none">
        <CardContent class="pt-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-red-100 text-sm font-medium">Total Predictions</p>
              <h3 class="text-4xl font-bold mt-1">{{ turnoverPredictions.length }}</h3>
            </div>
            <div class="rounded-xl bg-white/20 p-3">
              <TrendingUp class="h-6 w-6" />
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm text-red-100">
            <span>Employee turnover analyzed</span>
          </div>
        </CardContent>
      </Card>

      <Card class="border-none bg-white dark:bg-slate-900 shadow-sm">
        <CardContent class="pt-6 text-center">
          <div class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-500/10 p-3 mb-4">
            <ShieldAlert class="h-6 w-6 text-emerald-600" />
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-sm">Average Risk</p>
          <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ turnoverStats.average }}%</h3>
        </CardContent>
      </Card>

      <Card class="border-none bg-white dark:bg-slate-900 shadow-sm">
        <CardContent class="pt-6 text-center">
          <div class="inline-flex rounded-full bg-amber-50 dark:bg-amber-500/10 p-3 mb-4">
            <AlertTriangle class="h-6 w-6 text-amber-600" />
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-sm">High Risk</p>
          <h3 class="text-3xl font-bold text-amber-600 mt-1">{{ turnoverStats.high }}</h3>
        </CardContent>
      </Card>

      <Card class="border-none bg-white dark:bg-slate-900 shadow-sm">
        <CardContent class="pt-6 text-center">
          <div class="inline-flex rounded-full bg-blue-50 dark:bg-blue-500/10 p-3 mb-4">
            <PieChart class="h-6 w-6 text-blue-600" />
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-sm">Low Risk</p>
          <h3 class="text-3xl font-bold text-blue-600 mt-1">{{ turnoverStats.low }}</h3>
        </CardContent>
      </Card>
    </div>

    <!-- Turnover Prediction Form & Risk Distribution -->
    <div class="grid gap-6 lg:grid-cols-3">
      <!-- Prediction Form -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <TrendingUp class="w-5 h-5 text-red-600" />
            Turnover Risk Prediction
          </CardTitle>
          <CardDescription>Predict employee turnover likelihood based on key indicators.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
              <Label>Employee</Label>
              <select v-model="turnoverForm.employee_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
                <option value="">Select employee</option>
                <option v-for="emp in analysts" :key="emp.id" :value="String(emp.id)">
                  {{ emp.name }}{{ emp.department ? ` • ${emp.department}` : '' }}
                </option>
              </select>
            </div>
            <div class="space-y-2">
              <Label>Performance (1-10)</Label>
              <Input v-model="turnoverForm.performance" type="number" placeholder="7" min="1" max="10" />
            </div>
            <div class="space-y-2">
              <Label>Tenure (years)</Label>
              <Input v-model="turnoverForm.tenure_years" type="number" placeholder="5" />
            </div>
            <div class="space-y-2">
              <Label>Salary</Label>
              <Input v-model="turnoverForm.salary" type="number" placeholder="5000" />
            </div>
            <div class="space-y-2">
              <Label>Complaints</Label>
              <Input v-model="turnoverForm.complaints_count" type="number" placeholder="0" />
            </div>
            <div class="space-y-2">
              <Label>Leaves Taken (this year)</Label>
              <Input v-model="turnoverForm.leaves_taken" type="number" placeholder="10" />
            </div>
          </div>
          <Button class="w-full bg-red-600 hover:bg-red-700" @click="predictTurnover">
            Predict Turnover Risk
          </Button>
        </CardContent>
      </Card>

      <!-- Risk Distribution -->
      <Card class="shadow-sm border-slate-100 dark:border-slate-800">
        <CardHeader>
          <CardTitle class="text-lg">Risk Distribution</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
          <div v-for="tier in turnoverTierDistribution" :key="tier.name" class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span class="font-semibold text-slate-700 dark:text-slate-300">{{ tier.name }}</span>
              <span :class="tier.color">{{ tier.count }} ({{ Math.round(tier.percentage) }}%)</span>
            </div>
            <div class="h-3 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
              <div :class="`h-full ${tier.bg} transition-all duration-500`" :style="`width: ${tier.percentage}%`" />
            </div>
          </div>
          <p v-if="!turnoverPredictions.length" class="text-center text-slate-400 text-sm py-4">
            No predictions yet. Run a prediction to see distribution.
          </p>
        </CardContent>
      </Card>
    </div>

    <!-- High Risk Employees Alert -->
    <Card v-if="highRiskEmployees.length" class="border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/20">
      <CardHeader class="flex flex-row items-center justify-between">
        <CardTitle class="text-lg flex items-center gap-2">
          <AlertTriangle class="h-5 w-5 text-rose-500" />
          High Risk Employees
        </CardTitle>
        <Badge variant="destructive">{{ highRiskEmployees.length }} Critical</Badge>
      </CardHeader>
      <CardContent>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="emp in highRiskEmployees" :key="emp.employee_id" class="flex items-center justify-between p-4 rounded-2xl bg-white dark:bg-slate-900 shadow-sm">
            <div>
              <h4 class="font-bold text-slate-900 dark:text-white">Employee #{{ emp.employee_id }}</h4>
              <p class="text-xs text-slate-400 mt-1">{{ new Date(emp.timestamp).toLocaleDateString() }}</p>
            </div>
            <div class="text-right">
              <span class="text-2xl font-black text-rose-600">{{ Math.round(Number(emp.risk_score ?? emp.prediction_score ?? 0) * 100) }}%</span>
              <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mt-1">Risk Score</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Loan Risk Assessment -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <AlertTriangle class="w-5 h-5 text-amber-600" />
          Loan Risk Assessment
        </CardTitle>
        <CardDescription>Evaluate loan application default probability.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <div class="space-y-2">
            <Label>Employee</Label>
            <select v-model="loanForm.employee_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="">Select employee</option>
              <option v-for="emp in analysts" :key="emp.id" :value="String(emp.id)">
                {{ emp.name }}{{ emp.department ? ` • ${emp.department}` : '' }}
              </option>
            </select>
          </div>
          <div class="space-y-2">
            <Label>Monthly Salary</Label>
            <Input v-model="loanForm.salary" type="number" placeholder="5000" />
          </div>
          <div class="space-y-2">
            <Label>Loan Amount</Label>
            <Input v-model="loanForm.amount" type="number" placeholder="50000" />
          </div>
          <div class="space-y-2">
            <Label>Duration (months)</Label>
            <Input v-model="loanForm.duration_months" type="number" placeholder="24" />
          </div>
          <div class="space-y-2">
            <Label>Tenure (years)</Label>
            <Input v-model="loanForm.tenure_years" type="number" placeholder="3" />
          </div>
        </div>
        <Button class="bg-amber-600 hover:bg-amber-700" @click="assessLoanRisk">
          Assess Loan Risk
        </Button>
      </CardContent>
    </Card>

    <!-- Turnover Predictions History -->
    <div class="grid gap-6 lg:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>Recent Turnover Predictions</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="turnoverPredictions.length === 0" class="text-center py-8">
            <p class="text-gray-500">No predictions yet. Run a prediction above to get started.</p>
          </div>
          <div v-else class="space-y-3">
            <div v-for="(pred, idx) in turnoverPredictions.slice(0, 8)" :key="idx" class="flex items-center justify-between border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-900/20">
              <div>
                <p class="font-semibold">Employee #{{ pred.employee_id }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ new Date(pred.timestamp).toLocaleString() }}</p>
              </div>
              <div class="flex items-center gap-3">
                <div class="h-2 w-24 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div 
                    :class="getTurnoverRiskColor(Number(pred.risk_score ?? pred.prediction_score ?? 0)) === 'success' ? 'bg-emerald-500' : getTurnoverRiskColor(Number(pred.risk_score ?? pred.prediction_score ?? 0)) === 'warning' ? 'bg-amber-500' : 'bg-rose-500'"
                    class="h-full transition-all duration-500" 
                    :style="`width: ${(Number(pred.risk_score ?? pred.prediction_score ?? 0) * 100)}%`" 
                  />
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium">{{ Math.round(Number(pred.risk_score ?? pred.prediction_score ?? 0) * 100) }}%</p>
                  <Badge :variant="getTurnoverRiskColor(Number(pred.risk_score ?? pred.prediction_score ?? 0))">
                    {{ getTurnoverRiskLabel(Number(pred.risk_score ?? pred.prediction_score ?? 0)) }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Loan Assessments History -->
      <Card>
        <CardHeader>
          <CardTitle>Recent Loan Assessments</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="loanAssessments.length === 0" class="text-center py-8">
            <p class="text-gray-500">No assessments yet. Run an assessment above to get started.</p>
          </div>
          <div v-else class="space-y-3">
            <div v-for="(assessment, idx) in loanAssessments.slice(0, 8)" :key="idx" class="flex items-center justify-between border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-900/20">
              <div>
                <p class="font-semibold">Employee #{{ assessment.employee_id }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Loan: ${{ assessment.loan_amount.toLocaleString() }}</p>
                <p class="text-xs text-gray-500">{{ new Date(assessment.timestamp).toLocaleString() }}</p>
              </div>
              <div class="flex items-center gap-3">
                <div class="h-2 w-24 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                  <div 
                    :class="getRiskColor(assessment.safety_score) === 'success' ? 'bg-emerald-500' : getRiskColor(assessment.safety_score) === 'warning' ? 'bg-amber-500' : 'bg-rose-500'"
                    class="h-full transition-all duration-500" 
                    :style="`width: ${(assessment.safety_score * 100)}%`" 
                  />
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium">{{ (assessment.safety_score * 100).toFixed(1) }}%</p>
                  <Badge :variant="getRiskColor(assessment.safety_score)">
                    {{ getRiskTier(assessment.safety_score) }}
                  </Badge>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Leave Recommendations -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Clock class="w-5 h-5 text-blue-600" />
          Optimal Leave Recommendations
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="leaveRecommendations.length === 0" class="text-center py-8">
          <p class="text-gray-500">No leave recommendations available.</p>
        </div>
        <div v-else class="grid gap-4 md:grid-cols-2">
          <div v-for="(rec, idx) in leaveRecommendations" :key="idx" class="border rounded-lg p-4 bg-blue-50 dark:bg-blue-950/20">
            <div class="flex items-start justify-between">
              <div>
                <p v-if="rec.start_date" class="font-semibold">{{ rec.start_date }} to {{ rec.end_date }}</p>
                <p v-if="rec.reason" class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ rec.reason }}</p>
                <div class="mt-3">
                  <div class="h-2 w-full bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                    <div class="h-full bg-blue-500 transition-all duration-500" :style="`width: ${(rec.score * 100)}%`" />
                  </div>
                  <p class="text-xs text-slate-500 mt-1">Suitability: {{ Math.round(rec.score * 100) }}%</p>
                </div>
              </div>
              <CheckCircle class="w-5 h-5 text-green-600 flex-shrink-0" />
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Model Management -->
    <Card>
      <CardHeader>
        <CardTitle>Model Management</CardTitle>
      </CardHeader>
      <CardContent>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
          Retrain the turnover prediction model with the latest employee data to improve accuracy.
        </p>
        <Button class="bg-blue-600 hover:bg-blue-700" :disabled="isTraining" @click="trainTurnoverModel">
          <span v-if="isTraining">Training...</span>
          <span v-else>Train Turnover Model</span>
        </Button>
      </CardContent>
    </Card>
  </div>
</template>