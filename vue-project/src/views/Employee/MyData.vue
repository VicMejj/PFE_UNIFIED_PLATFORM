<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { 
  Bell, 
  CalendarDays, 
  Clock3, 
  Download, 
  FileSignature, 
  Gift, 
  Settings, 
  UserCircle2, 
  Sparkles, 
  Activity, 
  Plus,
  CheckCircle,
  Clock,
  AlertCircle,
  Zap,
  FileText,
  Eye,
  History,
  XCircle
} from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Label from '@/components/ui/Label.vue'
import Input from '@/components/ui/Input.vue'
import Badge from '@/components/ui/Badge.vue'
import Dialog from '@/components/ui/Dialog.vue'
import { useAuthStore } from '@/stores/auth'
import { getAvatarUrl } from '@/api/http'
import { platformApi, type ContractItem } from '@/api/laravel/platform'
import { submitClaim, getMyEnrollments, uploadClaimDocument, getMyClaims, getClaim } from '@/api/laravel/insurance'
import { useNotificationsStore } from '@/stores/notifications'
import { reactive } from 'vue'

const auth = useAuthStore()
const notifications = useNotificationsStore()
const avatarUrl = computed(() => getAvatarUrl(auth.user))
const contracts = ref<ContractItem[]>([])
const isLoadingContracts = ref(false)
const benefitRecommendations = ref<any[]>([])
const isLoadingBenefits = ref(false)
const employeeId = computed(() => Number(auth.user?.employee_id || 0))
const allAllowances = ref<any[]>([])
const isLoadingAllowances = ref(false)
const claimingBenefitId = ref<number | null>(null)
const claimSuccess = ref<number | null>(null)

const shortcuts = [
  { title: 'Edit Profile', description: 'Update your personal information and photo.', href: '/profile', icon: UserCircle2 },
  { title: 'Leave Requests', description: 'Submit or track time off requests.', href: '/leave-requests', icon: CalendarDays },
  { title: 'Attendance', description: 'Record your daily attendance and review history.', href: '/attendance', icon: Clock3 },
  { title: 'Contract Review', description: 'Open and confirm assigned contracts with your verification code.', href: '/contract-review', icon: FileSignature },
  { title: 'Notifications', description: 'Check account and leave updates.', href: '/notifications', icon: Bell },
  { title: 'Settings', description: 'Change password and appearance preferences.', href: '/settings', icon: Settings }
]

const myScore = ref<any>(null)
const enrollments = ref<any[]>([])
const claimForm = reactive({
  enrollment_id: '',
  claim_date: new Date().toISOString().split('T')[0],
  claimed_amount: ''
})
const claimFiles = reactive<{ [key: string]: File | null }>({
  invoice: null,
  prescription: null
})
const isSubmittingClaim = ref(false)
const claimFeedback = ref('')
const claimError = ref('')

const myClaims = ref<any[]>([])
const isLoadingClaims = ref(false)
const selectedClaim = ref<any | null>(null)
const claimDocuments = ref<any[]>([])
const showClaimDetailModal = ref(false)
const showImageModal = ref(false)
const selectedImageUrl = ref('')
const isLoadingClaimDocuments = ref(false)

const signedContracts = computed(() =>
  Array.isArray(contracts.value)
    ? contracts.value.filter(contract => contract.status === 'signed')
    : []
)

async function loadContracts() {
  isLoadingContracts.value = true
  try {
    const data = await platformApi.getContracts()
    contracts.value = data
  } catch (error) {
    console.error('Failed to load contracts:', error)
  } finally {
    isLoadingContracts.value = false
  }
}

async function loadBenefits() {
  if (!employeeId.value) return
  isLoadingBenefits.value = true
  try {
    const response: any = await platformApi.getBenefitRecommendations(employeeId.value)
    benefitRecommendations.value = Array.isArray(response)
      ? response
      : response?.data || response?.recommendations || []
  } catch (error) {
    console.error('Failed to load benefit recommendations:', error)
  } finally {
    isLoadingBenefits.value = false
  }
}

async function downloadContract(contract: ContractItem) {
  try {
    const blob = await platformApi.downloadContract(contract.id)
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `contract-${contract.id}.pdf`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
  } catch (error) {
    console.error('Download failed:', error)
    alert('Failed to download contract. Please try again.')
  }
}

