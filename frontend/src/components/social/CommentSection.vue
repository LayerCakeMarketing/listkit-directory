<template>
  <div class="space-y-4">
    <!-- Comment Form -->
    <div v-if="showForm" class="bg-gray-50 rounded-lg p-4">
      <h3 class="text-sm font-medium text-gray-900 mb-3">Add a comment</h3>
      <CommentForm
        :type="type"
        :id="id"
        :parent-id="replyingTo"
        @submitted="handleCommentSubmitted"
        @cancel="replyingTo = null"
      />
    </div>

    <!-- Comments List -->
    <div class="space-y-4">
      <div v-if="loading && comments.length === 0" class="text-center py-8">
        <div class="inline-flex items-center gap-2 text-gray-500">
          <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Loading comments...
        </div>
      </div>

      <div v-else-if="comments.length === 0" class="text-center py-8 text-gray-500">
        <p>No comments yet. Be the first to comment!</p>
      </div>

      <div v-else class="space-y-4">
        <CommentItem
          v-for="comment in comments"
          :key="comment.id"
          :comment="comment"
          :type="type"
          :parent-id="id"
          @reply="handleReply"
          @deleted="handleCommentDeleted"
          @updated="handleCommentUpdated"
        />
      </div>

      <!-- Load More -->
      <div v-if="hasMore" class="text-center pt-4">
        <button
          @click="loadMore"
          :disabled="loading"
          class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 disabled:opacity-50"
        >
          {{ loading ? 'Loading...' : 'Load more comments' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useComments } from '@/composables/useComments'
import CommentForm from './CommentForm.vue'
import CommentItem from './CommentItem.vue'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['post', 'list', 'place'].includes(value)
  },
  id: {
    type: [Number, String],
    required: true
  },
  showForm: {
    type: Boolean,
    default: true
  },
  perPage: {
    type: Number,
    default: 20
  }
})

const emit = defineEmits(['comment-added', 'comment-deleted'])

const { getComments, loading } = useComments()

const comments = ref([])
const currentPage = ref(1)
const hasMore = ref(false)
const replyingTo = ref(null)

const loadComments = async (page = 1) => {
  try {
    const response = await getComments(props.type, props.id, page, props.perPage)
    
    if (page === 1) {
      comments.value = response.data
    } else {
      comments.value.push(...response.data)
    }
    
    currentPage.value = response.current_page
    hasMore.value = response.current_page < response.last_page
  } catch (error) {
    console.error('Failed to load comments:', error)
  }
}

const loadMore = () => {
  loadComments(currentPage.value + 1)
}

const handleCommentSubmitted = (comment) => {
  if (comment.parent_id) {
    // Find parent comment and add reply
    const parent = findComment(comments.value, comment.parent_id)
    if (parent) {
      if (!parent.replies) parent.replies = []
      parent.replies.unshift(comment)
      parent.replies_count = (parent.replies_count || 0) + 1
    }
  } else {
    // Add to root comments
    comments.value.unshift(comment)
  }
  
  replyingTo.value = null
  emit('comment-added', comment)
}

const handleCommentDeleted = (commentId) => {
  // Remove from comments array
  const removeComment = (list, id) => {
    const index = list.findIndex(c => c.id === id)
    if (index > -1) {
      list.splice(index, 1)
      return true
    }
    
    // Check replies
    for (const comment of list) {
      if (comment.replies && removeComment(comment.replies, id)) {
        comment.replies_count = Math.max(0, (comment.replies_count || 0) - 1)
        return true
      }
    }
    return false
  }
  
  removeComment(comments.value, commentId)
  emit('comment-deleted', commentId)
}

const handleCommentUpdated = (updatedComment) => {
  const updateComment = (list, updated) => {
    const index = list.findIndex(c => c.id === updated.id)
    if (index > -1) {
      list[index] = { ...list[index], ...updated }
      return true
    }
    
    // Check replies
    for (const comment of list) {
      if (comment.replies && updateComment(comment.replies, updated)) {
        return true
      }
    }
    return false
  }
  
  updateComment(comments.value, updatedComment)
}

const handleReply = (commentId) => {
  replyingTo.value = commentId
}

const findComment = (list, id) => {
  for (const comment of list) {
    if (comment.id === id) return comment
    if (comment.replies) {
      const found = findComment(comment.replies, id)
      if (found) return found
    }
  }
  return null
}

onMounted(() => {
  loadComments()
})
</script>