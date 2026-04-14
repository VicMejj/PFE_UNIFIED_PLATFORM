<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Plus, LayoutGrid, Users, Briefcase, ShieldCheck, AlertCircle, CheckCircle2, DollarSign, Clock, FileText } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import * as insuranceApi from '@/api/laravel/insurance'
import { platformApi } from '@/api/laravel/platform'
import { useAuthStore } from '@/stores/auth'

const plans = ref<any[]>([])
const providers = ref<any[]>([])
const employees = ref<any[]>([])
const departments = ref<any[]>([])
const isLoading = ref(true)
const isCreating = ref(false)
const isAssigning = ref(false)
const feedback = ref('')
const errorMsg = ref('')
const auth = useAuthStore()

const isAdmin = computed(() => ['admin', 'rh_manager', 'manager'].includes(auth.user?.role ?? ''))

const form = reactive({
  provider_id: null as number | null,
  name: '',
  policy_name: '',
  description: '',
  policy_type: 'health',
  premium_amount: 0,
  coverage_amount: 5000,
  coverage_details: '',
  waiting_period_days: 30,
  is_active: true
})

const assignForm = reactive({
  insurance_plan_id: null as number | null,
  employee_ids: [] as number[],
  department_id: null as number | null,
  assignment_type: 'individual' as 'individual' | 'department'
})

const fetchInitialData = async () => {
  isLoading.value = true
  try {
    const [planData, providerData, employeeData, deptData] = await Promise.all([
      insuranceApi.getPlans(),
      insuranceApi.getProviders(),
      platformApi.getEmployees(),
      platformApi.getDepartments()
    ])
    plans.value = planData || []
    providers.value = providerData || []
    employees.value = Array.isArray(employeeData) ? employeeData : (employeeData as any).data || []
    departments.value = Array.isArray(deptData) ? deptData : (deptData as any).data || []
  } catch (err) {
    console.error('Failed to fetch plan data', err)
    errorMsg.value = 'Unable to load insurance plans.'
  } finally {
    isLoading.value = false
  }
}

async function savePlan() {
  errorMsg.value = ''
  feedback.value = ''
  
  if (!form.provider_id) {
    errorMsg.value = 'Please select a provider.'
    return
  }
  
  if (!form.name) {
    errorMsg.value = 'Please enter a plan name.'
    return
  }

  const payload = {
    provider_id: form.provider_id,
    name: form.name || form.policy_name,
    policy_name: form.policy_name || form.name,
    policy_type: form.policy_type,
    premium_amount: form.premium_amount > 0 ? form.premium_amount : null,
    coverage_amount: form.coverage_amount > 0 ? form.coverage_amount : null,
    coverage_details: form.coverage_details || null,
    waiting_period_days: form.waiting_period_days > 0 ? form.waiting_period_days : null,
    is_active: form.is_active
  }

  try {
    await insuranceApi.createPlan(payload)
    feedback.value = 'Plan created successfully!'
    isCreating.value = false
    Object.assign(form, {
      provider_id: null,
      name: '',
      policy_name: '',
      description: '',
      policy_type: 'health',
      premium_amount: 0,
      coverage_amount: 5000,
      coverage_details: '',
      waiting_period_days: 30,
      is_active: true
    })
    await fetchInitialData()
  } catch (err: any) {
    errorMsg.value = err.response?.data?.message || 'Failed to create plan.'
  }
}

async function handleAssignment() {
  errorMsg.value = ''
  feedback.value = ''
  
  if (!assignForm.insurance_plan_id) {
    errorMsg.value = 'Please select a plan.'
    return
  }

  try {
    if (assignForm.assignment_type === 'individual') {
      if (assignForm.employee_ids.length === 0) {
        errorMsg.value = 'Please select at least one employee.'
        return
      }
      for (const empId of assignForm.employee_ids) {
        await insuranceApi.createEnrollment({
          employee_id: empId,
          policy_id: assignForm.insurance_plan_id,
          start_date: new Date().toISOString().split('T')[0],
          status: 'active'
        })
      }
    } else if (assignForm.assignment_type === 'department') {
      if (!assignForm.department_id) {
        errorMsg.value = 'Please select a department.'
        return
      }
      const deptEmployees = employees.value.filter(e => e.department_id === assignForm.department_id)
      for (const emp of deptEmployees) {
        await insuranceApi.createEnrollment({
          employee_id: emp.id,
          policy_id: assignForm.insurance_plan_id,
          start_date: new Date().toISOString().split('T')[0],
          status: 'active'
        })
      }
    }
    
    feedback.value = 'Insurance assigned successfully!'
    isAssigning.value = false
    assignForm.employee_ids = []
    assignForm.department_id = null
  } catch (err: any) {
    console.error('Assignment error:', err.response?.data || err)
    errorMsg.value = err.response?.data?.message || err.response?.data?.error || 'Failed to assign plan. Please try again.'
  }
}

