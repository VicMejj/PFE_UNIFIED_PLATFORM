<script setup lang="ts">
import { ref } from 'vue'
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

const router = useRouter()
const auth = useAuthStore()

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const isLoading = ref(false)
const errorMsg = ref('')

const handleSubmit = async (e: Event) => {
  e.preventDefault()
  
  if (password.value !== confirmPassword.value) {
    errorMsg.value = 'Passwords do not match'
    return
  }
  if (password.value.length < 8) {
    errorMsg.value = 'Password must be at least 8 characters'
    return
  }

  isLoading.value = true
  errorMsg.value = ''

  try {
    const fullName = `${firstName.value} ${lastName.value}`.trim()
    await auth.registerLaravel(fullName, email.value, password.value)

    router.push({ path: '/verify-email', query: { email: email.value } })
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || 'Registration failed. Please try again.'
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
    <Card class="relative w-full max-w-md animate-in border-white/70 bg-white/92 shadow-2xl shadow-slate-200/70 backdrop-blur fade-in slide-in-from-bottom-4 duration-500 dark:border-slate-800 dark:bg-slate-950/90 dark:shadow-slate-950/50">
      <CardHeader class="space-y-3">
        <div class="flex justify-center">
          <BrandMark size="md" :show-label="false" />
        </div>
        <CardTitle class="text-2xl text-center">Create an account</CardTitle>
        <CardDescription class="text-center">
          Set up your access to the Unified Platform
        </CardDescription>
      </CardHeader>

      <form @submit="handleSubmit">
        <CardContent class="space-y-4">
          <div v-if="errorMsg" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg mb-4 text-center">
            {{ errorMsg }}
          </div>
          
          <div class="space-y-2">
            <Label for="firstName">First Name</Label>
            <Input
              id="firstName"
              placeholder="First name"
              v-model="firstName"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="lastName">Last Name</Label>
            <Input
              id="lastName"
              placeholder="Last name"
              v-model="lastName"
              required
            />
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

          <div class="space-y-2">
            <Label for="confirmPassword">Confirm Password</Label>
            <div class="relative">
              <Input
                id="confirmPassword"
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="••••••••"
                v-model="confirmPassword"
                required
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
              >
                <EyeOff v-if="showConfirmPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </div>
        </CardContent>
        
        <CardFooter class="flex flex-col space-y-4">
          <Button type="submit" class="w-full" :disabled="isLoading || auth.isLoading">
            <Loader2 v-if="isLoading || auth.isLoading" class="mr-2 h-4 w-4 animate-spin" />
            {{ (isLoading || auth.isLoading) ? 'Creating account...' : 'Create account' }}
          </Button>
          <p class="text-sm text-center text-gray-600 dark:text-gray-400">
            Already have an account?
            <RouterLink to="/login" class="text-blue-600 hover:text-blue-700 dark:text-blue-400">
              Sign in
            </RouterLink>
          </p>
        </CardFooter>
      </form>
    </Card>
  </div>
</template>
