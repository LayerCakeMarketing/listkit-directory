<template>
  <div>
    <!-- Trigger Button -->
    <button
      @click="open = true"
      class="w-full flex items-center justify-between px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
    >
      <div class="flex items-center">
        <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span class="text-gray-700">{{ currentLocationDisplay }}</span>
      </div>
      <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
      </svg>
    </button>

    <!-- Command Palette Dialog -->
    <TransitionRoot :show="open" as="template" @after-leave="rawQuery = ''" appear>
      <Dialog class="relative z-50" @close="open = false">
        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-gray-500/25 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto p-4 sm:p-6 md:p-20">
          <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="ease-in duration-200" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
            <DialogPanel class="mx-auto max-w-xl transform divide-y divide-gray-100 overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black/5 transition-all">
              <Combobox @update:modelValue="onSelect">
                <div class="relative">
                  <MagnifyingGlassIcon class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400" aria-hidden="true" />
                  <ComboboxInput 
                    class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm" 
                    placeholder="Search for a city, state, or neighborhood..." 
                    @change="rawQuery = $event.target.value"
                    :display-value="() => rawQuery"
                  />
                </div>

                <!-- Current Location Section -->
                <div v-if="!query && currentLocation" class="px-4 py-3 border-b border-gray-100">
                  <p class="text-xs font-semibold text-gray-500 mb-2">CURRENT LOCATION</p>
                  <button
                    class="w-full flex items-center px-3 py-2 bg-blue-50 rounded-lg text-left"
                  >
                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <div>
                      <p class="font-medium text-gray-900">{{ currentLocation.name }}</p>
                      <p class="text-sm text-gray-500">{{ currentLocation.parent?.name || currentLocation.state }}</p>
                    </div>
                  </button>
                </div>

                <!-- Search Results -->
                <ComboboxOptions v-if="filteredLocations.length > 0" static as="ul" class="max-h-80 scroll-py-2 overflow-y-auto">
                  <li v-if="nearbyLocations.length > 0 && !query" class="p-2">
                    <h2 class="mb-2 px-3 text-xs font-semibold text-gray-500">NEARBY</h2>
                    <ul class="text-sm text-gray-700">
                      <ComboboxOption v-for="location in nearbyLocations" :key="location.id" :value="location" as="template" v-slot="{ active }">
                        <li :class="['flex cursor-pointer select-none items-center rounded-md px-3 py-2', active && 'bg-blue-600 text-white']">
                          <svg :class="['h-5 w-5 flex-none', active ? 'text-white' : 'text-gray-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                          </svg>
                          <span class="ml-3 flex-auto truncate">{{ location.name }}</span>
                          <span :class="['ml-3 flex-none text-xs', active ? 'text-blue-200' : 'text-gray-500']">
                            {{ location.type === 'neighborhood' ? 'Neighborhood' : location.type === 'city' ? 'City' : 'State' }}
                          </span>
                        </li>
                      </ComboboxOption>
                    </ul>
                  </li>
                  
                  <li v-if="popularLocations.length > 0 && !query" class="p-2">
                    <h2 class="mb-2 px-3 text-xs font-semibold text-gray-500">POPULAR</h2>
                    <ul class="text-sm text-gray-700">
                      <ComboboxOption v-for="location in popularLocations" :key="location.id" :value="location" as="template" v-slot="{ active }">
                        <li :class="['flex cursor-pointer select-none items-center rounded-md px-3 py-2', active && 'bg-blue-600 text-white']">
                          <svg :class="['h-5 w-5 flex-none', active ? 'text-white' : 'text-gray-400']" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                          </svg>
                          <span class="ml-3 flex-auto truncate">{{ location.name }}</span>
                          <span :class="['ml-3 flex-none text-xs', active ? 'text-blue-200' : 'text-gray-500']">
                            {{ location.parent?.name || location.state }}
                          </span>
                        </li>
                      </ComboboxOption>
                    </ul>
                  </li>

                  <li v-if="searchResults.length > 0" class="p-2">
                    <h2 v-if="!query" class="mb-2 px-3 text-xs font-semibold text-gray-500">ALL LOCATIONS</h2>
                    <ul class="text-sm text-gray-700">
                      <ComboboxOption v-for="location in searchResults" :key="location.id" :value="location" as="template" v-slot="{ active }">
                        <li :class="['flex cursor-pointer select-none items-center rounded-md px-3 py-2', active && 'bg-blue-600 text-white']">
                          <svg :class="['h-5 w-5 flex-none', active ? 'text-white' : 'text-gray-400']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                          </svg>
                          <div class="ml-3 flex-auto">
                            <p :class="[active ? 'text-white' : 'text-gray-900']">{{ location.name }}</p>
                            <p :class="['text-xs', active ? 'text-blue-200' : 'text-gray-500']">{{ location.full_name || location.parent?.name }}</p>
                          </div>
                        </li>
                      </ComboboxOption>
                    </ul>
                  </li>
                </ComboboxOptions>

                <!-- Empty State -->
                <div v-if="query !== '' && filteredLocations.length === 0" class="px-6 py-14 text-center text-sm">
                  <svg class="mx-auto h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
                  <p class="mt-4 font-semibold text-gray-900">No locations found</p>
                  <p class="mt-2 text-gray-500">We couldn't find any locations matching "{{ query }}"</p>
                </div>

                <!-- Help Footer -->
                <div class="flex items-center px-4 py-3 text-xs text-gray-700 bg-gray-50">
                  <svg class="h-4 w-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  Type to search • Use ↑↓ to navigate • Enter to select • Esc to close
                </div>
              </Combobox>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid'
