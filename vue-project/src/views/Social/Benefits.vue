<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Brain, Gift, Plus, Sparkles, Activity, CheckCircle, Clock, AlertCircle, Zap, TrendingUp, Target, Award } from 'lucide-vue-next'
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
const selectedEmployeeScore = ref<any>(null)
const claimingBenefitId = ref<number | null>(null)
const allMyAllowances = ref<any[]>([])
const employeeRecs = ref<any[]>([])
const employeeRecsLoading = ref(false)
const showRequestModal = ref(false)
const pendingRequest = ref<any>(null)
const requestAmount = ref<number>(0)

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
  const source = items.value
  if (!query) return source
  return source.filter((item) =>
    [item.name, item.description, item.status]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

const sortedRecommendations = computed(() => {
  const recs = [...recommendations.value]
  const priorityOrder: Record<string, number> = { eligible: 0, nearly_eligible: 1, not_eligible: 2 }
  return recs.sort((a, b) => {
    const priorityDiff = (priorityOrder[a.status ?? ''] ?? 3) - (priorityOrder[b.status ?? ''] ?? 3)
    if (priorityDiff !== 0) return priorityDiff
    return (b.eligibility_score || 0) - (a.eligibility_score || 0)
  })
})



const eligibleCount = computed(() => recommendations.value.filter(r => r.status === 'eligible').length)
const nearlyEligibleCount = computed(() => recommendations.value.filter(r => r.status === 'nearly_eligible').length)

const sortedEmployeeRecs = computed(() => {
  const recs = [...employeeRecs.value]
  const priorityOrder: Record<string, number> = { eligible: 0, nearly_eligible: 1, not_eligible: 2 }
  return recs.sort((a, b) => {
    const priorityDiff = (priorityOrder[a.status ?? ''] ?? 3) - (priorityOrder[b.status ?? ''] ?? 3)
    if (priorityDiff !== 0) return priorityDiff
    return (b.eligibility_score || 0) - (a.eligibility_score || 0)
  })
})

const eligibleRecCount = computed(() => employeeRecs.value.filter(r => r.status === 'eligible').length)
const nearlyEligibleRecCount = computed(() => employeeRecs.value.filter(r => r.status === 'nearly_eligible').length)

function getEmployeeDisplayName(emp: any) {
  const fullName = [emp?.first_name, emp?.last_name].filter(Boolean).join(' ').trim()
  return emp?.full_name || emp?.name || fullName || emp?.email || `Employee #${emp?.id}`
}

function getRecommendationStatusVariant(status: string) {
  switch (status) {
    case 'eligible': return 'success'
    case 'nearly_eligible': return 'warning'
    default: return 'secondary'
  }
}

function getScoreVariant(score: number | undefined | null) {
  if (!score) return 'secondary'
  if (score >= 85) return 'success'
  if (score >= 70) return 'default'
  if (score >= 50) return 'warning'
  return 'destructive'
}

function getScoreTierLabel(score: any) {
  const tier = String(score?.score_tier || '').trim()
  if (!tier) return 'No Score'
  if (tier === 'not_started') return 'Not Started'
  return tier
    .split('_')
    .map((part: string) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

function getScoreStandingLabel(score: any) {
  if (score?.score_tier === 'not_started') return 'Not Started'
  const tierLabel = getScoreTierLabel(score)
  return tierLabel === 'No Score' ? tierLabel : `${tierLabel} Tier`
}

const primaryScoreTip = computed(() => {
  if (!myScore.value) return ''
  if (myScore.value.score_tier === 'not_started') {
    return 'No performance data has been recorded yet. Your score will begin once attendance, appraisals, or manager notes are added.'
  }
  return myScore.value.improvement_suggestions?.[0] || ''
})

function resolveBenefitName(benefitId: number | string | undefined) {
  if (!benefitId) return 'Unknown Benefit'
  const option = items.value.find((item) => Number(item.id) === Number(benefitId))
  return option?.name || `Benefit #${benefitId}`
}

function getBenefitDescription(benefitId: number | string | undefined) {
  if (!benefitId) return ''
  return items.value.find((item) => Number(item.id) === Number(benefitId))?.description || ''
}

async function loadBenefits() {
  isLoading.value = true
  errorMsg.value = ''

  try {
    if (isEmployeeView.value) {
      const [assignedData, scoreData, benefitData] = await Promise.all([
        platformApi.getMyAllowances(),
        platformApi.getMyScore(),
        platformApi.getAllowanceOptions()
      ])
      
      const assignedItems = unwrapItems<any>(assignedData)
      myScore.value = (scoreData as any)?.data || scoreData
      allMyAllowances.value = Array.isArray(assignedData) ? assignedData : (assignedData as any)?.data || []
      items.value = unwrapItems<any>(benefitData).map((item: any) => ({
        ...item,
        description: item.description || 'No description provided',
        status: item.is_active ? 'Active' : 'Inactive'
      }))

      assignedBenefits.value = assignedItems.map((item: any) => ({
        ...item,
        name: item.allowance_option?.name || item.allowanceOption?.name || item.name || 'Benefit',
        description: item.allowance_option?.description || item.allowanceOption?.description || 'Assigned benefit',
        displayStatus: item.claimed ? 'Claimed' : item.status === 'active' ? 'Ready to Claim' : item.status === 'pending' ? 'Pending Approval' : 'Inactive'
      }))

      const empId = auth.user?.employee_id ?? auth.user?.employee?.id
      if (empId) {
        employeeRecsLoading.value = true
        try {
          const recData = await platformApi.getBenefitRecommendations(Number(empId))
          const recs = Array.isArray(recData) ? recData : Array.isArray((recData as any)?.data) ? (recData as any).data : []
          employeeRecs.value = recs.map((rec: any) => ({
            ...rec,
            benefit_name: rec.benefit_name || resolveBenefitName(rec.benefit_id),
            eligibility_score: Number(rec.eligibility_score ?? 0)
          }))
        } catch {
          employeeRecs.value = []
        } finally {
          employeeRecsLoading.value = false
        }
      }
    } else {
      const benefitData = await platformApi.getAllowanceOptions()
      items.value = unwrapItems<any>(benefitData).map((item: any) => ({
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
  if (benefit.claimed) return { label: 'Claimed', variant: 'info' as const, icon: CheckCircle }
  if (benefit.status === 'active') return { label: 'Ready to Claim', variant: 'success' as const, icon: Zap }
  if (benefit.status === 'pending') return { label: 'Pending', variant: 'warning' as const, icon: Clock }
  return { label: 'Inactive', variant: 'secondary' as const, icon: AlertCircle }
}

function openRequestModal(benefit: any) {
  pendingRequest.value = benefit
  requestAmount.value = 0
  showRequestModal.value = true
}

async function confirmRequest() {
  if (!pendingRequest.value) return
  errorMsg.value = ''
  feedback.value = ''
  showRequestModal.value = false
  try {
    await platformApi.submitBenefitRequest({
      allowance_option_id: pendingRequest.value.id,
      reason: 'Requested via Benefit Catalog',
      requested_amount: requestAmount.value
    })
    feedback.value = `Request for ${pendingRequest.value.name || pendingRequest.value.benefit_name || 'benefit'} submitted successfully.`
    router.push('/social/claims')
  } catch (err: any) {
    console.error('Benefit request failed', err)
    errorMsg.value = err?.response?.data?.message || err?.response?.data?.error || 'Failed to submit benefit request.'
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
    const empData = await platformApi.getEmployees()
    const allEmployees = unwrapItems<any>(empData)
    
    employees.value = allEmployees.map(emp => ({
      ...emp,
      full_name: getEmployeeDisplayName(emp)
    }))
  } catch (error) {
    console.error('Failed to load employees', error)
  }
}

async function fetchRecommendations() {
  if (!selectedEmployeeId.value) return

  recLoading.value = true
  recommendations.value = []
  selectedEmployeeScore.value = null
  
  try {
    const [scoreData, recData] = await Promise.all([
      platformApi.getEmployeeScore(Number(selectedEmployeeId.value)).catch(() => null as any),
      platformApi.getBenefitRecommendations(Number(selectedEmployeeId.value))
    ])
    
    const rawScore = (scoreData as any)?.score ?? scoreData
    selectedEmployeeScore.value = (rawScore as any)?.overall_score !== undefined ? rawScore : null
    
    const recs = Array.isArray(recData) ? recData : Array.isArray((recData as any)?.data) ? (recData as any).data : []
    recommendations.value = recs.map((rec: any) => ({
      ...rec,
      benefit_name: rec.benefit_name || resolveBenefitName(rec.benefit_id),
      eligibility_score: Number(rec.eligibility_score ?? 0)
    }))
  } catch (error) {
    console.error('Failed to fetch recommendations', error)
    errorMsg.value = 'Could not generate recommendations. Please try again.'
    recommendations.value = []
  } finally {
    recLoading.value = false
  }
}

function onEmployeeSelect(event: Event) {
  const target = event.target as HTMLSelectElement
  selectedEmployeeId.value = target.value
  recommendations.value = []
  if (selectedEmployeeId.value) {
    fetchRecommendations()
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
            {{ isEmployeeView ? 'View your assigned benefits and track eligibility.' : 'Manage benefit catalog and employee entitlements.' }}
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

    <div v-if="isEmployeeView && myScore" class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 to-purple-700 p-8 text-white shadow-xl">
      <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
      <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-indigo-400/20 blur-3xl"></div>

      <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="flex-1 space-y-4">
          <div class="flex items-center gap-2">
            <Badge variant="outline" class="border-indigo-300 text-indigo-100 font-bold uppercase tracking-widest text-[10px]">Your Growth Status</Badge>
          </div>
          <h2 class="text-3xl font-black tracking-tight">Your Performance Score</h2>
          <p v-if="myScore.score_tier === 'not_started'" class="text-indigo-100 text-lg opacity-90 max-w-xl">
            Your score is currently at <span class="font-bold underline decoration-emerald-400 decoration-2">0%</span> because no attendance, appraisal, or manager score data has been recorded yet.
          </p>
          <p v-else class="text-indigo-100 text-lg opacity-90 max-w-xl">
            You're in the <span class="font-bold underline decoration-emerald-400 decoration-2">{{ getScoreStandingLabel(myScore) }}</span>. 
            Maintain a score above 85% to unlock premium tier benefits.
          </p>

          <div v-if="primaryScoreTip" class="mt-6 flex flex-wrap gap-2">
            <div class="flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-2 text-sm backdrop-blur-md">
              <Sparkles class="h-4 w-4 text-emerald-300" />
              <span>{{ primaryScoreTip }}</span>
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

    <Card v-if="isEmployeeView && (employeeRecsLoading || sortedEmployeeRecs.length > 0)">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Brain class="w-5 h-5 text-indigo-500" />
          AI Benefit Recommendations
        </CardTitle>
        <CardDescription>Personalized benefits matched to your performance score and eligibility.</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="employeeRecsLoading" class="py-6 text-center">
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-indigo-200 border-t-indigo-600"></div>
          <p class="mt-3 text-sm text-slate-500">Analyzing your eligibility...</p>
        </div>
        <template v-else>
          <div class="flex flex-wrap gap-3 mb-6">
            <div class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-200">
              <CheckCircle class="h-3.5 w-3.5" /> {{ eligibleRecCount }} Eligible
            </div>
            <div class="flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
              <TrendingUp class="h-3.5 w-3.5" /> {{ nearlyEligibleRecCount }} Nearly Eligible
            </div>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <div
              v-for="rec in sortedEmployeeRecs"
              :key="rec.benefit_id"
              :class="[
                'rounded-2xl border p-5 transition-all hover:shadow-md',
                rec.status === 'eligible' ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-800 dark:bg-emerald-950/20' :
                rec.status === 'nearly_eligible' ? 'border-amber-200 bg-amber-50/50 dark:border-amber-800 dark:bg-amber-950/20' :
                'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900'
              ]"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                  <div :class="['flex h-10 w-10 items-center justify-center rounded-xl', rec.status === 'eligible' ? 'bg-emerald-100 dark:bg-emerald-500/20' : 'bg-amber-100 dark:bg-amber-500/20']">
                    <Gift :class="['h-5 w-5', rec.status === 'eligible' ? 'text-emerald-600' : 'text-amber-600']" />
                  </div>
                  <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white">{{ rec.benefit_name }}</h4>
                    <div class="flex items-center gap-2 mt-0.5">
                      <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ Math.round(rec.eligibility_score * 100) }}%</span>
                      <span class="text-xs text-slate-500">match</span>
                      <Badge :variant="getRecommendationStatusVariant(rec.status)" class="capitalize text-xs">
                        {{ rec.status?.replace('_', ' ') }}
                      </Badge>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-3 text-sm text-slate-600 dark:text-slate-400 line-clamp-2">
                {{ resolveBenefitName(rec.benefit_id) === rec.benefit_name ? '' : getBenefitDescription(rec.benefit_id) }}
              </div>

              <div v-if="rec.gap_actions?.length > 0 && rec.gap_actions[0] !== 'All requirements met - ready for assignment'" class="mt-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2">Required Actions</div>
                <div class="space-y-1">
                  <div v-for="action in rec.gap_actions" :key="action" class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <div class="mt-1.5 h-1.5 w-1.5 rounded-full bg-slate-400 flex-shrink-0"></div>
                    {{ action }}
                  </div>
                </div>
              </div>
              <div v-else-if="rec.status === 'eligible'" class="mt-3 rounded-lg bg-emerald-100/50 p-2 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                All requirements met. Ready for immediate assignment.
              </div>

              <div v-if="rec.estimated_months_to_qualify > 0" class="mt-2 text-xs text-slate-500">
                Est. {{ rec.estimated_months_to_qualify }} month(s) to qualify
              </div>

              <div class="mt-4 flex items-center gap-2">
                <Badge v-if="getClaimableBenefit(Number(rec.benefit_id))" variant="success" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                  <CheckCircle class="mr-1 h-3 w-3" /> Assigned
                </Badge>
                <Button
                  size="sm"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white ml-auto"
                  :disabled="claimingBenefitId === rec.benefit_id"
                  @click="openRequestModal({ id: rec.benefit_id, name: rec.benefit_name })"
                >
                  <Sparkles class="mr-1 h-3 w-3" /> Request Benefit
                </Button>
              </div>
            </div>
          </div>

          <div v-if="sortedEmployeeRecs.length === 0" class="py-8 text-center">
            <Award class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
            <p class="mt-3 text-sm text-slate-500">No recommendations available for your profile.</p>
          </div>
        </template>
      </CardContent>
    </Card>

    <Card v-if="isCreating && canManageBenefits">
      <CardHeader>
        <CardTitle>Create Benefit</CardTitle>
        <CardDescription>Add a new benefit option to the catalog.</CardDescription>
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
          <Input v-model="form.description" placeholder="Describe the benefit" />
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
                    <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400">TND {{ Number(benefit.amount || 0).toFixed(2) }}</div>
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

        <div v-if="isEmployeeView && sortedEmployeeRecs.length === 0 && items.length > 0" class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Available Benefits Catalog</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">Browse and request benefits from the catalog.</p>
        </div>
        
        <div v-if="!isEmployeeView || sortedEmployeeRecs.length === 0">
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
                <Badge v-if="getClaimableBenefit(item.id)" variant="success" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">Assigned</Badge>
                <Button size="sm" variant="outline" @click="openRequestModal(item)">Request</Button>
              </div>
            </template>
          </DataTable>
        </div>
      </CardContent>
    </Card>

    <Dialog :open="showRequestModal" :title="`Request ${pendingRequest?.name || 'Benefit'}`" @close="showRequestModal = false">
      <div class="space-y-4">
        <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-purple-50 p-4 dark:border-indigo-900/40 dark:from-indigo-950/40 dark:to-purple-950/40">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600">
              <Gift class="h-5 w-5 text-white" />
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300">
              Specify the amount you'd like to request for <strong>{{ pendingRequest?.name || 'this benefit' }}</strong>.
            </p>
          </div>
        </div>

        <div class="space-y-2">
          <Label>Requested Amount (TND)</Label>
          <Input v-model.number="requestAmount" type="number" min="0" step="0.01" placeholder="0.00" />
        </div>
      </div>

      <template #footer>
        <div class="flex w-full justify-between gap-3">
          <Button variant="outline" class="flex-1" @click="showRequestModal = false">Cancel</Button>
          <Button class="flex-1 bg-indigo-600 text-white hover:bg-indigo-700" :disabled="!requestAmount && requestAmount !== 0" @click="confirmRequest">
            <Sparkles class="mr-2 h-4 w-4" /> Submit Request
          </Button>
        </div>
      </template>
    </Dialog>

    <Dialog :open="showRecModal" title="AI Benefit Recommendations" @close="showRecModal = false">
      <div class="space-y-5">
        <div class="rounded-2xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-purple-50 p-4 dark:border-indigo-900/40 dark:from-indigo-950/40 dark:to-purple-950/40">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600">
              <Brain class="h-5 w-5 text-white" />
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300">
              Select an employee to view personalized benefit eligibility recommendations based on their performance score.
            </p>
          </div>
        </div>

        <div class="space-y-2">
          <Label>Select Employee</Label>
          <select 
            :value="selectedEmployeeId"
            class="flex h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-800 dark:bg-slate-950 dark:text-white"
            @change="onEmployeeSelect"
          >
            <option value="">Choose an employee...</option>
            <option v-for="emp in employees" :key="emp.id" :value="String(emp.id)">
              {{ emp.full_name }}
            </option>
          </select>
        </div>

        <div v-if="selectedEmployeeScore" class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <Badge :variant="getScoreVariant(selectedEmployeeScore.overall_score)" class="h-10 w-10 rounded-lg p-0 flex items-center justify-center text-lg font-bold">
                {{ Math.round(selectedEmployeeScore.overall_score || 0) }}
              </Badge>
              <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">Employee Score</div>
                <div class="text-sm font-medium capitalize">{{ getScoreStandingLabel(selectedEmployeeScore) }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="recLoading" class="py-8 text-center">
          <div class="mx-auto h-8 w-8 animate-spin rounded-full border-2 border-indigo-200 border-t-indigo-600"></div>
          <p class="mt-3 text-sm text-slate-500">Analyzing eligibility...</p>
        </div>

        <template v-else-if="selectedEmployeeId">
          <div v-if="sortedRecommendations.length > 0" class="space-y-3">
            <div class="flex flex-wrap gap-3">
              <div class="flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-200">
                <CheckCircle class="h-3.5 w-3.5" /> {{ eligibleCount }} Eligible
              </div>
              <div class="flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-200">
                <TrendingUp class="h-3.5 w-3.5" /> {{ nearlyEligibleCount }} Nearly Eligible
              </div>
            </div>

            <div 
              v-for="rec in sortedRecommendations" 
              :key="rec.benefit_id"
              :class="[
                'rounded-2xl border p-4 transition-all',
                rec.status === 'eligible' ? 'border-emerald-200 bg-emerald-50/50 dark:border-emerald-800 dark:bg-emerald-950/20' :
                rec.status === 'nearly_eligible' ? 'border-amber-200 bg-amber-50/50 dark:border-amber-800 dark:bg-amber-950/20' :
                'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900'
              ]"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-slate-900 dark:text-white">{{ rec.benefit_name }}</h4>
                    <Badge :variant="getRecommendationStatusVariant(rec.status)" class="capitalize text-xs">
                      {{ rec.status?.replace('_', ' ') }}
                    </Badge>
                  </div>
                  <div class="mt-1 flex items-center gap-2">
                    <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ Math.round(rec.eligibility_score * 100) }}%</span>
                    <span class="text-xs text-slate-500">eligibility</span>
                  </div>
                </div>
              </div>

              <div v-if="rec.gap_actions?.length > 0 && rec.gap_actions[0] !== 'All requirements met - ready for assignment'" class="mt-3">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-2">Required Actions</div>
                <div class="space-y-1">
                  <div v-for="action in rec.gap_actions" :key="action" class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <div class="mt-1.5 h-1.5 w-1.5 rounded-full bg-slate-400 flex-shrink-0"></div>
                    {{ action }}
                  </div>
                </div>
              </div>
              <div v-else-if="rec.status === 'eligible'" class="mt-3 rounded-lg bg-emerald-100/50 p-2 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                All requirements met. Ready for immediate assignment.
              </div>

              <div v-if="rec.estimated_months_to_qualify > 0" class="mt-2 text-xs text-slate-500">
                Est. {{ rec.estimated_months_to_qualify }} month(s) to qualify
              </div>
            </div>
          </div>

          <div v-else class="py-8 text-center">
            <Award class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
            <p class="mt-3 text-sm text-slate-500">No recommendations available for this employee.</p>
          </div>
        </template>

        <div v-else class="py-8 text-center">
          <Target class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
          <p class="mt-3 text-sm text-slate-500">Select an employee to view their benefit eligibility.</p>
        </div>
      </div>

      <template #footer>
        <Button variant="outline" @click="showRecModal = false">Close</Button>
        <Button :disabled="!selectedEmployeeId || recLoading" class="bg-indigo-600 text-white hover:bg-indigo-700" @click="fetchRecommendations">
          <Brain class="w-4 h-4 mr-2" /> Refresh Analysis
        </Button>
      </template>
    </Dialog>
  </div>
</template>
