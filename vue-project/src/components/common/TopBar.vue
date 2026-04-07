<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Menu, Bell, LogOut, User as UserIcon, Settings, ExternalLink } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import ThemeToggle from '@/components/common/ThemeToggle.vue'
import { useNotificationsStore } from '@/stores/notifications'
import { useAuthStore } from '@/stores/auth'
import { getAvatarUrl } from '@/api/http'

const emit = defineEmits(['toggle-sidebar'])
const router = useRouter()
const auth = useAuthStore()
const notifications = useNotificationsStore()
const user = computed(() => auth.user)
const avatarUrl = computed(() => getAvatarUrl(user.value))
const rootRef = ref<HTMLElement | null>(null)

const getInitials = (name: string) => {
  if (!name) return 'U'
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
}
const isDropdownOpen = ref(false)
const isNotificationsOpen = ref(false)

onMounted(() => {
  if (auth.isAuthenticated) {
    notifications.fetchNotifications()
  }

  document.addEventListener('click', handleDocumentClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
})

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
    notifications.fetchNotifications()
  }
}

function toggleProfileMenu() {
  isDropdownOpen.value = !isDropdownOpen.value
  if (isDropdownOpen.value) {
    isNotificationsOpen.value = false
  }
}

function openNotification(item: { id: string | number; action?: string }) {
  notifications.markAsRead(item.id)
  isNotificationsOpen.value = false
  if (item.action) {
    router.push(item.action)
  }
}
</script>

<template>
  <header class="h-16 bg-white/95 backdrop-blur-md dark:bg-slate-950/95 border-b border-slate-200/30 dark:border-slate-700/30 px-6 flex items-center justify-between sticky top-0 z-40">
    <div class="flex items-center space-x-4">
      <button
        type="button"
        @click="emit('toggle-sidebar')"
        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 lg:hidden focus:outline-none"
      >
        <Menu class="h-6 w-6" />
      </button>
    </div>

    <div ref="rootRef" class="flex items-center space-x-4 relative">
      <!-- Theme Toggle -->
      <ThemeToggle />

      <!-- Notifications -->
      <Button
        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 relative w-10 h-10 p-0 !bg-transparent"
        type="button"
        @click="toggleNotifications"
      >
        <Bell class="h-5 w-5" />
        <span
          v-if="notifications.unreadCount"
          class="absolute -top-1 -right-1 flex min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-semibold text-white"
        >
          {{ notifications.unreadCount }}
        </span>
      </Button>

      <div
        v-if="isNotificationsOpen"
        class="absolute right-0 top-12 z-50 w-full max-w-[360px] rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800 sm:right-16"
      >
        <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
          <div>
            <div class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ notifications.unreadCount }} unread</div>
          </div>
          <button class="text-xs font-medium text-blue-600 dark:text-blue-400" @click="notifications.markAllAsRead">
            Mark all read
          </button>
        </div>
        <div v-if="notifications.isLoading" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          Loading...
        </div>
        <div v-else-if="!notifications.items.length" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
          No notifications yet.
        </div>
        <div v-else class="max-h-[420px] overflow-y-auto p-2">
          <button
            v-for="item in notifications.items.slice(0, 6)"
            :key="item.id"
            type="button"
            class="w-full rounded-xl px-3 py-3 text-left transition hover:bg-gray-50 dark:hover:bg-gray-700/60"
            :class="item.read ? '' : 'bg-blue-50/70 dark:bg-blue-950/20'"
            @click="openNotification(item)"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.title || 'Update' }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ item.message }}</div>
              </div>
              <ExternalLink class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
            </div>
          </button>
        </div>
        <div class="border-t border-gray-200 px-4 py-3 text-right dark:border-gray-700">
          <button class="text-sm font-medium text-blue-600 dark:text-blue-400" @click="router.push('/notifications'); isNotificationsOpen = false">
            View all
          </button>
        </div>
      </div>

      <!-- User Menu -->
      <div class="relative">
        <button
          type="button"
          @click="toggleProfileMenu"
          class="flex items-center space-x-2 focus:outline-none"
        >
          <div class="h-8 w-8 overflow-hidden rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-semibold text-xs border">
            <img v-if="avatarUrl" :src="avatarUrl" alt="Profile photo" class="h-full w-full object-cover" />
            <span v-else>{{ user ? getInitials(user.name) : 'U' }}</span>
          </div>
        </button>

        <div v-if="isDropdownOpen" class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50">
          <div class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-white border-b dark:border-gray-700">
            My Account
          </div>
          <button type="button" class="flex w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" @click="router.push('/profile'); isDropdownOpen = false">
            <UserIcon class="mr-2 h-4 w-4" /> Profile
          </button>
          <button type="button" class="flex w-full px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" @click="router.push('/settings'); isDropdownOpen = false">
            <Settings class="mr-2 h-4 w-4" /> Settings
          </button>
          <div class="border-t dark:border-gray-700 my-1"></div>
          <button type="button" @click="handleLogout" class="flex w-full px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-left">
            <LogOut class="mr-2 h-4 w-4" /> Logout
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
