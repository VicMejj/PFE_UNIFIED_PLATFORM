<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { Plus, Shield } from 'lucide-vue-next'
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
import { createPolicy, getPolicies, getProviders } from '@/api/laravel/insurance'
import { useAuthStore } from '@/stores/auth'

const items = ref<any[]>([])
const providers = ref<any[]>([])
const isLoading = ref(true)
const isCreating = ref(false)
const feedback = ref('')
const errorMsg = ref('')
const searchQuery = ref('')
const auth = useAuthStore()

const canManagePolicies = computed(() => ['admin', 'rh_manager'].includes(auth.user?.role ?? ''))

const form = reactive({
  provider_id: '',
  policy_name: '',
  coverage_details: '',
  premium_amount: '',
  is_active: true,
})

const columns = [
  { key: 'policy_name', label: 'Policy' },
  { key: 'provider', label: 'Provider' },
  { key: 'coverage_type', label: 'Coverage' },
  { key: 'premium_amount', label: 'Premium' },
  { key: 'status', label: 'Status' }
]

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()

  if (!query) return items.value

  return items.value.filter((item) =>
    [item.policy_name, item.provider, item.coverage_type, item.premium_amount, item.status]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

const fetchPolicies = async () => {
  isLoading.value = true
  try {
    const [policyData, providerData] = await Promise.all([
      getPolicies(),
      getProviders(),
    ])

    providers.value = providerData
    items.value = policyData.map((item: any) => ({
      ...item,
      policy_name: item.policy_name || item.name || `Policy #${item.id}`,
      provider: item.provider?.name || 'No provider',
      coverage_type: item.coverage_details || 'Coverage details not provided',
      premium_amount: item.premium_amount || item.premium ? `$${Number(item.premium_amount || item.premium).toLocaleString()}` : 'Not set',
      status: item.is_active ? 'Active' : 'Inactive',
    }))
  } catch (err) {
    console.error('Failed to fetch policies', err)
    errorMsg.value = 'Unable to load insurance policies right now.'
  } finally {
    isLoading.value = false
  }
}

async function savePolicy() {
  errorMsg.value = ''
  feedback.value = ''

  try {
    await createPolicy({
      provider_id: Number(form.provider_id),
      policy_name: form.policy_name,
      coverage_details: form.coverage_details,
      premium_amount: Number(form.premium_amount),
      is_active: form.is_active,
    })
    feedback.value = 'Policy created successfully.'
    isCreating.value = false
    form.provider_id = ''
    form.policy_name = ''
    form.coverage_details = ''
    form.premium_amount = ''
    form.is_active = true
    await fetchPolicies()
  } catch (error) {
    console.error('Unable to create policy', error)
    errorMsg.value = 'Unable to create the policy right now.'
  }
}

const getStatusVariant = (status: string) => status === 'Active' ? 'success' : 'destructive'

onMounted(fetchPolicies)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <Shield class="w-8 h-8 text-blue-600" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Insurance Policies</h2>
          <p class="text-gray-500 dark:text-gray-400">Manage insurance plans with live provider and policy data from Laravel.</p>
        </div>
      </div>
      <Button v-if="canManagePolicies" class="bg-blue-600" @click="isCreating = !isCreating"><Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'Add Policy' }}</Button>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <Card v-if="isCreating && canManagePolicies">
      <CardHeader>
        <CardTitle>Create Policy</CardTitle>
        <CardDescription>Add a policy using the provider and policy endpoints already available in Laravel.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <Label>Provider</Label>
          <select v-model="form.provider_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option value="">Select provider</option>
            <option v-for="provider in providers" :key="provider.id" :value="String(provider.id)">{{ provider.name }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <Label>Policy name</Label>
          <Input v-model="form.policy_name" placeholder="Health Plus Plan" />
        </div>
        <div class="space-y-2 md:col-span-2">
          <Label>Coverage details</Label>
          <Input v-model="form.coverage_details" placeholder="Medical, dental, and wellness coverage" />
        </div>
        <div class="space-y-2">
          <Label>Premium amount</Label>
          <Input v-model="form.premium_amount" type="number" placeholder="450" />
        </div>
        <div class="space-y-2">
          <Label>Status</Label>
          <select v-model="form.is_active" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </div>
        <div class="md:col-span-2 flex justify-end">
          <Button @click="savePolicy">Save Policy</Button>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="filteredItems" 
          :loading="isLoading"
          searchPlaceholder="Search policies or providers..."
          @search="searchQuery = $event"
        >
          <template #cell(status)="{ value }">
            <Badge :variant="getStatusVariant(value)">{{ value }}</Badge>
          </template>
        </DataTable>
      </CardContent>
    </Card>
  </div>
</template>
