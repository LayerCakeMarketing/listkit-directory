<template>
  <div class="py-16">
      <!-- Header -->
      <div class="bg-white shadow">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex justify-between items-center py-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Region Management</h1>
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
      </div>

      <!-- Main Content -->
      <div class="mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                    <span>{{ region.entries_count || region.cached_place_count || 0 }} places</span>
                    <span>{{ region.featured_entries?.length || 0 }} featured</span>
                  </div>
                </div>
              </div>
              <div class="flex items-center space-x-3">
                <a
                  :href="getRegionUrl(region)"
                  target="_blank"
                  @click.stop
                  class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors"
                  title="View Region"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                </a>
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
                  @click="handleTabChange(region.id, tab.key)"
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
                      entity-type="Region"
                      :entity-id="region.id"
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
                <!-- Featured Places List -->
                <div class="mb-6">
                  <div class="mb-4 flex justify-between items-center">
                    <h4 class="text-sm font-medium text-gray-900">Featured Places ({{ region.featured_entries?.length || 0 }})</h4>
                  </div>
                  
                  <div v-if="region.featured_entries?.length > 0" class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <h5 class="text-sm font-medium text-green-800 mb-3">Currently Featured Places:</h5>
                    <div class="flex flex-wrap gap-2">
                      <div
                        v-for="entry in region.featured_entries"
                        :key="entry.id"
                        class="inline-flex items-center bg-white border border-green-300 rounded-full px-3 py-1 text-sm"
                      >
                        <span class="text-gray-700">{{ entry.title || entry.name }} (ID: {{ entry.id }})</span>
                        <span class="ml-2 text-gray-500">Priority: {{ entry.pivot?.priority || 0 }}</span>
                        <button
                          @click="removeFeaturedEntry(region, entry)"
                          class="ml-2 text-red-600 hover:text-red-800"
                          title="Remove"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Search and Filters -->
                <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="md:col-span-2">
                    <input
                      v-model="placeTableSearch"
                      type="text"
                      placeholder="Search places by name..."
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                      @input="searchPlacesForTable"
                    />
                  </div>
                  <div>
                    <select
                      v-model="placeTableCategory"
                      @change="fetchPlacesForTable"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                      <option value="">All Categories</option>
                      <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                      </option>
                    </select>
                  </div>
                </div>

                <!-- Places Table -->
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-if="loadingPlacesTable" class="text-center">
                        <td colspan="6" class="px-6 py-4 text-gray-500">Loading places...</td>
                      </tr>
                      <tr v-else-if="!placesTableData.length" class="text-center">
                        <td colspan="6" class="px-6 py-4 text-gray-500">No places found</td>
                      </tr>
                      <tr
                        v-else
                        v-for="place in placesTableData"
                        :key="place.id"
                        :class="isPlaceFeatured(region, place.id) ? 'bg-green-50' : 'hover:bg-gray-50'"
                      >
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ place.id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div>
                            <div class="text-sm font-medium text-gray-900">{{ place.title || place.name }}</div>
                            <div v-if="place.description" class="text-sm text-gray-500 truncate max-w-xs">{{ place.description }}</div>
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {{ place.category?.name || '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          <span v-if="place.cityRegion || place.stateRegion">
                            {{ place.cityRegion?.name || place.city }}, {{ place.stateRegion?.name || place.state }}
                          </span>
                          <span v-else>-</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                          <input
                            v-if="isPlaceFeatured(region, place.id)"
                            type="number"
                            :value="getFeaturedPlacePriority(region, place.id)"
                            @change="updateFeaturedPlacePriority(region, place, $event.target.value)"
                            min="0"
                            max="100"
                            class="w-16 mx-auto rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm text-center"
                          />
                          <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                          <button
                            v-if="!isPlaceFeatured(region, place.id)"
                            @click="addSingleFeaturedPlace(region, place)"
                            class="text-indigo-600 hover:text-indigo-900"
                          >
                            Add
                          </button>
                          <button
                            v-else
                            @click="removeFeaturedEntry(region, place)"
                            class="text-red-600 hover:text-red-900"
                          >
                            Remove
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  
                  <!-- Pagination -->
                  <div v-if="placesTablePagination.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                      <div class="text-sm text-gray-700">
                        Showing
                        <span class="font-medium">{{ placesTablePagination.from }}</span>
                        to
                        <span class="font-medium">{{ placesTablePagination.to }}</span>
                        of
                        <span class="font-medium">{{ placesTablePagination.total }}</span>
                        results
                      </div>
                      <div class="flex space-x-2">
                        <button
                          v-for="page in paginationRange"
                          :key="page"
                          @click="fetchPlacesForTable(page)"
                          :class="page === placesTablePagination.current_page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                          class="px-3 py-1 rounded-md text-sm font-medium border border-gray-300"
                        >
                          {{ page }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Quick Add by IDs -->
                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                  <h5 class="text-sm font-medium text-gray-900 mb-2">Quick Add by IDs</h5>
                  <div class="flex gap-2">
                    <input
                      v-model="quickAddIds"
                      type="text"
                      placeholder="Enter place IDs separated by commas (e.g., 1,3,90)"
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    />
                    <button
                      @click="quickAddFeaturedPlaces(region)"
                      :disabled="!quickAddIds.trim()"
                      class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Add Places
                    </button>
                  </div>
                </div>
                
                <!-- Category-Specific Featured Places -->
                <div class="mt-8 border-t pt-8">
                  <div class="mb-4 flex justify-between items-center">
                    <h4 class="text-sm font-medium text-gray-900">Category-Specific Featured Places</h4>
                    <button
                      @click="showCategoryFeaturedModal(region)"
                      class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-700 text-sm font-medium rounded-md hover:bg-purple-200"
                    >
                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                      Add by Category
                    </button>
                  </div>
                  
                  <div v-if="getCategoryFeaturedPlaces(region).length > 0" class="space-y-4">
                    <div v-for="category in getCategoryGroups(region)" :key="category.id" class="bg-purple-50 rounded-lg p-4">
                      <h5 class="font-medium text-gray-900 mb-3">{{ category.name }}</h5>
                      <div class="space-y-2">
                        <div
                          v-for="entry in getCategoryFeaturedPlaces(region, category.id)"
                          :key="entry.id"
                          class="flex items-center justify-between p-3 bg-white rounded-lg"
                        >
                          <div class="flex items-center space-x-3">
                            <img
                              v-if="entry.logo_url || entry.cover_image_url"
                              :src="entry.logo_url || entry.cover_image_url"
                              :alt="entry.title || entry.name"
                              class="w-10 h-10 rounded object-cover"
                            />
                            <div class="flex-1">
                              <p class="font-medium text-gray-900">{{ entry.title || entry.name }}</p>
                            </div>
                          </div>
                          <div class="flex items-center space-x-3">
                            <div class="flex items-center space-x-2">
                              <label class="text-sm text-gray-600">Priority:</label>
                              <input
                                type="number"
                                :value="entry.pivot?.priority || 0"
                                @change="updateFeaturedPlacePriority(region, entry, $event.target.value)"
                                min="0"
                                max="100"
                                class="w-16 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                              />
                            </div>
                            <button
                              @click="removeFeaturedEntry(region, entry)"
                              class="text-red-600 hover:text-red-800"
                              title="Remove featured place"
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
                  <div v-else class="text-center py-6 text-gray-500 bg-gray-50 rounded-lg">
                    No category-specific featured places yet
                  </div>
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

              <!-- Statistics Tab -->
              <div v-if="activeTab[region.id] === 'stats'">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-500">Total Places</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ region.entries_count || region.places_count || region.cached_place_count || 0 }}</p>
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
    <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="lg">
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">Create New Region</h3>
      </template>
      <template #default>
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <form @submit.prevent="createRegion" class="space-y-4">
              <div>
                <label for="region-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input
                  id="region-name"
                  v-model="newRegion.name"
                  type="text"
                  required
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="Enter region name"
                />
              </div>
              
              <div>
                <label for="region-type" class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                  id="region-type"
                  v-model="newRegion.type"
                  required
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  @change="newRegion.parent_id = ''; newRegion.state_filter = ''"
                >
                  <option value="state">State</option>
                  <option value="city">City</option>
                  <option value="neighborhood">Neighborhood</option>
                </select>
              </div>
              
              <!-- State filter for neighborhoods -->
              <div v-if="newRegion.type === 'neighborhood'">
                <label for="state-filter" class="block text-sm font-medium text-gray-700 mb-1">
                  Filter by State (Optional)
                </label>
                <select
                  id="state-filter"
                  v-model="newRegion.state_filter"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  @change="newRegion.parent_id = ''"
                >
                  <option value="">All States</option>
                  <option v-for="state in parentRegions.filter(p => p.type === 'state' || p.level === 1)" :key="state.id" :value="state.id">
                    {{ state.name }}
                  </option>
                </select>
              </div>
              
              <div v-if="newRegion.type !== 'state'">
                <label for="region-parent" class="block text-sm font-medium text-gray-700 mb-1">
                  Parent {{ newRegion.type === 'city' ? 'State' : 'City' }} <span class="text-red-500">*</span>
                </label>
                <select
                  id="region-parent"
                  v-model="newRegion.parent_id"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  :required="newRegion.type !== 'state'"
                >
                  <option value="">Select Parent Region</option>
                  <option v-for="parent in getValidParentsForType(newRegion.type)" :key="parent.id" :value="parent.id">
                    {{ parent.name }}{{ parent.parent && newRegion.type === 'neighborhood' ? ` (${parent.parent.name})` : '' }}
                  </option>
                </select>
                <p v-if="newRegion.type !== 'state' && getValidParentsForType(newRegion.type).length === 0" class="mt-1 text-sm text-red-600">
                  No {{ newRegion.type === 'city' ? 'states' : 'cities' }} available. Please create a {{ newRegion.type === 'city' ? 'state' : 'city' }} first.
                </p>
              </div>
              
              <div>
                <label for="region-description" class="block text-sm font-medium text-gray-700 mb-1">
                  Description (Optional)
                </label>
                <textarea
                  id="region-description"
                  v-model="newRegion.description"
                  rows="3"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="Enter a brief description of this region"
                ></textarea>
              </div>
              
              <div v-if="newRegion.type === 'state'">
                <label for="region-abbreviation" class="block text-sm font-medium text-gray-700 mb-1">
                  State Abbreviation
                </label>
                <input
                  id="region-abbreviation"
                  v-model="newRegion.abbreviation"
                  type="text"
                  maxlength="2"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="e.g., CA, NY"
                  style="text-transform: uppercase"
                />
              </div>
              
              <div class="flex justify-end pt-4 space-x-3">
                <button
                  type="button"
                  @click="showCreateModal = false"
                  class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  Create Region
                </button>
              </div>
            </form>
          </div>
        </div>
      </template>
    </Modal>
    
    <!-- Featured Places Modal -->
    <Modal :show="showFeaturedPlaceModal" @close="showFeaturedPlaceModal = false" max-width="4xl">
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">
          Add Featured Places to {{ selectedRegion?.name }}
        </h3>
      </template>
      
      <div class="p-6">
        <!-- Tab switcher for search method -->
        <div class="mb-6">
          <div class="flex space-x-4 mb-4">
            <button
              @click="featuredPlaceMethod = 'search'"
              :class="featuredPlaceMethod === 'search' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700'"
              class="px-4 py-2 rounded-md text-sm font-medium"
            >
              Search by Name
            </button>
            <button
              @click="featuredPlaceMethod = 'ids'"
              :class="featuredPlaceMethod === 'ids' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700'"
              class="px-4 py-2 rounded-md text-sm font-medium"
            >
              Enter IDs
            </button>
          </div>
        </div>

        <!-- Search Bar -->
        <div v-if="featuredPlaceMethod === 'search'" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Search Places</label>
          <div class="relative">
            <input
              v-model="searchPlaceQuery"
              @input="searchPlaces"
              type="text"
              placeholder="Search by name..."
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10"
            />
            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        <!-- ID Input -->
        <div v-if="featuredPlaceMethod === 'ids'" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Enter Place IDs</label>
          <input
            v-model="placeIdsInput"
            type="text"
            placeholder="Enter IDs separated by commas (e.g., 1,3,90)"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            @input="validatePlaceIds"
          />
          <p class="mt-1 text-sm text-gray-500">Enter place IDs separated by commas</p>
        </div>
        
        <!-- Search Results -->
        <div v-if="featuredPlaceMethod === 'search' && searchedPlaces.length > 0" class="mb-6">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Search Results</h4>
          <div class="space-y-2 max-h-96 overflow-y-auto">
            <div
              v-for="place in searchedPlaces"
              :key="place.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100"
            >
              <div class="flex items-center space-x-3">
                <input
                  type="checkbox"
                  :id="`place-${place.id}`"
                  :value="place.id"
                  v-model="selectedPlaces"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <label :for="`place-${place.id}`" class="cursor-pointer flex-1">
                  <p class="font-medium text-gray-900">{{ place.title }}</p>
                  <p class="text-sm text-gray-500">ID: {{ place.id }} • {{ place.category?.name }} • {{ place.cityRegion?.name || place.city }}, {{ place.stateRegion?.name || place.state }}</p>
                </label>
              </div>
              <div v-if="selectedPlaces.includes(place.id)" class="flex items-center space-x-2">
                <label class="text-sm text-gray-700">Priority:</label>
                <input
                  type="number"
                  v-model.number="featuredPlacePriorities[place.id]"
                  min="0"
                  max="100"
                  class="w-16 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                  placeholder="0"
                />
              </div>
            </div>
          </div>
        </div>
        
        <!-- Selected Places Summary -->
        <div v-if="selectedPlaces.length > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm text-blue-800">
            {{ selectedPlaces.length }} place{{ selectedPlaces.length > 1 ? 's' : '' }} selected
          </p>
        </div>
      </div>
      
      <template #footer>
        <button
          @click="showFeaturedPlaceModal = false"
          class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Cancel
        </button>
        <button
          @click="addFeaturedPlaces"
          :disabled="featuredPlaceMethod === 'search' ? selectedPlaces.length === 0 : !placeIdsInput"
          class="ml-3 px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Add {{ featuredPlaceMethod === 'ids' ? 'Places by IDs' : 'Selected Places' }}
        </button>
      </template>
    </Modal>
    
    <!-- Featured Lists Modal -->
    <Modal :show="showFeaturedListModal" @close="showFeaturedListModal = false" max-width="3xl">
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">
          Add Featured Lists to {{ selectedRegion?.name }}
        </h3>
      </template>
      
      <div class="p-6">
        <!-- Tab switcher for search method -->
        <div class="mb-6">
          <div class="flex space-x-4 mb-4">
            <button
              @click="featuredListMethod = 'search'"
              :class="featuredListMethod === 'search' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700'"
              class="px-4 py-2 rounded-md text-sm font-medium"
            >
              Search by Name
            </button>
            <button
              @click="featuredListMethod = 'ids'"
              :class="featuredListMethod === 'ids' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-700'"
              class="px-4 py-2 rounded-md text-sm font-medium"
            >
              Enter IDs
            </button>
          </div>
        </div>

        <!-- Search Bar -->
        <div v-if="featuredListMethod === 'search'" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Search Curated Lists</label>
          <div class="relative">
            <input
              v-model="searchListQuery"
              @input="searchLists"
              type="text"
              placeholder="Search by name..."
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10"
            />
            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <button @click="loadAllLists" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">
            Load all lists
          </button>
        </div>

        <!-- ID Input -->
        <div v-if="featuredListMethod === 'ids'" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Enter List IDs</label>
          <input
            v-model="listIdsInput"
            type="text"
            placeholder="Enter IDs separated by commas (e.g., 1,3,90)"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          />
          <p class="mt-1 text-sm text-gray-500">Enter list IDs separated by commas</p>
        </div>
        
        <!-- Search Results -->
        <div v-if="featuredListMethod === 'search' && searchedLists.length > 0" class="space-y-2">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Available Curated Lists</h4>
          <div
            v-for="list in searchedLists"
            :key="list.id"
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100"
          >
            <div>
              <p class="font-medium text-gray-900">{{ list.name || list.title }}</p>
              <p class="text-sm text-gray-500">ID: {{ list.id }} • {{ list.description }}</p>
              <p class="text-xs text-gray-400 mt-1">
                {{ list.items_count || list.place_ids?.length || 0 }} items
                <span v-if="list.user">• by {{ list.user.name }}</span>
              </p>
            </div>
            <button
              @click="addFeaturedList(list)"
              class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
            >
              Add
            </button>
          </div>
        </div>
        
        <div v-else-if="featuredListMethod === 'search' && searchListQuery.length >= 2" class="text-center py-8 text-gray-500">
          No lists found. Try clicking "Load all lists" or search for a different term.
        </div>

        <!-- Add lists by ID button -->
        <div v-if="featuredListMethod === 'ids' && listIdsInput" class="mt-4">
          <button
            @click="addListsByIds"
            class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700"
          >
            Add Lists by IDs
          </button>
        </div>
      </div>
    </Modal>
    
    <!-- Category-Specific Featured Places Modal -->
    <Modal :show="showCategoryPlaceModal" @close="showCategoryPlaceModal = false" max-width="4xl">
      <template #header>
        <h3 class="text-lg font-medium text-gray-900">
          Add Category-Specific Featured Places to {{ selectedRegion?.name }}
        </h3>
      </template>
      
      <div class="p-6">
        <!-- Category Selection -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Select Category</label>
          <select
            v-model="selectedCategory"
            @change="searchPlacesInCategory"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="">Choose a category...</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
        
        <!-- Search Bar (only shown after category is selected) -->
        <div v-if="selectedCategory" class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Search Places in Category</label>
          <div class="relative">
            <input
              v-model="searchPlaceQuery"
              @input="searchPlacesInCategory"
              type="text"
              placeholder="Search by name..."
              class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 pl-10"
            />
            <svg class="absolute left-3 top-3 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
        
        <!-- Search Results -->
        <div v-if="selectedCategory && searchedPlaces.length > 0" class="mb-6">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Available Places</h4>
          <div class="space-y-2 max-h-96 overflow-y-auto">
            <div
              v-for="place in searchedPlaces"
              :key="place.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100"
            >
              <div class="flex items-center space-x-3">
                <input
                  type="checkbox"
                  :value="place.id"
                  v-model="selectedPlaces"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <div>
                  <p class="font-medium text-gray-900">{{ place.title }}</p>
                  <p class="text-sm text-gray-500">{{ place.cityRegion?.name || place.city }}, {{ place.stateRegion?.name || place.state }}</p>
                </div>
              </div>
              <div v-if="selectedPlaces.includes(place.id)" class="flex items-center space-x-2">
                <label class="text-sm text-gray-700">Priority:</label>
                <input
                  type="number"
                  v-model.number="featuredPlacePriorities[place.id]"
                  min="0"
                  max="100"
                  class="w-16 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                  placeholder="0"
                />
              </div>
            </div>
          </div>
        </div>
        
        <div v-else-if="selectedCategory && searchPlaceQuery.length >= 2" class="text-center py-8 text-gray-500">
          No places found in this category
        </div>
        
        <!-- Selected Places Summary -->
        <div v-if="selectedPlaces.length > 0" class="bg-purple-50 border border-purple-200 rounded-lg p-4">
          <p class="text-sm text-purple-800">
            {{ selectedPlaces.length }} place{{ selectedPlaces.length > 1 ? 's' : '' }} selected for the selected category
          </p>
        </div>
      </div>
      
      <template #footer>
        <button
          @click="showCategoryPlaceModal = false"
          class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Cancel
        </button>
        <button
          @click="addCategoryFeaturedPlaces"
          :disabled="selectedPlaces.length === 0 || !selectedCategory"
          class="ml-3 px-4 py-2 bg-purple-600 text-white rounded-md text-sm font-medium hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Add Selected Places
        </button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import axios from 'axios'
import Modal from '@/components/Modal.vue'
import Pagination from '@/components/Pagination.vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import { useNotification } from '@/composables/useNotification'

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
const showFeaturedPlaceModal = ref(false)
const showFeaturedListModal = ref(false)
const selectedRegion = ref(null)
const searchPlaceQuery = ref('')
const searchedPlaces = ref([])
const searchListQuery = ref('')
const searchedLists = ref([])
const selectedPlaces = ref([])

const { showSuccess, showError } = useNotification()
const featuredPlacePriorities = ref({})
const showCategoryPlaceModal = ref(false)
const selectedCategory = ref('')
const categories = ref([])
const featuredPlaceMethod = ref('search')
const featuredListMethod = ref('search')
const placeIdsInput = ref('')
const listIdsInput = ref('')

// Table-based featured places data
const placeTableSearch = ref('')
const placeTableCategory = ref('')
const placesTableData = ref([])
const loadingPlacesTable = ref(false)
const placesTablePagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
})
const quickAddIds = ref('')

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
  intro_text: '',
  description: '',
  abbreviation: '',
  state_filter: '' // For filtering cities when selecting neighborhood parent
})

