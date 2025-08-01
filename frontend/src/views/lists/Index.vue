<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-16 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-3xl font-bold text-gray-900">My Lists & Chains</h1>
          <div class="flex items-center space-x-3">
            <router-link
              to="/chains/create"
              class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
              Create Chain
            </router-link>
            <button
              @click="showCreateModal = true"
              class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Create List
            </button>
          </div>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
          <nav class="-mb-px flex space-x-8">
            <button
              @click="activeTab = 'lists'"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                activeTab === 'lists'
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              <span class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                Lists ({{ lists.length }})
              </span>
            </button>
            <button
              @click="activeTab = 'chains'"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                activeTab === 'chains'
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              <span class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                Chains ({{ chains.length }})
              </span>
            </button>
          </nav>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Tab Content -->
        <div v-else>
          <!-- Lists Tab Content -->
          <div v-if="activeTab === 'lists'">
            <!-- Lists Grid -->
          <div v-if="lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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

          <!-- Empty State for Lists -->
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

          <!-- Chains Tab Content -->
          <div v-if="activeTab === 'chains'">
          <!-- Chains Grid -->
          <div v-if="chains.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="chain in chains"
              :key="chain.id"
              class="relative bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
              @click="navigateToChain(chain)"
            >
              <!-- Chain Image -->
              <div class="h-48 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-t-lg overflow-hidden relative">
                <div class="absolute inset-0 flex items-center justify-center">
                  <svg class="w-24 h-24 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                  </svg>
                </div>
                <div class="absolute top-4 right-4">
                  <span class="px-2 py-1 text-xs font-semibold rounded-full bg-white bg-opacity-90 text-purple-700">
                    {{ chain.lists_count }} lists
                  </span>
                </div>
              </div>

              <!-- Chain Content -->
              <div class="p-4">
                <div class="flex justify-between items-start mb-2">
                  <h3 class="text-lg font-semibold text-gray-900">{{ chain.name }}</h3>
                  <div class="flex items-center space-x-2">
                    <span
                      class="px-2 py-1 text-xs rounded-full"
                      :class="{
                        'bg-green-100 text-green-800': chain.visibility === 'public',
                        'bg-gray-100 text-gray-800': chain.visibility === 'private',
                        'bg-yellow-100 text-yellow-800': chain.visibility === 'unlisted'
                      }"
                    >
                      {{ chain.visibility }}
                    </span>
                    
                    <!-- Dropdown Menu -->
                    <Menu as="div" class="relative inline-block text-left" @click.stop>
                      <div>
                        <MenuButton class="flex items-center rounded-full bg-gray-100 p-1 text-gray-400 hover:text-gray-600 focus:outline-none">
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
                                @click.stop="editChain(chain)"
                                :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block w-full text-left px-4 py-2 text-sm']"
                              >
                                Edit
                              </button>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                              <button
                                @click.stop="deleteChain(chain)"
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
                
                <p class="text-sm text-gray-600 mb-3">{{ chain.description || 'No description' }}</p>
                
                <!-- Chain Lists Preview -->
                <div class="flex -space-x-2 mb-3">
                  <div
                    v-for="(list, index) in chain.lists?.slice(0, 4)"
                    :key="list.id"
                    class="relative inline-block"
                    :style="{ zIndex: 4 - index }"
                  >
                    <div class="w-8 h-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-xs font-medium text-gray-600">
                      {{ list.name.charAt(0) }}
                    </div>
                  </div>
                  <div v-if="chain.lists?.length > 4" class="relative inline-block w-8 h-8 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-xs font-medium text-gray-600">
                    +{{ chain.lists.length - 4 }}
                  </div>
                </div>
                
                <div class="flex items-center text-xs text-gray-500 space-x-4">
                  <span>{{ new Date(chain.created_at).toLocaleDateString() }}</span>
                  <span>{{ chain.views_count }} views</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Empty State for Chains -->
          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No chains yet</h3>
            <p class="mt-1 text-sm text-gray-500">Create your first chain to organize lists into sequences</p>
            <div class="mt-6">
              <router-link
                to="/chains/create"
                class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                Create Chain
              </router-link>
            </div>
          </div>
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
const chains = ref([])
const activeTab = ref('lists')
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

// Fetch user's chains
async function fetchChains() {
  try {
    const response = await axios.get('/api/chains', {
      params: {
        owner: 'me',
        per_page: 100
      }
    })
    console.log('Chains API response:', response.data)
    chains.value = response.data.data || []
    console.log('Chains loaded:', chains.value.length, chains.value)
  } catch (error) {
    console.error('Error fetching chains:', error)
    chains.value = []
  }
}

// Navigate to chain
function navigateToChain(chain) {
  router.push(`/chains/${chain.id}`)
}

// Edit chain
function editChain(chain) {
  router.push(`/chains/${chain.id}/edit`)
}

// Delete chain
async function deleteChain(chain) {
  if (!confirm(`Are you sure you want to delete "${chain.name}"?`)) {
    return
  }
  
  try {
    await axios.delete(`/api/chains/${chain.id}`)
    chains.value = chains.value.filter(c => c.id !== chain.id)
  } catch (error) {
    alert('Failed to delete chain. Please try again.')
  }
}

onMounted(() => {
  fetchLists()
  fetchChains()
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