<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Mensagens
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          Gerencie suas conversas e mensagens
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Lista de Chats -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                  Conversas
                </h2>
                <Link
                  :href="route('chats.create')"
                  class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                >
                  <PlusIcon class="h-4 w-4 mr-1" />
                  Nova
                </Link>
              </div>
            </div>

            <div class="max-h-96 overflow-y-auto">
              <div v-if="chats.data.length === 0" class="p-6 text-center">
                <ChatBubbleLeftRightIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p class="text-gray-500 dark:text-gray-400">
                  Nenhuma conversa encontrada
                </p>
                <Link
                  :href="route('chats.create')"
                  class="mt-2 inline-flex items-center text-blue-600 hover:text-blue-700 font-medium"
                >
                  Iniciar uma conversa
                </Link>
              </div>

              <div v-else class="divide-y divide-gray-200 dark:divide-gray-700">
                <Link
                  v-for="chat in chats.data"
                  :key="chat.id"
                  :href="route('chats.show', chat.id)"
                  class="block p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                  :class="{
                    'bg-blue-50 dark:bg-blue-900/20 border-r-2 border-blue-500': $page.url.includes(`/chats/${chat.id}`)
                  }"
                >
                  <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                          {{ getChatInitials(chat) }}
                        </span>
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                          {{ getChatDisplayName(chat) }}
                        </p>
                        <div class="flex items-center space-x-2">
                          <NotificationBadge
                            :show-dot="displayUnread(chat) > 0"
                            :pulse="displayUnread(chat) > 0"
                            variant="danger"
                          >
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                              {{ formatTime(getChatPreview(chat.id)?.last_message_at || chat.last_message_at) }}
                            </span>
                          </NotificationBadge>
                        </div>
                      </div>
                      <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                        {{ (getChatPreview(chat.id)?.last_message?.content) || chat.last_message?.content || 'Nenhuma mensagem' }}
                      </p>
                    </div>
                  </div>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Área Principal -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-96">
            <div class="flex items-center justify-center h-full">
              <div class="text-center">
                <ChatBubbleLeftRightIcon class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                  Selecione uma conversa
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                  Escolha uma conversa da lista ao lado para começar a conversar
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import NotificationBadge from '@/components/NotificationBadge.vue'
import { PlusIcon, ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline'
import { useNotifications } from '@/composables/useNotifications'
import { onMounted, onUnmounted, watch } from 'vue'
import { usePusher } from '@/composables/usePusher'
import { useChatRealtime } from '@/composables/useChatRealtime'

interface Chat {
  id: number
  name?: string
  type: 'private' | 'group'
  last_message_at?: string
  last_message?: {
    content: string
  }
  unread_count: number
  participants: Array<{
    id: number
    name: string
    avatar?: string
  }>
}

interface Props {
  chats: {
    data: Chat[]
  }
}

const props = defineProps<Props>()
const { getUnreadCount } = useNotifications()
const { initializePusher, subscribeToChat, unsubscribeFromChat } = usePusher()
const { getChatPreview } = useChatRealtime()

const getChatDisplayName = (chat: Chat): string => {
  if (chat.type === 'group') {
    return chat.name || 'Chat em Grupo'
  }
  
  // Para chat privado, mostrar o nome do outro participante
  const otherParticipant = chat.participants.find(p => p.id !== $page.props.auth.user.id)
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

const formatTime = (dateString?: string): string => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)
  
  if (diffInHours < 1) {
    return 'Agora'
  } else if (diffInHours < 24) {
    return `${Math.floor(diffInHours)}h`
  } else if (diffInHours < 168) { // 7 dias
    return `${Math.floor(diffInHours / 24)}d`
  } else {
    return date.toLocaleDateString('pt-BR')
  }
}

// Retorna o contador a exibir priorizando o estado reativo local
const displayUnread = (chat: Chat): number => {
  // Se a URL atual já está no chat, não exibir badge
  if ($page.url.includes(`/chats/${chat.id}`)) {
    return 0
  }
  // Priorizar valor do mapa mesmo quando 0
  const overlay = getUnreadCount(chat.id)
  if (overlay !== undefined && overlay !== null) {
    return overlay
  }
  return chat.unread_count
}

// Subscrever em todos os chats listados para atualizar badges em tempo real
const subscribed = new Set<number>()

const ensureSubscriptions = () => {
  const pusherKey = (window as any).PUSHER_APP_KEY
  const pusherCluster = (window as any).PUSHER_APP_CLUSTER
  if (!pusherKey || !pusherCluster) return
  initializePusher({ key: pusherKey, cluster: pusherCluster })
  for (const chat of props.chats.data) {
    if (!subscribed.has(chat.id)) {
      subscribeToChat(chat.id, {
        onMessage: () => {
          // Não incrementa aqui; lógica centralizada no usePusher (evita self e chat ativo)
        },
      })
      subscribed.add(chat.id)
    }
  }
}

onMounted(() => {
  ensureSubscriptions()
})

watch(() => props.chats.data.map(c => c.id).join(','), () => {
  ensureSubscriptions()
})

onUnmounted(() => {
  for (const chatId of subscribed) {
    unsubscribeFromChat(chatId)
  }
  subscribed.clear()
})
</script>
