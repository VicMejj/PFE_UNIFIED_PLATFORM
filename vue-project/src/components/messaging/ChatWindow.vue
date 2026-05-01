<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted } from 'vue'
import { Send, Paperclip, File, ArrowLeft, Download, Check, CheckCheck, ShieldCheck } from 'lucide-vue-next'
import { useMessagingStore } from '@/stores/messaging'
import { messagingApi } from '@/api/laravel/messaging'

const emit = defineEmits<{
  (e: 'back'): void
}>()

const messagingStore = useMessagingStore()
const messageInput = ref('')
const composerInput = ref<HTMLTextAreaElement | null>(null)
const messagesContainer = ref<HTMLElement | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)
const typingTimeout = ref<number | null>(null)
const isTyping = ref(false)

const activeUser = computed(() => messagingStore.activeUser)

const canSend = computed(() => {
  return messageInput.value.trim().length > 0 && !messagingStore.isSending
})

watch(() => messagingStore.currentConversation, async () => {
  await nextTick()
  scrollToBottom()
}, { deep: true })

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

function resizeComposer() {
  if (!composerInput.value) return
  composerInput.value.style.height = '0px'
  composerInput.value.style.height = `${Math.min(composerInput.value.scrollHeight, 144)}px`
}

async function handleSend() {
  if (!canSend.value) return
  
  const content = messageInput.value.trim()
  messageInput.value = ''
  resizeComposer()
  await messagingStore.sendMessage(content)
}

function handleInput() {
  resizeComposer()

  if (!isTyping.value) {
    isTyping.value = true
    messagingStore.setTyping(true)
  }
  
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
  }
  
  typingTimeout.value = window.setTimeout(() => {
    isTyping.value = false
    messagingStore.setTyping(false)
  }, 2000)
}

function handleFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    const file = input.files[0]
    messagingStore.sendMessage('', file)
    input.value = ''
  }
}

async function downloadAttachment(messageId: number) {
  try {
    const blob = await messagingApi.downloadAttachment(messageId)
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = 'attachment'
    a.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download attachment:', error)
  }
}

