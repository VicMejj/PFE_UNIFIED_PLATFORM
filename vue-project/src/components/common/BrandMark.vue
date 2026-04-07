<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  size?: 'sm' | 'md' | 'lg'
  showLabel?: boolean
  title?: string
  subtitle?: string
  logoSrc?: string
}>(), {
  size: 'md',
  showLabel: true,
  title: 'Unified Platform',
  subtitle: 'Workforce hub',
  logoSrc: '/favicon.ico'
})

const sizeClasses = computed(() => {
  if (props.size === 'sm') {
    return {
      shell: 'h-10 w-10 rounded-xl text-base',
      title: 'text-base',
      subtitle: 'text-[11px]'
    }
  }

  if (props.size === 'lg') {
    return {
      shell: 'h-14 w-14 rounded-2xl text-2xl',
      title: 'text-xl',
      subtitle: 'text-xs'
    }
  }

  return {
    shell: 'h-12 w-12 rounded-2xl text-xl',
    title: 'text-lg',
    subtitle: 'text-xs'
  }
})
</script>

<template>
  <div class="flex items-center gap-3">
    <div
      :class="[
        sizeClasses.shell,
        'flex items-center justify-center bg-gradient-to-br from-sky-500 via-blue-600 to-slate-900 font-black tracking-[0.18em] text-white shadow-lg shadow-sky-500/20'
      ]"
      aria-label="Unified Platform"
    >
      <template v-if="logoSrc">
        <img :src="logoSrc" :alt="title" class="h-full w-full object-cover rounded-xl" />
      </template>
      <template v-else>
        U
      </template>
    </div>
    <div v-if="showLabel" class="min-w-0">
      <div :class="[sizeClasses.title, 'truncate font-semibold tracking-tight text-slate-900 dark:text-white']">
        {{ title }}
      </div>
      <div :class="[sizeClasses.subtitle, 'truncate uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500']">
        {{ subtitle }}
      </div>
    </div>
  </div>
</template>
