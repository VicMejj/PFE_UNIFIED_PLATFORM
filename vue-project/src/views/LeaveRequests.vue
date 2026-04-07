<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { ArrowUpRight, Brain, Check, RefreshCcw, Send, Sparkles, X } from 'lucide-vue-next'
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
import { unwrapItems } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const auth = useAuthStore()
const notifications = useNotificationsStore()

const requests = ref<any[]>([])
const employees = ref<any[]>([])
const leaveTypes = ref<any[]>([])
const aiSuggestions = ref<any>(null)
const leaveInsights = ref<any | null>(null)
const isLoading = ref(true)
const isSubmitting = ref(false)
const aiLoading = ref(false)
const previewLoading = ref(false)
const actionLoadingId = ref<number | null>(null)
const feedback = ref('')
const errorMsg = ref('')
const dateValidationMessage = ref('')
const overlapMessage = ref('')
const searchQuery = ref('')

const form = reactive({
  employee_id: '',
  leave_type_id: '',
  start_date: '',
  end_date: '',
  reason: ''
})

const smartFieldClass = 'flex h-12 w-full rounded-2xl border border-white/10 bg-[#1d1c18] px-4 py-2 text-sm text-white shadow-inner transition placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20'
const smartTextareaClass = 'min-h-[118px] w-full rounded-2xl border border-white/10 bg-[#1d1c18] px-4 py-3 text-sm text-white shadow-inner transition placeholder:text-slate-500 focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20'
const smartSelectClass = 'flex h-12 w-full rounded-2xl border border-white/10 bg-[#1d1c18] px-4 py-2 text-sm text-white shadow-inner transition focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-500/20'

const userRoles = computed(() =>
  [auth.user?.role, ...(auth.user?.allRoles ?? [])]
    .filter(Boolean)
    .map((role) => String(role).toLowerCase())
)

const isManagerView = computed(() =>
  userRoles.value.some((role) => ['admin', 'rh_manager', 'rh', 'hr', 'manager'].includes(role))
)

const isEmployeeView = computed(() => auth.user?.role === 'employee')

const columns = [
  { key: 'employee', label: 'Employee' },
  { key: 'period', label: 'Period' },
  { key: 'days_requested', label: 'Days' },
  { key: 'status', label: 'Status' },
  { key: 'reason', label: 'Reason' }
]

const filteredRequests = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query || !isManagerView.value) return requests.value

  return requests.value.filter((item) => {
    const haystack = [
      item.employee,
      item.period,
      item.reason,
      item.status,
      item.days_requested,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return haystack.includes(query)
  })
})

const minimumEndDate = computed(() => {
  if (!form.start_date) return ''
  return addDaysToIsoDate(form.start_date, 1)
})

const selectedEmployee = computed(() =>
  employees.value.find((employee) => String(employee.id) === String(form.employee_id))
)

const previewValidationErrors = computed<string[]>(() => {
  const messages: unknown[] = Array.isArray(leaveInsights.value?.validation_errors)
    ? leaveInsights.value.validation_errors
    : []

  return [...new Set(messages.filter((message: unknown): message is string => typeof message === 'string' && Boolean(message)))]
})

const hasBlockingRangeIssue = computed(() =>
  Boolean(dateValidationMessage.value)
  || Boolean(overlapMessage.value)
  || previewValidationErrors.value.length > 0
)

const isEndDateInvalid = computed(() => Boolean(dateValidationMessage.value))

const effectiveWorkingDays = computed(() => {
  if (typeof leaveInsights.value?.working_days === 'number') {
    return leaveInsights.value.working_days
  }

  if (!form.start_date || !form.end_date) return 0

  const start = new Date(`${form.start_date}T00:00:00`)
  const end = new Date(`${form.end_date}T00:00:00`)
  if (isNaN(start.getTime()) || isNaN(end.getTime()) || start > end) return 0

  let count = 0
  for (const cursor = new Date(start); cursor <= end; cursor.setDate(cursor.getDate() + 1)) {
    const day = cursor.getDay()
    if (day !== 0 && day !== 6) count += 1
  }

  return count
})

