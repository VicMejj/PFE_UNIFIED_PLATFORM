<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { CheckCircle2, Loader2, ArrowRight, Mail, RefreshCw } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardFooter from '@/components/ui/CardFooter.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const otp = ref('')
const isLoading = ref(false)
const isResending = ref(false)
const errorMsg = ref('')
const successMsg = ref('')

// Pre-fill email from query params if available
const email = ref((route.query.email as string) || '')

const handleSubmit = async (e: Event) => {
  e.preventDefault()
  
  if (!email.value) {
    errorMsg.value = 'Email address is missing. Please login again.'
    return
  }

  if (otp.value.length !== 6) {
    errorMsg.value = 'Please enter a valid 6-digit code.'
    return
  }

  isLoading.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    const response = await auth.verifyEmailOtp(email.value, otp.value)
    
    // Redirect based on role
    const role = response.user?.role
    if (role === 'admin') router.push('/admin')
    else if (role === 'rh_manager') router.push('/rh')
    else if (role === 'manager') router.push('/manager')
    else if (role === 'employee') router.push('/employee')
    else router.push('/dashboard')
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || 'The verification code is invalid or has expired.'
  } finally {
    isLoading.value = false
  }
}

const handleResend = async () => {
  if (!email.value) {
    errorMsg.value = 'Email address is missing. Please try again.'
    return
  }

  isResending.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    await auth.resendEmailOtp(email.value)
    successMsg.value = 'A new verification code has been sent to your email.'
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || 'Failed to resend verification code.'
  } finally {
    isResending.value = false
  }
}

// Watch for email changes in query params
onMounted(() => {
  if (route.query.email) {
    email.value = route.query.email as string
  }
})
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-transparent p-4">
    <Card class="w-full max-w-md animate-in border-white/70 bg-white/92 shadow-xl shadow-slate-200/70 backdrop-blur fade-in slide-in-from-bottom-4 duration-500 dark:border-slate-800 dark:bg-slate-950/90 dark:shadow-slate-950/50">
      <CardHeader class="space-y-1">
        <div class="flex items-center justify-center mb-4 text-blue-600">
          <CheckCircle2 class="w-12 h-12" />
        </div>
        <CardTitle class="text-2xl text-center">Verify your email</CardTitle>
        <CardDescription class="text-center">
          We've sent a 6-digit code to <span class="font-medium text-gray-900 dark:text-gray-100">{{ email || 'your email' }}</span>.
        </CardDescription>
      </CardHeader>

      <form @submit="handleSubmit">
        <CardContent class="space-y-4">
          <div v-if="errorMsg" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg text-center animate-in shake">
            {{ errorMsg }}
          </div>
          
          <div v-if="successMsg" class="p-3 bg-green-50 text-green-600 text-sm rounded-lg text-center">
            {{ successMsg }}
          </div>
          
          <div class="space-y-2">
            <Input
              id="otp"
              type="text"
              placeholder="Enter 6-digit code"
              v-model="otp"
              class="text-center text-2xl tracking-widest h-14"
              maxlength="6"
              required
            />
          </div>
        </CardContent>
        
        <CardFooter class="flex flex-col space-y-4">
          <Button type="submit" class="w-full h-12 text-md transition-all active:scale-95" :disabled="isLoading || auth.isLoading || otp.length < 6">
            <Loader2 v-if="isLoading || auth.isLoading" class="mr-2 h-5 w-5 animate-spin" />
            <span v-if="!(isLoading || auth.isLoading)" class="flex items-center">Verify Code <ArrowRight class="ml-2 w-4 h-4" /></span>
            <span v-else>Verifying...</span>
          </Button>
          
          <div class="flex items-center justify-center space-x-2">
            <p class="text-sm text-center text-gray-600 dark:text-gray-400">
              Didn't receive the code?
            </p>
            <Button 
              type="button" 
              variant="link" 
              class="p-0 h-auto font-medium"
              @click="handleResend"
              :disabled="isResending || auth.isLoading"
            >
              <RefreshCw v-if="isResending" class="mr-1 h-3 w-3 animate-spin" />
              <Mail v-else class="mr-1 h-3 w-3" />
              {{ isResending ? 'Sending...' : 'Resend code' }}
            </Button>
          </div>
          
          <p class="text-sm text-center text-gray-600 dark:text-gray-400">
            <RouterLink to="/login" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
              ← Back to login
            </RouterLink>
          </p>
        </CardFooter>
      </form>
    </Card>
  </div>
</template>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  50% { transform: translateX(5px); }
  75% { transform: translateX(-5px); }
}
.shake {
  animation: shake 0.4s ease-in-out;
}
</style>
