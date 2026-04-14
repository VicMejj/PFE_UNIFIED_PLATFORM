<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Activity, FileText, Clock, CheckCircle, XCircle, AlertCircle, Plus, Eye } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import Dialog from '@/components/ui/Dialog.vue'
import { getMyEnrollments, getMyClaims, submitClaim, uploadClaimDocument } from '@/api/laravel/insurance'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const myClaims = ref<any[]>([])
const enrollments = ref<any[]>([])
const isLoading = ref(true)
const isSubmitting = ref(false)
const showCreateModal = ref(false)
const showDetailModal = ref(false)
const selectedClaim = ref<any | null>(null)
const feedback = ref('')
const errorMsg = ref('')
const uploadingDocument = ref(false)

const form = reactive({
  enrollment_id: '',
  claim_date: new Date().toISOString().split('T')[0],
  claimed_amount: '',
  description: '',
})

const claimFiles = reactive<{ [key: string]: File | null }>({
  invoice: null,
  prescription: null,
  medical_report: null,
  other: null,
})

async function initAndLoadData() {
  if (!auth.isInitialized) {
    await auth.initializeAuth()
  }
  await loadData()
}

async function loadData() {
  isLoading.value = true
  errorMsg.value = ''
  feedback.value = ''
  
  try {
    const [enrollmentData, claimsData] = await Promise.all([
      getMyEnrollments().catch(() => []),
      getMyClaims().catch(() => [])
    ])
    
    enrollments.value = enrollmentData || []
    myClaims.value = claimsData || []
    
    if (enrollments.value.length > 0 && !form.enrollment_id) {
      form.enrollment_id = String(enrollments.value[0].id)
    }
  } catch (err: any) {
    console.error('Failed to load data:', err)
    errorMsg.value = 'Unable to load your insurance data.'
  } finally {
    isLoading.value = false
  }
}

