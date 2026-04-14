<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { CalendarDays, CheckCircle2, Clock3, Paperclip, Plus, Search, ShieldAlert, Upload, X } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import DataTable from '@/components/ui/DataTable.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const requests = ref<any[]>([])
const employees = ref<any[]>([])
const leaveTypes = ref<any[]>([])
const loading = ref(true)
const submitting = ref(false)
const actionLoadingId = ref<number | null>(null)
const searchQuery = ref('')
const attachments = ref<File[]>([])
const feedback = ref('')
const errorMessage = ref('')
const formErrors = ref<Record<string, string[]>>({})
const leaveAiInsight = ref<any>(null)
const leaveAiLoading = ref(false)
const leaveAiError = ref('')
const activeAiAction = ref<string>('')

const form = reactive({
  employee_id: '',
  leave_type_id: '',
  duration_type: 'full_day',
  start_date: '',
  end_date: '',
  reason: '',
})

const userRoles = computed(() =>
  [auth.user?.role, ...(auth.user?.allRoles ?? [])].filter(Boolean).map((role) => String(role).toLowerCase())
)
const isManagerView = computed(() => userRoles.value.some((role) => ['admin', 'rh_manager', 'rh', 'hr', 'manager'].includes(role)))
const columns = [
  { key: 'employee', label: 'Employee' },
  { key: 'period', label: 'Period' },
  { key: 'duration_type', label: 'Duration' },
  { key: 'status', label: 'Status' },
  { key: 'reason', label: 'Reason' },
]

const minimumEndDate = computed(() => form.start_date ? addDaysToIsoDate(form.start_date, 1) : '')
const maximumEndDate = computed(() => form.start_date ? addDaysToIsoDate(form.start_date, 14) : '')
const aiAppliedKey = computed(() => activeAiAction.value)

const smartRecommendations = computed(() => {
  const aiRecommendations = leaveAiInsight.value
  const recommendedSingleDays = Array.isArray(aiRecommendations?.recommended_single_days) ? aiRecommendations.recommended_single_days : []
  const recommendedWindows = Array.isArray(aiRecommendations?.recommended_windows) ? aiRecommendations.recommended_windows : []
  const bestSingleDay = recommendedSingleDays[0] ?? null
  const bestWindow = recommendedWindows[0] ?? null
  const approvedRequests = requests.value.filter((item) => String(item.status).toLowerCase() === 'approved')
  const typeCounts = approvedRequests.reduce<Record<string, number>>((acc, item) => {
    const key = String(item.leave_type?.name || item.leave_type?.leave_code || item.leave_type_id || 'unknown')
    acc[key] = (acc[key] ?? 0) + 1
    return acc
  }, {})
  const preferredType = Object.entries(typeCounts).sort((a, b) => b[1] - a[1])[0]?.[0] ?? null
  const latestApproved = approvedRequests[0] ?? null
  const latestDuration = latestApproved?.duration_type ? durationLabel(latestApproved.duration_type) : 'Full day'
  const pendingCount = requests.value.filter((item) => String(item.status).toLowerCase() === 'pending').length

  return [
    {
      title: 'Best day from AI',
      value: bestSingleDay ? `${bestSingleDay.date} · ${bestSingleDay.weekday}` : 'No AI suggestion yet',
      action: 'Use AI day',
      kind: 'ai_day',
      hint: bestSingleDay
        ? `Suitability score ${(Number(bestSingleDay.suitability_score || 0) * 100).toFixed(0)}%.`
        : 'Open the AI service result and click a recommendation to fill the date fields.',
    },
    {
      title: 'Best date range',
      value: getWindowLabel(bestWindow),
      action: 'Use range',
      kind: 'ai_range',
      hint: bestWindow
        ? `Recommended window with score ${(Number(bestWindow.suitability_score || 0) * 100).toFixed(0)}%.`
        : 'The AI service did not return a window suggestion yet.',
    },
    {
      title: 'Recommended leave type',
      value: preferredType ?? 'Annual leave',
      action: 'Use recommended type',
      kind: 'type',
      hint: preferredType
        ? 'Based on your approved request history.'
        : 'No history yet, so we suggest the default annual leave flow.',
    },
    {
      title: 'Preferred duration',
      value: latestDuration,
      action: 'Use duration',
      kind: 'duration',
      hint: latestApproved
        ? `Matches your latest approved request from ${formatDateLabel(latestApproved.start_date)}.`
        : 'Use the default full-day flow unless your team prefers half-day requests.',
    },
    {
      title: 'Pending requests',
      value: String(pendingCount),
      action: 'Prefill range',
      kind: 'range',
      hint: pendingCount > 0
        ? 'Keep an eye on pending items that still need approval.'
        : 'No pending items right now, so you are clear to submit a new request.',
    },
  ]
})

