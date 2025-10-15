import { ref, computed, readonly } from 'vue'
import { router } from '@inertiajs/vue3'

interface Chat {
  id: number
  name: string
  type: string
  last_message_at: string
  unread_count: number
  last_message?: {
    content: string
  }
  participants: Array<{
    id: number
    name: string
    avatar?: string
  }>
}

interface Message {
  id: number
  content: string
  type: string
  created_at: string
  user: {
    id: number
    name: string
    avatar?: string
  }
}

export function useChatModal() {
  // Estado
  const chats = ref<Chat[]>([])
  const activeChatId = ref<number | null>(null)
  const messages = ref<Message[]>([])
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  // Computed
  const activeChat = computed(() => {
    return chats.value.find(chat => chat.id === activeChatId.value) || null
  })

  const totalUnread = computed(() => {
    return chats.value.reduce((total, chat) => total + chat.unread_count, 0)
  })

  // Métodos
  const fetchChats = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      // Usar router do Inertia para fazer a requisição
      router.get('/chat', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: (page) => {
          // O ChatController retorna Inertia::render, então os dados vêm em page.props
          const pageProps = page.props as any
          chats.value = pageProps.chats?.data || []
        },
        onError: (errors) => {
          error.value = 'Falha ao carregar chats'
          console.error('Erro ao carregar chats:', errors)
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erro desconhecido'
      console.error('Erro ao carregar chats:', err)
      isLoading.value = false
    }
  }

  const selectChat = async (chatId: number) => {
    if (activeChatId.value === chatId) return

    activeChatId.value = chatId
    await fetchMessages(chatId)
  }

  const fetchMessages = async (chatId: number) => {
    try {
      isLoading.value = true
      error.value = null

      router.get(`/chat/${chatId}`, {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: (page) => {
          const pageProps = page.props as any
          messages.value = pageProps.messages?.data || []
        },
        onError: (errors) => {
          error.value = 'Falha ao carregar mensagens'
          console.error('Erro ao carregar mensagens:', errors)
        },
        onFinish: () => {
          isLoading.value = false
        }
      })
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erro desconhecido'
      console.error('Erro ao carregar mensagens:', err)
      isLoading.value = false
    }
  }

  const sendMessage = async (content: string) => {
    if (!activeChatId.value || !content.trim()) return

    try {
      const response = await fetch(`/chat/${activeChatId.value}/messages`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          content: content.trim(),
          type: 'text',
        }),
      })

      if (!response.ok) {
        throw new Error('Falha ao enviar mensagem')
      }

      // Recarregar mensagens após envio
      await fetchMessages(activeChatId.value)
      
      // Atualizar lista de chats para mostrar última mensagem
      await fetchChats()
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Erro desconhecido'
      console.error('Erro ao enviar mensagem:', err)
    }
  }

  const markAsRead = async (messageId: number) => {
    if (!activeChatId.value) return

    try {
      await fetch(`/chat/${activeChatId.value}/messages/${messageId}/read`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      // Atualizar contagem de não lidas
      await fetchChats()
    } catch (err) {
      console.error('Erro ao marcar mensagem como lida:', err)
    }
  }

  const refreshChats = async () => {
    await fetchChats()
  }

  const openCreateChat = () => {
    router.visit('/chat/create')
  }

  const addMessage = (message: Message) => {
    if (message.chat_id === activeChatId.value) {
      messages.value.push(message)
    }
  }

  const updateUnreadCount = (chatId: number, count: number) => {
    const chat = chats.value.find(c => c.id === chatId)
    if (chat) {
      chat.unread_count = count
    }
  }

  return {
    // Estado (readonly para evitar mutações externas)
    chats: readonly(chats),
    activeChat,
    activeChatId: readonly(activeChatId),
    messages: readonly(messages),
    isLoading: readonly(isLoading),
    error: readonly(error),
    totalUnread,

    // Métodos
    selectChat,
    sendMessage,
    markAsRead,
    refreshChats,
    openCreateChat,
    addMessage,
    updateUnreadCount,
  }
}
