<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { CircleDollarSign, FileDown, Plus, SendHorizonal, Wallet } from 'lucide-vue-next'
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
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'

const items = ref<any[]>([])
const employees = ref<any[]>([])
const isLoading = ref(true)
const isCreating = ref(false)
const feedback = ref('')
const errorMsg = ref('')
const searchQuery = ref('')

const form = reactive({
  employee_id: '',
  payroll_month: '',
  payroll_year: '',
  basic_salary: '',
  gross_salary: '',
  deductions: '',
  notes: ''
})

const columns = [
  { key: 'id', label: 'Ref No.' },
  { key: 'employee_name', label: 'Employee' },
  { key: 'period', label: 'Period' },
  { key: 'net_payable', label: 'Net Salary' },
  { key: 'status', label: 'Status' }
]

function formatEmployeeName(item: any) {
  return (
    item.employee?.name ||
    item.employee?.full_name ||
    employeeMap.value.get(item.employee_id) ||
    'Unassigned'
  )
}

function formatPeriod(item: any) {
  if (item.payroll_month && item.payroll_year) {
    return `${String(item.payroll_month).padStart(2, '0')}/${item.payroll_year}`
  }

  return 'Not scheduled'
}

function normalizePayrollItem(item: any) {
  const grossSalary = item.gross_salary !== null && item.gross_salary !== undefined && item.gross_salary !== ''
    ? Number(item.gross_salary)
    : null
  const deductions = item.deductions !== null && item.deductions !== undefined && item.deductions !== ''
    ? Number(item.deductions)
    : 0
  const derivedNet = grossSalary !== null ? grossSalary - deductions : null
  const netPayable = item.net_payable !== null && item.net_payable !== undefined && item.net_payable !== ''
    ? Number(item.net_payable)
    : derivedNet

  const missingFields = [
    !item.employee_id && !item.employee?.id ? 'employee' : null,
    !item.payroll_month ? 'month' : null,
    !item.payroll_year ? 'year' : null,
    grossSalary === null ? 'gross salary' : null,
  ].filter(Boolean)

  return {
    ...item,
    employee_name: formatEmployeeName(item),
    period: formatPeriod(item),
    net_payable_value: netPayable,
    net_payable: typeof netPayable === 'number' && Number.isFinite(netPayable)
      ? `$${netPayable.toLocaleString()}`
      : 'Pending calculation',
    status: missingFields.length
      ? 'incomplete'
      : String(item.status || 'draft').replace(/_/g, ' '),
    missing_fields: missingFields,
  }
}

const employeeMap = computed(() =>
  new Map(
    employees.value.map((employee) => [
      employee.id,
      employee.name || employee.full_name || `Employee #${employee.id}`,
    ])
  )
)

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return items.value

  return items.value.filter((item) => {
    const haystack = [
      item.id,
      item.employee_id,
      item.employee_name,
      item.period,
      item.net_payable,
      item.status,
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return haystack.includes(query)
  })
})

const fetchPayslips = async () => {
  isLoading.value = true
  try {
    const [paySlipData, employeeData] = await Promise.all([
      platformApi.getPaySlips(),
      platformApi.getEmployees()
    ])
    employees.value = unwrapItems<any>(employeeData)
    items.value = unwrapItems<any>(paySlipData).map(normalizePayrollItem)
  } catch (err) {
    console.error('Failed to fetch payslips', err)
    errorMsg.value = 'Unable to load payroll records right now.'
  } finally {
    isLoading.value = false
  }
}

const getStatusVariant = (status: string) => {
  const normalized = status.toLowerCase()
  if (normalized === 'paid' || normalized === 'sent') return 'success'
  if (normalized === 'incomplete') return 'destructive'
  if (normalized === 'pending' || normalized === 'draft') return 'warning'
  return 'secondary'
}

const payrollStats = computed(() => {
  const totalAmount = items.value.reduce((sum, item) => {
    const numeric = Number(item.net_payable_value)
    return Number.isFinite(numeric) ? sum + numeric : sum
  }, 0)

  return [
    {
      label: 'Total payslips',
      value: items.value.length,
      description: 'Payroll records currently available.',
      icon: Wallet,
      color: 'bg-sky-500'
    },
    {
      label: 'Awaiting send',
      value: items.value.filter((item) => !['sent', 'paid'].includes(String(item.status).toLowerCase())).length,
      description: 'Payslips that still need action.',
      icon: SendHorizonal,
      color: 'bg-amber-500'
    },
    {
      label: 'Payroll volume',
      value: `$${totalAmount.toLocaleString()}`,
      description: 'Visible net payable amount in this list.',
      icon: CircleDollarSign,
      color: 'bg-emerald-500'
    }
  ]
})