// Tabs configuration
const tabs = [
  { key: 'details', name: 'Details' },
  { key: 'facts', name: 'Facts' },
  { key: 'state-symbols', name: 'State Symbols' },
  { key: 'geodata', name: 'Geodata' },
  { key: 'featured-places', name: 'Featured Places' },
  { key: 'featured-lists', name: 'Featured Lists' },
  { key: 'stats', name: 'Statistics' }
]

// Computed
const totalPages = computed(() => Math.ceil(totalRegions.value / perPage.value))

const paginationRange = computed(() => {
  const currentPage = placesTablePagination.value.current_page
  const lastPage = placesTablePagination.value.last_page
  const range = []
  
  // Show max 5 pages
  let start = Math.max(1, currentPage - 2)
  let end = Math.min(lastPage, currentPage + 2)
  
  // Adjust if we're near the beginning or end
  if (currentPage <= 3) {
    end = Math.min(5, lastPage)
  }
  if (currentPage >= lastPage - 2) {
    start = Math.max(1, lastPage - 4)
  }
  
  for (let i = start; i <= end; i++) {
    range.push(i)
  }
  
  return range
})

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
    // Deep clone to preserve nested objects
    originalRegions.value[regionId] = JSON.parse(JSON.stringify(region))
    console.log('Stored original region data:', originalRegions.value[regionId])
    
    // If opening featured places tab, fetch places
    if (activeTab.value[regionId] === 'featured-places') {
      fetchPlacesForTable()
    }
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
      with: 'parent.parent,featuredEntries,featuredLists'
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
      
      // Initialize state_symbols with proper structure
      if (!region.state_symbols || typeof region.state_symbols !== 'object') {
        region.state_symbols = {}
      }
      
      // Ensure all nested objects exist
      const symbolTypes = ['bird', 'flower', 'tree', 'mammal', 'fish', 'song', 'flag', 'seal', 'resources']
      symbolTypes.forEach(type => {
        if (!region.state_symbols[type] || typeof region.state_symbols[type] !== 'object') {
          region.state_symbols[type] = {}
        }
      })
      
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
    showError('Error', 'Failed to fetch regions. Check console for details.')
  } finally {
    loading.value = false
  }
}

