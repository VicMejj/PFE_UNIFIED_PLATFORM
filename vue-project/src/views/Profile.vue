<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { Camera, Mail, Save, Shield, UserRound } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { useAuthStore } from '@/stores/auth'
import { getAvatarUrl } from '@/api/http'

const auth = useAuthStore()
const isSaving = ref(false)
const isUploading = ref(false)
const feedback = ref('')
const errorMsg = ref('')

const form = reactive({
  name: '',
  email: '',
  lang: 'en'
})

watch(
  () => auth.user,
  (user) => {
    form.name = user?.name ?? ''
    form.email = user?.email ?? ''
    form.lang = user?.lang ?? 'en'
  },
  { immediate: true }
)

const avatarUrl = computed(() => getAvatarUrl(auth.user))
const userInitials = computed(() =>
  (auth.user?.name ?? 'User')
    .split(' ')
    .map((part) => part[0])
    .join('')
    .slice(0, 2)
    .toUpperCase()
)

async function submitProfile() {
  isSaving.value = true
  feedback.value = ''
  errorMsg.value = ''

  try {
    await auth.updateUserProfile({ ...form })
    feedback.value = 'Profile updated successfully.'
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to update your profile right now.'
  } finally {
    isSaving.value = false
  }
}

async function handleAvatarChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  isUploading.value = true
  feedback.value = ''
  errorMsg.value = ''

  try {
    await auth.updateAvatar(file)
    feedback.value = 'Profile photo updated successfully.'
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to upload the new photo.'
  } finally {
    isUploading.value = false
    input.value = ''
  }
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Profile</h1>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Keep your account details, photo, and contact information up to date.
      </p>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
      <Card>
        <CardHeader>
          <CardTitle>Account Details</CardTitle>
          <CardDescription>These details appear across the platform and notifications.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <div class="grid gap-5 md:grid-cols-2">
            <div class="space-y-2">
              <Label for="profile-name">Full Name</Label>
              <Input id="profile-name" v-model="form.name" placeholder="Your full name" />
            </div>
            <div class="space-y-2">
              <Label for="profile-email">Email</Label>
              <Input id="profile-email" v-model="form.email" type="email" placeholder="you@company.com" />
            </div>
          </div>

          <div class="space-y-2">
            <Label for="profile-language">Language</Label>
            <select
              id="profile-language"
              v-model="form.lang"
              class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
              <option value="en">English</option>
              <option value="fr">French</option>
              <option value="ar">Arabic</option>
            </select>
          </div>

          <div class="flex justify-end">
            <Button :disabled="isSaving" @click="submitProfile">
              <Save class="mr-2 h-4 w-4" />
              {{ isSaving ? 'Saving...' : 'Save Profile' }}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Photo & Access</CardTitle>
          <CardDescription>Manage your profile photo and review your current access level.</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
          <div class="flex items-center gap-4">
            <div class="flex h-20 w-20 items-center justify-center overflow-hidden rounded-3xl bg-slate-200 text-lg font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
              <img v-if="avatarUrl" :src="avatarUrl" alt="Profile photo" class="h-full w-full object-cover" />
              <span v-else>{{ userInitials }}</span>
            </div>
            <div class="space-y-2">
              <label class="inline-flex cursor-pointer items-center rounded-md border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800">
                <Camera class="mr-2 h-4 w-4" />
                {{ isUploading ? 'Uploading...' : 'Change Photo' }}
                <input type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
              </label>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                PNG or JPG up to 2 MB.
              </p>
            </div>
          </div>

          <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/70">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
              <Shield class="h-4 w-4 text-emerald-500" />
              Account Access
            </div>
            <div class="mt-4 space-y-3 text-sm text-slate-600 dark:text-slate-300">
              <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-2">
                  <UserRound class="h-4 w-4" />
                  Primary role
                </span>
                <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                  {{ auth.user?.role?.replace('_', ' ') || 'employee' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="inline-flex items-center gap-2">
                  <Mail class="h-4 w-4" />
                  Email status
                </span>
                <span class="text-emerald-600 dark:text-emerald-400">
                  {{ auth.user?.email_verified_at ? 'Verified' : 'Pending verification' }}
                </span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
