import { ref, computed, onMounted, onUnmounted, readonly } from 'vue'
import { router } from '@inertiajs/vue3'

interface NotificationData {
  id: string
  title: string
  body: string
  icon?: string
  badge?: string
  tag?: string
  data?: any
  actions?: Array<{
    action: string
    title: string
    icon?: string
  }>
}

interface UnreadCount {
  chat_id: number
  count: number
}

// Singleton state (compartilhado entre todos os consumidores do composable)
const unreadCounts = ref<Map<number, number>>(new Map())
const totalUnreadCount = computed(() => {
  return Array.from(unreadCounts.value.values()).reduce((sum, count) => sum + count, 0)
})
const isSupported = ref(false)
const permission = ref<NotificationPermission>('default')

export function useNotifications() {

  // Verificar suporte a notificações (não exigir service worker para notificações simples)
  const checkSupport = () => {
    isSupported.value = 'Notification' in window
    if (isSupported.value) {
      permission.value = Notification.permission
    }
  }

  // Solicitar permissão para notificações
  const requestPermission = async (): Promise<boolean> => {
    if (!isSupported.value) {
      console.warn('Notificações não são suportadas neste navegador')
      return false
    }

    if (permission.value === 'granted') {
      return true
    }

    try {
      const result = await Notification.requestPermission()
      permission.value = result
      return result === 'granted'
    } catch (error) {
      console.error('Erro ao solicitar permissão para notificações:', error)
      return false
    }
  }

  // Mostrar notificação
  const showNotification = (data: NotificationData): void => {
    if (!isSupported.value || permission.value !== 'granted') {
      return
    }

    try {
      const notification = new Notification(data.title, {
        body: data.body,
        icon: data.icon || '/favicon.ico',
        badge: data.badge || '/favicon.ico',
        tag: data.tag,
        data: data.data,
        actions: data.actions,
        requireInteraction: false,
        silent: false,
      })

      // Ação ao clicar na notificação
      notification.onclick = () => {
        window.focus()
        if (data.data?.url) {
          router.visit(data.data.url)
        }
        notification.close()
      }

      // Auto-close após 5 segundos
      setTimeout(() => {
        notification.close()
      }, 5000)

    } catch (error) {
      console.error('Erro ao mostrar notificação:', error)
    }
  }

  // Atualizar contador de mensagens não lidas
  const updateUnreadCount = (chatId: number, count: number) => {
    unreadCounts.value.set(chatId, count)
    // Reatribuir o Map para disparar reatividade
    unreadCounts.value = new Map(unreadCounts.value)
    
    // Atualizar badge do navegador se suportado
    if ('setAppBadge' in navigator && totalUnreadCount.value > 0) {
      // @ts-expect-error experimental
      navigator.setAppBadge(totalUnreadCount.value)
    } else if ('setAppBadge' in navigator && totalUnreadCount.value === 0) {
      // @ts-expect-error experimental
      navigator.clearAppBadge()
    }
  }

  // Obter contador de mensagens não lidas para um chat específico
  const getUnreadCount = (chatId: number): number => {
    return unreadCounts.value.get(chatId) || 0
  }

  // Limpar contador de mensagens não lidas (mantendo a chave com 0 para evitar fallback)
  const clearUnreadCount = (chatId: number) => {
    unreadCounts.value.set(chatId, 0)
    // Reatribuir o Map para disparar reatividade
    unreadCounts.value = new Map(unreadCounts.value)
    
    // Atualizar badge do navegador
    if ('setAppBadge' in navigator && totalUnreadCount.value > 0) {
      // @ts-expect-error experimental
      navigator.setAppBadge(totalUnreadCount.value)
    } else if ('setAppBadge' in navigator && totalUnreadCount.value === 0) {
      // @ts-expect-error experimental
      navigator.clearAppBadge()
    }
  }

  // Notificação de nova mensagem
  const notifyNewMessage = (message: {
    id: number
    content: string
    user: { id?: number; name: string }
    chat_id: number
  }) => {
    // Não notificar se o chat estiver aberto
    const currentChatId = (window as any).CURRENT_CHAT_ID as number | undefined
    if (currentChatId && currentChatId === message.chat_id) {
      return
    }

    // Não notificar se a mensagem for do próprio usuário
    const currentUserId = (window as any).$page?.props?.auth?.user?.id as number | undefined
    if (currentUserId && message.user?.id && message.user.id === currentUserId) {
      return
    }

    // Sempre notificar quando permissões estiverem concedidas e chat estiver fechado
    showNotification({
      id: `message-${message.id}`,
      title: `Nova mensagem de ${message.user.name}`,
      body: message.content.length > 100 
        ? message.content.substring(0, 100) + '...' 
        : message.content,
      tag: `chat-${message.chat_id}`,
      data: {
        url: `/chats/${message.chat_id}`,
        chat_id: message.chat_id,
        message_id: message.id,
      },
      actions: [
        { action: 'view', title: 'Ver mensagem' },
        { action: 'mark-read', title: 'Marcar como lida' },
      ],
    })
  }

  // Notificação de mensagem lida
  const notifyMessageRead = (data: {
    message_id: number
    user: { name: string }
    chat_id: number
  }) => {
    console.log(`Mensagem ${data.message_id} foi lida por ${data.user.name}`)
  }

  // Inicializar (apenas garante leitura do suporte quando o primeiro consumidor montar)
  onMounted(() => {
    if (!isSupported.value) {
      checkSupport()
    }
  })

  return {
    // Estado
    unreadCounts: readonly(unreadCounts),
    totalUnreadCount,
    isSupported,
    permission: readonly(permission),

    // Métodos
    checkSupport,
    requestPermission,
    showNotification,
    updateUnreadCount,
    getUnreadCount,
    clearUnreadCount,
    notifyNewMessage,
    notifyMessageRead,
  }
}
