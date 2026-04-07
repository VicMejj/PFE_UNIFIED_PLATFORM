<script setup lang="ts">
import { computed } from 'vue'
import { cn } from './utils'

const props = defineProps<{ class?: string }>()
const computedClass = computed(() => cn('text-sm text-muted-foreground', props.class))
</script>

<template>
  <p :class="computedClass">
    <slot />
  </p>
</template>