const fetchParentRegions = async () => {
  try {
    const response = await axios.get('/api/admin/regions', {
      params: { 
        per_page: 200,  // Get more regions
        with: 'parent'  // Include parent relationships
      }
    })
    parentRegions.value = response.data.data || []
    console.log('Loaded parent regions:', parentRegions.value.length, 'regions')
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
  console.log('Getting valid parents for type:', type)
  console.log('Available parent regions:', parentRegions.value)
  
  if (type === 'state') return []
  
  if (type === 'city') {
    const states = parentRegions.value.filter(p => p.type === 'state' || p.level === 1)
    console.log('Found states:', states)
    return states
  }
  
  // For neighborhoods, filter cities by selected state if state_filter is set
  if (type === 'neighborhood') {
    let cities = parentRegions.value.filter(p => p.type === 'city' || p.level === 2)
    
    if (newRegion.state_filter) {
      cities = cities.filter(city => city.parent_id == newRegion.state_filter)
    }
    
    console.log('Found cities:', cities)
    return cities
  }
  
  return []
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
    console.log('State symbols being sent:', JSON.stringify(data.state_symbols, null, 2))
    console.log('Facts being sent:', JSON.stringify(data.facts, null, 2))
    
    const response = await axios.put(`/api/admin/regions/${region.id}`, data)
    console.log('Save response:', response.data)
    
    // Update the region with the response data to ensure we have the latest server state
    if (response.data) {
      Object.assign(region, response.data)
      // Re-initialize nested structures if needed
      if (!region.state_symbols || typeof region.state_symbols !== 'object') {
        region.state_symbols = {}
      }
      const symbolTypes = ['bird', 'flower', 'tree', 'mammal', 'fish', 'song', 'flag', 'seal', 'resources']
      symbolTypes.forEach(type => {
        if (!region.state_symbols[type] || typeof region.state_symbols[type] !== 'object') {
          region.state_symbols[type] = {}
        }
      })
    }
    
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
    // Don't send state_filter to the server
    const { state_filter, ...regionData } = newRegion
    
    // Add level based on type
    regionData.level = regionData.type === 'state' ? 1 : (regionData.type === 'city' ? 2 : 3)
    
    // Ensure parent_id is null if empty string, or convert to number
    if (regionData.parent_id === '') {
      regionData.parent_id = null
    } else if (regionData.parent_id) {
      regionData.parent_id = parseInt(regionData.parent_id)
    }
    
    console.log('Sending region data:', regionData)
    
    await axios.post('/api/admin/regions', regionData)
    
    showCreateModal.value = false
    newRegion.name = ''
    newRegion.type = 'state'
    newRegion.parent_id = ''
    newRegion.intro_text = ''
    newRegion.description = ''
    newRegion.abbreviation = ''
    newRegion.state_filter = ''
    
    // Refresh both regions list and parent regions
    fetchRegions()
    fetchParentRegions()
    
    showSuccess('Success', 'Region created successfully')
  } catch (error) {
    console.error('Error creating region:', error)
    console.error('Error response:', error.response?.data)
    
    if (error.response?.data?.errors) {
      // Show validation errors
      const errors = error.response.data.errors
      const errorMessages = Object.keys(errors).map(field => `${field}: ${errors[field].join(', ')}`)
      showError('Validation Error', errorMessages.join('\n'))
    } else if (error.response?.data?.message) {
      showError('Error', error.response.data.message)
    } else {
      showError('Error', 'Failed to create region')
    }
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
  selectedRegion.value = region
  showFeaturedPlaceModal.value = true
  searchPlaceQuery.value = ''
  searchedPlaces.value = []
  selectedPlaces.value = []
  featuredPlacePriorities.value = {}
  featuredPlaceMethod.value = 'search'
  placeIdsInput.value = ''
}

const showAddFeaturedListModal = (region) => {
  selectedRegion.value = region
  showFeaturedListModal.value = true
  searchListQuery.value = ''
  searchedLists.value = []
  featuredListMethod.value = 'search'
  listIdsInput.value = ''
}

const removeFeaturedEntry = async (region, entry) => {
  try {
    await axios.delete(`/api/admin/regions/${region.id}/featured-entries/${entry.id}`)
    const index = region.featured_entries.findIndex(e => e.id === entry.id)
    if (index > -1) {
      region.featured_entries.splice(index, 1)
    }
  } catch (error) {
    console.error('Error removing featured entry:', error)
  }
}

const removeFeaturedList = async (region, list) => {
  try {
    await axios.delete(`/api/admin/regions/${region.id}/featured-lists/${list.id}`)
    const index = region.featured_lists.findIndex(l => l.id === list.id)
    if (index > -1) {
      region.featured_lists.splice(index, 1)
    }
  } catch (error) {
    console.error('Error removing featured list:', error)
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
    
    // Update the image URL using Vue.set equivalent for reactivity
    region.state_symbols[symbolType] = {
      ...region.state_symbols[symbolType],
      image: event.url,
      cloudflare_id: event.id
    }
    console.log(`Set ${symbolType} image to:`, event.url)
    console.log('Updated state_symbols:', JSON.stringify(region.state_symbols))
  } else if (event.images && event.images.length > 0) {
    // Ensure state_symbols structure exists
    if (!region.state_symbols) {
      region.state_symbols = {}
    }
    if (!region.state_symbols[symbolType]) {
      region.state_symbols[symbolType] = {}
    }
    
    // Update the image URL using Vue.set equivalent for reactivity
    region.state_symbols[symbolType] = {
      ...region.state_symbols[symbolType],
      image: event.images[0].url,
      cloudflare_id: event.images[0].id
    }
    console.log(`Set ${symbolType} image to:`, event.images[0].url)
    console.log('Updated state_symbols:', JSON.stringify(region.state_symbols))
  }
}

// Helper functions for category-specific featured places
const getCategoryFeaturedPlaces = (region, categoryId = null) => {
  const featured = region.featured_entries || []
  if (categoryId) {
    return featured.filter(entry => {
      // Check if the place has the specific category
      if (entry.category?.id === categoryId) return true
      
      // Also check the custom_data in pivot for category-specific featuring
      if (entry.pivot?.custom_data) {
        try {
          const customData = typeof entry.pivot.custom_data === 'string' 
            ? JSON.parse(entry.pivot.custom_data) 
            : entry.pivot.custom_data
          return customData.category_id === categoryId
        } catch (e) {
          return false
        }
      }
      return false
    })
  }
  // Return only entries that have custom_data with category_id
  return featured.filter(entry => {
    if (entry.pivot?.custom_data) {
      try {
        const customData = typeof entry.pivot.custom_data === 'string' 
          ? JSON.parse(entry.pivot.custom_data) 
          : entry.pivot.custom_data
        return !!customData.category_id
      } catch (e) {
        return false
      }
    }
    return false
  })
}

const getCategoryGroups = (region) => {
  const featured = region.featured_entries || []
  const categories = new Map()
  
  featured.forEach(entry => {
    // Check for category-specific featuring in custom_data
    if (entry.pivot?.custom_data) {
      try {
        const customData = typeof entry.pivot.custom_data === 'string' 
          ? JSON.parse(entry.pivot.custom_data) 
          : entry.pivot.custom_data
        if (customData.category_id && entry.category) {
          categories.set(entry.category.id, entry.category)
        }
      } catch (e) {
        // Ignore parsing errors
      }
    }
  })
  
  return Array.from(categories.values())
}

const showCategoryFeaturedModal = (region) => {
  selectedRegion.value = region
  showCategoryPlaceModal.value = true
  selectedCategory.value = ''
  searchPlaceQuery.value = ''
  searchedPlaces.value = []
  selectedPlaces.value = []
  featuredPlacePriorities.value = {}
}

// Featured Places Functions
const searchPlaces = async () => {
  if (!searchPlaceQuery.value || searchPlaceQuery.value.length < 2) {
    searchedPlaces.value = []
    return
  }
  
  try {
    const response = await axios.get('/api/places', {
      params: {
        q: searchPlaceQuery.value,
        per_page: 20
      }
    })
    // API returns 'entries' not 'places'
    searchedPlaces.value = response.data.entries?.data || response.data.entries || []
  } catch (error) {
    console.error('Error searching places:', error)
  }
}

const addFeaturedPlaces = async () => {
  if (!selectedRegion.value) return
  
  let placeIds = []
  
  if (featuredPlaceMethod.value === 'ids') {
    // Parse IDs from input
    placeIds = placeIdsInput.value.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id))
    if (placeIds.length === 0) {
      console.log('No valid place IDs entered')
      return
    }
  } else {
    // Use selected places from search
    if (selectedPlaces.value.length === 0) return
    placeIds = selectedPlaces.value
  }
  
  try {
    // Add each place as a featured place
    for (const placeId of placeIds) {
      const priority = featuredPlacePriorities.value[placeId] || 0
      await axios.post(`/api/admin/regions/${selectedRegion.value.id}/featured-places`, {
        place_id: placeId,
        priority: priority
      })
    }
    
    // Refresh the region data
    await fetchRegions()
    
    // Close modal and reset
    showFeaturedPlaceModal.value = false
    selectedPlaces.value = []
    featuredPlacePriorities.value = {}
    searchPlaceQuery.value = ''
    searchedPlaces.value = []
    placeIdsInput.value = ''
    
    console.log(`Featured places added successfully (${placeIds.length} places)`)
  } catch (error) {
    console.error('Error adding featured places:', error)
  }
}

