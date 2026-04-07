<script setup lang="ts">
import { computed } from 'vue'
import { cn } from './utils'

const props = defineProps<{ class?: string; type?: 'button' | 'submit' | 'reset'; disabled?: boolean }>()
const computedClass = computed(() => cn(
  'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-10 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90',
  props.class
))
</script>

<template>
  <button :type="type || 'button'" :disabled="disabled" :class="computedClass">
    <slot />
  </button>
</template>
