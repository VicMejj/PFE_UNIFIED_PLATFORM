<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { organizationApi } from '@/api/laravel/organization'
import { unwrapItems } from '@/api/http'

const activeTab = ref<'branches' | 'departments' | 'designations'>('branches')
const branches = ref<any[]>([])
const departments = ref<any[]>([])
const designations = ref<any[]>([])
const isLoading = ref(true)
const isCreating = ref(false)
const feedback = ref('')
const errorMessage = ref('')

const branchForm = reactive({
  name: '',
  location: '',
  code: ''
})

const departmentForm = reactive({
  name: '',
  description: ''
})

const designationForm = reactive({
  name: '',
  description: ''
})

const branchColumns = [
  { key: 'name', label: 'Branch Name' },
  { key: 'location', label: 'Location' },
  { key: 'code', label: 'Code' }
]

const departmentColumns = [
  { key: 'name', label: 'Department Name' },
  { key: 'description', label: 'Description' }
]

const designationColumns = [
  { key: 'name', label: 'Designation' },
  { key: 'description', label: 'Description' }
]

const fetchBranches = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await organizationApi.getBranches()
    branches.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch branches', err)
    errorMessage.value = 'Unable to load branches.'
  } finally {
    isLoading.value = false
  }
}

const fetchDepartments = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await organizationApi.getDepartments()
    departments.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch departments', err)
    errorMessage.value = 'Unable to load departments.'
  } finally {
    isLoading.value = false
  }
}

const fetchDesignations = async () => {
  isLoading.value = true
  errorMessage.value = ''
  try {
    const response = await organizationApi.getDesignations()
    designations.value = unwrapItems<any>(response)
  } catch (err) {
    console.error('Failed to fetch designations', err)
    errorMessage.value = 'Unable to load designations.'
  } finally {
    isLoading.value = false
  }
}

const createBranch = async () => {
  isCreating.value = true
  errorMessage.value = ''
  feedback.value = ''

  try {
    await organizationApi.createBranch({
      name: branchForm.name,
      location: branchForm.location,
      code: branchForm.code
    })
    feedback.value = 'Branch created successfully.'
    branchForm.name = ''
    branchForm.location = ''
    branchForm.code = ''
    await fetchBranches()
  } catch (err) {
    console.error('Failed to create branch', err)
    errorMessage.value = 'Unable to create branch.'
  } finally {
    isCreating.value = false
  }
}

const createDepartment = async () => {
  isCreating.value = true
  errorMessage.value = ''
  feedback.value = ''

  try {
    await organizationApi.createDepartment({
      name: departmentForm.name,
      description: departmentForm.description
    })
    feedback.value = 'Department created successfully.'
    departmentForm.name = ''
    departmentForm.description = ''
    await fetchDepartments()
  } catch (err) {
    console.error('Failed to create department', err)
    errorMessage.value = 'Unable to create department.'
  } finally {
    isCreating.value = false
  }
}

const createDesignation = async () => {
  isCreating.value = true
  errorMessage.value = ''
  feedback.value = ''

  try {
    await organizationApi.createDesignation({
      name: designationForm.name,
      description: designationForm.description
    })
    feedback.value = 'Designation created successfully.'
    designationForm.name = ''
    designationForm.description = ''
    await fetchDesignations()
  } catch (err) {
    console.error('Failed to create designation', err)
    errorMessage.value = 'Unable to create designation.'
  } finally {
    isCreating.value = false
  }
}

const switchTab = (tab: 'branches' | 'departments' | 'designations') => {
  isCreating.value = false
  activeTab.value = tab
  if (tab === 'branches' && branches.value.length === 0) fetchBranches()
  if (tab === 'departments' && departments.value.length === 0) fetchDepartments()
  if (tab === 'designations' && designations.value.length === 0) fetchDesignations()
}

