<template>
  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Gerenciar Moderadores
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
          <button
            @click="showAddModeratorModal = true"
            class="btn btn-primary"
            :disabled="!canAddModerator"
          >
            Adicionar Moderador
          </button>
        </div>
      </div>
    </template>

    <div class="space-y-6">
      <!-- Lista de Moderadores -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Moderadores Ativos</h3>
        </div>
        <div class="card-body">
          <div class="overflow-x-auto">
            <table class="table">
              <thead>
                <tr>
                  <th>Usuário</th>
                  <th>Role</th>
                  <th>Designado por</th>
                  <th>Data</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="moderator in moderators" :key="moderator.id">
                  <td>
                    <div class="flex items-center space-x-3">
                      <img
                        :src="moderator.avatar ? `/storage/${moderator.avatar}` : null"
                        :alt="moderator.name"
                        class="h-10 w-10 rounded-full"
                      />
                      <div>
                        <div class="font-medium text-gray-900 dark:text-white">
                          {{ moderator.name }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getRoleColorClass(moderator.role)"
                    >
                      {{ moderator.role_label }}
                    </span>
                  </td>
                  <td>
                    <div v-if="moderator.assigned_by" class="flex items-center space-x-2">
                      <img
                        :src="moderator.assigned_by.avatar ? `/storage/${moderator.assigned_by.avatar}` : null"
                        :alt="moderator.assigned_by.name"
                        class="h-6 w-6 rounded-full"
                      />
                      <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ moderator.assigned_by.name }}
                      </span>
                    </div>
                    <span v-else class="text-sm text-gray-500 dark:text-gray-500">
                      Sistema
                    </span>
                  </td>
                  <td>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                      {{ formatDate(moderator.assigned_at) }}
                    </span>
                  </td>
                  <td>
                    <span
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="moderator.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                    >
                      {{ moderator.is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                  </td>
                  <td>
                    <div class="flex space-x-2">
                      <button
                        v-if="canManageModerator(moderator)"
                        @click="openChangeRoleModal(moderator)"
                        class="btn btn-sm btn-outline"
                      >
                        Alterar Role
                      </button>
                      <button
                        v-if="canRemoveModerator(moderator)"
                        @click="removeModerator(moderator)"
                        class="btn btn-sm btn-danger"
                      >
                        Remover
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Adicionar Moderador -->
    <Modal v-model="showAddModeratorModal" title="Adicionar Moderador">
      <Form
        @submit="addModerator"
        #default="{ processing, submit }"
      >
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Buscar Usuário
            </label>
            <input
              v-model="searchQuery"
              @input="searchUsers"
              type="text"
              placeholder="Digite o nome do usuário..."
              class="input"
            />
            
            <div v-if="searchResults.length > 0" class="mt-2 space-y-2">
              <button
                v-for="user in searchResults"
                :key="user.id"
                @click="selectUser(user)"
                type="button"
                class="w-full flex items-center space-x-3 p-3 text-left border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <img
                  :src="user.avatar ? `/storage/${user.avatar}` : null"
                  :alt="user.name"
                  class="h-8 w-8 rounded-full"
                />
                <span class="text-sm font-medium">{{ user.name }}</span>
              </button>
            </div>
          </div>

          <div v-if="selectedUser">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Usuário Selecionado
            </label>
            <div class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
              <img
                :src="selectedUser.avatar ? `/storage/${selectedUser.avatar}` : null"
                :alt="selectedUser.name"
                class="h-8 w-8 rounded-full"
              />
              <span class="text-sm font-medium">{{ selectedUser.name }}</span>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Role
            </label>
            <select v-model="form.role" class="select">
              <option value="moderator">Moderador</option>
              <option value="admin" :disabled="!canAddAdmin">Administrador</option>
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              <span v-if="form.role === 'admin' && !canAddAdmin">
                Limite de administradores atingido (máximo 3)
              </span>
              <span v-else-if="form.role === 'moderator' && !canAddModerator">
                Limite de moderadores atingido (máximo 10)
              </span>
            </p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Observações (opcional)
            </label>
            <textarea
              v-model="form.notes"
              placeholder="Motivo da designação..."
              class="textarea"
              rows="3"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <button
            type="button"
            @click="showAddModeratorModal = false"
            class="btn btn-outline"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="processing || !selectedUser || !canAssignRole"
            class="btn btn-primary"
          >
            {{ processing ? 'Adicionando...' : 'Adicionar Moderador' }}
          </button>
        </div>
      </Form>
    </Modal>

    <!-- Modal Alterar Role -->
    <Modal v-model="showChangeRoleModal" title="Alterar Role do Moderador">
      <Form
        v-if="selectedModerator"
        @submit="changeRole"
        #default="{ processing, submit }"
      >
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Moderador
            </label>
            <div class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
              <img
                :src="selectedModerator.avatar ? `/storage/${selectedModerator.avatar}` : null"
                :alt="selectedModerator.name"
                class="h-8 w-8 rounded-full"
              />
              <span class="text-sm font-medium">{{ selectedModerator.name }}</span>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Novo Role
            </label>
            <select v-model="changeRoleForm.new_role" class="select">
              <option value="moderator">Moderador</option>
              <option value="admin" :disabled="!canAddAdmin">Administrador</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Motivo (opcional)
            </label>
            <textarea
              v-model="changeRoleForm.reason"
              placeholder="Motivo da alteração..."
              class="textarea"
              rows="3"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <button
            type="button"
            @click="showChangeRoleModal = false"
            class="btn btn-outline"
          >
            Cancelar
          </button>
          <button
            type="submit"
            :disabled="processing"
            class="btn btn-primary"
          >
            {{ processing ? 'Alterando...' : 'Alterar Role' }}
          </button>
        </div>
      </Form>
    </Modal>
  </AppLayout>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'

interface Props {
  community: {
    id: number
    title: string
  }
  moderators: Array<{
    id: number
    name: string
    avatar?: string
    role: string
    role_label: string
    assigned_by?: {
      id: number
      name: string
      avatar?: string
    }
    assigned_at: string
    is_active: boolean
  }>
  canAddAdmin: boolean
  canAddModerator: boolean
}

const props = defineProps<Props>()

// Estado dos modais
const showAddModeratorModal = ref(false)
const showChangeRoleModal = ref(false)

// Estado da busca de usuários
const searchQuery = ref('')
const searchResults = ref<Array<{ id: number; name: string; avatar?: string }>>([])
const selectedUser = ref<{ id: number; name: string; avatar?: string } | null>(null)

// Estado do formulário de adicionar moderador
const form = ref({
  role: 'moderator',
  notes: '',
})

// Estado do formulário de alterar role
const selectedModerator = ref<Props['moderators'][0] | null>(null)
const changeRoleForm = ref({
  new_role: 'moderator',
  reason: '',
})

// Computed properties
const canAssignRole = computed(() => {
  if (form.value.role === 'admin') {
    return props.canAddAdmin
  }
  return props.canAddModerator
})

// Métodos
const searchUsers = async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = []
    return
  }

  try {
    const response = await fetch(
      route('communities.moderation.search-users', props.community.id, {
        q: searchQuery.value,
      })
    )
    const data = await response.json()
    searchResults.value = data.users || []
  } catch (error) {
    console.error('Erro ao buscar usuários:', error)
  }
}

