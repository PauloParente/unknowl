<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900 dark:text-gray-100">
          <div class="flex">
            <!-- Sidebar -->
            <div class="w-1/3 pr-4 border-r border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                  <Link :href="route('chat.index')" class="inline-flex items-center text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <ArrowLeftIcon class="h-5 w-5 mr-1" /> Voltar
                  </Link>
                </div>
                <NotificationBadge />
              </div>

              <!-- Info do Chat -->
              <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center h-10 w-10 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                  {{ getChatInitials(chat) }}
                </div>
                <div>
                  <div class="font-semibold">{{ getChatDisplayName(chat) }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">{{ chat.type === 'group' ? 'Grupo' : 'Privado' }}</div>
                </div>
              </div>
            </div>

            <!-- Conteúdo do Chat -->
            <div class="flex-1 pl-4 flex flex-col">
              <!-- Área de Mensagens -->
              <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="messagesContainer">
                <div v-if="localMessages.length === 0" class="flex items-center justify-center h-full">
                  <div class="text-center">
                    <ChatBubbleLeftRightIcon class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                      Nenhuma mensagem ainda
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400">
                      Seja o primeiro a enviar uma mensagem nesta conversa
                    </p>
                  </div>
                </div>

                <div v-else>
                  <div
                    v-for="message in localMessages"
                    :key="message.id"
                    class="flex"
                    :class="{
                      'justify-end': message.user.id === currentUserId,
                      'justify-start': message.user.id !== currentUserId
                    }"
                  >
                    <!-- bubble -->
                    <div
                      class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
                      :class="{
                        'bg-blue-600 text-white': message.user.id === currentUserId,
                        'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white': message.user.id !== currentUserId
                      }"
                    >
                      <div v-if="message.user.id !== currentUserId" class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                        {{ message.user.name }}
                      </div>
                      <p class="text-sm">{{ message.content }}</p>
                      <div class="text-xs mt-1 opacity-75">
                        {{ formatMessageTime(message.created_at) }}
                        <span v-if="message.is_edited" class="ml-1">(editado)</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Input de Mensagem -->
              <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                <Form
                  :action="route('messages.store', chat.id)"
                  method="post"
                  #default="{ processing }"
                  @submit="scrollToBottom"
                >
                  <div class="flex items-center space-x-4">
                    <div class="flex-1">
                      <input
                        name="content"
                        type="text"
                        placeholder="Digite sua mensagem..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        required
                      />
                      <input type="hidden" name="type" value="text" />
                    </div>
                    <button
                      type="submit"
                      :disabled="processing"
                      class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <PaperAirplaneIcon class="h-4 w-4" />
                    </button>
                  </div>
                </Form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick, onUnmounted, watch, computed } from 'vue'
import { Link, Form, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import NotificationBadge from '@/components/NotificationBadge.vue'
import { usePusher } from '@/composables/usePusher'
import { useNotifications } from '@/composables/useNotifications'
import {
  ArrowLeftIcon,
  ChatBubbleLeftRightIcon,
  InformationCircleIcon,
  PaperAirplaneIcon
} from '@heroicons/vue/24/outline'

interface User {
  id: number
  name: string
  avatar?: string
}

interface Message {
  id: number
  content: string
  type: string
  is_edited: boolean
  created_at: string
  user: User
}

interface Chat {
  id: number
  name?: string
  type: 'private' | 'group'
  participants: User[]
}

interface Props {
  chat: Chat
  messages: {
    data: Message[]
  }
  chats: {
    data: Chat[]
  }
}

const props = defineProps<Props>()
const messagesContainer = ref<HTMLElement>()
const { initializePusher, subscribeToChat, unsubscribeFromChat } = usePusher()
const { clearUnreadCount } = useNotifications()

// Inertia page for accessing current user id safely
const page = usePage()
// Expor Ziggy route para o template com tipagem permissiva
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const route = (window as any).route as any
const currentUserId = computed<number | undefined>(() => {
  const id = (page.props as any)?.auth?.user?.id
  return typeof id === 'string' ? Number(id) : id
})

// Estado local reativo para mensagens (ordem cronológica ascendente: antigo -> novo)
const localMessages = ref<Message[]>([...props.messages.data].slice().reverse())

// Sincronizar quando o Inertia atualizar as props (ex.: troca de página/refresh parcial)
watch(
  () => props.messages.data,
  (newVal) => {
    localMessages.value = [...newVal].slice().reverse()
    nextTick(() => scrollToBottom())
  }
)

const getChatDisplayName = (chat: Chat): string => {
  if (chat.type === 'group') {
    return chat.name || 'Chat em Grupo'
  }
  const otherParticipant = chat.participants.find(p => p.id !== currentUserId.value)
  return otherParticipant?.name || 'Usuário'
}

const getChatInitials = (chat: Chat): string => {
  const displayName = getChatDisplayName(chat)
  return displayName
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatMessageTime = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = (now.getTime() - date.getTime()) / (1000 * 60)
  if (diffInMinutes < 1) {
    return 'Agora'
  } else if (diffInMinutes < 60) {
    return `${Math.floor(diffInMinutes)}min`
  } else if (diffInMinutes < 1440) {
    return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
  } else {
    return date.toLocaleDateString('pt-BR')
  }
}

const scrollToBottom = async () => {
  await nextTick()
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

onMounted(() => {
  scrollToBottom()
  const pusherKey = (window as any).PUSHER_APP_KEY
  const pusherCluster = (window as any).PUSHER_APP_CLUSTER
  if (pusherKey && pusherCluster) {
    // Expor o chat atual globalmente ANTES de inicializar/subscrever para evitar corrida
    ;(window as any).CURRENT_CHAT_ID = props.chat.id
    initializePusher({
      key: pusherKey,
      cluster: pusherCluster,
    })
    // Subscrever ao chat atual e atualizar o estado local
    subscribeToChat(props.chat.id, {
      onMessage: (message: Message) => {
        // Inserir no final para manter ordem ascendente (mais novo em baixo)
        localMessages.value = [...localMessages.value, message]
        scrollToBottom()
        // Se a mensagem veio de outro usuário, marcar como lida no servidor
        const isFromOther = currentUserId.value ? message.user.id !== currentUserId.value : true
        if (isFromOther) {
          fetch(`/chats/${props.chat.id}/messages/read-all`, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
          }).catch(() => {})
        }
      },
      onMessageRead: (data) => {
        // placeholder para futuras atualizações de leitura
        console.log('Message read:', data)
      }
    })
  }
  // Ao abrir a conversa, marcar todas como lidas no servidor e limpar estado local
  fetch(`/chats/${props.chat.id}/messages/read-all`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
  }).catch(() => {})
  clearUnreadCount(props.chat.id)
})

onUnmounted(() => {
  unsubscribeFromChat(props.chat.id)
  if ((window as any).CURRENT_CHAT_ID === props.chat.id) {
    delete (window as any).CURRENT_CHAT_ID
  }
})
</script>
