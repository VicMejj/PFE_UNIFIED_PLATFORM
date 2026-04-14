<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  Bell, CheckCircle, AlertTriangle, Info, LogOut,
  Menu, Settings, User as UserIcon,
  X
} from 'lucide-vue-next'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import GlobalSearch from '@/components/common/GlobalSearch.vue'
import Button from '@/components/ui/Button.vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useAuthStore } from '@/stores/auth'
import { getAvatarUrl } from '@/api/http'

const emit = defineEmits(['toggle-sidebar'])
const router = useRouter()
const auth = useAuthStore()
const notifications = useNotificationsStore()
const user = computed(() => auth.user)
const canUseGlobalSearch = computed(() => ['admin', 'rh_manager', 'manager'].includes(String(user.value?.role ?? '')))
const avatarUrl = computed(() => getAvatarUrl(user.value))
const rootRef = ref<HTMLElement | null>(null)
const isDropdownOpen = ref(false)
const isNotificationsOpen = ref(false)
const getInitials = (name: string) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}

let pollInterval: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  if (auth.isAuthenticated) {
    void notifications.fetchNotifications()
    pollInterval = setInterval(() => void notifications.fetchNotifications(), 15_000)
  }
  document.addEventListener('click', handleDocumentClick)
  window.addEventListener('focus', handleWindowFocus)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  window.removeEventListener('focus', handleWindowFocus)
  if (pollInterval) clearInterval(pollInterval)
})

function handleWindowFocus() {
  void notifications.fetchNotifications()
}

const handleDocumentClick = (event: MouseEvent) => {
  if (!rootRef.value?.contains(event.target as Node)) {
    isDropdownOpen.value = false
    isNotificationsOpen.value = false
  }
}

const handleLogout = async () => {
  await auth.logout()
  router.push('/login')
}

function toggleNotifications() {
  isNotificationsOpen.value = !isNotificationsOpen.value
  if (isNotificationsOpen.value) {
    isDropdownOpen.value = false
    void notifications.fetchNotifications()
  }
}

function toggleProfileMenu() {
  isDropdownOpen.value = !isDropdownOpen.value
  if (isDropdownOpen.value) isNotificationsOpen.value = false
}

function openNotification(item: { id: string | number; action?: string }) {
  void notifications.markAsRead(item.id)
  isNotificationsOpen.value = false
  if (item.action) router.push(item.action)
}

function relativeTime(dateStr: string): string {
  const date = new Date(dateStr)
  const now = Date.now()
  const diff = Math.floor((now - date.getTime()) / 1000)
  if (diff < 60) return 'just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return `${Math.floor(diff / 86400)}d ago`
}

const notifConfig = (type: string) => {
  if (type === 'success') return { icon: CheckCircle, color: 'text-emerald-500', bg: 'bg-emerald-500/10' }
  if (type === 'warning') return { icon: AlertTriangle, color: 'text-amber-500', bg: 'bg-amber-500/10' }
  if (type === 'destructive') return { icon: X, color: 'text-rose-500', bg: 'bg-rose-500/10' }
  return { icon: Info, color: 'text-blue-500', bg: 'bg-blue-500/10' }
}
</script>

