<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { Brain, AlertTriangle, Check, Plus, Shield, ShieldOff, Users } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { platformApi } from '@/api/laravel/platform'
import { djangoAiApi } from '@/api/django/ai'
import { isNetworkOrServerUnavailable, unwrapItems } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const auth = useAuthStore()
const notifications = useNotificationsStore()

const organizationSetupHref = computed(() => {
  if (auth.user?.role === 'admin') return '/admin/organization'
  if (auth.user?.role === 'rh_manager') return '/rh/organization'
  return null
})

const directoryItems = ref<any[]>([])
const branches = ref<any[]>([])
const departments = ref<any[]>([])
const designations = ref<any[]>([])
const isCreating = ref(false)
const isLoading = ref(true)
const prediction = ref<any>(null)
const isPredictionLoading = ref(false)
const predictionEmployee = ref('')
const predictionError = ref('')
const feedback = ref('')
const errorMsg = ref('')
const searchQuery = ref('')
const selectedPendingUser = ref<any | null>(null)

const roleOptions = [
  { value: 'user', label: 'Employee' },
  { value: 'manager', label: 'Manager' },
  { value: 'rh', label: 'HR' },
  { value: 'admin', label: 'Admin' }
]

const form = reactive({
  user_id: '',
  name: '',
  email: '',
  gender: 'male',
  address: '',
  branch_id: '',
  department_id: '',
  designation_id: '',
  salary: '',
  role: 'user'
})

const columns = [
  { key: 'employee_id', label: 'ID' },
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'salary', label: 'Salary' },
  { key: 'status', label: 'Status' }
]

const items = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return directoryItems.value

  return directoryItems.value.filter((item) => {
    const haystack = [
      item.employee_id,
      item.name,
      item.email,
      item.salary,
      item.status,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return haystack.includes(query)
  })
})

const tableHeight = computed(() => {
  if (isCreating.value) return '28rem'
  return '36rem'
})

const pendingProfilesCount = computed(() =>
  directoryItems.value.filter((item) => item.isPendingProfile).length
)

function formatEmployeeItem(employee: any) {
  return {
    ...employee,
    salary_amount: employee.salary === null || employee.salary === undefined || employee.salary === ''
      ? null
      : Number(employee.salary),
    salary: employee.salary ? `$${Number(employee.salary).toLocaleString()}` : 'Not set',
    status: employee.is_active ? 'Active' : 'Inactive',
    isPendingProfile: false,
  }
}

function mergeUsersWithoutProfiles(employeeItems: any[], users: any[]) {
  const linkedUserIds = new Set(employeeItems.map((employee) => employee.user_id).filter(Boolean))
  const linkedEmails = new Set(
    employeeItems
      .map((employee) => String(employee.email ?? '').toLowerCase())
      .filter(Boolean)
  )

  const pendingUsers = users
    .filter((user) => !linkedUserIds.has(user.id) && !linkedEmails.has(String(user.email ?? '').toLowerCase()))
    .map((user) => ({
      id: `user-${user.id}`,
      user_id: user.id,
      employee_id: 'Pending profile',
      name: user.name,
      email: user.email,
      salary: 'Not set',
      status: 'Pending profile',
      isPendingProfile: true,
      roles: Array.isArray(user.roles) ? user.roles : [],
      created_at: user.created_at,
    }))
    .sort((left, right) => String(right.created_at ?? '').localeCompare(String(left.created_at ?? '')))

  return [...pendingUsers, ...employeeItems.map(formatEmployeeItem)]
}

