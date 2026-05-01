import { useMessagingStore } from '@/stores/messaging'

declare global {
  interface Window {
    Echo: any
  }
}

export function useRealTimeMessages() {
  const messagingStore = useMessagingStore()
  const isRealtimeConfigured = typeof window !== 'undefined' && !!window.Echo

  function getEcho() {
    if (typeof window === 'undefined') return null
    return window.Echo ?? null
  }

  function subscribeToMessages(userId: number) {
    const echo = getEcho()
    if (echo) {
      echo.private(`user.${userId}`)
        .listen('.new-message', (event: any) => {
          if (event.message) {
            messagingStore.syncIncomingMessage(event.message)

            if (
              messagingStore.activeUserId === event.message.sender_id &&
              messagingStore.currentUserId === event.message.receiver_id
            ) {
              messagingStore.setMessageStatus(event.message.id, 'read')
              messagingStore.markAsRead(event.message.id)
            } else {
              messagingStore.fetchUnreadCount()
            }
          }
        })
        .listen('.message-read', (event: any) => {
          messagingStore.setMessageStatus(event.message_id, 'read')
        })
        .listen('.typing', (event: any) => {
          messagingStore.setTypingIndicator(event.sender_id, event.is_typing)
        })
        .listen('.user-status', (event: any) => {
          if (event.is_online) {
            if (!messagingStore.onlineUsers.includes(event.user_id)) {
              messagingStore.onlineUsers.push(event.user_id)
            }
          } else {
            const index = messagingStore.onlineUsers.indexOf(event.user_id)
            if (index > -1) {
              messagingStore.onlineUsers.splice(index, 1)
            }
          }
        })
    }
  }

  function unsubscribeFromMessages(userId: number) {
    if (typeof window !== 'undefined' && window.Echo) {
      window.Echo.leave(`user.${userId}`)
    }
  }

  function subscribeToMessagingChannel() {
    const echo = getEcho()
    if (echo) {
      echo.join('messaging')
        .here((users: any[]) => {
          messagingStore.onlineUsers = users.map((u: any) => u.id)
        })
        .joining((user: any) => {
          if (!messagingStore.onlineUsers.includes(user.id)) {
            messagingStore.onlineUsers.push(user.id)
          }
        })
        .leaving((user: any) => {
          const index = messagingStore.onlineUsers.indexOf(user.id)
          if (index > -1) {
            messagingStore.onlineUsers.splice(index, 1)
          }
        })
    }
  }

  function unsubscribeFromMessagingChannel() {
    if (typeof window !== 'undefined' && window.Echo) {
      window.Echo.leave('messaging')
    }
  }

  function watchRealtimeStatus(options: { onConnected?: () => void; onDisconnected?: () => void } = {}) {
    const echo = getEcho()
    const connection = echo?.connector?.pusher?.connection

    if (!connection) {
      messagingStore.setRealtimeStatus('fallback')
      options.onDisconnected?.()
      return () => {}
    }

    const syncState = (state: string) => {
      if (state === 'connected') {
        messagingStore.setRealtimeStatus('live')
        options.onConnected?.()
        return
      }

      if (state === 'connecting' || state === 'initialized') {
        messagingStore.setRealtimeStatus('connecting')
        return
      }

      messagingStore.setRealtimeStatus('fallback')
      options.onDisconnected?.()
    }

    const handleStateChange = (states: { current: string }) => syncState(states.current)
    const handleError = () => {
      messagingStore.setRealtimeStatus('fallback')
      options.onDisconnected?.()
    }

    connection.bind('state_change', handleStateChange)
    connection.bind('error', handleError)
    syncState(connection.state)

    return () => {
      connection.unbind('state_change', handleStateChange)
      connection.unbind('error', handleError)
    }
  }

  return {
    isRealtimeConfigured,
    subscribeToMessages,
    unsubscribeFromMessages,
    subscribeToMessagingChannel,
    unsubscribeFromMessagingChannel,
    watchRealtimeStatus,
  }
}
