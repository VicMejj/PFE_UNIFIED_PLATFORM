<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight, Loader2, Search } from 'lucide-vue-next'
import Button from './Button.vue'
import Input from './Input.vue'

const props = withDefaults(defineProps<{
  columns: { key: string; label: string }[]
  data: any[]
  loading?: boolean
  searchPlaceholder?: string
  emptyMessage?: string
  pageSize?: number
  maxBodyHeight?: string
}>(), {
  loading: false,
  searchPlaceholder: undefined,
  emptyMessage: undefined,
  pageSize: 0,
  maxBodyHeight: ''
})

defineEmits(['search', 'row-click'])

const currentPage = ref(1)

const totalPages = computed(() => {
  if (!props.pageSize || props.pageSize <= 0) return 1
  return Math.max(1, Math.ceil(props.data.length / props.pageSize))
})

const paginatedData = computed(() => {
  if (!props.pageSize || props.pageSize <= 0) return props.data

  const start = (currentPage.value - 1) * props.pageSize
  return props.data.slice(start, start + props.pageSize)
})

const pageSummary = computed(() => {
  if (!props.data.length) {
    return 'Showing 0 results'
  }

  if (!props.pageSize || props.pageSize <= 0) {
    return `Showing ${props.data.length} results`
  }

  const start = (currentPage.value - 1) * props.pageSize + 1
  const end = Math.min(currentPage.value * props.pageSize, props.data.length)
  return `Showing ${start}-${end} of ${props.data.length}`
})

watch(
  () => [props.data.length, props.pageSize],
  () => {
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }

    if (currentPage.value < 1) {
      currentPage.value = 1
    }
  },
  { immediate: true }
)

watch(
  () => props.data,
  () => {
    currentPage.value = 1
  }
)
</script>

<template>
  <div class="space-y-4">
    <!-- Toolbar -->
    <div class="flex items-center justify-between" v-if="searchPlaceholder">
      <div class="relative w-72">
        <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-gray-500" />
        <Input 
          type="search" 
          :placeholder="searchPlaceholder" 
          class="pl-9 h-9"
          @input="$emit('search', ($event.target as HTMLInputElement).value)"
        />
      </div>
      <slot name="toolbar-actions"></slot>
    </div>

    <!-- Table Container -->
    <div class="rounded-md border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 overflow-hidden">
      <div
        class="overflow-x-auto"
        :class="maxBodyHeight ? 'overflow-y-auto scroll-smooth' : ''"
        :style="maxBodyHeight ? { maxHeight: maxBodyHeight } : undefined"
      >
        <table class="w-full text-sm text-left">
          <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
            <tr>
              <th
                v-for="col in columns"
                :key="col.key"
                class="h-10 px-4 font-medium align-middle"
                :class="maxBodyHeight ? 'sticky top-0 z-10 bg-gray-50 dark:bg-gray-800' : ''"
              >
                {{ col.label }}
              </th>
              <th
                v-if="$slots.actions"
                class="h-10 px-4 font-medium align-middle text-right"
                :class="maxBodyHeight ? 'sticky top-0 z-10 bg-gray-50 dark:bg-gray-800' : ''"
              >
                Actions
              </th>
            </tr>
          </thead>
          
          <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            <!-- Loading State -->
            <tr v-if="loading">
              <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="h-24 text-center">
                <Loader2 class="mx-auto h-6 w-6 animate-spin text-gray-400" />
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-else-if="!data.length">
              <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="h-24 text-center text-gray-500">
                {{ emptyMessage || 'No results found.' }}
              </td>
            </tr>

            <!-- Data Rows -->
            <tr 
              v-else
              v-for="(row, i) in paginatedData" 
              :key="i"
              class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors cursor-pointer"
              @click="$emit('row-click', row)"
            >
              <td v-for="col in columns" :key="col.key" class="p-4 align-middle">
                <slot :name="`cell(${col.key})`" :value="row[col.key]" :item="row">
                  {{ row[col.key] }}
                </slot>
              </td>
              <td v-if="$slots.actions" class="p-4 align-middle text-right">
                <slot name="actions" :item="row"></slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="pageSize && pageSize > 0 && totalPages > 1"
        class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-300 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>{{ pageSummary }}</div>
        <div class="flex items-center gap-2">
          <Button
            class="h-9 border border-gray-200 bg-white px-3 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800"
            :disabled="currentPage === 1"
            @click="currentPage -= 1"
          >
            <ChevronLeft class="mr-1 h-4 w-4" />
            Prev
          </Button>
          <div class="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
            Page {{ currentPage }} / {{ totalPages }}
          </div>
          <Button
            class="h-9 border border-gray-200 bg-white px-3 text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800"
            :disabled="currentPage === totalPages"
            @click="currentPage += 1"
          >
            Next
            <ChevronRight class="ml-1 h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
