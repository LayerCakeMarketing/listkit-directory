<template>
  <form @submit.prevent="handleSubmit" class="space-y-3">
    <div>
      <textarea
        v-model="content"
        @input="handleInput"
        :placeholder="placeholder"
        :disabled="submitting"
        rows="3"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none disabled:opacity-50"
        :class="{ 'border-red-300': error }"
      ></textarea>
      <div v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</div>
      <div v-if="content.length > 0" class="mt-1 text-xs text-gray-500 text-right">
        {{ content.length }} / 1000
      </div>
    </div>

    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <button
          type="submit"
          :disabled="!canSubmit"
          class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          {{ submitting ? 'Posting...' : 'Post Comment' }}
        </button>
        <button
          v-if="parentId"
          type="button"
          @click="handleCancel"
          class="px-4 py-2 text-gray-600 text-sm font-medium hover:text-gray-800 transition-colors"
        >
          Cancel
        </button>
      </div>

      <div v-if="mentions.length > 0" class="text-xs text-gray-500">
        Mentioning: {{ mentions.join(', ') }}
      </div>
    </div>
  </form>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useComments } from '@/composables/useComments'

const props = defineProps({
  type: {
    type: String,
    required: true
  },
  id: {
    type: [Number, String],
    required: true
  },
  parentId: {
    type: [Number, String],
    default: null
  },
  initialContent: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Write a comment...'
  }
})

const emit = defineEmits(['submitted', 'cancel'])

const { addComment } = useComments()

const content = ref(props.initialContent)
const submitting = ref(false)
const error = ref(null)

const mentions = computed(() => {
  const matches = content.value.match(/@(\w+)/g)
  return matches ? matches.map(m => m.substring(1)) : []
})

const canSubmit = computed(() => {
  return content.value.trim().length > 0 && 
         content.value.length <= 1000 && 
         !submitting.value
})

const handleInput = () => {
  error.value = null
  
  if (content.value.length > 1000) {
    error.value = 'Comment cannot exceed 1000 characters'
  }
}

const handleSubmit = async () => {
  if (!canSubmit.value) return
  
  submitting.value = true
  error.value = null
  
  try {
    const comment = await addComment(
      props.type, 
      props.id, 
      content.value.trim(),
      props.parentId
    )
    
    content.value = ''
    emit('submitted', comment)
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to post comment'
  } finally {
    submitting.value = false
  }
}

const handleCancel = () => {
  content.value = ''
  error.value = null
  emit('cancel')
}
</script>