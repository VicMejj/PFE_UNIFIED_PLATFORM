<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { AlertCircle, Building2, Check, Plus, Search, Trash2, UserRoundPen } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import Dialog from '@/components/ui/Dialog.vue'
import Badge from '@/components/ui/Badge.vue'
import { organizationApi } from '@/api/laravel/organization'
import { isNetworkOrServerUnavailable, unwrapItems } from '@/api/http'

type OrgTab = 'branches' | 'departments' | 'designations'
type OrgRecord = {
  id: number
  name?: string
  title?: string
  code?: string | null
  description?: string | null
  is_active?: boolean
}

const activeTab = ref<OrgTab>('branches')
const loading = ref(true)
const saving = ref(false)
const feedback = ref('')
const errorMessage = ref('')
const search = ref('')
const editDialogOpen = ref(false)
const deleteDialogOpen = ref(false)
const selectedRecord = ref<OrgRecord | null>(null)
const confirmMode = ref<'deactivate' | 'delete'>('deactivate')

const branches = ref<OrgRecord[]>([])
const departments = ref<OrgRecord[]>([])
const designations = ref<OrgRecord[]>([])
const tabs: OrgTab[] = ['branches', 'departments', 'designations']

type OrgForm = {
  name?: string
  title?: string
  code?: string
  description?: string
  is_active?: boolean
}

const forms = reactive<Record<OrgTab, OrgForm>>({
  branches: { name: '', code: '', description: '', is_active: true },
  departments: { name: '', code: '', description: '', is_active: true },
  designations: { title: '', code: '', description: '', is_active: true },
})

const currentConfig = computed(() => {
  if (activeTab.value === 'branches') {
    return {
      label: 'Branch',
      subtitle: 'Manage company locations and operational sites.',
      records: branches.value,
      columns: [
        { key: 'name', label: 'Name' },
        { key: 'code', label: 'Code' },
        { key: 'description', label: 'Description' },
      ],
      createLabel: 'Create Branch',
      api: {
        list: organizationApi.getBranches,
        create: organizationApi.createBranch,
        update: organizationApi.updateBranch,
        remove: organizationApi.deleteBranch,
      },
    }
  }

  if (activeTab.value === 'departments') {
    return {
      label: 'Department',
      subtitle: 'Maintain departments used throughout employee and payroll workflows.',
      records: departments.value,
      columns: [
        { key: 'name', label: 'Name' },
        { key: 'code', label: 'Code' },
        { key: 'description', label: 'Description' },
      ],
      createLabel: 'Create Department',
      api: {
        list: organizationApi.getDepartments,
        create: organizationApi.createDepartment,
        update: organizationApi.updateDepartment,
        remove: organizationApi.deleteDepartment,
      },
    }
  }

  return {
    label: 'Designation',
    subtitle: 'Manage titles and role hierarchy for the organization.',
    records: designations.value,
    columns: [
      { key: 'title', label: 'Title' },
      { key: 'code', label: 'Code' },
      { key: 'description', label: 'Description' },
    ],
    createLabel: 'Create Designation',
    api: {
      list: organizationApi.getDesignations,
      create: organizationApi.createDesignation,
      update: organizationApi.updateDesignation,
      remove: organizationApi.deleteDesignation,
    },
  }
})

function recordLabel(record: OrgRecord): string {
  return String(record.title || record.name || '')
}

function recordCode(record: OrgRecord): string {
  return String(record.code || '')
}

function recordDescription(record: OrgRecord): string {
  return String(record.description || '')
}

