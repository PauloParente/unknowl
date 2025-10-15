<template>
  <AppLayout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-8">
        <Link
          :href="route('chats.index')"
          class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4"
        >
          <ArrowLeftIcon class="h-4 w-4 mr-1" />
          Voltar para conversas
        </Link>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
          Nova Conversa
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          Inicie uma nova conversa privada ou em grupo
        </p>
      </div>

      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
          <Form
            :action="route('chats.store')"
            method="post"
            #default="{
              errors,
              hasErrors,
              processing,
              submit,
            }"
          >
            <!-- Tipo de Chat -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                Tipo de conversa
              </label>
              <div class="grid grid-cols-2 gap-4">
                <label class="relative">
                  <input
                    v-model="chatType"
                    type="radio"
                    value="private"
                    class="sr-only"
                  />
                  <div
                    class="p-4 border-2 rounded-lg cursor-pointer transition-colors"
                    :class="{
                      'border-blue-500 bg-blue-50 dark:bg-blue-900/20': chatType === 'private',
                      'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500': chatType !== 'private'
                    }"
                  >
                    <div class="flex items-center">
                      <UserIcon class="h-5 w-5 mr-2" :class="{
                        'text-blue-600 dark:text-blue-400': chatType === 'private',
                        'text-gray-400': chatType !== 'private'
                      }" />
                      <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                          Privada
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                          Conversa entre duas pessoas
                        </p>
                      </div>
                    </div>
                  </div>
                </label>

                <label class="relative">
                  <input
                    v-model="chatType"
                    type="radio"
                    value="group"
                    class="sr-only"
                  />
                  <div
                    class="p-4 border-2 rounded-lg cursor-pointer transition-colors"
                    :class="{
                      'border-blue-500 bg-blue-50 dark:bg-blue-900/20': chatType === 'group',
                      'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500': chatType !== 'group'
                    }"
                  >
                    <div class="flex items-center">
                      <UsersIcon class="h-5 w-5 mr-2" :class="{
                        'text-blue-600 dark:text-blue-400': chatType === 'group',
                        'text-gray-400': chatType !== 'group'
                      }" />
                      <div>
                        <p class="font-medium text-gray-900 dark:text-white">
                          Grupo
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                          Conversa com múltiplas pessoas
                        </p>
                      </div>
                    </div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Nome do Grupo (apenas para grupos) -->
            <div v-if="chatType === 'group'" class="mb-6">
              <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nome do grupo
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Digite o nome do grupo"
              />
              <div v-if="errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.name }}
              </div>
            </div>

            <!-- Descrição do Grupo (apenas para grupos) -->
            <div v-if="chatType === 'group'" class="mb-6">
              <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Descrição (opcional)
              </label>
              <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                placeholder="Descreva o propósito do grupo"
              />
              <div v-if="errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.description }}
              </div>
            </div>

            <!-- Seleção de Participantes -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ chatType === 'private' ? 'Pessoa para conversar' : 'Participantes' }}
              </label>
              
              <!-- Busca de usuários -->
              <div class="mb-4">
                <input
                  v-model="searchQuery"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                  :placeholder="chatType === 'private' ? 'Buscar pessoa...' : 'Buscar pessoas...'"
                />
              </div>

              <!-- Lista de usuários -->
              <div class="max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-md">
                <div v-if="filteredUsers.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400">
                  Nenhum usuário encontrado
                </div>
                <div v-else class="divide-y divide-gray-200 dark:divide-gray-600">
                  <label
                    v-for="user in filteredUsers"
                    :key="user.id"
                    class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                  >
                    <input
                      v-model="form.participant_ids"
                      :value="user.id"
                      type="checkbox"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      :disabled="chatType === 'private' && form.participant_ids.length >= 1"
                    />
                    <div class="ml-3 flex items-center">
                      <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                          {{ getUserInitials(user.name) }}
                        </span>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                          {{ user.name }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                          @{{ user.username }}
                        </p>
                      </div>
                    </div>
                  </label>
                </div>
              </div>
              
              <div v-if="errors.participant_ids" class="mt-1 text-sm text-red-600 dark:text-red-400">
                {{ errors.participant_ids }}
              </div>
            </div>

            <!-- Botões -->
            <div class="flex items-center justify-end space-x-4">
              <Link
                :href="route('chats.index')"
                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                :disabled="processing || !canSubmit"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ processing ? 'Criando...' : 'Criar Conversa' }}
              </button>
            </div>
          </Form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, Form } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { ArrowLeftIcon, UserIcon, UsersIcon } from '@heroicons/vue/24/outline'

interface User {
  id: number
  name: string
  username: string
  avatar?: string
}

interface Props {
  users: User[]
}

const props = defineProps<Props>()

const chatType = ref<'private' | 'group'>('private')
const searchQuery = ref('')

const form = ref({
  name: '',
  description: '',
  participant_ids: [] as number[]
})

// Filtrar usuários baseado na busca
const filteredUsers = computed(() => {
  if (!searchQuery.value) return props.users
  
  const query = searchQuery.value.toLowerCase()
  return props.users.filter(user => 
    user.name.toLowerCase().includes(query) ||
    user.username.toLowerCase().includes(query)
  )
})

// Verificar se pode submeter
const canSubmit = computed(() => {
  if (chatType.value === 'private') {
    return form.value.participant_ids.length === 1
  } else {
    return form.value.participant_ids.length > 0 && form.value.name.trim() !== ''
  }
})

// Limpar seleção quando mudar o tipo
watch(chatType, () => {
  form.value.participant_ids = []
  form.value.name = ''
  form.value.description = ''
})

const getUserInitials = (name: string): string => {
  return name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
</script>
