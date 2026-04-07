<script setup lang="ts">
import { Moon, Sun } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import { useTheme } from '@/composables/useTheme'
import { useAuthStore } from '@/stores/auth'

const props = defineProps<{
  subtle?: boolean
}>()

const themeState = useTheme()
const auth = useAuthStore()
const currentTheme = themeState.theme

async function handleToggle() {
  const nextTheme = themeState.toggleTheme()

  if (auth.isAuthenticated) {
    try {
      await auth.updatePreferences({
        dark_mode: nextTheme === 'dark'
      })
    } catch (error) {
      console.error('Unable to sync theme preference', error)
    }
  }
}
</script>

<template>
  <Button
    :class="`h-10 w-10 rounded-full p-0 ${
      subtle
        ? 'border border-white/20 bg-white/70 text-slate-700 backdrop-blur hover:bg-white dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100 dark:hover:bg-slate-900'
        : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800'
    }`"
    @click="handleToggle"
  >
    <Moon v-if="currentTheme === 'light'" class="h-4 w-4" />
    <Sun v-else class="h-4 w-4" />
  </Button>
</template>
