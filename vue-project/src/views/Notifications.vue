<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, CheckCheck, ExternalLink, RefreshCcw } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import CardContent from '@/components/ui/CardContent.vue'
import CardDescription from '@/components/ui/CardDescription.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardTitle from '@/components/ui/CardTitle.vue'
import Button from '@/components/ui/Button.vue'
import Badge from '@/components/ui/Badge.vue'
import { useNotificationsStore } from '@/stores/notifications'

const router = useRouter()
const notifications = useNotificationsStore()
const selectedFilter = ref<'all' | 'unread'>('all')

onMounted(() => {
  void notifications.fetchNotifications()
})

const filteredNotifications = computed(() => {
  if (selectedFilter.value === 'unread') {
    return notifications.items.filter((item) => !item.read)
  }
  return notifications.items
})

function variantFor(type: string) {
  if (type === 'success') return 'success'
  if (type === 'warning') return 'warning'
  if (type === 'destructive') return 'destructive'
  return 'default'
}

function openNotification(item: { id: string | number; action?: string }) {
  void notifications.markAsRead(item.id)
  if (item.action) {
    router.push(item.action)
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Notifications</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Review account updates, leave activity, and system alerts in one place.
        </p>
      </div>
      <div class="flex gap-3">
        <Button class="border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800" @click="notifications.fetchNotifications">
          <RefreshCcw class="mr-2 h-4 w-4" />
          Refresh
        </Button>
        <Button @click="notifications.markAllAsRead">
          <CheckCheck class="mr-2 h-4 w-4" />
          Mark All Read
        </Button>
      </div>
    </div>

    <Card>
      <CardHeader>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <CardTitle class="flex items-center gap-2">
              <Bell class="h-5 w-5 text-sky-500" />
              Notification Center
            </CardTitle>
            <CardDescription>
              {{ notifications.unreadCount }} unread item<span v-if="notifications.unreadCount !== 1">s</span>.
            </CardDescription>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <Button
              :class="selectedFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-100'"
              variant="secondary"
              @click="selectedFilter = 'all'"
            >
              All
            </Button>
            <Button
              :class="selectedFilter === 'unread' ? 'bg-sky-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-100'"
              variant="secondary"
              @click="selectedFilter = 'unread'"
            >
              Unread
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <div v-if="notifications.isLoading" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
          Loading notifications...
        </div>
        <div v-else-if="!notifications.items.length" class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">
          No notifications yet.
        </div>
        <div v-else class="space-y-4">
          <button
            v-for="item in filteredNotifications"
            :key="item.id"
            type="button"
            class="w-full rounded-2xl border p-4 text-left transition hover:bg-slate-50 dark:hover:bg-slate-900/60"
            :class="item.read ? 'border-slate-200 dark:border-slate-800' : 'border-sky-200 bg-sky-50/60 dark:border-sky-900/40 dark:bg-sky-950/20'"
            @click="openNotification(item)"
          >
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <Badge :variant="variantFor(item.type)">{{ item.type }}</Badge>
                  <span class="text-xs text-slate-500 dark:text-slate-400">{{ new Date(item.created_at).toLocaleString() }}</span>
                </div>
                <div class="text-base font-semibold text-slate-900 dark:text-white">{{ item.title || 'Update' }}</div>
                <p class="text-sm text-slate-600 dark:text-slate-300">{{ item.message }}</p>
              </div>
              <div class="flex items-center gap-2 text-sm text-sky-600 dark:text-sky-400">
                <span>{{ item.read ? 'Read' : 'Open' }}</span>
                <ExternalLink class="h-4 w-4" />
              </div>
            </div>
          </button>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
