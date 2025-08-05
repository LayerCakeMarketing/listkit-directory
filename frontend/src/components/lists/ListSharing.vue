<template>
  <div class="border-t pt-4">
    <h3 class="text-lg font-semibold mb-4">Share List</h3>
    <!-- Add New Share -->
    <div class="space-y-4 mb-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Share with user</label>
        <input
          v-model="shareSearch"
          @input="debouncedSearchUsers"
          type="text"
          placeholder="Search by name, username, or email..."
          class="w-full rounded-md border-gray-300"
        />
        <div v-if="userSearchResults.length > 0" class="mt-2 max-h-40 overflow-y-auto border rounded">
          <button
            v-for="user in userSearchResults"
            :key="user.id"
            @click="selectUserToShare(user)"
            class="w-full text-left p-2 hover:bg-gray-100 border-b last:border-b-0 flex items-center space-x-2"
          >
            <div>
              <div class="font-medium">{{ user.name }}</div>
              <div class="text-sm text-gray-500">@{{ user.username || user.email }}</div>
            </div>
          </button>
        </div>
      </div>
      <div v-if="selectedUser" class="p-3 bg-gray-50 rounded border">
        <div class="flex items-center justify-between mb-3">
          <div>
            <div class="font-medium">{{ selectedUser.name }}</div>
            <div class="text-sm text-gray-500">@{{ selectedUser.username || selectedUser.email }}</div>
          </div>
          <button @click="clearSelectedUser" class="text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
          </button>
        </div>
        <div class="space-y-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Permission</label>
            <select v-model="shareForm.permission" class="w-full rounded-md border-gray-300">
              <option value="view">View only</option>
              <option value="edit">Can edit</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Expires (optional)</label>
            <input
              v-model="shareForm.expires_at"
              type="datetime-local"
              class="w-full rounded-md border-gray-300"
              :min="new Date().toISOString().slice(0, 16)"
            />
          </div>
          <button
            @click="shareList"
            :disabled="sharing"
            class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
          >
            {{ sharing ? 'Sharing...' : 'Share List' }}
          </button>
        </div>
      </div>
    </div>
    <!-- Current Shares -->
    <div v-if="shares.length > 0">
      <h4 class="font-medium text-gray-900 mb-3">Current Shares</h4>
      <div class="space-y-2">
        <div
          v-for="share in shares"
          :key="share.id"
          class="flex items-center justify-between p-3 bg-gray-50 rounded border"
        >
          <div>
            <div class="font-medium">{{ share.user.name }}</div>
            <div class="text-sm text-gray-500">
              {{ share.permission }} access
              <span v-if="share.expires_at"> • Expires {{ formatDate(share.expires_at) }}</span>
            </div>
          </div>
          <div class="flex items-center space-x-2">
            <button
              @click="editShare(share)"
              class="text-blue-600 hover:text-blue-800 text-sm"
            >
              Edit
            </button>
            <button
              @click="removeShare(share)"
              class="text-red-600 hover:text-red-800 text-sm"
            >
              Remove
            </button>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="text-sm text-gray-500 text-center py-4">
      No one has access to this private list yet.
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  listId: [String, Number],
  shares: Array
})

const emit = defineEmits(['share-added', 'share-removed'])

const { success, error } = useToast()

const shareSearch = ref('')
const userSearchResults = ref([])
const selectedUser = ref(null)
const sharing = ref(false)

const shareForm = reactive({
  user_id: null,
  permission: 'view',
  expires_at: ''
})

const searchUsers = async () => {
  if (!shareSearch.value || shareSearch.value.length < 2) {
    userSearchResults.value = []
    return
  }
  
  try {
    const response = await axios.get('/api/users/search', {
      params: { q: shareSearch.value }
    })
    userSearchResults.value = response.data
  } catch (err) {
    console.error('Error searching users:', err)
  }
}

const debouncedSearchUsers = debounce(searchUsers, 300)

const selectUserToShare = (user) => {
  selectedUser.value = user
  shareForm.user_id = user.id
  shareSearch.value = ''
  userSearchResults.value = []
}

const clearSelectedUser = () => {
  selectedUser.value = null
  shareForm.user_id = null
  shareForm.permission = 'view'
  shareForm.expires_at = ''
}

const shareList = async () => {
  if (!selectedUser.value) return
  
  sharing.value = true
  try {
    const response = await axios.post(`/api/lists/${props.listId}/shares`, shareForm)
    emit('share-added', response.data.share)
    clearSelectedUser()
    success('Shared', 'List shared successfully!')
  } catch (err) {
    error('Error', err.response?.data?.error || err.message || 'Failed to share list')
  } finally {
    sharing.value = false
  }
}

const editShare = (share) => {
  if (confirm(`Edit sharing permissions for ${share.user.name}?\n\nCurrent: ${share.permission} access`)) {
    removeShare(share)
  }
}

const removeShare = async (share) => {
  if (!confirm(`Remove ${share.user.name}'s access to this list?`)) return
  
  try {
    await axios.delete(`/api/lists/${props.listId}/shares/${share.id}`)
    emit('share-removed', share.id)
  } catch (err) {
    error('Error', err.response?.data?.message || err.message || 'Failed to remove share')
  }
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleString()
}
</script>