<script setup lang="ts">
import { computed } from 'vue'
import { BadgeCheck, MessageCircle, Plus, Search } from 'lucide-vue-next'
import { useMessagingStore } from '@/stores/messaging'

const messagingStore = useMessagingStore()

const emit = defineEmits<{
  (e: 'select-user', userId: number): void
  (e: 'update:search-query', value: string): void
}>()

interface Props {
  searchQuery?: string
}

const props = withDefaults(defineProps<Props>(), {
  searchQuery: ''
})

const filteredConversations = computed(() => {
  if (!props.searchQuery) return messagingStore.conversations
  const query = props.searchQuery.toLowerCase()
  return messagingStore.conversations.filter(conv =>
    conv.user.name.toLowerCase().includes(query)
  )
})

const filteredContacts = computed(() => {
  if (!props.searchQuery) return messagingStore.contacts.slice(0, 12)
  const query = props.searchQuery.toLowerCase()
  return messagingStore.contacts.filter(contact =>
    contact.name.toLowerCase().includes(query) ||
    contact.email.toLowerCase().includes(query) ||
    contact.role.toLowerCase().includes(query)
  )
})

function selectUser(userId: number) {
  emit('select-user', userId)
}

async function startConversation(contactId: number) {
  const contact = messagingStore.contacts.find(item => item.id === contactId)
  if (!contact) return
  await messagingStore.startConversation(contact)
}

function formatTime(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))

  if (days === 0) return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
  if (days === 1) return 'Yesterday'
  if (days < 7) return date.toLocaleDateString([], { weekday: 'short' })
  return date.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

function getInitials(name: string): string {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}
</script>

