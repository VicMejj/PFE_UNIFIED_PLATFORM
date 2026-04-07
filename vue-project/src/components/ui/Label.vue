<script setup lang="ts">
import { computed } from 'vue'
import { cn } from './utils'

const props = defineProps<{ class?: string; for?: string }>()
const computedClass = computed(() => cn(
  'text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70',
  props.class
))
</script>

<template>
  <label :for="props.for" :class="computedClass">
    <slot />
  </label>
</template>