const openAssignModal = (plan: any) => {
  assignForm.insurance_plan_id = plan.id
  isAssigning.value = true
}

function formatCurrency(value: any) {
  const num = Number(value) || 0
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function getPlanStatus(plan: any) {
  if (plan.is_active) return { label: 'Active', variant: 'success' as const }
  return { label: 'Inactive', variant: 'secondary' as const }
}

onMounted(fetchInitialData)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <div class="bg-blue-600 p-2 rounded-xl text-white">
          <LayoutGrid class="w-6 h-6" />
        </div>
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Insurance Plans</h2>
          <p class="text-gray-500 dark:text-gray-400">
            {{ isAdmin ? 'Configure and assign insurance coverage plans to employees.' : 'Browse available insurance coverage plans.' }}
          </p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button v-if="isAdmin" @click="isCreating = !isCreating" :variant="isCreating ? 'outline' : 'default'">
          <Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Cancel' : 'New Plan' }}
        </Button>
        <Button v-if="!isAdmin" variant="outline" @click="fetchInitialData">
          <ShieldCheck class="w-4 h-4 mr-2" /> Refresh Plans
        </Button>
      </div>
    </div>

    <!-- Feedback Alerts -->
    <div v-if="feedback" class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:text-emerald-300">
      <CheckCircle2 class="w-4 h-4" />
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
      <AlertCircle class="w-4 h-4" />
      {{ errorMsg }}
    </div>

    <!-- Create Plan Form (Admin Only) -->
    <Card v-if="isCreating && isAdmin">
      <CardHeader>
        <CardTitle>Create New Insurance Plan</CardTitle>
        <CardDescription>Define the coverage rules, premium, and requirements for this insurance plan.</CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2">
            <Label>Provider *</Label>
            <select v-model="form.provider_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option :value="null">Select a provider...</option>
              <option v-for="provider in providers" :key="provider.id" :value="provider.id">{{ provider.name }}</option>
            </select>
          </div>
          <div class="space-y-2">
            <Label>Plan Name *</Label>
            <Input v-model="form.name" placeholder="Premium Health Gold" />
          </div>
          <div class="space-y-2">
            <Label>Policy Type</Label>
            <select v-model="form.policy_type" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option value="health">Health</option>
              <option value="dental">Dental</option>
              <option value="vision">Vision</option>
              <option value="life">Life</option>
              <option value="disability">Disability</option>
            </select>
          </div>
          <div class="space-y-2">
            <Label>Monthly Premium ($)</Label>
            <Input v-model="form.premium_amount" type="number" placeholder="150.00" min="0" step="0.01" />
          </div>
          <div class="space-y-2">
            <Label>Coverage Amount ($)</Label>
            <Input v-model="form.coverage_amount" type="number" placeholder="5000" min="0" />
          </div>
          <div class="space-y-2">
            <Label>Waiting Period (Days)</Label>
            <Input v-model="form.waiting_period_days" type="number" placeholder="30" min="0" />
          </div>
          <div class="space-y-2">
            <Label>Status</Label>
            <select v-model="form.is_active" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
              <option :value="true">Active</option>
              <option :value="false">Inactive</option>
            </select>
          </div>
          <div class="space-y-2 md:col-span-2">
            <Label>Coverage Details</Label>
            <textarea v-model="form.coverage_details" rows="3" class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm" placeholder="Describe what this plan covers..."></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="outline" @click="isCreating = false">Cancel</Button>
          <Button @click="savePlan" class="bg-blue-600 hover:bg-blue-700">Create Plan</Button>
        </div>
      </CardContent>
    </Card>

    <!-- Assign Plan UI (Admin Only) -->
    <Card v-if="isAssigning && isAdmin">
      <CardHeader>
        <div class="flex justify-between items-start">
          <div>
            <CardTitle>Assign Insurance Plan</CardTitle>
            <CardDescription>Grant insurance coverage to employees or entire departments.</CardDescription>
          </div>
          <Button variant="ghost" size="sm" @click="isAssigning = false">Close</Button>
        </div>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-xl dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
           <div class="font-medium">Assignment Type:</div>
           <div class="flex gap-2">
             <Button :variant="assignForm.assignment_type === 'individual' ? 'default' : 'outline'" size="sm" @click="assignForm.assignment_type = 'individual'">
               <Users class="w-4 h-4 mr-2" /> Individual
             </Button>
             <Button :variant="assignForm.assignment_type === 'department' ? 'default' : 'outline'" size="sm" @click="assignForm.assignment_type = 'department'">
               <Briefcase class="w-4 h-4 mr-2" /> Department
             </Button>
           </div>
        </div>

        <div v-if="assignForm.assignment_type === 'individual'" class="space-y-2">
          <Label>Select Employees</Label>
          <select v-model="assignForm.employee_ids" multiple class="flex w-full min-h-[150px] rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.name }} ({{ emp.employee_id || emp.id }})</option>
          </select>
          <p class="text-xs text-gray-400">Hold Ctrl/Cmd to select multiple employees. Selected: {{ assignForm.employee_ids.length }}</p>
        </div>

        <div v-if="assignForm.assignment_type === 'department'" class="space-y-2">
          <Label>Select Department</Label>
          <select v-model="assignForm.department_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option :value="null">Select a department...</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
          </select>
          <p v-if="assignForm.department_id" class="text-xs text-gray-500">
            This will enroll all {{ employees.filter(e => e.department_id === assignForm.department_id).length }} employees in this department.
          </p>
        </div>

        <div class="flex justify-end pt-2">
          <Button @click="handleAssignment" class="bg-emerald-600 hover:bg-emerald-700" :disabled="!assignForm.insurance_plan_id">
            <CheckCircle2 class="w-4 h-4 mr-2" /> Confirm Assignment
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Plans Grid -->
    <div v-if="isLoading" class="py-12 text-center">
      <div class="animate-pulse text-slate-500">Loading insurance plans...</div>
    </div>
    
    <div v-else-if="plans.length === 0" class="py-12 text-center">
      <ShieldCheck class="mx-auto h-12 w-12 text-slate-300" />
      <h3 class="mt-4 text-lg font-semibold text-slate-900 dark:text-white">No Insurance Plans</h3>
      <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
        {{ isAdmin ? 'Create your first insurance plan to get started.' : 'No insurance plans are available at this time.' }}
      </p>
    </div>

    <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="plan in plans"
        :key="plan.id"
        class="rounded-2xl border border-slate-200 bg-white p-6 transition-all hover:shadow-lg dark:border-slate-700 dark:bg-slate-900"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-500/20">
              <ShieldCheck class="h-6 w-6 text-blue-600" />
            </div>
            <div>
              <h3 class="font-bold text-slate-900 dark:text-white">{{ plan.name || plan.policy_name || 'Unnamed Plan' }}</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">{{ plan.provider?.name || 'No Provider' }}</p>
              <Badge :variant="getPlanStatus(plan).variant" class="mt-1">{{ getPlanStatus(plan).label }}</Badge>
            </div>
          </div>
        </div>

        <div class="space-y-3">
          <div class="flex items-center justify-between text-sm">
            <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
              <DollarSign class="h-4 w-4" /> Coverage
            </span>
            <span class="font-semibold text-slate-900 dark:text-white">${{ formatCurrency(plan.coverage_amount ?? plan.max_coverage_amount ?? 0) }}</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
              <DollarSign class="h-4 w-4" /> Premium
            </span>
            <span class="font-semibold text-slate-900 dark:text-white">${{ formatCurrency(plan.premium_amount ?? plan.premium ?? 0) }}/mo</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
              <Clock class="h-4 w-4" /> Waiting Period
            </span>
            <span class="font-semibold text-slate-900 dark:text-white">{{ plan.waiting_period_days ?? 30 }} days</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
              <FileText class="h-4 w-4" /> Type
            </span>
            <span class="font-medium text-slate-700 dark:text-slate-300 capitalize">{{ plan.policy_type || 'health' }}</span>
          </div>
        </div>

        <p v-if="plan.coverage_details || plan.description" class="mt-4 text-sm text-slate-500 dark:text-slate-400 line-clamp-2">
          {{ plan.coverage_details || plan.description }}
        </p>

        <div v-if="isAdmin" class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
          <Button variant="outline" class="w-full" @click="openAssignModal(plan)">
            <Users class="w-4 h-4 mr-2" /> Assign to Employees
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