async function loadMyScore() {
  try {
    const data = await platformApi.getMyScore()
    myScore.value = (data as any)?.data || data
  } catch (error) {
    console.error('Failed to load score:', error)
  }
}

async function loadMyAllowances() {
  isLoadingAllowances.value = true
  try {
    const data = await platformApi.getMyAllowances()
    allAllowances.value = Array.isArray(data) ? data : (data as any)?.data || []
  } catch (error) {
    console.error('Failed to load allowances:', error)
    allAllowances.value = []
  } finally {
    isLoadingAllowances.value = false
  }
}

async function claimBenefit(benefitId: number) {
  claimingBenefitId.value = benefitId
  try {
    await platformApi.claimAllowance(benefitId)
    claimSuccess.value = benefitId
    await loadMyAllowances()
    notifications.fetchNotifications()
  } catch (error: any) {
    console.error('Failed to claim benefit:', error)
    alert(error.response?.data?.message || 'Failed to claim benefit. Please try again.')
  } finally {
    claimingBenefitId.value = null
  }
}

function getBenefitStatusInfo(benefit: any) {
  if (benefit.claimed) {
    return { label: 'Claimed', variant: 'info' as const, icon: CheckCircle, color: 'text-blue-500' }
  }
  if (benefit.status === 'active') {
    return { label: 'Ready to Claim', variant: 'success' as const, icon: Zap, color: 'text-emerald-500' }
  }
  if (benefit.status === 'pending') {
    return { label: 'Pending Approval', variant: 'warning' as const, icon: Clock, color: 'text-amber-500' }
  }
  return { label: 'Inactive', variant: 'secondary' as const, icon: AlertCircle, color: 'text-slate-400' }
}

async function loadEnrollments() {
  try {
    const data = await getMyEnrollments()
    enrollments.value = Array.isArray(data) ? data : (data as any)?.data || []
    if (enrollments.value.length > 0) {
      claimForm.enrollment_id = String(enrollments.value[0].id)
    }
  } catch (err: any) {
    console.error('Failed to load enrollments:', err.response?.data || err.message)
    enrollments.value = []
  }
}

function handleClaimFileUpload(event: Event, type: 'invoice' | 'prescription') {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    claimFiles[type] = target.files[0]
  }
}

async function submitEmployeeClaim() {
  claimError.value = ''
  claimFeedback.value = ''
  if (!claimForm.enrollment_id) {
    claimError.value = 'Please select an insurance enrollment.'
    return
  }
  if (!claimForm.claimed_amount || Number(claimForm.claimed_amount) <= 0) {
    claimError.value = 'Please enter a valid claim amount.'
    return
  }

  isSubmittingClaim.value = true
  try {
    const claimData = {
      enrollment_id: Number(claimForm.enrollment_id),
      claim_date: claimForm.claim_date,
      claimed_amount: Number(claimForm.claimed_amount),
      total_amount: Number(claimForm.claimed_amount),
    }

    const claim: any = await submitClaim(claimData)
    
    if (claim && claim.id) {
      for (const [type, file] of Object.entries(claimFiles)) {
        if (file) {
          try {
            const docFormData = new FormData()
            docFormData.append('document', file)
            docFormData.append('document_type', type)
            const docResult = await uploadClaimDocument(claim.id, docFormData)
            console.log('Uploaded document:', type, docResult)
          } catch (docErr: any) {
            console.error(`Failed to upload ${type} document:`, docErr.response?.data || docErr.message)
          }
        }
      }
      // Refresh claim to get updated documents
      await loadMyClaims()
    }
    
    claimFeedback.value = 'Claim submitted successfully!'
    claimForm.claimed_amount = ''
    claimFiles.invoice = null
    claimFiles.prescription = null
  } catch (error: any) {
    console.error('Claim submission failed:', error)
    claimError.value = error.response?.data?.message || 'Failed to submit claim. Please try again.'
  } finally {
    isSubmittingClaim.value = false
  }
}

async function loadMyClaims() {
  isLoadingClaims.value = true
  try {
    const data = await getMyClaims()
    myClaims.value = Array.isArray(data) ? data : (data as any)?.data || []
  } catch (error: any) {
    console.error('Failed to load claims:', error.response?.data || error.message)
    myClaims.value = []
  } finally {
    isLoadingClaims.value = false
  }
}