const fetchEmployees = async () => {
  isLoading.value = true
  try {
    const [employeeData, branchData, departmentData, designationData] = await Promise.all([
      platformApi.getEmployees(),
      platformApi.getBranches(),
      platformApi.getDepartments(),
      platformApi.getDesignations()
    ])

    const employeeItems = unwrapItems<any>(employeeData)
    let nextItems = employeeItems.map(formatEmployeeItem)

    if (auth.user?.role === 'admin') {
      try {
        const userData = await platformApi.getUsers()
        nextItems = mergeUsersWithoutProfiles(employeeItems, unwrapItems<any>(userData))
      } catch (error) {
        console.warn('Unable to load pending signup users for the directory', error)
      }
    }

    directoryItems.value = nextItems
    branches.value = unwrapItems(branchData)
    departments.value = unwrapItems(departmentData)
    designations.value = unwrapItems(designationData)
  } catch (err) {
    errorMsg.value = isNetworkOrServerUnavailable(err)
      ? 'Laravel is unavailable right now, so employees cannot be loaded.'
      : 'Unable to load employees right now.'
  } finally {
    isLoading.value = false
  }
}

const getStatusVariant = (status: string) => {
  if (status === 'Active') return 'success'
  if (status === 'Pending profile') return 'warning'
  if (status === 'On Leave') return 'warning'
  if (status === 'Terminated') return 'destructive'
  return 'secondary'
}

function handleSearch(value: string) {
  searchQuery.value = value
}

function normalizeNumber(value: unknown, fallback = 0) {
  if (value === null || value === undefined || value === '') {
    return fallback
  }

  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : fallback
}

function deriveRiskLevel(score: number | null) {
  if (score === null) return 'Unavailable'
  if (score >= 0.7) return 'Critical'
  if (score >= 0.5) return 'High'
  if (score >= 0.3) return 'Medium'
  return 'Low'
}

function normalizeTurnoverPrediction(payload: any) {
  const rawScore = payload?.prediction_score ?? payload?.score ?? payload?.probability ?? null
  const predictionScore = rawScore === null ? null : normalizeNumber(rawScore, NaN)
  const safeScore = Number.isFinite(predictionScore) ? predictionScore : null

  return {
    ...payload,
    prediction_score: safeScore,
    risk_level: payload?.risk_level || payload?.level || deriveRiskLevel(safeScore),
    recommendations: Array.isArray(payload?.recommendations) ? payload.recommendations : [],
  }
}

function formatPredictionScore(score: number | null) {
  return typeof score === 'number' && Number.isFinite(score) ? score.toFixed(3) : 'N/A'
}

function resetForm() {
  form.user_id = ''
  form.name = ''
  form.email = ''
  form.gender = 'male'
  form.address = ''
  form.branch_id = ''
  form.department_id = ''
  form.designation_id = ''
  form.salary = ''
  form.role = 'user'
}

function toggleCreateForm() {
  isCreating.value = !isCreating.value

  if (!isCreating.value) {
    selectedPendingUser.value = null
    resetForm()
  }
}

function deriveRole(user: any, preferredRole?: string) {
  if (preferredRole) return preferredRole

  const roles = Array.isArray(user?.roles) ? user.roles : []

  if (roles.includes('admin')) return 'admin'
  if (roles.includes('rh')) return 'rh'
  if (roles.includes('manager')) return 'manager'
  return 'user'
}

function getUserIdForItem(item: any): number | null {
  const candidates = [
    item.user_id,
    item.id,
    item.employee_id,
    item.user?.id,
    item.employee?.user_id,
  ]

  for (const candidate of candidates) {
    if (candidate !== undefined && candidate !== null) {
      const num = Number(candidate)
      if (Number.isFinite(num) && num > 0) {
        return num
      }
    }
  }

  return null
}

const confirmOpen = ref(false)
const pendingAction = ref<'suspend' | 'ban' | 'activate' | null>(null)
const pendingTarget = ref<any | null>(null)

const pendingLabel = computed(() => {
  if (pendingAction.value === 'activate') return 'Activate'
  if (pendingAction.value === 'ban') return 'Ban'
  return 'Suspend'
})

const confirmationMessage = computed(() => {
  if (!pendingTarget.value) return ''
  return `Are you sure you want to ${pendingLabel.value.toLowerCase()} ${pendingTarget.value.name || 'this user'}?`
})

function openActionConfirmation(item: any, action: 'suspend' | 'ban' | 'activate') {
  pendingTarget.value = item
  pendingAction.value = action
  confirmOpen.value = true
}