const filteredRequests = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return requests.value
  return requests.value.filter((item) => [item.employee, item.reason, item.period, item.status, item.duration_type].filter(Boolean).join(' ').toLowerCase().includes(query))
})

function resolveEmployeeDisplayName(employee: any) {
  const fullName = [employee?.first_name, employee?.last_name].filter(Boolean).join(' ').trim()
  return employee?.name || employee?.full_name || fullName || employee?.email || `Employee #${employee?.id ?? 'Unknown'}`
}

function mapLeaves(data: any) {
  return unwrapItems<any>(data).map((item) => ({
    ...item,
    employee: resolveEmployeeDisplayName(item.employee ?? { id: item.employee_id }),
    period: `${item.start_date ?? 'N/A'} - ${item.end_date ?? 'N/A'}`,
  }))
}

function statusBadgeVariant(status: string) {
  const value = String(status).toLowerCase()
  if (value === 'approved') return 'success'
  if (value === 'rejected') return 'destructive'
  return 'warning'
}

function durationLabel(value: string) {
  return {
    full_day: 'Full day',
    half_day_morning: 'Half day (AM)',
    half_day_afternoon: 'Half day (PM)',
  }[String(value)] ?? value
}

const durationOptions = [
  { value: 'full_day', label: 'Full day' },
  { value: 'half_day_morning', label: 'Half day (AM)' },
  { value: 'half_day_afternoon', label: 'Half day (PM)' },
]

function isDurationActive(value: string) {
  return form.duration_type === value
}

function formatDateLabel(value?: string) {
  if (!value) return 'N/A'
  const normalized = String(value).includes('T') ? String(value) : `${value}T00:00:00`
  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) return String(value)
  return new Intl.DateTimeFormat('en', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(date)
}

function formatInputDate(value?: string) {
  if (!value) return new Date().toISOString().slice(0, 10)
  const normalized = String(value).includes('T') ? String(value) : `${value}T00:00:00`
  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) return new Date().toISOString().slice(0, 10)
  return date.toISOString().slice(0, 10)
}

function getWindowLabel(window: any) {
  const start = window?.start_date || window?.start || window?.from
  const end = window?.end_date || window?.end || window?.to
  if (!start || !end) return 'No window suggestion yet'
  return `${formatDateLabel(start)} → ${formatDateLabel(end)}`
}

function applyRecommendation(item: { kind: string; value: string }) {
  activeAiAction.value = item.kind
  if (item.kind === 'ai_day') {
    const aiRecommendations = leaveAiInsight.value
    const bestSingleDay = Array.isArray(aiRecommendations?.recommended_single_days) ? aiRecommendations.recommended_single_days[0] : null
    if (bestSingleDay?.date) {
      form.start_date = formatInputDate(bestSingleDay.date)
      form.end_date = formatInputDate(bestSingleDay.date)
      form.duration_type = 'full_day'
    }
    return
  }

  if (item.kind === 'ai_range') {
    const aiRecommendations = leaveAiInsight.value
    const bestWindow = Array.isArray(aiRecommendations?.recommended_windows) ? aiRecommendations.recommended_windows[0] : null
    const start = bestWindow?.start_date || bestWindow?.start || bestWindow?.from
    const end = bestWindow?.end_date || bestWindow?.end || bestWindow?.to
    if (start) form.start_date = formatInputDate(start)
    if (end) form.end_date = formatInputDate(end)
    if (!start) {
      form.start_date = new Date().toISOString().slice(0, 10)
      form.end_date = addDaysToIsoDate(form.start_date, 1)
    }
    return
  }

  if (item.kind === 'type') {
    const matched = leaveTypes.value.find((type) => String(type.name || type.leave_code).toLowerCase() === String(item.value).toLowerCase())
    if (matched) form.leave_type_id = String(matched.id)
    return
  }

  if (item.kind === 'duration') {
    const lower = String(item.value).toLowerCase()
    if (lower.includes('am')) form.duration_type = 'half_day_morning'
    else if (lower.includes('pm')) form.duration_type = 'half_day_afternoon'
    else form.duration_type = 'full_day'
    return
  }

  if (item.kind === 'range') {
    const latestApproved = requests.value.find((request) => String(request.status).toLowerCase() === 'approved')
    if (latestApproved?.start_date) form.start_date = formatInputDate(latestApproved.start_date)
    if (latestApproved?.end_date) form.end_date = formatInputDate(latestApproved.end_date)
    if (!latestApproved?.start_date) {
      form.start_date = new Date().toISOString().slice(0, 10)
      form.end_date = addDaysToIsoDate(form.start_date, 1)
    }
  }
}

