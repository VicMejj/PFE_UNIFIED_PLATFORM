<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  variant?: 'default' | 'success' | 'warning' | 'destructive' | 'outline' | 'secondary'
}>()

const baseClasses = "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'success':
      return "border-transparent bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 hover:bg-green-200"
    case 'warning':
      return "border-transparent bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 hover:bg-yellow-200"
    case 'destructive':
      return "border-transparent bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 hover:bg-red-200"
    case 'secondary':
      return "border-transparent bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100 hover:bg-gray-200"
    case 'outline':
      return "text-foreground border border-input"
    default:
      return "border-transparent bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 hover:bg-blue-200" // primary
  }
})
</script>

<template>
  <div :class="[baseClasses, variantClasses]">
    <slot />
  </div>
</template>
