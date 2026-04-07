<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'

const router = useRouter()
const items = ref<any[]>([])
const employees = ref<any[]>([])
const contractTypes = ref<any[]>([])
const selectedContract = ref<any | null>(null)
const contractAttachments = ref<any[]>([])
const contractComments = ref<any[]>([])
const contractNotes = ref<any[]>([])
const auditLogs = ref<any[]>([])
const isLoading = ref(true)
const isCreating = ref(false)
const isDetailLoading = ref(false)
const isSavingComment = ref(false)
const isSavingNote = ref(false)
const isUploadingAttachment = ref(false)
const isAssigning = ref(false)
const isDeleting = ref(false)
const feedback = ref('')
const errorMessage = ref('')

const form = reactive({
  employee_id: '',
  contract_type_id: '',
  contract_name: '',
  start_date: '',
  end_date: '',
  status: 'draft',
  notes: ''
})

const assignmentForm = reactive({
  signing_deadline: ''
})

const commentForm = ref('')
const noteForm = ref('')
const attachmentFile = ref<File | null>(null)
const attachmentInput = ref<HTMLInputElement | null>(null)

const columns = [
  { key: 'employee_id', label: 'Employee ID' },
  { key: 'contract_name', label: 'Contract' },
  { key: 'start_date', label: 'Start Date' },
  { key: 'end_date', label: 'End Date' },
  { key: 'status', label: 'Status' }
]

const hasEmployees = computed(() => employees.value.length > 0)

const getEmployeeDisplayName = (employee: any) => {
  const fullName = [employee?.first_name, employee?.last_name].filter(Boolean).join(' ').trim()

  return employee?.name
    || employee?.full_name
    || fullName
    || employee?.email
    || employee?.employee_id
    || `Employee #${employee?.id ?? 'Unknown'}`
}

const fetchContracts = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const [contractData, employeeData, contractTypeData] = await Promise.all([
      platformApi.getContracts(),
      platformApi.getEmployees(),
      platformApi.getContractTypes()
    ])
    items.value = unwrapItems<any>(contractData)
    employees.value = unwrapItems<any>(employeeData).map((employee: any) => ({
      ...employee,
      display_name: getEmployeeDisplayName(employee)
    }))
    contractTypes.value = unwrapItems<any>(contractTypeData)
  } catch (err) {
    console.error('Failed to fetch contracts', err)
    errorMessage.value = 'Unable to load contracts.'
  } finally {
    isLoading.value = false
  }
}

const getStatusVariant = (status: string) => {
  switch (status?.toLowerCase()) {
    case 'signed': return 'success'
    case 'active': return 'success'
    case 'draft': return 'secondary'
    case 'viewed': return 'default'
    case 'expired': return 'destructive'
    case 'rejected': return 'destructive'
    case 'pending': return 'warning'
    default: return 'secondary'
  }
}

async function createContract() {
  isCreating.value = true
  errorMessage.value = ''

  try {
    await platformApi.createContract({
      employee_id: Number(form.employee_id),
      contract_type_id: form.contract_type_id ? Number(form.contract_type_id) : null,
      contract_name: form.contract_name,
      start_date: form.start_date,
      end_date: form.end_date,
      status: form.status,
      notes: form.notes
    })
    feedback.value = 'Contract created successfully.'
    form.employee_id = ''
    form.contract_type_id = ''
    form.contract_name = ''
    form.start_date = ''
    form.end_date = ''
    form.status = 'draft'
    form.notes = ''
    await fetchContracts()
  } catch (err) {
    console.error('Failed to create contract', err)
    errorMessage.value = 'Unable to create contract.'
  } finally {
    isCreating.value = false
  }
}

const getContractTypeName = (typeId: string | number | undefined) => {
  if (!typeId) return 'N/A'
  const type = contractTypes.value.find((t: any) => t.id === typeId || t.id === Number(typeId))
  return type?.name || `Type ${typeId}`
}

const handleAttachmentChange = (event: Event) => {
  const target = event.target as HTMLInputElement | null
  attachmentFile.value = target?.files?.[0] ?? null
}