// Featured Lists Functions
const searchLists = async () => {
  if (!searchListQuery.value || searchListQuery.value.length < 2) {
    searchedLists.value = []
    return
  }
  
  try {
    const response = await axios.get('/api/admin/lists', {
      params: {
        search: searchListQuery.value,
        per_page: 20
      }
    })
    searchedLists.value = response.data.data || []
  } catch (error) {
    console.error('Error searching lists:', error)
  }
}

const loadAllLists = async () => {
  try {
    const response = await axios.get('/api/admin/lists', {
      params: {
        per_page: 100
      }
    })
    searchedLists.value = response.data.data || []
  } catch (error) {
    console.error('Error loading all lists:', error)
  }
}

const addFeaturedList = async (list) => {
  if (!selectedRegion.value) return
  
  try {
    await axios.post(`/api/admin/regions/${selectedRegion.value.id}/featured-lists`, {
      list_id: list.id,
      priority: 0
    })
    
    // Refresh the region data
    await fetchRegions()
    
    // Close modal and reset
    showFeaturedListModal.value = false
    searchListQuery.value = ''
    searchedLists.value = []
    
    console.log('Featured list added successfully')
  } catch (error) {
    console.error('Error adding featured list:', error)
  }
}

// Update featured place priority
const updateFeaturedPlacePriority = async (region, place, newPriority) => {
  try {
    await axios.put(`/api/admin/regions/${region.id}/featured-places/${place.id}`, {
      priority: newPriority
    })
    
    // Update local data
    const placeIndex = region.featured_entries.findIndex(p => p.id === place.id)
    if (placeIndex > -1) {
      region.featured_entries[placeIndex].pivot = { 
        ...region.featured_entries[placeIndex].pivot, 
        priority: newPriority 
      }
    }
    
    // Sort by priority
    region.featured_entries.sort((a, b) => (b.pivot?.priority || 0) - (a.pivot?.priority || 0))
  } catch (error) {
    console.error('Error updating place priority:', error)
  }
}

