<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { 
  ChevronLeft, 
  ChevronRight, 
  Plus, 
  MapPin, 
  Clock, 
  Trash2,
  CalendarDays,
  Sparkles,
  Info
} from 'lucide-vue-next'
import { platformApi } from '@/api/laravel/platform'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Label from '@/components/ui/Label.vue'
import Dialog from '@/components/ui/Dialog.vue'

// Tunisia Holidays 2025/2026
const holidayData = [
  { month: 1, day: 1, name: "New Year's Day" },
  { month: 3, day: 20, name: "Independence Day" },
  { month: 3, day: 21, name: "Youth Day" },
  { month: 4, day: 9, name: "Martyrs' Day" },
  { month: 5, day: 1, name: "Labour Day" },
  { month: 7, day: 25, name: "Republic Day" },
  { month: 8, day: 13, name: "Women's Day" },
  { month: 10, day: 15, name: "Evacuation Day" },
  { month: 12, day: 17, name: "Revolution Day" },
  { date: '2026-03-20', name: "Eid al-Fitr" },
  { date: '2026-03-21', name: "Eid al-Fitr" },
  { date: '2026-03-22', name: "Eid al-Fitr" },
  { date: '2026-05-27', name: "Eid al-Adha" },
  { date: '2026-05-28', name: "Eid al-Adha" },
  { date: '2026-06-16', name: "Islamic New Year" },
  { date: '2026-08-26', name: "Mawlid" }
]

const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
const months = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
]

const currentDate = ref(new Date())
const viewedMonth = ref(currentDate.value.getMonth())
const viewedYear = ref(currentDate.value.getFullYear())

const events = ref<any[]>([])
const isLoading = ref(false)
const isModalOpen = ref(false)
const isDeleting = ref(false)
const selectedEvent = ref<any>(null)
const selectedDate = ref<Date | null>(null)

const eventForm = reactive({
  title: '',
  description: '',
  event_date: '',
  start_time: '09:00',
  end_time: '10:00',
  location: '',
  type: 'general',
  color: '#3b82f6'
})

const firstDayOfMonth = computed(() => new Date(viewedYear.value, viewedMonth.value, 1).getDay())
const daysInMonth = computed(() => new Date(viewedYear.value, viewedMonth.value + 1, 0).getDate())

const calendarDays = computed(() => {
  const days = []
  const prevMonthDays = new Date(viewedYear.value, viewedMonth.value, 0).getDate()
  
  for (let i = firstDayOfMonth.value - 1; i >= 0; i--) {
    days.push({ day: prevMonthDays - i, month: viewedMonth.value - 1, year: viewedYear.value, currentMonth: false })
  }
  for (let i = 1; i <= daysInMonth.value; i++) {
    days.push({ day: i, month: viewedMonth.value, year: viewedYear.value, currentMonth: true })
  }
  const totalCells = 42
  const nextMonthPadding = totalCells - days.length
  for (let i = 1; i <= nextMonthPadding; i++) {
    days.push({ day: i, month: viewedMonth.value + 1, year: viewedYear.value, currentMonth: false })
  }
  
  return days.map(d => {
    const dateStr = formatDate(d.year, d.month, d.day)
    return {
      ...d,
      dateStr,
      isToday: isSameDay(new Date(), new Date(d.year, d.month, d.day)),
      holidays: getHolidays(d.year, d.month, d.day),
      events: getEventsForDay(dateStr)
    }
  })
})

function formatDate(y: number, m: number, d: number) {
  const date = new Date(y, m, d)
  return date.toISOString().split('T')[0]
}

function isSameDay(d1: Date, d2: Date) {
  return d1.getFullYear() === d2.getFullYear() && d1.getMonth() === d2.getMonth() && d1.getDate() === d2.getDate()
}

function getHolidays(y: number, m: number, d: number) {
  const adjustedMonth = m + 1
  return holidayData.filter(h => {
    if ('date' in h) return h.date === formatDate(y, m, d)
    return h.month === adjustedMonth && h.day === d
  })
}

function getEventsForDay(dateStr: string) {
  return events.value.filter(e => e.event_date === dateStr)
}