function parseFieldErrors(error: any) {
  formErrors.value = error?.response?.data?.errors ?? {}
  errorMessage.value = error?.response?.data?.message ?? 'Something went wrong.'
}

function validateLeaveRange() {
  if (!form.start_date || !form.end_date) {
    return 'Please select both start and end dates.'
  }
  if (form.end_date < form.start_date) {
    return 'End date must be on or after the start date.'
  }
  const diffDays = Math.round((new Date(`${form.end_date}T00:00:00`).getTime() - new Date(`${form.start_date}T00:00:00`).getTime()) / 86400000) + 1
  if (diffDays < 1) return 'Leave range must be at least 1 day.'
  if (diffDays > 14) return 'Leave range cannot exceed 14 days.'
  return ''
}

async function loadLeaveRecommendations() {
  leaveAiLoading.value = true
  leaveAiError.value = ''
  try {
    const response: any = await platformApi.getOptimalLeaveDates(auth.user?.employee_id ? Number(auth.user.employee_id) : undefined)
    leaveAiInsight.value = response?.data ?? response ?? null
  } catch (error: any) {
    console.error('Failed to load leave recommendations', error)
    leaveAiError.value = error?.response?.data?.message ?? 'AI recommendations are unavailable right now.'
    leaveAiInsight.value = null
  } finally {
    leaveAiLoading.value = false
  }
}

