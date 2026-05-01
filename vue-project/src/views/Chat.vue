<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import { LoaderCircle, Wifi, WifiOff } from 'lucide-vue-next'
import { useMessagingStore } from '@/stores/messaging'
import { useAuthStore } from '@/stores/auth'
import { useRealTimeMessages } from '@/composables/useRealTimeMessages'
import ConversationList from '@/components/messaging/ConversationList.vue'
import ChatWindow from '@/components/messaging/ChatWindow.vue'

const messagingStore = useMessagingStore()
const authStore = useAuthStore()
const { isRealtimeConfigured, subscribeToMessages, subscribeToMessagingChannel, unsubscribeFromMessages, unsubscribeFromMessagingChannel, watchRealtimeStatus } = useRealTimeMessages()

const searchQuery = ref('')
let pollTimer: ReturnType<typeof setInterval> | null = null
let stopRealtimeWatch: (() => void) | null = null
function getRealtimeStatus() {
  return messagingStore.realtimeStatus || (messagingStore.realtimeAvailable ? 'live' : 'fallback')
}

function updateRealtimeStatus(status: 'connecting' | 'live' | 'fallback') {
  if (typeof messagingStore.setRealtimeStatus === 'function') {
    messagingStore.setRealtimeStatus(status)
    return
  }

  if (typeof messagingStore.setRealtimeAvailable === 'function') {
    messagingStore.setRealtimeAvailable(status === 'live')
  }
}

const realtimeNotice = computed(() => {
  const status = getRealtimeStatus()

  if (status === 'connecting') {
    return {
      tone: 'info',
      icon: LoaderCircle,
      text: 'Connecting live updates. New messages should switch to instant delivery in a moment.',
    }
  }

  if (status === 'fallback') {
    return {
      tone: 'warning',
      icon: WifiOff,
      text: 'Live updates are offline right now, so this chat is temporarily refreshing in the background.',
    }
  }

  if (status === 'live') {
    return {
      tone: 'success',
      icon: Wifi,
      text: 'Live updates connected.',
    }
  }

  return null
})

function startPolling() {
  if (pollTimer) return

  updateRealtimeStatus('fallback')
  
  const pollActiveConversation = async () => {
    if (messagingStore.activeUserId) {
      try {
        await messagingStore.fetchMessagesSince(messagingStore.activeUserId)
      } catch (e) {
        console.warn('Polling error:', e)
      }
    }
  }
  
  pollTimer = setInterval(async () => {
    try {
      await messagingStore.fetchConversations()
      await messagingStore.fetchUnreadCount()
      await messagingStore.fetchContacts(searchQuery.value)
      await pollActiveConversation()
    } catch (e) {
      console.warn('Background polling error:', e)
    }
  }, 5000)
}

function stopPolling() {
  if (!pollTimer) return
  clearInterval(pollTimer)
  pollTimer = null
}

onMounted(async () => {
  messagingStore.setCurrentUserId(authStore.user?.id ?? null)
  updateRealtimeStatus(isRealtimeConfigured ? 'connecting' : 'fallback')
  await messagingStore.setOnline()
  await messagingStore.fetchConversations()
  await messagingStore.fetchContacts()
  await messagingStore.fetchUnreadCount()
  
  if (authStore.user?.id && isRealtimeConfigured) {
    stopRealtimeWatch = watchRealtimeStatus({
      onConnected: stopPolling,
      onDisconnected: startPolling,
    })
    subscribeToMessages(authStore.user.id)
    subscribeToMessagingChannel()
  } else {
    startPolling()
  }
})

async function handleSelectUser(userId: number) {
  await messagingStore.fetchConversation(userId)
}

function handleBack() {
  messagingStore.clearActiveConversation()
}

async function handleSearchUpdate(value: string) {
  searchQuery.value = value
  await messagingStore.fetchContacts(value)
}

onUnmounted(() => {
  messagingStore.setOffline()
  if (authStore.user?.id && isRealtimeConfigured) {
    unsubscribeFromMessages(authStore.user.id)
    unsubscribeFromMessagingChannel()
  }
  stopRealtimeWatch?.()
  stopPolling()
})
</script>

<template>
  <div class="min-h-[calc(100vh-4rem)] bg-[radial-gradient(circle_at_top_right,_rgba(59,130,246,0.18),_transparent_30%),linear-gradient(180deg,_rgba(15,23,42,0.02),_transparent_60%)] dark:bg-[radial-gradient(circle_at_top_right,_rgba(59,130,246,0.14),_transparent_28%),linear-gradient(180deg,_rgba(2,6,23,0.9),_rgba(2,6,23,1))]">
    <div class="mx-auto flex h-[calc(100vh-5.5rem)] min-h-0 max-w-[1500px] gap-6 px-4 py-6 lg:px-6">
      <aside class="min-h-0 w-full overflow-hidden rounded-[28px] border border-slate-200/70 bg-white/80 shadow-[0_24px_80px_-32px_rgba(15,23,42,0.45)] backdrop-blur xl:w-[390px] dark:border-white/10 dark:bg-slate-950/70">
        <ConversationList
          :search-query="searchQuery"
          @update:search-query="handleSearchUpdate"
          @select-user="handleSelectUser"
        />
      </aside>

      <section class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-[32px] border border-slate-200/70 bg-white/85 shadow-[0_24px_90px_-40px_rgba(15,23,42,0.5)] backdrop-blur dark:border-white/10 dark:bg-slate-950/75">
        <div
          v-if="realtimeNotice"
          class="flex items-center gap-3 border-b px-5 py-3 text-sm"
          :class="realtimeNotice.tone === 'warning'
            ? 'border-amber-200/70 bg-amber-50/90 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200'
            : realtimeNotice.tone === 'success'
              ? 'border-emerald-200/70 bg-emerald-50/90 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200'
              : 'border-sky-200/70 bg-sky-50/90 text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-200'"
        >
          <component :is="realtimeNotice.icon" class="h-4 w-4" :class="realtimeNotice.tone === 'info' ? 'animate-spin' : ''" />
          <span>{{ realtimeNotice.text }}</span>
        </div>
        <ChatWindow @back="handleBack" />
      </section>
    </div>
  </div>
</template>
