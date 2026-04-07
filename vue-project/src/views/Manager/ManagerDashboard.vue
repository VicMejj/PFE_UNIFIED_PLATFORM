<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { CalendarClock, FileCheck, TrendingUp, Users } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'

const employees = ref<any[]>([])
const leaves = ref<any[]>([])
const events = ref<any[]>([])
const isLoading = ref(true)

const summaryCards = computed(() => [
  {
    label: 'Employees visible',
    value: employees.value.length,
    detail: 'Directory records available to managers.',
    icon: Users,
    color: 'bg-sky-500',
  },
  {
    label: 'Pending approvals',
    value: leaves.value.filter((item) => String(item.status).toLowerCase() === 'pending').length,
    detail: 'Requests still waiting for action.',
    icon: FileCheck,
    color: 'bg-amber-500',
  },
  {
    label: 'Upcoming events',
    value: events.value.length,
    detail: 'Company events currently scheduled.',
    icon: CalendarClock,
    color: 'bg-violet-500',
  },
  {
    label: 'Approved leaves',
    value: leaves.value.filter((item) => String(item.status).toLowerCase().includes('approved')).length,
    detail: 'Requests already moved through review.',
    icon: TrendingUp,
    color: 'bg-emerald-500',
  },
])

const recentRequests = computed(() =>
  leaves.value
    .slice(0, 5)
    .map((item) => ({
      id: item.id,
      employee: item.employee?.name || item.employee?.full_name || `Employee #${item.employee_id}`,
      reason: item.reason || 'No reason provided',
      status: item.status || 'pending',
    }))
)

const upcomingEvents = computed(() => events.value.slice(0, 4))

onMounted(async () => {
  isLoading.value = true
  try {
    const [employeeData, leaveData, eventData] = await Promise.all([
      platformApi.getEmployees(),
      platformApi.getLeaves(),
      platformApi.getEvents(),
    ])

    employees.value = unwrapItems(employeeData)
    leaves.value = unwrapItems(leaveData)
    events.value = unwrapItems(eventData)
  } catch (error) {
    console.error('Unable to load manager dashboard data', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Manager Dashboard</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Review approvals, monitor upcoming events, and keep daily team operations moving.
        </p>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card v-for="card in summaryCards" :key="card.label" class="border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-800">
        <CardContent class="p-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm text-slate-500 dark:text-slate-400">{{ card.label }}</div>
              <div class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                {{ isLoading ? '...' : card.value }}
              </div>
              <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ card.detail }}</div>
            </div>
            <div :class="[card.color, 'flex h-12 w-12 items-center justify-center rounded-2xl text-white']">
              <component :is="card.icon" class="h-5 w-5" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
      <Card class="col-span-4 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-800">
        <CardHeader>
          <CardTitle>Request Pulse</CardTitle>
          <CardDescription>Recent leave requests now come from the live leave endpoint instead of hardcoded examples.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="flex h-[300px] items-center justify-center text-sm text-gray-500">
            Loading manager activity...
          </div>
          <div v-else class="space-y-4">
            <div
              v-for="request in recentRequests"
              :key="request.id"
              class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700"
            >
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ request.employee }}</p>
                  <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ request.reason }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium capitalize text-slate-600 dark:bg-slate-700 dark:text-slate-200">
                  {{ String(request.status).replace(/_/g, ' ') }}
                </span>
              </div>
            </div>
            <div v-if="!recentRequests.length" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
              No leave requests are available right now.
            </div>
          </div>
        </CardContent>
      </Card>
      <Card class="col-span-3 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-800">
        <CardHeader>
          <CardTitle>Upcoming Events</CardTitle>
          <CardDescription>Events pulled from the shared communication calendar.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            Loading events...
          </div>
          <div v-else class="space-y-4">
            <div v-for="event in upcomingEvents" :key="event.id" class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
              <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ event.title || event.name || 'Untitled event' }}</p>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ event.event_date || 'Date not set' }}</p>
            </div>
            <div v-if="!upcomingEvents.length" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
              No upcoming events are scheduled.
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
