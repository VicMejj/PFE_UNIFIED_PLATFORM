<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { CalendarDays, CheckCheck, Clock3, Filter, Loader2, RefreshCcw, Save, Search, TimerReset } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import Badge from '@/components/ui/Badge.vue'
import Dialog from '@/components/ui/Dialog.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

type AttendanceRow = {
  id: number
  employee_id: number
  employee?: { name?: string; full_name?: string }
  date?: string
  timesheet_date?: string
  check_in?: string | null
  check_out?: string | null
  status?: string
  notes?: string | null
  remarks?: string | null
  task_description?: string | null
  project_name?: string | null
}

const records = ref<AttendanceRow[]>([])
const employees = ref<any[]>([])
const auth = useAuthStore()
const stats = ref<any>(null)
const loading = ref(true)
const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const formErrors = ref<Record<string, string[]>>({})
const searchQuery = ref('')
const editingRecord = ref<AttendanceRow | null>(null)
const deleteRecord = ref<AttendanceRow | null>(null)

const filters = reactive({
  employee_id: '',
  date_from: new Date().toISOString().slice(0, 10),
  date_to: new Date().toISOString().slice(0, 10),
  status: '',
})

const form = reactive({
  employee_id: '',
  date: new Date().toISOString().slice(0, 10),
  check_in: '09:00',
  check_out: '17:00',
  status: 'present',
  notes: '',
})

const isEmployeeSelfService = computed(() => String(auth.user?.role ?? '') === 'employee')
const canManageAttendance = computed(() => ['admin', 'rh_manager', 'manager'].includes(String(auth.user?.role ?? '')))

const columns = [
  { key: 'employee', label: 'Employee' },
  { key: 'date', label: 'Date' },
  { key: 'check_in', label: 'Check-in' },
  { key: 'check_out', label: 'Check-out' },
  { key: 'status', label: 'Status' },
  { key: 'remarks', label: 'Notes' },
]

