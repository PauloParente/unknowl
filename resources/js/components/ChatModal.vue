<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
    @click="closeModal"
  >
    <div
      class="relative w-full max-w-4xl h-[80vh] bg-white dark:bg-gray-900 rounded-lg shadow-2xl overflow-hidden flex flex-col"
      @click.stop
    >
      <!-- Header do Modal -->
      <div class="flex items-center justify-between p-4 border-b border-sidebar-border/70 dark:border-sidebar-border">
        <div class="flex items-center gap-3">
          <ChatBubbleLeftRightIcon class="h-6 w-6 text-primary" />
          <h2 class="text-lg font-semibold text-foreground">
            Chat
          </h2>
          
        </div>
        <div class="flex items-center gap-2">
          <button
            @click="openCreateChat"
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-primary-foreground bg-primary rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary"
          >
            <PlusIcon class="h-4 w-4 mr-1" />
            Novo Chat
          </button>
          <button
            @click="closeModal"
            class="inline-flex items-center justify-center w-8 h-8 text-muted-foreground hover:text-foreground focus:outline-none focus:ring-2 focus:ring-primary rounded-md"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>
      </div>

      <!-- Conteúdo do Modal -->
      <div class="flex flex-1 min-h-0">
        <!-- Sidebar com Lista de Chats -->
        <div class="w-1/3 border-r border-sidebar-border/70 dark:border-sidebar-border bg-muted/30">
          <ChatSidebar
            :chats="chats"
            :active-chat-id="activeChatId"
            @select-chat="selectChat"
            @refresh="refreshChats"
          />
        </div>

        <!-- Área Principal do Chat -->
        <div class="flex-1 flex flex-col min-h-0">
          <ChatView
            v-if="activeChat"
            :chat="activeChat"
            :messages="messages"
            @send-message="sendMessage"
            @mark-as-read="markAsRead"
          />
          <div v-else class="flex-1 flex items-center justify-center text-muted-foreground">
            <div class="text-center">
              <ChatBubbleLeftRightIcon class="h-12 w-12 mx-auto mb-4 text-muted-foreground/50" />
              <p class="text-lg font-medium">Selecione um chat</p>
              <p class="text-sm">Escolha uma conversa para começar</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Criação de Chat -->
    <CreateChatModal
      :is-open="isCreateModalOpen"
      @close="closeCreateModal"
      @chat-created="handleChatCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { 
  ChatBubbleLeftRightIcon, 
  PlusIcon, 
  XMarkIcon 
} from '@heroicons/vue/24/outline'
import ChatSidebar from './ChatSidebar.vue'
import ChatView from './ChatView.vue'
import CreateChatModal from './CreateChatModal.vue'
import { useChatModal } from '@/composables/useChatModal'
import { usePusher } from '@/composables/usePusher'
import { useNotifications } from '@/composables/useNotifications'

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

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

// Estado local simplificado
const chats = ref<Chat[]>([])
const activeChatId = ref<number | null>(null)
const messages = ref<Message[]>([])
const isLoading = ref(false)
const isCreateModalOpen = ref(false)

// Pusher para tempo real
const { initializePusher, subscribeToChat, unsubscribeFromChat, isConnected } = usePusher()
const { clearUnreadCount } = useNotifications()

// Computed
const activeChat = computed(() => {
  const chat = chats.value.find(chat => chat.id === activeChatId.value) || null
  console.log('activeChat computed:', { activeChatId: activeChatId.value, chat, chats: chats.value })
  return chat
})

const totalUnreadCount = computed(() => {
  return chats.value.reduce((total, chat) => total + chat.unread_count, 0)
})

// Métodos
const closeModal = () => {
  emit('close')
}