const policyViolations = computed<string[]>(() => {
  const messages: unknown[] = Array.isArray(leaveInsights.value?.policy_violations)
    ? leaveInsights.value.policy_violations
    : []

  return messages.filter((message: unknown): message is string => typeof message === 'string' && Boolean(message))
})

const supplementalValidationErrors = computed<string[]>(() =>
  previewValidationErrors.value.filter((message: string): message is string =>
    message !== dateValidationMessage.value
    && message !== overlapMessage.value
  )
)

const approvalProbabilityPercent = computed(() => {
  const probability = leaveInsights.value?.approval_probability
  if (typeof probability !== 'number' || Number.isNaN(probability)) return null
  return Math.round(probability * 100)
})

const remainingDays = computed(() => {
  const remaining = leaveInsights.value?.leave_balance?.remaining
  return typeof remaining === 'number' ? remaining : null
})

const activeEmployeeLabel = computed(() =>
  leaveInsights.value?.employee?.name
  || selectedEmployee.value?.display_name
  || auth.user?.name
  || 'Select employee'
)

const isSubmitDisabled = computed(() =>
  isSubmitting.value
  || !form.leave_type_id
  || !form.start_date
  || !form.end_date
  || (isManagerView.value && !form.employee_id)
  || hasBlockingRangeIssue.value
)

const rangeInsightToneClass = computed(() => {
  if (hasBlockingRangeIssue.value) {
    return 'border-rose-500/30 bg-rose-500/15 text-rose-100'
  }

  if (approvalProbabilityPercent.value !== null && approvalProbabilityPercent.value >= 75) {
    return 'border-emerald-500/30 bg-emerald-600/40 text-emerald-50'
  }

  if (approvalProbabilityPercent.value !== null) {
    return 'border-amber-400/30 bg-amber-500/20 text-amber-50'
  }

  return 'border-white/10 bg-white/5 text-slate-200'
})

const rangeInsightText = computed(() => {
  if (previewLoading.value && (form.leave_type_id || form.start_date || form.end_date)) {
    return 'Analyzing workload, company policy, and approval likelihood...'
  }

  if (isManagerView.value && !form.employee_id) {
    return 'Select an employee to unlock leave balance validation and AI-assisted recommendations.'
  }

  if (!form.start_date || !form.end_date) {
    return 'Pick a leave type and a valid date range to see working days and predicted approval probability.'
  }

  if (hasBlockingRangeIssue.value) {
    return 'The current range needs correction before the request can be submitted.'
  }

  const segments: string[] = []
  if (effectiveWorkingDays.value) {
    segments.push(`${effectiveWorkingDays.value} working day${effectiveWorkingDays.value === 1 ? '' : 's'}`)
  }
  if (approvalProbabilityPercent.value !== null) {
    segments.push(`~${approvalProbabilityPercent.value}% approval probability`)
  }

  return segments.length
    ? segments.join(' · ')
    : 'Approval probability will appear once the selected dates pass validation.'
})

const normalizedSuggestions = computed(() => normalizeAiSuggestions(aiSuggestions.value))

function resolveEmployeeDisplayName(employee: any) {
  const fullName = [employee?.first_name, employee?.last_name].filter(Boolean).join(' ').trim()

  return employee?.name
    || employee?.full_name
    || fullName
    || employee?.email
    || employee?.employee_id
    || `Employee #${employee?.id ?? 'Unknown'}`
}

function statusVariant(status: string) {
  if (status === 'approved') return 'success'
  if (status === 'rejected') return 'destructive'
  if (status === 'approved_by_manager') return 'default'
  return 'warning'
}

function mapLeaves(data: any) {
  return unwrapItems<any>(data).map((item) => ({
    ...item,
    employee: resolveEmployeeDisplayName(item.employee ?? { id: item.employee_id }),
    period: `${item.start_date ?? 'N/A'} -> ${item.end_date ?? 'N/A'}`
  }))
}

