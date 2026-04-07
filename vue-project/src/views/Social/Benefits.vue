<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Gift, Plus, Sparkles } from 'lucide-vue-next'
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
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const items = ref<any[]>([])
const isCreating = ref(false)
const feedback = ref('')
const errorMsg = ref('')
const isLoading = ref(true)
const searchQuery = ref('')
const assignedBenefits = ref<any[]>([])

const form = reactive({
  name: '',
  description: '',
  is_active: true
})

const columns = [
  { key: 'name', label: 'Benefit Name' },
  { key: 'description', label: 'Description' },
  { key: 'status', label: 'Status' }
]

const userRoles = computed(() =>
  [auth.user?.role, ...(auth.user?.allRoles ?? [])]
    .filter(Boolean)
    .map((role) => String(role).toLowerCase())
)

const canManageBenefits = computed(() =>
  userRoles.value.some((role) => ['admin', 'rh_manager', 'rh', 'hr', 'manager'].includes(role))
)
const isEmployeeView = computed(() => auth.user?.role === 'employee')

const filteredItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  const source = isEmployeeView.value ? assignedBenefits.value : items.value

  if (!query) return source

  return source.filter((item) =>
    [item.name, item.description, item.status]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()
      .includes(query)
  )
})

const benefitStats = computed(() => [
  {
    label: isEmployeeView.value ? 'Assigned benefits' : 'Benefit catalog',
    value: isEmployeeView.value ? assignedBenefits.value.length : items.value.length,
    description: isEmployeeView.value ? 'Benefits currently assigned to your profile.' : 'Programs currently listed in the platform.',
    icon: Gift,
    color: 'bg-emerald-500'
  },
  {
    label: isEmployeeView.value ? 'Active assignments' : 'Active benefits',
    value: (isEmployeeView.value ? assignedBenefits.value : items.value).filter((item) => item.status === 'Active').length,
    description: isEmployeeView.value ? 'Benefits that are currently active for you.' : 'Visible and ready for employee use.',
    icon: Sparkles,
    color: 'bg-sky-500'
  }
])

async function loadBenefits() {
  isLoading.value = true
  errorMsg.value = ''

  try {
    if (isEmployeeView.value) {
      const assignedData: any = await platformApi.getMyAllowances()
      const assignedItems = Array.isArray(assignedData)
        ? assignedData
        : Array.isArray(assignedData?.data)
          ? assignedData.data
          : []

      assignedBenefits.value = assignedItems.map((item: any) => ({
        ...item,
        name: item.allowanceOption?.name || item.allowance_option?.name || item.name || 'Benefit',
        description: item.allowanceOption?.description || item.allowance_option?.description || 'Assigned benefit',
        status: item.status === 'active' ? 'Active' : item.status === 'pending' ? 'Pending' : 'Inactive'
      }))
      items.value = []
    } else {
      const benefitData = await platformApi.getAllowanceOptions()
      items.value = unwrapItems<any>(benefitData).map((item) => ({
        ...item,
        description: item.description || 'No description provided',
        status: item.is_active ? 'Active' : 'Inactive'
      }))
      assignedBenefits.value = []
    }
  } catch (error) {
    console.error('Unable to load benefits', error)
    errorMsg.value = isEmployeeView.value
      ? 'Unable to load your assigned benefits right now.'
      : 'Unable to load the benefits catalog right now.'
  } finally {
    isLoading.value = false
  }
}

async function createBenefit() {
  errorMsg.value = ''
  feedback.value = ''

  try {
    await platformApi.createAllowanceOption({
      name: form.name,
      description: form.description,
      is_active: form.is_active
    })
    feedback.value = 'Benefit created successfully.'
    isCreating.value = false
    form.name = ''
    form.description = ''
    form.is_active = true
    await loadBenefits()
  } catch (error) {
    console.error('Unable to create benefit', error)
    errorMsg.value = 'Unable to save the benefit right now.'
  }
}

onMounted(loadBenefits)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div class="flex items-center gap-3">
        <Gift class="w-8 h-8 text-emerald-500" />
        <div>
          <h2 class="text-3xl font-bold tracking-tight">Social Benefits</h2>
          <p class="text-gray-500 dark:text-gray-400">
            {{ isEmployeeView ? 'Review only the benefits assigned to your profile.' : 'Browse the benefit catalog and keep employee perks organized.' }}
          </p>
        </div>
      </div>
      <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <Button v-if="canManageBenefits" class="bg-emerald-600 hover:bg-emerald-700 text-white" @click="router.push('/social/employee-benefits')">
          <Plus class="w-4 h-4 mr-2" /> Manage Employee Benefits
        </Button>
        <Button v-if="canManageBenefits" class="bg-slate-700 hover:bg-slate-800 text-white" @click="isCreating = !isCreating">
          <Plus class="w-4 h-4 mr-2" /> {{ isCreating ? 'Close Form' : 'Add Benefit' }}
        </Button>
      </div>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <Card v-for="card in benefitStats" :key="card.label">
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

    <Card v-if="isCreating && canManageBenefits">
      <CardHeader>
        <CardTitle>Create Benefit</CardTitle>
        <CardDescription>The Add Benefit action now creates allowance options in Laravel.</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <Label>Benefit Name</Label>
          <Input v-model="form.name" placeholder="Annual wellness stipend" />
        </div>
        <div class="space-y-2">
          <Label>Status</Label>
          <select v-model="form.is_active" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm">
            <option :value="true">Active</option>
            <option :value="false">Inactive</option>
          </select>
        </div>
        <div class="space-y-2 md:col-span-2">
          <Label>Description</Label>
          <Input v-model="form.description" placeholder="Describe the benefit and who it applies to" />
        </div>
        <div class="md:col-span-2 flex justify-end">
          <Button @click="createBenefit">Save Benefit</Button>
        </div>
      </CardContent>
    </Card>

    <Card>
      <CardContent class="pt-6">
        <DataTable 
          :columns="columns" 
          :data="filteredItems"
          :loading="isLoading"
          :searchPlaceholder="isEmployeeView ? 'Search your assigned benefits...' : 'Search benefits by name or description...'"
          :emptyMessage="isEmployeeView ? 'No benefits are assigned to you yet.' : 'No benefits are available yet.'"
          @search="searchQuery = $event"
        >
          <template #cell(status)="{ value }">
            <Badge :variant="value === 'Active' ? 'success' : 'secondary'">{{ value }}</Badge>
          </template>
        </DataTable>
      </CardContent>
    </Card>
  </div>
</template>
