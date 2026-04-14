<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Activity, Bot, Download, Plus, ShieldCheck, ShieldAlert, FileText, Eye, CheckCircle, XCircle, Wallet } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import Dialog from '@/components/ui/Dialog.vue'
import {
  approveClaim,
  detectClaimAnomalies,
  getClaimHistoryEntries,
  getClaimHistory,
  getClaims,
  getClaimDocuments,
  getEnrollments,
  markClaimAsPaid,
  rejectClaim,
  submitClaim,
  sendClaimToPayroll,
} from '@/api/laravel/insurance'
import { useAuthStore } from '@/stores/auth'
import { platformApi } from '@/api/laravel/platform'

const auth = useAuthStore()

async function initAndFetchClaims() {
  if (!auth.isInitialized) {
    await auth.initializeAuth()
  }
  await fetchClaims()
}

const items = ref<any[]>([])
const enrollments = ref<any[]>([])
const selectedClaim = ref<any | null>(null)
const claimDocuments = ref<any[]>([])
const claimHistory = ref<any[]>([])
const claimHistoryStatus = ref<'idle' | 'loaded' | 'empty' | 'unavailable'>('idle')
const anomalyResult = ref<any | null>(null)
const isLoading = ref(true)
const isCreating = ref(false)
const showImageModal = ref(false)
const selectedImageUrl = ref('')
const feedback = ref('')
const errorMsg = ref('')
const isActionLoading = ref(false)
const isLoadingDocuments = ref(false)
const searchQuery = ref('')
const showRejectModal = ref(false)
const rejectReason = ref('')

const canManageClaims = computed(() => ['admin', 'rh_manager'].includes(auth.user?.role ?? ''))

const form = reactive({
  enrollment_id: '',
  claim_date: '',
  claimed_amount: '',
})

const files = reactive<{ [key: string]: File | null }>({
  invoice: null,
  prescription: null
})

function handleFileUpload(event: any, type: string) {
  const file = event.target.files[0]
  if (file) {
    files[type] = file
  }
}

const columns = [
  { key: 'claim_number', label: 'Claim No.' },
  { key: 'employee_name', label: 'Employee' },
  { key: 'date_filed', label: 'Date Filed' },
  { key: 'amount_requested', label: 'Amount' },
  { key: 'status', label: 'Status' }
]

const getEnrollmentEmployeeId = (enrollment: any) =>
  String(enrollment?.employee_id ?? enrollment?.employee?.id ?? enrollment?.id ?? '')

const getEnrollmentEmployeeName = (enrollment: any) =>
  enrollment?.employee?.name ||
  enrollment?.employee?.full_name ||
  `Enrollment #${enrollment?.id ?? 'Unknown'}`

const getEnrollmentPolicyName = (enrollment: any) =>
  enrollment?.policy?.policy_name ||
  enrollment?.policy?.name ||
  enrollment?.policy_name ||
  'No policy'

const getEnrollmentStatus = (enrollment: any) =>
  String(enrollment?.status || 'active').replace(/_/g, ' ')

const getEnrollmentOptionLabel = (enrollment: any) =>
  `${getEnrollmentEmployeeName(enrollment)} · ${getEnrollmentPolicyName(enrollment)} · ${getEnrollmentStatus(enrollment)}`

const shouldPreferEnrollment = (candidate: any, current: any) => {
  const candidateActive = String(candidate?.status || '').toLowerCase() === 'active'
  const currentActive = String(current?.status || '').toLowerCase() === 'active'
  if (candidateActive !== currentActive) return candidateActive
  return Number(candidate?.id || 0) > Number(current?.id || 0)
}