async function openClaimDetail(claim: any) {
  isLoadingClaimDocuments.value = true
  showClaimDetailModal.value = true
  claimDocuments.value = []
  
  try {
    // Get full claim details with documents
    console.log('Loading claim details for ID:', claim.id)
    const fullClaim: any = await getClaim(claim.id)
    console.log('Full claim response:', fullClaim)
    
    if (fullClaim) {
      selectedClaim.value = fullClaim
      claimDocuments.value = fullClaim.documents || fullClaim.claim_documents || []
      console.log('Documents from claim:', claimDocuments.value)
    }
  } catch (err: any) {
    console.error('Failed to load claim details:', err.response?.data || err.message)
    selectedClaim.value = claim
  } finally {
    isLoadingClaimDocuments.value = false
  }
}

function getClaimStatusVariant(status: string) {
  const s = status?.toLowerCase() || ''
  if (s.includes('approved') || s.includes('paid')) return 'success'
  if (s.includes('rejected')) return 'destructive'
  if (s.includes('pending') || s.includes('review') || s.includes('under_review')) return 'warning'
  return 'secondary'
}

function getClaimStatusLabel(status: string) {
  const s = status?.toLowerCase() || ''
  if (s.includes('paid')) return 'Paid'
  if (s.includes('approved')) return 'Approved'
  if (s.includes('rejected')) return 'Rejected'
  if (s.includes('sent_to_provider')) return 'Sent to Provider'
  if (s.includes('pending') || s.includes('under_review')) return 'Pending Review'
  return status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ')
}

function openDocUrl(doc: any) {
  // Show image in a modal
  selectedImageUrl.value = '/storage/' + doc.file_path
  showImageModal.value = true
}