const filteredRecords = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  if (!query) return records.value

  return records.value.filter((row) =>
    [
      row.employee?.name,
      row.employee?.full_name,
      row.date,
      row.timesheet_date,
      row.check_in,
      row.check_out,
      row.status,
      row.notes,
      row.remarks,
      row.project_name,
      row.task_description,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

const employeeRecords = computed(() => {
  if (!isEmployeeSelfService.value) return filteredRecords.value
  return filteredRecords.value.slice(0, 8)
})

const employeeRecentRecord = computed(() => employeeRecords.value[0] ?? null)

const employeeRecordCount = computed(() => filteredRecords.value.length)
const todayIso = new Date().toISOString().slice(0, 10)

function normalizeRow(row: AttendanceRow) {
  return {
    ...row,
    employee: row.employee ?? { name: `Employee #${row.employee_id}` },
    date: row.date ?? row.timesheet_date ?? 'N/A',
    remarks: row.notes ?? row.remarks ?? row.task_description ?? '—',
  }
}

function resolveEmployeeName(employee: any) {
  return employee?.name || employee?.full_name || `Employee #${employee?.id ?? 'Unknown'}`
}

function statusVariant(status?: string) {
  const value = String(status ?? '').toLowerCase()
  if (value === 'present') return 'success'
  if (value === 'late') return 'warning'
  if (value === 'absent') return 'destructive'
  if (value === 'leave') return 'secondary'
  return 'secondary'
}

function statusLabel(status?: string) {
  const value = String(status ?? '').toLowerCase()
  if (value === 'half_day') return 'Half day'
  if (value === 'on_leave' || value === 'leave') return 'On leave'
  if (!value) return 'Unknown'
  return value.charAt(0).toUpperCase() + value.slice(1).replace(/_/g, ' ')
}

function formatDateLabel(value?: string) {
  if (!value) return 'N/A'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('en', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(date)
}

function currentTimeValue() {
  return new Intl.DateTimeFormat('en-GB', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone,
  }).format(new Date())
}

function setQuickPunch(status: 'present' | 'late', timeType: 'in' | 'out') {
  form.date = new Date().toISOString().slice(0, 10)
  form.status = status
  if (timeType === 'in') {
    form.check_in = currentTimeValue()
  } else {
    form.check_out = currentTimeValue()
  }
  if (isEmployeeSelfService.value && !form.notes) {
    form.notes = status === 'late' ? 'Checked in late.' : 'Checked in from quick action.'
  }
}

function parseErrors(err: any) {
  formErrors.value = err?.response?.data?.errors ?? {}
  errorMessage.value = err?.response?.data?.message ?? 'Unable to save attendance.'
}

function openEdit(record: AttendanceRow) {
  editingRecord.value = { ...record }
}

function closeEdit() {
  editingRecord.value = null
  formErrors.value = {}
}

function openDelete(record: AttendanceRow) {
  deleteRecord.value = record
}

function closeDelete() {
  deleteRecord.value = null
}

async function loadEmployees() {
  if (isEmployeeSelfService.value && auth.user?.employee_id) {
    form.employee_id = String(auth.user.employee_id)
    employees.value = []
    return
  }

  const employeeData = await platformApi.getEmployees()
  employees.value = unwrapItems(employeeData)
}

async function loadAttendance() {
  loading.value = true
  errorMessage.value = ''
  try {
    const payload: Record<string, unknown> = {
      date_from: filters.date_from,
      date_to: filters.date_to,
    }
    if (canManageAttendance.value && filters.employee_id) payload.employee_id = Number(filters.employee_id)
    if (canManageAttendance.value && filters.status) payload.status = filters.status

    const [attendanceData, statsData] = await Promise.all([
      platformApi.getAttendanceRecords(payload),
      platformApi.getAttendanceStatistics(payload),
    ])

    records.value = unwrapItems<AttendanceRow>(attendanceData).map(normalizeRow)
    stats.value = statsData
  } catch (err: any) {
    console.error('Failed to load attendance', err)
    errorMessage.value = err?.response?.data?.message ?? 'Unable to load attendance records.'
  } finally {
    loading.value = false
  }
}

async function recordAttendance() {
  submitting.value = true
  successMessage.value = ''
  errorMessage.value = ''
  formErrors.value = {}

  try {
    await platformApi.createAttendanceRecord({
      employee_id: Number(form.employee_id),
      date: form.date,
      check_in: form.check_in,
      check_out: form.check_out,
      status: form.status,
      notes: form.notes,
    })

    successMessage.value = 'Attendance recorded successfully.'
    form.notes = ''
    await loadAttendance()
  } catch (err: any) {
    parseErrors(err)
  } finally {
    submitting.value = false
  }
}

async function saveEdit() {
  if (!editingRecord.value) return
  submitting.value = true
  successMessage.value = ''
  errorMessage.value = ''
  formErrors.value = {}

  try {
    await platformApi.updateAttendanceRecord(editingRecord.value.id, {
      check_in: editingRecord.value.check_in,
      check_out: editingRecord.value.check_out,
      status: editingRecord.value.status,
      notes: editingRecord.value.remarks,
    })

    successMessage.value = 'Attendance updated successfully.'
    closeEdit()
    await loadAttendance()
  } catch (err: any) {
    parseErrors(err)
  } finally {
    submitting.value = false
  }
}

async function removeAttendance() {
  if (!deleteRecord.value) return
  submitting.value = true
  successMessage.value = ''
  errorMessage.value = ''
  try {
    await platformApi.deleteAttendanceRecord(deleteRecord.value.id)
    successMessage.value = 'Attendance deleted successfully.'
    closeDelete()
    await loadAttendance()
  } catch (err: any) {
    parseErrors(err)
  } finally {
    submitting.value = false
  }
}

function resetFilters() {
  if (canManageAttendance.value) {
    filters.employee_id = ''
    filters.status = ''
  }
  filters.date_from = new Date().toISOString().slice(0, 10)
  filters.date_to = new Date().toISOString().slice(0, 10)
  loadAttendance()
}

watch(() => [filters.employee_id, filters.date_from, filters.date_to, filters.status], () => {
  if (canManageAttendance.value) loadAttendance()
})

onMounted(async () => {
  if (isEmployeeSelfService.value && auth.user?.employee_id) {
    form.employee_id = String(auth.user.employee_id)
  }
  await Promise.all([loadEmployees(), loadAttendance()])
})
</script>

<template>
  <div class="space-y-6">
    <section class="relative overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 p-6 text-white shadow-2xl dark:border-slate-800">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,0.18),_transparent_35%),radial-gradient(circle_at_bottom_left,_rgba(99,102,241,0.14),_transparent_30%)]"></div>
      <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-sky-100">
            <CalendarDays class="h-3.5 w-3.5" />
            Attendance workspace
          </div>
          <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
            {{ isEmployeeSelfService ? 'My Attendance' : 'Attendance Tracking' }}
          </h1>
          <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
            {{ isEmployeeSelfService
              ? 'Record your daily attendance and review your own history.'
              : 'Record daily attendance, filter historical entries, and review live status counts from one clean workspace.' }}
          </p>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-300">Present</div>
            <div class="mt-2 text-2xl font-semibold">{{ stats?.present_today ?? 0 }}</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-300">Late</div>
            <div class="mt-2 text-2xl font-semibold">{{ stats?.late_today ?? 0 }}</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-300">Absent</div>
            <div class="mt-2 text-2xl font-semibold">{{ stats?.absent_today ?? 0 }}</div>
          </div>
          <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
            <div class="text-[10px] uppercase tracking-[0.2em] text-slate-300">Leave</div>
            <div class="mt-2 text-2xl font-semibold">{{ stats?.on_leave_today ?? 0 }}</div>
          </div>
        </div>
      </div>
    </section>

    <div v-if="successMessage" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ successMessage }}
    </div>
    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
      <Card class="border-slate-200 bg-white/90 shadow-xl backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
        <CardHeader class="space-y-2">
          <CardTitle class="flex items-center gap-2 text-xl">
            <Save class="h-5 w-5 text-sky-500" />
            {{ isEmployeeSelfService ? 'Record my attendance' : 'Record attendance' }}
          </CardTitle>
          <CardDescription>
            {{ isEmployeeSelfService
              ? 'Capture your check-in and check-out for today. Your record will be visible to admins and managers immediately.'
              : 'Capture check-in and check-out details for the day.' }}
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="isEmployeeSelfService" class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60">
              <div class="text-[11px] uppercase tracking-[0.24em] text-slate-400">Linked profile</div>
              <div class="mt-2 text-sm font-medium text-slate-900 dark:text-white">
                {{ auth.user?.employee_id ? `Employee #${auth.user.employee_id}` : 'Not linked yet' }}
              </div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60">
              <div class="text-[11px] uppercase tracking-[0.24em] text-slate-400">Your records</div>
              <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ employeeRecordCount }}</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/60">
              <div class="text-[11px] uppercase tracking-[0.24em] text-slate-400">Latest status</div>
              <div class="mt-2">
                <Badge v-if="employeeRecentRecord" :variant="statusVariant(employeeRecentRecord.status)">
                  {{ statusLabel(employeeRecentRecord.status) }}
                </Badge>
                <span v-else class="text-sm text-slate-500 dark:text-slate-400">No records yet</span>
              </div>
            </div>
          </div>

          <div v-if="isEmployeeSelfService" class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300">
                  <Clock3 class="h-3.5 w-3.5" />
                  Quick punch
                </div>
                <div class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">Prefill today’s attendance in one tap</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">Use a one-tap action to prefill today’s attendance.</div>
              </div>
              <div class="flex flex-wrap gap-2">
                <Button variant="outline" class="rounded-2xl border-sky-200 bg-white text-sky-700 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:bg-sky-50 dark:border-sky-900/50 dark:bg-slate-950 dark:text-sky-300 dark:hover:border-sky-700 dark:hover:bg-sky-950/40" @click="setQuickPunch('present', 'in')">
                  <CheckCheck class="mr-2 h-4 w-4" />
                  Check in now
                </Button>
                <Button variant="outline" class="rounded-2xl border-slate-200 bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-slate-700 dark:hover:bg-slate-900" @click="setQuickPunch('present', 'out')">
                  <TimerReset class="mr-2 h-4 w-4" />
                  Check out now
                </Button>
                <Button variant="outline" class="rounded-2xl border-amber-200 bg-white text-amber-700 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:bg-amber-50 dark:border-amber-900/50 dark:bg-slate-950 dark:text-amber-300 dark:hover:border-amber-700 dark:hover:bg-amber-950/40" @click="setQuickPunch('late', 'in')">
                  <Clock3 class="mr-2 h-4 w-4" />
                  Mark late
                </Button>
              </div>
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div v-if="!isEmployeeSelfService" class="space-y-2 sm:col-span-2">
              <Label for="employee_id">Employee</Label>
              <select id="employee_id" v-model="form.employee_id" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-sky-500 dark:border-slate-800 dark:bg-slate-900">
                <option value="">Select employee</option>
                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                  {{ resolveEmployeeName(employee) }}
                </option>
              </select>
              <p v-if="formErrors.employee_id?.length" class="text-xs text-rose-600 dark:text-rose-300">{{ formErrors.employee_id[0] }}</p>
            </div>

            <div v-else class="space-y-2 sm:col-span-2">
              <Label>Employee</Label>
              <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-200">
                Attendance will be recorded for your linked employee profile.
              </div>
            </div>

            <div class="space-y-2">
              <Label for="date">Date</Label>
              <Input id="date" v-model="form.date" type="date" />
              <p v-if="formErrors.date?.length" class="text-xs text-rose-600 dark:text-rose-300">{{ formErrors.date[0] }}</p>
            </div>

            <div class="space-y-2">
              <Label for="status">Status</Label>
              <select id="status" v-model="form.status" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-sky-500 dark:border-slate-800 dark:bg-slate-900">
                <option value="present">Present</option>
                <option value="late">Late</option>
                <option value="absent">Absent</option>
                <option value="half_day">Half day</option>
                <option value="leave">On leave</option>
              </select>
              <p v-if="formErrors.status?.length" class="text-xs text-rose-600 dark:text-rose-300">{{ formErrors.status[0] }}</p>
            </div>

            <div class="space-y-2">
              <Label for="check_in">Check-in</Label>
              <Input id="check_in" v-model="form.check_in" type="time" />
              <p v-if="formErrors.check_in?.length" class="text-xs text-rose-600 dark:text-rose-300">{{ formErrors.check_in[0] }}</p>
            </div>

            <div class="space-y-2">
              <Label for="check_out">Check-out</Label>
              <Input id="check_out" v-model="form.check_out" type="time" />
              <p v-if="formErrors.check_out?.length" class="text-xs text-rose-600 dark:text-rose-300">{{ formErrors.check_out[0] }}</p>
            </div>

            <div class="space-y-2 sm:col-span-2">
              <Label for="notes">Notes</Label>
              <Input id="notes" v-model="form.notes" placeholder="Optional notes" />
              <p v-if="formErrors.notes?.length" class="text-xs text-rose-600 dark:text-rose-300">{{ formErrors.notes[0] }}</p>
            </div>
          </div>

          <div class="flex justify-end">
            <Button class="rounded-2xl px-5" :disabled="submitting || !form.employee_id" @click="recordAttendance">
              <Loader2 v-if="submitting" class="mr-2 h-4 w-4 animate-spin" />
              <Save v-else class="mr-2 h-4 w-4" />
              Record Attendance
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card class="border-slate-200 bg-white/90 shadow-xl backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
        <CardHeader class="space-y-2">
          <CardTitle class="flex items-center gap-2 text-xl">
            <Filter class="h-5 w-5 text-sky-500" />
            {{ canManageAttendance ? 'Filters & records' : 'My history' }}
          </CardTitle>
          <CardDescription>
            {{ canManageAttendance
              ? 'Filter by employee, date, and status. Pagination is handled by the table.'
              : 'A clean timeline of your own attendance. Admins and managers can still see the full record set in the dashboard.' }}
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="canManageAttendance" class="grid gap-3 md:grid-cols-4">
            <div class="relative md:col-span-2">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
              <Input v-model="searchQuery" class="pl-10" placeholder="Search records..." />
            </div>
            <select v-model="filters.employee_id" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-sky-500 dark:border-slate-800 dark:bg-slate-900">
              <option value="">All employees</option>
              <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                {{ resolveEmployeeName(employee) }}
              </option>
            </select>
            <select v-model="filters.status" class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-sky-500 dark:border-slate-800 dark:bg-slate-900">
              <option value="">All statuses</option>
              <option value="present">Present</option>
              <option value="late">Late</option>
              <option value="absent">Absent</option>
              <option value="half_day">Half day</option>
              <option value="leave">On leave</option>
            </select>
            <div class="space-y-2">
              <Label class="sr-only" for="date_from">From</Label>
              <Input id="date_from" v-model="filters.date_from" type="date" />
            </div>
            <div class="space-y-2">
              <Label class="sr-only" for="date_to">To</Label>
              <Input id="date_to" v-model="filters.date_to" type="date" />
            </div>
            <div class="flex items-end md:col-span-2">
              <Button variant="outline" class="w-full rounded-2xl" @click="resetFilters">
                <RefreshCcw class="mr-2 h-4 w-4" />
                Reset filters
              </Button>
            </div>
          </div>

          <div v-if="isEmployeeSelfService" class="space-y-4">
            <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-4 dark:border-slate-800 dark:from-slate-900/70 dark:to-slate-950">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-slate-900 dark:text-white">Recent attendance</div>
                  <div class="text-sm text-slate-500 dark:text-slate-400">A quick timeline of your latest records.</div>
                </div>
                <Badge :variant="employeeRecentRecord ? statusVariant(employeeRecentRecord.status) : 'secondary'">
                  {{ employeeRecentRecord ? statusLabel(employeeRecentRecord.status) : 'No recent record' }}
                </Badge>
              </div>

              <div v-if="employeeRecords.length" class="mt-4 space-y-3">
                <article
                  v-for="item in employeeRecords"
                  :key="item.id"
                  class="group relative rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-200 hover:shadow-md dark:border-slate-800 dark:bg-slate-950/70 dark:hover:border-sky-700/60"
                >
                  <div class="absolute left-5 top-0 h-full w-px bg-gradient-to-b from-sky-400/70 via-slate-200 to-transparent dark:via-slate-700" />
                  <div
                    class="absolute left-3.5 top-5 h-3 w-3 rounded-full border-2 border-white bg-sky-500 shadow-sm dark:border-slate-950"
                    :class="item.timesheet_date === todayIso ? 'ring-4 ring-sky-500/15' : ''"
                  />
                  <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-1 pl-8">
                      <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ formatDateLabel(item.date ?? item.timesheet_date) }}</h3>
                        <Badge v-if="(item.date ?? item.timesheet_date) === todayIso" variant="secondary">Today</Badge>
                      </div>
                      <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span>Check-in: {{ item.check_in ?? '—' }}</span>
                        <span>•</span>
                        <span>Check-out: {{ item.check_out ?? '—' }}</span>
                        <span>•</span>
                        <span>{{ item.project_name ?? 'General attendance' }}</span>
                      </div>
                    </div>
                    <Badge :variant="statusVariant(item.status)">
                      {{ statusLabel(item.status) }}
                    </Badge>
                  </div>
                  <p class="mt-3 pl-8 text-sm leading-6 text-slate-600 dark:text-slate-300">
                    {{ item.notes || item.remarks || item.task_description || 'No notes added for this record.' }}
                  </p>
                </article>
              </div>

              <div v-else class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
                No attendance history yet. Record your first attendance above and it will appear here.
              </div>
            </div>
          </div>

          <div v-else class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
              <div class="text-xs uppercase tracking-wide text-slate-400">Present</div>
              <div class="mt-2 text-2xl font-black">{{ stats?.present_today ?? 0 }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
              <div class="text-xs uppercase tracking-wide text-slate-400">Late</div>
              <div class="mt-2 text-2xl font-black">{{ stats?.late_today ?? 0 }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
              <div class="text-xs uppercase tracking-wide text-slate-400">Absent</div>
              <div class="mt-2 text-2xl font-black">{{ stats?.absent_today ?? 0 }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
              <div class="text-xs uppercase tracking-wide text-slate-400">Leave</div>
              <div class="mt-2 text-2xl font-black">{{ stats?.on_leave_today ?? 0 }}</div>
            </div>
          </div>

          <div v-if="canManageAttendance" class="overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800">
            <DataTable
              :columns="columns"
              :data="filteredRecords"
              :loading="loading"
              :pageSize="10"
              :searchPlaceholder="canManageAttendance ? 'Search records...' : undefined"
              emptyMessage="No attendance records found."
            >
              <template #cell(employee)="{ item }">
                <div class="min-w-0">
                  <div class="font-medium text-slate-900 dark:text-white">{{ item.employee?.name || item.employee?.full_name || `Employee #${item.employee_id}` }}</div>
                  <div class="text-xs text-slate-500 dark:text-slate-400">ID #{{ item.employee_id }}</div>
                </div>
              </template>
              <template #cell(status)="{ value }">
                <Badge :variant="statusVariant(value)">
                  {{ value }}
                </Badge>
              </template>
              <template v-if="canManageAttendance" #actions="{ item }">
                <div class="flex justify-end gap-2">
                  <Button variant="outline" class="rounded-xl px-3" @click.stop="openEdit(item)">
                    Edit
                  </Button>
                  <Button variant="destructive" class="rounded-xl px-3" @click.stop="openDelete(item)">
                    Delete
                  </Button>
                </div>
              </template>
            </DataTable>
          </div>
        </CardContent>
      </Card>
    </div>

    <Dialog v-if="canManageAttendance" :open="Boolean(editingRecord)" title="Edit Attendance" description="Update the selected attendance record." size="2xl" @close="closeEdit">
      <div v-if="editingRecord" class="grid gap-4 sm:grid-cols-2">
        <div class="space-y-2">
          <Label>Check-in</Label>
          <Input
            :model-value="editingRecord.check_in ?? ''"
            type="time"
            @update:modelValue="(value) => editingRecord && (editingRecord.check_in = String(value))"
          />
        </div>
        <div class="space-y-2">
          <Label>Check-out</Label>
          <Input
            :model-value="editingRecord.check_out ?? ''"
            type="time"
            @update:modelValue="(value) => editingRecord && (editingRecord.check_out = String(value))"
          />
        </div>
        <div class="space-y-2">
          <Label>Status</Label>
          <select v-model="editingRecord.status" class="h-11 w-full rounded-2xl border border-slate-200 bg-white px-4 text-sm outline-none transition focus:border-sky-500 dark:border-slate-800 dark:bg-slate-900">
            <option value="present">Present</option>
            <option value="late">Late</option>
            <option value="absent">Absent</option>
            <option value="half_day">Half day</option>
            <option value="leave">On leave</option>
          </select>
        </div>
        <div class="space-y-2 sm:col-span-2">
          <Label>Notes</Label>
          <Input
            :model-value="editingRecord.remarks ?? ''"
            placeholder="Notes"
            @update:modelValue="(value) => editingRecord && (editingRecord.remarks = String(value))"
          />
        </div>
        <div class="sm:col-span-2 flex justify-end gap-3">
          <Button variant="outline" class="rounded-2xl" @click="closeEdit">Cancel</Button>
          <Button class="rounded-2xl" :disabled="submitting" @click="saveEdit">Save changes</Button>
        </div>
      </div>
    </Dialog>

    <Dialog v-if="canManageAttendance" :open="Boolean(deleteRecord)" title="Delete Attendance" description="This action cannot be undone." size="md" @close="closeDelete">
      <div v-if="deleteRecord" class="space-y-4">
        <p class="text-sm text-slate-600 dark:text-slate-300">
          Delete attendance for {{ deleteRecord.employee?.name || `Employee #${deleteRecord.employee_id}` }} on {{ deleteRecord.date ?? deleteRecord.timesheet_date }}?
        </p>
        <div class="flex justify-end gap-3">
          <Button variant="outline" class="rounded-2xl" @click="closeDelete">Cancel</Button>
          <Button variant="destructive" class="rounded-2xl" :disabled="submitting" @click="removeAttendance">Delete</Button>
        </div>
      </div>
    </Dialog>
  </div>
</template>
