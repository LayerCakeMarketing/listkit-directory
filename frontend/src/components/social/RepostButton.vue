<template>
  <div class="relative">
    <button
      @click="handleClick"
      :disabled="loading"
      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200"
      :class="buttonClasses"
    >
      <svg
        class="w-4 h-4"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
        ></path>
      </svg>
      <span v-if="showCount && repostsCount > 0" class="font-semibold">
        {{ formatCount(repostsCount) }}
      </span>
      <span v-else-if="showLabel" class="font-medium">
        {{ reposted ? 'Reposted' : 'Repost' }}
      </span>
    </button>

    <!-- Repost Modal -->
    <Teleport to="body">
      <div
        v-if="showModal"
        @click="closeModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
      >
        <div
          @click.stop
          class="bg-white rounded-lg shadow-xl max-w-md w-full p-6"
        >
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Repost this {{ type }}?
          </h3>

          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Add a comment (optional)
              </label>
              <textarea
                v-model="repostComment"
                placeholder="What are your thoughts?"
                rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              ></textarea>
            </div>

            <div class="flex gap-3 justify-end">
              <button
                @click="closeModal"
                class="px-4 py-2 text-gray-700 font-medium hover:text-gray-900"
              >
                Cancel
              </button>
              <button
                @click="confirmRepost"
                :disabled="loading"
                class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {{ loading ? 'Reposting...' : 'Repost' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useReposts } from '@/composables/useReposts'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['post', 'list'].includes(value)
  },
  id: {
    type: [Number, String],
    required: true
  },
  initialReposted: {
    type: Boolean,
    default: false
  },
  initialCount: {
    type: Number,
    default: 0
  },
  showCount: {
    type: Boolean,
    default: true
  },
  showLabel: {
    type: Boolean,
    default: false
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'minimal', 'filled'].includes(value)
  }
})

const emit = defineEmits(['reposted', 'unreposted', 'update:count'])

const { createRepost, removeRepost, checkRepost } = useReposts()

const reposted = ref(props.initialReposted)
const repostsCount = ref(props.initialCount)
const loading = ref(false)
const showModal = ref(false)
const repostComment = ref('')

const buttonClasses = computed(() => {
  const baseClasses = {
    default: reposted.value
      ? 'bg-green-50 text-green-600 hover:bg-green-100 border border-green-200'
      : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
    minimal: reposted.value
      ? 'text-green-600 hover:text-green-700'
      : 'text-gray-600 hover:text-gray-800',
    filled: reposted.value
      ? 'bg-green-600 text-white hover:bg-green-700'
      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
  }

  return [
    baseClasses[props.variant],
    loading.value && 'opacity-50 cursor-not-allowed'
  ]
})

const handleClick = () => {
  if (loading.value) return
  
  if (reposted.value) {
    // Remove repost
    handleUnrepost()
  } else {
    // Show modal for new repost
    showModal.value = true
  }
}

const confirmRepost = async () => {
  loading.value = true
  
  try {
    const result = await createRepost(props.type, props.id, repostComment.value || null)
    
    reposted.value = true
    repostsCount.value = result.reposts_count
    
    emit('reposted', result.repost)
    emit('update:count', repostsCount.value)
    
    closeModal()
  } catch (error) {
    console.error('Failed to repost:', error)
    alert(error.response?.data?.message || 'Failed to repost')
  } finally {
    loading.value = false
  }
}

const handleUnrepost = async () => {
  if (!confirm('Remove this repost?')) return
  
  loading.value = true
  
  try {
    const result = await removeRepost(props.type, props.id)
    
    reposted.value = false
    repostsCount.value = result.reposts_count
    
    emit('unreposted')
    emit('update:count', repostsCount.value)
  } catch (error) {
    console.error('Failed to remove repost:', error)
  } finally {
    loading.value = false
  }
}

const closeModal = () => {
  showModal.value = false
  repostComment.value = ''
}

const formatCount = (count) => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1) + 'M'
  } else if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}

// Check repost status on mount if not provided
onMounted(async () => {
  if (props.initialReposted === false && props.initialCount === 0) {
    try {
      const result = await checkRepost(props.type, props.id)
      reposted.value = result.reposted
      repostsCount.value = result.reposts_count
    } catch (error) {
      console.error('Failed to check repost status:', error)
    }
  }
})
</script>