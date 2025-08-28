<template>
  <div>
    <!-- Header -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Advanced Region Management</h2>
          <p class="mt-1 text-sm text-gray-500">Manage regions with inline editing and featured content</p>
        </div>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
        >
          <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          Add Region
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div>
      <!-- Search and Filters -->
      <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search regions..."
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          <select
            v-model="filters.type"
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="">All Types</option>
            <option value="state">State</option>
            <option value="city">City</option>
            <option value="neighborhood">Neighborhood</option>
          </select>
          <select
            v-model="filters.parentId"
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="">All Parents</option>
            <option v-for="parent in parentRegions" :key="parent.id" :value="parent.id">
              {{ parent.name }}
            </option>
          </select>
          <button
            @click="fetchRegions"
            class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
            </svg>
            Apply Filters
          </button>
        </div>
      </div>

      <!-- Regions List with Inline Editing -->
      <div class="space-y-4">
        <div
          v-for="region in regions"
          :key="region.id"
          class="bg-white rounded-lg shadow"
        >
          <!-- Region Header -->
          <div
            @click="toggleRegion(region.id)"
            class="px-6 py-4 cursor-pointer hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-4">
                <img
                  v-if="region.cover_image_url"
                  :src="region.cover_image_url"
                  :alt="region.name"
                  class="w-16 h-16 rounded-lg object-cover"
                />
                <div v-else class="w-16 h-16 rounded-lg bg-gray-200 flex items-center justify-center">
                  <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-gray-900">{{ region.name }}</h3>
                  <div class="flex items-center space-x-4 text-sm text-gray-500">
                    <span class="capitalize">{{ region.type }}</span>
                    <span v-if="region.parent">Parent: {{ region.parent.name }}</span>
                    <span>{{ region.entries_count || 0 }} places</span>
                    <span>{{ region.featured_entries?.length || 0 }} featured</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center space-x-3">
                <!-- Media Button -->
                <MediaViewer
                  :entity-type="'region'"
                  :entity-id="region.id"
                  :button-text="''"
                  button-class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors"
                  :title="`Media for ${region.name}`"
                  :allow-upload="true"
                  upload-context="gallery"
                />
                <button
                  @click.stop="deleteRegion(region)"
                  class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md transition-colors"
                  title="Delete Region"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
                <svg
                  :class="{ 'rotate-180': expandedRegions.includes(region.id) }"
                  class="w-5 h-5 text-gray-400 transition-transform"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>

          <!-- Expanded Content with Tabs -->
          <div v-if="expandedRegions.includes(region.id)" class="border-t">
            <!-- Tab Navigation -->
            <div class="border-b px-6">
              <nav class="-mb-px flex space-x-8">
                <button
                  v-for="tab in tabs"
                  :key="tab.key"
                  @click="activeTab[region.id] = tab.key"
                  :class="[
                    activeTab[region.id] === tab.key
                      ? 'border-indigo-500 text-indigo-600'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                  ]"
                >
                  {{ tab.name }}
                </button>
              </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
              <!-- Details Tab -->
              <div v-if="activeTab[region.id] === 'details'">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input
                      v-model="region.name"
                      type="text"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                    <input
                      v-model="region.slug"
                      type="text"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select
                      v-model="region.type"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option value="state">State</option>
                      <option value="city">City</option>
                      <option value="neighborhood">Neighborhood</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Parent Region</label>
                    <select
                      v-model="region.parent_id"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option value="">No Parent</option>
                      <option v-for="parent in getValidParents(region)" :key="parent.id" :value="parent.id">
                        {{ parent.name }}
                      </option>
                    </select>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Intro Text</label>
                    <textarea
                      v-model="region.intro_text"
                      rows="3"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    ></textarea>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image</label>
                    <div v-if="region.cover_image_url || region.cloudflare_image_id" class="mb-4">
                      <img 
                        :src="region.cover_image_url || `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${region.cloudflare_image_id}/public`" 
                        alt="Current cover image"
                        class="w-full h-48 object-cover rounded-lg"
                      />
                    </div>
                    <CloudflareDragDropUploader
                      :max-files="1"
                      context="cover"
                      entity-type="App\Models\Region"
                      :entity-id="region.id"
                      :metadata="{
                        entity_id: region.id,
                        entity_type: 'Region',
                        region_name: region.name,
                        region_type: region.type,
                        region_level: region.level
                      }"
                      :auto-upload="true"
                      @upload-success="handleImageUpload($event, region)"
                    />
                  </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    @click="cancelEdit(region)"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    @click="saveRegion(region)"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
                  >
                    Save Changes
                  </button>
                </div>
              </div>

              <!-- Facts Tab -->
              <div v-if="activeTab[region.id] === 'facts'">
                <div class="space-y-6">
                  <!-- Core Location Facts -->
                  <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">🗺️ Core Location Facts</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Founded/Established Year</label>
                        <input
                          v-model="region.facts.established_year"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Population</label>
                        <input
                          v-model="region.facts.population"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Area Size</label>
                        <input
                          v-model="region.facts.area_size"
                          type="text"
                          placeholder="e.g., 500 sq miles"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Elevation</label>
                        <input
                          v-model="region.facts.elevation"
                          type="text"
                          placeholder="e.g., 500 ft"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
                        <input
                          v-model="region.facts.timezone"
                          type="text"
                          placeholder="e.g., PST"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nickname</label>
                        <input
                          v-model="region.facts.nickname"
                          type="text"
                          placeholder="e.g., City of Angels"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Quick Highlights -->
                  <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">📸 Quick Highlights</h4>
                    <div class="space-y-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Known For</label>
                        <textarea
                          v-model="region.facts.known_for"
                          rows="2"
                          placeholder="e.g., beaches, tech scene, ski resorts"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Most Famous Landmark</label>
                        <input
                          v-model="region.facts.famous_landmark"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Popular Local Food or Dish</label>
                        <input
                          v-model="region.facts.local_food"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Biggest Annual Event or Festival</label>
                        <input
                          v-model="region.facts.annual_event"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Filming Location For</label>
                        <input
                          v-model="region.facts.filming_location"
                          type="text"
                          placeholder="Famous movies or shows"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Fun Facts -->
                  <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">🧠 Fun Facts</h4>
                    <div class="space-y-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Historical Fact</label>
                        <input
                          v-model="region.facts.historical_fact"
                          type="text"
                          placeholder="e.g., first ___ in America"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unusual Law or Tradition</label>
                        <input
                          v-model="region.facts.unusual_law"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Famous People From Here</label>
                        <textarea
                          v-model="region.facts.famous_people"
                          rows="2"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Weird Claim to Fame</label>
                        <input
                          v-model="region.facts.weird_claim"
                          type="text"
                          placeholder="e.g., UFO capital of the world"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name Origin</label>
                        <input
                          v-model="region.facts.name_origin"
                          type="text"
                          placeholder="Where the name came from"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Lifestyle Snapshot -->
                  <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">📊 Lifestyle Snapshot</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Median Home Price</label>
                        <input
                          v-model="region.facts.median_home_price"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Walkability Score</label>
                        <input
                          v-model="region.facts.walkability_score"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Average Commute Time</label>
                        <input
                          v-model="region.facts.commute_time"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Top Industries</label>
                        <input
                          v-model="region.facts.top_industries"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vibe/Style</label>
                        <input
                          v-model="region.facts.vibe_style"
                          type="text"
                          placeholder="e.g., artsy, outdoorsy, high-tech, family-friendly"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Nature & Outdoors -->
                  <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">🌳 Nature & Outdoors</h4>
                    <div class="space-y-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Closest Major Park/Beach/Trail</label>
                        <input
                          v-model="region.facts.nature_spots"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Climate Summary</label>
                        <input
                          v-model="region.facts.climate"
                          type="text"
                          placeholder="e.g., Mild winters, dry summers"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Terrain Type</label>
                        <input
                          v-model="region.facts.terrain"
                          type="text"
                          placeholder="e.g., flat, hilly, coastal"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- Location Context -->
                  <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">🧭 Location Context</h4>
                    <div class="space-y-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Distance to Nearest Major City</label>
                        <input
                          v-model="region.facts.distance_to_city"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transportation Options</label>
                        <input
                          v-model="region.facts.transportation"
                          type="text"
                          placeholder="e.g., freeway access, light rail, airport"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Zip Codes</label>
                        <input
                          v-model="region.facts.zip_codes"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">School Districts</label>
                        <input
                          v-model="region.facts.school_districts"
                          type="text"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    @click="cancelEdit(region)"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    @click="saveRegion(region)"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
                  >
                    Save Facts
                  </button>
                </div>
              </div>

              <!-- State Symbols Tab -->
              <div v-if="activeTab[region.id] === 'state-symbols' && region.type === 'state'">
                <div class="space-y-6">
                  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">State symbols are only available for state-level regions.</p>
                  </div>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic State Info -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">State Nickname</label>
                      <input
                        v-model="region.state_symbols.nickname"
                        type="text"
                        placeholder="e.g., The Golden State"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">State Motto</label>
                      <input
                        v-model="region.state_symbols.motto"
                        type="text"
                        placeholder="e.g., Eureka"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">State Capital</label>
                      <input
                        v-model="region.state_symbols.capital"
                        type="text"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      />
                    </div>
                    
                    <!-- State Bird -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">🐦 State Bird</h5>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                          <input
                            v-model="region.state_symbols.bird.name"
                            type="text"
                            placeholder="e.g., California Quail"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                          <div v-if="region.state_symbols.bird.image" class="mb-2">
                            <img :src="region.state_symbols.bird.image" alt="State bird" class="h-20 w-auto rounded" />
                          </div>
                          <CloudflareDragDropUploader
                            :max-files="1"
                            context="gallery"
                            entity-type="Region"
                            :entity-id="region.id"
                            :auto-upload="true"
                            @upload-success="handleStateSymbolUpload($event, region, 'bird')"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- State Flower -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">🌸 State Flower</h5>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                          <input
                            v-model="region.state_symbols.flower.name"
                            type="text"
                            placeholder="e.g., California Poppy"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                          <div v-if="region.state_symbols.flower.image" class="mb-2">
                            <img :src="region.state_symbols.flower.image" alt="State flower" class="h-20 w-auto rounded" />
                          </div>
                          <CloudflareDragDropUploader
                            :max-files="1"
                            context="gallery"
                            entity-type="Region"
                            :entity-id="region.id"
                            :auto-upload="true"
                            @upload-success="handleStateSymbolUpload($event, region, 'flower')"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- State Tree -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">🌳 State Tree</h5>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                          <input
                            v-model="region.state_symbols.tree.name"
                            type="text"
                            placeholder="e.g., Coast Redwood"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                          <div v-if="region.state_symbols.tree.image" class="mb-2">
                            <img :src="region.state_symbols.tree.image" alt="State tree" class="h-20 w-auto rounded" />
                          </div>
                          <CloudflareDragDropUploader
                            :max-files="1"
                            context="gallery"
                            entity-type="Region"
                            :entity-id="region.id"
                            :auto-upload="true"
                            @upload-success="handleStateSymbolUpload($event, region, 'tree')"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- State Animal -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">🐻 State Animal/Mammal</h5>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                          <input
                            v-model="region.state_symbols.mammal.name"
                            type="text"
                            placeholder="e.g., California Grizzly Bear"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                          <div v-if="region.state_symbols.mammal.image" class="mb-2">
                            <img :src="region.state_symbols.mammal.image" alt="State mammal" class="h-20 w-auto rounded" />
                          </div>
                          <CloudflareDragDropUploader
                            :max-files="1"
                            context="gallery"
                            entity-type="Region"
                            :entity-id="region.id"
                            :auto-upload="true"
                            @upload-success="handleStateSymbolUpload($event, region, 'mammal')"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- State Fish -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">🎣 State Fish</h5>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                          <input
                            v-model="region.state_symbols.fish.name"
                            type="text"
                            placeholder="e.g., Golden Trout"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                          <div v-if="region.state_symbols.fish.image" class="mb-2">
                            <img :src="region.state_symbols.fish.image" alt="State fish" class="h-20 w-auto rounded" />
                          </div>
                          <CloudflareDragDropUploader
                            :max-files="1"
                            context="gallery"
                            entity-type="Region"
                            :entity-id="region.id"
                            :auto-upload="true"
                            @upload-success="handleStateSymbolUpload($event, region, 'fish')"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- State Song -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">🎶 State Song</h5>
                      <div class="space-y-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                          <input
                            v-model="region.state_symbols.song.title"
                            type="text"
                            placeholder="e.g., I Love You, California"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Audio URL</label>
                          <input
                            v-model="region.state_symbols.song.audio_url"
                            type="text"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Lyrics URL</label>
                          <input
                            v-model="region.state_symbols.song.lyrics_url"
                            type="text"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- State Flag -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">🏴 State Flag</h5>
                      <div class="space-y-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                          <div v-if="region.state_symbols.flag.image" class="mb-2">
                            <img :src="region.state_symbols.flag.image" alt="State flag" class="h-20 w-auto rounded" />
                          </div>
                          <CloudflareDragDropUploader
                            :max-files="1"
                            context="gallery"
                            entity-type="Region"
                            :entity-id="region.id"
                            :auto-upload="true"
                            @upload-success="handleStateSymbolUpload($event, region, 'flag')"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                          <input
                            v-model="region.state_symbols.flag.description"
                            type="text"
                            placeholder="e.g., Bear Flag adopted in 1911"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- State Seal -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">🛡️ State Seal</h5>
                      <div class="space-y-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                          <div v-if="region.state_symbols.seal.image" class="mb-2">
                            <img :src="region.state_symbols.seal.image" alt="State seal" class="h-20 w-auto rounded" />
                          </div>
                          <CloudflareDragDropUploader
                            :max-files="1"
                            context="gallery"
                            entity-type="Region"
                            :entity-id="region.id"
                            :auto-upload="true"
                            @upload-success="handleStateSymbolUpload($event, region, 'seal')"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                          <input
                            v-model="region.state_symbols.seal.description"
                            type="text"
                            placeholder="e.g., Depicts Minerva, grizzly bear, and a gold miner"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- Resources -->
                    <div class="md:col-span-2 border rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">📚 Resources</h5>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Official Website</label>
                          <input
                            v-model="region.state_symbols.resources.official_website"
                            type="text"
                            placeholder="e.g., https://www.ca.gov"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700 mb-1">Tourism Site</label>
                          <input
                            v-model="region.state_symbols.resources.tourism_site"
                            type="text"
                            placeholder="e.g., https://visitcalifornia.com"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    @click="cancelEdit(region)"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    @click="saveRegion(region)"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
                  >
                    Save State Symbols
                  </button>
                </div>
              </div>
              <div v-else-if="activeTab[region.id] === 'state-symbols' && region.type !== 'state'" class="text-center py-12 text-gray-500">
                State symbols are only available for state-level regions.
              </div>

              <!-- Geodata Tab -->
              <div v-if="activeTab[region.id] === 'geodata'">
                <div class="space-y-6">
                  <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800">Add geographic boundaries using either PostgreSQL polygon format or GeoJSON.</p>
                  </div>

                  <!-- PostgreSQL Polygon -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      PostgreSQL Polygon Coordinates
                    </label>
                    <textarea
                      v-model="region.polygon_coordinates"
                      rows="4"
                      placeholder="Format: ((lng1,lat1),(lng2,lat2),(lng3,lat3),...)&#10;Example: ((-122.5,37.7),(-122.4,37.7),(-122.4,37.8),(-122.5,37.8))"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500">Enter coordinates in longitude,latitude pairs. The polygon will be automatically closed.</p>
                  </div>

                  <!-- GeoJSON -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                      GeoJSON Data
                    </label>
                    <textarea
                      v-model="region.geojson_string"
                      rows="8"
                      placeholder='{"type": "Polygon", "coordinates": [[[-122.5, 37.7], [-122.4, 37.7], [-122.4, 37.8], [-122.5, 37.8], [-122.5, 37.7]]]}'
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm"
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500">Paste valid GeoJSON for a Polygon or MultiPolygon geometry.</p>
                  </div>

                  <!-- Preview Map (placeholder for future implementation) -->
                  <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Map preview will be available in a future update</p>
                  </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    @click="cancelEdit(region)"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    @click="saveRegion(region)"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
                  >
                    Save Geodata
                  </button>
                </div>
              </div>

              <!-- Featured Places Tab -->
              <div v-if="activeTab[region.id] === 'featured-places'">
                <div class="mb-4 flex justify-between items-center">
                  <h4 class="text-sm font-medium text-gray-900">Featured Places</h4>
                  <button
                    @click="showAddFeaturedModal(region)"
                    class="inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-200"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Featured
                  </button>
                </div>
                
                <div v-if="region.featured_entries?.length > 0" class="space-y-2">
                  <div
                    v-for="entry in region.featured_entries"
                    :key="entry.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div class="flex items-center space-x-3">
                      <img
                        v-if="entry.logo_url"
                        :src="entry.logo_url"
                        :alt="entry.name"
                        class="w-10 h-10 rounded object-cover"
                      />
                      <div>
                        <p class="font-medium text-gray-900">{{ entry.name }}</p>
                        <p class="text-sm text-gray-500">{{ entry.category?.name }}</p>
                      </div>
                    </div>
                    <button
                      @click="removeFeaturedEntry(region, entry)"
                      class="text-red-600 hover:text-red-800"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                  No featured places yet
                </div>
              </div>

              <!-- Featured Lists Tab -->
              <div v-if="activeTab[region.id] === 'featured-lists'">
                <div class="mb-4 flex justify-between items-center">
                  <h4 class="text-sm font-medium text-gray-900">Featured Lists</h4>
                  <button
                    @click="showAddFeaturedListModal(region)"
                    class="inline-flex items-center px-3 py-1 bg-indigo-100 text-indigo-700 text-sm font-medium rounded-md hover:bg-indigo-200"
                  >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Add Featured
                  </button>
                </div>
                
                <div v-if="region.featured_lists?.length > 0" class="space-y-2">
                  <div
                    v-for="list in region.featured_lists"
                    :key="list.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <div>
                      <p class="font-medium text-gray-900">{{ list.name }}</p>
                      <p class="text-sm text-gray-500">by {{ list.user?.name }} • {{ list.items_count }} items</p>
                    </div>
                    <button
                      @click="removeFeaturedList(region, list)"
                      class="text-red-600 hover:text-red-800"
                    >
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </div>
                <div v-else class="text-center py-8 text-gray-500">
                  No featured lists yet
                </div>
              </div>

              <!-- Featured Regions Tab -->
              <div v-if="activeTab[region.id] === 'featured-regions'">
                <FeaturedRegionsManager 
                  :region-id="region.id"
                  @updated="fetchRegions"
                />
              </div>

              <!-- Statistics Tab -->
              <div v-if="activeTab[region.id] === 'stats'">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Total Places</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ region.entries_count || 0 }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Featured Places</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ region.featured_entries?.length || 0 }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Featured Lists</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ region.featured_lists?.length || 0 }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Sub-regions</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ region.children_count || 0 }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Views</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ region.view_count || 0 }}</p>
                  </div>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Created</p>
                    <p class="text-sm font-medium text-gray-900">{{ formatDate(region.created_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-6">
        <Pagination
          :current-page="currentPage"
          :last-page="totalPages"
          :total="totalRegions"
          :from="(currentPage - 1) * perPage + 1"
          :to="Math.min(currentPage * perPage, totalRegions)"
          @change-page="currentPage = $event; fetchRegions()"
        />
      </div>
    </div>

    <!-- Create Region Modal -->
    <Modal v-if="showCreateModal" @close="showCreateModal = false">
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">Create New Region</h3>
      </template>
      <template #default>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input
              v-model="newRegion.name"
              type="text"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
            <select
              v-model="newRegion.type"
              required
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="state">State</option>
              <option value="city">City</option>
              <option value="neighborhood">Neighborhood</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Region</label>
            <select
              v-model="newRegion.parent_id"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">No Parent</option>
              <option v-for="parent in getValidParentsForType(newRegion.type)" :key="parent.id" :value="parent.id">
                {{ parent.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea
              v-model="newRegion.description"
              rows="3"
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            ></textarea>
          </div>
        </div>
      </template>
      <template #footer>
        <button
          @click="showCreateModal = false"
          class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 mr-3"
        >
          Cancel
        </button>
        <button
          @click="createRegion"
          class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
        >
          Create Region
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
// Removed AdminDashboardLayout import as this is now a child component
import Modal from '@/components/Modal.vue'
import Pagination from '@/components/Pagination.vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import MediaViewer from '@/components/MediaViewer.vue'
import FeaturedRegionsManager from '@/components/admin/FeaturedRegionsManager.vue'

// State
const regions = ref([])
const parentRegions = ref([])
const totalRegions = ref(0)
const currentPage = ref(1)
const perPage = ref(20)
const loading = ref(false)
const expandedRegions = ref([])
const activeTab = ref({})
const showCreateModal = ref(false)
const originalRegions = ref({})

// Filters
const filters = reactive({
  search: '',
  type: '',
  parentId: ''
})

// New region form
const newRegion = reactive({
  name: '',
  type: 'state',
  parent_id: '',
  intro_text: ''
})

// Tabs configuration
const tabs = [
  { key: 'details', name: 'Details' },
  { key: 'facts', name: 'Facts' },
  { key: 'state-symbols', name: 'State Symbols' },
  { key: 'geodata', name: 'Geodata' },
  { key: 'featured-places', name: 'Featured Places' },
  { key: 'featured-lists', name: 'Featured Lists' },
  { key: 'featured-regions', name: 'Featured Regions' },
  { key: 'stats', name: 'Statistics' }
]

// Computed
const totalPages = computed(() => Math.ceil(totalRegions.value / perPage.value))

// Methods
const toggleRegion = (regionId) => {
  const index = expandedRegions.value.indexOf(regionId)
  if (index > -1) {
    expandedRegions.value.splice(index, 1)
  } else {
    expandedRegions.value.push(regionId)
    if (!activeTab.value[regionId]) {
      activeTab.value[regionId] = 'details'
    }
    // Store original data for cancel functionality
    const region = regions.value.find(r => r.id === regionId)
    originalRegions.value[regionId] = JSON.parse(JSON.stringify(region))
  }
}

const fetchRegions = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      search: filters.search,
      type: filters.type,
      parent_id: filters.parentId,
      with: 'parent,featuredEntries,featuredLists'
    }
    
    const response = await axios.get('/api/admin/regions', { params })
    console.log('Regions response:', response.data)
    regions.value = response.data.data
    totalRegions.value = response.data.total
    
    // Map featured relationships and initialize empty data structures
    regions.value.forEach(region => {
      region.featured_entries = region.featured_entries || []
      region.featured_lists = region.featured_lists || []
      region.facts = region.facts || {}
      region.state_symbols = region.state_symbols || {
        bird: {},
        flower: {},
        tree: {},
        mammal: {},
        fish: {},
        song: {},
        flag: {},
        seal: {},
        resources: {}
      }
      // Parse GeoJSON if it's a string
      if (region.geojson && typeof region.geojson === 'string') {
        try {
          region.geojson_string = region.geojson
          region.geojson = JSON.parse(region.geojson)
        } catch (e) {
          region.geojson_string = region.geojson
        }
      } else if (region.geojson) {
        region.geojson_string = JSON.stringify(region.geojson, null, 2)
      }
    })
  } catch (error) {
    console.error('Error fetching regions:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
      console.error('Error status:', error.response.status)
    }
    alert('Failed to fetch regions. Check console for details.')
  } finally {
    loading.value = false
  }
}

const fetchParentRegions = async () => {
  try {
    const response = await axios.get('/api/regions', {
      params: { type: 'state,city', limit: 100 }
    })
    parentRegions.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching parent regions:', error)
  }
}

const getValidParents = (region) => {
  if (region.type === 'state') return []
  if (region.type === 'city') return parentRegions.value.filter(p => p.type === 'state')
  return parentRegions.value.filter(p => p.type === 'city')
}

const getValidParentsForType = (type) => {
  if (type === 'state') return []
  if (type === 'city') return parentRegions.value.filter(p => p.type === 'state')
  return parentRegions.value.filter(p => p.type === 'city')
}

const saveRegion = async (region) => {
  try {
    // Parse GeoJSON if it's a string
    let geojsonData = null
    if (region.geojson_string) {
      try {
        geojsonData = JSON.parse(region.geojson_string)
      } catch (e) {
        // If it's not valid JSON, set it as null
        geojsonData = null
      }
    }
    
    const data = {
      name: region.name,
      slug: region.slug,
      type: region.type,
      level: region.level, // Include level field
      parent_id: region.parent_id || null,
      intro_text: region.intro_text,
      cloudflare_image_id: region.cloudflare_image_id,
      cover_image: region.cover_image_url, // Also update cover_image field
      facts: region.facts || {},
      state_symbols: region.state_symbols || {},
      geojson: geojsonData,
      polygon_coordinates: region.polygon_coordinates,
      is_featured: region.is_featured || false,
      display_priority: region.display_priority || 0
    }
    
    console.log('Sending data to save:', data)
    
    await axios.put(`/api/admin/regions/${region.id}`, data)
    
    // Update original data
    originalRegions.value[region.id] = JSON.parse(JSON.stringify(region))
    
    showSuccess('Saved', 'Region updated successfully')
  } catch (error) {
    console.error('Error saving region:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
      console.error('Error status:', error.response.status)
      showError('Save Failed', error.response.data.message || 'Server error')
    } else {
      showError('Network Error', 'Failed to save region')
    }
  }
}

const cancelEdit = (region) => {
  // Restore original data
  const original = originalRegions.value[region.id]
  if (original) {
    Object.assign(region, original)
  }
  
  // Collapse the region
  const index = expandedRegions.value.indexOf(region.id)
  if (index > -1) {
    expandedRegions.value.splice(index, 1)
  }
}

const createRegion = async () => {
  try {
    await axios.post('/api/admin/regions', newRegion)
    
    showCreateModal.value = false
    newRegion.name = ''
    newRegion.type = 'state'
    newRegion.parent_id = ''
    newRegion.intro_text = ''
    
    fetchRegions()
  } catch (error) {
    console.error('Error creating region:', error)
    alert('Failed to create region')
  }
}

const handleImageUpload = (event, region) => {
  console.log('Image upload event:', event)
  if (event.id) {
    region.cloudflare_image_id = event.id
    region.cover_image_url = event.url
    console.log('Set cloudflare_image_id to:', event.id)
  } else if (event.images && event.images.length > 0) {
    region.cloudflare_image_id = event.images[0].id
    region.cover_image_url = event.images[0].url
    console.log('Set cloudflare_image_id to:', event.images[0].id)
  }
}

const showAddFeaturedModal = (region) => {
  // TODO: Implement modal to add featured places
  alert('Feature coming soon: Add featured places')
}

const showAddFeaturedListModal = (region) => {
  // TODO: Implement modal to add featured lists
  alert('Feature coming soon: Add featured lists')
}

const removeFeaturedEntry = async (region, entry) => {
  if (!confirm('Remove this featured place?')) return
  
  try {
    await axios.delete(`/api/admin/regions/${region.id}/featured-entries/${entry.id}`)
    const index = region.featured_entries.findIndex(e => e.id === entry.id)
    if (index > -1) {
      region.featured_entries.splice(index, 1)
    }
  } catch (error) {
    console.error('Error removing featured entry:', error)
    alert('Failed to remove featured entry')
  }
}

const removeFeaturedList = async (region, list) => {
  if (!confirm('Remove this featured list?')) return
  
  try {
    await axios.delete(`/api/admin/regions/${region.id}/featured-lists/${list.id}`)
    const index = region.featured_lists.findIndex(l => l.id === list.id)
    if (index > -1) {
      region.featured_lists.splice(index, 1)
    }
  } catch (error) {
    console.error('Error removing featured list:', error)
    alert('Failed to remove featured list')
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

const deleteRegion = async (region) => {
  if (!confirm(`Are you sure you want to delete the region "${region.name}"? This action cannot be undone.`)) {
    return
  }
  
  try {
    await axios.delete(`/api/admin/regions/${region.id}`)
    
    // Remove the region from the list
    const index = regions.value.findIndex(r => r.id === region.id)
    if (index > -1) {
      regions.value.splice(index, 1)
    }
    
    // Also remove from expanded regions if it was expanded
    const expandedIndex = expandedRegions.value.indexOf(region.id)
    if (expandedIndex > -1) {
      expandedRegions.value.splice(expandedIndex, 1)
    }
    
    showSuccess('Deleted', 'Region deleted successfully')
  } catch (error) {
    console.error('Error deleting region:', error)
    
    // Show more specific error message
    if (error.response && error.response.data && error.response.data.error) {
      showError('Delete Failed', error.response.data.error)
    } else {
      showError('Delete Failed', 'Failed to delete region. Check console for details.')
    }
  }
}

const handleStateSymbolUpload = (event, region, symbolType) => {
  console.log('State symbol upload event:', event, symbolType)
  if (event.id && event.url) {
    // Ensure state_symbols structure exists
    if (!region.state_symbols) {
      region.state_symbols = {}
    }
    if (!region.state_symbols[symbolType]) {
      region.state_symbols[symbolType] = {}
    }
    
    // Update the image URL
    region.state_symbols[symbolType].image = event.url
    region.state_symbols[symbolType].cloudflare_id = event.id
    console.log(`Set ${symbolType} image to:`, event.url)
  } else if (event.images && event.images.length > 0) {
    // Ensure state_symbols structure exists
    if (!region.state_symbols) {
      region.state_symbols = {}
    }
    if (!region.state_symbols[symbolType]) {
      region.state_symbols[symbolType] = {}
    }
    
    // Update the image URL
    region.state_symbols[symbolType].image = event.images[0].url
    region.state_symbols[symbolType].cloudflare_id = event.images[0].id
    console.log(`Set ${symbolType} image to:`, event.images[0].url)
  }
}

// Lifecycle
onMounted(() => {
  fetchRegions()
  fetchParentRegions()
})
</script>