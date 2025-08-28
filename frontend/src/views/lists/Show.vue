<template>
  <div class="min-h-screen bg-gray-50">
    {{ console.log('List Show template rendering') }}
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-screen">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
    </div>

    <!-- List Detail Page -->
    <div v-else-if="list">
      <!-- Cover Image -->
      <div v-if="list.featured_image_url" class="relative h-64 sm:h-80 lg:h-96 bg-gray-900">
        <img 
          :src="list.featured_image_url" 
          :alt="list.name"
          class="w-full h-full object-cover opacity-90"
        />
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
        <div class="absolute bottom-0 left-0 right-0 p-8">
          <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-white">{{ list.name }}</h1>
          </div>
        </div>
      </div>

      <!-- Header Section -->
      <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h1 v-if="!list.featured_image_url" class="text-3xl font-bold text-gray-900">{{ list.name }}</h1>
              
              <!-- List Meta -->
              <div class="mt-4 flex items-center space-x-6 text-sm text-gray-500">
                <router-link 
                  v-if="list.owner_type === 'App\\Models\\Channel' && list.owner"
                  :to="`/${list.owner.slug}`"
                  class="flex items-center hover:text-gray-700"
                >
                  <img 
                    :src="list.owner.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(list.owner.name)}&size=32`"
                    :alt="list.owner.name"
                    class="w-8 h-8 rounded-full mr-2"
                  />
                  <span>{{ list.owner.name }}</span>
                </router-link>
                <router-link 
                  v-else-if="list.owner_type === 'App\\Models\\User' && list.owner"
                  :to="`/up/@${list.owner.custom_url || list.owner.username}`"
                  class="flex items-center hover:text-gray-700"
                >
                  <img 
                    :src="list.owner.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(list.owner.name)}&size=32`"
                    :alt="list.owner.name"
                    class="w-8 h-8 rounded-full mr-2"
                  />
                  <span>{{ list.owner.name }}</span>
                </router-link>
                <!-- Fallback to legacy display -->
                <router-link 
                  v-else-if="list.channel"
                  :to="`/${list.channel.slug}`"
                  class="flex items-center hover:text-gray-700"
                >
                  <img 
                    :src="list.channel.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(list.channel.name)}&size=32`"
                    :alt="list.channel.name"
                    class="w-8 h-8 rounded-full mr-2"
                  />
                  <span>{{ list.channel.name }}</span>
                </router-link>
                <router-link 
                  v-else-if="list.user"
                  :to="`/up/@${list.user.custom_url || list.user.username}`"
                  class="flex items-center hover:text-gray-700"
                >
                  <img 
                    :src="list.user.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(list.user.name || `${list.user.firstname || ''} ${list.user.lastname || ''}`.trim() || 'User')}&size=32`"
                    :alt="list.user.name || `${list.user.firstname || ''} ${list.user.lastname || ''}`.trim() || 'User'"
                    class="w-8 h-8 rounded-full mr-2"
                  />
                  <span>{{ list.user.name || `${list.user.firstname || ''} ${list.user.lastname || ''}`.trim() || 'User' }}</span>
                </router-link>
                
                <span>•</span>
                <span>{{ list.items_count }} {{ list.items_count === 1 ? 'item' : 'items' }}</span>
                <span>•</span>
                <span>{{ list.view_count }} views</span>
                <span v-if="list.updated_at">•</span>
                <span v-if="list.updated_at">Updated {{ formatDate(list.updated_at) }}</span>
              </div>

              <!-- Tags -->
              <div v-if="list.tags && list.tags.length > 0" class="mt-4 flex flex-wrap gap-2">
                <span 
                  v-for="tag in list.tags" 
                  :key="tag.id"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                  :style="{ backgroundColor: tag.color + '20', color: tag.color }"
                >
                  {{ tag.name }}
                </span>
              </div>

              <!-- Social Interactions -->
              <div class="mt-6 pt-4 border-t">
                <InteractionBar
                  type="list"
                  :id="list.id"
                  :liked="list.is_liked || false"
                  :likes-count="list.likes_count || 0"
                  :comments-count="list.comments_count || 0"
                  :reposted="list.is_reposted || false"
                  :reposts-count="list.reposts_count || 0"
                  @toggle-comments="showComments = !showComments"
                />
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-3">
              <!-- Follow Button (for list creator) -->
              <FollowButton
                v-if="authStore.isAuthenticated && !isOwnList && listCreatorInfo"
                :followable-type="listCreatorInfo.type"
                :followable-id="listCreatorInfo.id"
              />
              <SaveButton
                v-if="authStore.isAuthenticated"
                item-type="list"
                :item-id="list.id"
                :initial-saved="list.is_saved || false"
              />
              <router-link
                v-if="isOwnList"
                :to="{ name: 'ListEdit', params: { id: list.id } }"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                Edit List
              </router-link>
              <QRCodeGenerator
                type="list"
                :data="list"
                button-text="QR Code"
                :show-button-text="false"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Description Section -->
      <div v-if="list.description" class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div class="list-description prose prose-lg max-w-4xl mx-auto" v-html="list.description"></div>
        </div>
      </div>

      <!-- Comments Section -->
      <div v-if="showComments" class="bg-gray-50 border-y">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
          <CommentSection
            type="list"
            :id="list.id"
            :per-page="10"
          />
        </div>
      </div>

      <!-- List Items -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Section-based display -->
        <div v-if="list.structure_version === '2.0' && list.sections && list.sections.length > 0" class="space-y-8">
          <div v-for="section in list.sections" :key="section.id" class="bg-gray-50 rounded-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">{{ section.heading || section.title }}</h2>
            <div class="space-y-4">
              <div 
                v-for="(item, index) in getSectionItems(section.id)" 
                :key="item.id"
                class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow"
              >
            <div class="p-6">
              <div class="flex items-start space-x-4">
                <!-- Item Number -->
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                  <span class="text-indigo-600 font-semibold">{{ index + 1 }}</span>
                </div>

                <!-- Item Content -->
                <div class="flex-1 min-w-0">
                  <!-- Directory Entry Type (Place) -->
                  <div v-if="item.type === 'directory_entry' && (item.directory_entry || item.place)">
                    <h3 class="text-lg font-semibold text-gray-900">
                      {{ item.directory_entry?.title || item.place?.title }}
                    </h3>
                    <p v-if="item.directory_entry?.description || item.place?.description" class="mt-1 text-gray-600">
                      {{ item.directory_entry?.description || item.place?.description }}
                    </p>
                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                      <span v-if="item.directory_entry?.location || item.place?.location">
                        📍 {{ item.directory_entry?.location?.city || item.place?.location?.city }}, {{ item.directory_entry?.location?.state || item.place?.location?.state }}
                      </span>
                      <span v-if="item.directory_entry?.category || item.place?.category">
                        🏷️ {{ item.directory_entry?.category?.name || item.place?.category?.name }}
                      </span>
                    </div>
                  </div>

                  <!-- Text Type -->
                  <div v-else-if="item.type === 'text'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <div v-if="item.content" class="mt-1 prose prose-sm max-w-none text-gray-600" v-html="item.content"></div>
                  </div>

                  <!-- Location Type -->
                  <div v-else-if="item.type === 'location'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <div v-if="item.content" class="mt-1 prose prose-sm max-w-none text-gray-600" v-html="item.content"></div>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.address">📍 {{ item.data.address }}</span>
                    </div>
                  </div>

                  <!-- Event Type -->
                  <div v-else-if="item.type === 'event'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <div v-if="item.content" class="mt-1 prose prose-sm max-w-none text-gray-600" v-html="item.content"></div>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.start_date">
                        📅 {{ formatDate(item.data.start_date) }}
                      </span>
                      <span v-if="item.data.location" class="ml-4">
                        📍 {{ item.data.location }}
                      </span>
                    </div>
                  </div>

                  <!-- Region Type -->
                  <div v-else-if="item.type === 'region'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <p v-if="item.content" class="mt-1 text-gray-600">{{ item.content }}</p>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.region_type">{{ item.data.region_type }}</span>
                      <span v-if="item.data.region_name" class="ml-2">in {{ item.data.region_name }}</span>
                    </div>
                  </div>

                  <!-- List Type -->
                  <div v-else-if="item.type === 'list'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <p v-if="item.content" class="mt-1 text-gray-600">{{ item.content }}</p>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.author">by {{ item.data.author }}</span>
                    </div>
                  </div>

                  <!-- Item Notes -->
                  <div v-if="item.notes" class="mt-3 p-3 bg-gray-50 rounded-md">
                    <p class="text-sm text-gray-700">{{ item.notes }}</p>
                  </div>

                  <!-- Affiliate Link -->
                  <div v-if="item.affiliate_url" class="mt-3">
                    <a 
                      :href="item.affiliate_url" 
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500"
                    >
                      <span>Visit Website</span>
                      <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                      </svg>
                    </a>
                  </div>
                </div>

                <!-- Item Image -->
                <div v-if="item.item_image_cloudflare_id || item.image" class="flex-shrink-0">
                  <img 
                    :src="getItemImageUrl(item)"
                    :alt="item.title || 'Item image'"
                    class="w-24 h-24 rounded-lg object-cover"
                  />
                </div>
              </div>
            </div>
          </div>
            </div>
          </div>
        </div>
        
        <!-- Legacy flat list display -->
        <div v-else-if="list.items && list.items.length > 0" class="space-y-6">
          <div 
            v-for="(item, index) in list.items" 
            :key="item.id"
            class="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow"
          >
            <div class="p-6">
              <div class="flex items-start space-x-4">
                <!-- Item Number -->
                <div class="flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                  <span class="text-indigo-600 font-semibold">{{ index + 1 }}</span>
                </div>

                <!-- Item Content -->
                <div class="flex-1 min-w-0">
                  <!-- Directory Entry Type (Place) -->
                  <div v-if="item.type === 'directory_entry' && (item.directory_entry || item.place)">
                    <h3 class="text-lg font-semibold text-gray-900">
                      {{ item.directory_entry?.title || item.place?.title }}
                    </h3>
                    <p v-if="item.directory_entry?.description || item.place?.description" class="mt-1 text-gray-600">
                      {{ item.directory_entry?.description || item.place?.description }}
                    </p>
                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                      <span v-if="item.directory_entry?.category?.name || item.place?.category?.name">
                        {{ item.directory_entry?.category?.name || item.place?.category?.name }}
                      </span>
                    </div>
                  </div>

                  <!-- Text Type -->
                  <div v-else-if="item.type === 'text'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <div v-if="item.content" class="mt-1 prose prose-sm max-w-none text-gray-600" v-html="item.content"></div>
                  </div>

                  <!-- Location Type -->
                  <div v-else-if="item.type === 'location'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <div v-if="item.content" class="mt-1 prose prose-sm max-w-none text-gray-600" v-html="item.content"></div>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.address">📍 {{ item.data.address }}</span>
                    </div>
                  </div>

                  <!-- Event Type -->
                  <div v-else-if="item.type === 'event'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <div v-if="item.content" class="mt-1 prose prose-sm max-w-none text-gray-600" v-html="item.content"></div>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.start_date">
                        📅 {{ formatDate(item.data.start_date) }}
                      </span>
                      <span v-if="item.data.location" class="ml-4">
                        📍 {{ item.data.location }}
                      </span>
                    </div>
                  </div>

                  <!-- Region Type -->
                  <div v-else-if="item.type === 'region'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <p v-if="item.content" class="mt-1 text-gray-600">{{ item.content }}</p>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.region_type">{{ item.data.region_type }}</span>
                      <span v-if="item.data.region_name" class="ml-2">in {{ item.data.region_name }}</span>
                    </div>
                  </div>

                  <!-- List Type -->
                  <div v-else-if="item.type === 'list'">
                    <h3 class="text-lg font-semibold text-gray-900">{{ item.title }}</h3>
                    <p v-if="item.content" class="mt-1 text-gray-600">{{ item.content }}</p>
                    <div v-if="item.data" class="mt-2 text-sm text-gray-500">
                      <span v-if="item.data.author">by {{ item.data.author }}</span>
                    </div>
                  </div>

                  <!-- Item Notes -->
                  <div v-if="item.notes" class="mt-3 p-3 bg-gray-50 rounded-md">
                    <p class="text-sm text-gray-700">{{ item.notes }}</p>
                  </div>

                  <!-- Affiliate Link -->
                  <div v-if="item.affiliate_url" class="mt-3">
                    <a 
                      :href="item.affiliate_url" 
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-500"
                    >
                      <span>Visit Website</span>
                      <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                      </svg>
                    </a>
                  </div>
                </div>

                <!-- Item Image -->
                <div v-if="item.item_image_cloudflare_id || item.image" class="flex-shrink-0">
                  <img 
                    :src="getItemImageUrl(item)"
                    :alt="item.title || 'Item image'"
                    class="w-24 h-24 rounded-lg object-cover"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No items in this list</h3>
          <p class="mt-1 text-sm text-gray-500">This list doesn't have any items yet.</p>
        </div>

        <!-- Back to Lists -->
        <div class="mt-8 text-center">
          <router-link
            v-if="list.channel"
            :to="`/${list.channel.slug}`"
            class="text-indigo-600 hover:text-indigo-500"
          >
            ← Back to {{ list.channel.name }}
          </router-link>
          <router-link
            v-else-if="list.user"
            :to="`/up/@${list.user.custom_url || list.user.username}`"
            class="text-indigo-600 hover:text-indigo-500"
          >
            ← Back to {{ list.user.name }}'s profile
          </router-link>
        </div>
      </div>
    </div>

    <!-- Not Found -->
    <div v-else class="flex flex-col items-center justify-center h-screen">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">{{ errorTitle }}</h1>
      <p class="text-gray-600 mb-8">{{ errorMessage }}</p>
      <div class="flex space-x-4">
        <router-link to="/" class="text-indigo-600 hover:underline">Go to Home</router-link>
        <router-link v-if="!authStore.isAuthenticated" to="/login" class="text-indigo-600 hover:underline">Sign In</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import SaveButton from '@/components/SaveButton.vue'
