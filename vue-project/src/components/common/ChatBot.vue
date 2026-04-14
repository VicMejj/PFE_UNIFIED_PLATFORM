<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Bot, MessageCircle, Send, Square, X } from 'lucide-vue-next'
import { djangoAiApi } from '@/api/django/ai'
import { useAuthStore } from '@/stores/auth'

interface Message {
  id: string
  text: string
  sender: 'user' | 'bot'
  timestamp: Date
}
interface StoredMessage {
  id: string
  text: string
  sender: 'user' | 'bot'
  timestamp: string
}

const ASSISTANT_NAME = 'Mejj'
const ASSISTANT_TAGLINE = 'Your HR AI assistant'

const auth = useAuthStore()
const isOpen = ref(false)
const messages = ref<Message[]>([])
const inputMessage = ref('')
const isTyping = ref(false)
const isHydrated = ref(false)
const messagesContainer = ref<HTMLElement | null>(null)
const chatWindowRef = ref<HTMLElement | null>(null)
const activeRequestController = ref<AbortController | null>(null)
const storageScope = computed(() => auth.user?.id ?? 'guest')
const sessionStorageKey = computed(() => `chatbot_session_id_${storageScope.value}`)
const messagesStorageKey = computed(() => `chatbot_messages_${storageScope.value}`)
const openStorageKey = computed(() => `chatbot_is_open_${storageScope.value}`)
const sessionId = ref('')

