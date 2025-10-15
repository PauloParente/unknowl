<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Skeleton } from '@/components/ui/skeleton';
import { Link } from '@inertiajs/vue3';
import { 
  Users, 
  MessageSquare, 
  Calendar, 
  ThumbsUp, 
  ThumbsDown,
  UserPlus,
  UserMinus,
  Hash,
  FileText,
  User
} from 'lucide-vue-next';
import { computed } from 'vue';
import { useAvatar } from '@/composables/useAvatar';

interface SearchResult {
  communities: any[];
  posts: any[];
  users: any[];
}

interface Props {
  results: SearchResult;
  query: string;
  type: string;
  loading: boolean;
}

const props = defineProps<Props>();

// Composables
const { getAvatarUrl, shouldShowAvatar, getCommunityAvatarFallback } = useAvatar();

const showCommunities = computed(() => props.type === 'all' || props.type === 'communities');
const showPosts = computed(() => props.type === 'all' || props.type === 'posts');
const showUsers = computed(() => props.type === 'all' || props.type === 'users');

const hasResults = computed(() => {
  return props.results.communities.length > 0 || 
         props.results.posts.length > 0 || 
         props.results.users.length > 0;
});

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  });
}

function formatScore(score: number) {
  if (score > 0) return `+${score}`;
  return score.toString();
}
</script>

<template>
  <div class="space-y-6">
    <!-- Loading state -->
    <div v-if="loading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="space-y-2">
        <Skeleton class="h-4 w-1/4" />
        <Skeleton class="h-20 w-full" />
      </div>
    </div>

    <!-- No results -->
    <div v-else-if="!hasResults" class="text-center py-12">
      <div class="mx-auto w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
        <Hash class="w-8 h-8 text-gray-400" />
      </div>
      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        Nenhum resultado encontrado
      </h3>
      <p class="text-gray-600 dark:text-gray-400 max-w-md mx-auto">
        Não encontramos resultados para "{{ query }}". Tente usar termos diferentes ou verifique a ortografia.
      </p>
    </div>

    <!-- Results -->
    <div v-else class="space-y-6">
      <!-- Communities -->
      <div v-if="showCommunities && results.communities.length > 0">
        <div class="flex items-center gap-2 mb-4">
          <Users class="h-5 w-5 text-blue-600" />
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Comunidades ({{ results.communities.length }})
          </h2>
        </div>
        
        <div class="grid gap-4">
          <Card v-for="community in results.communities" :key="`community-${community.id}`" class="hover:shadow-md transition-shadow">
            <CardContent class="p-4">
              <div class="flex items-start gap-4">
                <Avatar class="h-12 w-12">
                  <AvatarImage :src="`/storage/${community.avatar}`" :alt="community.name" />
                  <AvatarFallback class="bg-blue-500 text-white">
                    {{ getCommunityAvatarFallback(community.name) }}
                  </AvatarFallback>
                </Avatar>
                
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <Link :href="community.url" class="font-semibold text-gray-900 dark:text-white hover:text-blue-600">
                      r/{{ community.name }}
                    </Link>
                    <Badge variant="outline" class="text-xs">
                      {{ community.members_count }} membros
                    </Badge>
                  </div>
                  
                  <h3 class="font-medium text-gray-800 dark:text-gray-200 mb-1">
                    {{ community.title }}
                  </h3>
                  
                  <p v-if="community.description" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                    {{ community.description }}
                  </p>
                  
                  <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                      <FileText class="h-3 w-3" />
                      {{ community.posts_count }} posts
                    </span>
                  </div>
                </div>
                
                <Button 
                  :variant="community.is_following ? 'outline' : 'default'"
                  size="sm"
                  class="shrink-0"
                >
                  <UserPlus v-if="!community.is_following" class="h-4 w-4 mr-1" />
                  <UserMinus v-else class="h-4 w-4 mr-1" />
                  {{ community.is_following ? 'Seguindo' : 'Seguir' }}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Posts -->
      <div v-if="showPosts && results.posts.length > 0">
        <div class="flex items-center gap-2 mb-4">
          <FileText class="h-5 w-5 text-green-600" />
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Posts ({{ results.posts.length }})
          </h2>
        </div>
        
        <div class="grid gap-4">
          <Card v-for="post in results.posts" :key="`post-${post.id}`" class="hover:shadow-md transition-shadow">
            <CardContent class="p-4">
              <div class="flex gap-4">
                <div class="flex flex-col items-center gap-1">
                  <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                    <ThumbsUp class="h-4 w-4" />
                  </Button>
                  <span class="text-sm font-medium" :class="post.score > 0 ? 'text-green-600' : post.score < 0 ? 'text-red-600' : 'text-gray-500'">
                    {{ formatScore(post.score) }}
                  </span>
                  <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                    <ThumbsDown class="h-4 w-4" />
                  </Button>
                </div>
                
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-2">
                    <Link :href="`/r/${post.community.name}`" class="text-sm font-medium text-blue-600 hover:underline">
                      r/{{ post.community.name }}
                    </Link>
                    <span class="text-gray-400">•</span>
                    <span class="text-sm text-gray-500">
                      por u/{{ post.author.name }}
                    </span>
                    <span class="text-gray-400">•</span>
                    <span class="text-sm text-gray-500 flex items-center gap-1">
                      <Calendar class="h-3 w-3" />
                      {{ formatDate(post.created_at) }}
                    </span>
                  </div>
                  
                  <Link :href="post.url" class="block">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-2 hover:text-blue-600">
                      {{ post.title }}
                    </h3>
                  </Link>
                  
                  <p v-if="post.text" class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3 mb-2">
                    {{ post.text }}
                  </p>
                  
                  <div class="flex items-center gap-4 text-sm text-gray-500">
                    <span class="flex items-center gap-1">
                      <MessageSquare class="h-4 w-4" />
                      {{ post.comments_count }} comentários
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Users -->
      <div v-if="showUsers && results.users.length > 0">
        <div class="flex items-center gap-2 mb-4">
          <User class="h-5 w-5 text-purple-600" />
          <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Usuários ({{ results.users.length }})
          </h2>
        </div>
        
        <div class="grid gap-4">
          <Card v-for="user in results.users" :key="`user-${user.id}`" class="hover:shadow-md transition-shadow">
            <CardContent class="p-4">
              <div class="flex items-center gap-4">
                <Avatar class="h-12 w-12">
                  <AvatarImage :src="`/storage/${user.avatar}`" :alt="user.name" />
                  <AvatarFallback class="bg-purple-100 text-purple-600">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </AvatarFallback>
                </Avatar>
                
                <div class="flex-1 min-w-0">
                  <Link :href="user.url" class="font-semibold text-gray-900 dark:text-white hover:text-purple-600">
                    u/{{ user.name }}
                  </Link>
                  
                  <p v-if="user.bio" class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                    {{ user.bio }}
                  </p>
                  
                  <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                      <FileText class="h-3 w-3" />
                      {{ user.posts_count }} posts
                    </span>
                    <span class="flex items-center gap-1">
                      <MessageSquare class="h-3 w-3" />
                      {{ user.comments_count }} comentários
                    </span>
                  </div>
                </div>
                
                <Button variant="outline" size="sm" class="shrink-0">
                  <UserPlus class="h-4 w-4 mr-1" />
                  Seguir
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </div>
</template>