import FollowButton from '@/components/FollowButton.vue'
import InteractionBar from '@/components/social/InteractionBar.vue'
import CommentSection from '@/components/social/CommentSection.vue'
import QRCodeGenerator from '@/components/QRCodeGenerator.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const list = ref(null)
const errorTitle = ref('List Not Found')
const errorMessage = ref("The list you're looking for doesn't exist or is private.")
const showComments = ref(false)

const getSectionItems = (sectionId) => {
  if (!list.value || !list.value.items) return []
  return list.value.items.filter(item => item.section_id === sectionId && !item.is_section)
}

const currentUser = computed(() => authStore.user)
const isOwnList = computed(() => {
  if (!currentUser.value || !list.value) return false
  
  // Check polymorphic ownership
  if (list.value.owner_type === 'App\\Models\\User') {
    return list.value.owner_id === currentUser.value.id
  } else if (list.value.owner_type === 'App\\Models\\Channel' && list.value.owner) {
    return list.value.owner.user_id === currentUser.value.id
  }
  
  // Fallback to legacy ownership
  if (list.value.user?.id === currentUser.value.id) return true
  if (list.value.channel && list.value.channel.user_id === currentUser.value.id) return true
  
  return false
})

const listCreatorInfo = computed(() => {
  if (!list.value) return null
  
  // Check polymorphic ownership first
  if (list.value.owner_type === 'App\\Models\\User' && list.value.owner) {
    return {
      type: 'user',
      id: list.value.owner.id
    }
  } else if (list.value.owner_type === 'App\\Models\\Channel' && list.value.owner) {
    return {
      type: 'channel',
      id: list.value.owner.slug // Use slug for channels
    }
  }
  
  // Fallback to legacy fields
  if (list.value.channel) {
    return {
      type: 'channel',
      id: list.value.channel.slug // Use slug for channels
    }
  } else if (list.value.user) {
    return {
      type: 'user',
      id: list.value.user.id
    }
  }
  
  return null
})