async function createPaySlip() {
  errorMsg.value = ''
  feedback.value = ''

  try {
    await platformApi.createPaySlip({
      employee_id: Number(form.employee_id),
      payroll_month: Number(form.payroll_month),
      payroll_year: Number(form.payroll_year),
      basic_salary: Number(form.basic_salary),
      gross_salary: Number(form.gross_salary),
      deductions: Number(form.deductions || 0),
      notes: form.notes
    })
    feedback.value = 'Payslip created successfully.'
    isCreating.value = false
    form.employee_id = ''
    form.payroll_month = ''
    form.payroll_year = ''
    form.basic_salary = ''
    form.gross_salary = ''
    form.deductions = ''
    form.notes = ''
    await fetchPayslips()
  } catch (error) {
    console.error('Unable to create payslip', error)
    errorMsg.value = 'Unable to create the payslip right now.'
  }
}

async function generate(item: any) {
  errorMsg.value = ''
  feedback.value = ''
  try {
    await platformApi.generatePaySlip(item.id)
    feedback.value = `Payslip ${item.id} generated successfully.`
    await fetchPayslips()
  } catch (error: any) {
    console.error('Unable to generate payslip', error)
    errorMsg.value = error?.response?.data?.message ?? `Unable to generate payslip ${item.id}.`
  }
}

async function send(item: any) {
  errorMsg.value = ''
  feedback.value = ''
  try {
    await platformApi.sendPaySlip(item.id)
    feedback.value = `Payslip ${item.id} sent successfully.`
    await fetchPayslips()
  } catch (error: any) {
    console.error('Unable to send payslip', error)
    errorMsg.value = error?.response?.data?.message ?? `Unable to send payslip ${item.id}.`
  }
}

function handleSearch(value: string) {
  searchQuery.value = value
}

onMounted(fetchPayslips)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight">Payroll</h2>
        <p class="text-gray-500 dark:text-gray-400">Manage employee payslips and compensation.</p>
      </div>
      <div class="flex gap-2">
        <Button variant="outline"><FileDown class="w-4 h-4 mr-2" /> Export</Button>
        <Button class="bg-blue-600" @click="isCreating = !isCreating"><Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'Create Payslip' }}</Button>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <Card v-for="card in payrollStats" :key="card.label">
        <CardContent class="p-6">
          <div class="flex items-start justify-between gap-4">
            <div>
              <div class="text-sm text-slate-500 dark:text-slate-400">{{ card.label }}</div>
              <div class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">{{ card.value }}</div>
              <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ card.description }}</div>
            </div>
            <div :class="[card.color, 'flex h-12 w-12 items-center justify-center rounded-2xl text-white']">
              <component :is="card.icon" class="h-5 w-5" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card v-if="isCreating">
      <CardHeader>
        <CardTitle>Create Payslip</CardTitle>
        <CardDescription>The RH payroll page now submits directly to Laravel pay-slip endpoints.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div class="space-y-2">
          <Label>Employee</Label>
          <select v-model="form.employee_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">Select employee</option>
            <option v-for="employee in employees" :key="employee.id" :value="String(employee.id)">{{ employee.name }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Payroll Month</Label>
          <Input v-model="form.payroll_month" type="number" min="1" max="12" />
        </div>
        <div class="space-y-2">
          <Label>Payroll Year</Label>
          <Input v-model="form.payroll_year" type="number" min="2024" />
        </div>
        <div class="space-y-2">
          <Label>Basic Salary</Label>
          <Input v-model="form.basic_salary" type="number" />
        </div>
        <div class="space-y-2">
          <Label>Gross Salary</Label>
          <Input v-model="form.gross_salary" type="number" />
        </div>
        <div class="space-y-2">
          <Label>Deductions</Label>
          <Input v-model="form.deductions" type="number" />
        </div>
        <div class="space-y-2 md:col-span-2 xl:col-span-2">
          <Label>Notes</Label>
          <Input v-model="form.notes" placeholder="Optional payroll notes" />
        </div>
        <div class="md:col-span-2 xl:col-span-1 flex items-end justify-end">
          <Button @click="createPaySlip">Save Payslip</Button>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="filteredItems" 
          :loading="isLoading"
          searchPlaceholder="Search by payslip ID, employee, or period..."
          @search="handleSearch"
        >
          <template #cell(status)="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
          </template>
          <template #actions="{ item }">
            <div class="flex justify-end gap-2">
              <Button
                class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                :disabled="item.status === 'incomplete'"
                @click.stop="generate(item)"
              >
                Generate
              </Button>
              <Button
                class="bg-blue-600 text-white hover:bg-blue-700"
                :disabled="item.status === 'incomplete'"
                @click.stop="send(item)"
              >
                <SendHorizonal class="mr-2 h-4 w-4" />
                Send
              </Button>
            </div>
          </template>
        </DataTable>
      </CardContent>
    </Card>
  </div>
</template>
