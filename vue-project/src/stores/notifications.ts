import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { platformApi, type NotificationItem } from '@/api/laravel/platform'
import { getUserReadNotificationsKey } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref<NotificationItem[]>([])
  const isLoading = ref(false)
  const unreadCount = computed(() => items.value.filter((item) => !item.read).length)

  function getReadIds(): string[] {
    const auth = useAuthStore()
    const raw = localStorage.getItem(getUserReadNotificationsKey(auth.user))
    return raw ? JSON.parse(raw) : []
  }

  function saveReadIds(ids: string[]) {
    const auth = useAuthStore()
    localStorage.setItem(getUserReadNotificationsKey(auth.user), JSON.stringify(ids))
  }

  function applyReadState(nextItems: NotificationItem[]) {
    const readIds = new Set(getReadIds())
    return nextItems.map((item) => ({
      ...item,
      read: item.read || readIds.has(String(item.id))
    }))
  }

  async function fetchNotifications() {
    isLoading.value = true
    try {
      const laravelItems = await platformApi.getNotifications()
      const combined = (laravelItems as NotificationItem[]).sort(
        (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
      )

      items.value = applyReadState(combined)
    } finally {
      isLoading.value = false
    }
  }

  async function markAsRead(id: string | number) {
    const readIds = new Set(getReadIds())
    readIds.add(String(id))
    saveReadIds([...readIds])
    items.value = items.value.map((item) =>
      String(item.id) === String(id) ? { ...item, read: true } : item
    )

    try {
      await platformApi.markNotificationRead(id)
    } catch (error) {
      console.error('Unable to persist notification read state', error)
    }
  }

  async function markAllAsRead() {
    const ids = items.value.map((item) => String(item.id))
    saveReadIds(ids)
    items.value = items.value.map((item) => ({ ...item, read: true }))

    try {
      await platformApi.markAllNotificationsRead()
    } catch (error) {
      console.error('Unable to persist all-read notification state', error)
    }
  }

  return {
    items,
    isLoading,
    unreadCount,
    fetchNotifications,
    markAsRead,
    markAllAsRead
  }
})