const filteredRecords = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) return currentConfig.value.records
  return currentConfig.value.records.filter((record) =>
    [recordLabel(record), recordCode(record), recordDescription(record), record.is_active ? 'active' : 'inactive']
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

function blankForm(tab: OrgTab) {
  return tab === 'designations'
    ? { title: '', code: '', description: '', is_active: true }
    : { name: '', code: '', description: '', is_active: true }
}

async function loadRecords() {
  loading.value = true
  errorMessage.value = ''
  try {
    const [b, d, t] = await Promise.all([
      organizationApi.getBranches(),
      organizationApi.getDepartments(),
      organizationApi.getDesignations(),
    ])
    branches.value = unwrapItems<OrgRecord>(b)
    departments.value = unwrapItems<OrgRecord>(d)
    designations.value = unwrapItems<OrgRecord>(t)
  } catch (error) {
    errorMessage.value = isNetworkOrServerUnavailable(error)
      ? 'Laravel is unavailable right now, so organization records cannot be loaded.'
      : 'Unable to load organization records right now.'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  forms[activeTab.value] = blankForm(activeTab.value)
}

function openCreate() {
  selectedRecord.value = null
  editDialogOpen.value = true
  resetForm()
}

function openEdit(record: OrgRecord) {
  selectedRecord.value = record
  editDialogOpen.value = true
  forms[activeTab.value] = {
    ...blankForm(activeTab.value),
    ...record,
    code: record.code ?? '',
    description: record.description ?? '',
    is_active: record.is_active ?? true,
  }
}

function openDeactivate(record: OrgRecord) {
  selectedRecord.value = record
  confirmMode.value = 'deactivate'
  deleteDialogOpen.value = true
}

async function submitRecord() {
  saving.value = true
  errorMessage.value = ''
  feedback.value = ''
  try {
    const payload = { ...forms[activeTab.value] }
    if (activeTab.value === 'designations') {
      payload.title = String(payload.title ?? '').trim()
    } else {
      payload.name = String(payload.name ?? '').trim()
    }

    if (selectedRecord.value?.id) {
      await currentConfig.value.api.update(selectedRecord.value.id, payload)
      feedback.value = `${currentConfig.value.label} updated successfully.`
    } else {
      await currentConfig.value.api.create(payload)
      feedback.value = `${currentConfig.value.label} created successfully.`
    }
    editDialogOpen.value = false
    selectedRecord.value = null
    resetForm()
    await loadRecords()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? `Unable to save ${currentConfig.value.label.toLowerCase()}.`
  } finally {
    saving.value = false
  }
}

async function confirmDeactivate() {
  if (!selectedRecord.value?.id) return
  saving.value = true
  try {
    await currentConfig.value.api.remove(selectedRecord.value.id)
    feedback.value = `${currentConfig.value.label} deactivated successfully.`
    deleteDialogOpen.value = false
    selectedRecord.value = null
    await loadRecords()
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message ?? `Unable to deactivate ${currentConfig.value.label.toLowerCase()}.`
  } finally {
    saving.value = false
  }
}

function switchTab(tab: OrgTab) {
  activeTab.value = tab
  search.value = ''
  resetForm()
  selectedRecord.value = null
}

onMounted(loadRecords)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <div class="inline-flex items-center gap-2 rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.2em] text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300">
          <Building2 class="h-3 w-3" />
          Organization Admin
        </div>
        <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-900 dark:text-white">Company Structure</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create, edit, search, and deactivate branches, departments, and designations.</p>
      </div>
      <div class="flex items-center gap-3">
        <Button class="bg-sky-600 text-white hover:bg-sky-500" @click="openCreate">
          <Plus class="mr-2 h-4 w-4" />
          Add {{ currentConfig.label }}
        </Button>
      </div>
    </div>

    <div v-if="feedback" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
      <AlertCircle class="mr-2 inline h-4 w-4" />
      {{ errorMessage }}
    </div>

  <div class="flex flex-wrap gap-2">
      <button v-for="tab in tabs" :key="tab" @click="switchTab(tab)" :class="['rounded-full px-4 py-2 text-sm font-semibold transition', activeTab === tab ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'bg-slate-100 text-slate-600 dark:bg-slate-900 dark:text-slate-300']">
        {{ tab.charAt(0).toUpperCase() + tab.slice(1) }}
      </button>
    </div>

    <Card class="glass-card premium-shadow">
      <CardHeader class="flex flex-row items-center justify-between space-y-0">
        <div>
          <CardTitle>{{ currentConfig.label }}s</CardTitle>
          <CardDescription>{{ currentConfig.subtitle }}</CardDescription>
        </div>
        <div class="relative w-full max-w-sm">
          <Search class="absolute left-3 top-3 h-4 w-4 text-slate-400" />
          <Input v-model="search" class="pl-9" placeholder="Search records..." />
        </div>
      </CardHeader>
      <CardContent>
        <div v-if="loading" class="py-10 text-center text-sm text-slate-500">Loading organization structure...</div>
        <div v-else-if="!filteredRecords.length" class="rounded-3xl border border-dashed border-slate-200 p-10 text-center text-sm text-slate-500 dark:border-slate-800">
          No {{ currentConfig.label.toLowerCase() }}s found.
        </div>
        <div v-else class="grid gap-4">
          <div v-for="record in filteredRecords" :key="record.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-950">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ record.title || record.name }}</h3>
                  <Badge :variant="record.is_active ? 'success' : 'secondary'">{{ record.is_active ? 'Active' : 'Inactive' }}</Badge>
                </div>
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ record.code || 'No code' }}</div>
                <p class="max-w-3xl text-sm text-slate-600 dark:text-slate-300">{{ record.description || 'No description provided.' }}</p>
              </div>
              <div class="flex items-center gap-2">
                <Button variant="outline" @click="openEdit(record)">
                  <UserRoundPen class="mr-2 h-4 w-4" /> Edit
                </Button>
                <Button variant="outline" class="border-rose-200 text-rose-600 hover:bg-rose-50" @click="openDeactivate(record)">
                  <Trash2 class="mr-2 h-4 w-4" /> Deactivate
                </Button>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <Dialog :open="editDialogOpen" :title="selectedRecord ? `Edit ${currentConfig.label}` : `Create ${currentConfig.label}`" :description="currentConfig.subtitle" size="2xl" @close="editDialogOpen = false">
      <div class="grid gap-4 md:grid-cols-2">
        <div v-if="activeTab === 'designations'" class="space-y-2 md:col-span-2">
          <Label>Title</Label>
          <Input v-model="forms[activeTab].title" placeholder="e.g., Senior Manager" />
        </div>
        <div v-else class="space-y-2 md:col-span-2">
          <Label>Name</Label>
          <Input v-model="forms[activeTab].name" placeholder="e.g., Head Office" />
        </div>
        <div class="space-y-2">
          <Label>Code</Label>
          <Input v-model="forms[activeTab].code" placeholder="Optional short code" />
        </div>
        <div class="space-y-2">
          <Label>Status</Label>
          <select v-model="forms[activeTab].is_active" class="flex h-11 w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </div>
        <div class="space-y-2 md:col-span-2">
          <Label>Description</Label>
          <Input v-model="forms[activeTab].description" placeholder="Describe this record" />
        </div>
      </div>
      <template #footer>
        <Button variant="outline" @click="editDialogOpen = false">Cancel</Button>
        <Button class="bg-sky-600 text-white hover:bg-sky-500" :disabled="saving" @click="submitRecord">
          <Check class="mr-2 h-4 w-4" /> {{ saving ? 'Saving...' : 'Save Changes' }}
        </Button>
      </template>
    </Dialog>

    <Dialog :open="deleteDialogOpen" :title="`Deactivate ${currentConfig.label}`" description="This keeps the record for historical employee links while disabling it for new use." @close="deleteDialogOpen = false">
      <div class="space-y-4">
        <p class="text-sm text-slate-600 dark:text-slate-300">
          {{ selectedRecord?.title || selectedRecord?.name }} will be marked inactive.
        </p>
      </div>
      <template #footer>
        <Button variant="outline" @click="deleteDialogOpen = false">Cancel</Button>
        <Button class="bg-rose-600 text-white hover:bg-rose-500" :disabled="saving" @click="confirmDeactivate">
          <Trash2 class="mr-2 h-4 w-4" /> {{ saving ? 'Processing...' : 'Deactivate' }}
        </Button>
      </template>
    </Dialog>
  </div>
</template>
