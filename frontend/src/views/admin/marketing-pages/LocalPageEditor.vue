<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-6">Local Pages Settings</h2>
      
      <!-- Page Type Selector -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Page Type</label>
        <select
          v-model="pageType"
          @change="loadSettings"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="index">Local Index</option>
          <option value="state">State Pages</option>
          <option value="city">City Pages</option>
          <option value="neighborhood">Neighborhood Pages</option>
        </select>
      </div>
      
      <!-- Region Selector (for non-index pages) -->
      <div v-if="pageType !== 'index'" class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Specific Region (Optional)</label>
        <LocationSearch
          v-model="selectedRegion"
          @change="handleRegionChange"
          :placeholder="'Select a region (leave empty for default)'"
          class="w-full"
        />
        <p class="mt-1 text-sm text-gray-500">
          Leave empty to set default settings for all {{ pageType }} pages
        </p>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
      
      <!-- Settings Form -->
      <form v-else @submit.prevent="saveSettings" class="space-y-6">
        <!-- Basic Settings -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Basic Settings</h3>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Page Title</label>
            <input
              v-model="form.title"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Browse Local Places"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Intro Text</label>
            <textarea
              v-model="form.intro_text"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="Explore places organized by states and cities"
            ></textarea>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Meta Description</label>
            <textarea
              v-model="form.meta_description"
              rows="2"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="SEO meta description"
            ></textarea>
          </div>
          
          <div>
            <label class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="rounded text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Active</span>
            </label>
          </div>
        </div>
        
        <!-- Featured Lists -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Featured Lists</h3>
          
          <!-- List Search -->
          <div class="flex gap-2">
            <div class="flex-1">
              <input
                v-model="listSearchQuery"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Search for lists to feature..."
                @input="searchLists"
              />
            </div>
          </div>
          
          <!-- List Search Results -->
          <div v-if="listSearchResults.length > 0" class="border border-gray-200 rounded-md p-2 max-h-48 overflow-y-auto">
            <div
              v-for="list in listSearchResults"
              :key="list.id"
              @click="addFeaturedList(list)"
              class="p-2 hover:bg-gray-50 cursor-pointer rounded"
            >
              <div class="font-medium">{{ list.name }}</div>
              <div class="text-sm text-gray-500">by {{ list.user?.name }} • {{ list.items_count }} items</div>
            </div>
          </div>
          
          <!-- Selected Featured Lists -->
          <div v-if="form.featured_lists.length > 0" class="space-y-2">
            <div
              v-for="(item, index) in form.featured_lists"
              :key="item.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
            >
              <div>
                <div class="font-medium">{{ item.name }}</div>
                <div class="text-sm text-gray-500">{{ item.items_count }} items</div>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="moveListUp(index)"
                  :disabled="index === 0"
                  class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                  </svg>
                </button>
                <button
                  type="button"
                  @click="moveListDown(index)"
                  :disabled="index === form.featured_lists.length - 1"
                  class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </button>
                <button
                  type="button"
                  @click="removeFeaturedList(index)"
                  class="text-red-600 hover:text-red-800"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Featured Places -->
        <div class="space-y-4">
          <h3 class="text-lg font-medium text-gray-900">Featured Places</h3>
          
          <!-- Place Search -->
          <div class="flex gap-2">
            <div class="flex-1">
              <input
                v-model="placeSearchQuery"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Search for places to feature..."
                @input="searchPlaces"
              />
            </div>
          </div>
          
          <!-- Place Search Results -->
          <div v-if="placeSearchResults.length > 0" class="border border-gray-200 rounded-md p-2 max-h-48 overflow-y-auto">
            <div
              v-for="place in placeSearchResults"
              :key="place.id"
              @click="addFeaturedPlace(place)"
              class="p-2 hover:bg-gray-50 cursor-pointer rounded"
            >
              <div class="font-medium">{{ place.title }}</div>
              <div class="text-sm text-gray-500">{{ place.category?.name }} • {{ place.location?.city }}, {{ place.location?.state }}</div>
            </div>
          </div>
          
          <!-- Selected Featured Places -->
          <div v-if="form.featured_places.length > 0" class="space-y-2">
            <div
              v-for="(item, index) in form.featured_places"
              :key="item.id"
              class="p-3 bg-gray-50 rounded-md"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="font-medium">{{ item.title }}</div>
                  <div class="text-sm text-gray-500">{{ item.category?.name }}</div>
                  <input
                    v-model="item.tagline"
                    type="text"
                    class="mt-2 w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                    placeholder="Optional tagline"
                  />
                </div>
                <div class="flex items-center gap-2 ml-4">
                  <button
                    type="button"
                    @click="movePlaceUp(index)"
                    :disabled="index === 0"
                    class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="movePlaceDown(index)"
                    :disabled="index === form.featured_places.length - 1"
                    class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    @click="removeFeaturedPlace(index)"
                    class="text-red-600 hover:text-red-800"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Content Sections -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Content Sections</h3>
            <button
              type="button"
              @click="addContentSection"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              + Add Section
            </button>
          </div>
          
          <div v-if="form.content_sections.length > 0" class="space-y-4">
            <div
              v-for="(section, index) in form.content_sections"
              :key="index"
              class="border border-gray-200 rounded-md p-4"
            >
              <div class="flex items-center justify-between mb-3">
                <select
                  v-model="section.type"
                  class="px-3 py-1 border border-gray-300 rounded-md text-sm"
                >
                  <option value="text">Text</option>
                  <option value="html">HTML</option>
                </select>
                <button
                  type="button"
                  @click="removeContentSection(index)"
                  class="text-red-600 hover:text-red-800"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
              
              <input
                v-if="section.type === 'text'"
                v-model="section.title"
                type="text"
                class="w-full mb-2 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Section title (optional)"
              />
              
              <textarea
                v-model="section.content"
                rows="4"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :placeholder="section.type === 'html' ? 'HTML content...' : 'Text content...'"
              ></textarea>
            </div>
          </div>
        </div>
        
        <!-- Submit Button -->
        <div class="flex justify-end gap-3 pt-6 border-t">
          <router-link
            to="/admin/marketing-pages"
            class="px-4 py-2 text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
          >
            Cancel
          </router-link>
          <button
            type="submit"
            :disabled="saving"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {{ saving ? 'Saving...' : 'Save Settings' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { debounce } from 'lodash'
import LocationSearch from '@/components/LocationSearch.vue'

const router = useRouter()

// State
const loading = ref(false)
const saving = ref(false)
const pageType = ref('index')
const selectedRegion = ref(null)
const listSearchQuery = ref('')
const listSearchResults = ref([])
const placeSearchQuery = ref('')
const placeSearchResults = ref([])

const form = reactive({
  title: '',
  intro_text: '',
  meta_description: '',
  featured_lists: [],
  featured_places: [],
  content_sections: [],
  settings: {},
  is_active: true
})

// Load settings for current page type and region
const loadSettings = async () => {
  loading.value = true
  try {
    const params = {
      page_type: pageType.value
    }
    if (selectedRegion.value) {
      params.region_id = selectedRegion.value.id
    }
    
    const response = await axios.get('/api/admin/marketing-pages/special/local', { params })
    
    if (response.data.settings) {
      Object.assign(form, response.data.settings)
    } else {
      // Reset form for new settings
      Object.assign(form, {
        title: '',
        intro_text: '',
        meta_description: '',
        featured_lists: [],
        featured_places: [],
        content_sections: [],
        settings: {},
        is_active: true
      })
    }
  } catch (error) {
    console.error('Error loading settings:', error)
  } finally {
    loading.value = false
  }
}

// Search for lists
const searchLists = debounce(async () => {
  if (!listSearchQuery.value) {
    listSearchResults.value = []
    return
  }
  
  try {
    const params = { q: listSearchQuery.value }
    if (selectedRegion.value) {
      params.region_id = selectedRegion.value.id
    }
    
    const response = await axios.get('/api/admin/marketing-pages/special/local/search-lists', { params })
    listSearchResults.value = response.data
  } catch (error) {
    console.error('Error searching lists:', error)
  }
}, 300)

// Search for places
const searchPlaces = debounce(async () => {
  if (!placeSearchQuery.value) {
    placeSearchResults.value = []
    return
  }
  
  try {
    const params = { q: placeSearchQuery.value }
    if (selectedRegion.value) {
      params.region_id = selectedRegion.value.id
    }
    
    const response = await axios.get('/api/admin/marketing-pages/special/local/search-places', { params })
    placeSearchResults.value = response.data
  } catch (error) {
    console.error('Error searching places:', error)
  }
}, 300)

// Add featured list
const addFeaturedList = (list) => {
  if (!form.featured_lists.find(l => l.id === list.id)) {
    form.featured_lists.push({
      id: list.id,
      name: list.name,
      items_count: list.items_count,
      order: form.featured_lists.length
    })
  }
  listSearchQuery.value = ''
  listSearchResults.value = []
}

// Add featured place
const addFeaturedPlace = (place) => {
  if (!form.featured_places.find(p => p.id === place.id)) {
    form.featured_places.push({
      id: place.id,
      title: place.title,
      category: place.category,
      tagline: '',
      order: form.featured_places.length
    })
  }
  placeSearchQuery.value = ''
  placeSearchResults.value = []
}

// Remove featured list
const removeFeaturedList = (index) => {
  form.featured_lists.splice(index, 1)
}

// Remove featured place
const removeFeaturedPlace = (index) => {
  form.featured_places.splice(index, 1)
}

// Move list up
const moveListUp = (index) => {
  if (index > 0) {
    const temp = form.featured_lists[index]
    form.featured_lists[index] = form.featured_lists[index - 1]
    form.featured_lists[index - 1] = temp
  }
}

// Move list down
const moveListDown = (index) => {
  if (index < form.featured_lists.length - 1) {
    const temp = form.featured_lists[index]
    form.featured_lists[index] = form.featured_lists[index + 1]
    form.featured_lists[index + 1] = temp
  }
}

// Move place up
const movePlaceUp = (index) => {
  if (index > 0) {
    const temp = form.featured_places[index]
    form.featured_places[index] = form.featured_places[index - 1]
    form.featured_places[index - 1] = temp
  }
}

// Move place down
const movePlaceDown = (index) => {
  if (index < form.featured_places.length - 1) {
    const temp = form.featured_places[index]
    form.featured_places[index] = form.featured_places[index + 1]
    form.featured_places[index + 1] = temp
  }
}

// Add content section
const addContentSection = () => {
  form.content_sections.push({
    type: 'text',
    title: '',
    content: ''
  })
}

// Remove content section
const removeContentSection = (index) => {
  form.content_sections.splice(index, 1)
}

// Handle region change
const handleRegionChange = () => {
  loadSettings()
}

// Save settings
const saveSettings = async () => {
  saving.value = true
  try {
    const data = {
      page_type: pageType.value,
      region_id: selectedRegion.value?.id,
      ...form,
      featured_lists: form.featured_lists.map((list, index) => ({
        id: list.id,
        order: index
      })),
      featured_places: form.featured_places.map((place, index) => ({
        id: place.id,
        order: index,
        tagline: place.tagline
      }))
    }
    
    await axios.post('/api/admin/marketing-pages/special/local', data)
    
    // Show success message or redirect
    router.push('/admin/marketing-pages')
  } catch (error) {
    console.error('Error saving settings:', error)
    alert('Failed to save settings. Please try again.')
  } finally {
    saving.value = false
  }
}

// Initialize
onMounted(() => {
  loadSettings()
})

// Watch for page type changes
watch(pageType, () => {
  selectedRegion.value = null
  loadSettings()
})
</script>