const selectUser = (user: { id: number; name: string; avatar?: string }) => {
  selectedUser.value = user
  searchResults.value = []
  searchQuery.value = user.name
}

const addModerator = async () => {
  if (!selectedUser.value) return

  try {
    await router.post(
      route('communities.moderation.moderators.assign', props.community.id),
      {
        user_id: selectedUser.value.id,
        role: form.value.role,
        notes: form.value.notes,
      }
    )
    
    showAddModeratorModal.value = false
    resetAddModeratorForm()
  } catch (error) {
    console.error('Erro ao adicionar moderador:', error)
  }
}

const openChangeRoleModal = (moderator: Props['moderators'][0]) => {
  selectedModerator.value = moderator
  changeRoleForm.value.new_role = moderator.role
  changeRoleForm.value.reason = ''
  showChangeRoleModal.value = true
}

const changeRole = async () => {
  if (!selectedModerator.value) return

  try {
    await router.patch(
      route('communities.moderation.moderators.change-role', props.community.id),
      {
        user_id: selectedModerator.value.id,
        new_role: changeRoleForm.value.new_role,
        reason: changeRoleForm.value.reason,
      }
    )
    
    showChangeRoleModal.value = false
    selectedModerator.value = null
  } catch (error) {
    console.error('Erro ao alterar role:', error)
  }
}

const removeModerator = async (moderator: Props['moderators'][0]) => {
  if (!confirm(`Tem certeza que deseja remover ${moderator.name} como moderador?`)) {
    return
  }

  try {
    await router.delete(
      route('communities.moderation.moderators.remove', props.community.id),
      {
        data: {
          user_id: moderator.id,
        },
      }
    )
  } catch (error) {
    console.error('Erro ao remover moderador:', error)
  }
}

const resetAddModeratorForm = () => {
  selectedUser.value = null
  searchQuery.value = ''
  searchResults.value = []
  form.value.role = 'moderator'
  form.value.notes = ''
}

const getRoleColorClass = (role: string): string => {
  const colors: Record<string, string> = {
    owner: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    admin: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
    moderator: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  }
  return colors[role] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleString('pt-BR')
}

const canManageModerator = (moderator: Props['moderators'][0]): boolean => {
  // Implementar lógica baseada no role do usuário atual
  return true
}

const canRemoveModerator = (moderator: Props['moderators'][0]): boolean => {
  // Implementar lógica baseada no role do usuário atual
  return true
}
</script>
