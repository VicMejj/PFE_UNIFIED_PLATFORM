<script setup lang="ts">
import { computed } from 'vue'
import { Loader2, ShieldCheck } from 'lucide-vue-next'

const props = defineProps<{
  isVisible: boolean
  message?: string
}>()

const loadingMessage = computed(() => {
  if (props.message) return props.message
  
  const messages = [
    'Loading your dashboard...',
    'Preparing your workspace...',
    'Almost there...',
    'Setting things up...'
  ]
  return messages[Math.floor(Math.random() * messages.length)]
})
</script>

<template>
  <Transition name="fade">
    <div v-if="isVisible" class="fixed inset-0 z-[9999] flex items-center justify-center bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm">
      <div class="flex flex-col items-center space-y-6 animate-in zoom-in duration-300">
        <!-- Logo animation -->
        <div class="relative">
          <div class="w-20 h-20 bg-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
            <ShieldCheck class="w-10 h-10 text-white" />
          </div>
          <div class="absolute -inset-2 border-4 border-blue-200 dark:border-blue-800 rounded-2xl animate-pulse" />
        </div>

        <!-- Spinner -->
        <div class="relative">
          <Loader2 class="w-10 h-10 text-blue-600 animate-spin" />
          <div class="absolute inset-0 w-10 h-10 border-2 border-blue-200 dark:border-blue-800 rounded-full animate-ping" />
        </div>

        <!-- Message -->
        <p class="text-gray-600 dark:text-gray-400 font-medium text-lg">
          {{ loadingMessage }}
        </p>

        <!-- Progress bar -->
        <div class="w-48 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
          <div class="h-full bg-blue-600 rounded-full animate-pulse w-2/3" />
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>