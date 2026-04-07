<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  Bell,
  CalendarDays,
  CalendarRange,
  FileSignature,
  ShieldCheck,
  Users,
  Wallet,
} from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import { platformApi, type EventItem } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'

const router = useRouter()

const dashboardHome = ref<any>(null)
const employees = ref<any[]>([])
const leaves = ref<any[]>([])
const paySlips = ref<any[]>([])
const contracts = ref<any[]>([])
const notifications = ref<any[]>([])
const events = ref<EventItem[]>([])
const isLoading = ref(true)

const today = new Date()

function normalizeDate(value?: string | null) {
  if (!value) return null
  const parsed = new Date(value)
  return Number.isNaN(parsed.getTime()) ? null : parsed
}

function formatLongDate(value?: string | null) {
  const parsed = normalizeDate(value)
  if (!parsed) return 'Date not set'

  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(parsed)
}

function formatMonthLabel(date: Date) {
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    year: 'numeric',
  }).format(date)
}

function formatWeekday(date: Date) {
  return new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date)
}

function formatTime(value?: string | null) {
  if (!value) return 'All day'

  const directDate = normalizeDate(value)
  if (directDate) {
    return new Intl.DateTimeFormat('en-US', {
      hour: 'numeric',
      minute: '2-digit',
    }).format(directDate)
  }

  const todayIso = new Date().toISOString().slice(0, 10)
  const parsed = normalizeDate(`${todayIso}T${value}`)
  if (!parsed) return value

  return new Intl.DateTimeFormat('en-US', {
    hour: 'numeric',
    minute: '2-digit',
  }).format(parsed)
}

const dashboardStats = computed(() => dashboardHome.value?.statistics ?? {
  total_employees: employees.value.length,
  active_employees: employees.value.filter((item) => item.is_active).length,
  on_leave_employees: 0,
  new_employees_this_month: 0,
})

const pendingLeavesCount = computed(() =>
  leaves.value.filter((item) => String(item.status ?? '').toLowerCase() === 'pending').length
)

const pendingPaySlipsCount = computed(() =>
  paySlips.value.filter((item) => String(item.status ?? '').toLowerCase() !== 'sent').length
)

const activeEvents = computed(() =>
  events.value
    .filter((item) => item.is_active !== false)
    .sort((left, right) => {
      const leftTime = normalizeDate(left.event_date)?.getTime() ?? Number.MAX_SAFE_INTEGER
      const rightTime = normalizeDate(right.event_date)?.getTime() ?? Number.MAX_SAFE_INTEGER
      return leftTime - rightTime
    })
)

const upcomingEvents = computed(() => activeEvents.value.slice(0, 5))

const summaryCards = computed(() => [
  {
    label: 'Employees',
    value: dashboardStats.value.total_employees,
    description: `${dashboardStats.value.active_employees} active across the organization.`,
    icon: Users,
    accent: 'bg-sky-500',
  },
  {
    label: 'Leave Queue',
    value: pendingLeavesCount.value,
    description: 'Requests currently waiting for review.',
    icon: CalendarRange,
    accent: 'bg-amber-500',
  },
  {
    label: 'Payroll Actions',
    value: pendingPaySlipsCount.value,
    description: 'Payslips still pending generation or send.',
    icon: Wallet,
    accent: 'bg-emerald-500',
  },
  {
    label: 'Contracts',
    value: contracts.value.length,
    description: 'Contract records ready for admin oversight.',
    icon: FileSignature,
    accent: 'bg-violet-500',
  },
])

const dayHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

const calendarDays = computed(() => {
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
  const daysInMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0).getDate()
  const startOffset = firstDay.getDay()
  const cells: Array<{
    key: string
    dayNumber: number | null
    isoDate: string | null
    isToday: boolean
    eventCount: number
  }> = []

  for (let i = 0; i < startOffset; i += 1) {
    cells.push({
      key: `empty-start-${i}`,
      dayNumber: null,
      isoDate: null,
      isToday: false,
      eventCount: 0,
    })
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = new Date(today.getFullYear(), today.getMonth(), day)
    const isoDate = date.toISOString().slice(0, 10)
    const eventCount = activeEvents.value.filter((item) => (item.event_date ?? '').slice(0, 10) === isoDate).length

    cells.push({
      key: isoDate,
      dayNumber: day,
      isoDate,
      isToday: isoDate === new Date().toISOString().slice(0, 10),
      eventCount,
    })
  }

  while (cells.length % 7 !== 0) {
    cells.push({
      key: `empty-end-${cells.length}`,
      dayNumber: null,
      isoDate: null,
      isToday: false,
      eventCount: 0,
    })
  }

  return cells
})

