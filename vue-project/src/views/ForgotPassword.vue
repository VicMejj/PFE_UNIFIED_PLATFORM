<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { Loader2, Mail, ArrowLeft, CheckCircle2 } from 'lucide-vue-next'
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
const isLoading = ref(false)
const errorMsg = ref('')
const successMsg = ref('')
const isSubmitted = ref(false)

const handleSubmit = async (e: Event) => {
  e.preventDefault()
  isLoading.value = true
  errorMsg.value = ''
  successMsg.value = ''

  try {
    await auth.forgotPassword(email.value)
    isSubmitted.value = true
    successMsg.value = 'If an account exists for that email, a password reset code has been sent.'
  } catch (error: any) {
    errorMsg.value = error.response?.data?.message || 'Failed to send reset code. Please try again.'
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
            <Mail class="w-6 h-6 text-white" />
          </div>
        </div>
        <CardTitle class="text-2xl text-center">Forgot your password?</CardTitle>
        <CardDescription class="text-center">
          Enter your email address and we'll send you a code to reset your password.
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

          <div v-if="!isSubmitted" class="space-y-2">
            <Label for="email">Email</Label>
            <Input
              id="email"
              type="email"
              placeholder="name@example.com"
              v-model="email"
              required
            />
          </div>

          <div v-else class="text-center py-4">
            <CheckCircle2 class="w-12 h-12 text-green-500 mx-auto mb-4" />
            <p class="text-gray-600 dark:text-gray-400">
              Check your email for the reset code.
            </p>
          </div>
        </CardContent>
        
        <CardFooter class="flex flex-col space-y-4">
          <Button 
            v-if="!isSubmitted"
            type="submit" 
            class="w-full" 
            :disabled="isLoading || auth.isLoading || !email"
          >
            <Loader2 v-if="isLoading || auth.isLoading" class="mr-2 h-4 w-4 animate-spin" />
            {{ (isLoading || auth.isLoading) ? 'Sending code...' : 'Send reset code' }}
          </Button>
          
          <Button
            v-if="isSubmitted"
            type="button"
            class="w-full"
            @click="router.push('/reset-password')"
          >
            Enter reset code
          </Button>
          
          <p class="text-sm text-center text-gray-600 dark:text-gray-400">
            <RouterLink to="/login" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium flex items-center justify-center">
              <ArrowLeft class="mr-1 h-4 w-4" />
              Back to login
            </RouterLink>
          </p>
        </CardFooter>
      </form>
    </Card>
  </div>
</template>
