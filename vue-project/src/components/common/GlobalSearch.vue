<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useDebounceFn } from '@vueuse/core'
import { useRouter } from 'vue-router'
import { Loader2, Search, Sparkles } from 'lucide-vue-next'
import { platformApi } from '@/api/laravel/platform'

type ResultItem = { id: number | string; type: string; title: string; subtitle?: string; route: string }
type SearchResultsPayload = {
  employees?: ResultItem[]
  leaves?: ResultItem[]
  attendance?: ResultItem[]
  events?: ResultItem[]
  contracts?: ResultItem[]
  messages?: ResultItem[]
  payroll?: ResultItem[]
  benefits?: ResultItem[]
}

const router = useRouter()
const query = ref('')
const isOpen = ref(false)
const isLoading = ref(false)
const activeIndex = ref(0)
const results = ref<Record<string, ResultItem[]>>({
  employees: [],
  leaves: [],
  attendance: [],
  events: [],
  contracts: [],
  messages: [],
  payroll: [],
  benefits: [],
})

const groups = computed(() => [
  ['employees', 'Employees'],
  ['leaves', 'Leave Requests'],
  ['attendance', 'Attendance'],
  ['events', 'Events'],
  ['contracts', 'Contracts'],
  ['messages', 'Messages'],
  ['payroll', 'Payroll'],
  ['benefits', 'Benefits'],
] as const)

const flatResults = computed(() => groups.value.flatMap(([key]) => results.value[key] ?? []))

const search = useDebounceFn(async () => {
  const value = query.value.trim()
  if (value.length < 2) {
    results.value = { employees: [], leaves: [], attendance: [], events: [], contracts: [], messages: [], payroll: [], benefits: [] }
    isLoading.value = false
    return
  }

  isLoading.value = true
  try {
    const data = (await platformApi.search(value)) as SearchResultsPayload
    results.value = {
      employees: data.employees ?? [],
      leaves: data.leaves ?? [],
      attendance: data.attendance ?? [],
      events: data.events ?? [],
      contracts: data.contracts ?? [],
      messages: data.messages ?? [],
      payroll: data.payroll ?? [],
      benefits: data.benefits ?? [],
    }
    activeIndex.value = 0
  } finally {
    isLoading.value = false
  }
}, 220)

watch(query, () => {
  isOpen.value = true
  search()
})

function choose(item: ResultItem) {
  isOpen.value = false
  query.value = ''
  router.push(item.route)
}

function onKeydown(event: KeyboardEvent) {
  if (!isOpen.value || !flatResults.value.length) return
  if (event.key === 'ArrowDown') {
    event.preventDefault()
    activeIndex.value = (activeIndex.value + 1) % flatResults.value.length
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    activeIndex.value = (activeIndex.value - 1 + flatResults.value.length) % flatResults.value.length
  } else if (event.key === 'Enter') {
    event.preventDefault()
    const item = flatResults.value[activeIndex.value]
    if (item) choose(item)
  } else if (event.key === 'Escape') {
    isOpen.value = false
  }
}

onBeforeUnmount(() => {
  isOpen.value = false
})
</script>

<template>
  <div class="relative w-full max-w-xl">
    <div class="relative">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
      <input
        v-model="query"
        type="search"
        placeholder="Search employees, leaves, events..."
        class="h-11 w-full rounded-2xl border border-slate-200 bg-white/80 pl-10 pr-4 text-sm text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-sky-500 dark:border-slate-800 dark:bg-slate-950/70 dark:text-white"
        @focus="isOpen = true"
        @keydown="onKeydown"
      />
      <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center gap-2 text-[10px] text-slate-400">
        <Sparkles class="h-3.5 w-3.5" />
        <span>⌘K</span>
      </div>
    </div>

    <Transition name="fade-search">
      <div v-if="isOpen && (query.trim().length >= 2 || isLoading)" class="absolute left-0 right-0 top-[calc(100%+0.75rem)] z-50 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-950">
        <div class="max-h-[420px] overflow-y-auto p-2">
          <div v-if="isLoading" class="flex items-center gap-3 rounded-2xl px-4 py-4 text-sm text-slate-500">
            <Loader2 class="h-4 w-4 animate-spin" />
            Searching the platform...
          </div>

          <div v-else-if="!flatResults.length" class="px-4 py-10 text-center text-sm text-slate-500">
            No results found.
          </div>

          <div v-else v-for="([groupKey, label]) in groups" :key="groupKey" class="mb-2 last:mb-0">
            <div v-if="(results[groupKey] || []).length" class="px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-400">
              {{ label }}
            </div>
            <button
              v-for="item in results[groupKey] || []"
              :key="`${groupKey}-${item.id}`"
              type="button"
              @click="choose(item)"
              :class="[
                'flex w-full items-start gap-3 rounded-2xl px-4 py-3 text-left transition',
                activeIndex === flatResults.findIndex((row) => row.id === item.id && row.type === item.type)
                  ? 'bg-sky-500/10 ring-1 ring-sky-500/20'
                  : 'hover:bg-slate-50 dark:hover:bg-slate-900/60'
              ]"
            >
              <div class="mt-0.5 h-9 w-9 rounded-2xl bg-slate-100 text-slate-600 dark:bg-slate-900 dark:text-slate-300 flex items-center justify-center text-xs font-semibold">
                {{ item.type.slice(0, 2).toUpperCase() }}
              </div>
              <div class="min-w-0 flex-1">
                <div class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ item.title }}</div>
                <div class="truncate text-xs text-slate-500 dark:text-slate-400">{{ item.subtitle }}</div>
              </div>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.fade-search-enter-active,
.fade-search-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.fade-search-enter-from,
.fade-search-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
