<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Gift, Plus, Trash2, AlertTriangle, CheckCircle, XCircle, Clock, Zap } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const auth = useAuthStore()
const notifications = useNotificationsStore()
const employees = ref<any[]>([])
const allowanceOptions = ref<any[]>([])
const employeeAllowances = ref<any[]>([])
const benefitRecommendations = ref<any[]>([])
const isLoading = ref(true)
const recommendationsLoading = ref(false)
const feedback = ref('')
const errorMsg = ref('')
const selectedEmployee = ref<any | null>(null)
const selectedEmployeeScore = ref<any | null>(null)
const searchQuery = ref('')
const confirmOpen = ref(false)
const scoreConfirmOpen = ref(false)
const pendingRemoval = ref<any | null>(null)
const recommendationFeedback = ref('')
const statusConfirmOpen = ref(false)
const pendingStatusChange = ref<any | null>(null)
const isUpdatingStatus = ref(false)

const form = reactive({
  employee_id: '',
  allowance_option_id: '',
  amount: '',
  start_date: '',
  end_date: '',
  status: 'active'
})

const userRoles = computed(() =>
  [auth.user?.role, ...(auth.user?.allRoles ?? [])]
    .filter(Boolean)
    .map((role) => String(role).toLowerCase())
)

const getEmployeeDisplayName = (employee: any) => {
  const fullName = [employee?.first_name, employee?.last_name].filter(Boolean).join(' ').trim()

  return employee?.name
    || employee?.full_name
    || fullName
    || employee?.email
    || employee?.employee_id
    || `Employee #${employee?.id ?? 'Unknown'}`
}

const selectedEmployeeSummary = computed(() => {
  const active = employeeAllowances.value.filter((item) => item.status === 'active').length
  const pending = employeeAllowances.value.filter((item) => item.status === 'pending').length
  const inactive = employeeAllowances.value.filter((item) => item.status === 'inactive').length

  return {
    total: employeeAllowances.value.length,
    active,
    pending,
    inactive,
  }
})

