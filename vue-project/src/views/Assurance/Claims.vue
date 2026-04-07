<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Activity, Bot, Plus, ShieldCheck } from 'lucide-vue-next'
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
import {
  approveClaim,
  detectClaimAnomalies,
  getClaimHistory,
  getClaims,
  getEnrollments,
  markClaimAsPaid,
  rejectClaim,
  submitClaim,
} from '@/api/laravel/insurance'
import { useAuthStore } from '@/stores/auth'

const items = ref<any[]>([])
const enrollments = ref<any[]>([])
const selectedClaim = ref<any | null>(null)
const claimHistory = ref<any[]>([])
const anomalyResult = ref<any | null>(null)
const isLoading = ref(true)
const isCreating = ref(false)
const feedback = ref('')
const errorMsg = ref('')
const isActionLoading = ref(false)
const searchQuery = ref('')

const auth = useAuthStore()
const canManageClaims = computed(() => ['admin', 'rh_manager'].includes(auth.user?.role ?? ''))

const form = reactive({
  enrollment_id: '',
  claim_date: '',
  claimed_amount: '',
})

const columns = [
  { key: 'claim_number', label: 'Claim No.' },
  { key: 'employee_name', label: 'Employee' },
  { key: 'date_filed', label: 'Date Filed' },
  { key: 'amount_requested', label: 'Amount' },
  { key: 'status', label: 'Status' }
]

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
  try {
    const [claimData, enrollmentData] = await Promise.all([
      getClaims(),
      getEnrollments(),
    ])

    enrollments.value = enrollmentData
    items.value = claimData.map((item: any) => ({
      ...item,
      employee_name:
        item.enrollment?.employee?.name ||
        item.enrollment?.employee?.full_name ||
        `Enrollment #${item.enrollment_id}`,
      date_filed: item.claim_date || item.date_filed || 'Not set',
      amount_requested: `$${Number(item.claimed_amount || item.total_amount || 0).toLocaleString()}`,
      status: String(item.status || 'pending').replace(/_/g, ' '),
    }))
  } catch (err) {
    console.error('Failed to fetch claims', err)
    errorMsg.value = 'Unable to load insurance claims right now.'
  } finally {
    isLoading.value = false
  }
}

const getStatusVariant = (status: string) => {
  const normalized = status.toLowerCase()
  if (normalized.includes('approved') || normalized.includes('paid')) return 'success'
  if (normalized.includes('pending') || normalized.includes('review')) return 'warning'
  if (normalized.includes('rejected')) return 'destructive'
  return 'secondary'
}

async function openClaim(item: any) {
  selectedClaim.value = item
  anomalyResult.value = null

  try {
    claimHistory.value = await getClaimHistory(item.id)
  } catch (error) {
    console.error('Unable to load claim history', error)
    claimHistory.value = []
  }
}

async function createClaim() {
  errorMsg.value = ''
  feedback.value = ''

  try {
    await submitClaim({
      enrollment_id: Number(form.enrollment_id),
      claim_date: form.claim_date,
      claimed_amount: Number(form.claimed_amount),
      total_amount: Number(form.claimed_amount),
    })
    feedback.value = 'Claim filed successfully.'
    isCreating.value = false
    form.enrollment_id = ''
    form.claim_date = ''
    form.claimed_amount = ''
    await fetchClaims()
  } catch (error) {
    console.error('Unable to file claim', error)
    errorMsg.value = 'Unable to file the claim right now.'
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

async function applyClaimAction(action: 'approve' | 'reject' | 'paid') {
  if (!selectedClaim.value) return

  isActionLoading.value = true
  errorMsg.value = ''
  const currentId = selectedClaim.value.id

  try {
    if (action === 'approve') {
      await approveClaim(currentId)
      feedback.value = `Claim ${selectedClaim.value.claim_number} approved.`
    }

    if (action === 'reject') {
      await rejectClaim(currentId, { reason: 'Rejected from the claims review panel.' })
      feedback.value = `Claim ${selectedClaim.value.claim_number} rejected.`
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

onMounted(fetchClaims)
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
            <option v-for="enrollment in enrollments" :key="enrollment.id" :value="String(enrollment.id)">
              {{ enrollment.employee?.name || enrollment.employee?.full_name || `Enrollment #${enrollment.id}` }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Claim Date</Label>
          <Input v-model="form.claim_date" type="date" />
        </div>
        <div class="space-y-2">
          <Label>Claim Amount</Label>
          <Input v-model="form.claimed_amount" type="number" placeholder="250" />
        </div>
        <div class="md:col-span-3 flex justify-end">
          <Button @click="createClaim">Save Claim</Button>
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
            <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ selectedClaim.claim_number }}</div>
                  <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ selectedClaim.employee_name }}</div>
                </div>
                <Badge :variant="getStatusVariant(selectedClaim.status)">{{ selectedClaim.status }}</Badge>
              </div>
              <div class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                Requested amount: {{ selectedClaim.amount_requested }}
              </div>
            </div>

            <div v-if="canManageClaims" class="flex flex-wrap gap-2">
              <Button class="bg-emerald-600 hover:bg-emerald-700" :disabled="isActionLoading" @click="applyClaimAction('approve')">
                <ShieldCheck class="mr-2 h-4 w-4" /> Approve
              </Button>
              <Button class="bg-red-600 hover:bg-red-700" :disabled="isActionLoading" @click="applyClaimAction('reject')">
                Reject
              </Button>
              <Button class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" :disabled="isActionLoading" @click="applyClaimAction('paid')">
                Mark as Paid
              </Button>
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

            <div v-if="anomalyResult" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900/60 dark:bg-indigo-950/30">
              <div class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">AI anomaly result</div>
              <pre class="mt-3 whitespace-pre-wrap text-xs text-indigo-700 dark:text-indigo-200">{{ JSON.stringify(anomalyResult, null, 2) }}</pre>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
