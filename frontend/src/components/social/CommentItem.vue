<template>
  <div class="flex gap-3">
    <!-- Avatar -->
    <router-link 
      :to="`/@${comment.user.username}`"
      class="flex-shrink-0"
    >
      <img
        :src="userAvatar"
        :alt="comment.user.name"
        class="w-10 h-10 rounded-full object-cover"
      >
    </router-link>

    <!-- Comment Content -->
    <div class="flex-1 min-w-0">
      <!-- Header -->
      <div class="flex items-start justify-between gap-2">
        <div>
          <router-link 
            :to="`/@${comment.user.username}`"
            class="font-medium text-gray-900 hover:underline"
          >
            {{ comment.user.firstname }} {{ comment.user.lastname }}
          </router-link>
          <div class="flex items-center gap-2 text-xs text-gray-500">
            <span>@{{ comment.user.username }}</span>
            <span>·</span>
            <time :datetime="comment.created_at" :title="formatDate(comment.created_at)">
              {{ timeAgo(comment.created_at) }}
            </time>
            <span v-if="comment.updated_at !== comment.created_at" class="text-gray-400">
              (edited)
            </span>
          </div>
        </div>

        <!-- Actions Menu -->
        <div v-if="canEdit || canDelete" class="relative">
          <button
            @click="showMenu = !showMenu"
            class="p-1 text-gray-400 hover:text-gray-600 rounded"
          >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
            </svg>
          </button>

          <div
            v-if="showMenu"
            @click.away="showMenu = false"
            class="absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10"
          >
            <button
              v-if="canEdit"
              @click="startEdit"
              class="w-full px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-50"
            >
              Edit
            </button>
            <button
              v-if="canDelete"
              @click="handleDelete"
              class="w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50"
            >
              Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div v-if="!editing" class="mt-1">
        <p class="text-gray-900 whitespace-pre-wrap break-words" v-html="formattedContent"></p>
      </div>

      <!-- Edit Form -->
      <div v-else class="mt-2">
        <CommentForm
          :type="type"
          :id="parentId"
          :initial-content="comment.content"
          placeholder="Edit your comment..."
          @submitted="handleEditSubmit"
          @cancel="cancelEdit"
        />
      </div>

      <!-- Actions -->
      <div v-if="!editing" class="mt-2 flex items-center gap-4">
        <LikeButton
          type="comment"
          :id="comment.id"
          :initial-liked="comment.is_liked"
          :initial-count="comment.likes_count"
          variant="minimal"
          show-count
        />
        
        <button
          @click="handleReply"
          class="text-sm text-gray-600 hover:text-gray-800 font-medium"
        >
          Reply
        </button>

        <span v-if="comment.replies_count > 0" class="text-sm text-gray-500">
          {{ comment.replies_count }} {{ comment.replies_count === 1 ? 'reply' : 'replies' }}
        </span>
      </div>

      <!-- Reply Form -->
      <div v-if="replying" class="mt-3">
        <CommentForm
          :type="type"
          :id="parentId"
          :parent-id="comment.id"
          :placeholder="`Reply to @${comment.user.username}...`"
          @submitted="handleReplySubmit"
          @cancel="replying = false"
        />
      </div>

      <!-- Replies -->
      <div v-if="comment.replies && comment.replies.length > 0" class="mt-3 space-y-3 pl-4 border-l-2 border-gray-100">
        <CommentItem
          v-for="reply in comment.replies"
          :key="reply.id"
          :comment="reply"
          :type="type"
          :parent-id="parentId"
          @reply="$emit('reply', $event)"
          @deleted="$emit('deleted', $event)"
          @updated="$emit('updated', $event)"
        />
      </div>

      <!-- Load More Replies -->
      <div v-if="hasMoreReplies" class="mt-2">
        <button
          @click="loadMoreReplies"
          :disabled="loadingReplies"
          class="text-sm text-blue-600 hover:text-blue-700 font-medium disabled:opacity-50"
        >
          {{ loadingReplies ? 'Loading...' : `View ${remainingReplies} more ${remainingReplies === 1 ? 'reply' : 'replies'}` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useComments } from '@/composables/useComments'
import { formatDistanceToNow, format } from 'date-fns'
import LikeButton from './LikeButton.vue'
import CommentForm from './CommentForm.vue'

const props = defineProps({
  comment: {
    type: Object,
    required: true
  },
  type: {
    type: String,
    required: true
  },
  parentId: {
    type: [Number, String],
    required: true
  }
})

const emit = defineEmits(['reply', 'deleted', 'updated'])

const authStore = useAuthStore()
const { updateComment, deleteComment, getReplies } = useComments()

const showMenu = ref(false)
const editing = ref(false)
const replying = ref(false)
const loadingReplies = ref(false)

const canEdit = computed(() => {
  return authStore.user && authStore.user.id === props.comment.user_id
})

const canDelete = computed(() => {
  if (!authStore.user) return false
  
  // Comment owner can delete
  if (authStore.user.id === props.comment.user_id) return true
  
  // Admins/managers can delete
  if (['admin', 'manager'].includes(authStore.user.role)) return true
  
  return false
})

const userAvatar = computed(() => {
  if (props.comment.user.avatar_cloudflare_id) {
    return `https://imagedelivery.net/${import.meta.env.VITE_CLOUDFLARE_ACCOUNT_HASH}/${props.comment.user.avatar_cloudflare_id}/avatar`
  }
  return '/images/default-avatar.png'
})

const hasMoreReplies = computed(() => {
  return props.comment.replies_count > (props.comment.replies?.length || 0)
})

const remainingReplies = computed(() => {
  return props.comment.replies_count - (props.comment.replies?.length || 0)
})

const formattedContent = computed(() => {
  // Convert @mentions to links
  return props.comment.content.replace(
    /@(\w+)/g,
    '<a href="/@$1" class="text-blue-600 hover:underline">@$1</a>'
  )
})

const timeAgo = (date) => {
  return formatDistanceToNow(new Date(date), { addSuffix: true })
}

const formatDate = (date) => {
  return format(new Date(date), 'PPpp')
}

const startEdit = () => {
  editing.value = true
  showMenu.value = false
}

const cancelEdit = () => {
  editing.value = false
}

const handleEditSubmit = async (updatedComment) => {
  editing.value = false
  emit('updated', updatedComment)
}

const handleDelete = async () => {
  if (!confirm('Are you sure you want to delete this comment?')) return
  
  showMenu.value = false
  
  try {
    await deleteComment(props.comment.id)
    emit('deleted', props.comment.id)
  } catch (error) {
    console.error('Failed to delete comment:', error)
    alert('Failed to delete comment. Please try again.')
  }
}

const handleReply = () => {
  replying.value = true
  emit('reply', props.comment.id)
}

const handleReplySubmit = (reply) => {
  replying.value = false
  
  // Add reply to comment
  if (!props.comment.replies) {
    props.comment.replies = []
  }
  props.comment.replies.unshift(reply)
  props.comment.replies_count = (props.comment.replies_count || 0) + 1
}

const loadMoreReplies = async () => {
  loadingReplies.value = true
  
  try {
    const response = await getReplies(props.comment.id)
    
    // Replace replies with full list
    props.comment.replies = response.data
  } catch (error) {
    console.error('Failed to load replies:', error)
  } finally {
    loadingReplies.value = false
  }
}
</script>