const loadContractDetails = async (contractId: string | number) => {
  if (!contractId) {
    return
  }

  selectedContract.value = null
  contractAttachments.value = []
  contractComments.value = []
  contractNotes.value = []
  auditLogs.value = []
  isDetailLoading.value = true
  errorMessage.value = ''

  try {
    const [contractResponse, attachmentsResponse, commentsResponse, notesResponse, auditResponse] = await Promise.all<any>([
      platformApi.getContract(Number(contractId)),
      platformApi.getContractAttachments(),
      platformApi.getContractComments(),
      platformApi.getContractNotes(),
      platformApi.getContractAudit(Number(contractId))
    ])

    selectedContract.value = contractResponse ?? contractResponse?.data ?? null
    contractAttachments.value = (attachmentsResponse || []).filter((item: any) => item.contract_id === selectedContract.value?.id || item.contractId === selectedContract.value?.id)
    contractComments.value = (commentsResponse || []).filter((item: any) => item.contract_id === selectedContract.value?.id || item.contractId === selectedContract.value?.id)
    contractNotes.value = (notesResponse || []).filter((item: any) => item.contract_id === selectedContract.value?.id || item.contractId === selectedContract.value?.id)
    auditLogs.value = Array.isArray(auditResponse) ? auditResponse : auditResponse?.data || []
    assignmentForm.signing_deadline = selectedContract.value?.signing_deadline?.slice(0, 10) || ''
  } catch (err) {
    console.error('Failed to load contract details', err)
    errorMessage.value = 'Unable to load contract details.'
  } finally {
    isDetailLoading.value = false
  }
}

const assignContract = async () => {
  if (!selectedContract.value) {
    return
  }

  isAssigning.value = true
  errorMessage.value = ''

  try {
    const response: any = await platformApi.assignContract(selectedContract.value.id, {
      signing_deadline: assignmentForm.signing_deadline || undefined
    })
    const assignedContract = response?.contract || response
    selectedContract.value = assignedContract
    feedback.value = response?.email_sent === false
      ? 'Contract assigned, but the email could not be sent automatically.'
      : 'Contract assigned and employee notified.'
    await loadContractDetails(selectedContract.value.id)
    await fetchContracts()
  } catch (err: any) {
    console.error('Failed to assign contract', err)
    errorMessage.value = err.response?.data?.message || 'Unable to assign this contract.'
  } finally {
    isAssigning.value = false
  }
}

const openContract = async (contract: any) => {
  await loadContractDetails(contract.id)
}

const forceDeleteContract = async () => {
  if (!selectedContract.value) return

  if (!window.confirm('Force delete this contract immediately? This action cannot be undone.')) {
    return
  }

  isDeleting.value = true
  errorMessage.value = ''

  try {
    await platformApi.deleteContract(selectedContract.value.id)
    feedback.value = 'Contract force deleted successfully.'
    selectedContract.value = null
    await fetchContracts()
  } catch (err: any) {
    console.error('Failed to force delete contract', err)
    errorMessage.value = err.response?.data?.message || 'Unable to force delete the contract.'
  } finally {
    isDeleting.value = false
  }
}

const submitComment = async () => {
  if (!selectedContract.value || !commentForm.value.trim()) {
    return
  }

  isSavingComment.value = true
  errorMessage.value = ''

  try {
    await platformApi.addContractComment({ contract_id: selectedContract.value.id, comment_text: commentForm.value })
    commentForm.value = ''
    await loadContractDetails(selectedContract.value.id)
  } catch (err) {
    console.error('Failed to save comment', err)
    errorMessage.value = 'Unable to save comment.'
  } finally {
    isSavingComment.value = false
  }
}

const submitNote = async () => {
  if (!selectedContract.value || !noteForm.value.trim()) {
    return
  }

  isSavingNote.value = true
  errorMessage.value = ''

  try {
    await platformApi.addContractNote({ contract_id: selectedContract.value.id, note_text: noteForm.value })
    noteForm.value = ''
    await loadContractDetails(selectedContract.value.id)
  } catch (err) {
    console.error('Failed to save note', err)
    errorMessage.value = 'Unable to save note.'
  } finally {
    isSavingNote.value = false
  }
}

