<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Eye, EyeOff, Loader2 } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardFooter from '@/components/ui/CardFooter.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import BrandMark from '@/components/common/BrandMark.vue'
import {
  getRememberedEmail,
  getRememberMePreference,
  persistRememberedEmail,
  persistRememberMePreference
} from '@/utils/authStorage'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const showPassword = ref(false)
const isLoading = ref(false)
const errorMsg = ref('')

onMounted(() => {
  rememberMe.value = getRememberMePreference()
  email.value = getRememberedEmail()
})

function resolveLoginError(error: any) {
  const message = error?.response?.data?.message
  const errors = error?.response?.data?.errors
  const fieldMessage =
    errors?.email?.[0]
    || errors?.password?.[0]
    || errors?.otp?.[0]
    || errors?.message?.[0]

  if (fieldMessage) return fieldMessage
  if (typeof message === 'string' && message.length) return message

  if (error?.response?.status === 422) {
    return 'Login was rejected by Laravel. Please check your email/password or the validation message returned by the API.'
  }

  return error?.message || 'Invalid email or password'
}

const handleSubmit = async (e: Event) => {
  e.preventDefault()
  isLoading.value = true
  errorMsg.value = ''

  try {
    const response = await auth.loginLaravel(email.value, password.value, rememberMe.value)
    persistRememberMePreference(rememberMe.value)
    persistRememberedEmail(email.value, rememberMe.value)
    
    // Redirect based on role
    const role = response.user?.role
    if (role === 'admin') router.push('/admin')
    else if (role === 'rh_manager') router.push('/rh')
    else if (role === 'manager') router.push('/manager')
    else if (role === 'employee') router.push('/employee')
    else router.push('/dashboard')
  } catch (error: any) {
    if (error.response?.status === 403 && error.response.data?.message?.includes('verified')) {
      router.push({ path: '/verify-email', query: { email: email.value } })
    } else if (error.code === 'ECONNABORTED') {
      errorMsg.value = 'Unable to reach the backend service. Please check that Laravel is running on port 8000.'
    } else {
      errorMsg.value = resolveLoginError(error)
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-transparent p-4">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.12),_transparent_32%),linear-gradient(180deg,_rgba(248,250,252,0.98),_rgba(255,255,255,0.98))] dark:bg-[radial-gradient(circle_at_top,_rgba(14,165,233,0.18),_transparent_28%),linear-gradient(180deg,_rgba(2,6,23,0.98),_rgba(15,23,42,0.98))]"></div>
    <div class="absolute right-4 top-4">
      <ThemeToggle />
    </div>
    <Card class="relative w-full max-w-md border-white/70 bg-white/92 shadow-2xl shadow-slate-200/70 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90 dark:shadow-slate-950/50">
      <CardHeader class="space-y-3">
        <div class="flex justify-center">
          <BrandMark size="md" :show-label="false" />
        </div>
        <CardTitle class="text-2xl text-center">Welcome back</CardTitle>
        <CardDescription class="text-center">
          Sign in to continue to your workspace
        </CardDescription>
      </CardHeader>

      <form @submit="handleSubmit">
        <CardContent class="space-y-4">
          <div v-if="errorMsg" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg mb-4 text-center">
            {{ errorMsg }}
          </div>
          
          <div class="space-y-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              type="email"
              placeholder="name@example.com"
              v-model="email"
              required
            />
          </div>
          
          <div class="space-y-2">
            <Label for="password">Password</Label>
            <div class="relative">
              <Input
                id="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                v-model="password"
                required
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
              >
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </div>
          
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <input
                type="checkbox"
                id="remember"
                v-model="rememberMe"
                class="h-4 w-4 rounded border-gray-300"
              />
              <Label for="remember" class="text-sm font-normal">
                Remember me
              </Label>
            </div>
            <RouterLink to="/forgot-password" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
              Forgot password?
            </RouterLink>
          </div>
        </CardContent>
        
        <CardFooter class="flex flex-col space-y-4">
          <Button type="submit" class="w-full" :disabled="isLoading || auth.isLoading">
            <Loader2 v-if="isLoading || auth.isLoading" class="mr-2 h-4 w-4 animate-spin" />
            {{ (isLoading || auth.isLoading) ? 'Signing in...' : 'Sign in' }}
          </Button>
          <p class="text-sm text-center text-gray-600 dark:text-gray-400">
            Don't have an account?
            <RouterLink to="/register" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
              Sign up
            </RouterLink>
          </p>
        </CardFooter>
      </form>
    </Card>
  </div>
</template>