async function loadData() {
  loading.value = true
  errorMessage.value = ''
  try {
    const [leaveData, leaveTypeData, employeeData] = await Promise.all([
      platformApi.getLeaves(),
      platformApi.getLeaveTypes(),
      isManagerView.value ? platformApi.getEmployees() : Promise.resolve([]),
    ])
    requests.value = mapLeaves(leaveData)
    leaveTypes.value = unwrapItems<any>(leaveTypeData)
    employees.value = unwrapItems<any>(employeeData)
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Unable to load leave requests.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.employee_id = ''
  form.leave_type_id = ''
  form.duration_type = 'full_day'
  form.start_date = ''
  form.end_date = ''
  form.reason = ''
  attachments.value = []
  formErrors.value = {}
}

async function submitRequest() {
  submitting.value = true
  feedback.value = ''
  errorMessage.value = ''
  formErrors.value = {}

  const rangeError = validateLeaveRange()
  if (rangeError) {
    errorMessage.value = rangeError
    submitting.value = false
    return
  }

  try {
    const payload = new FormData()
    if (form.employee_id) payload.append('employee_id', String(form.employee_id))
    if (form.leave_type_id) payload.append('leave_type_id', String(form.leave_type_id))
    payload.append('duration_type', form.duration_type)
    payload.append('start_date', form.start_date)
    payload.append('end_date', form.end_date)
    if (form.reason) payload.append('reason', form.reason)
    attachments.value.forEach((file) => payload.append('attachments[]', file))

    await platformApi.createLeave(payload)
    feedback.value = 'Leave request submitted successfully.'
    resetForm()
    await loadData()
  } catch (error: any) {
    parseFieldErrors(error)
  } finally {
    submitting.value = false
  }
}

async function approve(item: any) {
  actionLoadingId.value = item.id
  try {
    await platformApi.approveLeaveByManager(item.id)
    feedback.value = 'Leave approved.'
    await loadData()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Unable to approve this request.'
  } finally {
    actionLoadingId.value = null
  }
}

async function reject(item: any) {
  actionLoadingId.value = item.id
  try {
    await platformApi.rejectLeave(item.id, 'Rejected from Leave Requests review.')
    feedback.value = 'Leave rejected.'
    await loadData()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? 'Unable to reject this request.'
  } finally {
    actionLoadingId.value = null
  }
}

function onAttachmentChange(event: Event) {
  const files = Array.from((event.target as HTMLInputElement).files ?? [])
  attachments.value = files
}

function clearAttachment(file: File) {
  attachments.value = attachments.value.filter((item) => item !== file)
}

function addDaysToIsoDate(date: string, days: number) {
  const copy = new Date(`${date}T00:00:00`)
  copy.setDate(copy.getDate() + days)
  return copy.toISOString().slice(0, 10)
}

function daysBetween(start: string, end: string) {
  if (!start || !end) return 0
  return Math.round((new Date(`${end}T00:00:00`).getTime() - new Date(`${start}T00:00:00`).getTime()) / 86400000) + 1
}

const selectedRangeDays = computed(() => daysBetween(form.start_date, form.end_date))

onMounted(loadData)
onMounted(loadLeaveRecommendations)
watch(() => form.start_date, () => {
  if (form.end_date && form.start_date && form.end_date <= form.start_date) {
    form.end_date = addDaysToIsoDate(form.start_date, 1)
  }
  if (form.start_date && form.end_date && form.end_date > maximumEndDate.value) {
    form.end_date = maximumEndDate.value
  }
})

watch(() => form.end_date, () => {
  if (form.start_date && form.end_date && form.end_date < form.start_date) {
    form.end_date = form.start_date
  }
  if (form.start_date && form.end_date && form.end_date > maximumEndDate.value) {
    form.end_date = maximumEndDate.value
  }
})
</script>

<template>
  <div class="space-y-6">
    <section class="relative overflow-hidden rounded-[2rem] border border-white/10 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_40%),linear-gradient(180deg,rgba(15,23,42,0.92),rgba(15,23,42,0.82))] p-6 text-white shadow-2xl dark:border-white/5">
      <div class="absolute inset-0 bg-grid-white/5 opacity-20"></div>
      <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-2xl">
          <div class="mb-3 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-sky-100">
            <CalendarDays class="h-3.5 w-3.5" />
            Leave management
          </div>
          <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">Leave Requests</h1>
          <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">
            Submit time off, review approvals, and manage attachments with a cleaner workflow that stays usable in both themes.
          </p>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-300">Requests</div>
            <div class="mt-2 text-2xl font-semibold">{{ requests.length }}</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-300">Pending</div>
            <div class="mt-2 text-2xl font-semibold">{{ requests.filter((item) => String(item.status).toLowerCase() === 'pending').length }}</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-300">Approved</div>
            <div class="mt-2 text-2xl font-semibold">{{ requests.filter((item) => String(item.status).toLowerCase() === 'approved').length }}</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-300">Mode</div>
            <div class="mt-2 text-sm font-semibold">{{ isManagerView ? 'Manager / HR' : 'Employee' }}</div>
          </div>
        </div>
      </div>
    </section>

    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200">
      {{ errorMessage }}
    </div>
    <div v-if="feedback" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200">
      {{ feedback }}
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
      <Card class="border-white/10 bg-white/80 shadow-xl backdrop-blur dark:border-white/5 dark:bg-slate-950/70">
        <CardHeader class="space-y-2">
          <CardTitle class="flex items-center gap-2 text-xl">
            <Plus class="h-5 w-5 text-sky-500" />
            Submit leave request
          </CardTitle>
          <CardDescription>Use the refined form layout for fast, low-friction requests.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="isManagerView" class="grid gap-2">
            <Label for="employee">Employee</Label>
            <select id="employee" v-model="form.employee_id" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-sky-500 dark:border-slate-800 dark:bg-slate-900">
              <option value="">Select employee</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                {{ resolveEmployeeDisplayName(employee) }}
              </option>
            </select>
          </div>

          <div class="grid gap-2">
            <Label for="leave_type">Leave type</Label>
            <select id="leave_type" v-model="form.leave_type_id" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-sky-500 dark:border-slate-800 dark:bg-slate-900">
              <option value="">Select leave type</option>
              <option v-for="type in leaveTypes" :key="type.id" :value="type.id">{{ type.name || type.leave_code }}</option>
            </select>
          </div>

          <div class="grid gap-2">
            <Label>Duration</Label>
            <div class="grid grid-cols-3 gap-2">
              <button
                v-for="option in durationOptions"
                :key="option.value"
                type="button"
                @click="form.duration_type = option.value; activeAiAction = 'duration'"
                :class="[
                  'rounded-2xl border px-3 py-2 text-sm transition',
                  isDurationActive(option.value)
                    ? 'border-sky-500 bg-sky-500 text-white shadow-sm'
                    : 'border-slate-200 bg-white hover:border-sky-300 dark:border-slate-800 dark:bg-slate-900'
                ]"
              >
                {{ option.label }}
              </button>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
              <Label for="start_date">Start date</Label>
              <Input id="start_date" v-model="form.start_date" type="date" :class="activeAiAction === 'ai_day' || activeAiAction === 'ai_range' ? 'ring-2 ring-sky-400/60 border-sky-400' : ''" />
            </div>
            <div class="grid gap-2">
              <Label for="end_date">End date</Label>
              <Input id="end_date" v-model="form.end_date" :min="minimumEndDate" :max="maximumEndDate" type="date" :class="activeAiAction === 'ai_day' || activeAiAction === 'ai_range' ? 'ring-2 ring-sky-400/60 border-sky-400' : ''" />
            </div>
          </div>
          <div class="rounded-2xl border border-sky-200/70 bg-sky-50/70 px-4 py-3 text-sm text-sky-900 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-100">
            Selected range: <span class="font-semibold">{{ selectedRangeDays || 0 }}</span> day(s). Allowed range: 1-14 days.
          </div>

          <div class="grid gap-2">
            <Label for="reason">Reason</Label>
            <textarea id="reason" v-model="form.reason" rows="4" class="min-h-[110px] rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-sky-500 dark:border-slate-800 dark:bg-slate-900 dark:placeholder:text-slate-500" placeholder="Add a short reason for the request" />
          </div>

          <div class="grid gap-2">
            <Label for="attachments">Attachments</Label>
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/60">
              <div class="flex items-center gap-3">
                <Upload class="h-5 w-5 text-sky-500" />
                <input id="attachments" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp" class="text-sm" @change="onAttachmentChange">
              </div>
              <div v-if="attachments.length" class="mt-3 flex flex-wrap gap-2">
                <span v-for="file in attachments" :key="file.name + file.size" class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 shadow-sm dark:bg-slate-900 dark:text-slate-200">
                  <Paperclip class="h-3.5 w-3.5" />
                  {{ file.name }}
                  <button type="button" class="text-slate-400 hover:text-rose-500" @click="clearAttachment(file)">
                    <X class="h-3.5 w-3.5" />
                  </button>
                </span>
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between gap-3">
            <div class="text-xs text-slate-500 dark:text-slate-400">Attachments support PDF and common image formats up to 10MB each.</div>
            <Button type="button" class="rounded-2xl px-5" :disabled="submitting" @click="submitRequest">
              <span v-if="submitting">Submitting...</span>
              <span v-else class="inline-flex items-center gap-2"><Plus class="h-4 w-4" /> Submit</span>
            </Button>
          </div>

          <div v-if="formErrors" class="space-y-2">
            <p v-for="(messages, field) in formErrors" :key="field" class="text-sm text-rose-600 dark:text-rose-300">
              {{ Array.isArray(messages) ? messages[0] : messages }}
            </p>
          </div>
        </CardContent>
      </Card>

      <Card class="border-white/10 bg-white/80 shadow-xl backdrop-blur dark:border-white/5 dark:bg-slate-950/70">
        <CardHeader class="space-y-2">
          <CardTitle class="flex items-center gap-2 text-xl">
            <ShieldAlert class="h-5 w-5 text-sky-500" />
            Queue overview
          </CardTitle>
          <CardDescription>Searchable, role-aware list of leave requests.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="relative">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <Input v-model="searchQuery" class="pl-10" placeholder="Search leave requests..." />
          </div>
          <div class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800">
            <DataTable
              :columns="columns"
              :data="filteredRequests"
              :loading="loading"
              search-placeholder="Search leave requests..."
              empty-message="No leave requests found."
            >
              <template #employee="{ row }">
                <div class="min-w-0">
                  <div class="font-medium text-slate-900 dark:text-white">{{ row.employee }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">{{ row.period }}</div>
                </div>
              </template>
              <template #duration_type="{ row }">
                <span class="text-sm text-slate-600 dark:text-slate-300">{{ durationLabel(row.duration_type || 'full_day') }}</span>
              </template>
              <template #status="{ row }">
                <Badge :variant="statusBadgeVariant(row.status)">
                  {{ row.status }}
                </Badge>
              </template>
              <template #reason="{ row }">
                <div class="max-w-[260px] truncate text-sm text-slate-600 dark:text-slate-300">{{ row.reason || 'No reason provided' }}</div>
              </template>
            </DataTable>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-900/40 dark:text-slate-300">
            <div class="flex items-center gap-2 font-medium text-slate-900 dark:text-white">
              <Clock3 class="h-4 w-4 text-sky-500" />
              Approval workflow
            </div>
            <p class="mt-2 leading-6">
              Casual and annual leave require manager approval. Sick leave follows a manager plus HR flow. Current request state stays readable through the pending, approved, and rejected statuses.
            </p>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/40">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
              <CheckCircle2 class="h-4 w-4 text-sky-500" />
              Smart recommendations
            </div>
            <div v-if="leaveAiLoading" class="mt-3 rounded-xl border border-dashed border-slate-200 px-3 py-3 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
              Loading AI suggestions...
            </div>
            <div v-else-if="leaveAiError" class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-200">
              {{ leaveAiError }}
            </div>
            <div v-else-if="aiAppliedKey" class="mt-3 rounded-xl border border-sky-200 bg-sky-50 px-3 py-3 text-sm text-sky-700 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
              AI recommendation applied to the form. You can still edit the dates, range, or duration before submitting.
            </div>
            <div v-else class="mt-3 rounded-xl border border-dashed border-slate-200 px-3 py-3 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
              Pick a range and use the AI suggestions to keep the request within policy.
            </div>
            <div class="mt-3 grid gap-3">
              <div v-for="item in smartRecommendations" :key="item.title" class="rounded-xl border border-white/70 bg-white px-3 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/80">
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">{{ item.title }}</div>
                <div class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">{{ item.value }}</div>
                <div class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ item.hint }}</div>
                <Button type="button" variant="outline" class="mt-3 rounded-xl px-3 py-2 text-xs" @click="applyRecommendation(item)">
                  {{ item.action }}
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card v-if="isManagerView" class="border-white/10 bg-white/80 shadow-xl backdrop-blur dark:border-white/5 dark:bg-slate-950/70">
      <CardHeader>
        <CardTitle>Approval actions</CardTitle>
        <CardDescription>Handle pending requests from the same screen.</CardDescription>
      </CardHeader>
      <CardContent>
        <div class="space-y-3">
          <div v-for="item in filteredRequests.filter((row) => String(row.status).toLowerCase() === 'pending')" :key="item.id" class="flex flex-col gap-3 rounded-2xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
            <div>
              <div class="font-medium text-slate-900 dark:text-white">{{ item.employee }}</div>
              <div class="text-sm text-slate-500 dark:text-slate-400">{{ item.period }} · {{ durationLabel(item.duration_type || 'full_day') }}</div>
            </div>
            <div class="flex items-center gap-2">
              <Button type="button" class="rounded-2xl bg-emerald-600 px-4 text-white hover:bg-emerald-500" :disabled="actionLoadingId === item.id" @click="approve(item)">
                <CheckCircle2 class="mr-2 h-4 w-4" />
                Approve
              </Button>
              <Button type="button" class="rounded-2xl bg-rose-600 px-4 text-white hover:bg-rose-500" :disabled="actionLoadingId === item.id" @click="reject(item)">
                <X class="mr-2 h-4 w-4" />
                Reject
              </Button>
            </div>
          </div>
          <div v-if="!filteredRequests.some((item) => String(item.status).toLowerCase() === 'pending')" class="rounded-2xl border border-dashed border-slate-200 p-6 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
            No pending leave requests right now.
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