function createMessage(text: string, sender: 'user' | 'bot'): Message {
  return {
    id: `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
    text,
    sender,
    timestamp: new Date()
  }
}

function createWelcomeMessage(): Message {
  return createMessage(`Hello! I'm ${ASSISTANT_NAME}, your HR assistant. How can I help you today?`, 'bot')
}

function restoreMessages(rawMessages: string | null): Message[] {
  if (!rawMessages) return [createWelcomeMessage()]
  try {
    const parsed = JSON.parse(rawMessages) as StoredMessage[]
    if (!Array.isArray(parsed) || parsed.length === 0) return [createWelcomeMessage()]
    return parsed
      .filter(m => m && typeof m.text === 'string' && (m.sender === 'user' || m.sender === 'bot'))
      .map(m => ({
        id: m.id || `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
        text: m.text,
        sender: m.sender,
        timestamp: new Date(m.timestamp)
      }))
  } catch {
    return [createWelcomeMessage()]
  }
}

function normalizeApiMessage(item: any): Message | null {
  const sender = item?.sender === 'BOT' ? 'bot' : item?.sender === 'USER' ? 'user' : null
  if (!sender || typeof item?.text !== 'string') return null
  return {
    id: String(item.id ?? `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`),
    text: item.text,
    sender,
    timestamp: new Date(item.timestamp || item.created_at || new Date().toISOString())
  }
}

function flattenHistory(payload: any): Message[] {
  const conversations = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : []
  const selectedConversation = conversations[0]
  const rawMessages = Array.isArray(selectedConversation?.messages) ? selectedConversation.messages : []
  const normalized = rawMessages.map(normalizeApiMessage).filter(Boolean) as Message[]
  return normalized.length ? normalized : [createWelcomeMessage()]
}

function conversationHasUserTurn(msgs: Message[]): boolean {
  return msgs.some(m => m.sender === 'user')
}

/** Prefer server thread when it has real turns; otherwise keep longer local cache (fixes empty API wiping history). */
function pickRicherThread(server: Message[], local: Message[]): Message[] {
  if (conversationHasUserTurn(server)) return server
  if (conversationHasUserTurn(local)) return local
  if (server.length > local.length) return server
  if (local.length > 0) return local
  return server.length > 0 ? server : local
}

function persistChatState() {
  localStorage.setItem(sessionStorageKey.value, sessionId.value)
  localStorage.setItem(openStorageKey.value, JSON.stringify(isOpen.value))
  localStorage.setItem(
    messagesStorageKey.value,
    JSON.stringify(messages.value.map(m => ({ ...m, timestamp: m.timestamp.toISOString() })))
  )
}

onMounted(() => {
  sessionId.value = localStorage.getItem(sessionStorageKey.value) || crypto.randomUUID()
  isOpen.value = localStorage.getItem(openStorageKey.value) === 'true'
  const localMessages = restoreMessages(localStorage.getItem(messagesStorageKey.value))
  document.addEventListener('click', onDocumentClick)

  void (async () => {
    try {
      const history = await djangoAiApi.getChatHistory(sessionId.value)
      messages.value = pickRicherThread(flattenHistory(history), localMessages)
    } catch {
      messages.value = localMessages
    } finally {
      isHydrated.value = true
      persistChatState()
      scrollToBottom()
    }
  })()
})

onBeforeUnmount(() => {
  stopCurrentRequest(false)
  document.removeEventListener('click', onDocumentClick)
})

function onDocumentClick(e: MouseEvent) {
  if (!isOpen.value) return
  // Close if click is outside the chat window AND outside the toggle button
  const target = e.target as Node
  if (chatWindowRef.value && !chatWindowRef.value.contains(target)) {
    // The toggle button has data-chat-toggle attr — don't close if clicking it
    const toggleBtn = document.getElementById('chat-toggle-btn')
    if (toggleBtn && toggleBtn.contains(target)) return
    isOpen.value = false
  }
}

watch(messages, persistChatState, { deep: true })
watch(isOpen, persistChatState)
watch(sessionId, persistChatState)

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTo({ top: messagesContainer.value.scrollHeight, behavior: 'smooth' })
  }
}

const isRequestCanceled = (error: any) =>
  error?.code === 'ERR_CANCELED' || error?.name === 'CanceledError'

const isNetworkFailure = (error: any) =>
  error?.code === 'ERR_NETWORK' ||
  error?.code === 'ECONNABORTED' ||
  String(error?.message || '').toLowerCase().includes('connection refused')

const stopCurrentRequest = (announceStop = true) => {
  if (!activeRequestController.value) return
  activeRequestController.value.abort()
  activeRequestController.value = null
  isTyping.value = false
  if (announceStop) {
    messages.value.push(createMessage('Request stopped. Send another message whenever you are ready.', 'bot'))
  }
  scrollToBottom()
}

const handleSendMessage = async () => {
  const trimmedMessage = inputMessage.value.trim()
  if (!trimmedMessage || isTyping.value) return

  const userMessage = createMessage(trimmedMessage, 'user')
  const controller = new AbortController()
  messages.value.push(userMessage)
  inputMessage.value = ''
  isTyping.value = true
  activeRequestController.value = controller
  scrollToBottom()

  try {
    const data: any = await djangoAiApi.sendChatMessage(userMessage.text, sessionId.value, controller.signal)
    messages.value.push(
      createMessage(
        data.response || data.reply || data.message || "I don't have an answer for that yet.",
        'bot'
      )
    )
  } catch (error: any) {
    if (!isRequestCanceled(error)) {
      const errorText = isNetworkFailure(error)
        ? 'The Django AI service is offline right now, so I switched to a local fallback.'
        : error?.response?.data?.error || error?.message || "Sorry, I couldn't reach the server right now."
      messages.value.push(createMessage(errorText, 'bot'))
    }
  } finally {
    if (activeRequestController.value === controller) activeRequestController.value = null
    isTyping.value = false
    scrollToBottom()
  }
}

const toggleChat = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) scrollToBottom()
}

function formatTime(date: Date): string {
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <!-- ── Chat Window ── -->
  <Transition name="chat-slide">
    <div
      v-if="isOpen"
      ref="chatWindowRef"
      class="chat-window"
    >
      <!-- Header -->
      <div class="chat-header">
        <div class="chat-header__info">
          <div class="chat-header__avatar">
            <Bot class="h-5 w-5" />
          </div>
          <div>
            <h3 class="chat-header__name">{{ ASSISTANT_NAME }}</h3>
            <p class="chat-header__status">
              <span class="chat-header__status-dot" />
              {{ ASSISTANT_TAGLINE }}
            </p>
          </div>
        </div>
        <button class="chat-header__close" @click="isOpen = false" aria-label="Close chat">
          <X class="h-4 w-4" />
        </button>
      </div>

      <!-- Messages -->
      <div class="chat-messages" ref="messagesContainer">
        <div v-if="!isHydrated" class="chat-hydration">
          <span class="chat-hydration__dot" />
          Restoring your conversation...
        </div>
        <div
          v-for="message in messages"
          :key="message.id"
          class="chat-msg"
          :class="message.sender === 'user' ? 'chat-msg--user' : 'chat-msg--bot'"
        >
          <!-- Bot avatar -->
          <div v-if="message.sender === 'bot'" class="chat-msg__avatar">
            <Bot class="h-3.5 w-3.5" />
          </div>

          <div class="chat-msg__content">
            <div class="chat-msg__bubble">{{ message.text }}</div>
            <p class="chat-msg__time">{{ formatTime(message.timestamp) }}</p>
          </div>
        </div>

        <!-- Typing indicator -->
        <div v-if="isTyping" class="chat-msg chat-msg--bot">
          <div class="chat-msg__avatar">
            <Bot class="h-3.5 w-3.5" />
          </div>
          <div class="chat-msg__content">
            <div class="chat-msg__bubble chat-msg__bubble--typing">
              <span class="typing-dot" style="animation-delay: 0ms" />
              <span class="typing-dot" style="animation-delay: 160ms" />
              <span class="typing-dot" style="animation-delay: 320ms" />
            </div>
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="chat-input-bar">
        <form @submit.prevent="handleSendMessage" class="chat-input-form">
          <input
            v-model="inputMessage"
            placeholder="Type your message…"
            class="chat-input"
            :disabled="isTyping"
            autocomplete="off"
          />
          <button
            v-if="isTyping"
            type="button"
            class="chat-send-btn chat-send-btn--stop"
            @click="stopCurrentRequest()"
            aria-label="Stop"
          >
            <Square class="h-4 w-4 fill-current" />
          </button>
          <button
            v-else
            type="submit"
            class="chat-send-btn"
            :class="{ 'chat-send-btn--disabled': !inputMessage.trim() }"
            :disabled="!inputMessage.trim()"
            aria-label="Send"
          >
            <Send class="h-4 w-4" />
          </button>
        </form>
      </div>
    </div>
  </Transition>

  <!-- ── Floating Toggle Button ── -->
  <button
    id="chat-toggle-btn"
    @click.stop="toggleChat"
    class="chat-fab"
    :class="{ 'chat-fab--open': isOpen }"
    aria-label="Toggle chat"
  >
    <Transition name="fab-icon" mode="out-in">
      <X v-if="isOpen" key="close" class="h-6 w-6" />
      <MessageCircle v-else key="open" class="h-6 w-6" />
    </Transition>
  </button>
</template>

<style scoped>
/* ── Chat window ── */
.chat-window {
  position: fixed;
  bottom: 88px;
  right: 24px;
  z-index: 50;
  width: 372px;
  height: 520px;
  display: flex;
  flex-direction: column;
  background: #fff;
  border: 1px solid rgba(148,163,184,0.18);
  border-radius: 24px;
  box-shadow: 0 24px 80px rgba(15,23,42,0.18), 0 4px 16px rgba(15,23,42,0.06);
  overflow: hidden;
}
.dark .chat-window {
  background: #0f172a;
  border-color: rgba(51,65,85,0.4);
  box-shadow: 0 24px 80px rgba(0,0,0,0.45);
}

/* ── Header ── */
.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 18px;
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
  flex-shrink: 0;
}
.chat-header__info {
  display: flex;
  align-items: center;
  gap: 12px;
}
.chat-header__avatar {
  width: 38px; height: 38px;
  border-radius: 12px;
  background: rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center;
  color: #fff;
}
.chat-header__name {
  font-size: 14px;
  font-weight: 700;
  color: #fff;
}
.chat-header__status {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: rgba(255,255,255,0.75);
  margin-top: 1px;
}
.chat-header__status-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #4ade80;
  box-shadow: 0 0 0 2px rgba(74,222,128,0.35);
}
.chat-header__close {
  width: 30px; height: 30px;
  border-radius: 8px;
  background: rgba(255,255,255,0.15);
  display: flex; align-items: center; justify-content: center;
  color: rgba(255,255,255,0.85);
  transition: background 0.15s;
}
.chat-header__close:hover { background: rgba(255,255,255,0.25); }

/* ── Messages ── */
.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 18px 14px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 transparent;
}
.dark .chat-messages { scrollbar-color: #334155 transparent; }

/* ── Message row ── */
.chat-msg { display: flex; align-items: flex-end; gap: 8px; }
.chat-msg--user { flex-direction: row-reverse; }
.chat-msg--bot { flex-direction: row; }

.chat-msg__avatar {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: #eff6ff;
  color: #3b82f6;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dark .chat-msg__avatar { background: rgba(37,99,235,0.15); color: #60a5fa; }

.chat-msg__content { display: flex; flex-direction: column; max-width: 78%; }
.chat-msg--user .chat-msg__content { align-items: flex-end; }
.chat-msg--bot .chat-msg__content { align-items: flex-start; }

/* ── Bubble ── */
.chat-msg__bubble {
  padding: 10px 14px;
  font-size: 13.5px;
  line-height: 1.5;
  word-break: break-word;
}
.chat-msg--user .chat-msg__bubble {
  background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%);
  color: #fff;
  border-radius: 18px 18px 4px 18px;
  box-shadow: 0 2px 8px rgba(37,99,235,0.25);
}
.chat-msg--bot .chat-msg__bubble {
  background: #f1f5f9;
  color: #1e293b;
  border-radius: 18px 18px 18px 4px;
}
.dark .chat-msg--bot .chat-msg__bubble {
  background: #1e293b;
  color: #f1f5f9;
}

/* ── Time ── */
.chat-msg__time {
  font-size: 10px;
  color: #94a3b8;
  margin-top: 4px;
  padding: 0 4px;
}

/* ── Typing indicator ── */
.chat-msg__bubble--typing {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 12px 16px;
}
.typing-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #94a3b8;
  animation: typing-bounce 1.2s ease-in-out infinite;
}
@keyframes typing-bounce {
  0%, 80%, 100% { transform: translateY(0); opacity: 0.5; }
  40%           { transform: translateY(-5px); opacity: 1; }
}

/* ── Input bar ── */
.chat-input-bar {
  padding: 12px 14px;
  border-top: 1px solid #f1f5f9;
  flex-shrink: 0;
}
.dark .chat-input-bar { border-color: #1e293b; }

.chat-input-form {
  display: flex;
  gap: 8px;
  align-items: center;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 6px 6px 6px 14px;
  transition: border-color 0.15s;
}
.chat-input-form:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
}
.dark .chat-input-form {
  background: #1e293b;
  border-color: #334155;
}
.dark .chat-input-form:focus-within { border-color: #3b82f6; }

.chat-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 13px;
  color: #1e293b;
}
.dark .chat-input { color: #f1f5f9; }
.chat-input::placeholder { color: #94a3b8; }

.chat-send-btn {
  width: 34px; height: 34px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  background: #2563eb;
  color: #fff;
  flex-shrink: 0;
  transition: background 0.15s, transform 0.1s;
}
.chat-send-btn:hover:not(:disabled) { background: #1d4ed8; transform: scale(1.05); }
.chat-send-btn--stop { background: #ef4444; }
.chat-send-btn--stop:hover { background: #dc2626; }
.chat-send-btn--disabled { background: #cbd5e1; cursor: not-allowed; }
.dark .chat-send-btn--disabled { background: #334155; }

/* ── FAB ── */
.chat-fab {
  position: fixed;
  bottom: 24px; right: 24px;
  z-index: 50;
  width: 56px; height: 56px;
  background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
  color: #fff;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 8px 24px rgba(37,99,235,0.4);
  transition: transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.2s;
}
.chat-fab:hover { transform: scale(1.08); box-shadow: 0 12px 32px rgba(37,99,235,0.5); }
.chat-fab:active { transform: scale(0.96); }
.chat-fab--open { background: linear-gradient(135deg, #374151 0%, #6b7280 100%); box-shadow: 0 8px 24px rgba(55,65,81,0.35); }

/* ── Chat window animation ── */
.chat-slide-enter-active,
.chat-slide-leave-active {
  transition: opacity 0.25s cubic-bezier(0.4,0,0.2,1),
              transform 0.25s cubic-bezier(0.4,0,0.2,1);
}
.chat-slide-enter-from,
.chat-slide-leave-to {
  opacity: 0;
  transform: translateY(16px) scale(0.97);
}

/* ── FAB icon swap animation ── */
.fab-icon-enter-active,
.fab-icon-leave-active { transition: opacity 0.15s, transform 0.15s; }
.fab-icon-enter-from { opacity: 0; transform: rotate(-90deg) scale(0.7); }
.fab-icon-leave-to   { opacity: 0; transform: rotate(90deg) scale(0.7); }

.chat-hydration {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 0.8125rem;
  color: rgb(100 116 139);
}

.chat-hydration__dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 9999px;
  background: rgb(59 130 246);
  box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.35);
  animation: pulse-ring 1.4s ease-out infinite;
}

@keyframes pulse-ring {
  0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.35); }
  70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
  100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
}
</style>
