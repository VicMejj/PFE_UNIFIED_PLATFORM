<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Brain, Gift, Plus, Sparkles, Activity, CheckCircle, Clock, AlertCircle, Zap } from 'lucide-vue-next'
import Dialog from '@/components/ui/Dialog.vue'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'

const auth = useAuthStore()
const notifications = useNotificationsStore()
const router = useRouter()
const items = ref<any[]>([])
const isCreating = ref(false)
const feedback = ref('')
const errorMsg = ref('')
const isLoading = ref(true)
const searchQuery = ref('')
const assignedBenefits = ref<any[]>([])
const employees = ref<any[]>([])
const recommendations = ref<any[]>([])
const myScore = ref<any>(null)
const recLoading = ref(false)
const showRecModal = ref(false)
const selectedEmployeeId = ref<string>('')
const claimingBenefitId = ref<number | null>(null)
const allMyAllowances = ref<any[]>([])

const form = reactive({
  name: '',
  description: '',
  is_active: true
})

const columns = [
  { key: 'name', label: 'Benefit Name' },
  { key: 'description', label: 'Description' },
  { key: 'status', label: 'Status' }
]

const userRoles = computed(() =>
  [auth.user?.role, ...(auth.user?.allRoles ?? [])]
    .filter(Boolean)
    .map((role) => String(role).toLowerCase())
)

