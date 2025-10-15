<script setup lang="ts">
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import SearchFilters from '@/components/SearchFilters.vue';
import SearchResults from '@/components/SearchResults.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Search, Filter, X } from 'lucide-vue-next';
import { computed, ref, watch, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

interface SearchResult {
  communities: any[];
  posts: any[];
  users: any[];
}

interface Props {
  results: SearchResult;
  query: string;
  type: string;
  total: number;
}

const props = defineProps<Props>();

const page = usePage();
const searchQuery = ref(props.query);
const searchType = ref(props.type);
const isLoading = ref(false);

const breadcrumbs = computed(() => [
  { title: 'Busca', href: '/search' },
  ...(props.query ? [{ title: `"${props.query}"`, href: `/search?q=${encodeURIComponent(props.query)}` }] : []),
]);

const hasResults = computed(() => props.total > 0);
const hasQuery = computed(() => props.query.trim().length > 0);

function performSearch() {
  if (!searchQuery.value.trim()) return;
  
  isLoading.value = true;
  router.get('/search', {
    q: searchQuery.value,
    type: searchType.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      isLoading.value = false;
    },
  });
}

function clearSearch() {
  searchQuery.value = '';
  searchType.value = 'all';
  router.get('/search', {}, {
    preserveState: true,
    preserveScroll: true,
  });
}

function handleTypeChange(newType: string) {
  searchType.value = newType;
  if (hasQuery.value) {
    performSearch();
  }
}

// Debounce search
let searchTimeout: NodeJS.Timeout;
watch(searchQuery, (newQuery) => {
  clearTimeout(searchTimeout);
  if (newQuery.trim().length > 0) {
    searchTimeout = setTimeout(() => {
      performSearch();
    }, 500);
  }
});

onMounted(() => {
  // Focus no input de busca quando a página carrega
  const searchInput = document.querySelector('input[type="text"]') as HTMLInputElement;
  if (searchInput) {
    searchInput.focus();
  }
});
</script>

<template>
  <AppHeaderLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto max-w-4xl px-4 py-6">
      <!-- Header da busca -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Buscar
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
          Encontre comunidades, posts e usuários
        </p>
      </div>

      <!-- Barra de busca principal -->
      <Card class="mb-6">
        <CardContent class="p-6">
          <div class="flex gap-4">
            <div class="flex-1 relative">
              <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                v-model="searchQuery"
                placeholder="Buscar comunidades, posts e usuários..."
                class="pl-10 pr-10"
                :disabled="isLoading"
              />
              <Button
                v-if="searchQuery"
                variant="ghost"
                size="sm"
                class="absolute right-1 top-1/2 h-6 w-6 -translate-y-1/2 p-0"
                @click="clearSearch"
              >
                <X class="h-3 w-3" />
              </Button>
            </div>
            <Button @click="performSearch" :disabled="!searchQuery.trim() || isLoading">
              <Search class="mr-2 h-4 w-4" />
              {{ isLoading ? 'Buscando...' : 'Buscar' }}
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- Filtros -->
      <SearchFilters
        v-model:type="searchType"
        :total="total"
        :query="query"
        @type-change="handleTypeChange"
        class="mb-6"
      />

      <!-- Resultados -->
      <div v-if="hasQuery">
        <SearchResults
          :results="results"
          :query="query"
          :type="type"
          :loading="isLoading"
        />
      </div>

      <!-- Estado vazio -->
      <div v-else class="text-center py-12">
        <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
          <Search class="w-8 h-8 text-gray-400" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
          Comece sua busca
        </h3>
        <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
          Digite um termo na barra de busca acima para encontrar comunidades, posts e usuários.
        </p>
      </div>

      <!-- Dicas de busca -->
      <Card v-if="!hasQuery" class="mt-6">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Filter class="h-5 w-5" />
            Dicas de busca
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <h4 class="font-medium text-gray-900 dark:text-white mb-2">Comunidades</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Busque por nome, título ou descrição das comunidades
              </p>
            </div>
            <div>
              <h4 class="font-medium text-gray-900 dark:text-white mb-2">Posts</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Encontre posts pelo título ou conteúdo
              </p>
            </div>
            <div>
              <h4 class="font-medium text-gray-900 dark:text-white mb-2">Usuários</h4>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Procure por nome ou email dos usuários
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppHeaderLayout>
</template>
