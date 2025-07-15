<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-16 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">My Lists</h1>
          <button
            @click="showCreateModal = true"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Create New List
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Lists Grid -->
        <div v-else-if="lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="list in lists"
            :key="list.id"
            class="relative bg-white rounded-lg shadow hover:shadow-lg transition-shadow"
          >
            <!-- List Image -->
            <div class="h-48 bg-gray-200 rounded-t-lg overflow-hidden">
              <img
                v-if="list.featured_image_url"
                :src="list.featured_image_url"
                :alt="list.name"
                class="w-full h-full object-cover"
              />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
              </div>
            </div>

            <!-- List Content -->
            <div class="p-4">
              <!-- Absolute positioned link for ADA compliance -->
              <a 
                :href="`/${currentUser?.custom_url || currentUser?.username}/${list.slug}`"
                class="absolute inset-0 z-10"
                :aria-label="`View ${list.name}`"
                @click.prevent="navigateToList(list)"
              >
                <span class="sr-only">View {{ list.name }}</span>
              </a>
              
              <div class="flex justify-between items-start mb-2">
                <h3 class="text-lg font-semibold text-gray-900">{{ list.name }}</h3>
                <span
                  class="px-2 py-1 text-xs rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': list.visibility === 'public',
                    'bg-gray-100 text-gray-800': list.visibility === 'private',
                    'bg-yellow-100 text-yellow-800': list.is_draft
                  }"
                >
                  {{ list.is_draft ? 'Draft' : list.visibility }}
                </span>
              </div>
              
              <p v-if="list.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
                {{ list.description }}
              </p>

              <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                <span>{{ list.items_count }} items</span>
                <span>{{ list.view_count }} views</span>
              </div>

              <!-- Actions - positioned above the absolute link -->
              <div class="flex space-x-2 relative z-20">
                <a
                  :href="`/${currentUser?.custom_url || currentUser?.username}/${list.slug}`"
                  class="flex-1 text-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                  @click.prevent="navigateToList(list)"
                >
                  View
                </a>
                <button
                  @click="editList(list)"
                  class="flex-1 text-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                  Edit
                </button>
                <button
                  @click="deleteList(list)"
                  class="px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 hover:bg-red-50"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <h3 class="mt-2 text-sm font-medium text-gray-900">No lists yet</h3>
          <p class="mt-1 text-sm text-gray-500">Get started by creating a new list.</p>
          <div class="mt-6">
            <button
              @click="showCreateModal = true"
              class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Create New List
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Create List Modal (simplified) -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-6">
        <h2 class="text-lg font-medium mb-4">Create New List</h2>
        <form @submit.prevent="createList">
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">List Name</label>
            <input
              v-model="newList.name"
              type="text"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="newList.description"
              rows="3"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            ></textarea>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Visibility</label>
            <select
              v-model="newList.visibility"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="private">Private</option>
              <option value="public">Public</option>
            </select>
          </div>
          <div class="flex justify-end space-x-3">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
            >
              Create List
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const lists = ref([])
const showCreateModal = ref(false)
const currentUser = computed(() => authStore.user)

const newList = reactive({
  name: '',
  description: '',
  visibility: 'private',
  category_id: 1 // Default category - you may want to fetch categories and let user select
})

// Fetch user's lists
async function fetchLists() {
  try {
    const response = await axios.get('/api/lists')
    // Handle paginated response
    if (response.data && response.data.data) {
      lists.value = response.data.data
    } else if (Array.isArray(response.data)) {
      lists.value = response.data
    } else {
      lists.value = []
    }
  } catch (error) {
    console.error('Failed to fetch lists:', error)
    lists.value = []
  } finally {
    loading.value = false
  }
}

// Create new list
async function createList() {
  try {
    const response = await axios.post('/api/lists', newList)
    const createdList = response.data.list || response.data.data || response.data
    lists.value.unshift(createdList)
    
    // Reset form and close modal
    newList.name = ''
    newList.description = ''
    newList.visibility = 'private'
    showCreateModal.value = false
    
    // Navigate to the new list
    router.push({
      name: 'UserList',
      params: {
        username: currentUser.value?.custom_url || currentUser.value?.username,
        slug: createdList.slug
      }
    })
  } catch (error) {
    alert('Failed to create list. Please try again.')
  }
}

// Navigate to list
function navigateToList(list) {
  const username = currentUser.value?.custom_url || currentUser.value?.username
  console.log('Navigating to list:', username, list.slug)
  router.push({ 
    name: 'UserList', 
    params: { 
      username: username,
      slug: list.slug 
    } 
  })
}

// Edit list
function editList(list) {
  router.push({
    name: 'ListEdit',
    params: {
      id: list.id
    }
  })
}

// Delete list
async function deleteList(list) {
  if (!confirm(`Are you sure you want to delete "${list.name}"?`)) {
    return
  }
  
  try {
    await axios.delete(`/api/lists/${list.id}`)
    lists.value = lists.value.filter(l => l.id !== list.id)
  } catch (error) {
    alert('Failed to delete list. Please try again.')
  }
}

onMounted(() => {
  fetchLists()
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>