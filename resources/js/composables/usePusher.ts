import { ref, onMounted, onUnmounted, readonly } from 'vue'
import Pusher from 'pusher-js'
import { useNotifications } from './useNotifications'
import { useChatRealtime } from './useChatRealtime'

interface PusherConfig {
  key: string
  cluster: string
  encrypted?: boolean
  authEndpoint?: string
  auth?: {
    headers: Record<string, string>
  }
}

interface Message {
  id: number
  content: string
  type: string
  is_edited: boolean
  created_at: string
  user: {
    id: number
    name: string
    avatar?: string
  }
}

interface MessageReadData {
  message_id: number
  user: {
    id: number
    name: string
  }
  read_at: string
}

export function usePusher() {
  const pusher = ref<Pusher | null>(null)
  const isConnected = ref(false)
  const channels = ref<Map<string, any>>(new Map())
  const { notifyNewMessage, notifyMessageRead, updateUnreadCount } = useNotifications()
  const { updateChatPreview } = useChatRealtime()

  const initializePusher = (config: PusherConfig) => {
    if (pusher.value) {
      pusher.value.disconnect()
    }

    pusher.value = new Pusher(config.key, {
      cluster: config.cluster,
      forceTLS: config.encrypted ?? (window.location.protocol === 'https:'),
      authEndpoint: config.authEndpoint ?? '/broadcasting/auth',
      auth: config.auth ?? {
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      },
    })

    pusher.value.connection.bind('connected', () => {
      isConnected.value = true
    })

    pusher.value.connection.bind('disconnected', () => {
      isConnected.value = false
    })

    pusher.value.connection.bind('error', (error: any) => {
      console.error('Pusher error:', error)
    })
  }

  const subscribeToChat = (chatId: number, callbacks: {
    onMessage?: (message: Message) => void
    onMessageRead?: (data: MessageReadData) => void
  }) => {
    if (!pusher.value) {
      console.error('Pusher not initialized')
      return
    }

    const channelName = `private-chat.${chatId}`
    const channel = pusher.value.subscribe(channelName)

    channels.value.set(channelName, channel)

    // Escutar evento de nova mensagem
    channel.bind('message.sent', (data: { message: Message }) => {
      const message = data.message
      // Não notificar/incrementar para mensagens do próprio usuário
      const currentUserId = (window as any).$page?.props?.auth?.user?.id as number | undefined
      const isFromOtherUser = currentUserId ? message.user.id !== currentUserId : true
      const currentChatId = (window as any).CURRENT_CHAT_ID as number | undefined
      const isOtherChat = !currentChatId || currentChatId !== chatId

      // Primeiro atualiza UI local (lista e janela)
      callbacks.onMessage?.(message)

      // Atualizar prévia do chat para sidebar/lista
      updateChatPreview(chatId, {
        last_message: { content: message.content },
        last_message_at: message.created_at,
      })

      // Regras de notificação/contagem (somente se for de outro usuário e chat fechado)
      if (isFromOtherUser) {
        if (isOtherChat) {
          // Incrementa contador só para chats fechados
          const existing = (window as any).__UNREAD_TMP__?.get?.(chatId) ?? 0
          const next = existing + 1
          updateUnreadCount(chatId, next)
          ;(window as any).__UNREAD_TMP__ = (window as any).__UNREAD_TMP__ || new Map()
          ;(window as any).__UNREAD_TMP__.set(chatId, next)
          // Dispara notificação (respeita aba oculta dentro do composable)
          notifyNewMessage({
            ...message,
            chat_id: chatId,
          })
        } else {
          // Chat atual: garantir que o overlay de unread fique zerado
          updateUnreadCount(chatId, 0)
        }
      }
    })

    // Escutar evento de mensagem lida
    channel.bind('message.read', (data: MessageReadData) => {
      callbacks.onMessageRead?.(data)
      notifyMessageRead({
        ...data,
        chat_id: chatId,
      })
    })

    // Escutar evento de todas as mensagens lidas no chat
    channel.bind('messages.read-all', (data: { chat_id?: number; user: { id: number; name: string }; read_at: string }) => {
      // Zerar contador local para esse chat em qualquer aba
      updateUnreadCount(chatId, 0)
    })

    return channel
  }

  const unsubscribeFromChat = (chatId: number) => {
    const channelName = `private-chat.${chatId}`
    const channel = channels.value.get(channelName)
    
    if (channel && pusher.value) {
      pusher.value.unsubscribe(channelName)
      channels.value.delete(channelName)
    }
  }

  const disconnect = () => {
    if (pusher.value) {
      pusher.value.disconnect()
      pusher.value = null
      isConnected.value = false
      channels.value.clear()
    }
  }

  onUnmounted(() => {
    disconnect()
  })

  return {
    pusher: readonly(pusher),
    isConnected: readonly(isConnected),
    initializePusher,
    subscribeToChat,
    unsubscribeFromChat,
    disconnect,
  }
}