// Category-specific featured places functions
const searchPlacesInCategory = async () => {
  if (!selectedCategory.value) {
    searchedPlaces.value = []
    return
  }
  
  if (!searchPlaceQuery.value || searchPlaceQuery.value.length < 2) {
    // If no search query, fetch all places in the category
    try {
      const response = await axios.get('/api/places', {
        params: {
          category_id: selectedCategory.value,
          per_page: 50
        }
      })
      searchedPlaces.value = response.data.entries?.data || response.data.entries || []
    } catch (error) {
      console.error('Error fetching category places:', error)
    }
  } else {
    // Search within the category
    try {
      const response = await axios.get('/api/places', {
        params: {
          q: searchPlaceQuery.value,
          category_id: selectedCategory.value,
          per_page: 20
        }
      })
      searchedPlaces.value = response.data.entries?.data || response.data.entries || []
    } catch (error) {
      console.error('Error searching category places:', error)
    }
  }
}

const addCategoryFeaturedPlaces = async () => {
  if (!selectedRegion.value || selectedPlaces.value.length === 0 || !selectedCategory.value) return
  
  try {
    // Add each selected place as a featured place with category info
    for (const placeId of selectedPlaces.value) {
      const priority = featuredPlacePriorities.value[placeId] || 0
      await axios.post(`/api/admin/regions/${selectedRegion.value.id}/featured-places`, {
        place_id: placeId,
        priority: priority,
        category_id: selectedCategory.value
      })
    }
    
    // Refresh the region data
    await fetchRegions()
    
    // Close modal and reset
    showCategoryPlaceModal.value = false
    selectedPlaces.value = []
    featuredPlacePriorities.value = {}
    searchPlaceQuery.value = ''
    searchedPlaces.value = []
    selectedCategory.value = ''
    
    console.log('Category-specific featured places added successfully')
  } catch (error) {
    console.error('Error adding category featured places:', error)
  }
}