const uploadAttachment = async () => {
  if (!selectedContract.value || !attachmentFile.value) {
    return
  }

  isUploadingAttachment.value = true
  errorMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('attachment', attachmentFile.value)
    formData.append('contract_id', String(selectedContract.value.id))
    await platformApi.uploadContractAttachment(formData)
    attachmentFile.value = null
    if (attachmentInput.value) {
      attachmentInput.value.value = ''
    }
    await loadContractDetails(selectedContract.value.id)
  } catch (err) {
    console.error('Failed to upload attachment', err)
    errorMessage.value = 'Unable to upload attachment.'
  } finally {
    isUploadingAttachment.value = false
  }
}

const getAttachmentUrl = (attachment: any) => {
  return attachment.url || attachment.file_url || attachment.path || attachment.file_path || ''
}

onMounted(fetchContracts)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Contracts</h2>
        <p class="text-gray-500 dark:text-gray-400">Manage employee agreements and durations.</p>
      </div>
      <Button class="bg-blue-600 hover:bg-blue-700 text-white" @click="isCreating = !isCreating">
        <Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'Add Contract' }}
      </Button>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="!isLoading && !hasEmployees" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-300">
      No employee profiles were found, so contracts cannot be created yet. Create employees first from the employee management page.
    </div>

    <Card v-if="isCreating">
      <CardHeader>
        <CardTitle>Create Contract</CardTitle>
        <CardDescription>This form is connected to the repaired Laravel contract controller.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="space-y-2">
          <Label>Employee</Label>
          <select v-model="form.employee_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">{{ hasEmployees ? 'Select employee' : 'No employees available' }}</option>
            <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.display_name }}</option>
          </select>
          <p v-if="!hasEmployees" class="text-xs text-amber-600 dark:text-amber-400">
            An employee profile must exist before a contract can be created.
          </p>
        </div>
        <div class="space-y-2">
          <Label>Contract Type</Label>
          <select v-model="form.contract_type_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">Select type</option>
            <option v-for="type in contractTypes" :key="type.id" :value="String(type.id)">{{ type.name }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Status</Label>
          <select v-model="form.status" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="draft">Draft</option>
            <option value="pending">Pending</option>
            <option value="expired">Expired</option>
          </select>
        </div>
        <div class="space-y-2 md:col-span-2 xl:col-span-3">
          <Label>Contract Name</Label>
          <Input v-model="form.contract_name" placeholder="Employment agreement" />
        </div>
        <div class="space-y-2">
          <Label>Start Date</Label>
          <Input v-model="form.start_date" type="date" />
        </div>
        <div class="space-y-2">
          <Label>End Date</Label>
          <Input v-model="form.end_date" type="date" />
        </div>
        <div class="space-y-2 md:col-span-2 xl:col-span-3">
          <Label>Notes</Label>
          <Input v-model="form.notes" placeholder="Optional notes" />
        </div>
        <div class="md:col-span-2 xl:col-span-3 flex justify-end">
          <div class="flex gap-3">
            <Button v-if="!hasEmployees" variant="outline" @click="router.push('/rh/employees')">Open Employees</Button>
            <Button :disabled="!hasEmployees" @click="createContract">Save Contract</Button>
          </div>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="items" 
          :loading="isLoading"
          searchPlaceholder="Search contracts..."
          emptyMessage="No contracts found."
        >
          <template #cell(status)="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
          </template>
          <template #actions="{ item }">
            <Button size="sm" variant="outline" @click.stop="openContract(item)">View</Button>
          </template>
        </DataTable>
      </CardContent>
    </Card>

    <Card v-if="selectedContract">
      <CardHeader>
        <CardTitle>Contract Details</CardTitle>
        <CardDescription>Review attachments, notes and comments for the selected contract.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
            <h3 class="text-lg font-semibold">Details</h3>
            <p><strong>Name:</strong> {{ selectedContract.contract_name }}</p>
            <p><strong>Employee ID:</strong> {{ selectedContract.employee_id }}</p>
            <p><strong>Type:</strong> {{ getContractTypeName(selectedContract.contract_type_id) }}</p>
            <p><strong>Status:</strong> <Badge :variant="getStatusVariant(selectedContract.status)">{{ selectedContract.status }}</Badge></p>
            <p><strong>Period:</strong> {{ selectedContract.start_date }} — {{ selectedContract.end_date }}</p>
            <p><strong>Verification Code:</strong> {{ selectedContract.verification_code || 'Not assigned yet' }}</p>
            <p><strong>Signing Deadline:</strong> {{ selectedContract.signing_deadline || 'Not set' }}</p>
            <p><strong>Notes:</strong> {{ selectedContract.notes || 'No notes available.' }}</p>
            <div class="space-y-2 pt-3">
              <Label>Signing Deadline</Label>
              <Input v-model="assignmentForm.signing_deadline" type="date" />
              <div class="flex flex-wrap gap-3">
                <Button size="sm" :disabled="isAssigning" @click="assignContract">
                  {{ isAssigning ? 'Assigning...' : 'Assign and Notify Employee' }}
                </Button>
                <Button
                  size="sm"
                  variant="destructive"
                  :disabled="isDeleting"
                  @click="forceDeleteContract"
                >
                  {{ isDeleting ? 'Deleting...' : 'Force Delete Contract' }}
                </Button>
              </div>
            </div>
          </div>

          <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
            <h3 class="text-lg font-semibold">Attachments</h3>
            <div v-if="contractAttachments.length">
              <ul class="space-y-2">
                <li v-for="attachment in contractAttachments" :key="attachment.id" class="flex items-center justify-between rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                  <span>{{ attachment.file_name || attachment.filename || attachment.name || 'Attachment' }}</span>
                  <a v-if="getAttachmentUrl(attachment)" :href="getAttachmentUrl(attachment)" target="_blank" rel="noreferrer" class="text-blue-600 hover:underline">Open</a>
                </li>
              </ul>
            </div>
            <p v-else class="text-sm text-slate-500">No attachments uploaded yet.</p>
            <div class="space-y-2 pt-3">
              <input ref="attachmentInput" type="file" @change="handleAttachmentChange" />
              <Button size="sm" :disabled="isUploadingAttachment" @click="uploadAttachment">Upload Attachment</Button>
            </div>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
            <h3 class="text-lg font-semibold">Comments</h3>
            <div v-if="contractComments.length">
              <ul class="space-y-2">
                <li v-for="comment in contractComments" :key="comment.id" class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                  <p class="text-sm text-slate-700 dark:text-slate-200">{{ comment.comment_text || comment.comment || comment.body || comment.text || 'No comment text' }}</p>
                  <p class="mt-1 text-xs text-slate-500">{{ comment.created_at || comment.updated_at || comment.date || 'Unknown date' }}</p>
                </li>
              </ul>
            </div>
            <p v-else class="text-sm text-slate-500">No comments yet.</p>
            <div class="space-y-2 pt-3">
              <textarea v-model="commentForm" rows="3" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Add a comment"></textarea>
              <Button type="button" size="sm" :disabled="isSavingComment" @click="submitComment">Save Comment</Button>
            </div>
          </div>

          <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
            <h3 class="text-lg font-semibold">Notes</h3>
            <div v-if="contractNotes.length">
              <ul class="space-y-2">
                <li v-for="note in contractNotes" :key="note.id" class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                  <p class="text-sm text-slate-700 dark:text-slate-200">{{ note.note_text || note.note || note.body || note.text || 'No note text' }}</p>
                  <p class="mt-1 text-xs text-slate-500">{{ note.created_at || note.updated_at || note.date || 'Unknown date' }}</p>
                </li>
              </ul>
            </div>
            <p v-else class="text-sm text-slate-500">No notes yet.</p>
            <div class="space-y-2 pt-3">
              <textarea v-model="noteForm" rows="3" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Add a note"></textarea>
              <Button type="button" size="sm" :disabled="isSavingNote" @click="submitNote">Save Note</Button>
            </div>
          </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/50">
          <h3 class="text-lg font-semibold">Audit Timeline</h3>
          <div v-if="auditLogs.length" class="mt-3 space-y-3">
            <div
              v-for="log in auditLogs"
              :key="log.id"
              class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="font-medium capitalize text-slate-900 dark:text-white">{{ String(log.action || 'update').replace(/_/g, ' ') }}</div>
                <div class="text-xs text-slate-500">{{ log.created_at || 'Unknown date' }}</div>
              </div>
              <div v-if="log.metadata" class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                {{ JSON.stringify(log.metadata) }}
              </div>
            </div>
          </div>
          <p v-else class="mt-2 text-sm text-slate-500">No audit activity recorded yet.</p>
        </div>

        <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
          {{ errorMessage }}
        </div>
      </CardContent>
    </Card>
  </div>
</template>