async function fetchEvents() {
  isLoading.value = true
  try {
    const data = await platformApi.getEvents({ month: viewedMonth.value + 1, year: viewedYear.value })
    events.value = Array.isArray(data) ? data : (data as any).data || []
  } catch (err) {
    console.error('Failed to fetch events', err)
  } finally {
    isLoading.value = false
  }
}

function nextMonth() {
  if (viewedMonth.value === 11) { viewedMonth.value = 0; viewedYear.value++ } else { viewedMonth.value++ }
}

function prevMonth() {
  if (viewedMonth.value === 0) { viewedMonth.value = 11; viewedYear.value-- } else { viewedMonth.value-- }
}

function openAddModal(day: any) {
  selectedEvent.value = null
  selectedDate.value = new Date(day.year, day.month, day.day)
  Object.assign(eventForm, {
    title: '', description: '', event_date: day.dateStr, start_time: '09:00', end_time: '10:00',
    location: '', type: 'general', color: '#3b82f6'
  })
  isModalOpen.value = true
}

function openEditModal(event: any) {
  selectedEvent.value = event
  Object.assign(eventForm, {
    title: event.title, description: event.description || '', event_date: event.event_date,
    start_time: event.start_time?.substring(0, 5) || '09:00', end_time: event.end_time?.substring(0, 5) || '10:00',
    location: event.location || '', type: event.type || 'general', color: event.color || '#3b82f6'
  })
  isModalOpen.value = true
}

async function saveEvent() {
  try {
    if (selectedEvent.value) {
      await platformApi.updateEvent(selectedEvent.value.id, { ...eventForm })
    } else {
      await platformApi.createEvent({ ...eventForm })
    }
    isModalOpen.value = false
    fetchEvents()
  } catch (err) { console.error('Failed to save event', err) }
}

async function deleteEvent() {
  if (!selectedEvent.value) return
  isDeleting.value = true
  try {
    await platformApi.deleteEvent(selectedEvent.value.id)
    isModalOpen.value = false
    fetchEvents()
  } catch (err) { console.error('Failed to delete event', err) } finally { isDeleting.value = false }
}

watch([viewedMonth, viewedYear], fetchEvents)
onMounted(fetchEvents)

const eventTypes = [
  { value: 'general', label: 'General', color: 'bg-slate-500' },
  { value: 'meeting', label: 'Meeting', color: 'bg-blue-600' },
  { value: 'reminder', label: 'Reminder', color: 'bg-amber-600' },
  { value: 'holiday', label: 'Holiday', color: 'bg-emerald-600' },
  { value: 'birthday', label: 'Birthday', color: 'bg-pink-600' },
  { value: 'deadline', label: 'Deadline', color: 'bg-rose-600' }
]
</script>

