<script setup lang="ts">
import { computed } from 'vue'
import { cn } from './utils'

const props = defineProps<{ class?: string }>()
const computedClass = computed(() => cn('text-2xl font-semibold leading-none tracking-tight', props.class))
</script>

<template>
  <h3 :class="computedClass">
    <slot />
  </h3>
</template>
