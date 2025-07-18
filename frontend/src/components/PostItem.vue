<template>
  <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow p-6">
    <!-- Image Lightbox -->
    <ImageLightbox
      v-model="showLightbox"
      :images="post.media_items"
      :initial-index="lightboxIndex"
    />
    
    <!-- Edit Modal -->
    <PostEditModal
      v-if="isOwner"
      v-model="showEditModal"
      :post="post"
      @updated="handlePostUpdated"
      @deleted="handlePostDeleted"
    />
    <!-- Post Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center space-x-3">
        <router-link 
          :to="`/up/@${post.user?.custom_url || post.user?.username}`"
          class="flex-shrink-0"
        >
          <img
            :src="getUserAvatar(post.user)"
            :alt="post.user?.name"
            class="h-10 w-10 rounded-full hover:ring-2 hover:ring-blue-500 transition-all"
          />
        </router-link>
        
        <div>
          <router-link 
            :to="`/up/@${post.user?.custom_url || post.user?.username}`"
            class="font-medium text-gray-900 hover:text-blue-600"
          >
            {{ post.user?.name }}
          </router-link>
          <div class="flex items-center text-sm text-gray-500 space-x-2">
            <span>{{ post.formatted_date }}</span>
            <span v-if="post.is_tacked" class="flex items-center text-blue-600">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05L14.5 16.95a1 1 0 01-1.414-.05l-1.086-1.086L11 17a1 1 0 01-2 0v-1.186l-1.086 1.086a1 1 0 01-1.414.05L3.333 13.98a1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.788l1.599.799L10 4.323V3a1 1 0 011-1z" />
              </svg>
              Tacked
            </span>
            <span v-if="post.time_until_expiration" class="text-orange-600">
              Expires {{ post.time_until_expiration }}
            </span>
          </div>
        </div>
      </div>
      
      <div class="relative">
        <button
          @click.stop="toggleMenu"
          class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
          </svg>
        </button>
        
        <!-- Dropdown Menu -->
        <div
          v-if="showMenu"
          v-click-outside="() => showMenu = false"
          class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border"
        >
          <div class="py-1">
            <button
              v-if="isOwner && !post.is_tacked"
              @click="handleTack"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
              </svg>
              Tack Post
            </button>
            
            <button
              v-if="isOwner && post.is_tacked"
              @click="handleUntack"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
              </svg>
              Untack Post
            </button>
            
            <button
              v-if="isOwner"
              @click="handleEdit"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
              Edit
            </button>
            
            <button
              v-if="isOwner"
              @click="handleDelete"
              class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
            >
              <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Delete
            </button>
            
            <button
              @click="handleShare"
              class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
            >
              <svg class="inline w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.632 4.368C18.114 16.062 18 16.518 18 17c0 .482.114.938.316 1.342m0-2.684a3 3 0 110 2.684M9.316 10.658C9.114 10.062 9 9.518 9 9c0-.482.114-.938.316-1.342m9.368 10.684C18.886 17.938 19 17.482 19 17c0-.482-.114-.938-.316-1.342M9.316 7.658a3 3 0 110 2.684m9.368 10.684a3 3 0 110-2.684m0 0c-.701-.437-1.52-.658-2.368-.658H7.684c-.848 0-1.667.221-2.368.658" />
              </svg>
              Share
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Post Content -->
    <div class="mb-4">
      <p class="text-gray-900 whitespace-pre-wrap">{{ post.content }}</p>
    </div>
    
    <!-- Tags -->
    <div v-if="post.tags && post.tags.length > 0" class="mb-4">
      <div class="flex flex-wrap gap-2">
        <span
          v-for="tag in post.tags"
          :key="tag.id"
          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          :style="{ 
            backgroundColor: (tag.color || '#6B7280') + '20', 
            color: tag.color || '#6B7280' 
          }"
        >
          #{{ tag.name }}
        </span>
      </div>
    </div>
    
    <!-- Media -->
    <div v-if="post.media_items && post.media_items.length > 0" class="mb-4">
      <!-- Single Image -->
      <div v-if="post.media_type === 'images' && post.media_items.length === 1" class="rounded-lg overflow-hidden">
        <ImageKitImage
          :src="post.media_items[0].url"
          :alt="'Post by ' + post.user?.name"
          transformation="large"
          container-class="w-full"
          image-class="w-full h-auto max-h-96 object-cover cursor-pointer"
          @click="openLightbox(0)"
        />
      </div>
      
      <!-- Multiple Images Gallery -->
      <div 
        v-else-if="post.media_type === 'images' && post.media_items.length > 1" 
        class="grid gap-1 rounded-lg overflow-hidden"
        :class="{
          'grid-cols-2': post.media_items.length === 2,
          'grid-cols-2': post.media_items.length === 3,
          'grid-cols-2': post.media_items.length >= 4
        }"
      >
        <div
          v-for="(item, index) in post.media_items.slice(0, 4)"
          :key="index"
          class="relative cursor-pointer"
          :class="{
            'col-span-2': post.media_items.length === 3 && index === 0,
            'row-span-2': post.media_items.length === 3 && index === 0
          }"
          @click="openLightbox(index)"
        >
          <ImageKitImage
            :src="item.url"
            :alt="`Image ${index + 1}`"
            transformation="gallery_thumb"
            container-class="w-full h-full"
            image-class="w-full h-full object-cover"
            :class="{
              'aspect-square': post.media_items.length > 1 && !(post.media_items.length === 3 && index === 0),
              'aspect-video': post.media_items.length === 3 && index === 0
            }"
          />
          
          <!-- Overlay for 4+ images -->
          <div 
            v-if="post.media_items.length > 4 && index === 3"
            class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-white text-2xl font-bold"
          >
            +{{ post.media_items.length - 4 }}
          </div>
        </div>
      </div>
      
      <!-- Video -->
      <div v-else-if="post.media_type === 'video' && post.media_items[0]" class="rounded-lg overflow-hidden">
        <video 
          :src="post.media_items[0].url" 
          controls 
          class="w-full h-auto max-h-96"
        />
      </div>
    </div>
    
    <!-- Actions Bar -->
    <div class="flex items-center justify-between text-sm">
      <div class="flex items-center space-x-4">
        <button
          @click="handleLike"
          class="flex items-center space-x-1 text-gray-500 hover:text-red-600 transition-colors"
          :class="{ 'text-red-600': isLiked }"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
          <span>{{ post.likes_count || 0 }}</span>
        </button>
        
        <button
          @click="handleReply"
          class="flex items-center space-x-1 text-gray-500 hover:text-blue-600 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <span>{{ post.replies_count || 0 }}</span>
        </button>
        
        <button
          @click="handleShare"
          class="flex items-center space-x-1 text-gray-500 hover:text-green-600 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.632 4.368C18.114 16.062 18 16.518 18 17c0 .482.114.938.316 1.342m0-2.684a3 3 0 110 2.684M9.316 10.658C9.114 10.062 9 9.518 9 9c0-.482.114-.938.316-1.342m9.368 10.684C18.886 17.938 19 17.482 19 17c0-.482-.114-.938-.316-1.342M9.316 7.658a3 3 0 110 2.684m9.368 10.684a3 3 0 110-2.684m0 0c-.701-.437-1.52-.658-2.368-.658H7.684c-.848 0-1.667.221-2.368.658" />
          </svg>
          <span>{{ post.shares_count || 0 }}</span>
        </button>
      </div>
      
      <div class="text-gray-500">
        <span v-if="post.views_count > 0">{{ post.views_count }} views</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useFeedStore } from '@/stores/feed'
