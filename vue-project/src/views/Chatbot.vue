<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Send, Loader, MessageCircle, Copy, Check } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Badge from '@/components/ui/Badge.vue'
import { djangoAiApi } from '@/api/django/ai'
import { useAuthStore } from '@/stores/auth'
import { v4 as uuidv4 } from 'uuid'

interface ChatMessage {
  id: string
  sender: 'USER' | 'BOT'
  text: string
  timestamp: Date
  intent?: string
  entities?: string[]
  context?: Record<string, any>
  isError?: boolean
}

const authStore = useAuthStore()
const sessionId = ref(localStorage.getItem('chatbot_session_id') || uuidv4())

const messages = ref<ChatMessage[]>([])
const inputMessage = ref('')
const isLoading = ref(false)
const isInitialized = ref(false)
const errorMessage = ref('')
const copiedId = ref<string | null>(null)
const abortController = ref<AbortController | null>(null)
const historyKey = `chatbot_messages_${authStore.user?.id ?? 'guest'}`

const suggestions = [
  'Help me with employee data',
  'Predict turnover risks',
  'Find optimal leave dates',
  'Assess a loan application',
  'Show recent analytics',
  'Generate a report'
]

const canUseChatbot = computed(() => {
  const roles = authStore.user?.roles || []
  return roles.some((r: any) => ['admin', 'manager', 'rh', 'hr'].includes(r.name?.toLowerCase()))
})

const isNetworkFailure = (error: any) =>
  error?.code === 'ERR_NETWORK' ||
  error?.code === 'ECONNABORTED' ||
  String(error?.message || '').toLowerCase().includes('connection refused')

const messageGroups = computed(() => {
  const groups: Array<{ sender: 'USER' | 'BOT'; messages: ChatMessage[] }> = []
  let currentGroup: { sender: 'USER' | 'BOT'; messages: ChatMessage[] } | null = null

  messages.value.forEach((msg) => {
    if (!currentGroup || currentGroup.sender !== msg.sender) {
      currentGroup = {
        sender: msg.sender,
        messages: [msg]
      }
      groups.push(currentGroup)
    } else {
      currentGroup.messages.push(msg)
    }
  })

  return groups
})

const initializeChat = async () => {
  if (!canUseChatbot.value) {
    errorMessage.value = 'You do not have permission to use the chatbot. Admin, Manager, or HR role required.'
    isInitialized.value = true
    return
  }

  localStorage.setItem('chatbot_session_id', sessionId.value)

  try {
    const history: any = await djangoAiApi.getChatHistory(sessionId.value)
    const conversation = Array.isArray(history?.data) ? history.data[0] : history?.data?.[0]
    const items = Array.isArray(conversation?.messages) ? conversation.messages : []
    messages.value = items.map((item: any) => ({
      id: String(item.id ?? uuidv4()),
      sender: item.sender,
      text: item.text,
      timestamp: new Date(item.created_at || item.timestamp || new Date().toISOString()),
      intent: item.intent,
      entities: item.entities || [],
      context: item.context
    }))
  } catch {
    const cached = localStorage.getItem(historyKey)
    messages.value = cached ? JSON.parse(cached) : []
  }

  if (!messages.value.length) {
    messages.value.push({
      id: uuidv4(),
      sender: 'BOT',
      text: `Welcome to Mejj AI Assistant! 👋\n\nI'm here to help you with:\n• Employee insights & turnover predictions\n• Optimal leave date recommendations\n• Loan risk assessments\n• HR analytics & reporting\n• Document processing & classification\n• Fraud detection\n\nHow can I assist you today?`,
      timestamp: new Date(),
      intent: 'greeting',
      entities: []
    })
  }

  isInitialized.value = true
}