const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/categories')
    categories.value = response.data.categories || response.data
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
}

const addListsByIds = async () => {
  if (!selectedRegion.value || !listIdsInput.value) return
  
  const listIds = listIdsInput.value.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id))
  
  if (listIds.length === 0) {
    console.log('No valid list IDs entered')
    return
  }
  
  try {
    // Add each list as a featured list
    for (const listId of listIds) {
      await axios.post(`/api/admin/regions/${selectedRegion.value.id}/featured-lists`, {
        list_id: listId,
        priority: 0
      })
    }
    
    // Refresh the region data
    await fetchRegions()
    
    // Close modal and reset
    showFeaturedListModal.value = false
    searchListQuery.value = ''
    searchedLists.value = []
    listIdsInput.value = ''
    
    console.log(`Featured lists added successfully (${listIds.length} lists)`)
  } catch (error) {
    console.error('Error adding featured lists:', error)
  }
}

const getRegionUrl = (region) => {
  // Debug logging
  console.log('Building URL for region:', {
    id: region.id,
    name: region.name,
    slug: region.slug,
    type: region.type,
    level: region.level,
    parent: region.parent,
    parent_state: region.parent_state
  })
  
  // Build hierarchical URL based on region type/level
  if (region.level === 1 || region.type === 'state') {
    // State: /regions/{state-slug}
    return `/regions/${region.slug}`
  } else if (region.level === 2 || region.type === 'city') {
    // City: /regions/{state-slug}/{city-slug}
    const stateSlug = region.parent?.slug || region.parent_state?.slug
    if (!stateSlug) {
      console.warn('City region missing parent state:', region)
      return `/regions/${region.slug}` // Fallback
    }
    const url = `/regions/${stateSlug}/${region.slug}`
    console.log(`City URL: ${url}`)
    return url
  } else if (region.level === 3 || region.type === 'neighborhood') {
    // Neighborhood: /regions/{state-slug}/{city-slug}/{neighborhood-slug}
    let stateSlug, citySlug
    
    // Get city (immediate parent)
    const city = region.parent
    if (city) {
      citySlug = city.slug
      // Get state (parent's parent)
      stateSlug = city.parent?.slug
    }
    
    if (!stateSlug || !citySlug) {
      console.warn('Neighborhood region missing parent hierarchy:', region)
      return `/regions/${region.slug}` // Fallback
    }
    
    const url = `/regions/${stateSlug}/${citySlug}/${region.slug}`
    console.log(`Neighborhood URL: ${url}`)
    return url
  }
  
  // Default fallback
  return `/regions/${region.slug}`
}

