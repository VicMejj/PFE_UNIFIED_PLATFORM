<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  Bell,
  CalendarRange,
  Clock3,
  FileSignature,
  ShieldCheck,
  Users,
  Wallet,
  ArrowRight,
} from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import SmartCalendar from '@/components/common/SmartCalendar.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'

const router = useRouter()

const dashboardHome = ref<any>(null)
const employees = ref<any[]>([])
const leaves = ref<any[]>([])
const paySlips = ref<any[]>([])
const contracts = ref<any[]>([])
const notifications = ref<any[]>([])
const attendanceRecords = ref<any[]>([])
const attendanceStats = ref<any | null>(null)
const isLoading = ref(true)

const dashboardStats = computed(() => ({
  total_employees: employees.value.length,
  active_employees: employees.value.filter((item) => item.is_active).length,
  on_leave_employees: leaves.value.filter(l => String(l.status).toLowerCase() === 'approved').length,
  new_employees_this_month: 0,
}))

const pendingLeavesCount = computed(() =>
  leaves.value.filter((item) => String(item.status ?? '').toLowerCase() === 'pending').length
)

const pendingPaySlipsCount = computed(() =>
  paySlips.value.filter((item) => String(item.status ?? '').toLowerCase() !== 'sent').length
)

const summaryCards = computed(() => [
  {
    label: 'Employees',
    value: dashboardStats.value.total_employees,
    description: `${dashboardStats.value.active_employees} active members.`,
    icon: Users,
    accent: 'bg-blue-500',
    path: '/rh/employees'
  },
  {
    label: 'Leave Queue',
    value: pendingLeavesCount.value,
    description: 'Awaiting admin review.',
    icon: CalendarRange,
    accent: 'bg-amber-500',
    path: '/rh/leaves'
  },
  {
    label: 'Payroll Actions',
    value: pendingPaySlipsCount.value,
    description: 'Pending generation/sent.',
    icon: Wallet,
    accent: 'bg-emerald-500',
    path: '/rh/payroll'
  },
  {
    label: 'Contracts',
    value: contracts.value.length,
    description: 'Awaiting oversight.',
    icon: FileSignature,
    accent: 'bg-indigo-500',
    path: '/rh/contracts'
  },
])

const priorityItems = computed(() => [
  {
    title: 'Pending Approvals',
    value: pendingLeavesCount.value,
    tone: pendingLeavesCount.value > 0 ? 'text-amber-500' : 'text-emerald-500',
    description: 'Leave requests needing immediate attention.',
  },
  {
    title: 'Active Alerts',
    value: notifications.value.length,
    tone: 'text-indigo-500',
    description: 'System signals requiring oversight.',
  },
])

const recentAttendance = computed(() => attendanceRecords.value.slice(0, 5))

