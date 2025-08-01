<template>
  <div class="flex items-center gap-1">
    <LikeButton
      :type="type"
      :id="id"
      :initial-liked="liked"
      :initial-count="likesCount"
      :variant="variant"
      :show-count="showCounts"
      @update:count="$emit('update:likes-count', $event)"
    />

    <button
      v-if="showComments"
      @click="$emit('toggle-comments')"
      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200"
      :class="commentButtonClasses"
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
          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
        ></path>
      </svg>
      <span v-if="showCounts && commentsCount > 0" class="font-semibold">
        {{ formatCount(commentsCount) }}
      </span>
    </button>

    <RepostButton
      v-if="showReposts && canRepost"
      :type="type"
      :id="id"
      :initial-reposted="reposted"
      :initial-count="repostsCount"
      :variant="variant"
      :show-count="showCounts"
      @update:count="$emit('update:reposts-count', $event)"
    />

    <button
      v-if="showShare"
      @click="handleShare"
      class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg transition-all duration-200"
      :class="shareButtonClasses"
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
          d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.632 4.316C18.114 15.938 18 15.482 18 15c0-.482.114-.938.316-1.342m0 2.684a3 3 0 110-2.684M5.684 15.342C5.886 14.938 6 14.482 6 14c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684"
        ></path>
      </svg>
    </button>

    <slot name="append"></slot>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import LikeButton from './LikeButton.vue'
import RepostButton from './RepostButton.vue'

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
  liked: {
    type: Boolean,
    default: false
  },
  likesCount: {
    type: Number,
    default: 0
  },
  commentsCount: {
    type: Number,
    default: 0
  },
  reposted: {
    type: Boolean,
    default: false
  },
  repostsCount: {
    type: Number,
    default: 0
  },
  showCounts: {
    type: Boolean,
    default: true
  },
  showComments: {
    type: Boolean,
    default: true
  },
  showReposts: {
    type: Boolean,
    default: true
  },
  showShare: {
    type: Boolean,
    default: true
  },
  variant: {
    type: String,
    default: 'minimal'
  },
  shareUrl: {
    type: String,
    default: null
  }
})

const emit = defineEmits([
  'toggle-comments',
  'update:likes-count',
  'update:reposts-count',
  'shared'
])

const canRepost = computed(() => {
  return ['post', 'list'].includes(props.type)
})

const commentButtonClasses = computed(() => {
  const classes = {
    default: 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
    minimal: 'text-gray-600 hover:text-gray-800',
    filled: 'bg-gray-200 text-gray-700 hover:bg-gray-300'
  }
  return classes[props.variant]
})

const shareButtonClasses = computed(() => {
  const classes = {
    default: 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300',
    minimal: 'text-gray-600 hover:text-gray-800',
    filled: 'bg-gray-200 text-gray-700 hover:bg-gray-300'
  }
  return classes[props.variant]
})

const formatCount = (count) => {
  if (count >= 1000000) {
    return (count / 1000000).toFixed(1) + 'M'
  } else if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'K'
  }
  return count.toString()
}

const handleShare = async () => {
  const url = props.shareUrl || window.location.href
  
  if (navigator.share) {
    try {
      await navigator.share({
        title: document.title,
        url: url
      })
      emit('shared')
    } catch (err) {
      if (err.name !== 'AbortError') {
        console.error('Share failed:', err)
        copyToClipboard(url)
      }
    }
  } else {
    copyToClipboard(url)
  }
}

const copyToClipboard = (text) => {
  const textarea = document.createElement('textarea')
  textarea.value = text
  textarea.style.position = 'fixed'
  textarea.style.opacity = '0'
  document.body.appendChild(textarea)
  textarea.select()
  document.execCommand('copy')
  document.body.removeChild(textarea)
  
  alert('Link copied to clipboard!')
  emit('shared')
}
</script>