<template>
  <div class="min-h-screen" :style="{ backgroundColor: profileColor + '10' }">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2" :style="{ borderColor: profileColor }"></div>
    </div>

    <!-- Profile Page -->
    <div v-else-if="user">
      <!-- Cover Section -->
      <div class="relative h-64 md:h-80 lg:h-96 bg-gray-900">
        <img 
          v-if="user.cover_image_url" 
          :src="user.cover_image_url" 
          alt="Cover"
          class="w-full h-full object-cover"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
        
        <!-- Profile Info Overlay -->
        <div class="absolute bottom-0 left-0 w-full p-6 text-white">
          <div class="max-w-7xl mx-auto flex items-end space-x-6">
            <img
              :src="user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&size=128`"
              alt="Avatar"
              class="w-32 h-32 rounded-full border-4 border-white shadow-lg"
            />
            <div class="flex-1">
              <h1 class="text-3xl md:text-4xl font-bold">
                {{ user.page_title || user.name }}
              </h1>
              <p v-if="user.display_title" class="text-lg opacity-90 mt-1">
                {{ user.display_title }}
              </p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-3">
              <button
                v-if="isOwnProfile"
                @click="editProfile"
                class="px-4 py-2 bg-white text-gray-900 rounded-lg font-medium hover:bg-gray-100"
              >
                Edit Profile
              </button>
              <button
                v-else-if="isAuthenticated"
                @click="toggleFollow"
                class="px-4 py-2 rounded-lg font-medium"
                :class="isFollowing ? 'bg-gray-700 text-white hover:bg-gray-800' : 'bg-white text-gray-900 hover:bg-gray-100'"
              >
                {{ isFollowing ? 'Following' : 'Follow' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Profile Content -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Sidebar -->
          <div class="lg:col-span-1 space-y-6">
            <!-- Bio -->
            <div v-if="user.bio" class="bg-white rounded-lg shadow p-6">
              <h2 class="font-semibold text-gray-900 mb-3">About</h2>
              <p class="text-gray-700 whitespace-pre-wrap">{{ user.bio }}</p>
            </div>

            <!-- Stats -->
            <div class="bg-white rounded-lg shadow p-6">
              <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                  <div class="text-2xl font-bold" :style="{ color: profileColor }">
                    {{ user.lists_count || 0 }}
                  </div>
                  <div class="text-sm text-gray-600">Lists</div>
                </div>
                <div v-if="user.show_followers">
                  <div class="text-2xl font-bold" :style="{ color: profileColor }">
                    {{ user.followers_count || 0 }}
                  </div>
                  <div class="text-sm text-gray-600">Followers</div>
                </div>
                <div v-if="user.show_following">
                  <div class="text-2xl font-bold" :style="{ color: profileColor }">
                    {{ user.following_count || 0 }}
                  </div>
                  <div class="text-sm text-gray-600">Following</div>
                </div>
              </div>
            </div>

            <!-- Info -->
            <div class="bg-white rounded-lg shadow p-6 space-y-3">
              <div v-if="user.show_location && user.location" class="flex items-center text-gray-700">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                {{ user.location }}
              </div>
              
              <div v-if="user.show_website && user.website" class="flex items-center text-gray-700">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                <a :href="user.website" target="_blank" class="text-blue-600 hover:underline">
                  {{ user.website.replace(/^https?:\/\//, '') }}
                </a>
              </div>

              <div v-if="user.show_join_date" class="flex items-center text-gray-700">
                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Joined {{ formatDate(user.created_at) }}
              </div>
            </div>
          </div>

          <!-- Main Content -->
          <div class="lg:col-span-2 space-y-8">
            <!-- Tacked Post -->
            <div v-if="tackedPost">
              <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05L14.5 16.95a1 1 0 01-1.414-.05l-1.086-1.086L11 17a1 1 0 01-2 0v-1.186l-1.086 1.086a1 1 0 01-1.414.05L3.333 13.98a1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L10 4.323V3a1 1 0 011-1z" />
                </svg>
                Tacked Post
              </h2>
              <PostItem 
                :post="tackedPost"
                @updated="handlePostUpdated"
                @deleted="tackedPost = null"
              />
            </div>
            
            <!-- Pinned Lists -->
            <div v-if="pinnedLists.length > 0">
              <h2 class="text-xl font-semibold mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"></path>
                </svg>
                Pinned Lists
              </h2>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <ListCard
                  v-for="list in pinnedLists"
                  :key="list.id"
                  :list="list"
                  :profile-color="profileColor"
                />
              </div>
            </div>

            <!-- Activity Feed -->
            <div>
              <h2 class="text-xl font-semibold mb-4">Activity</h2>
              <div v-if="loading" class="space-y-4">
                <div v-for="i in 3" :key="i" class="bg-white rounded-lg shadow p-6 animate-pulse">
                  <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                  <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                </div>
              </div>
              <div v-else-if="activityFeed.length > 0" class="space-y-4">
                <!-- Render different feed items based on type -->
                <div v-for="item in activityFeed" :key="`${item.feed_type}-${item.id}`">
                  <!-- Post Item -->
                  <PostItem 
                    v-if="item.feed_type === 'post'"
                    :post="item"
                    @updated="handlePostUpdated"
                    @deleted="handleItemDeleted(item)"
                  />
                  
                  <!-- List Item -->
                  <div v-else-if="item.feed_type === 'list'" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-start justify-between mb-4">
                      <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                          <router-link 
                            :to="`/@${user.custom_url || user.username}/${item.slug}`"
                            class="hover:text-blue-600"
                          >
                            {{ item.name }}
                          </router-link>
                        </h3>
                        <p v-if="item.description" class="text-gray-600 text-sm mb-2">{{ item.description }}</p>
                        <div class="flex items-center text-sm text-gray-500 space-x-4">
                          <span>{{ item.items_count || 0 }} items</span>
                          <span v-if="item.category">{{ item.category.name }}</span>
                          <span>{{ formatDate(item.created_at) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Place Item (if user creates places) -->
                  <div v-else-if="item.feed_type === 'place'" class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
                    <div class="flex items-start justify-between">
                      <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                          <router-link 
                            :to="item.canonical_url || `/p/${item.id}`"
                            class="hover:text-blue-600"
                          >
                            {{ item.title }}
                          </router-link>
                        </h3>
                        <p v-if="item.description" class="text-gray-600 text-sm mb-2">{{ item.description }}</p>
                        <div class="flex items-center text-sm text-gray-500 space-x-4">
                          <span v-if="item.category">{{ item.category.name }}</span>
                          <span v-if="item.location">{{ item.location.city }}, {{ item.location.state }}</span>
                          <span>{{ formatDate(item.created_at) }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                No activity yet
              </div>
              
              <!-- Load More -->
              <div v-if="hasMoreActivity" class="text-center mt-6">
                <button
                  @click="loadMoreActivity"
                  :disabled="loadingMore"
                  class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                >
                  <span v-if="loadingMore">Loading...</span>
                  <span v-else>Load More</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Not Found -->
    <div v-else class="flex flex-col items-center justify-center h-screen">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">Profile Not Found</h1>
      <p class="text-gray-600 mb-8">The profile you're looking for doesn't exist.</p>
      <router-link to="/" class="text-blue-600 hover:underline">Go to Home</router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useProfilesStore } from '@/stores/profiles'
import PostItem from '@/components/PostItem.vue'
import axios from 'axios'

// Note: ListCard component is defined inline below

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const profilesStore = useProfilesStore()

const loading = ref(true)
const user = ref(null)
const tackedPost = ref(null)
const pinnedLists = ref([])
const recentLists = ref([])
const isFollowing = ref(false)
const activityFeed = ref([])
const activityPage = ref(1)
const hasMoreActivity = ref(true)
const loadingMore = ref(false)

const isAuthenticated = computed(() => authStore.isAuthenticated)
const currentUser = computed(() => authStore.user)
const isOwnProfile = computed(() => currentUser.value?.id === user.value?.id)
const profileColor = computed(() => user.value?.profile_color || '#3B82F6')

async function fetchProfile(forceRefresh = false) {
  loading.value = true
  
  try {
    const customUrl = route.params.username
    
    // Try to use cached data first
    const data = await profilesStore.fetchProfile(customUrl, forceRefresh)
    
    user.value = data.user
    pinnedLists.value = data.pinnedLists
    recentLists.value = data.recentLists
    isFollowing.value = data.user.is_following
    
    // Fetch tacked post
    if (user.value) {
      fetchTackedPost()
    }
    
    // Fetch activity feed
    fetchActivityFeed()
  } catch (error) {
    console.error('Failed to fetch profile:', error)
    user.value = null
  } finally {
    loading.value = false
  }
}

async function fetchTackedPost() {
  try {
    const response = await axios.get(`/api/users/${user.value.username}/posts`, {
      params: { tacked_only: true }
    })
    if (response.data.data && response.data.data.length > 0) {
      tackedPost.value = response.data.data[0]
    }
  } catch (error) {
    console.error('Failed to fetch tacked post:', error)
  }
}

function editProfile() {
  router.push({ name: 'ProfileEdit' })
}

async function toggleFollow() {
  try {
    const endpoint = isFollowing.value 
      ? `/api/users/${user.value.username}/unfollow`
      : `/api/users/${user.value.username}/follow`
    
    await axios.post(endpoint)
    
    isFollowing.value = !isFollowing.value
    user.value.followers_count += isFollowing.value ? 1 : -1
  } catch (error) {
    console.error('Failed to toggle follow:', error)
    alert('Failed to update follow status')
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'long',
    year: 'numeric'
  })
}

function handlePostUpdated(updatedPost) {
  tackedPost.value = updatedPost
  // Also update in activity feed if present
  const index = activityFeed.value.findIndex(item => item.feed_type === 'post' && item.id === updatedPost.id)
  if (index !== -1) {
    activityFeed.value[index] = updatedPost
  }
}

function handleItemDeleted(item) {
  activityFeed.value = activityFeed.value.filter(
    feedItem => !(feedItem.feed_type === item.feed_type && feedItem.id === item.id)
  )
}

async function fetchActivityFeed() {
  try {
    const response = await axios.get(`/api/users/${user.value.username}/activity`, {
      params: {
        page: activityPage.value,
        per_page: 10
      }
    })
    
    if (activityPage.value === 1) {
      activityFeed.value = response.data.data || []
    } else {
      activityFeed.value.push(...(response.data.data || []))
    }
    
    hasMoreActivity.value = response.data.last_page > activityPage.value
  } catch (error) {
    console.error('Failed to fetch activity feed:', error)
  }
}

async function loadMoreActivity() {
  if (loadingMore.value || !hasMoreActivity.value) return
  
  loadingMore.value = true
  activityPage.value++
  
  try {
    await fetchActivityFeed()
  } finally {
    loadingMore.value = false
  }
}

onMounted(() => {
  fetchProfile()
})

// Watch for route changes to refresh profile
watch(() => route.params.username, (newUsername, oldUsername) => {
  if (newUsername && newUsername !== oldUsername) {
    fetchProfile()
  }
})
</script>

<script>
// Simple ListCard component (you can extract this to a separate file)
export const ListCard = {
  props: ['list', 'profileColor'],
  template: `
    <router-link
      :to="{ name: 'UserList', params: { username: list.user.custom_url || list.user.username, slug: list.slug } }"
      class="block bg-white rounded-lg shadow hover:shadow-md transition-shadow p-4"
    >
      <div v-if="list.featured_image_url" class="h-32 rounded-lg overflow-hidden mb-4">
        <img :src="list.featured_image_url" :alt="list.name" class="w-full h-full object-cover">
      </div>
      <h3 class="font-semibold text-gray-900 mb-1">{{ list.name }}</h3>
      <p v-if="list.description" class="text-sm text-gray-600 line-clamp-2 mb-2">
        {{ list.description }}
      </p>
      <div class="flex items-center text-sm text-gray-500">
        <span :style="{ color: profileColor }">{{ list.items_count }} items</span>
        <span class="mx-2">•</span>
        <span>{{ list.view_count }} views</span>
      </div>
    </router-link>
  `
}
</script>