async function submitNewClaim() {
  if (!form.enrollment_id || !form.claimed_amount) {
    errorMsg.value = 'Please fill in all required fields.'
    return
  }

  isSubmitting.value = true
  errorMsg.value = ''
  feedback.value = ''

  try {
    const claimData = {
      enrollment_id: Number(form.enrollment_id),
      claim_date: form.claim_date,
      claimed_amount: Number(form.claimed_amount),
      total_amount: Number(form.claimed_amount),
      description: form.description || `Insurance claim submitted on ${form.claim_date}`,
    }

    const claim: any = await submitClaim(claimData)
    
    if (claim && claim.id) {
      for (const [type, file] of Object.entries(claimFiles)) {
        if (file) {
          try {
            const docFormData = new FormData()
            docFormData.append('document', file)
            docFormData.append('document_type', type)
            await uploadClaimDocument(claim.id, docFormData)
          } catch (docErr) {
            console.warn(`Failed to upload ${type} document:`, docErr)
          }
        }
      }
    }

    feedback.value = 'Claim submitted successfully! Your claim is now pending review.'
    showCreateModal.value = false
    resetForm()
    await loadData()
  } catch (err: any) {
    console.error('Failed to submit claim:', err)
    errorMsg.value = err.response?.data?.message || 'Failed to submit claim. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

async function uploadDocument(claimId: number, type: string, file: File) {
  uploadingDocument.value = true
  try {
    const formData = new FormData()
    formData.append('document', file)
    formData.append('document_type', type)
    
    await uploadClaimDocument(claimId, formData)
    feedback.value = `${type} document uploaded successfully!`
    await loadData()
  } catch (err: any) {
    console.error('Failed to upload document:', err)
    errorMsg.value = err.response?.data?.message || 'Failed to upload document.'
  } finally {
    uploadingDocument.value = false
  }
}

function resetForm() {
  form.enrollment_id = enrollments.value.length > 0 ? String(enrollments.value[0].id) : ''
  form.claim_date = new Date().toISOString().split('T')[0]
  form.claimed_amount = ''
  form.description = ''
  Object.keys(claimFiles).forEach(key => {
    claimFiles[key as keyof typeof claimFiles] = null
  })
}

function handleFileUpload(event: Event, type: string) {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    claimFiles[type as keyof typeof claimFiles] = target.files[0]
  }
}

function openClaimDetail(claim: any) {
  selectedClaim.value = claim
  showDetailModal.value = true
}

function getStatusVariant(status: string) {
  const s = status?.toLowerCase() || ''
  if (s.includes('approved') || s.includes('paid')) return 'success'
  if (s.includes('rejected')) return 'destructive'
  if (s.includes('pending') || s.includes('review')) return 'warning'
  return 'secondary'
}

function getStatusIcon(status: string) {
  const s = status?.toLowerCase() || ''
  if (s.includes('approved') || s.includes('paid')) return CheckCircle
  if (s.includes('rejected')) return XCircle
  if (s.includes('pending') || s.includes('review')) return Clock
  return AlertCircle
}

const claimStats = computed(() => ({
  total: myClaims.value.length,
  pending: myClaims.value.filter(c => c.status === 'pending' || c.status === 'under_review').length,
  approved: myClaims.value.filter(c => c.status === 'approved').length,
  paid: myClaims.value.filter(c => c.status === 'paid').length,
  rejected: myClaims.value.filter(c => c.status === 'rejected').length,
}))

onMounted(initAndLoadData)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <Activity class="w-8 h-8 text-blue-500" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">My Insurance Claims</h2>
          <p class="text-gray-500 dark:text-gray-400">Submit and track your insurance claims.</p>
        </div>
      </div>
      <Button 
        v-if="enrollments.length > 0"
        class="bg-blue-600 hover:bg-blue-700 text-white"
        @click="showCreateModal = true"
      >
        <Plus class="w-4 h-4 mr-2" />
        New Claim
      </Button>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <!-- Stats Cards -->
    <div class="grid gap-4 md:grid-cols-4">
      <Card class="border-slate-200 dark:border-slate-700">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm text-slate-500 dark:text-slate-400">Total Claims</div>
              <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ claimStats.total }}</div>
            </div>
            <FileText class="h-8 w-8 text-slate-400" />
          </div>
        </CardContent>
      </Card>
      <Card class="border-amber-200 dark:border-amber-700">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm text-slate-500 dark:text-slate-400">Pending</div>
              <div class="text-2xl font-bold text-amber-600">{{ claimStats.pending }}</div>
            </div>
            <Clock class="h-8 w-8 text-amber-400" />
          </div>
        </CardContent>
      </Card>
      <Card class="border-emerald-200 dark:border-emerald-700">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm text-slate-500 dark:text-slate-400">Approved</div>
              <div class="text-2xl font-bold text-emerald-600">{{ claimStats.approved }}</div>
            </div>
            <CheckCircle class="h-8 w-8 text-emerald-400" />
          </div>
        </CardContent>
      </Card>
      <Card class="border-slate-200 dark:border-slate-700">
        <CardContent class="p-4">
          <div class="flex items-center justify-between">
            <div>
              <div class="text-sm text-slate-500 dark:text-slate-400">Paid</div>
              <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ claimStats.paid }}</div>
            </div>
            <CheckCircle class="h-8 w-8 text-slate-400" />
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- No Enrollments -->
    <Card v-if="enrollments.length === 0 && !isLoading" class="border-dashed border-2 border-slate-300 dark:border-slate-600">
      <CardContent class="py-12 text-center">
        <FileText class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
        <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">No Insurance Enrollment</h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          You don't have an active insurance enrollment. Contact HR to get enrolled.
        </p>
      </CardContent>
    </Card>

    <!-- Claims List -->
    <Card v-if="myClaims.length > 0 || isLoading">
      <CardHeader>
        <CardTitle>My Claims History</CardTitle>
        <CardDescription>Track the status of your submitted claims.</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoading" class="py-8 text-center text-slate-500">Loading your claims...</div>
        <div v-else-if="myClaims.length === 0" class="py-8 text-center text-slate-500">
          No claims submitted yet. Click "New Claim" to submit your first claim.
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="claim in myClaims"
            :key="claim.id"
            class="rounded-xl border border-slate-200 bg-white p-4 transition-all hover:shadow-md dark:border-slate-700 dark:bg-slate-900"
          >
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div class="flex items-start gap-4">
                <div :class="[
                  'flex h-12 w-12 items-center justify-center rounded-xl',
                  claim.status === 'approved' || claim.status === 'paid' ? 'bg-emerald-100 dark:bg-emerald-500/20' :
                  claim.status === 'rejected' ? 'bg-red-100 dark:bg-red-500/20' :
                  'bg-amber-100 dark:bg-amber-500/20'
                ]">
                  <component :is="getStatusIcon(claim.status)" :class="[
                    'h-6 w-6',
                    claim.status === 'approved' || claim.status === 'paid' ? 'text-emerald-600' :
                    claim.status === 'rejected' ? 'text-red-600' :
                    'text-amber-600'
                  ]" />
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h4 class="font-semibold text-slate-900 dark:text-white">{{ claim.claim_number || `Claim #${claim.id}` }}</h4>
                    <Badge :variant="getStatusVariant(claim.status)" class="capitalize">
                      {{ claim.status?.replace(/_/g, ' ') }}
                    </Badge>
                  </div>
                  <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                    <span>Filed: {{ new Date(claim.claim_date || claim.created_at).toLocaleDateString() }}</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">${{ Number(claim.claimed_amount || 0).toFixed(2) }}</span>
                    <span v-if="claim.approved_amount">Approved: ${{ Number(claim.approved_amount).toFixed(2) }}</span>
                  </div>
                  <p v-if="claim.description" class="mt-1 text-sm text-slate-500 dark:text-slate-400 line-clamp-1">
                    {{ claim.description }}
                  </p>
                </div>
              </div>
              <Button size="sm" variant="outline" @click="openClaimDetail(claim)">
                <Eye class="w-4 h-4 mr-1" />
                View Details
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Create Claim Modal -->
    <Dialog :open="showCreateModal" title="Submit New Insurance Claim" @close="showCreateModal = false">
      <div class="space-y-4">
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-700 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-300">
          Submit a claim and upload supporting documents (invoice, prescription, etc.) for review.
        </div>

        <div class="space-y-2">
          <Label>Insurance Enrollment *</Label>
          <select
            v-model="form.enrollment_id"
            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
          >
            <option value="">Select enrollment...</option>
            <option v-for="enrollment in enrollments" :key="enrollment.id" :value="String(enrollment.id)">
              {{ enrollment.policy?.name || `Enrollment #${enrollment.id}` }} - 
              {{ enrollment.employee_contribution ? `$${enrollment.employee_contribution}/mo` : 'Active' }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label>Claim Date *</Label>
            <Input v-model="form.claim_date" type="date" />
          </div>
          <div class="space-y-2">
            <Label>Claim Amount ($) *</Label>
            <Input v-model="form.claimed_amount" type="number" placeholder="0.00" min="0" step="0.01" />
          </div>
        </div>

        <div class="space-y-2">
          <Label>Description</Label>
          <textarea
            v-model="form.description"
            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            placeholder="Brief description of the claim..."
            rows="3"
          ></textarea>
        </div>

        <div class="space-y-3">
          <Label>Supporting Documents</Label>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <Label class="text-xs text-slate-500">Invoice / Receipt</Label>
              <Input type="file" @change="handleFileUpload($event, 'invoice')" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
            <div class="space-y-1">
              <Label class="text-xs text-slate-500">Prescription</Label>
              <Input type="file" @change="handleFileUpload($event, 'prescription')" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
            <div class="space-y-1">
              <Label class="text-xs text-slate-500">Medical Report</Label>
              <Input type="file" @change="handleFileUpload($event, 'medical_report')" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
            <div class="space-y-1">
              <Label class="text-xs text-slate-500">Other Document</Label>
              <Input type="file" @change="handleFileUpload($event, 'other')" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button variant="outline" @click="showCreateModal = false">Cancel</Button>
        <Button 
          class="bg-blue-600 hover:bg-blue-700"
          :disabled="isSubmitting || !form.enrollment_id || !form.claimed_amount"
          @click="submitNewClaim"
        >
          {{ isSubmitting ? 'Submitting...' : 'Submit Claim' }}
        </Button>
      </template>
    </Dialog>

    <!-- Claim Detail Modal -->
    <Dialog :open="showDetailModal" :title="`Claim ${selectedClaim?.claim_number || `#${selectedClaim?.id}`}`" @close="showDetailModal = false">
      <div v-if="selectedClaim" class="space-y-4">
        <div class="flex items-center justify-between">
          <Badge :variant="getStatusVariant(selectedClaim.status)" class="capitalize text-sm">
            {{ selectedClaim.status?.replace(/_/g, ' ') }}
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

        <div class="space-y-3">
          <Label>Upload Additional Documents</Label>
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <Label class="text-xs text-slate-500">Invoice</Label>
              <Input type="file" @change="(e: Event) => { const f = (e.target as HTMLInputElement).files?.[0]; if (f) uploadDocument(selectedClaim.id, 'invoice', f) }" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
            <div class="space-y-1">
              <Label class="text-xs text-slate-500">Prescription</Label>
              <Input type="file" @change="(e: Event) => { const f = (e.target as HTMLInputElement).files?.[0]; if (f) uploadDocument(selectedClaim.id, 'prescription', f) }" accept=".pdf,.jpg,.jpeg,.png" />
            </div>
          </div>
          <p class="text-xs text-slate-500">
            Documents will be sent to HR for review.
          </p>
        </div>
      </div>

      <template #footer>
        <Button variant="outline" @click="showDetailModal = false">Close</Button>
      </template>
    </Dialog>
  </div>
</template>