const selectChat = async (chatId: number) => {
  console.log('Selecionando chat:', chatId)
  
  // Desconectar do chat anterior se existir
  if (activeChatId.value) {
    unsubscribeFromChat(activeChatId.value)
  }
  
  activeChatId.value = chatId
  // Marcar como lidas no servidor e limpar estado local imediatamente
  try {
    fetch(`/chats/${chatId}/messages/read-all`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'same-origin',
    }).catch(() => {})
  } catch {}
  clearUnreadCount(chatId)
  
  try {
    const response = await fetch(`/chat/${chatId}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'same-origin',
    })

    if (response.ok) {
      const data = await response.json()
      messages.value = data.messages?.data || []
      console.log('Mensagens carregadas:', messages.value.length)
      
      // Subscrever ao chat para receber mensagens em tempo real
      subscribeToChat(chatId, {
        onMessage: (newMessage: Message) => {
          console.log('Nova mensagem recebida:', newMessage)
          // Adicionar nova mensagem à lista (no início pois as mensagens vêm ordenadas por desc)
          messages.value.unshift(newMessage)
        },
        onMessageRead: (data) => {
          console.log('Mensagem lida:', data)
          // Atualizar status de leitura se necessário
        }
      })
    }
  } catch (error) {
    console.error('Erro ao carregar mensagens:', error)
  }
}

const sendMessage = async (content: string) => {
  if (!activeChatId.value || !content.trim()) return
  
  console.log('Enviando mensagem:', content)
  
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
        type: 'text'
      }),
    })

    if (response.ok) {
      console.log('Mensagem enviada com sucesso')
      // Não precisamos recarregar as mensagens, o Pusher vai notificar em tempo real
    } else {
      console.error('Erro ao enviar mensagem:', response.status)
    }
  } catch (error) {
    console.error('Erro ao enviar mensagem:', error)
  }
}

const markAsRead = (messageId: number) => {
  console.log('Marcando mensagem como lida:', messageId)
  // Implementação simplificada
}

const refreshChats = async () => {
  console.log('Atualizando chats...')
  try {
    const response = await fetch('/chat', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      credentials: 'same-origin',
    })

            if (response.ok) {
              const data = await response.json()
              chats.value = data.chats?.data || []
              console.log('Chats carregados:', chats.value.length)
              console.log('Dados dos chats:', chats.value)
            }
  } catch (error) {
    console.error('Erro ao carregar chats:', error)
  }
}

const openCreateChat = () => {
  isCreateModalOpen.value = true
}

const closeCreateModal = () => {
  isCreateModalOpen.value = false
}

const handleChatCreated = (data: { chatId: number, user: any }) => {
  console.log('Chat criado, abrindo:', data)
  console.log('Modal principal está aberto:', props.isOpen)
  
  // Atualizar lista de chats
  console.log('Chamando refreshChats...')
  refreshChats()
  console.log('refreshChats concluído')
  
  // Selecionar o novo chat
  console.log('Chamando selectChat...')
  selectChat(data.chatId)
  console.log('selectChat concluído')
  
  // Fechar modal de criação
  console.log('Fechando modal de criação...')
  isCreateModalOpen.value = false
  console.log('Modal de criação fechado')
  
  console.log('Chat selecionado, modal de criação fechado')
}

// Lifecycle
onMounted(() => {
  console.log('ChatModal montado')
  
  // Inicializar Pusher se as configurações estiverem disponíveis
  const pusherKey = (window as any).PUSHER_APP_KEY
  const pusherCluster = (window as any).PUSHER_APP_CLUSTER
  
  if (pusherKey && pusherCluster) {
    console.log('Inicializando Pusher...')
    initializePusher({
      key: pusherKey,
      cluster: pusherCluster,
    })
  } else {
    console.warn('Configurações do Pusher não encontradas')
  }
  
  // Carregar chats quando o modal abrir
  refreshChats()
})

onUnmounted(() => {
  console.log('ChatModal desmontado')
  
  // Desconectar do chat ativo se existir
  if (activeChatId.value) {
    unsubscribeFromChat(activeChatId.value)
  }
})
</script>