async function fetchList() {
  loading.value = true
  
  try {
    let response
    
    // Check if this is a channel list or user list
    if (route.params.channelSlug) {
      // Channel list: /{channel}/{list}
      const { channelSlug, slug } = route.params
      console.log('Fetching channel list:', channelSlug, slug || route.params.listSlug)
      const listSlug = slug || route.params.listSlug
      response = await axios.get(`/api/channels/${channelSlug}/lists/${listSlug}`)
    } else {
      // User list: /up/@{username}/{list}
      const { username, slug } = route.params
      console.log('Fetching user list:', username, slug)
      response = await axios.get(`/api/up/@${username}/${slug}`)
    }
    
    console.log('List fetched successfully:', response.data)
    list.value = response.data
  } catch (error) {
    console.error('Failed to fetch list:', error)
    
    // If it's a 403 (forbidden) and user is not authenticated, redirect to login
    if (error.response?.status === 403 && !authStore.isAuthenticated) {
      // Save the current route to redirect back after login
      router.push({ 
        name: 'Login', 
        query: { redirect: route.fullPath } 
      })
      return
    }
    
    // Set appropriate error messages based on status
    if (error.response?.status === 403) {
      errorTitle.value = 'Access Denied'
      errorMessage.value = 'You do not have permission to view this list.'
    } else if (error.response?.status === 404) {
      errorTitle.value = 'List Not Found'
      errorMessage.value = "The list you're looking for doesn't exist."
    }
    
    list.value = null
  } finally {
    loading.value = false
  }
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  })
}