const validatePlaceIds = () => {
  // Optional: Add validation logic here
}

// Table-based featured places methods
const fetchPlacesForTable = async (page = 1) => {
  loadingPlacesTable.value = true
  console.log('Fetching places for table, page:', page)
  try {
    const params = {
      page: page,
      per_page: 20
    }
    
    // Add optional parameters only if they have values
    if (placeTableSearch.value) {
      params.q = placeTableSearch.value
    }
    if (placeTableCategory.value) {
      params.category_id = placeTableCategory.value
    }
    
    console.log('Fetching places with params:', params)
    const response = await axios.get('/api/admin/places', { params })
    console.log('Places response:', response.data)
    
    // Handle different response structures
    if (response.data.data) {
      // Paginated response
      placesTableData.value = response.data.data
      placesTablePagination.value = {
        current_page: response.data.current_page || 1,
        last_page: response.data.last_page || 1,
        from: response.data.from || 0,
        to: response.data.to || 0,
        total: response.data.total || 0
      }
    } else if (response.data.entries) {
      // Entries response
      if (response.data.entries.data) {
        placesTableData.value = response.data.entries.data
        placesTablePagination.value = {
          current_page: response.data.entries.current_page || 1,
          last_page: response.data.entries.last_page || 1,
          from: response.data.entries.from || 0,
          to: response.data.entries.to || 0,
          total: response.data.entries.total || 0
        }
      } else {
        placesTableData.value = response.data.entries
      }
    } else {
      // Direct array response
      placesTableData.value = response.data
    }
    
    console.log('Places loaded:', placesTableData.value.length)
  } catch (error) {
    console.error('Error fetching places for table:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
      console.error('Error status:', error.response.status)
    }
    placesTableData.value = []
  } finally {
    loadingPlacesTable.value = false
  }
}

