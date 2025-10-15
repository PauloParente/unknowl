<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
    @click="closeModal"
  >
    <div
      class="relative w-full max-w-md bg-background rounded-lg shadow-2xl overflow-hidden"
      @click.stop
    >
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-sidebar-border/70">
        <div class="flex items-center gap-3">
          <UserPlusIcon class="h-6 w-6 text-primary" />
          <h2 class="text-lg font-semibold text-foreground">
            Novo Chat
          </h2>
        </div>
        <button
          @click="closeModal"
          class="inline-flex items-center justify-center w-8 h-8 text-muted-foreground hover:text-foreground focus:outline-none focus:ring-2 focus:ring-primary rounded-md"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <!-- Conteúdo -->
      <div class="p-4">
        <!-- Campo de Busca -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-foreground mb-2">
            Buscar usuário
          </label>
          <div class="relative">
            <!-- Ícone de busca ou loading -->
            <div class="absolute left-3 top-1/2 transform -translate-y-1/2">
              <div v-if="isSearching" class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
              <MagnifyingGlassIcon v-else class="h-4 w-4 text-muted-foreground" />
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Digite o nome do usuário..."
              class="w-full pl-10 pr-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-background text-foreground"
            />
          </div>
        </div>

        <!-- Resultados da Busca -->
        <div class="max-h-64 overflow-y-auto">
          <div v-if="searchResults.length === 0 && searchQuery && !isSearching" class="text-center py-4 text-muted-foreground">
            <UserIcon class="h-8 w-8 mx-auto mb-2 text-muted-foreground/50" />
            <p class="text-sm">Nenhum usuário encontrado</p>
            <p class="text-xs">Tente outro nome ou username</p>
          </div>

          <div v-else-if="searchResults.length > 0" class="space-y-2">
            <div
              v-for="user in searchResults"
              :key="user.id"
              @click="selectUser(user)"
              class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors hover:bg-muted/50"
            >
              <!-- Avatar -->
              <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full bg-muted flex items-center justify-center overflow-hidden">
                  <img
                    v-if="user.avatar"
                    :src="user.avatar"
                    :alt="user.name"
                    class="w-full h-full object-cover"
                  />
                  <span
                    v-else
                    class="text-muted-foreground font-medium text-sm"
                  >
                    {{ user.name?.charAt(0) || '?' }}
                  </span>
                </div>
              </div>

              <!-- Informações do Usuário -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-foreground truncate">
                  {{ user.name }}
                </p>
                <p class="text-xs text-muted-foreground truncate">
                  {{ user.username }}
                </p>
              </div>

              <!-- Botão de Seleção -->
              <div class="flex-shrink-0">
                <button
                  class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-primary-foreground bg-primary rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary"
                >
                  <ChatBubbleLeftRightIcon class="h-4 w-4 mr-1" />
                  Chat
                </button>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-4 text-muted-foreground">
            <ChatBubbleLeftRightIcon class="h-8 w-8 mx-auto mb-2 text-muted-foreground/50" />
            <p class="text-sm">Digite um nome para buscar usuários</p>
            <p class="text-xs">Encontre pessoas para conversar</p>
          </div>
        </div>

        <!-- Usuários Recentes (se houver) -->
        <div v-if="recentUsers.length > 0 && !searchQuery" class="mt-4">
          <h3 class="text-sm font-medium text-foreground mb-2">Conversas Recentes</h3>
          <div class="space-y-2">
            <div
              v-for="user in recentUsers"
              :key="user.id"
              @click="selectUser(user)"
              class="flex items-center gap-3 p-2 rounded-lg cursor-pointer transition-colors hover:bg-muted/50"
            >
              <div class="w-8 h-8 rounded-full bg-muted flex items-center justify-center overflow-hidden">
                <img
                  v-if="user.avatar"
                  :src="user.avatar"
                  :alt="user.name"
                  class="w-full h-full object-cover"
                />
                <span
                  v-else
                  class="text-muted-foreground font-medium text-xs"
                >
                  {{ user.name?.charAt(0) || '?' }}
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-foreground truncate">{{ user.name }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { 
  UserPlusIcon,
  XMarkIcon,
  MagnifyingGlassIcon,
  UserIcon,
  ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline'

interface User {
  id: number
  name: string
  username: string
  avatar?: string
}

const props = defineProps<{
  isOpen: boolean
}>()

const emit = defineEmits<{
  close: []
  'chat-created': [chatId: number]
}>()

// Estado
const searchQuery = ref('')
const searchResults = ref<User[]>([])
const recentUsers = ref<User[]>([])
const isSearching = ref(false)
let searchTimeout: NodeJS.Timeout | null = null

// Métodos
const closeModal = () => {
  searchQuery.value = ''
  searchResults.value = []
  emit('close')
}

const searchUsers = async () => {
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }

  isSearching.value = true
  try {
    console.log('Buscando usuários com query:', searchQuery.value)
    
    const response = await fetch(`/chat/search/users?query=${encodeURIComponent(searchQuery.value)}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })

    console.log('Response status:', response.status)
    console.log('Response ok:', response.ok)

    if (!response.ok) {
      const errorText = await response.text()
      console.error('Erro na resposta:', errorText)
      throw new Error(`Falha ao buscar usuários: ${response.status}`)
    }

    const data = await response.json()
    console.log('Dados recebidos:', data)
    searchResults.value = data.users || []
  } catch (error) {
    console.error('Erro ao buscar usuários:', error)
    searchResults.value = []
  } finally {
    isSearching.value = false
  }
}

const debouncedSearch = () => {
  // Limpar timeout anterior
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }

  // Se não há query, limpar resultados imediatamente
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }

  // Definir novo timeout para busca
  searchTimeout = setTimeout(() => {
    searchUsers()
  }, 300) // 300ms de delay
}

const selectUser = async (user: User) => {
  try {
    console.log('Criando chat com usuário:', user.name)
    
    // Criar novo chat com o usuário selecionado
    const response = await fetch('/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        type: 'private',
        participant_ids: [user.id]
      }),
    })

    console.log('Response status:', response.status)

    if (!response.ok) {
      const errorText = await response.text()
      console.error('Erro na resposta:', errorText)
      throw new Error(`Falha ao criar chat: ${response.status}`)
    }

    const data = await response.json()
    console.log('Chat criado:', data)
    
    // Emitir evento de chat criado com dados completos
    emit('chat-created', {
      chatId: data.chat.id,
      user: user
    })
    
    // Não fechar o modal aqui - o handleChatCreated vai fazer isso
    // closeModal() // REMOVIDO - estava causando fechamento do modal principal
  } catch (error) {
    console.error('Erro ao criar chat:', error)
    // Em uma implementação real, mostrar erro para o usuário
    alert('Erro ao criar chat. Tente novamente.')
  }
}

// Watchers
watch(searchQuery, () => {
  debouncedSearch()
})

// Cleanup
onUnmounted(() => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
})
</script>
