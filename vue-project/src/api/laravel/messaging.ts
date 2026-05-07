import { laravelApi, unwrapResponse } from '@/api/http'

export interface ChatUser {
  id: number
  name: string
  avatar_url: string | null
  email?: string
  role?: string
}

export interface ConversationUser {
  user: ChatUser
  last_message: {
    content: string
    created_at: string
    is_mine: boolean
    status?: 'sent' | 'delivered' | 'read'
  } | null
  unread_count: number
  is_online: boolean
}

export interface ChatMessage {
  id: number
  sender_id: number
  receiver_id: number
  content: string
  attachment_path: string | null
  attachment_type: string | null
  attachment_name: string | null
  status: 'sent' | 'delivered' | 'read'
  created_at: string
  is_mine: boolean
}

export interface MessagingContact {
  id: number
  name: string
  email: string
  avatar_url: string | null
  role: string
  is_online: boolean
}

export interface SendMessagePayload {
  receiver_id: number
  content: string
  attachment?: File
}

export const messagingApi = {
  async getConversations(): Promise<ConversationUser[]> {
    const response = await laravelApi.get('/messaging/conversations')
    const data = unwrapResponse<{ conversations?: ConversationUser[] }>(response)
    return data?.conversations || []
  },

  async getConversation(userId: number): Promise<ChatMessage[]> {
    const response = await laravelApi.get(`/messaging/conversation/${userId}`)
    const data = unwrapResponse<{ messages?: ChatMessage[] }>(response)
    return data?.messages || []
  },

  async getNewMessages(userId: number, afterId?: number): Promise<ChatMessage[]> {
    const response = await laravelApi.get(`/messaging/new-messages/${userId}`, {
      params: afterId ? { after_id: afterId } : undefined,
    })
    const data = unwrapResponse<{ messages?: ChatMessage[] }>(response)
    return data?.messages || []
  },

  async getContacts(search = ''): Promise<MessagingContact[]> {
    const response = await laravelApi.get('/messaging/contacts', {
      params: search ? { search } : undefined
    })
    const data = unwrapResponse<{ contacts?: MessagingContact[] }>(response)
    return data?.contacts || []
  },

  async sendMessage(payload: SendMessagePayload): Promise<any> {
    const formData = new FormData()
    formData.append('receiver_id', String(payload.receiver_id))
    formData.append('content', payload.content)
    if (payload.attachment) {
      formData.append('attachment', payload.attachment)
    }
    const response = await laravelApi.post('/messaging/send', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return unwrapResponse(response)
  },

  async setTyping(receiverId: number, isTyping: boolean): Promise<void> {
    await laravelApi.post('/messaging/typing', {
      receiver_id: receiverId,
      is_typing: isTyping
    })
  },

  async markAsRead(messageId: number): Promise<void> {
    await laravelApi.post(`/messaging/mark-read/${messageId}`)
  },

  async markConversationAsRead(userId: number): Promise<void> {
    await laravelApi.post(`/messaging/mark-conversation-read/${userId}`)
  },

  async markConversationAsDelivered(userId: number): Promise<void> {
    await laravelApi.post(`/messaging/mark-conversation-delivered/${userId}`)
  },

  async getUnreadCount(): Promise<number> {
    const response = await laravelApi.get('/messaging/unread-count')
    const data = unwrapResponse<{ unread_count?: number }>(response)
    return data?.unread_count || 0
  },

  async getOnlineStatus(): Promise<{ online_users: number[] }> {
    const response = await laravelApi.get('/messaging/online-status')
    return unwrapResponse(response)
  },

  async setOfflineStatus(): Promise<void> {
    await laravelApi.post('/messaging/offline-status')
  },

  async downloadAttachment(messageId: number): Promise<Blob> {
    const response = await laravelApi.get(`/messaging/attachment/${messageId}`, {
      responseType: 'blob'
    })
    return response.data as Blob
  }
}
