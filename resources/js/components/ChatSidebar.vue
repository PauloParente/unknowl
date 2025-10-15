<template>
  <div class="flex flex-col h-full">
    <!-- Header da Sidebar -->
    <div class="p-4 border-b border-sidebar-border/70 dark:border-sidebar-border">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-medium text-foreground">
          Conversas
        </h3>
        <button
          @click="$emit('refresh')"
          class="inline-flex items-center justify-center w-8 h-8 text-muted-foreground hover:text-foreground focus:outline-none focus:ring-2 focus:ring-primary rounded-md"
          title="Atualizar"
        >
          <ArrowPathIcon class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Lista de Chats -->
    <div class="flex-1 overflow-y-auto">
      <div v-if="chats.length === 0" class="p-4 text-center text-muted-foreground">
        <ChatBubbleLeftRightIcon class="h-8 w-8 mx-auto mb-2 text-muted-foreground/50" />
        <p class="text-sm">Nenhuma conversa ainda</p>
        <p class="text-xs">Inicie uma nova conversa</p>
      </div>
      
      <div v-else class="space-y-1 p-2">
        <div
          v-for="chat in chats"
          :key="chat.id"
          @click="selectChat(chat.id)"
          :class="[
            'flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors',
            activeChatId === chat.id
              ? 'bg-primary/10 border border-primary/20'
              : 'hover:bg-muted/50'
          ]"
        >
          <!-- Avatar do Chat -->
          <div class="flex-shrink-0">
            <Avatar class="w-10 h-10 overflow-hidden rounded-full">
              <AvatarImage 
                v-if="chat.type === 'group' ? false : getOtherParticipant(chat)?.avatar"
                :src="getOtherParticipant(chat)?.avatar" 
                :alt="getOtherParticipant(chat)?.name" 
              />
              <AvatarFallback 
                class="rounded-full font-medium text-sm"
                :class="chat.type === 'group' 
                  ? 'bg-gradient-to-br from-primary to-primary/80 text-primary-foreground' 
                  : 'bg-muted text-muted-foreground'"
              >
                {{ chat.type === 'group' 
                  ? getGroupInitials(chat.name) 
                  : getUserAvatarFallback(getOtherParticipant(chat)?.name) }}
              </AvatarFallback>
            </Avatar>
          </div>

          <!-- Informações do Chat -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-foreground truncate">
                {{ getChatDisplayName(chat) }}
              </p>
              <div class="flex items-center gap-2">
                <span
                  v-if="(getChatPreview(chat.id)?.last_message_at || chat.last_message_at)"
                  class="text-xs text-muted-foreground"
                >
                  {{ formatTime(getChatPreview(chat.id)?.last_message_at || chat.last_message_at) }}
                </span>
                <NotificationBadge
                  :show-dot="displayUnread(chat) > 0"
                  :pulse="displayUnread(chat) > 0"
                  variant="danger"
                />
              </div>
            </div>
            <p
              v-if="getChatPreview(chat.id)?.last_message || chat.last_message"
              class="text-xs text-muted-foreground truncate mt-1"
            >
              {{ (getChatPreview(chat.id)?.last_message?.content) || chat.last_message?.content }}
            </p>
            <p
              v-else
              class="text-xs text-muted-foreground/70 truncate mt-1"
            >
              Nenhuma mensagem ainda
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'
import { 
  ChatBubbleLeftRightIcon, 
  ArrowPathIcon 
} from '@heroicons/vue/24/outline'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { useAvatar } from '@/composables/useAvatar'
import NotificationBadge from './NotificationBadge.vue'
import { useNotifications } from '@/composables/useNotifications'
import { usePusher } from '@/composables/usePusher'
import { useChatRealtime } from '@/composables/useChatRealtime'

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

const props = defineProps<{
  chats: Chat[]
  activeChatId: number | null
}>()

const emit = defineEmits<{
  'select-chat': [chatId: number]
  refresh: []
}>()

// Composables
const { getUserAvatarFallback } = useAvatar()
const { getUnreadCount, clearUnreadCount, requestPermission } = useNotifications()
const { initializePusher, subscribeToChat, unsubscribeFromChat } = usePusher()
const { getChatPreview } = useChatRealtime()

// Evitar inscrições duplicadas
const subscribed = new Set<number>()

// Métodos
const selectChat = (chatId: number) => {
  // Limpar contador local imediatamente ao abrir
  clearUnreadCount(chatId)
  emit('select-chat', chatId)
}

const getChatDisplayName = (chat: Chat): string => {
  if (chat.type === 'group') {
    return chat.name || 'Chat em Grupo'
  }
  
  const otherParticipant = getOtherParticipant(chat)
  return otherParticipant?.name || 'Usuário'
}

const getOtherParticipant = (chat: Chat) => {
  // Obter o ID do usuário atual do Inertia
  const currentUserId = (window as any).$page?.props?.auth?.user?.id
  // Encontrar o participante que não é o usuário atual
  return chat.participants.find(p => p.id !== currentUserId)
}

const getGroupInitials = (name: string): string => {
  if (!name) return 'G'
  return getUserAvatarFallback(name) || 'G'
}

const formatTime = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = (now.getTime() - date.getTime()) / (1000 * 60 * 60)
  
  if (diffInHours < 1) {
    const diffInMinutes = Math.floor(diffInHours * 60)
    return diffInMinutes < 1 ? 'Agora' : `${diffInMinutes}m`
  } else if (diffInHours < 24) {
    return `${Math.floor(diffInHours)}h`
  } else if (diffInHours < 168) { // 7 dias
    return `${Math.floor(diffInHours / 24)}d`
  } else {
    return date.toLocaleDateString('pt-BR', { 
      day: '2-digit', 
      month: '2-digit' 
    })
  }
}

// Exibir contador reativo de não lidas
const displayUnread = (chat: Chat): number => {
  // Se este chat está ativo/aberto, não exibir badge
  if (props.activeChatId === chat.id) {
    return 0
  }
  // Priorizar o valor do mapa mesmo quando for 0 (evita fallback para unread_count antigo)
  const overlay = getUnreadCount(chat.id)
  if (overlay !== undefined && overlay !== null) {
    return overlay
  }
  return chat.unread_count
}

onMounted(() => {
  // Solicitar permissão para notificações (não bloqueante)
  requestPermission().catch(() => {})

  const pusherKey = (window as any).PUSHER_APP_KEY
  const pusherCluster = (window as any).PUSHER_APP_CLUSTER
  if (pusherKey && pusherCluster) {
    initializePusher({ key: pusherKey, cluster: pusherCluster })
    // Inscrever em todos os chats exibidos para receber novas mensagens
    for (const chat of props.chats) {
      if (!subscribed.has(chat.id)) {
        subscribeToChat(chat.id, {
          onMessage: () => {
            // Não incrementa aqui; lógica centralizada em usePusher (evita self e chat ativo)
          },
        })
        subscribed.add(chat.id)
      }
    }
  }
})

onUnmounted(() => {
  for (const chatId of subscribed) {
    unsubscribeFromChat(chatId)
  }
  subscribed.clear()
})
</script>
