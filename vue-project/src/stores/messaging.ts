import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { messagingApi, type ChatMessage, type ConversationUser, type MessagingContact, type SendMessagePayload } from '@/api/laravel/messaging'

type ChatStatus = ChatMessage['status']
type RealtimeStatus = 'idle' | 'connecting' | 'live' | 'fallback'

export const useMessagingStore = defineStore('messaging', () => {
  const conversations = ref<ConversationUser[]>([])
  const contacts = ref<MessagingContact[]>([])
  const currentConversation = ref<ChatMessage[]>([])
  const currentUserId = ref<number | null>(null)
  const activeUserId = ref<number | null>(null)
  const isLoading = ref(false)
  const isSending = ref(false)
  const isContactsLoading = ref(false)
  const unreadCount = ref(0)
  const onlineUsers = ref<number[]>([])
  const typingUsers = ref<Map<number, boolean>>(new Map())
  const errorMessage = ref('')
  const realtimeAvailable = ref(false)
  const realtimeStatus = ref<RealtimeStatus>('idle')
  let tempMessageId = -1

  const hasActiveConversation = computed(() => activeUserId.value !== null)

  const activeUser = computed(() => {
    if (!activeUserId.value) return null

    const existingConversation = conversations.value.find(c => c.user.id === activeUserId.value)
    if (existingConversation) return existingConversation

    const contact = contacts.value.find(c => c.id === activeUserId.value)
    return contact ? {
      user: {
        id: contact.id,
        name: contact.name,
        avatar_url: contact.avatar_url,
        email: contact.email,
        role: contact.role,
      },
      last_message: null,
      unread_count: 0,
      is_online: isUserOnline(contact.id),
    } : null
  })

  async function fetchConversations() {
    isLoading.value = true
    errorMessage.value = ''
    try {
      conversations.value = await messagingApi.getConversations()
    } catch (error) {
      console.error('Failed to fetch conversations:', error)
      errorMessage.value = 'Messages could not be loaded right now.'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchConversation(userId: number) {
    isLoading.value = true
    activeUserId.value = userId
    errorMessage.value = ''

    try {
      currentConversation.value = (await messagingApi.getConversation(userId)).map(normalizeMessage)
      await messagingApi.markConversationAsRead(userId)
      markConversationMessagesAsRead(userId)

      const conversation = conversations.value.find(c => c.user.id === userId)
      if (conversation) {
        conversation.unread_count = 0
      }

      await fetchUnreadCount()
    } catch (error) {
      console.error('Failed to fetch conversation:', error)
      errorMessage.value = 'This conversation could not be opened.'
    } finally {
      isLoading.value = false
    }
  }

  async function fetchMessagesSince(userId: number) {
    if (!userId) return
    
    try {
      const newMessages = (await messagingApi.getNewMessages(userId)).map(normalizeMessage)
      
      if (newMessages.length > 0) {
        const existingIds = new Set(currentConversation.value.map(m => m.id))
        const onlyNew = newMessages.filter(m => !existingIds.has(m.id))
        
        if (onlyNew.length > 0) {
          currentConversation.value = [...currentConversation.value, ...onlyNew].sort(
            (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
          )
          
          await messagingApi.markConversationAsRead(userId)
          markConversationMessagesAsRead(userId)
          
          const conversation = conversations.value.find(c => c.user.id === userId)
          if (conversation) {
            conversation.unread_count = 0
          }
        }
      }
      
      await fetchUnreadCount()
    } catch (error) {
      console.warn('Failed to fetch new messages:', error)
    }
  }

  async function fetchContacts(search = '') {
    isContactsLoading.value = true
    try {
      contacts.value = await messagingApi.getContacts(search)
    } catch (error) {
      console.error('Failed to fetch contacts:', error)
    } finally {
      isContactsLoading.value = false
    }
  }

  async function sendMessage(content: string, attachment?: File) {
    if (!activeUserId.value || !currentUserId.value) return

    isSending.value = true
    errorMessage.value = ''
    const optimisticMessage = createOptimisticMessage(content, attachment)
    addMessage(optimisticMessage)

    try {
      const payload: SendMessagePayload = {
        receiver_id: activeUserId.value,
        content,
        attachment,
      }

      const queuedMessage = await messagingApi.sendMessage(payload)
      finalizeQueuedMessage(optimisticMessage.id, queuedMessage)
    } catch (error) {
      removeMessage(optimisticMessage.id)
      console.error('Failed to send message:', error)
      errorMessage.value = 'Your message could not be sent.'
    } finally {
      isSending.value = false
    }
  }

  async function setTyping(isTyping: boolean) {
    if (!activeUserId.value) return

    try {
      await messagingApi.setTyping(activeUserId.value, isTyping)
    } catch (error) {
      console.error('Failed to set typing:', error)
    }
  }

  async function markAsRead(messageId: number) {
    try {
      await messagingApi.markAsRead(messageId)
    } catch (error) {
      console.error('Failed to mark as read:', error)
    }
  }

  async function fetchUnreadCount() {
    try {
      unreadCount.value = await messagingApi.getUnreadCount()
    } catch (error) {
      console.error('Failed to fetch unread count:', error)
    }
  }

  async function setOnline() {
    try {
      const status = await messagingApi.getOnlineStatus()
      onlineUsers.value = status.online_users || []
    } catch (error) {
      console.error('Failed to set online status:', error)
    }
  }

  async function setOffline() {
    try {
      await messagingApi.setOfflineStatus()
    } catch (error) {
      console.error('Failed to set offline status:', error)
    }
  }

  function setCurrentUserId(userId: number | null) {
    currentUserId.value = userId
  }

  function isUserOnline(userId: number): boolean {
    return onlineUsers.value.includes(userId)
  }

  function isUserTyping(userId: number): boolean {
    return typingUsers.value.get(userId) || false
  }

  function addMessage(message: ChatMessage) {
    const normalizedMessage = normalizeMessage(message)
    const reconciledMessage = reconcilePendingMessage(normalizedMessage) ?? normalizedMessage

    if (currentConversation.value.some(item => item.id === reconciledMessage.id)) {
      currentConversation.value = currentConversation.value.map(item =>
        item.id === reconciledMessage.id ? reconciledMessage : item
      )
    } else if (isMessageForActiveConversation(reconciledMessage)) {
      currentConversation.value.push(reconciledMessage)
    }

    syncConversationPreview(reconciledMessage)
  }

  function syncIncomingMessage(message: ChatMessage) {
    const normalizedMessage = normalizeMessage(message)
    addMessage(normalizedMessage)

    if (normalizedMessage.sender_id === currentUserId.value) {
      setMessageStatus(normalizedMessage.id, normalizedMessage.status === 'read' ? 'read' : 'delivered')
      return
    }

    if (activeUserId.value === normalizedMessage.sender_id) {
      setConversationUnread(normalizedMessage.sender_id, 0)
      unreadCount.value = Math.max(0, unreadCount.value - 1)
      return
    }

    incrementConversationUnread(normalizedMessage.sender_id)
    unreadCount.value += 1
  }

  function ensureConversationUser(contact: MessagingContact) {
    const existing = conversations.value.find(c => c.user.id === contact.id)
    if (existing) return

    conversations.value.unshift({
      user: {
        id: contact.id,
        name: contact.name,
        avatar_url: contact.avatar_url,
        email: contact.email,
        role: contact.role,
      },
      last_message: null,
      unread_count: 0,
      is_online: contact.is_online,
    })
  }

  async function startConversation(contact: MessagingContact) {
    ensureConversationUser(contact)
    activeUserId.value = contact.id
    currentConversation.value = []
    await fetchConversation(contact.id)
  }

  function setRealtimeAvailable(value: boolean) {
    realtimeAvailable.value = value
    realtimeStatus.value = value ? 'live' : 'fallback'
  }

  function setRealtimeStatus(status: RealtimeStatus) {
    realtimeStatus.value = status
    realtimeAvailable.value = status === 'live'
  }

  function setTypingIndicator(userId: number, isTyping: boolean) {
    typingUsers.value.set(userId, isTyping)
  }

  function setMessageStatus(messageId: number, status: ChatStatus) {
    currentConversation.value = currentConversation.value.map(message =>
      message.id === messageId ? { ...message, status } : message
    )

    const target = currentConversation.value.find(message => message.id === messageId)
    if (target) {
      syncConversationPreview({ ...target, status })
    }
  }

  function markConversationMessagesAsRead(userId: number) {
    currentConversation.value = currentConversation.value.map(message => {
      if (message.sender_id === userId && message.receiver_id === currentUserId.value) {
        return { ...message, status: 'read' }
      }

      return message
    })
  }

  function clearActiveConversation() {
    activeUserId.value = null
    currentConversation.value = []
  }

  function normalizeMessage(message: ChatMessage): ChatMessage {
    const senderId = Number(message.sender_id)
    const receiverId = Number(message.receiver_id)
    const mine = typeof message.is_mine === 'boolean'
      ? message.is_mine
      : senderId === currentUserId.value

    return {
      ...message,
      id: Number(message.id),
      sender_id: senderId,
      receiver_id: receiverId,
      is_mine: mine,
      status: message.status || (mine ? 'sent' : 'delivered'),
    }
  }

  function createOptimisticMessage(content: string, attachment?: File): ChatMessage {
    return {
      id: tempMessageId--,
      sender_id: currentUserId.value!,
      receiver_id: activeUserId.value!,
      content,
      attachment_path: null,
      attachment_type: attachment?.type || null,
      attachment_name: attachment?.name || null,
      status: 'sent',
      created_at: new Date().toISOString(),
      is_mine: true,
    }
  }

  function finalizeQueuedMessage(optimisticMessageId: number, queuedMessage: Partial<ChatMessage> & {
    content?: string
    attachment_name?: string | null
    attachment_path?: string | null
    attachment_type?: string | null
    created_at?: string
  }) {
    const optimisticMessage = currentConversation.value.find(message => message.id === optimisticMessageId)
    if (!optimisticMessage) {
      return
    }

    const finalizedMessage: ChatMessage = normalizeMessage({
      ...optimisticMessage,
      content: queuedMessage.content ?? optimisticMessage.content,
      attachment_name: queuedMessage.attachment_name ?? optimisticMessage.attachment_name,
      attachment_path: queuedMessage.attachment_path ?? optimisticMessage.attachment_path,
      attachment_type: queuedMessage.attachment_type ?? optimisticMessage.attachment_type,
      created_at: queuedMessage.created_at ?? optimisticMessage.created_at,
      status: 'sent',
      is_mine: true,
    })

    currentConversation.value = currentConversation.value.map(message =>
      message.id === optimisticMessageId ? finalizedMessage : message
    )

    syncConversationPreview(finalizedMessage)
  }

  function reconcilePendingMessage(message: ChatMessage): ChatMessage | null {
    if (message.sender_id !== currentUserId.value) {
      return null
    }

    const pending = currentConversation.value.find(item =>
      item.id < 0 &&
      item.sender_id === message.sender_id &&
      item.receiver_id === message.receiver_id &&
      item.content === message.content &&
      item.attachment_name === message.attachment_name
    )

    if (!pending) {
      return null
    }

    currentConversation.value = currentConversation.value.map(item =>
      item.id === pending.id ? { ...message, is_mine: true, status: 'delivered' } : item
    )

    return { ...message, is_mine: true, status: 'delivered' }
  }

  function isMessageForActiveConversation(message: ChatMessage) {
    return message.sender_id === activeUserId.value || message.receiver_id === activeUserId.value
  }

  function syncConversationPreview(message: ChatMessage) {
    const otherUserId = message.sender_id === currentUserId.value ? message.receiver_id : message.sender_id
    const preview = {
      content: message.content || (message.attachment_name ? `Attachment: ${message.attachment_name}` : 'Attachment'),
      created_at: message.created_at,
      is_mine: message.sender_id === currentUserId.value,
      status: message.status,
    }

    const conversation = conversations.value.find(item => item.user.id === otherUserId)
    if (conversation) {
      conversation.last_message = preview
      conversation.is_online = isUserOnline(otherUserId)
      if (message.sender_id === currentUserId.value) {
        conversation.unread_count = 0
      }

      conversations.value = [
        conversation,
        ...conversations.value.filter(item => item.user.id !== otherUserId),
      ]
      return
    }

    const contact = contacts.value.find(item => item.id === otherUserId)
    if (!contact) return

    conversations.value.unshift({
      user: {
        id: contact.id,
        name: contact.name,
        avatar_url: contact.avatar_url,
        email: contact.email,
        role: contact.role,
      },
      last_message: preview,
      unread_count: message.sender_id === currentUserId.value ? 0 : 1,
      is_online: contact.is_online,
    })
  }

  function removeMessage(messageId: number) {
    currentConversation.value = currentConversation.value.filter(message => message.id !== messageId)
  }

  function incrementConversationUnread(userId: number) {
    const conversation = conversations.value.find(item => item.user.id === userId)
    if (conversation) {
      conversation.unread_count += 1
    }
  }

  function setConversationUnread(userId: number, value: number) {
    const conversation = conversations.value.find(item => item.user.id === userId)
    if (conversation) {
      conversation.unread_count = Math.max(0, value)
    }
  }

  return {
    conversations,
    contacts,
    currentConversation,
    currentUserId,
    activeUserId,
    isLoading,
    isSending,
    isContactsLoading,
    unreadCount,
    onlineUsers,
    typingUsers,
    errorMessage,
    realtimeAvailable,
    realtimeStatus,
    hasActiveConversation,
    activeUser,
    fetchConversations,
    fetchConversation,
    fetchMessagesSince,
    fetchContacts,
    sendMessage,
    setTyping,
    markAsRead,
    fetchUnreadCount,
    setOnline,
    setOffline,
    setCurrentUserId,
    isUserOnline,
    isUserTyping,
    addMessage,
    syncIncomingMessage,
    ensureConversationUser,
    startConversation,
    setRealtimeAvailable,
    setRealtimeStatus,
    setTypingIndicator,
    setMessageStatus,
    markConversationMessagesAsRead,
    clearActiveConversation,
  }
})
