<template>
  <div class="bg-white rounded-lg shadow p-4">
    <!-- Header -->
    <div class="flex items-start space-x-3">
      <div class="flex-shrink-0">
        <img
          :src="getUserAvatar()"
          :alt="user.name"
          class="h-10 w-10 rounded-full"
        />
      </div>
      
      <div class="flex-1">
        <!-- Text Input -->
        <div class="relative">
          <textarea
            v-model="content"
            @input="autoResize"
            @focus="isFocused = true"
            @keydown.meta.enter="handleSubmit"
            @keydown.ctrl.enter="handleSubmit"
            ref="textareaRef"
            :placeholder="placeholder"
            :disabled="isSubmitting"
            class="w-full border-0 p-0 text-gray-900 placeholder-gray-500 focus:ring-0 resize-none"
            :class="{ 'opacity-50': isSubmitting }"
            rows="1"
            maxlength="500"
          />
          
          <!-- Character Count -->
          <div 
            v-if="content.length > 0" 
            class="absolute bottom-0 right-0 text-xs"
            :class="characterCountClass"
          >
            {{ remainingCharacters }}
          </div>
        </div>
        
        <!-- Media Uploader -->
        <div v-if="isFocused || content.length > 0" class="mt-3">
          <PostMediaUploader
            ref="mediaUploader"
            @media-uploaded="handleMediaUploaded"
            @error="handleMediaError"
          />
        </div>
        
        <!-- Actions Bar -->
        <div 
          v-if="isFocused || content.length > 0 || hasMedia" 
          class="mt-3 flex items-center justify-between border-t pt-3"
        >
          <div class="flex items-center space-x-2">
            <!-- Expiration Settings -->
            <div class="relative">
              <button
                type="button"
                @click="showExpirationMenu = !showExpirationMenu"
                class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors flex items-center space-x-1"
                title="Set expiration"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-xs">{{ expirationLabel }}</span>
              </button>
              
              <!-- Expiration Dropdown -->
              <div
                v-if="showExpirationMenu"
                v-click-outside="() => showExpirationMenu = false"
                class="absolute bottom-full mb-2 left-0 bg-white rounded-lg shadow-lg border py-1 min-w-[150px] z-10"
              >
                <button
                  v-for="option in expirationOptions"
                  :key="option.value"
                  @click="setExpiration(option.value)"
                  class="block w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
                  :class="{ 'bg-gray-50 font-medium': expiresInDays === option.value }"
                >
                  {{ option.label }}
                </button>
              </div>
            </div>
          </div>
          
          <div class="flex items-center space-x-3">
            <!-- Cancel Button -->
            <button
              v-if="content.length > 0 || hasMedia"
              @click="handleCancel"
              type="button"
              class="px-3 py-1 text-sm text-gray-500 hover:text-gray-700"
            >
              Cancel
            </button>
            
            <!-- Post Button -->
            <button
              @click="handleSubmit"
              :disabled="!canSubmit"
              type="button"
              class="px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-full hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center space-x-2"
            >
              <span>Post</span>
              <svg
                v-if="isSubmitting"
                class="animate-spin h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Success Message -->
        <div
          v-if="showSuccess"
          class="mt-3 p-3 bg-green-50 text-green-800 text-sm rounded-lg flex items-center"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Post created successfully!
        </div>
        
        <!-- Error Message -->
        <div
          v-if="error"
          class="mt-3 p-3 bg-red-50 text-red-800 text-sm rounded-lg"
        >
          {{ error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useFeedStore } from '@/stores/feed'
import PostMediaUploader from '@/components/PostMediaUploader.vue'
import axios from 'axios'

const authStore = useAuthStore()
const feedStore = useFeedStore()

const user = computed(() => authStore.user)
const placeholder = computed(() => `What's on your mind, ${user.value?.name?.split(' ')[0]}?`)

// Form state
const content = ref('')
const mediaData = ref(null)
const expiresInDays = ref(30)
const isSubmitting = ref(false)
const isFocused = ref(false)
const showSuccess = ref(false)
const error = ref(null)
const showExpirationMenu = ref(false)
const textareaRef = ref(null)
const mediaUploader = ref(null)

// Media state
const hasMedia = computed(() => mediaData.value !== null)

// Character count
const remainingCharacters = computed(() => 500 - content.value.length)
const characterCountClass = computed(() => {
  if (remainingCharacters.value < 0) return 'text-red-600'
  if (remainingCharacters.value < 50) return 'text-orange-600'
  return 'text-gray-400'
})

// Can submit check
const canSubmit = computed(() => {
  return content.value.trim().length > 0 && 
         content.value.length <= 500 && 
         !isSubmitting.value
})

// Expiration options
const expirationOptions = [
  { value: 1, label: '1 day' },
  { value: 7, label: '7 days' },
  { value: 30, label: '30 days' },
  { value: 60, label: '60 days' },
  { value: 90, label: '90 days' },
  { value: 365, label: '1 year' },
  { value: null, label: 'Never' }
]

const expirationLabel = computed(() => {
  const option = expirationOptions.find(o => o.value === expiresInDays.value)
  return option ? option.label : '30 days'
})

// Auto-resize textarea
const autoResize = () => {
  nextTick(() => {
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
      textareaRef.value.style.height = textareaRef.value.scrollHeight + 'px'
    }
  })
}