import ImageKitImage from '@/components/ImageKitImage.vue'
import ImageLightbox from '@/components/ImageLightbox.vue'
import PostEditModal from '@/components/PostEditModal.vue'
import axios from 'axios'

const props = defineProps({
  post: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['updated', 'deleted'])

const authStore = useAuthStore()
const feedStore = useFeedStore()

const showMenu = ref(false)
const isLiked = ref(false)
const showLightbox = ref(false)
const lightboxIndex = ref(0)
const showEditModal = ref(false)

const isOwner = computed(() => {
  return authStore.user?.id === props.post.user?.id
})

// Toggle menu
const toggleMenu = () => {
  showMenu.value = !showMenu.value
}

// Handle tack
const handleTack = async () => {
  showMenu.value = false
  try {
    const response = await axios.post(`/api/posts/${props.post.id}/tack`)
    feedStore.updatePost(props.post.id, response.data.post)
    emit('updated', response.data.post)
  } catch (error) {
    console.error('Error tacking post:', error)
  }
}

// Handle untack
const handleUntack = async () => {
  showMenu.value = false
  try {
    const response = await axios.delete(`/api/posts/${props.post.id}/tack`)
    feedStore.updatePost(props.post.id, response.data.post)
    emit('updated', response.data.post)
  } catch (error) {
    console.error('Error untacking post:', error)
  }
}

// Handle edit
const handleEdit = () => {
  showMenu.value = false
  showEditModal.value = true
}

// Handle delete
const handleDelete = async () => {
  showMenu.value = false
  if (!confirm('Are you sure you want to delete this post?')) return
  
  try {
    await axios.delete(`/api/posts/${props.post.id}`)
    feedStore.removePost(props.post.id)
    emit('deleted')
  } catch (error) {
    console.error('Error deleting post:', error)
  }
}

// Handle like
const handleLike = () => {
  // TODO: Implement like functionality
  isLiked.value = !isLiked.value
}

// Handle reply
const handleReply = () => {
  // TODO: Implement reply functionality
  console.log('Reply to post:', props.post.id)
}

// Handle share
const handleShare = () => {
  showMenu.value = false
  // TODO: Implement share functionality
  console.log('Share post:', props.post.id)
}

// Open lightbox
const openLightbox = (index) => {
  lightboxIndex.value = index
  showLightbox.value = true
}

// Handle post updated from edit modal
const handlePostUpdated = (updatedPost) => {
  emit('updated', updatedPost)
}

// Handle post deleted from edit modal
const handlePostDeleted = () => {
  emit('deleted')
}

// Get user avatar URL
const getUserAvatar = (user) => {
  if (!user) return `https://ui-avatars.com/api/?name=User&background=3B82F6&color=fff`
  
  if (user.avatar_url) {
    return user.avatar_url
  }
  if (user.avatar_cloudflare_id) {
    return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${user.avatar_cloudflare_id}/avatar`
  }
  if (user.avatar) {
    return user.avatar
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name || 'User')}&background=3B82F6&color=fff`
}

// Custom directive for click outside
const vClickOutside = {
  mounted(el, binding) {
    el.clickOutsideEvent = function(event) {
      if (!(el === event.target || el.contains(event.target))) {
        binding.value(event)
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el) {
    document.removeEventListener('click', el.clickOutsideEvent)
  }
}
</script>