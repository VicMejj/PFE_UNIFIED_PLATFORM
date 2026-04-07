<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { TrendingUp, AlertTriangle, CheckCircle, Clock } from 'lucide-vue-next'
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

const analysts = ref<any[]>([])
const turnoverPredictions = ref<any[]>([])
const loanAssessments = ref<any[]>([])
const leaveRecommendations = ref<any[]>([])
const dashboardInsights = ref<any | null>(null)
const isLoading = ref(true)
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

const fetchEmployees = async () => {
  try {
    const response: any = await platformApi.getEmployees()
    analysts.value = (response.data || []).slice(0, 20)
  } catch (err) {
    console.error('Failed to fetch employees', err)
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

const predictTurnover = async () => {
  errorMessage.value = ''
  feedback.value = ''

  if (!turnoverForm.employee_id) {
    errorMessage.value = 'Please select an employee.'
    return
  }

  try {
    const result: any = await djangoAiApi.predictTurnover({
      tenure_years: parseFloat(turnoverForm.tenure_years) || 0,
      salary: parseFloat(turnoverForm.salary) || 0,
      complaints_count: parseInt(turnoverForm.complaints_count) || 0,
      performance: parseFloat(turnoverForm.performance) || 5,
      leaves_taken: parseInt(turnoverForm.leaves_taken) || 0
    })

    turnoverPredictions.value.unshift({
      employee_id: turnoverForm.employee_id,
      timestamp: new Date(),
      ...result
    })

    feedback.value = `Turnover prediction complete: ${getTurnoverRiskLabel(result.risk_score)}`

    // Reset form
    turnoverForm.employee_id = ''
    turnoverForm.tenure_years = ''
    turnoverForm.salary = ''
    turnoverForm.complaints_count = ''
    turnoverForm.performance = ''
    turnoverForm.leaves_taken = ''
  } catch (err: any) {
    console.error('Failed to predict turnover', err)
    errorMessage.value = err.response?.data?.detail || 'Unable to predict turnover.'
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
    const result: any = await djangoAiApi.assessLoanRisk({
      salary: parseFloat(loanForm.salary) || 0,
      amount: parseFloat(loanForm.amount) || 0,
      duration: parseInt(loanForm.duration_months) || 12,
      tenure_years: parseFloat(loanForm.tenure_years) || 0
    })

    loanAssessments.value.unshift({
      employee_id: loanForm.employee_id,
      loan_amount: parseFloat(loanForm.amount),
      timestamp: new Date(),
      ...result
    })

    feedback.value = `Loan assessment complete: ${getRiskTier(result.safety_score)} (${(result.safety_score * 100).toFixed(1)}%)`

    // Reset form
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

  try {
    feedback.value = 'Training turnover model... This may take a moment.'
    await djangoAiApi.trainTurnoverModel()
    feedback.value = 'Turnover model trained successfully!'
  } catch (err: any) {
    console.error('Failed to train model', err)
    errorMessage.value = err.response?.data?.detail || 'Unable to train model.'
  }
}

onMounted(async () => {
  await Promise.all([fetchEmployees(), fetchLeaveRecommendations(), fetchDashboardInsights()])
  isLoading.value = false
})
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-3xl font-bold tracking-tight">AI Analytics & Predictions</h2>
      <p class="text-gray-500 dark:text-gray-400">Advanced machine learning insights for HR decisions.</p>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

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

    <!-- TURNOVER PREDICTION -->
    <div class="grid gap-6 md:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <TrendingUp class="w-5 h-5 text-red-600" />
            Turnover Risk Prediction
          </CardTitle>
          <CardDescription>Predict employee turnover likelihood based on key indicators.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label>Employee</Label>
            <select v-model="turnoverForm.employee_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="">Select employee</option>
              <option v-for="emp in analysts" :key="emp.id" :value="String(emp.id)">
                {{ emp.name }} (#{{ emp.id }})
              </option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
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
              <Label>Performance (1-10)</Label>
              <Input v-model="turnoverForm.performance" type="number" placeholder="7" min="1" max="10" />
            </div>
          </div>
          <div class="space-y-2">
            <Label>Leaves Taken (this year)</Label>
            <Input v-model="turnoverForm.leaves_taken" type="number" placeholder="10" />
          </div>
          <Button class="w-full bg-red-600 hover:bg-red-700" @click="predictTurnover">
            Predict Turnover Risk
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <AlertTriangle class="w-5 h-5 text-amber-600" />
            Loan Risk Assessment
          </CardTitle>
          <CardDescription>Evaluate loan application default probability.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label>Employee</Label>
            <select v-model="loanForm.employee_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="">Select employee</option>
              <option v-for="emp in analysts" :key="emp.id" :value="String(emp.id)">
                {{ emp.name }} (#{{ emp.id }})
              </option>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
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
          <Button class="w-full bg-amber-600 hover:bg-amber-700" @click="assessLoanRisk">
            Assess Loan Risk
          </Button>
        </CardContent>
      </Card>
    </div>

    <!-- TURNOVER PREDICTIONS HISTORY -->
    <Card>
      <CardHeader>
        <CardTitle>Recent Turnover Predictions</CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="turnoverPredictions.length === 0" class="text-center py-8">
          <p class="text-gray-500">No predictions yet. Run a prediction above to get started.</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="(pred, idx) in turnoverPredictions" :key="idx" class="flex items-center justify-between border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-900/20">
            <div>
              <p class="font-semibold">Employee #{{ pred.employee_id }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ new Date(pred.timestamp).toLocaleString() }}</p>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-right">
                <p class="text-sm font-medium">{{ (pred.risk_score * 100).toFixed(1) }}%</p>
                <Badge :variant="getTurnoverRiskColor(pred.risk_score)">
                  {{ getTurnoverRiskLabel(pred.risk_score) }}
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- LOAN ASSESSMENTS HISTORY -->
    <Card>
      <CardHeader>
        <CardTitle>Recent Loan Assessments</CardTitle>
      </CardHeader>
      <CardContent>
        <div v-if="loanAssessments.length === 0" class="text-center py-8">
          <p class="text-gray-500">No assessments yet. Run an assessment above to get started.</p>
        </div>
        <div v-else class="space-y-3">
          <div v-for="(assessment, idx) in loanAssessments" :key="idx" class="flex items-center justify-between border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-900/20">
            <div>
              <p class="font-semibold">Employee #{{ assessment.employee_id }}</p>
              <p class="text-sm text-gray-600 dark:text-gray-400">Loan: ${{ assessment.loan_amount.toLocaleString() }}</p>
              <p class="text-xs text-gray-500">{{ new Date(assessment.timestamp).toLocaleString() }}</p>
            </div>
            <div class="flex items-center gap-3">
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

    <!-- LEAVE RECOMMENDATIONS -->
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
                <p class="text-xs text-gray-500 mt-2">Score: {{ (rec.score * 100).toFixed(1) }}%</p>
              </div>
              <CheckCircle class="w-5 h-5 text-green-600 flex-shrink-0" />
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- MODEL MANAGEMENT -->
    <Card>
      <CardHeader>
        <CardTitle>Model Management</CardTitle>
      </CardHeader>
      <CardContent>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
          Retrain the turnover prediction model with the latest employee data to improve accuracy.
        </p>
        <Button class="bg-blue-600 hover:bg-blue-700" @click="trainTurnoverModel">
          Train Turnover Model
        </Button>
      </CardContent>
    </Card>
  </div>
</template>