const searchPlacesForTable = debounce(() => {
  placesTablePagination.value.current_page = 1
  fetchPlacesForTable(1)
}, 300)

const isPlaceFeatured = (region, placeId) => {
  return region.featured_entries?.some(entry => entry.id === placeId) || false
}

const getFeaturedPlacePriority = (region, placeId) => {
  const entry = region.featured_entries?.find(e => e.id === placeId)
  return entry?.pivot?.priority || 0
}

const addSingleFeaturedPlace = async (region, place) => {
  try {
    await axios.post(`/api/admin/regions/${region.id}/featured-places`, {
      place_id: place.id,
      priority: 0
    })
    
    // Add to the featured entries locally
    if (!region.featured_entries) {
      region.featured_entries = []
    }
    region.featured_entries.push({
      ...place,
      pivot: { priority: 0 }
    })
  } catch (error) {
    console.error('Error adding featured place:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
    }
  }
}

const quickAddFeaturedPlaces = async (region) => {
  if (!quickAddIds.value.trim()) return
  
  const placeIds = quickAddIds.value.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id))
  
  if (placeIds.length === 0) {
    console.log('No valid place IDs entered')
    return
  }
  
  try {
    let addedCount = 0
    for (const placeId of placeIds) {
      // Skip if already featured
      if (isPlaceFeatured(region, placeId)) continue
      
      await axios.post(`/api/admin/regions/${region.id}/featured-places`, {
        place_id: placeId,
        priority: 0
      })
      addedCount++
    }
    
    // Refresh the region data
    await fetchRegions()
    
    // Clear the input
    quickAddIds.value = ''
    
    if (addedCount > 0) {
      console.log(`Added ${addedCount} featured place(s)`)
    } else {
      console.log('All specified places are already featured')
    }
  } catch (error) {
    console.error('Error adding featured places:', error)
  }
}

// Debounce helper
function debounce(func, wait) {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Watch for tab changes to load places when needed
const handleTabChange = (regionId, tabKey) => {
  activeTab.value[regionId] = tabKey
  if (tabKey === 'featured-places' && placesTableData.value.length === 0) {
    fetchPlacesForTable()
  }
}

// Lifecycle
onMounted(() => {
  fetchRegions()
  fetchParentRegions()
  fetchCategories()
})
</script>