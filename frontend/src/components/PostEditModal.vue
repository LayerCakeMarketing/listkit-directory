<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="close"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50" />
        
        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div
            class="relative w-full max-w-2xl bg-white rounded-lg shadow-xl"
            @click.stop
          >
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b">
              <h3 class="text-lg font-semibold">Edit Post</h3>
              <button
                @click="close"
                class="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            
            <!-- Content -->
            <div class="p-4">
              <!-- Text Content -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Content
                </label>
                <textarea
                  v-model="editedContent"
                  @input="autoResize"
                  ref="textareaRef"
                  class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                  rows="3"
                  maxlength="500"
                  placeholder="What's on your mind?"
                />
                <div class="mt-1 text-sm text-gray-500 text-right">
                  {{ editedContent.length }} / 500
                </div>
              </div>
              
              <!-- Current Media -->
              <div v-if="currentMedia.length > 0" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Current Media
                </label>
                <div 
                  class="grid gap-2"
                  :class="{
                    'grid-cols-1': currentMedia.length === 1,
                    'grid-cols-2': currentMedia.length === 2,
                    'grid-cols-2 sm:grid-cols-3': currentMedia.length >= 3
                  }"
                >
                  <div
                    v-for="(item, index) in currentMedia"
                    :key="item.fileId || index"
                    class="relative group"
                  >
                    <img
                      v-if="post.media_type === 'images'"
                      :src="item.url"
                      :alt="`Image ${index + 1}`"
                      class="w-full h-32 object-cover rounded-lg"
                    />
                    <video
                      v-else
                      :src="item.url"
                      class="w-full h-32 object-cover rounded-lg"
                    />
                    
                    <!-- Remove button -->
                    <button
                      @click="removeExistingMedia(index)"
                      class="absolute top-2 right-2 p-1 bg-red-600 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                      title="Remove media"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
                
                <p v-if="removedMedia.length > 0" class="mt-2 text-sm text-red-600">
                  {{ removedMedia.length }} item(s) will be removed when you save
                </p>
              </div>
              
              <!-- Add New Media -->
              <div v-if="canAddMedia" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Add New Media
                </label>
                <PostMediaUploader
                  ref="mediaUploader"
                  :max-images="maxNewImages"
                  @media-uploaded="handleNewMedia"
                  @error="handleMediaError"
                />
              </div>
              
              <!-- Error Message -->
              <div v-if="error" class="mb-4 p-3 bg-red-50 text-red-800 text-sm rounded-lg">
                {{ error }}
              </div>
            </div>
            
            <!-- Footer -->
            <div class="flex items-center justify-between p-4 border-t bg-gray-50">
              <button
                @click="handleDelete"
                class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                :disabled="isSubmitting"
              >
                Delete Post
              </button>
              
              <div class="flex items-center space-x-3">
                <button
                  @click="close"
                  class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                  :disabled="isSubmitting"
                >
                  Cancel
                </button>
                
                <button
                  @click="handleSave"
                  class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition-colors flex items-center space-x-2"
                  :disabled="!canSave || isSubmitting"
                >
                  <span>Save Changes</span>
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
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import PostMediaUploader from '@/components/PostMediaUploader.vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  post: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:modelValue', 'updated', 'deleted'])

// Refs
const textareaRef = ref(null)
const mediaUploader = ref(null)

// State
const editedContent = ref('')
const currentMedia = ref([])
const removedMedia = ref([])
const newMedia = ref(null)
const isSubmitting = ref(false)
const error = ref('')

// Computed
const hasChanges = computed(() => {
  return editedContent.value !== props.post.content ||
         removedMedia.value.length > 0 ||
         newMedia.value !== null
})

const canSave = computed(() => {
  return editedContent.value.trim().length > 0 && 
         editedContent.value.length <= 500 && 
         !isSubmitting.value &&
         hasChanges.value
})

const canAddMedia = computed(() => {
  // Allow adding media if we have room (max 4 total)
  const totalMedia = currentMedia.value.length + (newMedia.value?.images?.length || 0) + (newMedia.value?.video ? 1 : 0)
  return totalMedia < 4
})

const maxNewImages = computed(() => {
  // Calculate how many more images can be added
  return Math.max(0, 4 - currentMedia.value.length)
})

// Methods
const close = () => {
  if (!isSubmitting.value) {
    emit('update:modelValue', false)
  }
}

const autoResize = () => {
  nextTick(() => {
    if (textareaRef.value) {
      textareaRef.value.style.height = 'auto'
      textareaRef.value.style.height = textareaRef.value.scrollHeight + 'px'
    }
  })
}

const removeExistingMedia = (index) => {
  const removed = currentMedia.value.splice(index, 1)[0]
  removedMedia.value.push(removed)
}

const handleNewMedia = (mediaData) => {
  newMedia.value = mediaData
}

const handleMediaError = (errorMessage) => {
  error.value = errorMessage
}

const handleSave = async () => {
  if (!canSave.value) return
  
  isSubmitting.value = true
  error.value = ''
  
  try {
    const updateData = {
      content: editedContent.value.trim()
    }
    
    // Add removed media IDs for cleanup
    if (removedMedia.value.length > 0) {
      updateData.removed_media = removedMedia.value.map(m => ({
        fileId: m.fileId,
        url: m.url
      }))
    }
    
    // Add new media if any
    if (newMedia.value) {
      if (newMedia.value.type === 'images' && newMedia.value.images.length > 0) {
        updateData.media = newMedia.value.images
        updateData.media_type = 'images'
      } else if (newMedia.value.type === 'video' && newMedia.value.video) {
        updateData.media = [newMedia.value.video]
        updateData.media_type = 'video'
      }
    }
    
    const response = await axios.put(`/api/posts/${props.post.id}`, updateData)
    
    emit('updated', response.data.post)
    emit('update:modelValue', false)
    
  } catch (err) {
    console.error('Error updating post:', err)
    error.value = err.response?.data?.message || 'Failed to update post. Please try again.'
  } finally {
    isSubmitting.value = false
  }
}

const handleDelete = async () => {
  if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
    return
  }
  
  isSubmitting.value = true
  error.value = ''
  
  try {
    await axios.delete(`/api/posts/${props.post.id}`)
    
    emit('deleted')
    emit('update:modelValue', false)
    
  } catch (err) {
    console.error('Error deleting post:', err)
    error.value = err.response?.data?.message || 'Failed to delete post. Please try again.'
    isSubmitting.value = false
  }
}

// Initialize data when post changes
watch(() => props.post, (newPost) => {
  if (newPost) {
    editedContent.value = newPost.content || ''
    currentMedia.value = [...(newPost.media_items || [])]
    removedMedia.value = []
    newMedia.value = null
    error.value = ''
    
    nextTick(() => {
      autoResize()
    })
  }
}, { immediate: true })

// Reset when modal closes
watch(() => props.modelValue, (isOpen) => {
  if (!isOpen) {
    setTimeout(() => {
      editedContent.value = ''
      currentMedia.value = []
      removedMedia.value = []
      newMedia.value = null
      error.value = ''
      if (mediaUploader.value) {
        mediaUploader.value.clearAllMedia()
      }
    }, 200)
  }
})
</script>