async function confirmUserAction() {
  if (!pendingTarget.value || !pendingAction.value) {
    closeActionConfirmation()
    return
  }

  await performUserAction(pendingTarget.value, pendingAction.value)
  closeActionConfirmation()
}

function closeActionConfirmation() {
  confirmOpen.value = false
  pendingAction.value = null
  pendingTarget.value = null
}

async function performUserAction(item: any, action: 'suspend' | 'ban' | 'activate') {
  const targetId = getUserIdForItem(item)
  const label = action === 'activate' ? 'Activate' : action === 'ban' ? 'Ban' : 'Suspend'

  if (targetId === null) {
    console.error('Unable to resolve user ID for item:', item)
    errorMsg.value = 'Unable to identify the user record for this employee. Please check the profile and try again.'
    return
  }

  try {
    if (action === 'suspend') {
      await platformApi.suspendUser(targetId)
      feedback.value = `${item.name || 'User'} was suspended successfully.`
    } else if (action === 'ban') {
      await platformApi.banUser(targetId)
      feedback.value = `${item.name || 'User'} was banned successfully.`
    } else {
      await platformApi.activateUser(targetId)
      feedback.value = `${item.name || 'User'} has been reactivated.`
    }

    errorMsg.value = ''
    await fetchEmployees()
    notifications.fetchNotifications()
  } catch (error: any) {
    console.error('User action failed', error, 'for item:', item, 'targetId:', targetId)
    errorMsg.value = error.response?.data?.message ?? `Unable to ${label.toLowerCase()} user.`
  }
}

function startPendingProfile(item: any, preferredRole?: string) {
  selectedPendingUser.value = item
  form.user_id = String(item.user_id ?? '')
  form.name = item.name ?? ''
  form.email = item.email ?? ''
  form.gender = 'male'
  form.address = ''
  form.salary = ''
  form.role = deriveRole(item, preferredRole)
  isCreating.value = true
  feedback.value = ''
  errorMsg.value = ''
}

async function createEmployee() {
  try {
    await platformApi.createEmployee({
      user_id: form.user_id ? Number(form.user_id) : undefined,
      name: form.name,
      email: form.email,
      gender: form.gender,
      address: form.address,
      branch_id: Number(form.branch_id),
      department_id: Number(form.department_id),
      designation_id: Number(form.designation_id),
      salary: Number(form.salary || 0),
      roles: [form.role]
    })
    feedback.value = selectedPendingUser.value
      ? `${selectedPendingUser.value.name} has been accepted as ${roleOptions.find((option) => option.value === form.role)?.label ?? 'Employee'}.`
      : 'Employee created successfully.'
    errorMsg.value = ''
    isCreating.value = false
    const acceptedUserId = form.user_id ? Number(form.user_id) : null
    const acceptedRole = form.role
    selectedPendingUser.value = null
    resetForm()

    if (acceptedUserId && acceptedUserId === auth.user?.id && acceptedRole === 'admin') {
      await auth.fetchUser()
    }

    await fetchEmployees()
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to create employee.'
  }
}

async function openPrediction(employee: any) {
  if (employee.isPendingProfile) {
    return
  }

  predictionEmployee.value = employee.name
  prediction.value = null
  predictionError.value = ''
  isPredictionLoading.value = true

  try {
    const stats = await platformApi.getEmployeeStatistics(employee.id)
    const result = await djangoAiApi.predictTurnover({
      employee_id: employee.id,
      tenure_years: normalizeNumber(stats?.tenure_years, 0),
      salary: normalizeNumber(employee.salary_amount, 60000),
      complaints_count: 0,
      performance_score: 4.0,
      leaves_taken: normalizeNumber(stats?.leaves_count, 0),
    })

    prediction.value = normalizeTurnoverPrediction(result)
  } catch (error: any) {
    const isConnectionRefused =
      error?.code === 'ERR_NETWORK' ||
      error?.code === 'ECONNABORTED' ||
      String(error?.message || '').toLowerCase().includes('connection refused')

    predictionError.value = isConnectionRefused
      ? 'AI turnover prediction is temporarily unavailable. The employee profile is still fully usable.'
      : error?.response?.data?.error ?? error?.response?.data?.message ?? 'Unable to fetch turnover prediction right now.'

    if (!isConnectionRefused) {
      console.warn('Unable to fetch turnover prediction', error)
    }
  } finally {
    isPredictionLoading.value = false
  }
}