// Handle media uploaded from PostMediaUploader
const handleMediaUploaded = (data) => {
  mediaData.value = data
}

// Handle media upload error
const handleMediaError = (errorMessage) => {
  error.value = errorMessage
}

// Set expiration
const setExpiration = (days) => {
  expiresInDays.value = days
  showExpirationMenu.value = false
}

// Handle form submission
const handleSubmit = async () => {
  if (!canSubmit.value) return
  
  isSubmitting.value = true
  error.value = null
  
  try {
    const postData = {
      content: content.value.trim(),
      expires_in_days: expiresInDays.value
    }
    
    // Add media data if present
    if (mediaData.value) {
      if (mediaData.value.type === 'images' && mediaData.value.images.length > 0) {
        postData.media = mediaData.value.images
        postData.media_type = 'images'
      } else if (mediaData.value.type === 'video' && mediaData.value.video) {
        postData.media = [mediaData.value.video]
        postData.media_type = 'video'
      }
    }
    
    const response = await axios.post('/api/posts', postData)
    
    // Add to feed store
    feedStore.prependPost(response.data.post)
    
    // Reset form
    content.value = ''
    mediaData.value = null
    if (mediaUploader.value) {
      mediaUploader.value.clearAllMedia()
    }
    isFocused.value = false
    expiresInDays.value = 30
    
    // Show success
    showSuccess.value = true
    setTimeout(() => {
      showSuccess.value = false
    }, 3000)
    
    // Reset textarea height
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
    }
    
  } catch (err) {
    console.error('Error creating post:', err)
    error.value = err.response?.data?.message || 'Failed to create post. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

// Handle cancel
const handleCancel = () => {
  content.value = ''
  mediaData.value = null
  if (mediaUploader.value) {
    mediaUploader.value.clearAllMedia()
  }
  isFocused.value = false
  expiresInDays.value = 30
  error.value = null
  
  if (textareaRef.value) {
    textareaRef.value.style.height = 'auto'
  }
}

// Get user avatar URL
const getUserAvatar = () => {
  if (user.value?.avatar_url) {
    return user.value.avatar_url
  }
  if (user.value?.avatar_cloudflare_id) {
    return `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${user.value.avatar_cloudflare_id}/avatar`
  }
  if (user.value?.avatar) {
    return user.value.avatar
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(user.value?.name || 'User')}&background=3B82F6&color=fff`
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

onMounted(() => {
  // Focus textarea on mount if needed
})
</script>