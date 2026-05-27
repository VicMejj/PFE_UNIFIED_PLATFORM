<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { CircleDollarSign, Download, Wallet } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import DataTable from '@/components/ui/DataTable.vue'
import Badge from '@/components/ui/Badge.vue'
import { platformApi } from '@/api/laravel/platform'
import { unwrapItems } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const items = ref<any[]>([])
const isLoading = ref(true)
const feedback = ref('')
const errorMsg = ref('')
const downloadingId = ref<number | null>(null)

const employeeId = computed(() => auth.user?.employee_id ?? auth.user?.employee?.id)

const columns = [
  { key: 'period', label: 'Period' },
  { key: 'gross_salary', label: 'Gross Salary' },
  { key: 'deductions', label: 'Deductions' },
  { key: 'net_salary', label: 'Net Salary' },
  { key: 'status', label: 'Status' }
]

function formatPeriod(item: any) {
  if (item.payroll_month && item.payroll_year) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    return `${months[Number(item.payroll_month) - 1] || String(item.payroll_month).padStart(2, '0')} ${item.payroll_year}`
  }
  return 'Not scheduled'
}

function normalizePayslip(item: any) {
  const grossSalary = item.gross_salary != null && item.gross_salary !== '' ? Number(item.gross_salary) : null
  const deductions = item.deductions != null && item.deductions !== '' ? Number(item.deductions) : 0
  const derivedNet = grossSalary !== null ? grossSalary - deductions : null
  const netPayable = item.net_payable != null && item.net_payable !== '' ? Number(item.net_payable) : derivedNet

  const formatCurrency = (val: number | null) =>
    val != null && Number.isFinite(val) ? `TND ${val.toLocaleString()}` : '—'

  return {
    ...item,
    period: formatPeriod(item),
    gross_salary: formatCurrency(grossSalary),
    deductions: formatCurrency(deductions),
    net_salary: formatCurrency(netPayable),
    net_payable_value: netPayable
  }
}

const filteredItems = computed(() => items.value)

const payslipStats = computed(() => {
  const totalAmount = items.value.reduce((sum, item) => {
    const n = Number(item.net_payable_value)
    return Number.isFinite(n) ? sum + n : sum
  }, 0)
  const paidCount = items.value.filter(i => ['sent', 'paid'].includes(String(i.status).toLowerCase())).length
  return [
    { label: 'Total Pay Slips', value: items.value.length, description: 'Payroll records on file.', icon: Wallet, color: 'bg-sky-500' },
    { label: 'Processed', value: paidCount, description: 'Sent or paid.', icon: CircleDollarSign, color: 'bg-emerald-500' },
    { label: 'Total Net Pay', value: `TND ${totalAmount.toLocaleString()}`, description: 'Sum of all pay slips.', icon: CircleDollarSign, color: 'bg-indigo-500' }
  ]
})

const getStatusVariant = (status: string) => {
  const n = status.toLowerCase()
  if (n === 'paid' || n === 'sent') return 'success'
  if (n === 'pending' || n === 'draft') return 'warning'
  if (n === 'incomplete') return 'destructive'
  return 'secondary'
}

async function fetchPayslips() {
  isLoading.value = true
  errorMsg.value = ''
  try {
    const data = await platformApi.getPaySlips(employeeId.value ?? undefined)
    items.value = unwrapItems<any>(data).map(normalizePayslip)
  } catch {
    errorMsg.value = 'Unable to load your pay slips.'
  } finally {
    isLoading.value = false
  }
}

async function downloadPDF(item: any) {
  downloadingId.value = item.id
  errorMsg.value = ''
  try {
    const blob = await platformApi.downloadPayslipPDF(item.id)
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `payslip-${item.id}-${item.period?.replace(/[/\s]/g, '-') ?? 'download'}.pdf`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
    feedback.value = `Pay slip #${item.id} downloaded.`
  } catch (err: any) {
    errorMsg.value = err?.response?.data?.message ?? 'Unable to download this pay slip.'
  } finally {
    downloadingId.value = null
  }
}

onMounted(fetchPayslips)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <Wallet class="w-8 h-8 text-emerald-500" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">My Pay Slips</h2>
          <p class="text-gray-500 dark:text-gray-400">View and download your pay slips.</p>
        </div>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <Card v-for="card in payslipStats" :key="card.label">
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

    <Card>
      <CardContent class="pt-6">
        <DataTable
          :columns="columns"
          :data="filteredItems"
          :loading="isLoading"
          emptyMessage="No pay slips found for your account."
          searchPlaceholder="Search by period or status…"
          @search="() => {}"
        >
          <template #cell(status)="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
          </template>
          <template #actions="{ item }">
            <Button
              class="bg-emerald-600 text-white hover:bg-emerald-700 text-xs gap-1"
              :disabled="downloadingId === item.id || !['sent', 'paid'].includes(String(item.status).toLowerCase())"
              @click.stop="downloadPDF(item)"
            >
              <Download class="h-3.5 w-3.5" />
              {{ downloadingId === item.id ? '…' : 'PDF' }}
            </Button>
          </template>
        </DataTable>
      </CardContent>
    </Card>
  </div>
</template>