const enrollmentOptions = computed(() => {
  const grouped = new Map<string, any>()

  for (const enrollment of enrollments.value) {
    const key = getEnrollmentEmployeeId(enrollment)
    if (!key) continue
    const existing = grouped.get(key)
    if (!existing || shouldPreferEnrollment(enrollment, existing)) {
      grouped.set(key, enrollment)
    }
  }

  return Array.from(grouped.values()).sort((a, b) =>
    getEnrollmentEmployeeName(a).localeCompare(getEnrollmentEmployeeName(b))
  )
})

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return items.value

  return items.value.filter((item) =>
    [item.claim_number, item.employee_name, item.date_filed, item.amount_requested, item.status]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

const fetchClaims = async () => {
  isLoading.value = true
  errorMsg.value = ''
  try {
    const claimData = await getClaims()
    
    try {
      const enrollmentData = await getEnrollments()
      enrollments.value = enrollmentData || []
    } catch (enrollErr) {
      console.warn('Could not load enrollments:', enrollErr)
      enrollments.value = []
    }

    items.value = (claimData || []).map((item: any) => ({
      ...item,
      employee_name:
        (item.enrollment?.employee?.name ||
        item.enrollment?.employee?.full_name ||
        (item.enrollment?.employee?.first_name + ' ' + item.enrollment?.employee?.last_name) ||
        item.employee?.name ||
        item.employee?.full_name ||
        item.employee_name) ?? 'Unknown Employee',
      date_filed: item.claim_date || item.date_filed || 'Not set',
      amount_requested: `$${Number(item.claimed_amount || item.total_amount || 0).toLocaleString()}`,
      status: String(item.status || 'pending').replace(/_/g, ' '),
    }))
  } catch (err: any) {
    console.error('Failed to fetch claims', err)
    if (err.response?.status === 401) {
      errorMsg.value = 'Session expired. Please log in again.'
    } else if (err.response?.status === 403) {
      errorMsg.value = 'You do not have permission to view claims.'
    } else if (err.code === 'ERR_NETWORK' || err.message?.includes('Network')) {
      errorMsg.value = 'Unable to connect to server. Please check your connection.'
    } else {
      errorMsg.value = err.response?.data?.message || 'Unable to load insurance claims right now.'
    }
  } finally {
    isLoading.value = false
  }
}

const getStatusVariant = (status: string) => {
  const normalized = status.toLowerCase()
  if (normalized.includes('paid')) return 'success'
  if (normalized.includes('approved')) return 'default'
  if (normalized === 'sent_to_provider' || normalized.includes('provider')) return 'warning'
  if (normalized.includes('pending') || normalized.includes('review')) return 'secondary'
  if (normalized.includes('rejected')) return 'destructive'
  return 'secondary'
}

const escapeCsv = (value: unknown) => {
  const text = String(value ?? '')
  return /[",\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text
}

const downloadCsv = (name: string, rows: Record<string, unknown>[]) => {
  const headers = Array.from(new Set(rows.flatMap((row) => Object.keys(row))))
  const csv = [headers.join(','), ...rows.map((row) => headers.map((header) => escapeCsv(row[header])).join(','))].join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${name}.csv`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const downloadJson = (name: string, rows: unknown) => {
  const blob = new Blob([JSON.stringify(rows, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${name}.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

async function openClaim(item: any) {
  selectedClaim.value = item
  anomalyResult.value = null
  claimHistoryStatus.value = 'idle'
  claimDocuments.value = []
  rejectReason.value = ''

  isLoadingDocuments.value = true
  try {
    const [historyData, documentsData] = await Promise.all([
      getClaimHistory(item.id).catch(() => []),
      getClaimDocuments(item.id).catch(() => [])
    ])
    
    claimHistory.value = Array.isArray(historyData) ? historyData : []
    claimHistoryStatus.value = claimHistory.value.length ? 'loaded' : 'empty'
    
    claimDocuments.value = Array.isArray(documentsData) ? documentsData : []
  } catch (error) {
    try {
      const historyEntries = await getClaimHistoryEntries()
      claimHistory.value = (Array.isArray(historyEntries) ? historyEntries : []).filter((entry: any) =>
        String(entry.claim_id ?? entry.claim?.id ?? entry.related_claim_id ?? '') === String(item.id)
      )
      claimHistoryStatus.value = claimHistory.value.length ? 'loaded' : 'empty'
    } catch (fallbackError) {
      console.warn('Claim history endpoint unavailable, showing empty history.', fallbackError)
      claimHistory.value = []
      claimHistoryStatus.value = 'unavailable'
    }
  } finally {
    isLoadingDocuments.value = false
  }
}

async function createClaim() {
  errorMsg.value = ''
  feedback.value = ''

  try {
    const formData = new FormData()
    formData.append('enrollment_id', String(form.enrollment_id))
    formData.append('claim_date', form.claim_date)
    formData.append('claimed_amount', String(form.claimed_amount))
    formData.append('total_amount', String(form.claimed_amount))

    if (files.invoice) formData.append('documents[invoice]', files.invoice)
    if (files.prescription) formData.append('documents[prescription]', files.prescription)

    await submitClaim(formData)
    feedback.value = 'Claim filed successfully with supporting documents.'
    isCreating.value = false
    form.enrollment_id = ''
    form.claim_date = ''
    form.claimed_amount = ''
    files.invoice = null
    files.prescription = null
    await fetchClaims()
  } catch (error) {
    console.error('Unable to file claim', error)
    errorMsg.value = 'Unable to file the claim. Please ensure all fields are correct and files are valid.'
  }
}

async function runAnomalyCheck() {
  if (!selectedClaim.value) return

  isActionLoading.value = true
  try {
    anomalyResult.value = await detectClaimAnomalies(selectedClaim.value.id)
  } catch (error) {
    console.error('Unable to detect anomalies', error)
    errorMsg.value = 'Unable to run anomaly detection right now.'
  } finally {
    isActionLoading.value = false
  }
}

async function applyClaimAction(action: 'approve' | 'reject' | 'paid' | 'send_to_provider' | 'send_to_payroll') {
  if (!selectedClaim.value) return

  if (action === 'reject' && !rejectReason.value.trim()) {
    errorMsg.value = 'Please provide a reason for rejection.'
    return
  }

  isActionLoading.value = true
  errorMsg.value = ''
  const currentId = selectedClaim.value.id

  try {
    if (action === 'approve') {
      await approveClaim(currentId)
      feedback.value = `Claim ${selectedClaim.value.claim_number} approved successfully!`
    }

    if (action === 'send_to_provider') {
      await platformApi.sendClaimToProvider(currentId)
      feedback.value = `Claim ${selectedClaim.value.claim_number} sent to insurance provider.`
    }

    if (action === 'send_to_payroll') {
      const result: any = await sendClaimToPayroll(currentId)
      feedback.value = `Claim ${selectedClaim.value.claim_number} sent to payroll! Reimbursement: $${result.reimbursement_amount} (Company kept: $${result.company_discount})`
    }

    if (action === 'reject') {
      await rejectClaim(currentId, { reason: rejectReason.value })
      feedback.value = `Claim ${selectedClaim.value.claim_number} rejected.`
      showRejectModal.value = false
      rejectReason.value = ''
    }

    if (action === 'paid') {
      await markClaimAsPaid(currentId)
      feedback.value = `Claim ${selectedClaim.value.claim_number} marked as paid.`
    }

    await fetchClaims()
    const refreshedClaim = items.value.find((item) => item.id === currentId)
    if (refreshedClaim) {
      await openClaim(refreshedClaim)
    }
  } catch (error) {
    console.error('Unable to update claim', error)
    errorMsg.value = 'Unable to update the claim right now.'
  } finally {
    isActionLoading.value = false
  }
}

function getDocumentIcon(type: string) {
  const t = type?.toLowerCase() || ''
  if (t.includes('invoice') || t.includes('facture')) return FileText
  if (t.includes('prescription') || t.includes('ordonnance')) return FileText
  return FileText
}

function getDocumentTypeLabel(type: string) {
  const t = type?.toLowerCase() || ''
  if (t.includes('invoice') || t.includes('facture')) return 'Invoice / Facture'
  if (t.includes('prescription') || t.includes('ordonnance')) return 'Prescription / Ordonnance'
  if (t.includes('medical')) return 'Medical Report'
  return type || 'Document'
}

function openDocument(doc: any) {
  const url = doc.url || doc.file_url || doc.download_url || '/storage/' + doc.file_path
  selectedImageUrl.value = url
  showImageModal.value = true
}

function downloadDocument(doc: any) {
  const url = doc.url || doc.file_url || doc.download_url || '/storage/' + doc.file_path
  const filename = doc.filename || doc.file_name || doc.document_name || 'document'
  
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.target = '_blank'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

onMounted(initAndFetchClaims)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <Activity class="w-8 h-8 text-rose-500" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Insurance Claims</h2>
          <p class="text-gray-500 dark:text-gray-400">Review submitted claims, inspect claim history, and trigger AI anomaly checks.</p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button
          variant="outline"
          class="border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900"
          :disabled="isLoading"
          @click="downloadJson('insurance-claims', items)"
        >
          <Download class="w-4 h-4 mr-2" /> JSON
        </Button>
        <Button
          variant="outline"
          class="border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900"
          :disabled="isLoading"
          @click="downloadCsv('insurance-claims', items)"
        >
          <Download class="w-4 h-4 mr-2" /> CSV
        </Button>
        <Button
          v-if="selectedClaim"
          variant="outline"
          class="border-indigo-200 text-indigo-600 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-400"
          :disabled="isActionLoading"
          @click="runAnomalyCheck"
        >
          <Bot class="w-4 h-4 mr-2" /> {{ isActionLoading ? 'Checking...' : 'Detect Anomalies (AI)' }}
        </Button>
        <Button class="bg-blue-600" @click="isCreating = !isCreating"><Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'File Claim' }}</Button>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <Card v-if="isCreating">
      <CardHeader>
        <CardTitle>File Claim</CardTitle>
        <CardDescription>Create a claim record directly from the Laravel insurance claim endpoint.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-3">
        <div class="space-y-2">
          <Label>Enrollment</Label>
          <select v-model="form.enrollment_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">Select enrollment</option>
            <option v-for="enrollment in enrollmentOptions" :key="enrollment.id" :value="String(enrollment.id)">
              {{ getEnrollmentOptionLabel(enrollment) }}
            </option>
          </select>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing one preferred insurance enrollment per employee to keep the list clean.
          </p>
        </div>
        <div class="space-y-2">
          <Label>Claim Date</Label>
          <Input v-model="form.claim_date" type="date" />
        </div>
        <div class="space-y-2">
          <Label>Claim Amount</Label>
          <Input v-model="form.claimed_amount" type="number" placeholder="250" />
        </div>
        <div class="md:col-span-3 space-y-2">
          <Label>Supporting Documents (Factures, Ordonnances)</Label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
             <div class="rounded-lg border border-dashed border-slate-300 p-4 text-center">
               <p class="text-sm text-slate-500 mb-2">Upload Invoice (Facture)</p>
               <Input type="file" @change="handleFileUpload($event, 'invoice')" />
             </div>
             <div class="rounded-lg border border-dashed border-slate-300 p-4 text-center">
               <p class="text-sm text-slate-500 mb-2">Upload Prescription (Ordonnance)</p>
               <Input type="file" @change="handleFileUpload($event, 'prescription')" />
             </div>
          </div>
        </div>
        <div class="md:col-span-3 flex justify-end">
          <Button @click="createClaim">Submit Claim Request</Button>
        </div>
      </CardContent>
    </Card>

    <div class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <Card>
        <CardContent class="pt-6">
          <DataTable
            :columns="columns"
            :data="filteredItems"
            :loading="isLoading"
            searchPlaceholder="Search claims by number, employee, or status..."
            @search="searchQuery = $event"
            @row-click="openClaim"
          >
            <template #cell(status)="{ value }">
              <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
            </template>
            <template #actions="{ item }">
              <Button variant="outline" size="sm" @click.stop="openClaim(item)">Review</Button>
            </template>
          </DataTable>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Claim Detail</CardTitle>
          <CardDescription>History comes from the Laravel claim history endpoint and anomaly checks from Django.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!selectedClaim" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            Select a claim to inspect its history and AI findings.
          </div>
          <div v-else class="space-y-5">
            <!-- Claim Header -->
            <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ selectedClaim.claim_number }}</div>
                  <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ selectedClaim.employee_name }}</div>
                </div>
                <Badge :variant="getStatusVariant(selectedClaim.status)">{{ selectedClaim.status }}</Badge>
              </div>
              <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                <div>
                  <div class="text-slate-500">Requested Amount</div>
                  <div class="font-semibold text-emerald-600">{{ selectedClaim.amount_requested }}</div>
                </div>
                <div v-if="selectedClaim.approved_amount">
                  <div class="text-slate-500">Approved Amount</div>
                  <div class="font-semibold text-emerald-600">${{ Number(selectedClaim.approved_amount).toFixed(2) }}</div>
                </div>
                <div v-if="selectedClaim.reimbursement_amount">
                  <div class="text-slate-500">Employee Reimbursement (90%)</div>
                  <div class="font-semibold text-blue-600">${{ Number(selectedClaim.reimbursement_amount).toFixed(2) }}</div>
                </div>
                <div>
                  <div class="text-slate-500">Filed On</div>
                  <div class="font-medium">{{ new Date(selectedClaim.claim_date || selectedClaim.created_at).toLocaleDateString() }}</div>
                </div>
              </div>
              <div v-if="selectedClaim.sent_to_payroll_at" class="mt-2">
                <Badge variant="success" class="text-xs">Sent to Payroll</Badge>
              </div>
              <div v-if="selectedClaim.description" class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                {{ selectedClaim.description }}
              </div>
            </div>

            <!-- Documents Section -->
            <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
              <div class="mb-3 flex items-center justify-between">
                <div class="text-sm font-semibold text-slate-900 dark:text-white">Uploaded Documents</div>
                <Badge v-if="isLoadingDocuments" variant="secondary" class="text-xs">Loading...</Badge>
                <Badge v-else variant="outline" class="text-xs">{{ claimDocuments.length }} file(s)</Badge>
              </div>
              
              <div v-if="isLoadingDocuments" class="py-4 text-center text-sm text-slate-500">
                Loading documents...
              </div>
              
              <div v-else-if="claimDocuments.length === 0" class="rounded-xl border border-dashed border-slate-300 p-6 text-center">
                <FileText class="mx-auto h-8 w-8 text-slate-300" />
                <p class="mt-2 text-sm text-slate-500">No documents uploaded for this claim.</p>
              </div>
              
              <div v-else class="space-y-3">
                <div 
                  v-for="doc in claimDocuments" 
                  :key="doc.id" 
                  class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900"
                >
                  <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/20">
                      <component :is="getDocumentIcon(doc.document_type || doc.type)" class="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                      <div class="text-sm font-medium text-slate-900 dark:text-white">{{ getDocumentTypeLabel(doc.document_type || doc.type) }}</div>
                      <div class="text-xs text-slate-500">{{ doc.filename || doc.file_name || 'Document' }}</div>
                    </div>
                  </div>
                    <div class="flex items-center gap-2">
                    <Badge v-if="doc.confidence_score" :variant="doc.confidence_score > 0.8 ? 'success' : doc.confidence_score > 0.5 ? 'warning' : 'destructive'" class="text-xs">
                      {{ Math.round(doc.confidence_score * 100) }}% match
                    </Badge>
                    <Button variant="ghost" size="sm" @click="openDocument(doc)">
                      <Eye class="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm" @click="downloadDocument(doc)">
                      <Download class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Missing Documents Alert -->
            <div v-if="selectedClaim.missing_documents?.length" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/30">
              <div class="flex items-start gap-3">
                <ShieldAlert class="h-5 w-5 text-rose-600 mt-0.5" />
                <div>
                  <h4 class="font-bold text-rose-800 dark:text-rose-300">Missing Documents</h4>
                  <ul class="mt-2 list-inside list-disc text-sm text-rose-700 dark:text-rose-400">
                    <li v-for="doc in selectedClaim.missing_documents" :key="doc">{{ doc }}</li>
                  </ul>
                </div>
              </div>
            </div>

            <!-- Action Buttons for Admin -->
            <div v-if="canManageClaims" class="space-y-3">
              <div class="text-sm font-semibold text-slate-900 dark:text-white">Actions</div>
              <div class="flex flex-wrap gap-2">
                <Button 
                  v-if="selectedClaim.status === 'pending'" 
                  class="bg-emerald-600 hover:bg-emerald-700" 
                  :disabled="isActionLoading" 
                  @click="applyClaimAction('approve')"
                >
                  <CheckCircle class="mr-2 h-4 w-4" /> Approve Claim
                </Button>
                <Button 
                  v-if="selectedClaim.status === 'pending'" 
                  variant="destructive" 
                  :disabled="isActionLoading" 
                  @click="showRejectModal = true"
                >
                  <XCircle class="mr-2 h-4 w-4" /> Reject Claim
                </Button>
                <Button 
                  v-if="selectedClaim.status === 'approved'" 
                  class="bg-blue-600 hover:bg-blue-700 text-white" 
                  :disabled="isActionLoading" 
                  @click="applyClaimAction('send_to_provider')"
                >
                  <ShieldCheck class="mr-2 h-4 w-4" /> Send to Provider
                </Button>
                <Button 
                  v-if="selectedClaim.status === 'approved' && !selectedClaim.sent_to_payroll_at" 
                  class="bg-emerald-600 hover:bg-emerald-700 text-white" 
                  :disabled="isActionLoading" 
                  @click="applyClaimAction('send_to_payroll')"
                >
                  <Wallet class="mr-2 h-4 w-4" /> Send to Payroll
                </Button>
                <Button 
                  v-if="selectedClaim.status === 'sent_to_provider'" 
                  class="bg-violet-600 hover:bg-violet-700 text-white" 
                  :disabled="isActionLoading" 
                  @click="applyClaimAction('paid')"
                >
                  <CheckCircle class="mr-2 h-4 w-4" /> Mark as Paid
                </Button>
              </div>
            </div>

            <div class="space-y-3">
              <div class="text-sm font-semibold text-slate-900 dark:text-white">History</div>
              <div v-for="entry in claimHistory" :key="entry.id" class="rounded-2xl border border-slate-200 p-3 dark:border-slate-800">
                <div class="text-sm font-medium capitalize text-slate-900 dark:text-white">{{ entry.status || 'Updated' }}</div>
                <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ entry.changed_at || entry.created_at || 'No date' }}</div>
                <div v-if="entry.remarks" class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ entry.remarks }}</div>
              </div>
              <div v-if="!claimHistory.length" class="rounded-2xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">
                No history entries are available for this claim yet.
              </div>
            </div>

            <!-- Anomaly Detection Result -->
            <div v-if="anomalyResult" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/30">
              <div class="mb-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300">AI Anomaly Detection</div>
              <pre class="whitespace-pre-wrap text-xs text-indigo-700 dark:text-indigo-200">{{ JSON.stringify(anomalyResult, null, 2) }}</pre>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Reject Claim Modal -->
    <Dialog :open="showRejectModal" title="Reject Claim" @close="showRejectModal = false">
      <div class="space-y-4">
        <div class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
          You are about to reject claim <strong>{{ selectedClaim?.claim_number }}</strong>. Please provide a reason.
        </div>
        <div class="space-y-2">
          <Label>Rejection Reason *</Label>
          <textarea 
            v-model="rejectReason" 
            class="flex min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm" 
            placeholder="Explain why this claim is being rejected..."
          ></textarea>
        </div>
      </div>
      <template #footer>
        <Button variant="outline" @click="showRejectModal = false">Cancel</Button>
        <Button 
          variant="destructive" 
          :disabled="isActionLoading || !rejectReason.trim()" 
          @click="applyClaimAction('reject')"
        >
          {{ isActionLoading ? 'Rejecting...' : 'Confirm Rejection' }}
        </Button>
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
  </div>
</template>