<template>
  <div class="h-full flex flex-col bg-transparent">
    <div class="border-b border-slate-200/70 bg-white/70 p-5 backdrop-blur dark:border-white/10 dark:bg-slate-950/55">
      <div class="flex items-center gap-3">
        <div class="flex h-12 w-12 items-center justify-center rounded-3xl bg-[linear-gradient(135deg,#2563eb,#1d4ed8)] text-white shadow-lg shadow-blue-600/25">
          <MessageCircle class="h-5 w-5" />
        </div>
        <div class="min-w-0 flex-1">
          <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-white">Messages</h2>
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Instant team chat for admins, HR, and managers</p>
        </div>
        <span v-if="messagingStore.unreadCount > 0" class="rounded-full bg-rose-500 px-2.5 py-1 text-xs font-bold text-white shadow-sm">
          {{ messagingStore.unreadCount }}
        </span>
      </div>

      <div class="mt-4 rounded-[24px] border border-slate-200 bg-white/85 px-3 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center gap-2">
          <Search class="h-4 w-4 text-slate-400" />
          <input
            :value="searchQuery"
            @input="emit('update:search-query', ($event.target as HTMLInputElement).value)"
            type="text"
            placeholder="Search people or roles..."
            class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400 dark:text-slate-200"
          />
        </div>
      </div>
    </div>

    <div class="flex-1 overflow-y-auto bg-[linear-gradient(180deg,rgba(255,255,255,0.65),rgba(248,250,252,0.92))] px-3 py-4 dark:bg-[linear-gradient(180deg,rgba(2,6,23,0.2),rgba(2,6,23,0.65))]">
      <div v-if="messagingStore.isLoading" class="p-4 text-center text-gray-500">
        Loading conversations...
      </div>

      <div v-else class="space-y-6">
        <section>
          <div class="mb-3 flex items-center justify-between px-2">
            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">Recent</h3>
            <span class="text-[11px] font-semibold text-slate-400">{{ filteredConversations.length }}</span>
          </div>

          <div v-if="filteredConversations.length === 0" class="rounded-3xl border border-dashed border-slate-200 p-6 text-center text-slate-500 dark:border-slate-800">
            <MessageCircle class="mx-auto mb-3 h-10 w-10 opacity-50" />
            <p class="font-semibold">No conversations yet</p>
            <p class="mt-1 text-xs">Use the people list below to start one.</p>
          </div>

          <div v-else class="space-y-2">
            <button
              v-for="conv in filteredConversations"
              :key="conv.user.id"
              @click="selectUser(conv.user.id)"
              class="w-full rounded-[28px] border px-4 py-3 text-left transition"
              :class="messagingStore.activeUserId === conv.user.id
                ? 'border-blue-200 bg-[linear-gradient(135deg,rgba(239,246,255,0.98),rgba(219,234,254,0.95))] shadow-[0_18px_35px_-26px_rgba(37,99,235,0.7)] dark:border-blue-900/50 dark:bg-blue-950/25'
                : 'border-transparent bg-white/72 hover:border-slate-200 hover:bg-white dark:bg-slate-900/60 dark:hover:border-slate-800 dark:hover:bg-slate-900'"
            >
              <div class="flex items-center gap-3">
                <div class="relative flex-shrink-0">
                  <div
                    v-if="conv.user.avatar_url"
                    class="h-12 w-12 rounded-2xl bg-cover bg-center"
                    :style="{ backgroundImage: `url(${conv.user.avatar_url})` }"
                  />
                  <div
                    v-else
                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 text-sm font-bold text-white"
                  >
                    {{ getInitials(conv.user.name) }}
                  </div>
                  <span
                    v-if="conv.is_online"
                    class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-950"
                  />
                </div>

                <div class="min-w-0 flex-1">
                  <div class="flex items-center justify-between gap-3">
                    <span class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ conv.user.name }}</span>
                    <span class="shrink-0 text-[11px] font-semibold text-slate-400">
                      {{ conv.last_message?.created_at ? formatTime(conv.last_message.created_at) : '' }}
                    </span>
                  </div>
                  <div class="mt-1 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                      <p class="truncate text-xs text-slate-500 dark:text-slate-400">
                        {{ conv.last_message?.is_mine ? 'You: ' : '' }}{{ conv.last_message?.content || 'No messages yet' }}
                      </p>
                      <p
                        v-if="conv.last_message?.is_mine && conv.last_message?.status"
                        class="mt-1 text-[10px] font-bold uppercase tracking-[0.16em]"
                        :class="conv.last_message.status === 'read'
                          ? 'text-blue-500'
                          : conv.last_message.status === 'delivered'
                            ? 'text-emerald-500'
                            : 'text-slate-400'"
                      >
                        {{ conv.last_message.status }}
                      </p>
                    </div>
                    <span
                      v-if="conv.unread_count > 0"
                      class="rounded-full bg-blue-600 px-2 py-0.5 text-[11px] font-bold text-white"
                    >
                      {{ conv.unread_count }}
                    </span>
                  </div>
                </div>
              </div>
            </button>
          </div>
        </section>

        <section>
          <div class="mb-3 flex items-center justify-between px-2">
            <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">People</h3>
            <span class="text-[11px] font-semibold text-slate-400">Start new chat</span>
          </div>

          <div v-if="messagingStore.isContactsLoading" class="p-4 text-center text-sm text-slate-500">
            Loading people...
          </div>

          <div v-else-if="filteredContacts.length === 0" class="rounded-3xl border border-dashed border-slate-200 p-6 text-center text-slate-500 dark:border-slate-800">
            <p class="font-semibold">Nobody matched your search.</p>
          </div>

          <div v-else class="space-y-2">
            <button
              v-for="contact in filteredContacts"
              :key="contact.id"
              @click="startConversation(contact.id)"
              class="w-full rounded-[26px] border border-transparent bg-white/50 px-4 py-3 text-left transition hover:border-slate-200 hover:bg-white dark:bg-slate-900/35 dark:hover:border-slate-800 dark:hover:bg-slate-900/70"
            >
              <div class="flex items-center gap-3">
                <div class="relative flex-shrink-0">
                  <div
                    v-if="contact.avatar_url"
                    class="h-11 w-11 rounded-2xl bg-cover bg-center"
                    :style="{ backgroundImage: `url(${contact.avatar_url})` }"
                  />
                  <div
                    v-else
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-200 text-sm font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-200"
                  >
                    {{ getInitials(contact.name) }}
                  </div>
                  <span
                    v-if="contact.is_online"
                    class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white bg-emerald-500 dark:border-slate-950"
                  />
                </div>

                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <span class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ contact.name }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                      <BadgeCheck class="h-3 w-3" />
                      {{ contact.role }}
                    </span>
                  </div>
                  <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ contact.email }}</p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-300">
                  <Plus class="h-4 w-4" />
                </div>
              </div>
            </button>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>