function handleSearch(value: string) {
  searchQuery.value = value
}

function canApprove(item: any) {
  const status = String(item?.status ?? '').toLowerCase()
  const role = auth.user?.role ?? ''

  if (!isManagerView.value) return false
  if (status === 'approved' || status === 'rejected') return false

  if (role === 'manager') {
    return status === 'pending'
  }

  return status === 'pending' || status === 'approved_by_manager'
}

function canReject(item: any) {
  const status = String(item?.status ?? '').toLowerCase()

  if (!isManagerView.value) return false
  return status === 'pending' || status === 'approved_by_manager'
}

function addDaysToIsoDate(dateValue: string, days: number) {
  const nextDate = new Date(`${dateValue}T00:00:00`)
  nextDate.setDate(nextDate.getDate() + days)
  return nextDate.toISOString().slice(0, 10)
}

function getNextBusinessDay(dateValue: string) {
  const nextDate = new Date(`${dateValue}T00:00:00`)

  do {
    nextDate.setDate(nextDate.getDate() + 1)
  } while ([0, 6].includes(nextDate.getDay()))

  return nextDate.toISOString().slice(0, 10)
}

const debouncedOverlapCheck = useDebounceFn(async () => {
  overlapMessage.value = ''
  if (!form.start_date || !form.end_date) return

  if (minimumEndDate.value && form.end_date < minimumEndDate.value) {
    return
  }

  const start = new Date(`${form.start_date}T00:00:00`)
  const end = new Date(`${form.end_date}T00:00:00`)
  const scopedEmployeeId = isManagerView.value && form.employee_id ? Number(form.employee_id) : undefined

  const overlapping = requests.value
    .filter((item) => !scopedEmployeeId || Number(item.employee_id) === scopedEmployeeId)
    .some((item) => {
      const itemStart = new Date(`${item.start_date}T00:00:00`)
      const itemEnd = new Date(`${item.end_date}T00:00:00`)

      return itemStart <= end && itemEnd >= start && item.id !== undefined
    })

  if (overlapping) {
    overlapMessage.value = 'This date range overlaps an existing leave request.'
  }
}, 400)

const debouncedInsightsPreview = useDebounceFn(async () => {
  if (isManagerView.value && !form.employee_id) {
    leaveInsights.value = null
    previewLoading.value = false
    return
  }

  if (!form.leave_type_id && !form.start_date && !form.end_date) {
    leaveInsights.value = null
    previewLoading.value = false
    return
  }

  previewLoading.value = true

  try {
    leaveInsights.value = await platformApi.previewLeaveRequest({
      employee_id: form.employee_id ? Number(form.employee_id) : undefined,
      leave_type_id: form.leave_type_id ? Number(form.leave_type_id) : undefined,
      start_date: form.start_date || undefined,
      end_date: form.end_date || undefined,
    })
  } catch (error) {
    console.error('Unable to preview leave insights', error)
    leaveInsights.value = null
  } finally {
    previewLoading.value = false
  }
}, 300)

watch(() => form.start_date, (startDate) => {
  overlapMessage.value = ''

  if (!startDate) {
    form.end_date = ''
    dateValidationMessage.value = ''
    return
  }

  if (form.end_date && minimumEndDate.value && form.end_date < minimumEndDate.value) {
    form.end_date = ''
    dateValidationMessage.value = 'End date was cleared. Please choose a date after the selected start date.'
    return
  }

  if (dateValidationMessage.value.startsWith('End date')) {
    dateValidationMessage.value = ''
  }
})

watch([() => form.employee_id, () => form.leave_type_id, () => form.start_date, () => form.end_date], () => {
  overlapMessage.value = ''

  if (!form.start_date || !form.end_date) {
    if (!form.end_date && dateValidationMessage.value === 'End date must be after the selected start date.') {
      dateValidationMessage.value = ''
    }
    debouncedInsightsPreview()
    return
  }

  if (minimumEndDate.value && form.end_date < minimumEndDate.value) {
    dateValidationMessage.value = 'End date must be after the selected start date.'
    debouncedInsightsPreview()
    return
  }

  dateValidationMessage.value = ''
  debouncedOverlapCheck()
  debouncedInsightsPreview()
})