const priorityItems = computed(() => [
  {
    title: 'Pending leave approvals',
    value: pendingLeavesCount.value,
    tone: pendingLeavesCount.value > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400',
    description: pendingLeavesCount.value > 0
      ? 'Managers and RH still have requests to review.'
      : 'The leave queue is clear right now.',
  },
  {
    title: 'New hires this month',
    value: dashboardStats.value.new_employees_this_month,
    tone: 'text-sky-600 dark:text-sky-400',
    description: 'Fresh employee records created during the current month.',
  },
  {
    title: 'Unread signals',
    value: notifications.value.length,
    tone: 'text-violet-600 dark:text-violet-400',
    description: 'Notifications that may need admin attention today.',
  },
])

onMounted(async () => {
  isLoading.value = true

  try {
    const [
      homeData,
      employeeData,
      leaveData,
      paySlipData,
      contractData,
      notificationData,
      eventData,
    ] = await Promise.all([
      platformApi.getHomeDashboard(),
      platformApi.getEmployees(),
      platformApi.getLeaves(),
      platformApi.getPaySlips(),
      platformApi.getContracts(),
      platformApi.getNotifications(),
      platformApi.getEvents(),
    ])

    dashboardHome.value = homeData
    employees.value = unwrapItems(employeeData)
    leaves.value = unwrapItems(leaveData)
    paySlips.value = unwrapItems(paySlipData)
    contracts.value = unwrapItems(contractData)
    notifications.value = Array.isArray(notificationData) ? notificationData : []
    events.value = unwrapItems(eventData)
  } catch (error) {
    console.error('Unable to load admin dashboard data', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-sky-700 dark:border-sky-900/60 dark:bg-sky-950/40 dark:text-sky-300">
          <ShieldCheck class="h-3.5 w-3.5" />
          Admin Oversight
        </div>
        <h1 class="mt-3 text-3xl font-bold text-slate-900 dark:text-white">Admin Command Center</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-500 dark:text-slate-400">
          Live overview of employees, leave pressure, payroll activity, contracts, notifications, and upcoming events.
        </p>
      </div>

      <div class="flex flex-wrap gap-3">
        <Button class="bg-blue-600 hover:bg-blue-700" @click="router.push('/rh/employees')">
          Open Employees
        </Button>
        <Button class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" @click="router.push('/rh/leaves')">
          Review Leave Queue
        </Button>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Card v-for="card in summaryCards" :key="card.label" class="overflow-hidden">
        <CardContent class="p-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm text-slate-500 dark:text-slate-400">{{ card.label }}</div>
              <div class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ card.value }}</div>
              <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ card.description }}</div>
            </div>
            <div :class="[card.accent, 'flex h-12 w-12 items-center justify-center rounded-2xl text-white shadow-lg shadow-slate-200/50 dark:shadow-slate-950/40']">
              <component :is="card.icon" class="h-5 w-5" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <Card>
        <CardHeader>
          <CardTitle>Operations Snapshot</CardTitle>
          <CardDescription>The admin view for the teams and records that usually need attention first.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="py-16 text-center text-sm text-slate-500 dark:text-slate-400">
            Loading admin metrics...
          </div>
          <div v-else class="space-y-4">
            <div
              v-for="item in priorityItems"
              :key="item.title"
              class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/30"
            >
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.title }}</div>
                  <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ item.description }}</div>
                </div>
                <div :class="[item.tone, 'text-2xl font-bold']">{{ item.value }}</div>
              </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                  <Bell class="h-4 w-4 text-violet-500" />
                  Notifications
                </div>
                <div class="mt-3 space-y-3">
                  <div
                    v-for="notice in notifications.slice(0, 3)"
                    :key="notice.id"
                    class="rounded-xl bg-slate-50 px-3 py-3 text-sm dark:bg-slate-900/60"
                  >
                    <div class="font-medium text-slate-900 dark:text-white">{{ notice.title || notice.message }}</div>
                    <div class="mt-1 text-slate-500 dark:text-slate-400">{{ notice.message }}</div>
                  </div>
                  <div v-if="!notifications.length" class="rounded-xl bg-slate-50 px-3 py-6 text-center text-sm text-slate-500 dark:bg-slate-900/60 dark:text-slate-400">
                    No notifications waiting right now.
                  </div>
                </div>
              </div>

              <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                  <CalendarDays class="h-4 w-4 text-sky-500" />
                  Upcoming events
                </div>
                <div class="mt-3 space-y-3">
                  <div
                    v-for="event in upcomingEvents"
                    :key="event.id"
                    class="rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-900/60"
                  >
                    <div class="font-medium text-slate-900 dark:text-white">{{ event.title || event.name || 'Untitled event' }}</div>
                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                      {{ formatLongDate(event.event_date) }} at {{ formatTime(event.start_time) }}
                    </div>
                    <div v-if="event.location" class="mt-1 text-xs uppercase tracking-wide text-slate-400 dark:text-slate-500">
                      {{ event.location }}
                    </div>
                  </div>
                  <div v-if="!upcomingEvents.length" class="rounded-xl bg-slate-50 px-3 py-6 text-center text-sm text-slate-500 dark:bg-slate-900/60 dark:text-slate-400">
                    No upcoming events scheduled yet.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card class="overflow-hidden">
        <CardHeader>
          <CardTitle>Events Calendar</CardTitle>
          <CardDescription>{{ formatMonthLabel(today) }} with highlighted event days and a quick schedule list.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <div class="grid grid-cols-7 gap-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500">
            <div v-for="day in dayHeaders" :key="day">{{ day }}</div>
          </div>

          <div class="grid grid-cols-7 gap-2">
            <div
              v-for="cell in calendarDays"
              :key="cell.key"
              :class="[
                'min-h-[74px] rounded-2xl border p-2 transition-colors',
                cell.dayNumber
                  ? 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900'
                  : 'border-transparent bg-slate-50/70 dark:bg-slate-950/20',
                cell.isToday ? 'ring-2 ring-blue-500/70' : '',
              ]"
            >
              <template v-if="cell.dayNumber">
                <div class="flex items-start justify-between gap-2">
                  <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ cell.dayNumber }}</span>
                  <span
                    v-if="cell.eventCount"
                    class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold text-white"
                  >
                    {{ cell.eventCount }}
                  </span>
                </div>
                <div v-if="cell.eventCount" class="mt-4 h-1.5 rounded-full bg-blue-100 dark:bg-blue-900/50">
                  <div
                    class="h-1.5 rounded-full bg-blue-600"
                    :style="{ width: `${Math.min(100, 28 * cell.eventCount)}%` }"
                  />
                </div>
              </template>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-950/30">
            <div class="mb-3 text-sm font-semibold text-slate-900 dark:text-white">This month's schedule</div>
            <div class="space-y-3">
              <div
                v-for="event in upcomingEvents"
                :key="`schedule-${event.id}`"
                class="flex items-start gap-3 rounded-xl bg-white px-3 py-3 shadow-sm dark:bg-slate-900"
              >
                <div class="flex w-14 shrink-0 flex-col items-center rounded-xl bg-blue-50 px-2 py-2 text-center dark:bg-blue-950/40">
                  <div class="text-[11px] font-semibold uppercase tracking-wide text-blue-500">
                    {{ normalizeDate(event.event_date) ? formatWeekday(normalizeDate(event.event_date)!) : 'TBD' }}
                  </div>
                  <div class="text-lg font-bold text-blue-700 dark:text-blue-300">
                    {{ normalizeDate(event.event_date)?.getDate() ?? '--' }}
                  </div>
                </div>
                <div class="min-w-0">
                  <div class="font-medium text-slate-900 dark:text-white">{{ event.title || event.name || 'Untitled event' }}</div>
                  <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ formatLongDate(event.event_date) }} at {{ formatTime(event.start_time) }}
                  </div>
                  <div v-if="event.description" class="mt-1 line-clamp-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ event.description }}
                  </div>
                </div>
              </div>
              <div v-if="!upcomingEvents.length" class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                Add company events and they will appear here.
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
