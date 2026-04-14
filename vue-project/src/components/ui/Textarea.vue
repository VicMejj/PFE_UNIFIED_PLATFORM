<script setup lang="ts">
import { computed, useAttrs } from 'vue'
import { cn } from './utils'

defineOptions({ inheritAttrs: false })

const props = defineProps<{
  modelValue?: string | number
  class?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  rows?: number
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | number): void
}>()

const attrs = useAttrs()

const computedClass = computed(() => cn(
  'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
  props.class
))
</script>

<template>
  <textarea
    v-bind="attrs"
    :class="computedClass"
    :placeholder="placeholder"
    :required="required"
    :disabled="disabled"
    :rows="rows || 3"
    :value="modelValue"
    @input="$emit('update:modelValue', ($event.target as HTMLTextAreaElement).value)"
  />
</template>