watch(() => form.employee_id, (employeeId, previousEmployeeId) => {
  if (isManagerView.value && employeeId && employeeId !== previousEmployeeId) {
    runAiSuggestions()
  }
})

function toSafeDate(dateValue: string) {
  if (!dateValue) return null
  const date = new Date(`${dateValue}T00:00:00`)
  return Number.isNaN(date.getTime()) ? null : date
}

function formatSuggestionRange(start: string, end?: string) {
  const startDate = toSafeDate(start)
  const endDate = toSafeDate(end || start)

  if (!startDate) return start || 'Suggested date'
  if (!endDate || start === end) {
    return startDate.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    })
  }

  const sameYear = startDate.getFullYear() === endDate.getFullYear()
  const sameMonth = sameYear && startDate.getMonth() === endDate.getMonth()

  if (sameMonth) {
    return `${startDate.toLocaleDateString('en-US', { month: 'short' })} ${startDate.getDate()} - ${endDate.getDate()}, ${endDate.getFullYear()}`
  }

  if (sameYear) {
    return `${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`
  }

  return `${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}`
}

function formatWeekdaySpan(start: string, end?: string, fallbackWeekday?: string) {
  const startDate = toSafeDate(start)
  const endDate = toSafeDate(end || start)

  if (!startDate) return fallbackWeekday || 'Suggested days'
  const startLabel = startDate.toLocaleDateString('en-US', { weekday: 'short' })

  if (!endDate || start === end) {
    return fallbackWeekday || startLabel
  }

  const endLabel = endDate.toLocaleDateString('en-US', { weekday: 'short' })
  return `${startLabel} - ${endLabel}`
}

function calculateDurationDays(start: string, end?: string) {
  const startDate = toSafeDate(start)
  const endDate = toSafeDate(end || start)

  if (!startDate || !endDate) return 1

  const duration = Math.round((endDate.getTime() - startDate.getTime()) / 86400000) + 1
  return Math.max(1, duration)
}

function isLongWeekendSuggestion(start: string, end?: string) {
  const startDate = toSafeDate(start)
  const endDate = toSafeDate(end || start)
  if (!startDate || !endDate) return false

  return startDate.getDay() === 5 || endDate.getDay() === 1 || endDate.getDay() === 0
}

function resolveSuggestionBadge(item: any, index: number) {
  if (index === 0) {
    return { label: 'Best pick', tone: 'best' }
  }

  if (isLongWeekendSuggestion(item.start, item.end)) {
    return { label: 'Long weekend', tone: 'warning' }
  }

  if (item.score >= 0.75) {
    return { label: 'Low load', tone: 'good' }
  }

  return { label: 'Balanced', tone: 'neutral' }
}

function buildSuggestionSubtitle(item: any, badge: { label: string }) {
  const weekdaySpan = formatWeekdaySpan(item.start, item.end, item.weekday)
  const durationLabel = `${item.duration_days} day${item.duration_days === 1 ? '' : 's'}`
  let context = 'Balanced team load'

  if (badge.label === 'Best pick' || badge.label === 'Low load') {
    context = 'Low team load'
  } else if (badge.label === 'Long weekend') {
    context = 'Weekend extension'
  }

  return [weekdaySpan, durationLabel, context].filter(Boolean).join(' · ')
}