import {
  Combobox,
  ComboboxInput,
  ComboboxOptions,
  ComboboxOption,
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import axios from 'axios'
import { debounce } from 'lodash'

const props = defineProps({
  modelValue: Object,
  currentLocation: Object
})

const emit = defineEmits(['update:modelValue', 'change'])

// State
const open = ref(false)
const rawQuery = ref('')
const searchResults = ref([])
const nearbyLocations = ref([])
const popularLocations = ref([])
const loading = ref(false)

// Computed
const query = computed(() => rawQuery.value.toLowerCase().trim())

const currentLocationDisplay = computed(() => {
  if (!props.currentLocation) return 'Select location'
  return props.currentLocation.name
})

const filteredLocations = computed(() => {
  if (!query.value) {
    // Show all sections when no query
    return [...nearbyLocations.value, ...popularLocations.value]
  }
  // Show only search results when there's a query
  return searchResults.value
})

// Methods
const fetchNearbyLocations = async () => {
  if (!props.currentLocation) return
  
  try {
    const response = await axios.get('/api/regions/nearby', {
      params: {
        region_id: props.currentLocation.id,
        limit: 5
      }
    })
    nearbyLocations.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching nearby locations:', error)
  }
}

const fetchPopularLocations = async () => {
  try {
    const response = await axios.get('/api/regions/popular', {
      params: { limit: 5 }
    })
    popularLocations.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching popular locations:', error)
  }
}

const searchLocations = debounce(async () => {
  if (!query.value) {
    searchResults.value = []
    return
  }

  loading.value = true
  try {
    const response = await axios.get('/api/regions/search', {
      params: { 
        q: query.value,
        limit: 10
      }
    })
    searchResults.value = response.data.data || []
  } catch (error) {
    console.error('Error searching locations:', error)
  } finally {
    loading.value = false
  }
}, 300)

const onSelect = async (location) => {
  if (!location) return
  
  try {
    // Set the location on the backend
    const response = await axios.post('/api/places/set-location', {
      region_id: location.id
    })
    
    if (response.data.success) {
      emit('update:modelValue', location)
      emit('change', location)
      open.value = false
      rawQuery.value = ''
    }
  } catch (error) {
    console.error('Error setting location:', error)
  }
}

// Watchers
watch(rawQuery, () => {
  searchLocations()
})

watch(open, (newValue) => {
  if (newValue) {
    fetchNearbyLocations()
    fetchPopularLocations()
  }
})
</script>