function getItemImageUrl(item) {
  if (item.item_image_cloudflare_id) {
    return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${item.item_image_cloudflare_id}/public`
  }
  return item.image
}

onMounted(() => {
  console.log('List Show component mounted')
  console.log('Route params:', route.params)
  console.log('Route path:', route.path)
  console.log('Route name:', route.name)
  fetchList()
})
</script>

<style scoped>
/* Prose styles for list items - using deep selectors for dynamic content */
.prose :deep(*) {
  @apply text-gray-700;
}

/* Bullet Lists */
.prose :deep(ul) {
  @apply my-2 ml-0 list-none;
}

.prose :deep(ul li) {
  @apply relative pl-6 mb-2;
  line-height: 1.6;
}

.prose :deep(ul li::before) {
  content: "•";
  @apply absolute text-gray-400;
  left: 0;
  font-size: 1rem;
  line-height: inherit;
  display: flex;
  align-items: center;
  height: 100%;
}

/* Numbered Lists */
.prose :deep(ol) {
  @apply my-2 ml-0 list-none;
  counter-reset: list-counter;
}

.prose :deep(ol li) {
  @apply relative pl-7 mb-2;
  line-height: 1.6;
  counter-increment: list-counter;
}

.prose :deep(ol li::before) {
  content: counter(list-counter) ".";
  @apply absolute left-0 font-medium text-gray-500;
  line-height: inherit;
  display: flex;
  align-items: center;
  height: 100%;
}

/* Paragraphs in lists */
.prose :deep(li p) {
  @apply inline-block m-0;
  line-height: inherit;
}

/* Nested lists */
.prose :deep(ul ul),
.prose :deep(ul ol),
.prose :deep(ol ul),
.prose :deep(ol ol) {
  @apply mt-1 mb-1 ml-6;
}

/* Headings in prose */
.prose :deep(h2) {
  @apply text-xl font-bold text-gray-900 mt-3 mb-2;
}

.prose :deep(h3) {
  @apply text-lg font-semibold text-gray-900 mt-2 mb-1;
}

/* Strong text */
.prose :deep(strong) {
  @apply font-semibold text-gray-900;
}

/* Horizontal rule */
.prose :deep(hr) {
  @apply my-4 border-0 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent;
}

/* Elegant typography for list description */
.list-description {
  @apply text-gray-800;
  line-height: 1.8;
  font-size: 1.125rem;
}

/* Headings */
.list-description :deep(h2) {
  @apply text-2xl font-bold text-gray-900 mt-8 mb-4;
  letter-spacing: -0.02em;
  line-height: 1.3;
}

.list-description :deep(h3) {
  @apply text-xl font-semibold text-gray-900 mt-6 mb-3;
  letter-spacing: -0.01em;
  line-height: 1.4;
}

.list-description :deep(h4) {
  @apply text-lg font-semibold text-gray-800 mt-5 mb-2;
  line-height: 1.4;
}

/* Paragraphs */
.list-description :deep(p) {
  @apply mb-4 text-gray-700;
  line-height: 1.75;
  font-size: 1.0625rem;
}

.list-description :deep(p:first-child) {
  @apply mt-0;
}

.list-description :deep(p:last-child) {
  @apply mb-0;
}

/* Strong text */
.list-description :deep(strong) {
  @apply font-semibold text-gray-900;
  letter-spacing: -0.01em;
}

/* Lists */
.list-description :deep(ul) {
  @apply my-4 ml-0 list-none;
}

.list-description :deep(ul li) {
  @apply relative pl-7 mb-2 text-gray-700;
  line-height: 1.75;
  font-size: 1.0625rem;
}

.list-description :deep(ul li:before) {
  content: "•";
  @apply absolute left-0 text-gray-400;
  font-size: 1.25rem;
  line-height: 1.2;
  top: -0.05em;
}

.list-description :deep(ol) {
  @apply my-4 ml-0 list-none;
  counter-reset: list-counter;
}

.list-description :deep(ol li) {
  @apply relative pl-8 mb-3 text-gray-700;
  line-height: 1.75;
  font-size: 1.0625rem;
  counter-increment: list-counter;
}

.list-description :deep(ol li:before) {
  content: counter(list-counter) ".";
  @apply absolute left-0 font-medium text-gray-500;
  top: 0;
}

/* Nested lists */
.list-description :deep(li ul),
.list-description :deep(li ol) {
  @apply mt-2 mb-2;
}

.list-description :deep(li li) {
  @apply text-base;
  font-size: 1rem;
}

/* Horizontal rule */
.list-description :deep(hr) {
  @apply my-8 border-0 h-px bg-gradient-to-r from-transparent via-gray-300 to-transparent;
}

/* Links */
.list-description :deep(a) {
  @apply text-blue-600 underline decoration-blue-200 hover:decoration-blue-600 transition-colors;
}

/* Blockquotes */
.list-description :deep(blockquote) {
  @apply pl-6 my-6 border-l-4 border-gray-300 italic text-gray-700;
  font-size: 1.125rem;
  line-height: 1.75;
}

/* Code */
.list-description :deep(code) {
  @apply px-1.5 py-0.5 bg-gray-100 text-gray-800 rounded text-sm;
  font-family: 'SF Mono', Monaco, Inconsolata, 'Fira Code', monospace;
}

.list-description :deep(pre) {
  @apply my-4 p-4 bg-gray-50 rounded-lg overflow-x-auto;
}

.list-description :deep(pre code) {
  @apply p-0 bg-transparent;
}

/* Tables */
.list-description :deep(table) {
  @apply w-full my-6 border-collapse;
}

.list-description :deep(th) {
  @apply text-left font-semibold text-gray-900 border-b-2 border-gray-300 pb-2 px-3;
}

.list-description :deep(td) {
  @apply border-b border-gray-200 py-2 px-3 text-gray-700;
}

/* Images in content */
.list-description :deep(img) {
  @apply my-6 rounded-lg shadow-sm;
}

/* First paragraph after heading - larger text */
.list-description :deep(h2 + p),
.list-description :deep(h3 + p) {
  font-size: 1.125rem;
  @apply text-gray-700;
}

/* Emphasis on first paragraph */
.list-description > :deep(p:first-of-type) {
  @apply text-lg leading-relaxed text-gray-800;
  font-size: 1.25rem;
  line-height: 1.75;
}
</style>