function normalizeAiSuggestions(payload: any) {
  const windows = Array.isArray(payload?.recommended_windows) ? payload.recommended_windows : []
  const singleDays = Array.isArray(payload?.recommended_single_days) ? payload.recommended_single_days : []

  const rawSuggestions = [
    ...windows.map((item: any) => ({
      id: `window-${item.start}-${item.end}`,
      start: item.start,
      end: item.end,
      duration_days: Number(item.duration_days ?? calculateDurationDays(item.start, item.end)),
      score: Number(item.avg_suitability ?? item.suitability_score ?? item.score ?? 0),
      weekday: item.weekday,
    })),
    ...singleDays.map((item: any) => ({
      id: `day-${item.date}`,
      start: item.date,
      end: item.date,
      duration_days: 1,
      score: Number(item.suitability_score ?? item.avg_suitability ?? item.score ?? 0),
      weekday: item.weekday,
    })),
  ]
    .filter((item) => item.start)
    .sort((a, b) => b.score - a.score)

  return rawSuggestions.map((item, index) => {
    const badge = resolveSuggestionBadge(item, index)

    return {
      ...item,
      badge,
      title: formatSuggestionRange(item.start, item.end),
      subtitle: buildSuggestionSubtitle(item, badge),
      scoreLabel: item.score.toFixed(2),
    }
  })
}

function suggestionCardClass(index: number) {
  if (index === 0) {
    return 'border-sky-400/60 bg-sky-500/15 shadow-[0_0_0_1px_rgba(96,165,250,0.15)]'
  }

  return 'border-white/10 bg-white/5'
}

function suggestionBadgeClass(tone: string) {
  if (tone === 'best') return 'bg-emerald-500/20 text-emerald-200'
  if (tone === 'warning') return 'bg-amber-500/20 text-amber-200'
  if (tone === 'good') return 'bg-sky-500/20 text-sky-200'
  return 'bg-slate-700/70 text-slate-200'
}

function suggestionScoreClass(score: number) {
  if (score >= 0.78) return 'text-emerald-300'
  if (score >= 0.68) return 'text-sky-300'
  return 'text-amber-300'
}

function applySuggestion(suggestion: any) {
  const suggestedStart = suggestion.start || ''
  const suggestedEnd = suggestion.end || (suggestedStart ? getNextBusinessDay(suggestedStart) : '')

  form.start_date = suggestedStart
  form.end_date = suggestedEnd
  dateValidationMessage.value = ''
  overlapMessage.value = ''
  feedback.value = suggestion.end && suggestion.end !== suggestion.start
    ? 'AI suggestion applied to the leave form.'
    : 'AI suggestion applied. The end date was auto-filled with the next business day to keep the request valid.'
}

async function loadPageData() {
  isLoading.value = true
  errorMsg.value = ''

  try {
    const [leaveData, leaveTypeData] = await Promise.all([
      platformApi.getLeaves(),
      platformApi.getLeaveTypes()
    ])
    requests.value = mapLeaves(leaveData)
    leaveTypes.value = unwrapItems<any>(leaveTypeData)

    if (isManagerView.value) {
      const employeeData = await platformApi.getEmployees()
      employees.value = unwrapItems<any>(employeeData).map((employee: any) => ({
        ...employee,
        display_name: resolveEmployeeDisplayName(employee),
      }))
    } else {
      employees.value = []
    }
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to load leave requests.'
  } finally {
    isLoading.value = false
  }
}

async function submitLeave() {
  isSubmitting.value = true
  feedback.value = ''
  errorMsg.value = ''

  try {
    await platformApi.createLeave({
      employee_id: form.employee_id ? Number(form.employee_id) : undefined,
      leave_type_id: form.leave_type_id ? Number(form.leave_type_id) : undefined,
      start_date: form.start_date,
      end_date: form.end_date,
      reason: form.reason
    })

    feedback.value = 'Leave request submitted successfully.'
    form.employee_id = ''
    form.leave_type_id = ''
    form.start_date = ''
    form.end_date = ''
    form.reason = ''
    leaveInsights.value = null
    await loadPageData()
    await notifications.fetchNotifications()
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to submit the leave request.'
  } finally {
    isSubmitting.value = false
  }
}

async function approve(item: any) {
  actionLoadingId.value = item.id
  feedback.value = ''
  errorMsg.value = ''

  try {
    if (auth.user?.role === 'manager') {
      await platformApi.approveLeaveByManager(item.id)
      feedback.value = `Leave request for ${item.employee} approved by manager.`
    } else {
      await platformApi.approveLeaveByHr(item.id)
      feedback.value = `Leave request for ${item.employee} approved successfully.`
    }

    await loadPageData()
    await notifications.fetchNotifications()
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to approve this leave request.'
  } finally {
    actionLoadingId.value = null
  }
}