function downloadDoc(doc: any) {
  const url = '/storage/' + doc.file_path
  const filename = doc.document_name || doc.file_name || 'document'
  
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

function getClaimStatusIcon(status: string) {
  const s = status?.toLowerCase() || ''
  if (s.includes('approved') || s.includes('paid')) return CheckCircle
  if (s.includes('rejected')) return XCircle
  if (s.includes('pending') || s.includes('review')) return Clock
  return AlertCircle
}

const claimStats = computed(() => ({
  total: myClaims.value.length,
  pending: myClaims.value.filter(c => ['pending', 'under_review'].includes(c.status)).length,
  approved: myClaims.value.filter(c => c.status === 'approved').length,
  paid: myClaims.value.filter(c => c.status === 'paid').length,
  rejected: myClaims.value.filter(c => c.status === 'rejected').length,
}))

onMounted(() => {
  loadContracts()
  loadBenefits()
  loadMyScore()
  loadMyAllowances()
  loadEnrollments()
  loadMyClaims()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">My Workspace</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Everything you need is now reachable from here: performance, benefits, insurance, and documents.
        </p>
      </div>
      <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
          <img v-if="avatarUrl" :src="avatarUrl" alt="Profile photo" class="h-full w-full object-cover" />
          <span v-else class="text-sm font-semibold text-slate-700 dark:text-slate-200">
            {{ auth.user?.name?.slice(0, 1) || 'U' }}
          </span>
        </div>
        <div>
          <div class="font-semibold text-slate-900 dark:text-white">{{ auth.user?.name }}</div>
          <div class="text-sm text-slate-500 dark:text-slate-400">{{ auth.user?.email }}</div>
        </div>
      </div>
    </div>

    <!-- Performance Section -->
    <div v-if="myScore" class="relative overflow-hidden rounded-3xl bg-indigo-600 p-8 text-white shadow-xl shadow-indigo-100 dark:shadow-none">
      <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
      <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-indigo-400/20 blur-3xl"></div>

      <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="flex-1 space-y-4">
          <div class="flex items-center gap-2">
            <Badge variant="outline" class="border-indigo-300 text-indigo-100 font-bold uppercase tracking-widest text-[10px]">Your Growth Status</Badge>
          </div>
          <h2 class="text-3xl font-black tracking-tight">Your Performance Score</h2>
          <p class="text-indigo-100 text-lg opacity-90 max-w-xl">
            Current Standing: <span class="font-bold underline decoration-emerald-400 decoration-2">{{ myScore.score_tier }} Tier</span>.
          </p>
          <div v-if="myScore.improvement_suggestions?.length" class="mt-4 flex flex-wrap gap-2">
            <div v-for="tip in myScore.improvement_suggestions.slice(0, 1)" :key="tip" class="flex items-center gap-2 rounded-2xl bg-white/10 px-4 py-2 text-sm backdrop-blur-md">
              <Sparkles class="h-4 w-4 text-emerald-300" />
              <span>AI Tip: {{ tip }}</span>
            </div>
          </div>
        </div>

        <div class="flex flex-col items-center gap-2">
          <div class="relative flex h-32 w-32 items-center justify-center rounded-full border-8 border-indigo-500/30 bg-indigo-500/20 shadow-inner">
             <div class="text-center">
               <span class="text-4xl font-black">{{ Math.round(myScore.overall_score) }}%</span>
             </div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Card class="md:col-span-2 xl:col-span-3 border-sky-200/70 bg-gradient-to-r from-sky-50 via-white to-cyan-50 shadow-lg dark:border-sky-900/40 dark:from-sky-950/30 dark:via-slate-950 dark:to-cyan-950/20">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Clock3 class="h-5 w-5 text-sky-500" />
            Attendance
          </CardTitle>
          <CardDescription>
            Record your daily attendance, then open the attendance page to review your history in a clean timeline.
          </CardDescription>
        </CardHeader>
        <CardContent class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="text-sm text-slate-600 dark:text-slate-300">
            Your attendance workspace is ready. Use the quick punch actions for a fast check-in or check-out.
          </div>
          <Button class="border border-sky-200 bg-sky-600 text-white shadow-sm hover:bg-sky-500 dark:border-sky-900/50" @click="$router.push('/attendance')">
            Open Attendance
          </Button>
        </CardContent>
      </Card>

      <Card v-for="shortcut in shortcuts" :key="shortcut.title">
        <CardHeader>
          <CardTitle class="flex items-center gap-2 text-base">
            <component :is="shortcut.icon" class="h-5 w-5 text-sky-500" />
            {{ shortcut.title }}
          </CardTitle>
          <CardDescription class="text-xs line-clamp-1">{{ shortcut.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <Button size="sm" class="w-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" @click="$router.push(shortcut.href)">
            Open
          </Button>
        </CardContent>
      </Card>
    </div>

    <!-- Benefits Section -->
    <Card class="border-emerald-200/70 bg-gradient-to-br from-emerald-50 via-white to-sky-50 shadow-lg dark:border-emerald-900/40 dark:from-emerald-950/20 dark:via-slate-950 dark:to-sky-950/20">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Gift class="h-5 w-5 text-emerald-500" />
          Your Benefits
        </CardTitle>
        <CardDescription>
          All benefits assigned to you by your manager.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoadingAllowances" class="py-8 text-center text-sm text-slate-500">
          Loading your benefits...
        </div>
        <div v-else-if="allAllowances.length === 0" class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 dark:border-slate-800">
          <Gift class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
          <p class="mt-4">No benefits assigned to your profile at this moment.</p>
          <p class="mt-1 text-xs text-slate-400">Contact HR if you believe you should have access to benefits.</p>
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="benefit in allAllowances"
            :key="benefit.id"
            class="rounded-2xl border border-slate-200 bg-white p-5 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-900"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-500/20">
                  <Gift class="h-6 w-6 text-emerald-600" />
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-slate-900 dark:text-white">
                    {{ benefit.allowance_option?.name || benefit.name || 'Company Sponsored Perk' }}
                  </h4>
                  <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ benefit.allowance_option?.description || 'Benefit allocated by your manager.' }}
                  </p>
                  <div class="mt-2 flex flex-wrap items-center gap-2">
                    <Badge :variant="getBenefitStatusInfo(benefit).variant" class="capitalize">
                      <component :is="getBenefitStatusInfo(benefit).icon" class="mr-1 h-3 w-3" />
                      {{ getBenefitStatusInfo(benefit).label }}
                    </Badge>
                    <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                      ${{ Number(benefit.amount || 0).toFixed(2) }}
                    </span>
                    <span class="text-xs text-slate-400">/ month</span>
                  </div>
                </div>
              </div>
              <div class="flex flex-col items-end gap-2">
                <div v-if="benefit.status === 'active' && !benefit.claimed" class="flex items-center gap-2">
                  <Button
                    class="bg-emerald-600 hover:bg-emerald-700 text-white"
                    :disabled="claimingBenefitId === benefit.id"
                    @click="claimBenefit(benefit.id)"
                  >
                    <Zap v-if="claimingBenefitId !== benefit.id" class="mr-2 h-4 w-4" />
                    <span v-if="claimingBenefitId === benefit.id" class="animate-pulse mr-2">...</span>
                    {{ claimingBenefitId === benefit.id ? 'Claiming...' : 'Claim Benefit' }}
                  </Button>
                </div>
                <div v-if="benefit.claimed" class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                  <CheckCircle class="h-5 w-5" />
                  <span class="text-sm font-medium">Claimed on {{ new Date(benefit.claimed_at).toLocaleDateString() }}</span>
                </div>
                <div v-if="benefit.status === 'pending'" class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                  <Clock class="h-4 w-4" />
                  <span class="text-xs">Waiting for manager activation</span>
                </div>
              </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
              <span>Start: {{ benefit.start_date || 'N/A' }}</span>
              <span v-if="benefit.end_date">End: {{ benefit.end_date }}</span>
            </div>
          </div>
        </div>
        <div class="mt-4 flex justify-end">
          <Button variant="outline" class="text-emerald-600 border-emerald-200 hover:bg-emerald-50 dark:text-emerald-400" @click="$router.push('/social/benefits')">
            View All Benefits
          </Button>
        </div>
      </CardContent>
    </Card>

    <Card class="border-blue-200/70 shadow-lg dark:border-blue-900/40">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Activity class="h-5 w-5 text-blue-500" />
            Insurance Claim Document
          </CardTitle>
          <CardDescription>Submit your insurance documents (invoices, prescriptions) directly to HR.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div v-if="claimFeedback" class="text-sm text-emerald-600 font-medium mb-2">{{ claimFeedback }}</div>
          <div v-if="claimError" class="text-sm text-rose-600 font-medium mb-2">{{ claimError }}</div>
          
          <div v-if="enrollments.length === 0" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
            No active insurance enrollment found. Please contact HR to get enrolled in an insurance plan.
          </div>
          
          <div v-else class="space-y-4">
            <div class="space-y-2">
              <Label class="text-xs">Insurance Plan *</Label>
              <select
                v-model="claimForm.enrollment_id"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              >
                <option value="">Select your enrollment...</option>
                <option v-for="enrollment in enrollments" :key="enrollment.id" :value="String(enrollment.id)">
                  {{ enrollment.policy?.name || enrollment.policy?.policy_name || `Plan #${enrollment.policy_id}` }} 
                  ({{ enrollment.status || 'active' }})
                </option>
              </select>
            </div>
          
          <div class="grid grid-cols-2 gap-3">
             <div class="space-y-1.5">
               <Label class="text-xs">Invoice (Facture)</Label>
               <div class="relative">
                 <Input type="file" @change="handleClaimFileUpload($event, 'invoice')" class="text-[10px] h-9" />
               </div>
             </div>
             <div class="space-y-1.5">
               <Label class="text-xs">Prescription</Label>
               <div class="relative">
                 <Input type="file" @change="handleClaimFileUpload($event, 'prescription')" class="text-[10px] h-9" />
               </div>
             </div>
          </div>
          
          <div class="space-y-1.5">
            <Label class="text-xs">Claim Amount ($)</Label>
            <Input v-model="claimForm.claimed_amount" type="number" placeholder="0.00" class="h-9" />
          </div>

          <Button 
            class="w-full bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50" 
            :disabled="isSubmittingClaim || !claimForm.enrollment_id || !claimForm.claimed_amount"
            @click="submitEmployeeClaim"
          >
            <Plus v-if="!isSubmittingClaim" class="h-4 w-4 mr-2" />
            <span v-else class="animate-pulse mr-2">...</span>
            {{ isSubmittingClaim ? 'Submitting...' : 'Submit Claim Documents' }}
          </Button>
          </div>
      </CardContent>
    </Card>

    <!-- My Claims History -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <div>
            <CardTitle class="flex items-center gap-2">
              <History class="h-5 w-5 text-violet-500" />
              My Claims History
            </CardTitle>
            <CardDescription>Track all your insurance claims and their status.</CardDescription>
          </div>
          <Button variant="outline" size="sm" @click="loadMyClaims" :disabled="isLoadingClaims">
            Refresh
          </Button>
        </div>
      </CardHeader>
      <CardContent class="space-y-4">
        <!-- Stats -->
        <div class="grid grid-cols-4 gap-3">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-center dark:border-slate-700 dark:bg-slate-900">
            <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ claimStats.total }}</div>
            <div class="text-xs text-slate-500">Total</div>
          </div>
          <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-center dark:border-amber-900/40 dark:bg-amber-950/20">
            <div class="text-2xl font-bold text-amber-600">{{ claimStats.pending }}</div>
            <div class="text-xs text-amber-600">Pending</div>
          </div>
          <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-center dark:border-emerald-900/40 dark:bg-emerald-950/20">
            <div class="text-2xl font-bold text-emerald-600">{{ claimStats.approved + claimStats.paid }}</div>
            <div class="text-xs text-emerald-600">Approved</div>
          </div>
          <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-center dark:border-red-900/40 dark:bg-red-950/20">
            <div class="text-2xl font-bold text-red-600">{{ claimStats.rejected }}</div>
            <div class="text-xs text-red-600">Rejected</div>
          </div>
        </div>

        <!-- Claims List -->
        <div v-if="isLoadingClaims" class="py-8 text-center text-sm text-slate-500">
          Loading your claims...
        </div>
        
        <div v-else-if="myClaims.length === 0" class="rounded-xl border border-dashed border-slate-300 p-8 text-center">
          <FileText class="mx-auto h-12 w-12 text-slate-300" />
          <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">No Claims Yet</h3>
          <p class="mt-1 text-xs text-slate-500">Submit your first insurance claim above.</p>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="claim in myClaims"
            :key="claim.id"
            class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-900 cursor-pointer"
            @click="openClaimDetail(claim)"
          >
            <div class="flex items-center gap-4">
              <div :class="[
                'flex h-10 w-10 items-center justify-center rounded-xl',
                claim.status === 'approved' || claim.status === 'paid' ? 'bg-emerald-100 dark:bg-emerald-500/20' :
                claim.status === 'rejected' ? 'bg-red-100 dark:bg-red-500/20' :
                'bg-amber-100 dark:bg-amber-500/20'
              ]">
                <component :is="getClaimStatusIcon(claim.status)" :class="[
                  'h-5 w-5',
                  claim.status === 'approved' || claim.status === 'paid' ? 'text-emerald-600' :
                  claim.status === 'rejected' ? 'text-red-600' :
                  'text-amber-600'
                ]" />
              </div>
              <div>
                <div class="font-semibold text-slate-900 dark:text-white">{{ claim.claim_number || `Claim #${claim.id}` }}</div>
                <div class="text-sm text-slate-500">{{ claim.policy?.name || claim.enrollment?.policy?.name || 'Insurance' }}</div>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="text-right">
                <div class="font-bold text-emerald-600">${{ Number(claim.claimed_amount || 0).toFixed(2) }}</div>
                <div class="text-xs text-slate-500">{{ new Date(claim.claim_date || claim.created_at).toLocaleDateString() }}</div>
              </div>
              <Badge :variant="getClaimStatusVariant(claim.status)" class="capitalize">
                {{ getClaimStatusLabel(claim.status) }}
              </Badge>
              <Button variant="ghost" size="sm">
                <Eye class="h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Claim Detail Modal -->
    <Dialog :open="showClaimDetailModal" :title="`Claim ${selectedClaim?.claim_number || `#${selectedClaim?.id}`}`" @close="showClaimDetailModal = false">
      <div v-if="selectedClaim" class="space-y-4">
        <div class="flex items-center justify-between">
          <Badge :variant="getClaimStatusVariant(selectedClaim.status)" class="capitalize text-sm">
            {{ getClaimStatusLabel(selectedClaim.status) }}
          </Badge>
          <span class="text-2xl font-bold text-emerald-600">${{ Number(selectedClaim.claimed_amount || 0).toFixed(2) }}</span>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <div class="text-slate-500">Filed On</div>
            <div class="font-medium">{{ new Date(selectedClaim.claim_date || selectedClaim.created_at).toLocaleDateString() }}</div>
          </div>
          <div v-if="selectedClaim.approved_amount">
            <div class="text-slate-500">Approved Amount</div>
            <div class="font-medium text-emerald-600">${{ Number(selectedClaim.approved_amount).toFixed(2) }}</div>
          </div>
          <div v-if="selectedClaim.payment_date">
            <div class="text-slate-500">Payment Date</div>
            <div class="font-medium">{{ new Date(selectedClaim.payment_date).toLocaleDateString() }}</div>
          </div>
        </div>

        <div v-if="selectedClaim.description" class="space-y-1">
          <div class="text-sm text-slate-500">Description</div>
          <div class="rounded-lg bg-slate-50 p-3 text-sm dark:bg-slate-800">{{ selectedClaim.description }}</div>
        </div>

        <div v-if="selectedClaim.rejection_reason" class="space-y-1">
          <div class="text-sm text-red-500">Rejection Reason</div>
          <div class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-950/20 dark:text-red-300">{{ selectedClaim.rejection_reason }}</div>
        </div>

        <!-- Documents -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <div class="text-sm font-medium text-slate-900 dark:text-white">Uploaded Documents</div>
            <Badge v-if="isLoadingClaimDocuments" variant="secondary" class="text-xs">Loading...</Badge>
            <Badge v-else variant="outline" class="text-xs">{{ claimDocuments.length }} file(s)</Badge>
          </div>
          
          <div v-if="isLoadingClaimDocuments" class="py-4 text-center text-sm text-slate-500">
            Loading documents...
          </div>
          
          <div v-else-if="claimDocuments.length === 0" class="rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
            No documents uploaded.
          </div>
          
          <div v-else class="space-y-2">
            <div 
              v-for="doc in claimDocuments" 
              :key="doc.id" 
              class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800"
            >
              <div class="flex items-center gap-3">
                <FileText class="h-5 w-5 text-slate-400" />
                <div>
                  <div class="text-sm font-medium">{{ doc.document_type || doc.document_name || doc.file_name || 'Document' }}</div>
                  <div class="text-xs text-slate-500">{{ doc.document_name || doc.file_name || 'No filename' }}</div>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <Button variant="ghost" size="sm" @click="openDocUrl(doc)">
                  <Eye class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="sm" @click="downloadDoc(doc)">
                  <Download class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <template #footer>
        <Button variant="outline" @click="showClaimDetailModal = false">Close</Button>
      </template>
    </Dialog>

    <!-- Image Preview Modal -->
    <Dialog :open="showImageModal" title="Document Preview" @close="showImageModal = false">
      <div v-if="selectedImageUrl" class="flex justify-center">
        <img :src="selectedImageUrl" alt="Document" class="max-h-[70vh] max-w-full rounded-lg" />
      </div>
      <template #footer>
        <Button variant="outline" @click="showImageModal = false">Close</Button>
      </template>
    </Dialog>

    <Card>
      <CardHeader>
        <CardTitle>My Signed Contracts</CardTitle>
        <CardDescription>
          Download your signed contracts as PDF files.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoadingContracts" class="py-8 text-center text-sm text-slate-500">
          Loading contracts...
        </div>
        <div v-else-if="!signedContracts.length" class="py-8 text-center text-sm text-slate-500">
          No signed contracts found.
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="contract in signedContracts"
            :key="contract.id"
            class="flex items-center justify-between rounded-lg border border-slate-200 p-4 dark:border-slate-700"
          >
            <div>
              <div class="font-medium text-slate-900 dark:text-white">
                {{ contract.title || `Contract #${contract.id}` }}
              </div>
              <div class="text-sm text-slate-500 dark:text-slate-400">
                Signed on {{ new Date(contract.signed_at ?? contract.updated_at ?? '').toLocaleDateString() }}
              </div>
            </div>
            <Button
              class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
              @click="downloadContract(contract)"
            >
              <Download class="mr-2 h-4 w-4" />
              Download PDF
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