const canManageBenefits = computed(() =>
  userRoles.value.some((role) => ['admin', 'rh_manager', 'rh', 'hr', 'manager'].includes(role))
)
const isEmployeeView = computed(() => auth.user?.role === 'employee')

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const source = isEmployeeView.value ? assignedBenefits.value : items.value

  if (!query) return source

  return source.filter((item) =>
    [item.name, item.description, item.status]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

const benefitStats = computed(() => [
  {
    label: isEmployeeView.value ? 'Assigned benefits' : 'Benefit catalog',
    value: isEmployeeView.value ? assignedBenefits.value.length : items.value.length,
    description: isEmployeeView.value ? 'Benefits currently assigned to your profile.' : 'Programs currently listed in the platform.',
    icon: Gift,
    color: 'bg-emerald-500'
  },
  {
    label: isEmployeeView.value ? 'Active assignments' : 'Active benefits',
    value: (isEmployeeView.value ? assignedBenefits.value : items.value).filter((item) => item.status === 'Active').length,
    description: isEmployeeView.value ? 'Benefits that are currently active for you.' : 'Visible and ready for employee use.',
    icon: Sparkles,
    color: 'bg-sky-500'
  }
])

async function loadBenefits() {
  isLoading.value = true
  errorMsg.value = ''

  try {
    if (isEmployeeView.value) {
      const [assignedData, scoreData, allowancesData] = await Promise.all([
        platformApi.getMyAllowances(),
        platformApi.getMyScore(),
        platformApi.getMyAllowances()
      ])
      
      const assignedItems = unwrapItems<any>(assignedData)
      myScore.value = (scoreData as any).data || scoreData
      allMyAllowances.value = Array.isArray(allowancesData) ? allowancesData : (allowancesData as any)?.data || []

      assignedBenefits.value = assignedItems.map((item: any) => ({
        ...item,
        name: item.allowance_option?.name || item.allowanceOption?.name || item.name || 'Benefit',
        description: item.allowance_option?.description || item.allowanceOption?.description || 'Assigned benefit',
        displayStatus: item.claimed ? 'Claimed' : item.status === 'active' ? 'Ready to Claim' : item.status === 'pending' ? 'Pending Approval' : 'Inactive'
      }))
      
      const benefitData = await platformApi.getAllowanceOptions()
      items.value = unwrapItems<any>(benefitData).map((item) => ({
        ...item,
        description: item.description || 'No description provided',
        status: item.is_active ? 'Active' : 'Inactive'
      }))
    } else {
      const benefitData = await platformApi.getAllowanceOptions()
      items.value = unwrapItems<any>(benefitData).map((item) => ({
        ...item,
        description: item.description || 'No description provided',
        status: item.is_active ? 'Active' : 'Inactive'
      }))
      assignedBenefits.value = []
    }
  } catch (error) {
    console.error('Unable to load benefits', error)
    errorMsg.value = 'Unable to load the benefits data. Please check your connection or permissions.'
  } finally {
    isLoading.value = false
  }
}

async function claimBenefit(benefitId: number) {
  claimingBenefitId.value = benefitId
  try {
    await platformApi.claimAllowance(benefitId)
    feedback.value = 'Benefit claimed successfully!'
    errorMsg.value = ''
    await loadBenefits()
    notifications.fetchNotifications()
  } catch (err: any) {
    errorMsg.value = err.response?.data?.message || 'Failed to claim benefit. Please try again.'
    console.error('Claim failed', err)
  } finally {
    claimingBenefitId.value = null
  }
}

function getClaimableBenefit(allowanceOptionId: number) {
  return allMyAllowances.value.find(a => 
    Number(a.allowance_option_id || a.allowanceOption?.id) === Number(allowanceOptionId) && 
    a.status === 'active' && 
    !a.claimed
  )
}

function getBenefitClaimStatus(benefit: any) {
  if (benefit.claimed) {
    return { label: 'Claimed', variant: 'info' as const, icon: CheckCircle }
  }
  if (benefit.status === 'active') {
    return { label: 'Ready to Claim', variant: 'success' as const, icon: Zap }
  }
  if (benefit.status === 'pending') {
    return { label: 'Pending', variant: 'warning' as const, icon: Clock }
  }
  return { label: 'Inactive', variant: 'secondary' as const, icon: AlertCircle }
}

async function requestBenefit(benefit: any) {
  errorMsg.value = ''
  feedback.value = ''
  
  try {
    await platformApi.submitBenefitRequest({
      allowance_option_id: benefit.id,
      reason: `Requested via Benefit Catalog`,
      requested_amount: 0 // Will be determined by HR
    })
    feedback.value = `Request for ${benefit.name} submitted successfully.`
    // Refresh or redirect
    router.push('/social/claims')
  } catch (error) {
    errorMsg.value = 'Failed to submit benefit request.'
  }
}

async function createBenefit() {
  errorMsg.value = ''
  feedback.value = ''

  try {
    await platformApi.createAllowanceOption({
      name: form.name,
      description: form.description,
      is_active: form.is_active
    })
    feedback.value = 'Benefit created successfully.'
    isCreating.value = false
    form.name = ''
    form.description = ''
    form.is_active = true
    await loadBenefits()
  } catch (error) {
    console.error('Unable to create benefit', error)
    errorMsg.value = 'Unable to save the benefit right now.'
  }
}

async function loadEmployees() {
  if (!canManageBenefits.value) return
  try {
    const [empData, scoreData] = await Promise.all([
      platformApi.getEmployees(),
      platformApi.getDashboardScores()
    ])
    
    const allEmployees = unwrapItems<any>(empData)
    const scoreDataAny = scoreData as any
    const allScores = scoreDataAny?.at_risk_employees?.concat(scoreDataAny?.excellent_employees) || []
    
    // Create a map for quick score lookup
    const scoreMap = new Map()
    allScores.forEach((s: any) => scoreMap.set(s.employee_id, s.overall_score))

    employees.value = allEmployees.map(emp => ({
      ...emp,
      full_name: `${emp.first_name} ${emp.last_name}`,
      score: scoreMap.get(emp.id) || null
    }))
  } catch (error) {
    console.error('Failed to load employees', error)
  }
}

async function fetchRecommendations() {
  if (!selectedEmployeeId.value) return

  recLoading.value = true
  recommendations.value = []
  try {
    const data: any = await platformApi.getBenefitRecommendations(Number(selectedEmployeeId.value))
    recommendations.value = data?.recommendations || data || []
  } catch (error) {
    console.error('Failed to fetch recommendations', error)
    errorMsg.value = 'AI could not generate recommendations at this time.'
  } finally {
    recLoading.value = false
  }
}

onMounted(() => {
  loadBenefits()
  if (canManageBenefits.value) {
    loadEmployees()
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <Gift class="w-8 h-8 text-emerald-500" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Social Benefits</h2>
          <p class="text-gray-500 dark:text-gray-400">
            {{ isEmployeeView ? 'Review only the benefits assigned to your profile.' : 'Browse the benefit catalog and keep employee perks organized.' }}
          </p>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <Button v-if="canManageBenefits" class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-100 dark:shadow-none" @click="showRecModal = true">
          <Brain class="w-4 h-4 mr-2" /> AI Recommendations
        </Button>
        <Button variant="outline" class="border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-400" @click="router.push('/assurance/claims')">
          <Activity class="w-4 h-4 mr-2" /> Insurance Claims
        </Button>
        <Button v-if="canManageBenefits" class="bg-emerald-600 hover:bg-emerald-700 text-white" @click="router.push('/social/employee-benefits')">
          <Plus class="w-4 h-4 mr-2" /> Manage Employee Benefits
        </Button>
        <Button v-if="canManageBenefits" class="bg-slate-700 hover:bg-slate-800 text-white" @click="isCreating = !isCreating">
          <Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'Add Benefit' }}
        </Button>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <!-- Employee Holistic Score Header -->
    <div v-if="isEmployeeView && myScore" class="relative overflow-hidden rounded-3xl bg-indigo-600 p-8 text-white shadow-xl shadow-indigo-100 dark:shadow-none">
      <!-- Decorative Elements -->
      <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
      <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-indigo-400/20 blur-3xl"></div>

      <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="flex-1 space-y-4">
          <div class="flex items-center gap-2">
            <Badge variant="outline" class="border-indigo-300 text-indigo-100 font-bold uppercase tracking-widest text-[10px]">Your Growth Status</Badge>
            <span class="text-indigo-200 text-sm font-medium">Evaluation active: {{ new Date().toLocaleDateString() }}</span>
          </div>
          <h2 class="text-3xl font-black tracking-tight">Your Holistic Performance Score</h2>
          <p class="text-indigo-100 text-lg opacity-90 max-w-xl">
            You're currently in the <span class="font-bold underline decoration-emerald-400 decoration-2">{{ myScore.score_tier }} Tier</span>. 
            Maintain a score above 85% to unlock premium tier benefits automatically.
          </p>
          
          <div v-if="myScore.improvement_suggestions?.length" class="mt-6 flex flex-wrap gap-2">
            <div v-for="tip in myScore.improvement_suggestions.slice(0, 2)" :key="tip" class="flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-2 text-sm backdrop-blur-md">
              <Sparkles class="h-4 w-4 text-emerald-300" />
              <span>AI Suggests: {{ tip }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col items-center gap-2">
          <div class="relative flex h-40 w-40 items-center justify-center rounded-full border-8 border-indigo-500/30 bg-indigo-500/20 shadow-inner">
             <div class="text-center">
               <span class="text-5xl font-black">{{ Math.round(myScore.overall_score) }}%</span>
               <div class="text-[10px] font-bold uppercase tracking-widest opacity-60">Global Score</div>
             </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="grid gap-4 lg:grid-cols-2">
      <Card v-for="card in benefitStats" :key="card.label">
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

    <Card v-if="isCreating && canManageBenefits">
      <CardHeader>
        <CardTitle>Create Benefit</CardTitle>
        <CardDescription>The Add Benefit action now creates allowance options in Laravel.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <Label>Benefit Name</Label>
          <Input v-model="form.name" placeholder="Annual wellness stipend" />
        </div>
        <div class="space-y-2">
          <Label>Status</Label>
          <select v-model="form.is_active" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </div>
        <div class="space-y-2 md:col-span-2">
          <Label>Description</Label>
          <Input v-model="form.description" placeholder="Describe the benefit and who it applies to" />
        </div>
        <div class="md:col-span-2 flex justify-end">
          <Button @click="createBenefit">Save Benefit</Button>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="pt-6">
        <div v-if="isEmployeeView && assignedBenefits.length > 0" class="mb-6">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Your Assigned Benefits</h3>
          <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div
              v-for="benefit in assignedBenefits"
              :key="benefit.id"
              class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-900"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                  <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', benefit.displayStatus === 'Ready to Claim' ? 'bg-emerald-100 dark:bg-emerald-500/20' : benefit.displayStatus === 'Claimed' ? 'bg-blue-100 dark:bg-blue-500/20' : 'bg-slate-100 dark:bg-slate-500/20']">
                    <Gift :class="['h-5 w-5', benefit.displayStatus === 'Ready to Claim' ? 'text-emerald-600' : benefit.displayStatus === 'Claimed' ? 'text-blue-600' : 'text-slate-400']" />
                  </div>
                  <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white">{{ benefit.name }}</h4>
                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">${{ Number(benefit.amount || 0).toFixed(2) }}</div>
                  </div>
                </div>
              </div>
              <p class="mt-3 text-sm text-slate-500 dark:text-slate-400 line-clamp-2">{{ benefit.description }}</p>
              <div class="mt-4 flex items-center justify-between">
                <Badge :variant="getBenefitClaimStatus(benefit).variant" class="capitalize">
                  <component :is="getBenefitClaimStatus(benefit).icon" class="mr-1 h-3 w-3" />
                  {{ getBenefitClaimStatus(benefit).label }}
                </Badge>
                <Button
                  v-if="benefit.displayStatus === 'Ready to Claim'"
                  size="sm"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white"
                  :disabled="claimingBenefitId === benefit.id"
                  @click="claimBenefit(benefit.id)"
                >
                  <Zap v-if="claimingBenefitId !== benefit.id" class="mr-1 h-3 w-3" />
                  {{ claimingBenefitId === benefit.id ? 'Claiming...' : 'Claim Now' }}
                </Button>
                <span v-else-if="benefit.claimed_at" class="text-xs text-slate-400">
                  Claimed {{ new Date(benefit.claimed_at).toLocaleDateString() }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div v-if="isEmployeeView && items.length > 0" class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Available Benefits Catalog</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Browse available benefits. Contact HR if you'd like to request access to any benefit.</p>
        </div>
        
        <DataTable 
          :columns="columns" 
          :data="filteredItems"
          :loading="isLoading"
          :searchPlaceholder="isEmployeeView ? 'Search available benefits...' : 'Search benefits by name or description...'"
          :emptyMessage="isEmployeeView ? 'No benefits available in the catalog.' : 'No benefits are available yet.'"
          @search="searchQuery = $event"
        >
          <template #cell(status)="{ value }">
            <Badge :variant="value === 'Active' ? 'success' : 'secondary'">{{ value }}</Badge>
          </template>
          <template v-if="isEmployeeView" #actions="{ item }">
            <div class="flex items-center gap-2">
              <Badge v-if="getClaimableBenefit(item.id)" variant="success" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                Assigned
              </Badge>
              <Button size="sm" variant="outline" @click="requestBenefit(item)">
                Request
              </Button>
            </div>
          </template>
        </DataTable>
      </CardContent>
    </Card>

    <Dialog :open="showRecModal" title="AI Benefit Recommendations" @close="showRecModal = false">
      <div class="space-y-5">
        <div class="rounded-3xl border border-blue-500/10 bg-gradient-to-br from-blue-500/8 via-white to-emerald-500/8 p-4 dark:from-blue-500/10 dark:via-slate-950 dark:to-emerald-500/10">
          <p class="text-sm text-slate-500 dark:text-slate-400">
            Our AI blends role, department, employee history, and previous selections to surface high-confidence benefit matches.
          </p>
        </div>

        <div class="space-y-2">
          <Label>Select Employee</Label>
          <select 
            v-model="selectedEmployeeId" 
            class="flex h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white"
            @change="fetchRecommendations"
          >
            <option value="">Choose an employee...</option>
            <option v-for="emp in employees" :key="emp.id" :value="String(emp.id)">
              {{ emp.full_name }} — {{ emp.score ? Math.round(emp.score) + '%' : 'No Score' }}
            </option>
          </select>
        </div>

        <div v-if="recLoading" class="py-8 text-center">
          <Sparkles class="mx-auto h-8 w-8 animate-pulse text-indigo-400" />
          <p class="mt-2 text-sm text-slate-400">Analyzing suitability...</p>
        </div>

        <div v-else-if="recommendations.length" class="space-y-3 pt-2">
          <div 
            v-for="rec in recommendations" 
            :key="rec.benefit_id"
            class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-950"
          >
            <div class="flex items-start justify-between">
              <div>
                <div class="flex items-center gap-2">
                  <h4 class="font-semibold text-slate-900 dark:text-white">{{ rec.benefit_name || 'Premium Perk' }}</h4>
                  <Badge :variant="rec.status === 'eligible' ? 'success' : 'secondary'">
                    {{ rec.status || 'review' }}
                  </Badge>
                </div>
                <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ rec.reasoning || 'Highly recommended based on current role and performance.' }}</p>
              </div>
              <div class="flex flex-col items-end">
                <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ Math.round((rec.suitability_score || rec.score || 0.85) * 100) }}%</span>
                <span class="text-[10px] uppercase tracking-wider text-slate-500">Match</span>
              </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
              <Badge v-for="tag in (rec.tags || ['Top Choice', 'Retention'])" :key="tag" variant="secondary" class="text-[10px]">
                {{ tag }}
              </Badge>
            </div>
            <div v-if="rec.admin_guidance" class="mt-4 rounded-2xl border border-sky-500/15 bg-sky-50 p-3 text-sm text-sky-900 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-100">
              {{ rec.admin_guidance }}
            </div>
          </div>
        </div>

        <div v-else-if="selectedEmployeeId" class="py-8 text-center text-sm text-slate-500 border border-dashed border-white/10 rounded-xl">
          No specific recommendations found for this profile.
        </div>
      </div>

      <template #footer>
        <Button variant="outline" @click="showRecModal = false">Close</Button>
        <Button :disabled="!selectedEmployeeId || recLoading" class="bg-indigo-600 text-white" @click="fetchRecommendations">
          Refresh AI
        </Button>
      </template>
    </Dialog>
  </div>
</template>