function formatTime(dateString: string): string {
  return new Date(dateString).toLocaleTimeString([], { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

function getInitials(name: string): string {
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

function getStatusIcon(status: string) {
  if (status === 'sent') return Check
  if (status === 'read') return CheckCheck
  if (status === 'delivered') return Check
  return Check
}

function getStatusColor(status: string) {
  if (status === 'sent') return 'text-slate-400'
  if (status === 'read') return 'text-blue-500'
  if (status === 'delivered') return 'text-emerald-500'
  return 'text-slate-400'
}

function getStatusLabel(status: string) {
  if (status === 'read') return 'Read'
  if (status === 'delivered') return 'Delivered'
  return 'Sent'
}

function formatDayLabel(dateString: string): string {
  const date = new Date(dateString)
  const today = new Date()
  const yesterday = new Date()
  yesterday.setDate(today.getDate() - 1)

  if (date.toDateString() === today.toDateString()) return 'Today'
  if (date.toDateString() === yesterday.toDateString()) return 'Yesterday'

  return date.toLocaleDateString([], {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  })
}

const messageGroups = computed(() => {
  const groups: Array<{ label: string; messages: typeof messagingStore.currentConversation }> = []

  messagingStore.currentConversation.forEach((message) => {
    const label = formatDayLabel(message.created_at)
    const group = groups[groups.length - 1]

    if (!group || group.label !== label) {
      groups.push({ label, messages: [message] as typeof messagingStore.currentConversation })
      return
    }

    group.messages.push(message)
  })

  return groups
})

onMounted(() => {
  resizeComposer()
  scrollToBottom()
})
</script>

<template>
  <div class="flex h-full min-h-0 flex-col bg-[linear-gradient(180deg,rgba(255,255,255,0.72),rgba(248,250,252,0.94))] dark:bg-[linear-gradient(180deg,rgba(2,6,23,0.6),rgba(2,6,23,0.92))]">
    <!-- HEADER -->
    <div v-if="activeUser" class="flex items-center gap-3 border-b border-slate-200/70 bg-white/70 px-5 py-4 backdrop-blur dark:border-white/10 dark:bg-slate-950/55">
      <button 
        @click="emit('back')" 
        class="lg:hidden p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg"
      >
        <ArrowLeft class="w-5 h-5" />
      </button>
      
      <div class="relative">
        <div
          v-if="activeUser.user.avatar_url"
          class="w-10 h-10 rounded-full bg-cover bg-center"
          :style="{ backgroundImage: `url(${activeUser.user.avatar_url})` }"
        />
        <div
          v-else
          class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-medium text-sm"
        >
          {{ getInitials(activeUser.user.name) }}
        </div>
        <span
          v-if="activeUser.is_online"
          class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white dark:border-slate-900 rounded-full"
        />
      </div>
      
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
          <h3 class="truncate text-base font-black text-slate-900 dark:text-white">{{ activeUser.user.name }}</h3>
          <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:bg-slate-800 dark:text-slate-300">
            <ShieldCheck class="h-3 w-3" />
            {{ activeUser.user.role || 'team' }}
          </span>
        </div>
        <p class="text-xs font-medium text-gray-500">
          {{ activeUser.is_online ? 'Active now' : 'Away right now' }}
        </p>
      </div>
    </div>

    <!-- MESSAGES -->
    <div 
      ref="messagesContainer" 
      class="min-h-0 flex-1 overflow-y-auto bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.08),_transparent_28%),linear-gradient(180deg,rgba(248,250,252,0.86),rgba(255,255,255,0.92))] px-4 py-5 pb-24 dark:bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.12),_transparent_24%),linear-gradient(180deg,rgba(2,6,23,0.58),rgba(2,6,23,0.94))] sm:px-6 sm:pb-28"
    >
      <div v-if="messagingStore.isLoading" class="flex items-center justify-center h-full">
        <p class="text-gray-500">Loading messages...</p>
      </div>

      <div v-else-if="!messagingStore.hasActiveConversation" class="flex items-center justify-center h-full">
        <div class="text-center">
          <p class="mb-2 text-lg font-semibold text-slate-700 dark:text-slate-200">Select a conversation to start messaging</p>
          <p class="text-sm text-slate-500 dark:text-slate-400">Search for an admin, HR partner, or manager from the left panel.</p>
        </div>
      </div>

      <template v-else>
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-7">
          <section
            v-for="group in messageGroups"
            :key="group.label"
            class="space-y-4"
          >
            <div class="flex justify-center">
              <span class="rounded-full border border-slate-200/80 bg-white/85 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500 shadow-sm dark:border-white/10 dark:bg-slate-900/70 dark:text-slate-300">
                {{ group.label }}
              </span>
            </div>

            <div
              v-for="message in group.messages"
              :key="message.id"
              class="flex px-1"
              :class="message.is_mine ? 'justify-end' : 'justify-start'"
            >
              <div class="max-w-[min(78%,34rem)] space-y-1.5">
                <div
                  class="rounded-[26px] px-4 py-3 shadow-[0_14px_38px_-26px_rgba(15,23,42,0.45)]"
                  :class="message.is_mine
                    ? 'rounded-br-lg bg-[linear-gradient(135deg,#2563eb,#1d4ed8)] text-white'
                    : 'rounded-bl-lg border border-slate-200/80 bg-white/92 text-slate-800 dark:border-white/10 dark:bg-slate-900/86 dark:text-white'"
                >
                  <p v-if="message.content" class="whitespace-pre-wrap break-words text-[15px] leading-6">
                    {{ message.content }}
                  </p>

                  <div
                    v-if="message.attachment_path"
                    class="mt-3 flex items-center gap-3 rounded-2xl border px-3 py-3"
                    :class="message.is_mine
                      ? 'border-white/15 bg-white/10'
                      : 'border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/5'"
                  >
                    <div
                      class="flex h-10 w-10 items-center justify-center rounded-2xl"
                      :class="message.is_mine ? 'bg-white/10' : 'bg-slate-900/5 dark:bg-white/10'"
                    >
                      <File class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="truncate text-sm font-semibold">{{ message.attachment_name || 'Attachment' }}</p>
                      <p class="text-xs opacity-70">Tap to download</p>
                    </div>
                    <button
                      @click="downloadAttachment(message.id)"
                      class="rounded-full p-2 transition"
                      :class="message.is_mine ? 'hover:bg-white/10' : 'hover:bg-slate-100 dark:hover:bg-white/10'"
                    >
                      <Download class="h-4 w-4" />
                    </button>
                  </div>

                </div>

                <div
                  class="flex items-center gap-1.5 px-1 text-[11px] font-semibold"
                  :class="message.is_mine ? 'justify-end text-slate-500 dark:text-slate-400' : 'justify-start text-slate-400 dark:text-slate-500'"
                >
                  <span>{{ formatTime(message.created_at) }}</span>
                  <template v-if="message.is_mine">
                    <span class="text-slate-300 dark:text-slate-600">•</span>
                    <component
                      :is="getStatusIcon(message.status)"
                      class="h-3.5 w-3.5"
                      :class="getStatusColor(message.status)"
                    />
                    <span>{{ getStatusLabel(message.status) }}</span>
                  </template>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- TYPING INDICATOR -->
        <div 
          v-if="activeUser && messagingStore.isUserTyping(activeUser.user.id)"
          class="flex justify-start"
        >
          <div class="rounded-[24px] rounded-bl-lg border border-slate-200/80 bg-white/90 px-4 py-3 shadow-sm dark:border-white/10 dark:bg-slate-900/80">
            <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-400">
              {{ activeUser.user.name }} is typing
            </div>
            <div class="flex gap-1">
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms" />
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms" />
              <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms" />
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- INPUT -->
    <div v-if="messagingStore.hasActiveConversation" class="sticky bottom-0 border-t border-slate-200/70 bg-white/92 px-4 pb-[calc(1rem+env(safe-area-inset-bottom))] pt-3 backdrop-blur supports-[backdrop-filter]:bg-white/80 dark:border-white/10 dark:bg-slate-950/88 dark:supports-[backdrop-filter]:bg-slate-950/78">
      <div v-if="messagingStore.errorMessage" class="mb-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
        {{ messagingStore.errorMessage }}
      </div>
      <form @submit.prevent="handleSend" class="mx-auto flex w-full max-w-4xl items-end gap-3 rounded-[30px] border border-slate-200/80 bg-white/95 px-3 py-3 shadow-[0_18px_50px_-30px_rgba(15,23,42,0.35)] dark:border-slate-800/80 dark:bg-slate-900/95">
        <input
          ref="fileInput"
          type="file"
          class="hidden"
          accept="image/*,.pdf,.doc,.docx"
          @change="handleFileSelect"
        />
        <button
          type="button"
          @click="fileInput?.click()"
          class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-gray-500 transition hover:border-slate-300 hover:bg-slate-100 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-800"
        >
          <Paperclip class="w-5 h-5" />
        </button>
        
        <div class="flex-1 rounded-[24px] border border-slate-200 bg-white px-4 py-2.5 shadow-sm dark:border-slate-800 dark:bg-slate-950">
          <textarea
            ref="composerInput"
            v-model="messageInput"
            @input="handleInput"
            placeholder="Write a message..."
            rows="1"
            class="max-h-36 min-h-[24px] w-full resize-none overflow-y-auto bg-transparent text-[15px] leading-6 text-slate-800 outline-none placeholder:text-slate-400 dark:text-white"
            @keydown.enter.exact.prevent="handleSend"
            @keydown.enter.shift.stop
          />
        </div>
        
        <button
          type="submit"
          :disabled="!canSend"
          class="rounded-2xl bg-blue-600 p-3 text-white shadow-lg shadow-blue-600/25 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
          <Send class="w-5 h-5" />
        </button>
      </form>
    </div>
  </div>
</template>
