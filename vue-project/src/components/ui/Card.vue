<script setup lang="ts">
import { computed } from 'vue'
import { cn } from './utils'

const props = defineProps<{ class?: string }>()
const computedClass = computed(() => cn('bg-card text-card-foreground rounded-xl border shadow-sm', props.class))
</script>

<template>
  <div :class="computedClass">
    <slot />
  </div>
</template>