onMounted(() => {
  fetchBranches()
  fetchDepartments()
  fetchDesignations()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Organization Structure</h2>
        <p class="text-gray-500 dark:text-gray-400">Manage branches, departments, and designations.</p>
      </div>
      <Button class="bg-blue-600 hover:bg-blue-700 text-white" @click="isCreating = !isCreating">
        <Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'Add Item' }}
      </Button>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>

    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <!-- TABS -->
    <div class="flex space-x-4 border-b">
      <button 
        @click="switchTab('branches')"
        :class="[
          'px-4 py-2 font-medium border-b-2 transition',
          activeTab === 'branches' 
            ? 'border-blue-600 text-blue-600' 
            : 'border-transparent text-gray-600 hover:text-gray-900'
        ]"
      >
        Branches
      </button>
      <button 
        @click="switchTab('departments')"
        :class="[
          'px-4 py-2 font-medium border-b-2 transition',
          activeTab === 'departments' 
            ? 'border-blue-600 text-blue-600' 
            : 'border-transparent text-gray-600 hover:text-gray-900'
        ]"
      >
        Departments
      </button>
      <button 
        @click="switchTab('designations')"
        :class="[
          'px-4 py-2 font-medium border-b-2 transition',
          activeTab === 'designations' 
            ? 'border-blue-600 text-blue-600' 
            : 'border-transparent text-gray-600 hover:text-gray-900'
        ]"
      >
        Designations
      </button>
    </div>

    <!-- BRANCHES TAB -->
    <div v-if="activeTab === 'branches'" class="space-y-4">
      <Card v-if="isCreating">
        <CardHeader>
          <CardTitle>Create Branch</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-3">
          <div class="space-y-2">
            <Label>Branch Name</Label>
            <Input v-model="branchForm.name" placeholder="e.g., Head Office" />
          </div>
          <div class="space-y-2">
            <Label>Location</Label>
            <Input v-model="branchForm.location" placeholder="City or Address" />
          </div>
          <div class="space-y-2">
            <Label>Code</Label>
            <Input v-model="branchForm.code" placeholder="Branch code" />
          </div>
          <div class="md:col-span-3 flex justify-end">
            <Button :disabled="isCreating" @click="createBranch">Create Branch</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="pt-6">
          <DataTable 
            :columns="branchColumns" 
            :data="branches" 
            :loading="isLoading"
            searchPlaceholder="Search branches..."
            emptyMessage="No branches found."
          />
        </CardContent>
      </Card>
    </div>

    <!-- DEPARTMENTS TAB -->
    <div v-if="activeTab === 'departments'" class="space-y-4">
      <Card v-if="isCreating">
        <CardHeader>
          <CardTitle>Create Department</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2">
            <Label>Department Name</Label>
            <Input v-model="departmentForm.name" placeholder="e.g., Human Resources" />
          </div>
          <div class="space-y-2">
            <Label>Description</Label>
            <Input v-model="departmentForm.description" placeholder="Department description" />
          </div>
          <div class="md:col-span-2 flex justify-end">
            <Button :disabled="isCreating" @click="createDepartment">Create Department</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="pt-6">
          <DataTable 
            :columns="departmentColumns" 
            :data="departments" 
            :loading="isLoading"
            searchPlaceholder="Search departments..."
            emptyMessage="No departments found."
          />
        </CardContent>
      </Card>
    </div>

    <!-- DESIGNATIONS TAB -->
    <div v-if="activeTab === 'designations'" class="space-y-4">
      <Card v-if="isCreating">
        <CardHeader>
          <CardTitle>Create Designation</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2">
            <Label>Designation</Label>
            <Input v-model="designationForm.name" placeholder="e.g., Senior Manager" />
          </div>
          <div class="space-y-2">
            <Label>Description</Label>
            <Input v-model="designationForm.description" placeholder="Role description" />
          </div>
          <div class="md:col-span-2 flex justify-end">
            <Button :disabled="isCreating" @click="createDesignation">Create Designation</Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="pt-6">
          <DataTable 
            :columns="designationColumns" 
            :data="designations" 
            :loading="isLoading"
            searchPlaceholder="Search designations..."
            emptyMessage="No designations found."
          />
        </CardContent>
      </Card>
    </div>
  </div>
</template>
