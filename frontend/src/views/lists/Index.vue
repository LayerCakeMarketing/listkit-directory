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
            class="relative bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
            @click="navigateToList(list)"
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
              <div class="flex justify-between items-start mb-2">
                <h3 class="text-lg font-semibold text-gray-900">{{ list.name }}</h3>
                <div class="flex items-center space-x-2">
                  <span
                    v-if="list.status === 'on_hold'"
                    class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800"
                  >
                    On Hold
                  </span>
                  <span
                    v-else
                    class="px-2 py-1 text-xs rounded-full"
                    :class="{
                      'bg-green-100 text-green-800': list.visibility === 'public',
                      'bg-gray-100 text-gray-800': list.visibility === 'private',
                      'bg-yellow-100 text-yellow-800': list.is_draft
                    }"
                  >
                    {{ list.is_draft ? 'Draft' : list.visibility }}
                  </span>
                  
                  <!-- Dropdown Menu -->
                  <Menu as="div" class="relative inline-block text-left" @click.stop>
                    <div>
                      <MenuButton class="flex items-center rounded-full bg-gray-100 p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-gray-100">
                        <span class="sr-only">Open options</span>
                        <EllipsisVerticalIcon class="h-5 w-5" aria-hidden="true" />
                      </MenuButton>
                    </div>

                    <transition 
                      enter-active-class="transition ease-out duration-100" 
                      enter-from-class="transform opacity-0 scale-95" 
                      enter-to-class="transform opacity-100 scale-100" 
                      leave-active-class="transition ease-in duration-75" 
                      leave-from-class="transform opacity-100 scale-100" 
                      leave-to-class="transform opacity-0 scale-95"
                    >
                      <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none">
                        <div class="py-1">
                          <MenuItem v-slot="{ active }">
                            <button
                              @click.stop="editList(list)"
                              :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block w-full text-left px-4 py-2 text-sm']"
                            >
                              Edit
                            </button>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click.stop="deleteList(list)"
                              :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block w-full text-left px-4 py-2 text-sm text-red-600']"
                            >
                              Delete
                            </button>
                          </MenuItem>
                        </div>
                      </MenuItems>
                    </transition>
                  </Menu>
                </div>
              </div>
              
              <p v-if="list.description" class="text-sm text-gray-600 mb-3 line-clamp-2">
                {{ list.description }}
              </p>

              <div class="flex items-center justify-between text-sm text-gray-500">
                <span>{{ list.items_count }} items</span>
                <span>{{ list.view_count }} views</span>
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
    <div v-if="showCreateModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
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
          <div class="mb-4">
            <TagInput
              v-model="newList.tags"
              label="Tags"
              placeholder="Add tags..."
              :max-tags="10"
            />
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
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { EllipsisVerticalIcon } from '@heroicons/vue/20/solid'
import TagInput from '@/components/TagInput.vue'

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
  category_id: 1, // Default category - you may want to fetch categories and let user select
  tags: []
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
    newList.tags = []
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
  // Check if this is a channel list or user list
  if (list.channel_id && list.channel) {
    // Navigate to channel list
    router.push({ 
      name: 'ChannelList', 
      params: { 
        channelSlug: list.channel.slug,
        listSlug: list.slug 
      } 
    })
  } else {
    // Navigate to user list
    const username = currentUser.value?.custom_url || currentUser.value?.username
    router.push({ 
      name: 'UserList', 
      params: { 
        username: username,
        slug: list.slug 
      } 
    })
  }
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