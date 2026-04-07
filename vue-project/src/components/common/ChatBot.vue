<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Bot, MessageCircle, Send, Square, User, X } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
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
const ASSISTANT_TAGLINE = 'Always here to help'

const auth = useAuthStore()
const isOpen = ref(false)
const messages = ref<Message[]>([])
const inputMessage = ref('')
const isTyping = ref(false)
const messagesContainer = ref<HTMLElement | null>(null)
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
  return createMessage(`Hello! I'm ${ASSISTANT_NAME}. How can I help you today?`, 'bot')
}

function restoreMessages(rawMessages: string | null): Message[] {
  if (!rawMessages) return [createWelcomeMessage()]

  try {
    const parsed = JSON.parse(rawMessages) as StoredMessage[]
    if (!Array.isArray(parsed) || parsed.length === 0) {
      return [createWelcomeMessage()]
    }

    return parsed
      .filter((message) => message && typeof message.text === 'string' && (message.sender === 'user' || message.sender === 'bot'))
      .map((message) => ({
        id: message.id || `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`,
        text: message.text,
        sender: message.sender,
        timestamp: new Date(message.timestamp)
      }))
  } catch (error) {
    console.error('Unable to restore chatbot messages', error)
    return [createWelcomeMessage()]
  }
}

function persistChatState() {
  localStorage.setItem(sessionStorageKey.value, sessionId.value)
  localStorage.setItem(openStorageKey.value, JSON.stringify(isOpen.value))
  localStorage.setItem(
    messagesStorageKey.value,
    JSON.stringify(
      messages.value.map((message) => ({
        ...message,
        timestamp: message.timestamp.toISOString()
      }))
    )
  )
}

onMounted(() => {
  sessionId.value = localStorage.getItem(sessionStorageKey.value) || crypto.randomUUID()
  messages.value = restoreMessages(localStorage.getItem(messagesStorageKey.value))
  isOpen.value = localStorage.getItem(openStorageKey.value) === 'true'
  persistChatState()
  scrollToBottom()
})

onBeforeUnmount(() => {
  stopCurrentRequest(false)
})

watch(messages, persistChatState, { deep: true })
watch(isOpen, persistChatState)
watch(sessionId, persistChatState)

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const isRequestCanceled = (error: any) =>
  error?.code === 'ERR_CANCELED' || error?.name === 'CanceledError'

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
      const errorText = error?.response?.data?.error || error?.message || "Sorry, I couldn't reach the server right now. Please try again later."
      messages.value.push(
        createMessage(errorText, 'bot')
      )
    }
  } finally {
    if (activeRequestController.value === controller) {
      activeRequestController.value = null
    }
    isTyping.value = false
    scrollToBottom()
  }
}

const toggleChat = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) scrollToBottom()
}
</script>

<template>
  <!-- Chat Window -->
  <Transition name="slide-up">
    <div
      v-if="isOpen"
      class="fixed bottom-24 right-6 z-50 w-96 h-[500px] bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col"
    >
      <!-- Header -->
      <div class="p-4 bg-blue-600 text-white rounded-t-lg flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
            <Bot class="h-6 w-6" />
          </div>
          <div>
            <h3 class="font-semibold">{{ ASSISTANT_NAME }}</h3>
            <p class="text-xs text-blue-100">{{ ASSISTANT_TAGLINE }}</p>
          </div>
        </div>
        <button
          @click="isOpen = false"
          class="text-white hover:bg-white/10 rounded-full p-1 transition-colors"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Messages -->
      <div class="flex-1 p-4 overflow-y-auto" ref="messagesContainer">
        <div class="space-y-4">
          <div
            v-for="message in messages"
            :key="message.id"
            :class="['flex', message.sender === 'user' ? 'justify-end' : 'justify-start']"
          >
            <div
              :class="[
                'flex items-start space-x-2 max-w-[80%]',
                message.sender === 'user' ? 'flex-row-reverse space-x-reverse' : ''
              ]"
            >
              <div
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0',
                  message.sender === 'bot'
                    ? 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400'
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                ]"
              >
                <Bot v-if="message.sender === 'bot'" class="h-4 w-4" />
                <User v-else class="h-4 w-4" />
              </div>
              <div>
                <div
                  :class="[
                    'px-4 py-2 rounded-lg text-sm',
                    message.sender === 'user'
                      ? 'bg-blue-600 text-white'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100'
                  ]"
                >
                  <p>{{ message.text }}</p>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 px-2">
                  {{
                    message.timestamp.toLocaleTimeString([], {
                      hour: '2-digit',
                      minute: '2-digit'
                    })
                  }}
                </p>
              </div>
            </div>
          </div>

          <!-- Typing indicator -->
          <div v-if="isTyping" class="flex justify-start">
            <div class="flex items-start space-x-2">
              <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <Bot class="h-4 w-4" />
              </div>
              <div class="bg-gray-100 dark:bg-gray-700 px-4 py-2 rounded-lg">
                <div class="flex space-x-2">
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-100"></div>
                  <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce delay-200"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <form @submit.prevent="handleSendMessage" class="flex space-x-2">
          <Input
            v-model="inputMessage"
            placeholder="Type your message..."
            class="flex-1"
            :disabled="isTyping"
          />
          <Button
            v-if="isTyping"
            type="button"
            class="h-10 w-10 shrink-0 bg-red-500 px-0 text-white hover:bg-red-600"
            @click="stopCurrentRequest()"
          >
            <Square class="h-4 w-4 fill-current" />
          </Button>
          <Button
            v-else
            type="submit"
            class="h-10 w-10 shrink-0 px-0"
            :disabled="!inputMessage.trim()"
          >
            <Send class="h-4 w-4" />
          </Button>
        </form>
      </div>
    </div>
  </Transition>

  <!-- Floating Button -->
  <button
    @click="toggleChat"
    class="fixed bottom-6 right-6 z-50 w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition-transform hover:scale-110 active:scale-95"
  >
    <X v-if="isOpen" class="h-6 w-6" />
    <MessageCircle v-else class="h-6 w-6" />
  </button>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
</style>