onMounted(fetchEmployees)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <Users class="w-8 h-8 text-blue-600" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Employee Directory</h2>
          <p class="text-gray-500 dark:text-gray-400">Manage all staff members across the organization.</p>
        </div>
      </div>
      <Button class="bg-blue-600" @click="toggleCreateForm">
        <Plus class="w-4 h-4 mr-2" />
        {{ isCreating ? 'Close Form' : 'Add Employee' }}
      </Button>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="pendingProfilesCount" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300">
      {{ pendingProfilesCount }} registered {{ pendingProfilesCount === 1 ? 'account is' : 'accounts are' }} waiting for a full employee profile. They now appear at the top of this directory as `Pending profile`.
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <Card v-if="isCreating">
      <CardHeader>
        <CardTitle>{{ selectedPendingUser ? 'Accept Pending User' : 'Create Employee' }}</CardTitle>
        <CardDescription class="space-y-2">
          <span>
            {{ selectedPendingUser
              ? `Finish ${selectedPendingUser.name}'s employee profile and choose the access role.`
              : 'This form now posts directly to the Laravel employee endpoint.' }}
          </span>
          <span v-if="organizationSetupHref" class="block text-sky-700 dark:text-sky-300">
            Need new branches, departments, or job titles?
            <RouterLink :to="organizationSetupHref" class="font-semibold underline underline-offset-2 hover:text-sky-900 dark:hover:text-sky-100">
              Open Organization setup
            </RouterLink>
            , then return here and refresh if lists are empty.
          </span>
        </CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div v-if="selectedPendingUser" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 md:col-span-2 xl:col-span-3 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300">
          Accepting <span class="font-semibold">{{ selectedPendingUser.name }}</span> with email <span class="font-semibold">{{ selectedPendingUser.email }}</span>.
        </div>
        <div class="space-y-2">
          <Label>Name</Label>
          <Input v-model="form.name" placeholder="Employee full name" />
        </div>
        <div class="space-y-2">
          <Label>Email</Label>
          <Input v-model="form.email" type="email" placeholder="employee@company.com" />
        </div>
        <div class="space-y-2">
          <Label>Gender</Label>
          <select v-model="form.gender" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Role</Label>
          <select v-model="form.role" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option v-for="option in roleOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Branch</Label>
          <select v-model="form.branch_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">Select branch</option>
            <option v-for="branch in branches" :key="branch.id" :value="String(branch.id)">{{ branch.name }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Department</Label>
          <select v-model="form.department_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">Select department</option>
            <option v-for="department in departments" :key="department.id" :value="String(department.id)">{{ department.name }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Designation</Label>
          <select v-model="form.designation_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">Select designation</option>
            <option v-for="designation in designations" :key="designation.id" :value="String(designation.id)">{{ designation.title || designation.name }}</option>
          </select>
        </div>
        <div class="space-y-2 xl:col-span-1">
          <Label>Salary</Label>
          <Input v-model="form.salary" type="number" placeholder="50000" />
        </div>
        <div class="space-y-2 md:col-span-2 xl:col-span-2">
          <Label>Address</Label>
          <Input v-model="form.address" placeholder="Employee address" />
        </div>
        <div class="md:col-span-2 xl:col-span-3 flex justify-end gap-2">
          <Button
            v-if="selectedPendingUser"
            class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
            @click="selectedPendingUser = null; resetForm()"
          >
            Clear Pending User
          </Button>
          <Button @click="createEmployee">
            {{ selectedPendingUser ? 'Accept User' : 'Create Employee' }}
          </Button>
        </div>
      </CardContent>
    </Card>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
      <Card>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="items" 
          :loading="isLoading"
          :page-size="10"
          :max-body-height="tableHeight"
          searchPlaceholder="Search employees by name or ID..."
          @search="handleSearch"
        >
          <template #cell(status)="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
          </template>
          <template #actions="{ item }">
            <div class="flex flex-wrap items-center gap-2 justify-end">
              <Button v-if="!item.isPendingProfile" class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" @click.stop="openPrediction(item)">
                <Brain class="mr-2 h-4 w-4" />
                AI Risk
              </Button>
              <Button
                v-if="!item.isPendingProfile && item.is_active"
                class="border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/20 dark:text-amber-300"
                @click.stop="openActionConfirmation(item, 'suspend')"
              >
                <AlertTriangle class="mr-2 h-4 w-4" />
                Suspend
              </Button>
              <Button
                v-if="!item.isPendingProfile && item.is_active"
                class="border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/20 dark:text-rose-300"
                @click.stop="openActionConfirmation(item, 'ban')"
              >
                <ShieldOff class="mr-2 h-4 w-4" />
                Ban
              </Button>
              <Button
                v-if="!item.isPendingProfile && !item.is_active"
                class="border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-300"
                @click.stop="openActionConfirmation(item, 'activate')"
              >
                <Shield class="mr-2 h-4 w-4" />
                Activate
              </Button>
              <div v-if="item.isPendingProfile" class="flex justify-end gap-2">
                <Button class="h-8 bg-blue-600 px-3 text-xs text-white hover:bg-blue-700" @click.stop="startPendingProfile(item)">
                  <Check class="mr-1 h-3.5 w-3.5" />
                  Accept
                </Button>
                <Button class="h-8 bg-slate-900 px-3 text-xs text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200" @click.stop="startPendingProfile(item, 'admin')">
                  <Shield class="mr-1 h-3.5 w-3.5" />
                  Admin
                </Button>
              </div>
            </div>
          </template>
        </DataTable>
      </CardContent>
      </Card>

      <div v-if="confirmOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4">
        <div class="w-full max-w-xl rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl shadow-slate-900/20 dark:border-slate-700 dark:bg-slate-950">
          <div class="flex items-start gap-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300">
              <AlertTriangle class="h-6 w-6" />
            </span>
            <div class="flex-1">
              <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100">Confirm {{ pendingLabel }}</h3>
              <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">{{ confirmationMessage }}</p>
            </div>
          </div>

          <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-end">
            <Button variant="secondary" class="w-full sm:w-auto" @click="closeActionConfirmation">
              Cancel
            </Button>
            <Button
              class="w-full sm:w-auto bg-rose-600 text-white hover:bg-rose-700"
              @click="confirmUserAction"
            >
              Confirm {{ pendingLabel }}
            </Button>
          </div>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Turnover Insight</CardTitle>
          <CardDescription>AI prediction fetched directly from the Django turnover model using employee statistics.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="isPredictionLoading" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
            Loading Django turnover prediction...
          </div>
          <div v-else-if="predictionError" class="py-10 text-center text-sm text-red-600 dark:text-red-400">
            {{ predictionError }}
          </div>
          <div v-else-if="!prediction" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
            Choose an employee to review predicted turnover risk.
          </div>
          <div v-else class="space-y-4">
            <div class="text-sm text-slate-500 dark:text-slate-400">{{ predictionEmployee }}</div>
            <div class="text-3xl font-bold text-slate-900 dark:text-white">
              {{ prediction.risk_level }}
            </div>
            <div class="text-sm text-slate-600 dark:text-slate-300">
              Prediction score: {{ formatPredictionScore(prediction.prediction_score) }}
            </div>
            <div v-if="prediction.recommendations?.length" class="space-y-2">
              <div class="text-sm font-semibold text-slate-900 dark:text-white">Recommendations</div>
              <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-300">
                <li v-for="item in prediction.recommendations" :key="item">• {{ item }}</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
