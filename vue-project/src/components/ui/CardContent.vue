<script setup lang="ts">
import { computed } from 'vue'
import { cn } from './utils'

const props = defineProps<{ class?: string }>()
const computedClass = computed(() => cn('p-6 pt-0', props.class))
</script>

<template>
  <div :class="computedClass">
    <slot />
  </div>
</template>