async function reject(item: any) {
  actionLoadingId.value = item.id
  feedback.value = ''
  errorMsg.value = ''

  try {
    await platformApi.rejectLeave(item.id, 'Rejected from leave center review.')
    feedback.value = `Leave request for ${item.employee} rejected successfully.`
    await loadPageData()
    await notifications.fetchNotifications()
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to reject this leave request.'
  } finally {
    actionLoadingId.value = null
  }
}

async function runAiSuggestions() {
  if (isManagerView.value && !form.employee_id) {
    feedback.value = 'Select an employee first to generate tailored AI leave suggestions.'
    return
  }

  aiLoading.value = true
  feedback.value = ''

  try {
    aiSuggestions.value = await platformApi.getOptimalLeaveDates(
      form.employee_id ? Number(form.employee_id) : undefined
    )
  } catch (error) {
    console.error('Unable to load AI leave suggestions', error)
    errorMsg.value = 'Unable to generate AI leave suggestions right now.'
  } finally {
    aiLoading.value = false
  }
}

onMounted(async () => {
  await loadPageData()
  if (!isManagerView.value) {
    await runAiSuggestions()
  }
})
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Leave Requests</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Plan smarter leave windows with AI-assisted guidance, policy validation, and live approval prediction.
        </p>
      </div>
      <Button class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" @click="loadPageData">
        <RefreshCcw class="mr-2 h-4 w-4" />
        Refresh
      </Button>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
      <section class="rounded-[2rem] border border-[#4b463b] bg-[linear-gradient(180deg,#2f2b25_0%,#24211d_100%)] p-6 text-white shadow-[0_24px_80px_rgba(15,23,42,0.2)]">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-2xl font-semibold">Submit leave request</h2>
            <p class="mt-1 text-sm text-slate-400">
              {{ isEmployeeView ? 'Your request is validated before submission.' : 'Choose an employee and let AI suggest the best window.' }}
            </p>
          </div>
          <div class="inline-flex items-center gap-2 rounded-full bg-sky-500/15 px-3 py-1 text-xs font-semibold text-sky-200">
            <Sparkles class="h-3.5 w-3.5" />
            AI-assisted
          </div>
        </div>

        <div class="mt-6 space-y-5">
          <div class="space-y-2">
            <div class="flex items-center justify-between gap-3">
              <Label class="text-sm font-medium text-slate-300">Employee</Label>
              <span
                v-if="remainingDays !== null && form.leave_type_id"
                class="inline-flex items-center rounded-full bg-sky-500/15 px-3 py-1 text-xs font-semibold text-sky-200"
              >
                {{ remainingDays }} days remaining
              </span>
            </div>

            <select
              v-if="isManagerView"
              v-model="form.employee_id"
              :class="smartSelectClass"
            >
              <option value="" class="text-slate-900">Select employee</option>
              <option
                v-for="employee in employees"
                :key="employee.id"
                :value="String(employee.id)"
                class="text-slate-900"
              >
                {{ employee.display_name }}
              </option>
            </select>

            <div
              v-else
              class="flex h-12 items-center justify-between rounded-2xl border border-white/10 bg-[#1d1c18] px-4 text-sm text-white shadow-inner"
            >
              <span>{{ activeEmployeeLabel }}</span>
            </div>

            <p v-if="isManagerView && !form.employee_id" class="text-xs text-amber-200/80">
              Choose an employee to unlock leave balance, policy validation, and AI scoring.
            </p>
          </div>

          <div class="space-y-2">
            <Label class="text-sm font-medium text-slate-300">Leave type</Label>
            <select
              v-model="form.leave_type_id"
              :class="smartSelectClass"
            >
              <option value="" class="text-slate-900">Select leave type</option>
              <option
                v-for="type in leaveTypes"
                :key="type.id"
                :value="String(type.id)"
                class="text-slate-900"
              >
                {{ type.name || type.leave_code || `Type #${type.id}` }}
              </option>
            </select>
            <p v-if="!leaveTypes.length" class="text-xs text-amber-200/80">
              No leave types are configured yet. An admin needs to activate them before requests can be submitted.
            </p>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
              <Label class="text-sm font-medium text-slate-300" for="leave-start">Start date</Label>
              <Input
                id="leave-start"
                v-model="form.start_date"
                type="date"
                :class="smartFieldClass"
              />
            </div>

            <div class="space-y-2">
              <Label class="text-sm font-medium text-slate-300" for="leave-end">End date</Label>
              <Input
                id="leave-end"
                v-model="form.end_date"
                type="date"
                :min="minimumEndDate || undefined"
                :disabled="!form.start_date"
                :aria-invalid="isEndDateInvalid ? 'true' : 'false'"
                :class="`${smartFieldClass} ${!form.start_date ? 'cursor-not-allowed bg-[#171612] text-slate-500' : ''} ${isEndDateInvalid ? 'border-rose-400 ring-2 ring-rose-500/20' : ''}`.trim()"
              />

              <p v-if="!form.start_date" class="text-xs text-slate-400">
                Choose a start date first.
              </p>
              <p v-else-if="dateValidationMessage" class="text-xs text-rose-300">
                {{ dateValidationMessage }}
              </p>
              <p v-else class="text-xs text-slate-400">
                End date must be after the selected start date.
              </p>
            </div>
          </div>

          <div :class="['rounded-2xl border px-4 py-3 text-sm font-medium transition', rangeInsightToneClass]">
            {{ rangeInsightText }}
          </div>

          <div v-if="overlapMessage" class="rounded-2xl border border-amber-400/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-100">
            {{ overlapMessage }}
          </div>

          <div v-if="supplementalValidationErrors.length" class="space-y-2">
            <div
              v-for="(message, index) in supplementalValidationErrors"
              :key="String(message) || index"
              class="rounded-2xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-100"
            >
              {{ message }}
            </div>
          </div>

          <div v-if="policyViolations.length" class="flex flex-wrap gap-2">
            <span
              v-for="(note, index) in policyViolations"
              :key="String(note) || index"
              class="inline-flex items-center rounded-full bg-white/8 px-3 py-1 text-xs text-slate-200"
            >
              {{ note }}
            </span>
          </div>

          <div class="space-y-2">
            <Label class="text-sm font-medium text-slate-300" for="leave-reason">Reason (optional)</Label>
            <textarea
              id="leave-reason"
              v-model="form.reason"
              rows="4"
              :class="smartTextareaClass"
              placeholder="Briefly explain the leave request"
            ></textarea>
          </div>

          <div class="flex flex-wrap gap-3">
            <Button
              class="min-w-[10rem] rounded-2xl bg-white text-slate-900 hover:bg-slate-100"
              :disabled="isSubmitDisabled"
              @click="submitLeave"
            >
              <Send class="mr-2 h-4 w-4" />
              {{ isSubmitting ? 'Submitting...' : 'Submit request' }}
            </Button>

            <Button
              class="min-w-[10rem] rounded-2xl border border-white/15 bg-transparent text-white hover:bg-white/5"
              :disabled="aiLoading || (isManagerView && !form.employee_id)"
              @click="runAiSuggestions"
            >
              <Brain class="mr-2 h-4 w-4" />
              {{ aiLoading ? 'Analyzing...' : 'Ask AI' }}
              <ArrowUpRight class="ml-2 h-4 w-4" />
            </Button>
          </div>
        </div>
      </section>

      <section class="rounded-[2rem] border border-[#4b463b] bg-[linear-gradient(180deg,#2c2924_0%,#23201b_100%)] p-6 text-white shadow-[0_24px_80px_rgba(15,23,42,0.2)]">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-2xl font-semibold">AI suggestions</h2>
            <p class="mt-1 text-sm text-slate-400">Low workload periods</p>
          </div>
          <div class="rounded-full border border-white/10 px-3 py-1 text-xs text-slate-300">
            {{ normalizedSuggestions.length || 0 }} options
          </div>
        </div>

        <div class="mt-6 space-y-3">
          <div v-if="aiLoading" class="rounded-2xl border border-white/10 bg-white/5 px-4 py-10 text-center text-sm text-slate-300">
            AI is analyzing your leave windows...
          </div>

          <div
            v-else-if="normalizedSuggestions.length"
            class="space-y-3"
          >
            <button
              v-for="(suggestion, index) in normalizedSuggestions"
              :key="suggestion.id"
              type="button"
              :class="['w-full rounded-2xl border p-4 text-left transition hover:border-sky-400/40 hover:bg-white/10', suggestionCardClass(index)]"
              @click="applySuggestion(suggestion)"
            >
              <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                  <div class="text-xl font-semibold text-white">{{ suggestion.title }}</div>
                  <div class="mt-1 text-sm text-slate-300">{{ suggestion.subtitle }}</div>
                </div>

                <div class="flex shrink-0 flex-col items-end gap-2">
                  <div :class="['text-2xl font-semibold leading-none', suggestionScoreClass(suggestion.score)]">
                    {{ suggestion.scoreLabel }}
                  </div>
                  <span :class="['rounded-full px-3 py-1 text-xs font-semibold', suggestionBadgeClass(suggestion.badge.tone)]">
                    {{ suggestion.badge.label }}
                  </span>
                </div>
              </div>
            </button>
          </div>

          <div v-else class="rounded-2xl border border-dashed border-white/10 px-4 py-10 text-center text-sm text-slate-400">
            No suggestions yet. Use the AI action to generate the best leave windows.
          </div>
        </div>

        <div class="mt-6 border-t border-white/10 pt-4 text-sm text-slate-400">
          Score = workload suitability. Click any suggestion to auto-fill the leave form.
        </div>
      </section>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>{{ isEmployeeView ? 'My Leave History' : 'Leave History' }}</CardTitle>
        <CardDescription>
          {{ isEmployeeView
            ? 'Only your leave requests are shown here. Employee history is scoped to your own account.'
            : 'Managers, RH, and admins can review and act on leave requests directly from this table.' }}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <DataTable
          :columns="columns"
          :data="filteredRequests"
          :loading="isLoading"
          :page-size="10"
          :max-body-height="'32rem'"
          :search-placeholder="isManagerView ? 'Search by employee name or request details...' : undefined"
          :empty-message="isEmployeeView ? 'You have not submitted any leave requests yet.' : 'No leave requests found.'"
          @search="handleSearch"
        >
          <template #cell(status)="{ value }">
            <Badge :variant="statusVariant(String(value ?? ''))">{{ value ?? 'Unknown' }}</Badge>
          </template>

          <template #actions="{ item }">
            <div v-if="canApprove(item) || canReject(item)" class="flex justify-end gap-2">
              <Button
                v-if="canApprove(item)"
                class="h-8 bg-emerald-600 px-3 text-xs text-white hover:bg-emerald-700"
                :disabled="actionLoadingId === item.id"
                @click.stop="approve(item)"
              >
                <Check class="mr-1 h-3.5 w-3.5" />
                {{ actionLoadingId === item.id ? 'Working...' : auth.user?.role === 'manager' ? 'Approve' : 'Accept' }}
              </Button>

              <Button
                v-if="canReject(item)"
                class="h-8 bg-red-600 px-3 text-xs text-white hover:bg-red-700"
                :disabled="actionLoadingId === item.id"
                @click.stop="reject(item)"
              >
                <X class="mr-1 h-3.5 w-3.5" />
                {{ actionLoadingId === item.id ? 'Working...' : 'Reject' }}
              </Button>
            </div>

            <span v-else class="text-xs text-slate-400">No action</span>
          </template>
        </DataTable>
      </CardContent>
    </Card>
  </div>
</template>