const sendMessage = async () => {
  if (!inputMessage.value.trim() || isLoading.value) return

  const userMessage = inputMessage.value.trim()
  inputMessage.value = ''

  // Add user message to history
  messages.value.push({
    id: uuidv4(),
    sender: 'USER',
    text: userMessage,
    timestamp: new Date()
  })

  isLoading.value = true
  errorMessage.value = ''

  try {
    abortController.value = new AbortController()

    const response = await djangoAiApi.sendChatMessage(
      userMessage,
      sessionId.value,
      abortController.value.signal
    ) as any

    if (response?.success === false) {
      const serverError = response.error || 'Unable to get response from chatbot.'
      errorMessage.value = serverError
      messages.value.push({
        id: uuidv4(),
        sender: 'BOT',
        text: 'Sorry, I encountered an error. Please try again later.',
        timestamp: new Date(),
        isError: true
      })
      return
    }

    const botText = response?.response || response?.message || response?.reply || 'I understand. How else can I help?'

    // Add bot response to history
    messages.value.push({
      id: uuidv4(),
      sender: 'BOT',
      text: botText,
      timestamp: new Date(),
      intent: response.intent,
      entities: response.entities || [],
      context: response.context
    })
    localStorage.setItem(historyKey, JSON.stringify(messages.value))
  } catch (err: any) {
    if (err.name !== 'AbortError') {
      console.error('Chatbot error:', err)
      errorMessage.value = isNetworkFailure(err)
        ? 'The Django AI service is offline right now, so this view is using local fallback behavior.'
        : err.response?.data?.detail || 'Unable to get response from chatbot.'
      messages.value.push({
        id: uuidv4(),
        sender: 'BOT',
        text: 'Sorry, I encountered an error. Please try again later.',
        timestamp: new Date(),
        isError: true
      })
    }
  } finally {
    isLoading.value = false
  }
}

const suggestAction = (suggestion: string) => {
  inputMessage.value = suggestion
}

const copyMessage = (messageId: string) => {
  const msg = messages.value.find((m) => m.id === messageId)
  if (msg) {
    navigator.clipboard.writeText(msg.text)
    copiedId.value = messageId
    setTimeout(() => {
      copiedId.value = null
    }, 2000)
  }
}

const stopGeneration = () => {
  if (abortController.value) {
    abortController.value.abort()
    isLoading.value = false
  }
}

const clearHistory = () => {
  if (window.confirm('Clear all chat history?')) {
    messages.value = messages.value.filter((m) => m.sender === 'BOT' && m.intent === 'greeting')
    localStorage.setItem(historyKey, JSON.stringify(messages.value))
  }
}

const formatMessage = (text: string) => {
  // Convert markdown-like syntax to readable text
  return text
    .replace(/\*\*(.*?)\*\*/g, (_, p1) => `${p1}`)
    .replace(/\n/g, '<br>')
}

onMounted(initializeChat)
</script>

