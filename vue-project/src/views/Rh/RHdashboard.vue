<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Brain, CalendarClock, FileSignature, Users, Wallet } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'
import { djangoAiApi } from '@/api/django/ai'

const employees = ref<any[]>([])
const leaves = ref<any[]>([])
const paySlips = ref<any[]>([])
const aiSuggestions = ref<any>(null)
const isLoading = ref(true)

const summaryCards = computed(() => [
  {
    label: 'Employees',
    value: employees.value.length,
    description: 'People records connected to Laravel employee endpoints.',
    icon: Users,
    color: 'bg-sky-500'
  },
  {
    label: 'Pending Leaves',
    value: leaves.value.filter((item) => item.status === 'pending').length,
    description: 'Requests waiting for manager or HR approval.',
    icon: CalendarClock,
    color: 'bg-amber-500'
  },
  {
    label: 'Contracts',
    value: leaves.value.length ? 'Live' : 'Ready',
    description: 'Contracts page now points to the corrected backend model.',
    icon: FileSignature,
    color: 'bg-violet-500'
  },
  {
    label: 'Payslips',
    value: paySlips.value.length,
    description: 'Payroll page is connected to the Laravel pay-slip endpoints.',
    icon: Wallet,
    color: 'bg-emerald-500'
  }
])

onMounted(async () => {
  isLoading.value = true
  try {
    const [employeeData, leaveData, paySlipData, suggestionData] = await Promise.all([
      platformApi.getEmployees(),
      platformApi.getLeaves(),
      platformApi.getPaySlips(),
      djangoAiApi.getOptimalLeaveDates()
    ])

    employees.value = unwrapItems(employeeData)
    leaves.value = unwrapItems(leaveData)
    paySlips.value = unwrapItems(paySlipData)
    aiSuggestions.value = suggestionData
  } catch (error) {
    console.error('Unable to load RH dashboard data', error)
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">RH Command Center</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          One place to manage employees, leave, payroll, contracts, benefits, and AI recommendations.
        </p>
      </div>
      <Button class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" @click="$router.push('/rh/employees')">
        Open Employee Management
      </Button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Card v-for="card in summaryCards" :key="card.label">
        <CardContent class="p-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm text-slate-500 dark:text-slate-400">{{ card.label }}</div>
              <div class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ card.value }}</div>
              <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ card.description }}</div>
            </div>
            <div :class="[card.color, 'flex h-12 w-12 items-center justify-center rounded-2xl text-white']">
              <component :is="card.icon" class="h-5 w-5" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
      <Card>
        <CardHeader>
          <CardTitle>RH Priorities</CardTitle>
          <CardDescription>Quick glance at the teams and requests that need attention today.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="isLoading" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            Loading RH metrics...
          </div>
          <div v-else class="space-y-4">
            <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
              <div class="text-sm font-semibold text-slate-900 dark:text-white">Employees loaded</div>
              <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ employees.length }} employee records are available for RH actions.
              </div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
              <div class="text-sm font-semibold text-slate-900 dark:text-white">Pending leave queue</div>
              <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ leaves.filter((item) => item.status === 'pending').length }} leave requests are awaiting review.
              </div>
            </div>
            <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
              <div class="text-sm font-semibold text-slate-900 dark:text-white">Payroll processing</div>
              <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ paySlips.filter((item) => item.status !== 'sent').length }} pay slips are ready to generate or send.
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Brain class="h-5 w-5 text-violet-500" />
            AI Leave Guidance
          </CardTitle>
          <CardDescription>Direct result from the Django leave optimizer endpoint.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="aiSuggestions?.recommended_single_days?.length" class="space-y-3">
            <div
              v-for="day in aiSuggestions.recommended_single_days.slice(0, 3)"
              :key="day.date"
              class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800"
            >
              <div class="font-semibold text-slate-900 dark:text-white">{{ day.date }}</div>
              <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ day.weekday }} with suitability score {{ day.suitability_score }}
              </div>
            </div>
          </div>
          <div v-else class="py-10 text-center text-sm text-slate-500 dark:text-slate-400">
            AI suggestions are loading or temporarily unavailable.
          </div>
          <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800">
            <Button class="w-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" @click="$router.push('/notifications')">
              View All Notifications
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
