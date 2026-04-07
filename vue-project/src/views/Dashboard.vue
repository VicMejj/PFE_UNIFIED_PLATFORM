<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Calendar, FileText, TrendingUp, Users, Wallet } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'

const employees = ref<any[]>([])
const leaves = ref<any[]>([])
const paySlips = ref<any[]>([])
const events = ref<any[]>([])
const isLoading = ref(true)

const stats = computed(() => [
  { name: 'Employees', value: employees.value.length, change: 'Live employee records', icon: Users, color: 'bg-blue-500' },
  { name: 'Leave Requests', value: leaves.value.length, change: 'Requests loaded from Laravel', icon: Calendar, color: 'bg-green-500' },
  { name: 'Pending Leaves', value: leaves.value.filter((item) => String(item.status).toLowerCase() === 'pending').length, change: 'Awaiting review', icon: FileText, color: 'bg-yellow-500' },
  { name: 'Payslips', value: paySlips.value.length, change: 'Payroll records synced', icon: Wallet, color: 'bg-violet-500' },
])

const recentActivities = computed(() =>
  leaves.value.slice(0, 4).map((item) => ({
    id: item.id,
    employee: item.employee?.name || item.employee?.full_name || `Employee #${item.employee_id}`,
    action: item.reason || 'submitted a leave request',
    time: item.created_at ? new Date(item.created_at).toLocaleDateString() : 'Recently',
    status: item.status || 'pending',
  }))
)

const upcomingEvents = computed(() => events.value.slice(0, 3))
const getInitials = (name: string) => name.split(' ').map(n => n[0]).join('').toUpperCase()

onMounted(async () => {
  isLoading.value = true
  try {
    const [employeeData, leaveData, paySlipData, eventData] = await Promise.all([
      platformApi.getEmployees(),
      platformApi.getLeaves(),
      platformApi.getPaySlips(),
      platformApi.getEvents(),
    ])

    employees.value = unwrapItems(employeeData)
    leaves.value = unwrapItems(leaveData)
    paySlips.value = unwrapItems(paySlipData)
    events.value = unwrapItems(eventData)
  } catch (error) {
    console.error('Unable to load dashboard data', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <!-- Page Header -->
    <div>
      <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dashboard</h1>
      <p class="text-gray-500 dark:text-gray-400 mt-1">
        Shared operational overview across people, leave, payroll, and upcoming events.
      </p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <Card v-for="stat in stats" :key="stat.name">
        <CardContent class="p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600 dark:text-gray-400">{{ stat.name }}</p>
              <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                {{ stat.value }}
              </p>
              <div class="flex items-center mt-2">
                <TrendingUp class="h-4 w-4 text-green-500 mr-1" />
                <span class="text-sm text-slate-500 dark:text-slate-400">
                  {{ stat.change }}
                </span>
              </div>
            </div>
            <div :class="[stat.color, 'p-3 rounded-lg flex items-center justify-center']">
              <component :is="stat.icon" class="h-6 w-6 text-white" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Recent Activities -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Recent Activities</CardTitle>
          <CardDescription>Latest updates from your team</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-if="isLoading" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
              Loading recent activity...
            </div>
            <div
              v-for="activity in recentActivities"
              :key="activity.id"
              class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg"
            >
              <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                  <span class="text-blue-600 dark:text-blue-400 font-semibold">
                    {{ getInitials(activity.employee) }}
                  </span>
                </div>
                <div>
                  <p class="text-sm text-gray-900 dark:text-white">
                    <span class="font-medium">{{ activity.employee }}</span>
                    {{ activity.action }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ activity.time }}
                  </p>
                </div>
              </div>
              <span
                :class="[
                  'px-3 py-1 rounded-full text-xs capitalize',
                  String(activity.status).toLowerCase() === 'pending'
                    ? 'bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300'
                    : 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300'
                ]"
              >
                {{ String(activity.status).replace(/_/g, ' ') }}
              </span>
            </div>
            <div v-if="!isLoading && !recentActivities.length" class="rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
              No recent activity is available.
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Upcoming Events -->
      <Card>
        <CardHeader>
          <CardTitle>Upcoming Events</CardTitle>
          <CardDescription>What's on your calendar</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-if="isLoading" class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
              Loading events...
            </div>
            <div
              v-for="event in upcomingEvents"
              :key="event.id"
              class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <h4 class="font-medium text-gray-900 dark:text-white">
                {{ event.title || event.name || 'Untitled event' }}
              </h4>
              <div class="flex items-center justify-between mt-2">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ event.event_date || 'Date not set' }}
                </p>
              </div>
            </div>
            <div v-if="!isLoading && !upcomingEvents.length" class="rounded-lg border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
              No events are scheduled.
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
