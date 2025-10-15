<template>
  <div class="flex flex-col h-full">
    <!-- Header do Chat -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0">
      <div class="flex items-center gap-3">
        <Avatar class="w-10 h-10 overflow-hidden rounded-full">
          <AvatarImage 
            v-if="getOtherParticipant()?.avatar" 
            :src="getOtherParticipant()?.avatar" 
            :alt="getOtherParticipant()?.name" 
          />
          <AvatarFallback class="rounded-full bg-blue-500 text-white font-medium">
            {{ getUserAvatarFallback(getOtherParticipant()?.name) }}
          </AvatarFallback>
        </Avatar>
        <div>
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ getChatDisplayName() }}
          </h3>
          <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ chat.participants.length }} participantes
          </p>
        </div>
      </div>
    </div>

    <!-- Área de Mensagens -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="messagesContainer">
      <div v-if="messages.length === 0" class="flex items-center justify-center h-full">
        <div class="text-center text-gray-500">
          <p class="text-lg font-medium">Nenhuma mensagem ainda</p>
          <p class="text-sm">Seja o primeiro a enviar uma mensagem!</p>
        </div>
      </div>
      
      <div
        v-for="message in orderedMessages"
        :key="message.id"
        class="flex"
        :class="isOwnMessage(message) ? 'justify-end' : 'justify-start'"
      >
        <div
          class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg"
          :class="isOwnMessage(message)
            ? 'bg-blue-600 text-white'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'"
        >
          <div v-if="!isOwnMessage(message)" class="text-xs text-gray-500 dark:text-gray-400 mb-1">
            {{ message.user.name }}
          </div>
          <p class="text-sm">{{ message.content }}</p>
          <div class="text-xs mt-1 opacity-75">
            {{ formatMessageTime(message.created_at) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Campo de Mensagem - SEMPRE VISÍVEL -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0">
      <div class="flex items-center gap-3">
        <input
          v-model="messageInput"
          type="text"
          placeholder="Digite sua mensagem..."
          class="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
          :disabled="isSending"
          @keydown.enter.prevent="handleSendMessage"
        />
        <button
          type="button"
          @click="handleSendMessage"
          :disabled="!messageInput.trim() || isSending"
          class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Enviar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, onMounted, watch, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { useAvatar } from '@/composables/useAvatar'

interface Chat {
  id: number
  name: string
  type: string
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
  chat: Chat
  messages: Message[]
}>()

const emit = defineEmits<{
  'send-message': [content: string]
  'mark-as-read': [messageId: number]
}>()

// Refs
const messagesContainer = ref<HTMLElement>()
const messageInput = ref('')
const isSending = ref(false)

// Composables
const { getUserAvatarFallback } = useAvatar()
// Mensagens em ordem cronológica (antiga -> nova)
const orderedMessages = computed(() => {
  return [...props.messages].reverse()
})


// Métodos
const handleSendMessage = async () => {
  if (!messageInput.value.trim() || isSending.value) return
  
  isSending.value = true
  try {
    emit('send-message', messageInput.value.trim())
    messageInput.value = ''
    await nextTick()
    scrollToBottom()
  } finally {
    isSending.value = false
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const page = usePage()
const currentUserId = computed<number | undefined>(() => {
  const id = (page.props as any)?.auth?.user?.id
  return typeof id === 'string' ? Number(id) : id
})

const isOwnMessage = (message: Message): boolean => {
  return currentUserId.value !== undefined && message.user.id === currentUserId.value
}

const getChatDisplayName = (): string => {
  if (props.chat.type === 'group') {
    return props.chat.name || 'Chat em Grupo'
  }
  
  const otherParticipant = getOtherParticipant()
  return otherParticipant?.name || 'Usuário'
}

const getOtherParticipant = () => {
  return props.chat.participants.find(p => p.id !== currentUserId.value)
}


const formatMessageTime = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('pt-BR', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

// Watchers
watch(() => props.messages, () => {
  nextTick(() => {
    scrollToBottom()
  })
}, { deep: true })

// Lifecycle
onMounted(() => {
  scrollToBottom()
})
</script>