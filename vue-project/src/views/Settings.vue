<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { LockKeyhole, MonitorCog, Palette, Save, ShieldCheck } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import { platformApi } from '@/api/laravel/platform'
import { useTheme } from '@/composables/useTheme'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const themeState = useTheme()
const companySettings = ref<any>(null)
const isSavingPreferences = ref(false)
const isSavingPassword = ref(false)
const feedback = ref('')
const errorMsg = ref('')

const preferencesForm = reactive({
  lang: 'en',
  messenger_color: '#2563eb'
})

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})

watch(
  () => auth.user,
  (user) => {
    preferencesForm.lang = user?.lang ?? 'en'
    preferencesForm.messenger_color = user?.messenger_color ?? '#2563eb'
  },
  { immediate: true }
)

const currentTheme = computed(() => themeState.theme.value)

onMounted(async () => {
  try {
    companySettings.value = await platformApi.getSettings()
  } catch (error) {
    console.error('Unable to load settings', error)
  }
})

async function savePreferences() {
  isSavingPreferences.value = true
  feedback.value = ''
  errorMsg.value = ''

  try {
    await auth.updatePreferences({
      ...preferencesForm,
      dark_mode: currentTheme.value === 'dark'
    })
    feedback.value = 'Settings saved successfully.'
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to save your settings.'
  } finally {
    isSavingPreferences.value = false
  }
}

async function changePassword() {
  isSavingPassword.value = true
  feedback.value = ''
  errorMsg.value = ''

  try {
    await auth.updatePassword({ ...passwordForm })
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    feedback.value = 'Password updated successfully.'
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message ?? 'Unable to update your password.'
  } finally {
    isSavingPassword.value = false
  }
}

function chooseTheme(nextTheme: 'light' | 'dark') {
  themeState.setTheme(nextTheme)
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Settings</h1>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Control your appearance, account security, and workspace preferences.
      </p>
    </div>

    <div v-if="feedback" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
      {{ feedback }}
    </div>
    <div v-if="errorMsg" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300">
      {{ errorMsg }}
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Palette class="h-5 w-5 text-sky-500" />
              Appearance
            </CardTitle>
            <CardDescription>Default mode is light. Your choice now follows you from landing page to login and dashboard.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-5">
            <div class="grid gap-3 md:grid-cols-2">
              <button
                type="button"
                class="rounded-2xl border p-4 text-left transition"
                :class="currentTheme === 'light'
                  ? 'border-sky-500 bg-sky-50 dark:border-sky-400 dark:bg-sky-950/40'
                  : 'border-slate-200 dark:border-slate-700'"
                @click="chooseTheme('light')"
              >
                <div class="text-sm font-semibold text-slate-900 dark:text-white">Light mode</div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">Clean default workspace across all pages.</div>
              </button>
              <button
                type="button"
                class="rounded-2xl border p-4 text-left transition"
                :class="currentTheme === 'dark'
                  ? 'border-sky-500 bg-sky-50 dark:border-sky-400 dark:bg-sky-950/40'
                  : 'border-slate-200 dark:border-slate-700'"
                @click="chooseTheme('dark')"
              >
                <div class="text-sm font-semibold text-slate-900 dark:text-white">Dark mode</div>
                <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">Great for long sessions and lower light environments.</div>
              </button>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="settings-lang">Language</Label>
                <select
                  id="settings-lang"
                  v-model="preferencesForm.lang"
                  class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
                  <option value="en">English</option>
                  <option value="fr">French</option>
                  <option value="ar">Arabic</option>
                </select>
              </div>
              <div class="space-y-2">
                <Label for="settings-color">Accent Color</Label>
                <Input id="settings-color" v-model="preferencesForm.messenger_color" type="color" class="h-10 p-1" />
              </div>
            </div>

            <div class="flex justify-end">
              <Button :disabled="isSavingPreferences" @click="savePreferences">
                <Save class="mr-2 h-4 w-4" />
                {{ isSavingPreferences ? 'Saving...' : 'Save Preferences' }}
              </Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <LockKeyhole class="h-5 w-5 text-amber-500" />
              Security
            </CardTitle>
            <CardDescription>All users can change their password here.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="current-password">Current Password</Label>
              <Input id="current-password" v-model="passwordForm.current_password" type="password" />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="new-password">New Password</Label>
                <Input id="new-password" v-model="passwordForm.password" type="password" />
              </div>
              <div class="space-y-2">
                <Label for="confirm-password">Confirm Password</Label>
                <Input id="confirm-password" v-model="passwordForm.password_confirmation" type="password" />
              </div>
            </div>
            <div class="flex justify-end">
              <Button :disabled="isSavingPassword" @click="changePassword">
                <ShieldCheck class="mr-2 h-4 w-4" />
                {{ isSavingPassword ? 'Updating...' : 'Update Password' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <MonitorCog class="h-5 w-5 text-emerald-500" />
              Workspace Snapshot
            </CardTitle>
            <CardDescription>Read-only platform settings pulled from Laravel.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4 text-sm">
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
              <div class="font-semibold text-slate-900 dark:text-white">Company</div>
              <div class="mt-3 space-y-2 text-slate-600 dark:text-slate-300">
                <div>Name: {{ companySettings?.company?.name || 'Not configured' }}</div>
                <div>Email: {{ companySettings?.company?.email || 'Not configured' }}</div>
                <div>Phone: {{ companySettings?.company?.phone || 'Not configured' }}</div>
              </div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
              <div class="font-semibold text-slate-900 dark:text-white">System</div>
              <div class="mt-3 space-y-2 text-slate-600 dark:text-slate-300">
                <div>Timezone: {{ companySettings?.system?.timezone || 'UTC' }}</div>
                <div>Locale: {{ companySettings?.system?.locale || 'en' }}</div>
                <div>Currency: {{ companySettings?.system?.currency || 'USD' }}</div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
