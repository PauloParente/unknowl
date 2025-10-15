import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface SearchSuggestion {
  type: 'community' | 'user';
  id: number;
  name: string;
  title?: string;
  avatar?: string;
  url: string;
}

export function useSearchAutocomplete() {
  const query = ref('');
  const suggestions = ref<SearchSuggestion[]>([]);
  const isLoading = ref(false);
  const showSuggestions = ref(false);
  const selectedIndex = ref(-1);

  let debounceTimeout: NodeJS.Timeout;

  const hasSuggestions = computed(() => suggestions.value.length > 0);
  const hasQuery = computed(() => query.value.trim().length > 0);

  // Debounced search function
  const searchSuggestions = async (searchQuery: string) => {
    if (searchQuery.length < 2) {
      suggestions.value = [];
      showSuggestions.value = false;
      return;
    }

    isLoading.value = true;
    
    try {
      const response = await fetch(`/search/autocomplete?q=${encodeURIComponent(searchQuery)}`);
      const data = await response.json();
      suggestions.value = data.suggestions || [];
      showSuggestions.value = true;
      selectedIndex.value = -1;
    } catch (error) {
      console.error('Erro ao buscar sugestões:', error);
      suggestions.value = [];
    } finally {
      isLoading.value = false;
    }
  };

  // Watch for query changes with debounce
  watch(query, (newQuery) => {
    clearTimeout(debounceTimeout);
    if (newQuery.trim().length >= 2) {
      debounceTimeout = setTimeout(() => {
        searchSuggestions(newQuery);
      }, 300);
    } else {
      suggestions.value = [];
      showSuggestions.value = false;
    }
  });

  const performSearch = () => {
    if (!query.value.trim()) return;
    
    showSuggestions.value = false;
    router.visit(`/search?q=${encodeURIComponent(query.value)}`);
  };

  const selectSuggestion = (suggestion: SearchSuggestion) => {
    query.value = suggestion.name;
    showSuggestions.value = false;
    router.visit(suggestion.url);
  };

  const handleKeydown = (event: KeyboardEvent) => {
    if (!showSuggestions.value || !hasSuggestions.value) {
      if (event.key === 'Enter') {
        performSearch();
      }
      return;
    }

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        selectedIndex.value = Math.min(selectedIndex.value + 1, suggestions.value.length - 1);
        break;
      case 'ArrowUp':
        event.preventDefault();
        selectedIndex.value = Math.max(selectedIndex.value - 1, -1);
        break;
      case 'Enter':
        event.preventDefault();
        if (selectedIndex.value >= 0) {
          selectSuggestion(suggestions.value[selectedIndex.value]);
        } else {
          performSearch();
        }
        break;
      case 'Escape':
        showSuggestions.value = false;
        selectedIndex.value = -1;
        break;
    }
  };

  const hideSuggestions = () => {
    // Delay to allow click events to fire
    setTimeout(() => {
      showSuggestions.value = false;
      selectedIndex.value = -1;
    }, 150);
  };

  const clearQuery = () => {
    query.value = '';
    suggestions.value = [];
    showSuggestions.value = false;
    selectedIndex.value = -1;
  };

  return {
    query,
    suggestions,
    isLoading,
    showSuggestions,
    selectedIndex,
    hasSuggestions,
    hasQuery,
    performSearch,
    selectSuggestion,
    handleKeydown,
    hideSuggestions,
    clearQuery,
  };
}
