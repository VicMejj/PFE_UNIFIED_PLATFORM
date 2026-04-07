<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Loader2, Lock, Eye, EyeOff, CheckCircle2 } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardFooter from '@/components/ui/CardFooter.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const otp = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const isLoading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const isCompleted = ref(false)

const handleSubmit = async (e: Event) => {
  e.preventDefault()
  
  // Validation
  if (password.value !== confirmPassword.value) {
    errorMsg.value = 'Passwords do not match'
    return
  }
  if (password.value.length < 8) {
    errorMsg.value = 'Password must be at least 8 characters'
    return
  }
  if (otp.value.length !== 6) {
    errorMsg.value = 'Please enter a valid 6-digit code'
    return
  }

  isLoading.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    await auth.resetPassword(email.value, otp.value, password.value, confirmPassword.value)
    isCompleted.value = true
    successMsg.value = 'Password reset successfully!'
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || 'Failed to reset password. Please check your code and try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-transparent p-4">
    <Card class="w-full max-w-md animate-in border-white/70 bg-white/92 shadow-xl shadow-slate-200/70 backdrop-blur fade-in slide-in-from-bottom-4 duration-500 dark:border-slate-800 dark:bg-slate-950/90 dark:shadow-slate-950/50">
      <CardHeader class="space-y-1">
        <div class="flex items-center justify-center mb-4">
          <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
            <Lock class="w-6 h-6 text-white" />
          </div>
        </div>
        <CardTitle class="text-2xl text-center">Reset your password</CardTitle>
        <CardDescription class="text-center">
          Enter the reset code and your new password.
        </CardDescription>
      </CardHeader>

      <form @submit="handleSubmit">
        <CardContent class="space-y-4">
          <div v-if="errorMsg" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg text-center">
            {{ errorMsg }}
          </div>
          
          <div v-if="successMsg" class="p-3 bg-green-50 text-green-600 text-sm rounded-lg text-center">
            {{ successMsg }}
          </div>

          <div v-if="isCompleted" class="text-center py-4">
            <CheckCircle2 class="w-12 h-12 text-green-500 mx-auto mb-4" />
            <p class="text-gray-600 dark:text-gray-400">
              Your password has been reset successfully.
            </p>
          </div>

          <template v-else>
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
              <Label for="otp">Reset Code</Label>
              <Input
                id="otp"
                type="text"
                placeholder="Enter 6-digit code"
                v-model="otp"
                class="text-center text-xl tracking-widest"
                maxlength="6"
                required
              />
            </div>
            
            <div class="space-y-2">
              <Label for="password">New Password</Label>
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
              <Label for="confirmPassword">Confirm New Password</Label>
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
          </template>
        </CardContent>
        
        <CardFooter class="flex flex-col space-y-4">
          <Button 
            v-if="!isCompleted"
            type="submit" 
            class="w-full" 
            :disabled="isLoading || auth.isLoading"
          >
            <Loader2 v-if="isLoading || auth.isLoading" class="mr-2 h-4 w-4 animate-spin" />
            {{ (isLoading || auth.isLoading) ? 'Resetting password...' : 'Reset password' }}
          </Button>
          
          <Button
            v-else
            type="button"
            class="w-full"
            @click="router.push('/login')"
          >
            Back to login
          </Button>
          
          <p class="text-sm text-center text-gray-600 dark:text-gray-400">
            <RouterLink to="/forgot-password" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
              Didn't receive a code? Request one
            </RouterLink>
          </p>
        </CardFooter>
      </form>
    </Card>
  </div>
</template>
