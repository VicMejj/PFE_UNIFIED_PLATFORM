<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Bell, CalendarDays, Download, FileSignature, Gift, Settings, UserCircle2 } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import { useAuthStore } from '@/stores/auth'
import { getAvatarUrl } from '@/api/http'
import { platformApi, type ContractItem } from '@/api/laravel/platform'

const auth = useAuthStore()
const avatarUrl = computed(() => getAvatarUrl(auth.user))
const contracts = ref<ContractItem[]>([])
const isLoadingContracts = ref(false)

const shortcuts = [
  { title: 'Edit Profile', description: 'Update your personal information and photo.', href: '/profile', icon: UserCircle2 },
  { title: 'Leave Requests', description: 'Submit or track time off requests.', href: '/leave-requests', icon: CalendarDays },
  { title: 'Contract Review', description: 'Open and confirm assigned contracts with your verification code.', href: '/contract-review', icon: FileSignature },
  { title: 'Benefits', description: 'Review the current benefit catalog.', href: '/social/benefits', icon: Gift },
  { title: 'Notifications', description: 'Check account and leave updates.', href: '/notifications', icon: Bell },
  { title: 'Settings', description: 'Change password and appearance preferences.', href: '/settings', icon: Settings }
]

const signedContracts = computed(() =>
  Array.isArray(contracts.value)
    ? contracts.value.filter(contract => contract.status === 'signed')
    : []
)

async function loadContracts() {
  isLoadingContracts.value = true
  try {
    const data = await platformApi.getContracts()
    contracts.value = data
  } catch (error) {
    console.error('Failed to load contracts:', error)
  } finally {
    isLoadingContracts.value = false
  }
}

async function downloadContract(contract: ContractItem) {
  try {
    const blob = await platformApi.downloadContract(contract.id)
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `contract-${contract.id}.pdf`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
  } catch (error) {
    console.error('Download failed:', error)
    alert('Failed to download contract. Please try again.')
  }
}

onMounted(() => {
  loadContracts()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">My Workspace</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Everything a user asked for is now reachable from here: profile, contracts, settings, leave, benefits, and notifications.
        </p>
      </div>
      <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
        <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
          <img v-if="avatarUrl" :src="avatarUrl" alt="Profile photo" class="h-full w-full object-cover" />
          <span v-else class="text-sm font-semibold text-slate-700 dark:text-slate-200">
            {{ auth.user?.name?.slice(0, 1) || 'U' }}
          </span>
        </div>
        <div>
          <div class="font-semibold text-slate-900 dark:text-white">{{ auth.user?.name }}</div>
          <div class="text-sm text-slate-500 dark:text-slate-400">{{ auth.user?.email }}</div>
        </div>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Card v-for="shortcut in shortcuts" :key="shortcut.title">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <component :is="shortcut.icon" class="h-5 w-5 text-sky-500" />
            {{ shortcut.title }}
          </CardTitle>
          <CardDescription>{{ shortcut.description }}</CardDescription>
        </CardHeader>
        <CardContent>
          <Button class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" @click="$router.push(shortcut.href)">
            Open
          </Button>
        </CardContent>
      </Card>
    </div>

    <Card>
      <CardHeader>
        <CardTitle>My Signed Contracts</CardTitle>
        <CardDescription>
          Download your signed contracts as PDF files.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div v-if="isLoadingContracts" class="py-8 text-center text-sm text-slate-500">
          Loading contracts...
        </div>
        <div v-else-if="!signedContracts.length" class="py-8 text-center text-sm text-slate-500">
          No signed contracts found.
        </div>
        <div v-else class="space-y-4">
          <div
            v-for="contract in signedContracts"
            :key="contract.id"
            class="flex items-center justify-between rounded-lg border border-slate-200 p-4 dark:border-slate-700"
          >
            <div>
              <div class="font-medium text-slate-900 dark:text-white">
                {{ contract.title || `Contract #${contract.id}` }}
              </div>
              <div class="text-sm text-slate-500 dark:text-slate-400">
                Signed on {{ new Date(contract.signed_at ?? contract.updated_at ?? '').toLocaleDateString() }}
              </div>
            </div>
            <Button
              class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
              @click="downloadContract(contract)"
            >
              <Download class="mr-2 h-4 w-4" />
              Download PDF
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
