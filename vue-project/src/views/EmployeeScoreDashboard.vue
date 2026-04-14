<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  Users, 
  TrendingUp, 
  Award, 
  AlertTriangle, 
  ChevronRight, 
  RefreshCw, 
  Calendar, 
  ShieldAlert,
  BrainCircuit,
  PieChart
} from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import { platformApi } from '@/api/laravel/platform'
import { laravelApi } from '@/api/http'

const summary = ref<any>({})
const employeesWithScores = ref<any[]>([])
const atRiskEmployees = ref<any[]>([])
const isLoading = ref(true)

const scoreTiers = computed(() => [
  { name: 'Excellent', count: summary.value.excellent_count || 0, color: 'text-emerald-500', bg: 'bg-emerald-500', percentage: ((summary.value.excellent_count || 0) / (summary.value.total_employees || 1)) * 100 },
  { name: 'Good', count: summary.value.good_count || 0, color: 'text-blue-500', bg: 'bg-blue-500', percentage: ((summary.value.good_count || 0) / (summary.value.total_employees || 1)) * 100 },
  { name: 'Medium', count: summary.value.medium_count || 0, color: 'text-amber-500', bg: 'bg-amber-500', percentage: ((summary.value.medium_count || 0) / (summary.value.total_employees || 1)) * 100 },
  { name: 'Risk', count: summary.value.risk_count || 0, color: 'text-rose-500', bg: 'bg-rose-500', percentage: ((summary.value.risk_count || 0) / (summary.value.total_employees || 1)) * 100 },
])

const loadDashboardData = async () => {
  isLoading.value = true
  try {
    const dashboardData = await platformApi.getDashboardScores()
    summary.value = (dashboardData as any)?.summary || dashboardData || {}
    atRiskEmployees.value = (dashboardData as any)?.at_risk_employees || []
    
    const scoresResponse = await laravelApi.get('/employees/scores?per_page=false')
    const scoresData = scoresResponse.data?.data || scoresResponse.data || []
    
    employeesWithScores.value = scoresData.slice(0, 10).map((s: any) => ({
      ...s.employee,
      id: s.employee?.id,
      name: s.employee?.name,
      department: s.employee?.department,
      score: {
        overall_score: s.overall_score,
        attendance_score: s.attendance_score,
        discipline_score: s.discipline_score,
        performance_score: s.performance_score,
        score_tier: s.score_tier
      }
    }))
  } catch {
    summary.value = {}
    atRiskEmployees.value = []
    employeesWithScores.value = []
  } finally {
    isLoading.value = false
  }
}

const getScoreColor = (score: number) => {
  if (score >= 85) return 'text-emerald-500'
  if (score >= 70) return 'text-blue-500'
  if (score >= 50) return 'text-amber-500'
  return 'text-rose-500'
}

const getProgressColor = (score: number) => {
  if (score >= 85) return 'bg-emerald-500'
  if (score >= 70) return 'bg-blue-500'
  if (score >= 50) return 'bg-amber-500'
  return 'bg-rose-500'
}

onMounted(loadDashboardData)
</script>

