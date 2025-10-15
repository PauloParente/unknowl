<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Logs de Moderação
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ community.title }}
          </p>
        </div>
        <div class="flex gap-2">
          <Link
            :href="route('communities.moderation.dashboard', community.id)"
            class="btn btn-outline"
          >
            Dashboard
          </Link>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Filtros -->
      <div class="card">
        <div class="card-body">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Ação
              </label>
              <select v-model="filters.action" class="select">
                <option value="">Todas as ações</option>
                <option value="ban_user">Banir Usuário</option>
                <option value="unban_user">Desbanir Usuário</option>
                <option value="remove_post">Remover Post</option>
                <option value="remove_comment">Remover Comentário</option>
                <option value="pin_post">Fixar Post</option>
                <option value="lock_post">Bloquear Post</option>
                <option value="assign_moderator">Designar Moderador</option>
                <option value="remove_moderator">Remover Moderador</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Moderador
              </label>
              <select v-model="filters.moderator" class="select">
                <option value="">Todos os moderadores</option>
                <option
                  v-for="moderator in moderators"
                  :key="moderator.id"
                  :value="moderator.id"
                >
                  {{ moderator.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Período
              </label>
              <select v-model="filters.period" class="select">
                <option value="">Todo o período</option>
                <option value="today">Hoje</option>
                <option value="week">Esta semana</option>
                <option value="month">Este mês</option>
                <option value="year">Este ano</option>
              </select>
            </div>

            <div class="flex items-end">
              <button @click="applyFilters" class="btn btn-primary w-full">
                Aplicar Filtros
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de Logs -->
      <div class="card">
        <div class="card-body">
          <div class="space-y-4">
            <div
              v-for="log in logs.data"
              :key="log.id"
              class="border border-gray-200 dark:border-gray-700 rounded-lg p-4"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-start space-x-4">
                  <img
                    :src="log.moderator.avatar ? `/storage/${log.moderator.avatar}` : null"
                    :alt="log.moderator.name"
                    class="h-10 w-10 rounded-full"
                  />
                  <div class="flex-1">
                    <div class="flex items-center space-x-2">
                      <span class="font-medium text-gray-900 dark:text-white">
                        {{ log.moderator.name }}
                      </span>
                      <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        :class="getActionColorClass(log.action)"
                      >
                        {{ getActionLabel(log.action) }}
                      </span>
                    </div>
                    
                    <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                      <span v-if="log.target_user">
                        Ação aplicada em <strong>{{ log.target_user.name }}</strong>
                      </span>
                      <span v-else>
                        {{ getActionDescription(log.action) }}
                      </span>
                    </div>

                    <div v-if="log.reason" class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                      <strong>Motivo:</strong> {{ log.reason }}
                    </div>

                    <div v-if="log.metadata" class="mt-2 text-xs text-gray-500 dark:text-gray-500">
                      <details>
                        <summary class="cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                          Detalhes adicionais
                        </summary>
                        <pre class="mt-2 text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded overflow-auto">{{ JSON.stringify(log.metadata, null, 2) }}</pre>
                      </details>
                    </div>
                  </div>
                </div>

                <div class="text-right">
                  <div class="text-sm text-gray-500 dark:text-gray-500">
                    {{ formatDate(log.created_at) }}
                  </div>
                  <div class="text-xs text-gray-400 dark:text-gray-600">
                    {{ formatRelativeTime(log.created_at) }}
                  </div>
                </div>
              </div>
            </div>

            <div v-if="logs.data.length === 0" class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">
                Nenhum log encontrado com os filtros aplicados.
              </p>
            </div>
          </div>

          <!-- Paginação -->
          <div v-if="logs.links" class="mt-6">
            <nav class="flex items-center justify-between">
              <div class="flex-1 flex justify-between sm:hidden">
                <Link
                  v-if="logs.prev_page_url"
                  :href="logs.prev_page_url"
                  class="btn btn-outline"
                >
                  Anterior
                </Link>
                <Link
                  v-if="logs.next_page_url"
                  :href="logs.next_page_url"
                  class="btn btn-outline"
                >
                  Próxima
                </Link>
              </div>
              
              <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm text-gray-700 dark:text-gray-300">
                    Mostrando
                    <span class="font-medium">{{ logs.from || 0 }}</span>
                    até
                    <span class="font-medium">{{ logs.to || 0 }}</span>
                    de
                    <span class="font-medium">{{ logs.total }}</span>
                    resultados
                  </p>
                </div>
                
                <div>
                  <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <template v-for="link in logs.links" :key="link.label">
                      <Link
                        v-if="link.url"
                        :href="link.url"
                        v-html="link.label"
                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium"
                        :class="link.active ? 'bg-blue-50 border-blue-500 text-blue-600 dark:bg-blue-900 dark:border-blue-400 dark:text-blue-200' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700'"
                      />
                      <span
                        v-else
                        v-html="link.label"
                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-gray-500 text-sm font-medium dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400"
                      />
                    </template>
                  </nav>
                </div>
              </div>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, reactive } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

interface Props {
  community: {
    id: number
    title: string
  }
  logs: {
    data: Array<{
      id: number
      action: string
      reason?: string
      metadata?: any
      created_at: string
      moderator: {
        id: number
        name: string
        avatar?: string
      }
      target_user?: {
        id: number
        name: string
      }
    }>
    links?: any[]
    prev_page_url?: string
    next_page_url?: string
    from?: number
    to?: number
    total: number
  }
  moderators: Array<{
    id: number
    name: string
  }>
}

const props = defineProps<Props>()

// Filtros
const filters = reactive({
  action: '',
  moderator: '',
  period: '',
})

// Métodos
const applyFilters = () => {
  router.get(route('communities.moderation.logs', props.community.id), filters, {
    preserveState: true,
    replace: true,
  })
}

const getActionLabel = (action: string): string => {
  const actions: Record<string, string> = {
    ban_user: 'Banir Usuário',
    unban_user: 'Desbanir Usuário',
    remove_post: 'Remover Post',
    remove_comment: 'Remover Comentário',
    pin_post: 'Fixar Post',
    unpin_post: 'Desfixar Post',
    lock_post: 'Bloquear Post',
    unlock_post: 'Desbloquear Post',
    assign_moderator: 'Designar Moderador',
    remove_moderator: 'Remover Moderador',
    promote_moderator: 'Promover Moderador',
    demote_moderator: 'Rebaixar Moderador',
    warn_user: 'Avisar Usuário',
    mute_user: 'Silenciar Usuário',
    unmute_user: 'Remover Silêncio',
  }
  return actions[action] || action
}

const getActionColorClass = (action: string): string => {
  const colors: Record<string, string> = {
    ban_user: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    unban_user: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    remove_post: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    remove_comment: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    pin_post: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    unpin_post: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    lock_post: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    unlock_post: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    assign_moderator: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    remove_moderator: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    promote_moderator: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    demote_moderator: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    warn_user: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    mute_user: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    unmute_user: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  }
  return colors[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
}

const getActionDescription = (action: string): string => {
  const descriptions: Record<string, string> = {
    ban_user: 'Usuário banido da comunidade',
    unban_user: 'Usuário desbanido da comunidade',
    remove_post: 'Post removido',
    remove_comment: 'Comentário removido',
    pin_post: 'Post fixado no topo',
    unpin_post: 'Post desfixado',
    lock_post: 'Post bloqueado para comentários',
    unlock_post: 'Post desbloqueado',
    assign_moderator: 'Moderador designado',
    remove_moderator: 'Moderador removido',
    promote_moderator: 'Moderador promovido',
    demote_moderator: 'Moderador rebaixado',
    warn_user: 'Usuário avisado',
    mute_user: 'Usuário silenciado',
    unmute_user: 'Silêncio removido',
  }
  return descriptions[action] || 'Ação de moderação executada'
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleString('pt-BR')
}

const formatRelativeTime = (date: string): string => {
  const now = new Date()
  const targetDate = new Date(date)
  const diffInSeconds = Math.floor((now.getTime() - targetDate.getTime()) / 1000)

  if (diffInSeconds < 60) {
    return 'há poucos segundos'
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60)
    return `há ${minutes} minuto${minutes > 1 ? 's' : ''}`
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600)
    return `há ${hours} hora${hours > 1 ? 's' : ''}`
  } else if (diffInSeconds < 2592000) {
    const days = Math.floor(diffInSeconds / 86400)
    return `há ${days} dia${days > 1 ? 's' : ''}`
  } else {
    const months = Math.floor(diffInSeconds / 2592000)
    return `há ${months} mês${months > 1 ? 'es' : ''}`
  }
}
</script>