<template>
  <div class="calendar-card glass-card premium-shadow group/calendar">
    <!-- Header -->
    <div class="flex items-center justify-between p-6 border-b border-white/10 dark:border-slate-800/50 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl rounded-t-3xl">
      <div class="flex items-center gap-4">
        <div class="p-2.5 bg-blue-600/10 text-blue-600 dark:text-blue-400 rounded-2xl ring-1 ring-blue-600/20">
          <CalendarDays class="w-6 h-6" />
        </div>
        <div>
          <h3 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ months[viewedMonth] }} {{ viewedYear }}</h3>
          <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            {{ events.length }} Synchronized Sessions
          </p>
        </div>
      </div>
      
      <div class="flex items-center gap-4">
        <div class="flex items-center bg-slate-100/80 dark:bg-slate-800/80 backdrop-blur-md rounded-2xl p-1.5 shadow-inner border border-white/10">
          <button @click="prevMonth" class="p-2.5 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-all text-slate-600 dark:text-slate-300 active:scale-90">
            <ChevronLeft class="w-4 h-4" />
          </button>
          <button @click="currentDate = new Date(); viewedMonth = currentDate.getMonth(); viewedYear = currentDate.getFullYear()" class="px-4 py-1 text-xs font-black uppercase tracking-widest text-slate-600 dark:text-slate-300 hover:text-blue-600 active:scale-95 transition-all">
            Now
          </button>
          <button @click="nextMonth" class="p-2.5 hover:bg-white dark:hover:bg-slate-700 rounded-xl transition-all text-slate-600 dark:text-slate-300 active:scale-90">
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
        
        <Button class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl px-5 h-11 premium-shadow btn-transition" @click="openAddModal({ dateStr: formatDate(new Date().getFullYear(), new Date().getMonth(), new Date().getDate()), year: new Date().getFullYear(), month: new Date().getMonth(), day: new Date().getDate() })">
          <Plus class="w-4 h-4 mr-2" />
          New Event
        </Button>
      </div>
    </div>

    <!-- Calendar Grid -->
    <div class="p-4 bg-transparent relative">
      <div v-if="isLoading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/20 dark:bg-slate-900/20 backdrop-blur-[2px] transition-all duration-500">
        <Sparkles class="w-8 h-8 text-blue-500 animate-spin" />
      </div>

      <div class="grid grid-cols-7 gap-px rounded-3xl overflow-hidden border border-white/20 dark:border-slate-800/50 bg-white/10 dark:bg-slate-800/30">
        <!-- Weekdays -->
        <div v-for="day in daysOfWeek" :key="day" class="bg-slate-50/50 dark:bg-slate-900/50 py-4 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 border-b border-white/10">
          {{ day }}
        </div>
        
        <!-- Days -->
        <div 
          v-for="(d, i) in calendarDays" 
          :key="i"
          class="min-h-[130px] bg-white/30 dark:bg-slate-900/40 p-3 group relative transition-all duration-300 hover:z-[5]"
          :class="[
            !d.currentMonth ? 'bg-slate-100/20 dark:bg-slate-900/10 opacity-40' : 'hover:bg-white dark:hover:bg-slate-800 hover:shadow-2xl hover:scale-[1.02] hover:rounded-xl ring-1 ring-inset ring-transparent hover:ring-blue-500/30'
          ]"
          @dblclick="openAddModal(d)"
        >
          <!-- Day Number -->
          <div class="flex justify-between items-start mb-2">
            <span 
              :class="[
                'w-8 h-8 flex items-center justify-center rounded-2xl text-sm font-black transition-all duration-300',
                d.isToday ? 'bg-blue-600 text-white shadow-xl shadow-blue-600/40 ring-4 ring-blue-600/10' : 'text-slate-500 dark:text-slate-400 group-hover:text-blue-600 group-hover:bg-blue-50 dark:group-hover:bg-blue-900/20',
              ]"
            >
              {{ d.day }}
            </span>
            
            <button 
              v-if="d.currentMonth"
              @click="openAddModal(d)"
              class="opacity-0 group-hover:opacity-100 p-2 text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/40 rounded-xl transition-all transform scale-90 hover:scale-100 active:scale-75"
            >
              <Plus class="w-4 h-4" />
            </button>
          </div>

          <!-- Holidays -->
          <div v-for="h in d.holidays" :key="h.name" class="mb-1.5 last:mb-0">
            <div class="px-2 py-1 rounded-xl bg-emerald-500/10 text-[9px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5 border border-emerald-500/20 animate-in">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
              <span class="truncate">{{ h.name }}</span>
            </div>
          </div>

          <!-- Events -->
          <div class="mt-1 flex flex-col gap-1.5 max-h-[70px] overflow-y-auto no-scrollbar pt-1">
            <button 
              v-for="e in d.events" 
              :key="e.id"
              @click="openEditModal(e)"
              class="w-full text-left px-2.5 py-1.5 rounded-xl text-[10px] font-bold transition-all hover:translate-x-1 active:scale-95 flex items-center gap-2 border shadow-sm group/event animate-in"
              :style="{ 
                backgroundColor: `${e.color}10`, 
                borderColor: `${e.color}25`,
                color: e.color 
              }"
            >
              <span class="w-2 h-2 rounded-full flex-shrink-0 group-hover/event:scale-125 transition-transform" :style="{ backgroundColor: e.color }"></span>
              <span class="truncate group-hover/event:font-black transition-all">{{ e.title }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Premium Event Modal -->
    <Dialog 
      :open="isModalOpen" 
      @close="isModalOpen = false" 
      :title="selectedEvent ? 'Session Details' : 'Collaborative Session'"
      size="full"
    >
      <div class="space-y-8 py-2 animate-in">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div class="md:col-span-2 space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Session Target</Label>
            <Input v-model="eventForm.title" placeholder="Define session objective..." class="h-12 text-lg font-bold bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border-white/20" />
          </div>
          
          <div class="space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Target Date</Label>
            <Input type="date" v-model="eventForm.event_date" class="h-11 bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border-white/20" />
          </div>
          
          <div class="space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Engagement Category</Label>
            <select v-model="eventForm.type" class="flex h-11 w-full rounded-2xl border border-white/20 bg-slate-50/50 dark:bg-slate-900/50 px-4 py-2 text-sm font-bold ring-offset-background focus:ring-2 focus:ring-blue-500 transition-all">
              <option v-for="t in eventTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
          </div>

          <div class="space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Start Threshold</Label>
            <div class="relative group">
              <Clock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
              <Input type="time" v-model="eventForm.start_time" class="h-11 pl-12 bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border-white/20" />
            </div>
          </div>

          <div class="space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Termination Point</Label>
            <div class="relative group">
              <Clock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
              <Input type="time" v-model="eventForm.end_time" class="h-11 pl-12 bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border-white/20" />
            </div>
          </div>

          <div class="md:col-span-2 space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Environmental Venue</Label>
            <div class="relative group">
              <MapPin class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
              <Input v-model="eventForm.location" placeholder="Command center, Remote hub..." class="h-11 pl-12 bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border-white/20" />
            </div>
          </div>

          <div class="md:col-span-2 space-y-2">
            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Technical Briefing</Label>
            <textarea 
              v-model="eventForm.description" 
              class="flex min-h-[100px] w-full rounded-2xl border border-white/20 bg-slate-50/50 dark:bg-slate-900/50 px-4 py-3 text-sm font-medium focus:ring-2 focus:ring-blue-500 transition-all no-scrollbar"
              placeholder="Operational details for participants..."
            ></textarea>
          </div>

          <div class="md:col-span-2 space-y-3">
            <Label class="text-[10px] font-black uppercase tracking-widest text-slate-400 px-1">Visual Identifier</Label>
            <div class="flex flex-wrap gap-3 p-1">
              <button 
                v-for="c in ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#ec4899', '#8b5cf6', '#64748b']" 
                :key="c"
                @click="eventForm.color = c"
                class="w-10 h-10 rounded-2xl border-2 transition-all p-1 hover:scale-110 active:scale-90 premium-shadow"
                :class="[eventForm.color === c ? 'border-blue-500 scale-110' : 'border-transparent']"
              >
                <div class="w-full h-full rounded-xl shadow-inner" :style="{ backgroundColor: c }"></div>
              </button>
            </div>
          </div>
        </div>

        <div v-if="selectedEvent" class="flex items-center gap-4 rounded-2xl border border-blue-500/10 bg-blue-500/5 p-4">
            <Info class="h-5 w-5 text-blue-500" />
            <div class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-widest">
              Last intelligence sync: {{ new Date(selectedEvent.updated_at || selectedEvent.created_at).toLocaleString() }}
            </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-between w-full pt-4">
          <div class="flex items-center gap-3">
            <Button v-if="selectedEvent" variant="outline" class="text-rose-500 hover:bg-rose-500 hover:text-white border-rose-200 dark:border-rose-900/30 rounded-2xl h-11 px-6 font-bold" @click="deleteEvent" :disabled="isDeleting">
              <Trash2 v-if="!isDeleting" class="w-4 h-4 mr-2" />
              {{ isDeleting ? 'Purging...' : 'Delete Session' }}
            </Button>
          </div>
          <div class="flex gap-4">
            <Button variant="outline" class="rounded-2xl h-11 px-6 font-bold" @click="isModalOpen = false">Abort</Button>
            <Button class="bg-blue-600 hover:bg-blue-700 text-white rounded-2xl h-11 px-8 min-w-[140px] font-bold premium-shadow btn-transition" @click="saveEvent">
               {{ selectedEvent ? 'Synchronize' : 'Authorize Session' }}
            </Button>
          </div>
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.calendar-card {
  @apply rounded-[2rem] overflow-hidden;
}

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.animate-in {
  animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes slideIn {
  from { opacity: 0; transform: translateY(12px) scale(0.98); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
