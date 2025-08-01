<template>
  <button
    @click="handleLike"
    :disabled="loading"
    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200"
    :class="buttonClasses"
  >
    <svg
      class="w-4 h-4 transition-transform"
      :class="{ 'scale-110': liked && !loading }"
      :fill="liked ? 'currentColor' : 'none'"
      stroke="currentColor"
      viewBox="0 0 24 24"
      xmlns="http://www.w3.org/2000/svg"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
      ></path>
    </svg>
    <span v-if="showCount && likesCount > 0" class="font-semibold">
      {{ formatCount(likesCount) }}
    </span>
    <span v-else-if="showLabel" class="font-medium">
      {{ liked ? 'Liked' : 'Like' }}
    </span>
  </button>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useLikes } from '@/composables/useLikes'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['post', 'list', 'place', 'comment'].includes(value)
  },
  id: {
    type: [Number, String],
    required: true
  },
  initialLiked: {
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

const emit = defineEmits(['liked', 'unliked', 'update:count'])

const { toggleLike, checkLike } = useLikes()

const liked = ref(props.initialLiked)
const likesCount = ref(props.initialCount)
const loading = ref(false)

const buttonClasses = computed(() => {
  const baseClasses = {
    default: liked.value
      ? 'bg-red-50 text-red-600 hover:bg-red-100 border border-red-200'
      : 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
    minimal: liked.value
      ? 'text-red-600 hover:text-red-700'
      : 'text-gray-600 hover:text-gray-800',
    filled: liked.value
      ? 'bg-red-600 text-white hover:bg-red-700'
      : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
  }

  return [
    baseClasses[props.variant],
    loading.value && 'opacity-50 cursor-not-allowed'
  ]
})

const handleLike = async () => {
  if (loading.value) return

  loading.value = true
  const previousLiked = liked.value
  const previousCount = likesCount.value

  // Optimistic update
  liked.value = !liked.value
  likesCount.value = liked.value ? likesCount.value + 1 : likesCount.value - 1

  try {
    const result = await toggleLike(props.type, props.id)
    
    // Update with server response
    liked.value = result.liked
    likesCount.value = result.likes_count

    // Emit events
    emit(result.liked ? 'liked' : 'unliked')
    emit('update:count', likesCount.value)
  } catch (error) {
    // Revert on error
    liked.value = previousLiked
    likesCount.value = previousCount
    console.error('Failed to toggle like:', error)
  } finally {
    loading.value = false
  }
}

const formatCount = (count) => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1) + 'M'
  } else if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}

// Check like status on mount if not provided
onMounted(async () => {
  if (props.initialLiked === false && props.initialCount === 0) {
    try {
      const result = await checkLike(props.type, props.id)
      liked.value = result.liked
      likesCount.value = result.likes_count
    } catch (error) {
      console.error('Failed to check like status:', error)
    }
  }
})
</script>