const filteredEmployees = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return employees.value

  return employees.value.filter((emp) =>
    [emp.display_name, emp.name, emp.full_name, emp.email, emp.employee_id]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

const canManageBenefits = computed(() =>
  userRoles.value.some((role) => ['admin', 'rh_manager', 'rh', 'hr', 'manager'].includes(role))
)

const recommendationStatusVariant = (status: string) => {
  switch (status) {
    case 'eligible':
      return 'success'
    case 'nearly_eligible':
      return 'warning'
    default:
      return 'secondary'
  }
}

const resolveBenefitName = (benefitId: number | string | undefined) => {
  const option = allowanceOptions.value.find((item) => Number(item.id) === Number(benefitId))
  return option?.name || `Benefit #${benefitId}`
}

async function loadData() {
  isLoading.value = true
  try {
    const [empData, optData] = await Promise.all([
      platformApi.getEmployees(),
      platformApi.getAllowanceOptions()
    ])

    employees.value = unwrapItems<any>(empData).map((employee: any) => ({
      ...employee,
      display_name: getEmployeeDisplayName(employee)
    }))
    allowanceOptions.value = unwrapItems<any>(optData)
  } catch (err) {
    errorMsg.value = 'Unable to load data right now.'
    console.error('Load failed', err)
  } finally {
    isLoading.value = false
  }
}

async function selectEmployee(employee: any) {
  selectedEmployee.value = employee
  form.employee_id = String(employee.id)
  recommendationsLoading.value = true
  selectedEmployeeScore.value = null

  try {
    const [allowances, recommendations, scoreData] = await Promise.all<any>([
      platformApi.getEmployeeAllowances(employee.id),
      platformApi.getBenefitRecommendations(employee.id),
      platformApi.getEmployeeScore(employee.id)
    ])
    selectedEmployeeScore.value = scoreData?.score || scoreData
    const allowanceItems = Array.isArray(allowances)
      ? allowances
      : Array.isArray(allowances?.data)
        ? allowances.data
        : []

    employeeAllowances.value = allowanceItems.map((item: any) => ({
      ...item,
      benefit_name: item.allowanceOption?.name || item.allowance_option?.name || item.name || 'Benefit',
      amount: item.amount ? `$${Number(item.amount).toFixed(2)}` : 'N/A',
      start_date: item.start_date || item.start_date || 'N/A',
      status: item.status || 'active'
    }))
    benefitRecommendations.value = Array.isArray(recommendations)
      ? recommendations
      : Array.isArray(recommendations?.data)
        ? recommendations.data
        : []
  } catch (err) {
    employeeAllowances.value = []
    benefitRecommendations.value = []
    console.error('Failed to load employee allowances', err)
  } finally {
    recommendationsLoading.value = false
  }
}

async function assignBenefit(force = false) {
  if (!form.employee_id || !form.allowance_option_id || !form.amount || !form.start_date) {
    errorMsg.value = 'Please fill all required fields.'
    return
  }

  try {
    await platformApi.assignAllowance({
      employee_id: Number(form.employee_id),
      allowance_option_id: Number(form.allowance_option_id),
      amount: Number(form.amount),
      start_date: form.start_date,
      end_date: form.end_date || null,
      status: form.status,
      force: force
    })

    feedback.value = 'Benefit assigned successfully!'
    errorMsg.value = ''
    scoreConfirmOpen.value = false
    resetForm()
    if (selectedEmployee.value) {
      await selectEmployee(selectedEmployee.value)
    }
  } catch (err: any) {
    if (err.response?.status === 422 && err.response?.data?.data?.requires_force) {
      scoreConfirmOpen.value = true
    } else {
      errorMsg.value = err.response?.data?.message || 'Unable to assign benefit.'
    }
    console.error('Assign failed', err)
  } finally {
    notifications.fetchNotifications()
  }
}

function openRemoveConfirmation(item: any) {
  pendingRemoval.value = item
  confirmOpen.value = true
}

function closeRemoveConfirmation() {
  pendingRemoval.value = null
  confirmOpen.value = false
}

async function confirmRemoveBenefit() {
  if (!pendingRemoval.value) return

  try {
    await platformApi.removeAllowance(pendingRemoval.value.id)
    feedback.value = 'Benefit removed successfully!'
    errorMsg.value = ''
    pendingRemoval.value = null
    confirmOpen.value = false
    if (selectedEmployee.value) {
      await selectEmployee(selectedEmployee.value)
    }
  } catch (err: any) {
    errorMsg.value = err.response?.data?.message || 'Unable to remove benefit.'
    console.error('Remove failed', err)
  } finally {
    notifications.fetchNotifications()
  }
}

function resetForm() {
  form.employee_id = selectedEmployee.value?.id ? String(selectedEmployee.value.id) : ''
  form.allowance_option_id = ''
  form.amount = ''
  form.start_date = ''
  form.end_date = ''
  form.status = 'active'
}

function applyRecommendation(recommendation: any) {
  if (!selectedEmployee.value) {
    return
  }

  form.employee_id = String(selectedEmployee.value.id)
  form.allowance_option_id = recommendation?.benefit_id ? String(recommendation.benefit_id) : ''
  form.status = recommendation?.status === 'eligible' ? 'active' : 'pending'
  recommendationFeedback.value = recommendation?.benefit_id
    ? `${resolveBenefitName(recommendation.benefit_id)} was added to the assignment form.`
    : 'Recommendation applied to the assignment form.'
}

function getStatusVariant(status: string) {
  switch (status) {
    case 'active':
      return 'success'
    case 'pending':
      return 'warning'
    case 'inactive':
      return 'secondary'
    case 'claimed':
      return 'info'
    default:
      return 'secondary'
  }
}

function getStatusIcon(status: string) {
  switch (status) {
    case 'active':
      return CheckCircle
    case 'pending':
      return Clock
    case 'inactive':
      return XCircle
    default:
      return Clock
  }
}

function getStatusLabel(status: string, claimed?: boolean) {
  if (status === 'active' && claimed) {
    return 'Claimed'
  }
  return status.charAt(0).toUpperCase() + status.slice(1)
}

function openStatusChange(item: any, newStatus: string) {
  pendingStatusChange.value = { ...item, newStatus }
  statusConfirmOpen.value = true
}

function closeStatusChange() {
  pendingStatusChange.value = null
  statusConfirmOpen.value = false
}

async function confirmStatusChange() {
  if (!pendingStatusChange.value) return

  isUpdatingStatus.value = true
  try {
    await platformApi.updateAllowanceStatus(pendingStatusChange.value.id, pendingStatusChange.value.newStatus)
    feedback.value = `Benefit status changed to "${pendingStatusChange.value.newStatus}" successfully!`
    errorMsg.value = ''
    pendingStatusChange.value = null
    statusConfirmOpen.value = false
    if (selectedEmployee.value) {
      await selectEmployee(selectedEmployee.value)
    }
  } catch (err: any) {
    errorMsg.value = err.response?.data?.message || 'Unable to update benefit status.'
    console.error('Status update failed', err)
  } finally {
    isUpdatingStatus.value = false
    notifications.fetchNotifications()
  }
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <Gift class="w-8 h-8 text-emerald-600" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Employee Benefits</h2>
          <p class="text-gray-500 dark:text-gray-400">Assign and manage employee benefits and allowances.</p>
        </div>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="recommendationFeedback" class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300">
      {{ recommendationFeedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr] scroll-smooth">
      <!-- Employee List -->
      <Card>
        <CardHeader>
          <CardTitle>Select Employee</CardTitle>
          <CardDescription>Choose an employee to manage their benefits.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <Input
            v-model="searchQuery"
            placeholder="Search employees by name or ID..."
            class="w-full"
          />
          <div class="border rounded-lg max-h-96 overflow-y-auto">
            <div
              v-for="emp in filteredEmployees"
              :key="emp.id"
              :class="[
                'p-3 cursor-pointer border-b transition',
                selectedEmployee?.id === emp.id
                  ? 'bg-blue-50 dark:bg-blue-950/30 border-l-4 border-l-blue-600'
                  : 'hover:bg-slate-50 dark:hover:bg-slate-900'
              ]"
              @click="selectEmployee(emp)"
            >
              <div class="font-semibold text-sm">{{ emp.display_name }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ emp.email }}</div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Assign Benefit Form -->
      <Card v-if="selectedEmployee && canManageBenefits">
        <CardHeader>
          <CardTitle>Assign Benefit</CardTitle>
          <CardDescription>
            Assigning to: <span class="font-semibold">{{ selectedEmployee.display_name }}</span>
          </CardDescription>

          <!-- Performance Context Header -->
          <div v-if="selectedEmployeeScore" class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Badge :variant="selectedEmployeeScore.overall_score >= 85 ? 'success' : selectedEmployeeScore.overall_score >= 70 ? 'default' : 'warning'" class="h-8 w-8 rounded-lg p-0 flex items-center justify-center text-lg font-bold">
                  {{ Math.round(selectedEmployeeScore.overall_score) }}
                </Badge>
                <div>
                  <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Holistic Score</div>
                  <div class="text-sm font-semibold capitalize">{{ selectedEmployeeScore.score_tier }} Performance</div>
                </div>
              </div>
              <div class="text-right">
                <div class="text-[10px] text-slate-500">Last calculated</div>
                <div class="text-xs font-medium">{{ new Date(selectedEmployeeScore.last_calculated_at).toLocaleDateString() }}</div>
              </div>
            </div>
          </div>

          <div class="mt-4 flex flex-wrap gap-2">
            <span class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">
              Total benefits: {{ selectedEmployeeSummary.total }}
            </span>
            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-200">
              Active: {{ selectedEmployeeSummary.active }}
            </span>
            <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
              Pending: {{ selectedEmployeeSummary.pending }}
            </span>
            <span class="rounded-full border border-slate-200 bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300">
              Inactive: {{ selectedEmployeeSummary.inactive }}
            </span>
          </div>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label>Benefit Type *</Label>
            <select
              v-model="form.allowance_option_id"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
              <option value="">Select benefit</option>
              <option
                v-for="option in allowanceOptions"
                :key="option.id"
                :value="String(option.id)"
              >
                {{ option.name }}
              </option>
            </select>
          </div>

          <div class="space-y-2">
            <Label>Amount ($) *</Label>
            <Input
              v-model="form.amount"
              type="number"
              placeholder="0.00"
              min="0"
              step="0.01"
            />
          </div>

          <div class="space-y-2">
            <Label>Start Date *</Label>
            <Input
              v-model="form.start_date"
              type="date"
            />
          </div>

          <div class="space-y-2">
            <Label>End Date</Label>
            <Input
              v-model="form.end_date"
              type="date"
            />
          </div>

          <div class="space-y-2">
            <Label>Status</Label>
            <select
              v-model="form.status"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="pending">Pending</option>
            </select>
          </div>

          <div class="flex gap-2">
            <Button @click="assignBenefit" class="flex-1 bg-emerald-600 hover:bg-emerald-700">
              <Plus class="w-4 h-4 mr-2" />
              Assign Benefit
            </Button>
            <Button
              @click="resetForm"
              variant="outline"
              class="flex-1"
            >
              Clear
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <div v-if="!selectedEmployee" class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center dark:border-slate-700 dark:bg-slate-950/40">
      <p class="text-lg font-semibold text-slate-900 dark:text-white">Select an employee to manage benefits.</p>
      <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Click a row on the left to open their benefit history, assign a new allowance, or remove existing benefits.</p>
    </div>

    <!-- Employee Benefits List -->
    <Card v-if="selectedEmployee">
      <CardHeader>
        <CardTitle>AI Benefit Recommendations</CardTitle>
        <CardDescription>
          Personalized eligibility guidance for {{ selectedEmployee.display_name }} based on performance, attendance, and tenure.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="recommendationsLoading" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
          Loading recommendations...
        </div>
        <div v-else-if="!benefitRecommendations.length" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
          No AI recommendations are available for this employee yet.
        </div>
        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="recommendation in benefitRecommendations"
            :key="`${selectedEmployee.id}-${recommendation.benefit_id}`"
            class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800"
          >
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-semibold text-slate-900 dark:text-white">
                  {{ resolveBenefitName(recommendation.benefit_id) }}
                </div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  Likelihood: {{ Math.round(Number(recommendation.eligibility_score || 0) * 100) }}%
                </div>
              </div>
              <Badge :variant="recommendationStatusVariant(recommendation.status)">
                {{ recommendation.status || 'not_eligible' }}
              </Badge>
            </div>
            <div class="mt-4 text-sm text-slate-600 dark:text-slate-300">
              Estimated timeline:
              <strong>{{ recommendation.estimated_months_to_qualify ?? 0 }} month(s)</strong>
            </div>
            <div class="mt-3">
              <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Gap actions</div>
              <div v-if="recommendation.gap_actions?.length" class="mt-2 space-y-2">
                <div
                  v-for="action in recommendation.gap_actions"
                  :key="action"
                  class="rounded-md bg-slate-100 px-3 py-2 text-sm text-slate-700 dark:bg-slate-900 dark:text-slate-300"
                >
                  {{ action }}
                </div>
              </div>
              <p v-else class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">
                This employee already meets the current recommendation thresholds.
              </p>
            </div>
            <div v-if="recommendation.admin_guidance" class="mt-3 rounded-xl border border-sky-200 bg-sky-50 px-3 py-3 text-sm text-sky-700 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
              <div class="text-xs font-semibold uppercase tracking-wide">Admin guidance</div>
              <div class="mt-2">{{ recommendation.admin_guidance }}</div>
            </div>
            <div v-if="canManageBenefits" class="mt-4">
              <Button size="sm" class="w-full bg-sky-600 text-white hover:bg-sky-700" @click="applyRecommendation(recommendation)">
                Use Recommendation
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card v-if="selectedEmployee">
      <CardHeader>
        <CardTitle>{{ selectedEmployee.display_name }}'s Benefits</CardTitle>
        <CardDescription>All benefits assigned to this employee.</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="employeeAllowances.length === 0 && !isLoading" class="rounded-2xl border border-dashed border-slate-200 p-8 text-center dark:border-slate-700">
          <Gift class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
          <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">No benefits assigned yet.</p>
          <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">Use the form to assign a new benefit.</p>
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="item in employeeAllowances"
            :key="item.id"
            class="rounded-2xl border border-slate-200 bg-white p-4 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-900"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex-1">
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-500/20">
                    <component :is="getStatusIcon(item.status)" :class="['h-5 w-5', item.status === 'active' ? 'text-emerald-600' : item.status === 'pending' ? 'text-amber-600' : 'text-slate-400']" />
                  </div>
                  <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white">{{ item.benefit_name }}</h4>
                    <div class="flex items-center gap-2 mt-1">
                      <Badge :variant="getStatusVariant(item.status)" class="capitalize">
                        {{ getStatusLabel(item.status, item.claimed) }}
                      </Badge>
                      <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ item.amount }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-2">
                <div v-if="item.status === 'pending'" class="flex items-center gap-2">
                  <Button
                    size="sm"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white"
                    @click.stop="openStatusChange(item, 'active')"
                  >
                    <Zap class="w-4 h-4 mr-1" />
                    Activate
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    @click.stop="openStatusChange(item, 'inactive')"
                  >
                    Deactivate
                  </Button>
                </div>
                <div v-else-if="item.status === 'active' && !item.claimed" class="flex items-center gap-2">
                  <Badge variant="success" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                    Ready to Claim
                  </Badge>
                  <Button
                    size="sm"
                    variant="outline"
                    @click.stop="openStatusChange(item, 'inactive')"
                  >
                    Deactivate
                  </Button>
                </div>
                <div v-else-if="item.status === 'active' && item.claimed" class="flex items-center gap-2">
                  <Badge variant="info" class="bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                    Claimed
                  </Badge>
                </div>
                <div v-else-if="item.status === 'inactive'">
                  <Button
                    size="sm"
                    class="bg-amber-600 hover:bg-amber-700 text-white"
                    @click.stop="openStatusChange(item, 'pending')"
                  >
                    Set Pending
                  </Button>
                </div>
                <Button
                  v-if="canManageBenefits"
                  size="sm"
                  variant="destructive"
                  class="bg-rose-600 hover:bg-rose-700 text-white ml-2"
                  type="button"
                  @click.stop="openRemoveConfirmation(item)"
                >
                  <Trash2 class="w-4 h-4" />
                </Button>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
              <span>Start: {{ item.start_date }}</span>
              <span v-if="item.end_date">End: {{ item.end_date }}</span>
              <span v-if="item.claimed_at">Claimed: {{ new Date(item.claimed_at).toLocaleDateString() }}</span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <div v-if="confirmOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4" @click.self="closeRemoveConfirmation">
      <div class="w-full max-w-xl rounded-[2rem] border border-slate-800 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-start gap-4">
          <div class="mt-1 rounded-3xl bg-rose-500/10 p-3 text-rose-300">
            <AlertTriangle class="h-6 w-6" />
          </div>
          <div class="flex-1">
            <h3 class="text-2xl font-semibold text-white">Remove assigned benefit?</h3>
            <p class="mt-3 text-sm leading-6 text-slate-400">
              This will remove the selected benefit from <strong>{{ selectedEmployee?.display_name }}</strong> and refresh the list.
            </p>
            <div class="mt-4 rounded-3xl border border-slate-800 bg-slate-950 p-4 text-sm text-slate-300">
              <div class="font-medium text-white">{{ pendingRemoval?.benefit_name || 'Benefit' }}</div>
              <div class="mt-1 text-slate-400">Amount: {{ pendingRemoval?.amount || 'N/A' }}</div>
              <div class="mt-1 text-slate-500">Start date: {{ pendingRemoval?.start_date || 'N/A' }}</div>
            </div>
          </div>
        </div>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
          <Button variant="outline" class="w-full sm:w-auto" @click="closeRemoveConfirmation">
            Cancel
          </Button>
          <Button class="w-full sm:w-auto bg-rose-600 hover:bg-rose-700 text-white" @click="confirmRemoveBenefit">
            Remove benefit
          </Button>
        </div>
      </div>
    </div>
    <div v-if="scoreConfirmOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4" @click.self="scoreConfirmOpen = false">
      <div class="w-full max-w-xl rounded-[2rem] border border-amber-800 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-start gap-4">
          <div class="mt-1 rounded-3xl bg-amber-500/10 p-3 text-amber-300">
            <AlertTriangle class="h-6 w-6" />
          </div>
          <div class="flex-1">
            <h3 class="text-2xl font-semibold text-white">Low Performance Warning</h3>
            <p class="mt-3 text-sm leading-6 text-slate-400">
              <strong>{{ selectedEmployee?.display_name }}</strong> has a holistic score of 
              <span class="text-amber-400 font-bold">{{ Math.round(selectedEmployeeScore?.overall_score) }}%</span>, 
              which is below the recommended threshold (70%).
            </p>
            <p class="mt-2 text-sm text-slate-500">
              Are you sure you want to award this benefit despite the current performance standing?
            </p>
          </div>
        </div>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
          <Button variant="outline" class="w-full sm:w-auto" @click="scoreConfirmOpen = false">
            Cancel
          </Button>
          <Button class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white" @click="assignBenefit(true)">
            Proceed Anyway
          </Button>
        </div>
      </div>
    </div>

    <div v-if="statusConfirmOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4" @click.self="closeStatusChange">
      <div class="w-full max-w-xl rounded-[2rem] border border-slate-800 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-start gap-4">
          <div :class="['mt-1 rounded-3xl p-3', pendingStatusChange?.newStatus === 'active' ? 'bg-emerald-500/10 text-emerald-300' : pendingStatusChange?.newStatus === 'inactive' ? 'bg-slate-500/10 text-slate-300' : 'bg-amber-500/10 text-amber-300']">
            <component :is="pendingStatusChange?.newStatus === 'active' ? CheckCircle : pendingStatusChange?.newStatus === 'inactive' ? XCircle : Clock" class="h-6 w-6" />
          </div>
          <div class="flex-1">
            <h3 class="text-2xl font-semibold text-white">
              {{ pendingStatusChange?.newStatus === 'active' ? 'Activate Benefit' : pendingStatusChange?.newStatus === 'inactive' ? 'Deactivate Benefit' : 'Set Pending' }}
            </h3>
            <p class="mt-3 text-sm leading-6 text-slate-400">
              Change <strong class="text-white">{{ pendingStatusChange?.benefit_name }}</strong> status from 
              <Badge :variant="getStatusVariant(pendingStatusChange?.status)" class="capitalize mx-1">
                {{ pendingStatusChange?.status }}
              </Badge>
              to 
              <Badge :variant="getStatusVariant(pendingStatusChange?.newStatus)" class="capitalize mx-1">
                {{ pendingStatusChange?.newStatus }}
              </Badge>
            </p>
            <p v-if="pendingStatusChange?.newStatus === 'active'" class="mt-2 text-sm text-emerald-400">
              The employee will be able to claim this benefit once activated.
            </p>
            <p v-else-if="pendingStatusChange?.newStatus === 'inactive'" class="mt-2 text-sm text-slate-400">
              The employee will lose access to this benefit.
            </p>
          </div>
        </div>
        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
          <Button variant="outline" class="w-full sm:w-auto" :disabled="isUpdatingStatus" @click="closeStatusChange">
            Cancel
          </Button>
          <Button 
            :class="`w-full sm:w-auto ${pendingStatusChange?.newStatus === 'active' ? 'bg-emerald-600 hover:bg-emerald-700' : pendingStatusChange?.newStatus === 'inactive' ? 'bg-slate-600 hover:bg-slate-700' : 'bg-amber-600 hover:bg-amber-700'}`"
            :disabled="isUpdatingStatus"
            @click="confirmStatusChange"
          >
            <span v-if="isUpdatingStatus" class="animate-pulse mr-2">...</span>
            {{ isUpdatingStatus ? 'Updating...' : 'Confirm Change' }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
