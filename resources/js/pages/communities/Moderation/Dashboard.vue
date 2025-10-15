<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Moderação - {{ community.title }}
          </h1>
          <p class="text-sm text-gray-600 dark:text-gray-400">
            Dashboard de moderação da comunidade r/{{ community.name }}
          </p>
        </div>
        <div class="flex gap-2">
          <Link
            :href="route('communities.show', community.name)"
            class="btn btn-outline"
          >
            Ver Comunidade
          </Link>
          <Link
            :href="route('communities.moderation.moderators', community.id)"
            class="btn btn-primary"
          >
            Gerenciar Moderadores
          </Link>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Estatísticas -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Users class="h-8 w-8 text-blue-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Total de Membros
                </p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                  {{ stats.total_members.toLocaleString() }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Shield class="h-8 w-8 text-green-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Moderadores Ativos
                </p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                  {{ stats.active_moderators }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Clock class="h-8 w-8 text-yellow-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Aprovações Pendentes
                </p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                  {{ stats.pending_approvals }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Ban class="h-8 w-8 text-red-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                  Bans Ativos
                </p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                  {{ stats.recent_bans }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Atividade Recente -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Atividade Recente de Moderação</h3>
          <Link
            :href="route('communities.moderation.logs', community.id)"
            class="btn btn-sm btn-outline"
          >
            Ver Todos os Logs
          </Link>
        </div>
        <div class="card-body">
          <div class="space-y-4">
            <div
              v-for="log in stats.recent_activity"
              :key="log.id"
              class="flex items-center space-x-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div class="flex-shrink-0">
                <img
                  :src="log.moderator.avatar ? `/storage/${log.moderator.avatar}` : null"
                  :alt="log.moderator.name"
                  class="h-10 w-10 rounded-full"
                />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ log.moderator.name }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ getActionLabel(log.action) }}
                  <span v-if="log.target_user">
                    para {{ log.target_user.name }}
                  </span>
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500">
                  {{ formatDate(log.created_at) }}
                </p>
              </div>
              <div class="flex-shrink-0">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getActionColorClass(log.action)"
                >
                  {{ getActionLabel(log.action) }}
                </span>
              </div>
            </div>
            
            <div v-if="stats.recent_activity.length === 0" class="text-center py-8">
              <p class="text-gray-500 dark:text-gray-400">
                Nenhuma atividade de moderação recente.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Moderadores -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Moderadores</h3>
          <Link
            :href="route('communities.moderation.moderators', community.id)"
            class="btn btn-sm btn-outline"
          >
            Gerenciar
          </Link>
        </div>
        <div class="card-body">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="moderator in community.moderators"
              :key="moderator.id"
              class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <img
                :src="moderator.avatar ? `/storage/${moderator.avatar}` : null"
                :alt="moderator.name"
                class="h-10 w-10 rounded-full"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ moderator.name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500">
                  {{ getRoleLabel(moderator.pivot.role) }}
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
import { Users, Shield, Clock, Ban } from 'lucide-vue-next'

interface Props {
  community: {
    id: number
    name: string
    title: string
    moderators: Array<{
      id: number
      name: string
      avatar?: string
      pivot: {
        role: string
      }
    }>
  }
  stats: {
    total_members: number
    active_moderators: number
    pending_approvals: number
    recent_bans: number
    recent_activity: Array<{
      id: number
      action: string
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
  }
}

defineProps<Props>()

const getActionLabel = (action: string): string => {
  const actions: Record<string, string> = {
    ban_user: 'Banir Usuário',
    unban_user: 'Desbanir Usuário',
    remove_post: 'Remover Post',
    remove_comment: 'Remover Comentário',
    pin_post: 'Fixar Post',
    lock_post: 'Bloquear Post',
    assign_moderator: 'Designar Moderador',
    remove_moderator: 'Remover Moderador',
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
    lock_post: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    assign_moderator: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    remove_moderator: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  }
  return colors[action] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
}

const getRoleLabel = (role: string): string => {
  const roles: Record<string, string> = {
    owner: 'Dono',
    admin: 'Administrador',
    moderator: 'Moderador',
  }
  return roles[role] || role
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleString('pt-BR')
}
</script>