<template>
  <div class="space-y-8 pb-12">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div>
        <div class="flex items-center gap-2 text-indigo-600 font-semibold mb-1">
          <BrainCircuit class="h-5 w-5" />
          <span class="text-sm tracking-wider uppercase">AI Personnel Analytics</span>
        </div>
        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">Employee Performance Scores</h1>
        <p class="text-slate-500 dark:text-slate-400 mt-2 max-w-2xl">
          Real-time holistic evaluation of workforce health based on attendance, activity, leaves, and workplace incidents.
        </p>
      </div>
      <Button variant="outline" class="group border-slate-200 dark:border-slate-800" @click="loadDashboardData">
        <RefreshCw class="mr-2 h-4 w-4 transition-transform group-hover:rotate-180" /> Sync Scores
      </Button>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <Card class="relative overflow-hidden border-none bg-indigo-600 text-white shadow-xl shadow-indigo-200 dark:shadow-none">
        <CardContent class="pt-6">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-indigo-100 text-sm font-medium">Total Employees</p>
              <h3 class="text-4xl font-bold mt-1">{{ summary.total_employees || '0' }}</h3>
            </div>
            <div class="rounded-xl bg-white/20 p-3">
              <Users class="h-6 w-6" />
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm text-indigo-100">
            <TrendingUp class="h-4 w-4 mr-1" />
            <span>Active Workforce</span>
          </div>
        </CardContent>
      </Card>

      <Card class="border-none bg-white dark:bg-slate-900 shadow-sm">
        <CardContent class="pt-6 text-center">
          <div class="inline-flex rounded-full bg-blue-50 dark:bg-blue-500/10 p-3 mb-4">
            <PieChart class="h-6 w-6 text-blue-600" />
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-sm">Average Score</p>
          <h3 class="text-3xl font-bold text-slate-900 dark:text-white mt-1">{{ summary.average_score || '0' }}%</h3>
        </CardContent>
      </Card>

      <Card class="border-none bg-white dark:bg-slate-900 shadow-sm">
        <CardContent class="pt-6 text-center">
          <div class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-500/10 p-3 mb-4">
            <Award class="h-6 w-6 text-emerald-600" />
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-sm">Excellent</p>
          <h3 class="text-3xl font-bold text-emerald-600 mt-1">{{ summary.excellent_count || '0' }}</h3>
        </CardContent>
      </Card>

      <Card class="border-none bg-white dark:bg-slate-900 shadow-sm">
        <CardContent class="pt-6 text-center">
          <div class="inline-flex rounded-full bg-rose-50 dark:bg-rose-500/10 p-3 mb-4">
            <ShieldAlert class="h-6 w-6 text-rose-600" />
          </div>
          <p class="text-slate-500 dark:text-slate-400 text-sm">At High Risk</p>
          <h3 class="text-3xl font-bold text-rose-600 mt-1">{{ summary.risk_count || '0' }}</h3>
        </CardContent>
      </Card>
    </div>

    <!-- Detailed Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Tier Distribution -->
      <Card class="lg:col-span-1 shadow-sm border-slate-100 dark:border-slate-800">
        <CardHeader>
          <CardTitle class="text-lg">Score Distribution</CardTitle>
        </CardHeader>
        <CardContent class="space-y-6">
          <div v-for="tier in scoreTiers" :key="tier.name" class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span class="font-semibold text-slate-700 dark:text-slate-300">{{ tier.name }}</span>
              <span :class="tier.color">{{ tier.count }}</span>
            </div>
            <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
              <div :class="`h-full ${tier.bg} transition-all duration-500`" :style="`width: ${tier.percentage}%`" />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Risk Alert List -->
      <Card class="lg:col-span-2 shadow-sm border-slate-100 dark:border-slate-800">
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle class="text-lg flex items-center gap-2">
            <AlertTriangle class="h-5 w-5 text-rose-500" />
            Immediate Attention Required
          </CardTitle>
          <Badge variant="destructive">{{ atRiskEmployees.length }} Critical</Badge>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div v-for="emp in atRiskEmployees" :key="emp.id" class="flex items-center justify-between p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
              <div class="flex items-center gap-4">
                <div class="h-12 w-12 rounded-2xl bg-rose-100 dark:bg-rose-500/10 flex items-center justify-center text-rose-600 font-bold text-lg">
                  {{ emp.name.charAt(0) }}
                </div>
                <div>
                  <h4 class="font-bold text-slate-900 dark:text-white">{{ emp.name }}</h4>
                  <div class="flex items-center gap-2 text-sm text-slate-500 mt-1">
                    <Calendar class="h-3 w-3" />
                    <span>Frequent Absences / Disciplinary History</span>
                  </div>
                </div>
              </div>
              <div class="text-right">
                <span class="text-2xl font-black text-rose-600">{{ emp.score?.overall_score }}%</span>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mt-1">Impact Level</p>
              </div>
            </div>
            <div v-if="!atRiskEmployees.length" class="text-center py-8 text-slate-400">
              No active risks detected in the current evaluation cycle.
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Leaderboard / Detailed List -->
    <Card class="shadow-sm border-slate-100 dark:border-slate-800">
      <CardHeader>
        <CardTitle>Top Talent Performance</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-800">
          <table class="w-full text-left">
            <thead>
              <tr class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                <th class="px-6 py-4">Employee</th>
                <th class="px-6 py-4">Status & Factors</th>
                <th class="px-6 py-4 text-center">Score</th>
                <th class="px-6 py-4">Rating</th>
                <th class="px-6 py-4"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
              <tr v-for="emp in employeesWithScores" :key="emp.id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                <td class="px-6 py-4">
                  <div class="font-bold text-slate-900 dark:text-white">{{ emp.name }}</div>
                  <div class="text-xs text-slate-400 mt-1">{{ emp.department?.name }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex gap-1">
                    <Badge variant="outline" class="text-[10px]" :class="emp.score?.attendance_score < 70 ? 'text-rose-500 border-rose-200' : 'text-slate-500'">Attendance: {{ Math.round(emp.score?.attendance_score || 0) }}%</Badge>
                    <Badge variant="outline" class="text-[10px]" :class="emp.score?.discipline_score < 70 ? 'text-rose-500 border-rose-200' : 'text-slate-500'">Discipline: {{ Math.round(emp.score?.discipline_score || 0) }}%</Badge>
                  </div>
                </td>
                <td class="px-6 py-4 text-center">
                  <span :class="`text-xl font-bold ${getScoreColor(emp.score?.overall_score)}`">{{ emp.score?.overall_score }}%</span>
                </td>
                <td class="px-6 py-4">
                  <div class="h-1.5 w-24 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                    <div :class="`h-full ${getProgressColor(emp.score?.overall_score)} animate-pulse-slow`" :style="`width: ${emp.score?.overall_score}%`" />
                  </div>
                </td>
                <td class="px-6 py-4 text-right">
                  <button class="p-2 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <ChevronRight class="h-4 w-4 text-slate-400" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  </div>
</template>


<style scoped>
.employee-score-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}
</style>