<template>
  <header class="h-16 sticky top-0 z-40 bg-white/70 dark:bg-slate-950/60 backdrop-blur-3xl border-b border-white/40 dark:border-white/5 transition-all duration-300 px-6 sm:px-8 flex items-center justify-between">
    <!-- Left: sidebar toggle & Search -->
    <div class="flex items-center gap-6 flex-1">
      <button 
        type="button" 
        class="lg:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" 
        @click="emit('toggle-sidebar')"
      >
        <Menu class="h-5 w-5" />
      </button>

      <div v-if="canUseGlobalSearch" class="hidden md:block w-full max-w-xl">
        <GlobalSearch />
      </div>
    </div>

    <!-- Right: actions -->
    <div ref="rootRef" class="flex items-center gap-2 sm:gap-4">
      <ThemeToggle />

      <!-- Notifications -->
      <div class="relative">
        <button
          type="button"
          @click="toggleNotifications"
          class="relative h-10 w-10 flex items-center justify-center rounded-2xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all active:scale-90"
        >
          <Bell class="h-5 w-5" />
          <span
            v-if="notifications.unreadCount"
            class="absolute -top-1 -right-1 min-w-5 h-5 px-1.5 rounded-full bg-rose-500 text-white text-[10px] font-black leading-none flex items-center justify-center ring-2 ring-white dark:ring-slate-950 shadow-lg"
          >
            {{ notifications.unreadCount > 99 ? '99+' : notifications.unreadCount }}
          </span>
        </button>

        <Transition name="slide-fade">
          <div v-if="isNotificationsOpen" class="absolute right-0 mt-3 w-[380px] glass-card premium-shadow rounded-[2rem] overflow-hidden animate-in">
            <div class="p-6 border-b border-white/10 dark:border-slate-800/50 flex items-center justify-between">
              <div>
                <h3 class="font-black text-slate-900 dark:text-white">Signals</h3>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500 dark:text-slate-400">{{ notifications.unreadCount }} urgent items</p>
              </div>
              <button class="text-[10px] font-black uppercase tracking-widest text-blue-500 hover:text-blue-600 transition-colors" @click="notifications.markAllAsRead">
                Mark all read
              </button>
            </div>

            <div class="max-h-[420px] overflow-y-auto p-3 no-scrollbar space-y-2">
               <div v-if="notifications.isLoading" class="p-4 space-y-3">
                 <div v-for="i in 3" :key="i" class="h-16 rounded-2xl bg-slate-100 dark:bg-slate-900 animate-pulse" />
               </div>

               <div v-else-if="!notifications.items.length" class="py-20 text-center">
                 <Bell class="h-10 w-10 text-slate-200 dark:text-slate-800 mx-auto mb-3" />
                 <p class="text-sm font-bold text-slate-400">All caught up</p>
                 <p class="mt-1 text-xs text-slate-500 dark:text-slate-500">New alerts will appear here instantly.</p>
               </div>

               <div 
                 v-else 
                 v-for="item in notifications.items.slice(0, 8)" 
                 :key="item.id"
                 @click="openNotification(item)"
                 class="group relative p-4 rounded-2xl border border-transparent hover:border-blue-500/20 hover:bg-white dark:hover:bg-slate-900 transition-all cursor-pointer overflow-hidden"
               >
                 <div class="flex gap-4">
                    <div :class="['h-10 w-10 rounded-xl flex items-center justify-center shrink-0 ring-1 ring-inset ring-white/10', notifConfig(item.type).bg, notifConfig(item.type).color]">
                      <component :is="notifConfig(item.type).icon" class="h-5 w-5" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex justify-between items-start">
                        <h4 class="text-sm font-black text-slate-900 dark:text-white truncate pr-4">{{ item.title || 'System Update' }}</h4>
                        <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 shrink-0">{{ relativeTime(item.created_at) }}</span>
                      </div>
                      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 line-clamp-2 leading-relaxed">{{ item.message }}</p>
                      <div class="mt-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-400">
                        {{ item.read ? 'Read' : 'Unread' }}
                      </div>
                    </div>
                 </div>
                 <div v-if="!item.read" class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
               </div>
            </div>

            <div class="p-4 border-t border-white/10 dark:border-slate-800/50">
               <Button variant="ghost" class="w-full rounded-2xl text-[11px] font-black uppercase tracking-widest text-slate-500 hover:text-blue-500" @click="router.push('/notifications'); isNotificationsOpen = false">
                 Launch Full Inbox
               </Button>
            </div>
          </div>
        </Transition>
      </div>

      <!-- Profile Shell -->
      <div class="relative">
        <button 
          @click="toggleProfileMenu"
          class="flex items-center gap-3 p-1 rounded-2xl border border-transparent hover:border-white/40 dark:hover:border-white/5 hover:bg-white/40 dark:hover:bg-slate-900/40 transition-all duration-300"
        >
          <div class="h-10 w-10 rounded-2xl overflow-hidden ring-2 ring-white/20 dark:ring-white/5 shadow-xl bg-blue-600 text-white flex items-center justify-center font-black text-sm">
            <img v-if="avatarUrl" :src="avatarUrl" alt="Profile" class="h-full w-full object-cover" />
            <span v-else>{{ getInitials(user?.name || '') }}</span>
          </div>
          <div class="hidden lg:block text-left mr-2">
            <div class="text-xs font-black text-slate-900 dark:text-white leading-none capitalize">{{ user?.name || 'User' }}</div>
            <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ user?.role?.replace('_', ' ') }}</div>
          </div>
        </button>

        <Transition name="slide-fade">
          <div v-if="isDropdownOpen" class="absolute right-0 mt-3 w-64 glass-card premium-shadow rounded-[2rem] overflow-hidden animate-in p-2">
            <div class="p-4 border-b border-white/5 dark:border-slate-800/50 mb-1">
              <div class="text-[10px] font-black uppercase tracking-[2px] text-slate-400">Account Control</div>
            </div>
            
            <button @click="router.push('/profile'); isDropdownOpen = false" class="w-full flex items-center gap-3 p-4 rounded-2xl hover:bg-white dark:hover:bg-slate-900 text-sm font-bold text-slate-600 dark:text-slate-300 transition-all group">
              <UserIcon class="h-4 w-4 group-hover:text-blue-500" />
              Manage Identity
            </button>
            <button @click="router.push('/settings'); isDropdownOpen = false" class="w-full flex items-center gap-3 p-4 rounded-2xl hover:bg-white dark:hover:bg-slate-900 text-sm font-bold text-slate-600 dark:text-slate-300 transition-all group">
              <Settings class="h-4 w-4 group-hover:text-blue-500" />
              Settings
            </button>
            
            <div class="h-px bg-white/5 dark:bg-slate-800/50 my-2 mx-4"></div>
            
            <button @click="handleLogout" class="w-full flex items-center gap-3 p-4 rounded-2xl hover:bg-rose-500/10 text-rose-500 text-sm font-black transition-all">
              <LogOut class="h-4 w-4" />
              Log Out
            </button>
          </div>
        </Transition>
      </div>
    </div>
  </header>
</template>

<style scoped>
.slide-fade-enter-active, .slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-fade-enter-from, .slide-fade-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.98);
}

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