<template>
  <div class="h-full flex flex-col">
    <!-- HEADER -->
    <div class="border-b bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-slate-900 dark:to-slate-800 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <MessageCircle class="w-6 h-6 text-blue-600" />
          <div>
            <h2 class="text-2xl font-bold">Mejj AI Assistant</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">Smart HR & Business Intelligence</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button v-if="messages.length > 1" size="sm" variant="outline" @click="clearHistory">
            Clear
          </Button>
        </div>
      </div>
    </div>

    <!-- ACCESS WARNING -->
    <div v-if="!canUseChatbot" class="bg-amber-50 border-b border-amber-200 px-6 py-4 dark:bg-amber-950/30 dark:border-amber-900">
      <p class="text-sm text-amber-800 dark:text-amber-200">
        ⚠️ Chatbot access restricted: Only Admin, Manager, RH, and HR roles can use this feature.
      </p>
    </div>

    <!-- ERROR MESSAGE -->
    <div v-if="errorMessage" class="bg-rose-50 border-b border-rose-200 px-6 py-3 dark:bg-rose-950/30 dark:border-rose-900">
      <p class="text-sm text-rose-800 dark:text-rose-200">{{ errorMessage }}</p>
    </div>

    <!-- MESSAGES CONTAINER -->
    <div class="flex-1 overflow-y-auto p-6 space-y-6">
      <div v-if="!isInitialized" class="flex items-center justify-center h-full">
        <div class="text-center">
          <Loader class="w-8 h-8 animate-spin mx-auto mb-4 text-blue-600" />
          <p class="text-gray-600">Initializing chatbot...</p>
        </div>
      </div>

      <template v-else v-for="group in messageGroups" :key="group.messages[0].id">
        <!-- USER MESSAGES -->
        <div v-if="group.sender === 'USER'" class="flex justify-end">
          <div class="space-y-2 max-w-xs lg:max-w-md">
            <div v-for="msg in group.messages" :key="msg.id" class="group relative">
              <div class="bg-blue-600 text-white rounded-xl rounded-tr-none px-4 py-3 shadow-sm">
                <p class="text-sm whitespace-pre-wrap break-words">{{ msg.text }}</p>
                <p class="text-xs mt-2 opacity-70">{{ new Date(msg.timestamp).toLocaleTimeString() }}</p>
              </div>
              <Button
                size="sm"
                variant="ghost"
                class="absolute -left-12 top-0 opacity-0 group-hover:opacity-100 transition"
                @click="copyMessage(msg.id)"
              >
                <Copy v-if="copiedId !== msg.id" class="w-4 h-4" />
                <Check v-else class="w-4 h-4 text-green-600" />
              </Button>
            </div>
          </div>
        </div>

        <!-- BOT MESSAGES -->
        <div v-if="group.sender === 'BOT'" class="flex justify-start">
          <div class="space-y-2 max-w-2xl">
            <div v-for="msg in group.messages" :key="msg.id" class="group relative">
              <div
                :class="[
                  'rounded-xl rounded-tl-none px-4 py-3 shadow-sm',
                  msg.isError
                    ? 'bg-rose-50 border border-rose-200 dark:bg-rose-950/30 dark:border-rose-900'
                    : 'bg-slate-100 dark:bg-slate-800'
                ]"
              >
                <p class="text-sm whitespace-pre-wrap break-words" v-html="formatMessage(msg.text)" />
                
                <!-- CONTEXT INFO -->
                <div v-if="msg.entities && msg.entities.length > 0" class="mt-3 pt-3 border-t border-slate-300 dark:border-slate-700">
                  <p class="text-xs font-semibold text-slate-600 dark:text-slate-400 mb-2">Entities Detected:</p>
                  <div class="flex flex-wrap gap-2">
                    <Badge v-for="(entity, idx) in msg.entities" :key="idx" variant="secondary">
                      {{ entity }}
                    </Badge>
                  </div>
                </div>

                <p class="text-xs mt-2 opacity-70">{{ new Date(msg.timestamp).toLocaleTimeString() }}</p>
              </div>
              <Button
                size="sm"
                variant="ghost"
                class="absolute -left-12 top-0 opacity-0 group-hover:opacity-100 transition"
                @click="copyMessage(msg.id)"
              >
                <Copy v-if="copiedId !== msg.id" class="w-4 h-4" />
                <Check v-else class="w-4 h-4 text-green-600" />
              </Button>
            </div>
          </div>
        </div>
      </template>

      <!-- TYPING INDICATOR -->
      <div v-if="isLoading" class="flex justify-start">
        <div class="bg-slate-100 dark:bg-slate-800 rounded-xl rounded-tl-none px-4 py-3">
          <div class="flex gap-2">
            <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0s" />
            <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.2s" />
            <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.4s" />
          </div>
        </div>
      </div>
    </div>

    <!-- SUGGESTIONS -->
    <div v-if="isInitialized && !isLoading && messages.length <= 1" class="border-t bg-slate-50 dark:bg-slate-900 px-6 py-4">
      <p class="text-xs font-semibold text-slate-600 dark:text-slate-400 mb-3">Quick Actions:</p>
      <div class="flex flex-wrap gap-2">
        <Button
          v-for="suggestion in suggestions"
          :key="suggestion"
          size="sm"
          variant="outline"
          @click="suggestAction(suggestion)"
        >
          {{ suggestion }}
        </Button>
      </div>
    </div>

    <!-- INPUT AREA -->
    <div v-if="isInitialized && canUseChatbot" class="border-t bg-white dark:bg-slate-950 px-6 py-4">
      <form @submit.prevent="sendMessage" class="flex gap-3">
        <Input
          v-model="inputMessage"
          :disabled="isLoading"
          placeholder="Ask me anything about HR, employees, or analytics..."
          class="flex-1"
          @keydown.escape="stopGeneration"
        />
        <Button
          v-if="!isLoading"
          type="submit"
          :disabled="!inputMessage.trim()"
          class="bg-blue-600 hover:bg-blue-700 text-white"
        >
          <Send class="w-4 h-4" />
        </Button>
        <Button v-else type="button" variant="destructive" @click="stopGeneration">
          Stop
        </Button>
      </form>
      <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
        Session ID: {{ sessionId.slice(0, 8) }}...
      </p>
    </div>
  </div>
</template>

<style scoped>
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.dark ::-webkit-scrollbar-thumb {
  background: #475569;
}

.dark ::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}
</style>
