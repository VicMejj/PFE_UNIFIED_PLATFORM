<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { CheckCircle2, Download, FileSignature, ShieldCheck, XCircle } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { platformApi } from '@/api/laravel/platform'

const form = reactive({
  contract_id: '',
  verification_code: '',
  rejection_reason: ''
})

const contract = ref<any | null>(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const feedback = ref('')
const errorMessage = ref('')

const normalizedCode = computed(() => form.verification_code.trim().toUpperCase())
const canActOnContract = computed(() => {
  const status = String(contract.value?.status || '').toLowerCase()
  return Boolean(contract.value) && !['signed', 'rejected', 'expired'].includes(status)
})

function statusVariant(status: string) {
  switch (String(status).toLowerCase()) {
    case 'signed':
      return 'success'
    case 'rejected':
    case 'expired':
      return 'destructive'
    case 'viewed':
      return 'default'
    default:
      return 'warning'
  }
}

async function reviewContract() {
  if (!form.contract_id || !normalizedCode.value) {
    errorMessage.value = 'Enter both the contract ID and verification code.'
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  feedback.value = ''

  try {
    contract.value = await platformApi.markContractViewed(Number(form.contract_id), {
      verification_code: normalizedCode.value
    })
    feedback.value = 'Contract loaded successfully. Review the details below, then sign or reject.'
  } catch (error: any) {
    console.error('Unable to review contract', error)
    errorMessage.value = error.response?.data?.message ?? 'Unable to review this contract.'
  } finally {
    isLoading.value = false
  }
}

async function signContract() {
  if (!contract.value) return

  isSubmitting.value = true
  errorMessage.value = ''
  feedback.value = ''

  try {
    contract.value = await platformApi.signContract({
      contract_id: contract.value.id,
      verification_code: normalizedCode.value
    })
    feedback.value = 'Contract signed successfully.'
  } catch (error: any) {
    console.error('Unable to sign contract', error)
    errorMessage.value = error.response?.data?.message ?? 'Unable to sign the contract.'
  } finally {
    isSubmitting.value = false
  }
}

async function rejectContract() {
  if (!contract.value) return

  isSubmitting.value = true
  errorMessage.value = ''
  feedback.value = ''

  try {
    contract.value = await platformApi.rejectContract(contract.value.id, {
      verification_code: normalizedCode.value,
      reason: form.rejection_reason || null
    })
    feedback.value = 'Contract rejected successfully.'
  } catch (error: any) {
    console.error('Unable to reject contract', error)
    errorMessage.value = error.response?.data?.message ?? 'Unable to reject the contract.'
  } finally {
    isSubmitting.value = false
  }
}

async function downloadContract() {
  if (!contract.value) return

  try {
    const blob = await platformApi.downloadContract(contract.value.id, {
      verification_code: normalizedCode.value,
    })
    const url = window.URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `contract-${contract.value.id}.pdf`
    document.body.appendChild(a)
    a.click()
    window.URL.revokeObjectURL(url)
    document.body.removeChild(a)
  } catch (error: any) {
    console.error('Unable to download contract', error)
    errorMessage.value = error.response?.data?.message ?? 'Unable to download the contract.'
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Contract Review</h1>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Enter the contract ID and verification code from your email to review and confirm the agreement.
      </p>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMessage" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/40 dark:text-rose-300">
      {{ errorMessage }}
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <ShieldCheck class="h-5 w-5 text-sky-500" />
            Verify Contract
          </CardTitle>
          <CardDescription>Use the one-time verification code sent by HR.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-2">
            <Label for="contract-id">Contract ID</Label>
            <Input id="contract-id" v-model="form.contract_id" type="number" placeholder="e.g. 14" />
          </div>
          <div class="space-y-2">
            <Label for="verification-code">Verification Code</Label>
            <Input id="verification-code" v-model="form.verification_code" placeholder="Enter your code" />
          </div>
          <Button class="w-full" :disabled="isLoading" @click="reviewContract">
            <FileSignature class="mr-2 h-4 w-4" />
            {{ isLoading ? 'Loading...' : 'Review Contract' }}
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Agreement Details</CardTitle>
          <CardDescription>Confirm the terms before signing or rejecting.</CardDescription>
        </CardHeader>
        <CardContent>
          <div v-if="!contract" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
            No contract loaded yet.
          </div>
          <div v-else class="space-y-5">
            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
              <div>
                <div class="text-lg font-semibold text-slate-900 dark:text-white">{{ contract.contract_name }}</div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                  Contract #{{ contract.id }} for employee ID {{ contract.employee_id }}
                </div>
              </div>
              <Badge :variant="statusVariant(contract.status)">{{ contract.status }}</Badge>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
              <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Start date</div>
                <div class="mt-1 text-base font-semibold text-slate-900 dark:text-white">{{ contract.start_date || 'N/A' }}</div>
              </div>
              <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">End date</div>
                <div class="mt-1 text-base font-semibold text-slate-900 dark:text-white">{{ contract.end_date || 'Open-ended' }}</div>
              </div>
              <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Review deadline</div>
                <div class="mt-1 text-base font-semibold text-slate-900 dark:text-white">{{ contract.signing_deadline || 'Not set' }}</div>
              </div>
              <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Viewed at</div>
                <div class="mt-1 text-base font-semibold text-slate-900 dark:text-white">{{ contract.viewed_at || 'Just now' }}</div>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-800">
              <div class="text-sm font-medium text-slate-500 dark:text-slate-400">Notes</div>
              <div class="mt-2 text-sm text-slate-700 dark:text-slate-300">
                {{ contract.notes || 'No additional notes were provided for this contract.' }}
              </div>
            </div>

            <div class="space-y-2">
              <Label for="rejection-reason">Optional rejection reason</Label>
              <textarea
                id="rejection-reason"
                v-model="form.rejection_reason"
                rows="3"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                placeholder="Share any issue that should be addressed before signing"
              ></textarea>
            </div>

            <div class="flex flex-wrap gap-3">
              <Button class="bg-emerald-600 text-white hover:bg-emerald-700" :disabled="isSubmitting || !canActOnContract" @click="signContract">
                <CheckCircle2 class="mr-2 h-4 w-4" />
                {{ isSubmitting ? 'Working...' : 'Sign Contract' }}
              </Button>
              <Button class="bg-rose-600 text-white hover:bg-rose-700" :disabled="isSubmitting || !canActOnContract" @click="rejectContract">
                <XCircle class="mr-2 h-4 w-4" />
                {{ isSubmitting ? 'Working...' : 'Reject Contract' }}
              </Button>
              <Button
                v-if="contract.status === 'signed'"
                class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                @click="downloadContract"
              >
                <Download class="mr-2 h-4 w-4" />
                Download PDF
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
