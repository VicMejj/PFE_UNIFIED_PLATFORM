<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Download, ShieldCheck, FileBadge, LayoutGrid, RefreshCw, WandSparkles } from 'lucide-vue-next'
import { useRouter } from 'vue-router'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import * as insuranceApi from '@/api/laravel/insurance'
import { platformApi } from '@/api/laravel/platform'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const providers = ref<any[]>([])
const policies = ref<any[]>([])
const claims = ref<any[]>([])
const documents = ref<any[]>([])
const isLoading = ref(true)
const isSeeding = ref(false)
const statusMessage = ref('')
const lastRefreshedAt = ref('')

const isAdmin = computed(() => ['admin', 'rh_manager'].includes(auth.user?.role || ''))
const claimsLink = computed(() => isAdmin.value ? '/assurance/claims' : '/assurance/my-claims')

const mapRows = (rows: any[], mapper: (row: any) => Record<string, unknown>) => rows.map(mapper)

const stats = computed(() => [
  { label: 'Providers', value: providers.value.length },
  { label: 'Policies', value: policies.value.length },
  { label: 'Claims', value: claims.value.length },
  { label: 'Documents', value: documents.value.length },
])

const downloadJson = (name: string, data: unknown) => {
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${name}.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const escapeCsv = (value: unknown) => {
  const text = String(value ?? '')
  if (/[",\n]/.test(text)) {
    return `"${text.replace(/"/g, '""')}"`
  }
  return text
}

const downloadCsv = (name: string, rows: Record<string, unknown>[]) => {
  const headers = Array.from(new Set(rows.flatMap((row) => Object.keys(row))))
  const csv = [
    headers.join(','),
    ...rows.map((row) => headers.map((header) => escapeCsv(row[header])).join(',')),
  ].join('\n')
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

const normalizeItems = <T = any>(payload: any): T[] => {
  if (Array.isArray(payload)) return payload as T[]
  if (Array.isArray(payload?.data)) return payload.data as T[]
  if (Array.isArray(payload?.items)) return payload.items as T[]
  return []
}

const normalizeEmployees = (payload: any) => {
  return normalizeItems(payload)
    .map((employee: any) => {
      const id = Number(employee?.id ?? employee?.employee_id ?? 0)
      const name = employee?.name ?? [employee?.first_name, employee?.last_name].filter(Boolean).join(' ').trim()
      if (!id || !name) return null
      return { id, name }
    })
    .filter(Boolean) as Array<{ id: number; name: string }>
}

const resolveDemoEmployees = async () => {
  const employees = normalizeEmployees(await platformApi.getEmployees())
  return employees.slice(0, 3)
}

const downloadSampleDocument = () => {
  const sample = `<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Sample OCR Document</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 48px; color: #111; }
    .card { border: 2px solid #111; padding: 24px; max-width: 720px; }
    h1 { margin: 0 0 16px; font-size: 28px; }
    .row { display: flex; justify-content: space-between; margin: 8px 0; }
    .muted { color: #555; }
    .total { margin-top: 18px; padding-top: 12px; border-top: 1px solid #ccc; font-weight: 700; }
  </style>
</head>
<body>
  <div class="card">
    <h1>MEDCARE CLINIC</h1>
    <div class="muted">123 Health Ave, New York</div>
    <p><strong>Date:</strong> 15/05/2026</p>
    <p><strong>Invoice #:</strong> INV-99283</p>
    <p><strong>Patient:</strong> John Doe</p>
    <div class="row"><span>Consultation</span><span>$150.00</span></div>
    <div class="row"><span>Lab Tests</span><span>$50.00</span></div>
    <div class="total">Total Due: $200.00</div>
    <p class="muted" style="margin-top: 18px;">Contact: billing@medcare.com | +1 555-0199</p>
  </div>
  <p style="margin-top: 20px; font-size: 12px; color: #555;">Tip: open this file in your browser and use Print to save as PDF for OCR testing.</p>
</body>
</html>`
  const blob = new Blob([sample], { type: 'text/html;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = 'sample-ocr-invoice.html'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

const createSampleData = async () => {
  isSeeding.value = true
  statusMessage.value = ''
  try {
    const unique = Date.now()
    const suffix = String(unique).slice(-6)
    const demoEmployees = await resolveDemoEmployees()
    if (!demoEmployees.length) {
      throw new Error('No employees available for insurance demo creation.')
    }

    const existingPolicies = normalizeItems(await insuranceApi.getPolicies())
    const existingEnrollments = normalizeItems(await insuranceApi.getEnrollments())
    let enrollment = existingEnrollments.find((item: any) => Number(item.employee_id ?? item.employee?.id ?? 0) === demoEmployees[0]?.id) as any
    let provider: any = null
    let policy: any = existingPolicies[0] ?? null

    if (!policy) {
      provider = await insuranceApi.createProvider({
        name: `Sample Provider ${suffix}`,
        contact_info: `demo-${suffix}@insurance.test`,
        is_active: true,
      }) as any

      policy = await insuranceApi.createPolicy({
        provider_id: Number(provider?.id),
        name: `Sample Policy ${suffix}`,
        policy_name: `Sample Policy ${suffix}`,
        coverage_details: 'Medical coverage for demo testing',
        premium: 180,
        is_active: true,
      }) as any
    }

    const policyId = Number(policy?.id)
    const enrollmentMap = new Map<string, any>()
    for (const existing of existingEnrollments) {
      const employeeId = Number(existing?.employee_id ?? existing?.employee?.id ?? 0)
      if (employeeId) {
        enrollmentMap.set(String(employeeId), existing)
      }
    }

    for (const employee of demoEmployees) {
      if (!enrollmentMap.has(String(employee.id))) {
        const createdEnrollment = await insuranceApi.createEnrollment({
          employee_id: employee.id,
          policy_id: policyId,
          start_date: new Date().toISOString().slice(0, 10),
          end_date: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
          status: 'active',
        }) as any
        enrollmentMap.set(String(employee.id), createdEnrollment)
      }
    }

    enrollment = enrollmentMap.get(String(demoEmployees[0].id))

    await insuranceApi.submitClaim({
      enrollment_id: Number(enrollment?.id),
      claim_number: `CLM-${suffix}`,
      date_filed: new Date().toISOString().slice(0, 10),
      total_amount: 200,
      claim_date: new Date().toISOString().slice(0, 10),
      claimed_amount: 200,
    })

    statusMessage.value = 'Sample provider, policy, enrollment, and claim created successfully.'
    await fetchData()
    lastRefreshedAt.value = new Date().toLocaleString()
  } catch (error) {
    const suffix = String(Date.now()).slice(-6)
    providers.value = [{
      id: `local-provider-${suffix}`,
      name: `Demo Provider ${suffix}`,
      provider_name: `Demo Provider ${suffix}`,
      provider_code: `DP-${suffix}`,
      status: 'active',
    }]
    policies.value = [{
      id: `local-policy-${suffix}`,
      policy_name: `Demo Policy ${suffix}`,
      name: `Demo Policy ${suffix}`,
      provider: { name: `Demo Provider ${suffix}` },
      coverage_details: 'Medical coverage for demo testing',
      premium_amount: 180,
      is_active: true,
    }]
    claims.value = [{
      id: `local-claim-${suffix}`,
      claim_number: `CLM-${suffix}`,
      employee_name: 'Demo Employee',
      enrollment_id: 1,
      claimed_amount: 200,
      total_amount: 200,
      status: 'pending',
      claim_date: new Date().toISOString().slice(0, 10),
      date_filed: new Date().toISOString().slice(0, 10),
    }]
    documents.value = [{
      id: `local-doc-${suffix}`,
      filename: 'sample-ocr-invoice.html',
      document_category: 'Medical Invoice',
      confidence_score: 0.88,
      created_at: new Date().toISOString(),
    }]
    statusMessage.value = 'Backend demo creation was unavailable, so local demo records were loaded for testing.'
    lastRefreshedAt.value = new Date().toLocaleString()
  } finally {
    isSeeding.value = false
  }
}

const fetchData = async () => {
  isLoading.value = true
  try {
    const [providerData, policyData, claimData, documentData] = await Promise.all([
      insuranceApi.getProviders(),
      insuranceApi.getPolicies(),
      insuranceApi.getClaims(),
      insuranceApi.getClaimDocuments(),
    ])
    providers.value = Array.isArray(providerData) ? providerData : []
    policies.value = Array.isArray(policyData) ? policyData : []
    claims.value = Array.isArray(claimData) ? claimData : []
    documents.value = Array.isArray(documentData) ? documentData : []
    lastRefreshedAt.value = new Date().toLocaleString()
  } catch (error) {
    console.error('Failed to load insurance hub', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchData)

const refreshHub = async () => {
  statusMessage.value = 'Refreshing insurance hub...'
  await fetchData()
  statusMessage.value = 'Insurance hub refreshed.'
}

const providerCsvRows = computed(() => mapRows(providers.value, (provider) => ({
  id: provider.id,
  name: provider.name ?? provider.provider_name ?? '',
  source: String(provider.id ?? '').startsWith('local-provider-') ? 'Local Demo' : 'Backend',
  code: provider.provider_code ?? provider.code ?? '',
  status: provider.status ?? '',
})))

const policyCsvRows = computed(() => mapRows(policies.value, (policy) => ({
  id: policy.id,
  name: policy.policy_name ?? policy.name ?? '',
  source: String(policy.id ?? '').startsWith('local-policy-') ? 'Local Demo' : 'Backend',
  provider: policy.provider?.name ?? policy.provider_name ?? '',
  coverage: policy.coverage_details ?? policy.coverage_type ?? '',
  premium_amount: policy.premium_amount ?? policy.premium ?? 0,
  status: policy.is_active ? 'active' : policy.status ?? 'inactive',
})))

const claimCsvRows = computed(() => mapRows(claims.value, (claim) => ({
  id: claim.id,
  claim_number: claim.claim_number ?? '',
  source: String(claim.id ?? '').startsWith('local-claim-') ? 'Local Demo' : 'Backend',
  employee: (claim.enrollment?.employee?.name || claim.enrollment?.employee?.full_name || (claim.enrollment?.employee?.first_name + ' ' + claim.enrollment?.employee?.last_name) || claim.employee?.name || claim.employee?.full_name || claim.employee_name) ?? '',
  enrollment_id: claim.enrollment_id ?? '',
  amount: claim.claimed_amount ?? claim.total_amount ?? 0,
  status: claim.status ?? '',
  date_filed: claim.claim_date ?? claim.date_filed ?? '',
})))

const documentCsvRows = computed(() => mapRows(documents.value, (doc) => ({
  id: doc.id,
  filename: doc.filename ?? doc.file_name ?? '',
  source: String(doc.id ?? '').startsWith('local-doc-') ? 'Local Demo' : 'Backend',
  category: doc.classification?.category ?? doc.document_category ?? '',
  confidence: doc.classification?.confidence ?? doc.confidence_score ?? '',
  risk: doc.fraud?.risk_tier ?? '',
  created_at: doc.created_at ?? doc.timestamp ?? '',
})))
</script>

<template>
  <div class="space-y-6">
    <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50 p-6 shadow-sm dark:border-slate-800 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="space-y-2">
          <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300">
            <FileBadge class="h-3.5 w-3.5" />
            Insurance Hub
          </div>
          <h2 class="text-3xl font-bold tracking-tight">Insurance Pages</h2>
          <p class="max-w-2xl text-slate-600 dark:text-slate-400">
            Open policies, claims, and document processing from one place. You can also download the records currently loaded from Laravel.
          </p>
          <div class="flex flex-wrap items-center gap-2 pt-2">
            <Button variant="outline" class="border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-900" :disabled="isLoading" @click="refreshHub">
              <RefreshCw class="mr-2 h-4 w-4" /> Refresh Hub
            </Button>
            <Badge variant="secondary">Last refresh: {{ lastRefreshedAt || 'just now' }}</Badge>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div v-for="item in stats" :key="item.label" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ item.label }}</div>
            <div class="mt-1 text-2xl font-bold">{{ item.value }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.1fr_0.95fr_0.95fr]">
      <Card class="h-full">
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><LayoutGrid class="h-5 w-5 text-blue-600" /> Insurance Pages</CardTitle>
          <CardDescription>Direct access to the insurance section pages.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3">
          <Button v-if="isAdmin" class="w-full" @click="router.push('/assurance/plans')">Open Insurance Plans</Button>
          <Button v-if="isAdmin" class="w-full" variant="outline" @click="router.push('/assurance/policies')">Open Policies</Button>
          <Button class="w-full" variant="outline" @click="router.push(claimsLink)">{{ isAdmin ? 'Open All Claims' : 'My Claims' }}</Button>
        </CardContent>
      </Card>

      <Card class="h-full">
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><Download class="h-5 w-5 text-emerald-600" /> Download Records</CardTitle>
          <CardDescription>Export the currently loaded insurance records as JSON.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3">
          <Button class="w-full bg-emerald-600 hover:bg-emerald-700" :disabled="isLoading" @click="downloadJson('insurance-providers', providers)">Download Providers JSON</Button>
          <Button class="w-full bg-emerald-600 hover:bg-emerald-700" :disabled="isLoading" @click="downloadCsv('insurance-providers', providerCsvRows)">Download Providers CSV</Button>
          <Button class="w-full bg-emerald-600 hover:bg-emerald-700" :disabled="isLoading" @click="downloadJson('insurance-policies', policies)">Download Policies JSON</Button>
          <Button class="w-full bg-emerald-600 hover:bg-emerald-700" :disabled="isLoading" @click="downloadCsv('insurance-policies', policyCsvRows)">Download Policies CSV</Button>
          <Button class="w-full bg-emerald-600 hover:bg-emerald-700" :disabled="isLoading" @click="downloadJson('insurance-claims', claims)">Download Claims JSON</Button>
          <Button class="w-full bg-emerald-600 hover:bg-emerald-700" :disabled="isLoading" @click="downloadCsv('insurance-claims', claimCsvRows)">Download Claims CSV</Button>
          <Button class="w-full bg-emerald-600 hover:bg-emerald-700" :disabled="isLoading" @click="downloadJson('insurance-documents', documents)">Download Documents JSON</Button>
          <Button class="w-full bg-emerald-600 hover:bg-emerald-700" :disabled="isLoading" @click="downloadCsv('insurance-documents', documentCsvRows)">Download Documents CSV</Button>
        </CardContent>
      </Card>

      <Card class="h-full">
        <CardHeader>
          <CardTitle class="flex items-center gap-2"><WandSparkles class="h-5 w-5 text-violet-600" /> Test Scenarios</CardTitle>
          <CardDescription>Use these pages to validate AI and insurance workflows.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-2 text-sm text-slate-600 dark:text-slate-400">
          <div class="rounded-xl border border-dashed border-slate-200 p-3 dark:border-slate-800">Create a claim in <span class="font-semibold">Claims</span> and run anomaly detection.</div>
          <div class="rounded-xl border border-dashed border-slate-200 p-3 dark:border-slate-800">Upload a PDF in <span class="font-semibold">Document Lab</span> to test OCR/classification/fraud.</div>
          <div class="rounded-xl border border-dashed border-slate-200 p-3 dark:border-slate-800">Review policies in <span class="font-semibold">Policies</span> before creating new coverage.</div>
          <div class="pt-2 space-y-2">
            <Button class="w-full" @click="createSampleData" :disabled="isLoading || isSeeding">
              {{ isSeeding ? 'Creating Sample Data...' : 'Generate Full Demo Set' }}
            </Button>
            <Button class="w-full" variant="outline" @click="downloadSampleDocument">
              Download Sample OCR Document
            </Button>
            <Button class="w-full" variant="outline" @click="refreshHub" :disabled="isLoading">
              Refresh After Demo
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <div v-if="statusMessage" class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700 dark:border-blue-900/60 dark:bg-blue-950/40 dark:text-blue-300">
      {{ statusMessage }}
    </div>

    <Card class="overflow-hidden">
      <CardHeader>
        <CardTitle class="flex items-center gap-2"><ShieldCheck class="h-5 w-5 text-rose-600" /> Quick Preview</CardTitle>
        <CardDescription>Recent insurance records loaded from the backend.</CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoading" class="py-8 text-center text-slate-500">Loading insurance data...</div>
        <div v-else class="grid gap-3 lg:grid-cols-3">
          <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/60" v-for="claim in claims.slice(0, 3)" :key="claim.id">
            <div class="flex items-center justify-between gap-3">
              <div>
                <div class="font-semibold">{{ claim.claim_number || `Claim #${claim.id}` }}</div>
                <div class="text-sm font-medium text-slate-900 dark:text-white">{{ (claim.enrollment?.employee?.name || claim.enrollment?.employee?.full_name || (claim.enrollment?.employee?.first_name + ' ' + claim.enrollment?.employee?.last_name) || claim.employee?.name || claim.employee?.full_name || claim.employee_name) ?? 'Unknown Employee' }}</div>
                <div class="text-xs text-slate-500">{{ claim.status || 'pending' }}</div>
              </div>
              <div class="flex flex-col items-end gap-1">
                <Badge variant="secondary">{{ claim.claimed_amount || claim.total_amount || 'n/a' }}</Badge>
                <Badge :variant="String(claim.id ?? '').startsWith('local-') ? 'warning' : 'secondary'">
                  {{ String(claim.id ?? '').startsWith('local-') ? 'Local Demo' : 'Backend' }}
                </Badge>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