onMounted(async () => {
  isLoading.value = true
  try {
    const today = new Date().toISOString().slice(0, 10)
    const [homeData, employeeData, leaveData, paySlipData, contractData, notificationData, attendanceData, attendanceStatsData] = await Promise.all([
      platformApi.getHomeDashboard(),
      platformApi.getEmployees(),
      platformApi.getLeaves(),
      platformApi.getPaySlips(),
      platformApi.getContracts(),
      platformApi.getNotifications(),
      platformApi.getAttendanceRecords({ date_from: today, date_to: today }),
      platformApi.getAttendanceStatistics({ date_from: today, date_to: today }),
    ])
    dashboardHome.value = homeData
    employees.value = unwrapItems(employeeData)
    leaves.value = unwrapItems(leaveData)
    paySlips.value = unwrapItems(paySlipData)
    contracts.value = unwrapItems(contractData)
    notifications.value = Array.isArray(notificationData) ? notificationData : []
    attendanceRecords.value = unwrapItems(attendanceData)
    attendanceStats.value = attendanceStatsData
  } catch (error) {
    console.error('Incomplete admin dashboard sync', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-8 animate-in">
    <!-- Premium Header -->
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between px-1">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50/50 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-blue-700 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-400">
          <ShieldCheck class="h-3 w-3" />
          Administrative Command
        </div>
        <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">Elite Dashboard</h1>
        <p class="mt-2 text-slate-500 dark:text-slate-400 font-medium">
          Unified operational intelligence across people, payroll, and upcoming events.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <Button variant="outline" @click="router.push('/settings')">
          System Settings
        </Button>
        <Button class="bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-600/20" @click="router.push('/rh/employees')">
          Manage Talent <ArrowRight class="ml-2 h-4 w-4" />
        </Button>
      </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
      <Card 
        v-for="card in summaryCards" 
        :key="card.label" 
        class="glass-card card-hover premium-shadow cursor-pointer"
        @click="router.push(card.path)"
      >
        <CardContent class="p-6">
          <div class="flex items-start justify-between">
            <div>
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ card.label }}</div>
              <div class="mt-3 text-3xl font-black text-slate-900 dark:text-white">{{ card.value }}</div>
              <div class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">{{ card.description }}</div>
            </div>
            <div :class="[card.accent, 'flex h-12 w-12 items-center justify-center rounded-2xl text-white shadow-xl shadow-opacity-20']">
              <component :is="card.icon" class="h-5 w-5" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Main Content Layout -->
    <div class="grid gap-8 xl:grid-cols-[1fr_380px]">
      <div class="space-y-8">
        <!-- Unified Calendar Section -->
        <SmartCalendar class="glass-card premium-shadow rounded-3xl" />

        <Card class="glass-card premium-shadow">
          <CardHeader class="flex flex-row items-center justify-between space-y-0">
            <div>
              <CardTitle class="text-lg">Attendance Overview</CardTitle>
              <CardDescription>Live check-ins for today</CardDescription>
            </div>
            <Clock3 class="h-4 w-4 text-sky-500" />
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-3 md:grid-cols-4">
              <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
                <div class="text-xs uppercase tracking-wide text-slate-400">Present</div>
                <div class="mt-2 text-2xl font-black">{{ attendanceStats?.present_today ?? 0 }}</div>
              </div>
              <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
                <div class="text-xs uppercase tracking-wide text-slate-400">Late</div>
                <div class="mt-2 text-2xl font-black">{{ attendanceStats?.late_today ?? 0 }}</div>
              </div>
              <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
                <div class="text-xs uppercase tracking-wide text-slate-400">Absent</div>
                <div class="mt-2 text-2xl font-black">{{ attendanceStats?.absent_today ?? 0 }}</div>
              </div>
              <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/50">
                <div class="text-xs uppercase tracking-wide text-slate-400">Leave</div>
                <div class="mt-2 text-2xl font-black">{{ attendanceStats?.on_leave_today ?? 0 }}</div>
              </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
              <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-900/60">
                  <tr>
                    <th class="px-4 py-3">Employee</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Check-in</th>
                    <th class="px-4 py-3">Check-out</th>
                    <th class="px-4 py-3">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                  <tr v-for="record in recentAttendance" :key="record.id">
                    <td class="px-4 py-3 font-medium">{{ record.employee?.name || record.employee?.full_name || `Employee #${record.employee_id}` }}</td>
                    <td class="px-4 py-3">{{ record.timesheet_date || record.date || 'N/A' }}</td>
                    <td class="px-4 py-3">{{ record.check_in || '—' }}</td>
                    <td class="px-4 py-3">{{ record.check_out || '—' }}</td>
                    <td class="px-4 py-3">
                      <Badge variant="secondary">{{ record.status }}</Badge>
                    </td>
                  </tr>
                  <tr v-if="!recentAttendance.length">
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No attendance records for today.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-8">
        <!-- Ops Overview Card -->
        <Card class="glass-card border-none premium-shadow bg-blue-600/5 dark:bg-blue-500/5">
          <CardHeader class="pb-2">
            <CardTitle class="text-lg">Ops Overview</CardTitle>
            <CardDescription>Live attention signals</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-if="isLoading" class="py-12 flex justify-center">
              <div class="h-6 w-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>
            <div v-else v-for="item in priorityItems" :key="item.title" class="group rounded-2xl border border-blue-100 bg-white/60 p-4 dark:border-blue-900/20 dark:bg-slate-900/40 transition-all hover:bg-white dark:hover:bg-slate-900">
              <div class="flex justify-between items-center mb-1">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wide group-hover:text-blue-500 transition-colors">{{ item.title }}</span>
                <span :class="[item.tone, 'text-2xl font-black']">{{ item.value }}</span>
              </div>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ item.description }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Notifications Card -->
        <Card class="glass-card premium-shadow">
          <CardHeader class="flex flex-row items-center justify-between space-y-0">
             <div>
               <CardTitle class="text-lg">Inbox</CardTitle>
               <CardDescription>Latest system activities</CardDescription>
             </div>
             <Bell class="h-4 w-4 text-violet-500" />
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div v-for="notice in notifications.slice(0, 4)" :key="notice.id" class="relative pl-4 before:absolute before:left-0 before:top-1.5 before:bottom-1.5 before:w-1 before:rounded-full before:bg-blue-500">
                <div class="text-xs font-bold text-slate-900 dark:text-white line-clamp-1">{{ notice.title || 'System Update' }}</div>
                <div class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 line-clamp-2">{{ notice.message }}</div>
              </div>
              <div v-if="!notifications.length" class="py-20 text-center">
                <Badge variant="secondary" class="font-bold opacity-40">No pending signals</Badge>
              </div>
            </div>
            <div v-if="notifications.length" class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800">
              <Button variant="ghost" class="w-full text-xs font-bold text-blue-500 hover:text-blue-600" @click="router.push('/rh/notifications')">
                View All Signals
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>

<style scoped>
.glass-card {
  @apply bg-white/70 dark:bg-slate-950/60 backdrop-blur-xl border border-white/40 dark:border-slate-800/50;
}
</style>
