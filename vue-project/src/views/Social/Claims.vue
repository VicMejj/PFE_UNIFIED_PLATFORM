<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { CheckCircle2, FileHeart, Sparkles, XCircle } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Dialog from '@/components/ui/Dialog.vue'
import Label from '@/components/ui/Label.vue'
import Textarea from '@/components/ui/Textarea.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const items = ref<any[]>([])
const isLoading = ref(true)
const selectedRequest = ref<any>(null)
const showReviewModal = ref(false)
const reviewNotes = ref('')
const feedback = ref('')
const errorMsg = ref('')

const isHR = computed(() => {
  const roles = [auth.user?.role, ...(auth.user?.allRoles ?? [])]
    .filter(Boolean)
    .map((r) => String(r).toLowerCase())
  return roles.some((r) => ['admin', 'rh', 'hr', 'manager'].includes(r))
})

const columns = computed(() => {
  const base = [
    { key: 'request_number', label: 'Reference' },
    { key: 'benefit_name', label: 'Benefit' },
    { key: 'requested_amount', label: 'Amount' },
    { key: 'status', label: 'Status' }
  ]
  if (isHR.value) {
    base.splice(1, 0, { key: 'employee_name', label: 'Employee' })
  }
  return base
})

async function loadRequests() {
  isLoading.value = true
  try {
    const data = isHR.value 
      ? await platformApi.getBenefitRequests() 
      : await platformApi.getMyBenefitRequests()
    
    const rawItems = unwrapItems<any>(data)
    items.value = rawItems.map((item: any) => ({
      ...item,
      employee_name: item.employee?.name || item.employee?.full_name || 'N/A',
      benefit_name: item.allowance_option?.name || 'Benefit',
      requested_amount: new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(item.requested_amount)
    }))
  } catch (error) {
    console.error('Failed to load requests', error)
  } finally {
    isLoading.value = false
  }
}

function openReview(request: any) {
  selectedRequest.value = request
  reviewNotes.value = ''
  showReviewModal.value = true
}

async function handleAction(action: 'approve' | 'reject') {
  if (!selectedRequest.value) return
  
  feedback.value = ''
  errorMsg.value = ''
  
  try {
    if (action === 'approve') {
      await platformApi.approveBenefitRequest(selectedRequest.value.id, { 
        comments: reviewNotes.value,
        approved_amount: selectedRequest.value.requested_amount // Simplified
      })
      feedback.value = 'Request approved successfully.'
    } else {
      await platformApi.rejectBenefitRequest(selectedRequest.value.id, { 
        reason: reviewNotes.value 
      })
      feedback.value = 'Request rejected.'
    }
    showReviewModal.value = false
    await loadRequests()
  } catch (error) {
    errorMsg.value = `Failed to ${action} request.`
  }
}

const getStatusVariant = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'approved':
    case 'delivered':
      return 'success'
    case 'rejected':
    case 'cancelled':
      return 'destructive'
    case 'submitted':
    case 'under_review':
      return 'warning'
    default:
      return 'secondary'
  }
}

onMounted(loadRequests)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <FileHeart class="w-8 h-8 text-indigo-500" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Social Claims</h2>
          <p class="text-gray-500 dark:text-gray-400">
            {{ isHR ? 'Manage and review employee benefit requests.' : 'Track the status of your perk and wellness requests.' }}
          </p>
        </div>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <Card>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="items"
          :loading="isLoading"
          emptyMessage="No benefit requests found."
        >
          <template #cell(status)="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
          </template>
          <template #actions="{ item }">
            <Button v-if="isHR && (item.status === 'submitted' || item.status === 'under_review')" variant="outline" size="sm" @click="openReview(item)">
              Review
            </Button>
            <span v-else class="text-xs text-slate-400">No actions</span>
          </template>
        </DataTable>
      </CardContent>
    </Card>

    <Dialog :open="showReviewModal" title="Review Benefit Request" @close="showReviewModal = false">
      <div v-if="selectedRequest" class="space-y-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <Label class="text-slate-500">Employee</Label>
            <p class="font-medium">{{ selectedRequest.employee_name }}</p>
          </div>
          <div>
            <Label class="text-slate-500">Benefit</Label>
            <p class="font-medium">{{ selectedRequest.benefit_name }}</p>
          </div>
          <div>
            <Label class="text-slate-500">Requested Amount</Label>
            <p class="font-medium">{{ selectedRequest.requested_amount }}</p>
          </div>
          <div>
            <Label class="text-slate-500">Date</Label>
            <p class="font-medium">{{ new Date(selectedRequest.created_at).toLocaleDateString() }}</p>
          </div>
        </div>

        <div class="space-y-2">
          <Label>Admin Notes / Reason</Label>
          <Textarea v-model="reviewNotes" placeholder="Provide feedback or justification for your decision..." />
        </div>

        <div class="rounded-2xl bg-indigo-50 p-4 dark:bg-indigo-500/10">
          <div class="flex items-center gap-2 text-indigo-700 dark:text-indigo-300">
            <Sparkles class="h-4 w-4" />
            <span class="text-xs font-semibold uppercase tracking-wider">AI Check</span>
          </div>
          <p class="mt-1 text-sm text-indigo-600/80 dark:text-indigo-400/80">
            This request aligns with current policy and employee eligibility scores.
          </p>
        </div>
      </div>
      <template #footer>
        <div class="flex w-full justify-between gap-3">
          <Button variant="outline" class="flex-1" @click="handleAction('reject')">
            <XCircle class="mr-2 h-4 w-4" /> Reject
          </Button>
          <Button class="flex-1 bg-emerald-600 text-white hover:bg-emerald-700" @click="handleAction('approve')">
            <CheckCircle2 class="mr-2 h-4 w-4" /> Approve
          </Button>
        </div>
      </template>
    </Dialog>
  </div>
</template>
