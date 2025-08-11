<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Breadcrumb -->
      <nav class="mb-4">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
          <li>
            <router-link to="/local" class="hover:text-gray-700">Local</router-link>
          </li>
          <li>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </li>
          <li>
            <router-link :to="`/local/${state}`" class="hover:text-gray-700">{{ stateData?.name || state }}</router-link>
          </li>
          <template v-if="isNeighborhood">
            <li>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li>
              <router-link :to="`/local/${state}/${city}`" class="hover:text-gray-700">{{ cityData?.parent?.name || city }}</router-link>
            </li>
            <li>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="font-medium text-gray-900">{{ cityData?.name || neighborhood }}</li>
          </template>
          <template v-else>
            <li>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </li>
            <li class="font-medium text-gray-900">{{ cityData?.name || city }}</li>
          </template>
        </ol>
      </nav>

      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">
              {{ cityData?.name || 'Loading...' }}<span v-if="isNeighborhood && cityData?.parent">, {{ cityData.parent.name }}</span><span v-if="!isNeighborhood && stateData">, {{ stateData.name }}</span>
            </h1>
            <p v-if="cityData?.description" class="mt-2 text-lg text-gray-600">{{ cityData.description }}</p>
          </div>
          <div class="flex items-center space-x-3">
            <!-- Explorer View Button (for neighborhoods and parks) -->
            <router-link
              v-if="cityData?.id && (isNeighborhood || cityData?.park_designation)"
              :to="`/regions/${cityData.id}/explore`"
              class="inline-flex items-center px-4 py-2 border border-green-600 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
            >
              <MapIcon class="w-5 h-5 mr-2" />
              Explorer View
            </router-link>
            <SaveButton
              v-if="authStore.isAuthenticated && cityData?.id"
              item-type="region"
              :item-id="cityData.id"
              :initial-saved="cityData.is_saved || false"
            />
            <QRCodeGenerator
              v-if="cityData"
              type="region"
              :data="cityData"
              button-text="QR Code"
              :show-button-text="false"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-800">{{ error }}</p>
      </div>

      <!-- Content -->
      <div v-else>
        <!-- Cover Image -->
        <div v-if="cityData?.cover_image_url" class="mb-8 rounded-lg overflow-hidden shadow-lg">
          <img 
            :src="cityData.cover_image_url" 
            :alt="cityData.name"
            class="w-full h-64 object-cover"
          >
        </div>

        <!-- Intro Text -->
        <div v-if="cityData?.intro_text" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <p class="text-gray-700 whitespace-pre-line">{{ cityData.intro_text }}</p>
        </div>

        <!-- Stats - Only show for cities, not neighborhoods -->
        <div v-if="cityData && !isNeighborhood" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Total Places</div>
            <div class="text-2xl font-bold text-gray-900">{{ cityData.entries_count || places.length }}</div>
          </div>
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Featured Places</div>
            <div class="text-2xl font-bold text-gray-900">{{ featuredPlaces.length }}</div>
          </div>
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Categories</div>
            <div class="text-2xl font-bold text-gray-900">{{ uniqueCategories }}</div>
          </div>
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="text-sm text-gray-500">Neighborhoods</div>
            <div class="text-2xl font-bold text-gray-900">{{ neighborhoods.length }}</div>
          </div>
        </div>

        <!-- Region Details Tab -->
        <RegionDetailsTab
          v-if="cityData"
          :region="cityData"
          class="mb-8"
        />

        <!-- Featured Places -->
        <div v-if="featuredPlaces.length > 0" class="mb-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Featured Places</h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <router-link
                v-for="place in featuredPlaces"
                :key="place.id"
                :to="place.canonical_url || `/p/${place.id}`"
                class="group block rounded-lg border-2 border-purple-200 hover:border-purple-500 hover:shadow-md transition-all duration-200 overflow-hidden bg-purple-50"
              >
                <div v-if="place.featured_image_url" class="aspect-w-16 aspect-h-9">
                  <img 
                    :src="place.featured_image_url" 
                    :alt="place.title"
                    class="w-full h-48 object-cover"
                  >
                </div>
                <div class="p-4">
                  <h3 class="text-lg font-medium text-gray-900 group-hover:text-purple-600">
                    {{ place.title }}
                  </h3>
                  <p v-if="place.category" class="text-sm text-gray-600">{{ place.category.name }}</p>
                  <p v-if="place.pivot?.tagline" class="text-sm text-gray-500 mt-1">{{ place.pivot.tagline }}</p>
                  <div class="flex items-center gap-2 mt-2">
                    <span class="text-purple-600">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </span>
                    <span class="text-sm text-purple-600 font-medium">Featured</span>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Featured Lists -->
        <div v-if="featuredLists.length > 0" class="mb-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Curated Lists</h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <router-link
                v-for="list in featuredLists"
                :key="list.id"
                :to="`/lists/${list.slug}`"
                class="group block rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden"
              >
                <div v-if="list.featured_image_url" class="aspect-w-16 aspect-h-9">
                  <img 
                    :src="list.featured_image_url" 
                    :alt="list.name"
                    class="w-full h-32 object-cover"
                  >
                </div>
                <div class="p-4">
                  <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600">
                    {{ list.name }}
                  </h3>
                  <p v-if="list.description" class="text-sm text-gray-500 mt-1 line-clamp-2">
                    {{ list.description }}
                  </p>
                  <div class="flex items-center gap-2 mt-2 text-sm text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span>{{ list.items_count || 0 }} places</span>
                  </div>
                </div>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Quick Links -->
        <div v-if="categories.length > 0" class="mb-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Browse by Category</h2>
          </div>
          <div class="p-6">
            <div class="flex flex-wrap gap-2">
              <router-link
                v-for="category in categories"
                :key="category.id"
                :to="`/places/${state}/${city}/${category.slug}`"
                class="inline-flex items-center px-4 py-2 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors"
              >
                {{ category.name }}
                <span class="ml-2 text-xs bg-gray-200 px-2 py-0.5 rounded-full">
                  {{ category.count }}
                </span>
              </router-link>
            </div>
          </div>
        </div>

        <!-- Places/Regions List with Map -->
        <div class="bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
              <span v-if="isNeighborhood">All Places in {{ cityData?.name }}</span>
              <span v-else>Explore {{ cityData?.name }}</span>
            </h2>
          </div>
          
          <div class="p-6">
            <!-- Two-column layout for cities: Show neighborhoods and parks -->
            <div v-if="!isNeighborhood && (neighborhoods.length > 0 || parkRegions.length > 0)" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
              <!-- Left Column - Neighborhoods and Parks List (40% width) -->
              <div class="lg:col-span-2 space-y-4 overflow-y-auto" style="max-height: 90vh;">
                <!-- Neighborhoods Section -->
                <div v-if="neighborhoods.length > 0">
                  <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Neighborhoods</h3>
                  <div class="space-y-3">
                    <div
                      v-for="neighborhood in neighborhoods"
                      :key="neighborhood.id"
                      @click="handleRegionClick(neighborhood)"
                      @mouseenter="hoveredRegionId = neighborhood.id"
                      @mouseleave="hoveredRegionId = null"
                      class="group cursor-pointer rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden"
                    >
                      <!-- Neighborhood Cover Image -->
                      <div v-if="neighborhood.cover_image_url" class="relative h-24">
                        <img 
                          :src="neighborhood.cover_image_url" 
                          :alt="neighborhood.name"
                          class="w-full h-full object-cover"
                        >
                      </div>
                      
                      <!-- Neighborhood Info -->
                      <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                          <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600 flex-1">
                            {{ neighborhood.name }}
                          </h3>
                          <span v-if="neighborhood.entries_count > 0" class="text-sm text-gray-500">
                            {{ neighborhood.entries_count }} places
                          </span>
                        </div>
                        
                        <p v-if="neighborhood.description" class="text-sm text-gray-500 line-clamp-2">
                          {{ stripHtml(neighborhood.description) }}
                        </p>
                        
                        <!-- View Details Link -->
                        <router-link
                          :to="`/local/${state}/${city}/${neighborhood.slug}`"
                          @click.stop
                          class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 mt-3"
                        >
                          View Neighborhood
                          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                          </svg>
                        </router-link>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Parks Section -->
                <div v-if="parkRegions.length > 0">
                  <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Parks & Recreation</h3>
                  <div class="space-y-3">
                    <div
                      v-for="park in parkRegions"
                      :key="park.id"
                      @click="handleRegionClick(park)"
                      @mouseenter="hoveredRegionId = park.id"
                      @mouseleave="hoveredRegionId = null"
                      class="group cursor-pointer rounded-lg border border-green-200 hover:border-green-500 hover:shadow-md transition-all duration-200 overflow-hidden bg-green-50"
                    >
                      <!-- Park Cover Image -->
                      <div v-if="park.cover_image_url" class="relative h-24">
                        <img 
                          :src="park.cover_image_url" 
                          :alt="park.name"
                          class="w-full h-full object-cover"
                        >
                        <div class="absolute top-2 left-2 bg-green-600 text-white px-2 py-1 rounded text-xs font-medium">
                          {{ park.park_designation || 'Park' }}
                        </div>
                      </div>
                      
                      <!-- Park Info -->
                      <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                          <h3 class="text-lg font-medium text-gray-900 group-hover:text-green-600 flex-1">
                            {{ park.name }}
                          </h3>
                          <span v-if="park.entries_count > 0" class="text-sm text-gray-500">
                            {{ park.entries_count }} places
                          </span>
                        </div>
                        
                        <p v-if="park.description" class="text-sm text-gray-500 line-clamp-2">
                          {{ stripHtml(park.description) }}
                        </p>
                        
                        <!-- View Details Link -->
                        <router-link
                          :to="`/regions/${park.id}/explore`"
                          @click.stop
                          class="inline-flex items-center text-sm font-medium text-green-600 hover:text-green-800 mt-3"
                        >
                          Explore Park
                          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                          </svg>
                        </router-link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Right Column - Map (60% width) -->
              <div class="lg:col-span-3 relative">
                <div 
                  ref="mapContainer"
                  id="city-map"
                  class="w-full h-[90vh] rounded-lg shadow-inner"
                >
                  <div v-if="mapLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100 rounded-lg">
                    <div class="text-center">
                      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                      <p class="mt-3 text-sm text-gray-600">Loading map...</p>
                    </div>
                  </div>
                  
                  <div v-if="mapError" class="absolute inset-0 flex items-center justify-center bg-red-50 rounded-lg">
                    <div class="text-center">
                      <svg class="w-12 h-12 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      <p class="mt-3 text-sm text-red-600">{{ mapError }}</p>
                    </div>
                  </div>
                </div>
                
                <!-- Map Style Selector -->
                <div class="absolute top-4 left-4 bg-white/95 backdrop-blur-sm rounded-lg shadow-lg">
                  <select 
                    v-model="currentMapStyle" 
                    @change="changeMapStyle"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-transparent border-0 rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <!-- Standard Mapbox Styles -->
                    <option value="mapbox://styles/mapbox/streets-v12">Streets</option>
                    <option value="mapbox://styles/mapbox/outdoors-v12">Outdoors</option>
                    <option value="mapbox://styles/mapbox/light-v11">Light</option>
                    <option value="mapbox://styles/mapbox/dark-v11">Dark</option>
                    <option value="mapbox://styles/mapbox/satellite-v9">Satellite</option>
                    <option value="mapbox://styles/mapbox/satellite-streets-v12">Hybrid</option>
                    
                    <!-- Navigation Styles -->
                    <option value="mapbox://styles/mapbox/navigation-day-v1">Navigation Day</option>
                    <option value="mapbox://styles/mapbox/navigation-night-v1">Navigation Night</option>
                    
                    <!-- Custom styles from Mapbox Studio -->
                    <option value="mapbox://styles/illuminatelocal/clf0ha89i000a01o7fyggdklz">Illuminate Local</option>
                  </select>
                </div>
                
                <!-- Map Legend -->
                <div class="absolute bottom-4 right-4 bg-white/95 backdrop-blur-sm rounded-lg shadow-lg p-3">
                  <div class="text-xs font-semibold text-gray-700 mb-2">Legend</div>
                  <div class="space-y-1">
                    <div class="flex items-center">
                      <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                      <span class="text-xs text-gray-600">Neighborhoods</span>
                    </div>
                    <div class="flex items-center">
                      <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                      <span class="text-xs text-gray-600">Parks</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Two-column layout for neighborhoods: Places list on left (40%), map on right (60%) -->
            <div v-else-if="places.length > 0 && isNeighborhood" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
              <!-- Left Column - Places List (40% width) -->
              <div class="lg:col-span-2 space-y-4 overflow-y-auto" style="max-height: 90vh;">
                <div
                  v-for="place in places"
                  :key="place.id"
                  @click="handlePlaceClick(place)"
                  @mouseenter="hoveredPlaceId = place.id"
                  @mouseleave="hoveredPlaceId = null"
                  class="group cursor-pointer rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden"
                >
                  <!-- Cover Image -->
                  <div v-if="place.cover_image_url || place.featured_image_url || place.logo_url" class="relative h-32">
                    <img 
                      :src="place.cover_image_url || place.featured_image_url || place.logo_url" 
                      :alt="place.title"
                      class="w-full h-full object-cover"
                    >
                    <div v-if="place.is_featured" class="absolute top-2 left-2 bg-purple-600 text-white px-2 py-1 rounded text-xs font-medium">
                      Featured
                    </div>
                  </div>
                  
                  <!-- Place Info -->
                  <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                      <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600 flex-1">
                        {{ place.title }}
                      </h3>
                      <span v-if="place.is_verified" class="text-blue-600 ml-2" title="Verified">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                      </span>
                    </div>
                    
                    <p v-if="place.category" class="text-sm text-gray-600 mb-1">
                      {{ place.category.name }}
                    </p>
                    
                    <p v-if="place.description" class="text-sm text-gray-500 line-clamp-2 mb-2">
                      {{ stripHtml(place.description) }}
                    </p>
                    
                    <p v-if="place.location?.address_line1" class="text-xs text-gray-400 mb-3">
                      <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      {{ place.location.address_line1 }}
                    </p>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center justify-between">
                      <router-link
                        :to="place.canonical_url || `/p/${place.id}`"
                        @click.stop
                        class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800"
                      >
                        View Details
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                      </router-link>
                      
                      <!-- Directions Link -->
                      <a
                        v-if="place.location?.latitude && place.location?.longitude"
                        :href="`https://www.google.com/maps/dir/Current+Location/${place.location.latitude},${place.location.longitude}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        @click.stop
                        class="inline-flex items-center text-sm font-medium text-green-600 hover:text-green-800"
                        title="Get directions"
                      >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Directions
                      </a>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Right Column - Map (60% width) -->
              <div class="lg:col-span-3 relative bg-gray-100 rounded-lg overflow-hidden" style="height: 90vh;">
                <div id="neighborhood-map" class="w-full h-full" ref="mapContainer"></div>
                
                <!-- Map Loading State -->
                <div v-if="mapLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                  <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-gray-600">Loading map...</p>
                  </div>
                </div>
                
                <!-- Map Error State -->
                <div v-if="mapError" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                  <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-red-600">{{ mapError }}</p>
                  </div>
                </div>
                
                <!-- Map Style Selector -->
                <div class="absolute top-4 left-4 bg-white/95 backdrop-blur-sm rounded-lg shadow-lg">
                  <select 
                    v-model="currentMapStyle" 
                    @change="changeMapStyle"
                    class="px-3 py-2 text-sm font-medium text-gray-700 bg-transparent border-0 rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <!-- Standard Mapbox Styles -->
                    <option value="mapbox://styles/mapbox/streets-v12">Streets</option>
                    <option value="mapbox://styles/mapbox/outdoors-v12">Outdoors</option>
                    <option value="mapbox://styles/mapbox/light-v11">Light</option>
                    <option value="mapbox://styles/mapbox/dark-v11">Dark</option>
                    <option value="mapbox://styles/mapbox/satellite-v9">Satellite</option>
                    <option value="mapbox://styles/mapbox/satellite-streets-v12">Hybrid</option>
                    
                    <!-- Navigation Styles -->
                    <option value="mapbox://styles/mapbox/navigation-day-v1">Navigation Day</option>
                    <option value="mapbox://styles/mapbox/navigation-night-v1">Navigation Night</option>
                    
                    <!-- Custom styles from Mapbox Studio -->
                    <option value="mapbox://styles/illuminatelocal/clf0ha89i000a01o7fyggdklz">Illuminate Local</option>
                  </select>
                </div>
                
                <!-- Map Legend -->
                <div class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-sm rounded-lg p-3 shadow-lg">
                  <div class="text-xs font-medium text-gray-700 mb-1">Legend</div>
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                      <span class="text-xs text-gray-600">Place</span>
                    </div>
                    <div v-if="featuredPlaces.length > 0" class="flex items-center gap-2">
                      <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                      <span class="text-xs text-gray-600">Featured</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Regular list for cities (non-neighborhoods) -->
            <div v-else-if="places.length > 0 && !isNeighborhood" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <router-link
                v-for="place in places"
                :key="place.id"
                :to="place.canonical_url || `/p/${place.id}`"
                class="group block rounded-lg border border-gray-200 hover:border-blue-500 hover:shadow-md transition-all duration-200 overflow-hidden"
              >
                <div v-if="place.logo_url || place.featured_image_url" class="aspect-w-16 aspect-h-9">
                  <img 
                    :src="place.logo_url || place.featured_image_url" 
                    :alt="place.title"
                    class="w-full h-48 object-cover"
                  >
                </div>
                <div class="p-4">
                  <h3 class="text-lg font-medium text-gray-900 group-hover:text-blue-600">
                    {{ place.title }}
                  </h3>
                  <p v-if="place.category" class="text-sm text-gray-500">{{ place.category.name }}</p>
                  <p v-if="place.location?.address_line1" class="text-sm text-gray-400 mt-1">
                    {{ place.location.address_line1 }}
                  </p>
                  <div v-if="place.is_featured || place.is_verified" class="flex items-center gap-2 mt-2">
                    <span v-if="place.is_featured" class="text-purple-600">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </span>
                    <span v-if="place.is_verified" class="text-blue-600">
                      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </div>
                </div>
              </router-link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No places found</h3>
              <p class="mt-1 text-sm text-gray-500">No places have been added to this city yet.</p>
            </div>
          </div>
        </div>

        <!-- Neighborhoods -->
        <div v-if="neighborhoods.length > 0" class="mt-8 bg-white shadow-sm rounded-lg">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Neighborhoods</h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
              <div
                v-for="neighborhood in neighborhoods"
                :key="neighborhood.id"
                class="p-3 rounded-lg bg-gray-50 text-gray-700 text-sm"
              >
                {{ neighborhood.name }}
                <span v-if="neighborhood.entries_count > 0" class="text-xs text-gray-500 ml-1">
                  ({{ neighborhood.entries_count }})
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Admin Edit Button -->
      <button
        v-if="authStore.user && (authStore.user.role === 'admin' || authStore.user.role === 'manager')"
        @click="isDrawerOpen = true"
        class="fixed bottom-6 right-6 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        <CogIcon class="h-6 w-6" />
      </button>

      <!-- Admin Edit Drawer -->
      <RegionEditDrawer
        v-if="authStore.user && (authStore.user.role === 'admin' || authStore.user.role === 'manager')"
        :is-open="isDrawerOpen"
        :region="cityData"
        @close="isDrawerOpen = false"
        @updated="handleRegionUpdated"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import SaveButton from '@/components/SaveButton.vue'
import RegionDetailsTab from '@/components/regions/RegionDetailsTab.vue'
import RegionEditDrawer from '@/components/regions/RegionEditDrawer.vue'
import QRCodeGenerator from '@/components/QRCodeGenerator.vue'
import { CogIcon, MapIcon } from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import { useMapbox } from '@/composables/useMapbox'

const props = defineProps({
  state: {
    type: String,
    required: true
  },
  city: {
    type: String,
    required: true
  },
  neighborhood: {
    type: String,
    required: false,
    default: null
  },
  isNeighborhood: {
    type: Boolean,
    required: false,
    default: false
  }
})

const route = useRoute()
const authStore = useAuthStore()
const loading = ref(true)
const error = ref(null)
const stateData = ref(null)
const cityData = ref(null)
const places = ref([])
const neighborhoods = ref([])
const categories = ref([])
const featuredPlaces = ref([])
const featuredLists = ref([])
const isDrawerOpen = ref(false)
const hoveredPlaceId = ref(null)
const hoveredRegionId = ref(null)
const parkRegions = ref([])
const mapContainer = ref(null)
const mapLoading = ref(false)
const mapError = ref(null)
const currentMapStyle = ref('mapbox://styles/illuminatelocal/clf0ha89i000a01o7fyggdklz') // Illuminate Local custom style

// Mapbox composable
const {
  map,
  initializeMap,
  updatePlaces,
  flyTo,
  cleanup,
  isLoaded
} = useMapbox()

const uniqueCategories = computed(() => {
  const cats = new Set(places.value.filter(p => p.category).map(p => p.category.id))
  return cats.size
})

// Helper function to strip HTML tags from text
const stripHtml = (html) => {
  if (!html) return ''
  // Create a temporary div element to parse HTML
  const tmp = document.createElement('div')
  tmp.innerHTML = html
  // Return text content, also decode HTML entities
  return tmp.textContent || tmp.innerText || ''
}

const fetchCityData = async () => {
  try {
    loading.value = true
    error.value = null
    
    // Fetch region data based on whether it's a neighborhood or city
    let response
    if (props.isNeighborhood && props.neighborhood) {
      response = await axios.get(`/api/regions/by-slug/${props.state}/${props.city}/${props.neighborhood}`)
    } else {
      response = await axios.get(`/api/regions/by-slug/${props.state}/${props.city}`)
    }
    cityData.value = response.data.city || response.data.data
    stateData.value = response.data.state
    
    console.log('City data response:', { cityData: cityData.value, stateData: stateData.value })
    
    if (cityData.value && cityData.value.id) {
      // Fetch places in this city
      const placesResponse = await axios.get(`/api/regions/${cityData.value.id}/entries`)
      places.value = placesResponse.data.data || []
      
      // Fetch featured places for this city
      try {
        const featuredResponse = await axios.get(`/api/regions/${cityData.value.id}/featured`)
        featuredPlaces.value = featuredResponse.data.data || []
      } catch (err) {
        console.log('No featured places found')
        featuredPlaces.value = []
      }
      
      // Fetch featured lists for this city
      try {
        const listsResponse = await axios.get(`/api/admin/regions/${cityData.value.id}/featured-lists`)
        featuredLists.value = listsResponse.data.data || []
      } catch (err) {
        console.log('No featured lists found')
        featuredLists.value = []
      }
      
      // Calculate categories with counts
      const categoryMap = new Map()
      places.value.forEach(place => {
        if (place.category) {
          const cat = categoryMap.get(place.category.id) || { ...place.category, count: 0 }
          cat.count++
          categoryMap.set(place.category.id, cat)
        }
      })
      categories.value = Array.from(categoryMap.values()).sort((a, b) => b.count - a.count)
      
      // Fetch neighborhoods
      const neighborhoodsResponse = await axios.get(`/api/regions/${cityData.value.id}/children`)
      neighborhoods.value = neighborhoodsResponse.data.data || []
      
      // Fetch park regions for cities
      if (!props.isNeighborhood) {
        try {
          const parksResponse = await axios.get('/api/regions/search', {
            params: {
              type: 'neighborhood',
              park_designation: true,
              city: cityData.value.name,
              limit: 50
            }
          })
          parkRegions.value = parksResponse.data.data || []
        } catch (err) {
          console.error('Error fetching park regions:', err)
          parkRegions.value = []
        }
      }
    } else {
      console.error('City data missing or invalid:', cityData.value)
      error.value = 'Invalid city data received'
    }
    
  } catch (err) {
    console.error('Error fetching city data:', err)
    error.value = 'Failed to load city information. Please try again later.'
  } finally {
    loading.value = false
  }
}

const initCityMap = async () => {
  console.log('initCityMap called for city:', cityData.value?.name)
  
  // Reset states
  mapLoading.value = true
  mapError.value = null
  
  try {
    // Combine neighborhoods and parks for map display
    const regionsToShow = [...neighborhoods.value, ...parkRegions.value]
    
    if (regionsToShow.length === 0) {
      mapError.value = 'No regions to display on map'
      mapLoading.value = false
      return
    }
    
    // Wait for DOM to update
    await nextTick()
    
    // Use the ref directly or fallback to getElementById
    let mapContainerEl = mapContainer.value || document.getElementById('city-map')
    
    // Retry logic if container not found
    let retries = 0
    while (!mapContainerEl && retries < 5) {
      await new Promise(resolve => setTimeout(resolve, 100))
      mapContainerEl = mapContainer.value || document.getElementById('city-map')
      retries++
    }
    
    if (!mapContainerEl) {
      console.error('Map container not found after retries')
      mapError.value = 'Map container element not found'
      mapLoading.value = false
      return
    }
    
    console.log('City map container found:', mapContainerEl)
    
    // Initialize map centered on city
    const centerLat = cityData.value?.lat || cityData.value?.latitude || 33.6846
    const centerLng = cityData.value?.lng || cityData.value?.longitude || -117.8265
    
    console.log('Initializing city map at:', { centerLat, centerLng })
    
    const success = await initializeMap('city-map', {
      center: [centerLng, centerLat],
      zoom: 11,
      style: currentMapStyle.value
    })
    
    if (!success) {
      console.error('Failed to initialize city map')
      mapError.value = 'Failed to initialize map. Please check your internet connection.'
      mapLoading.value = false
      return
    }
    
    console.log('City map initialized successfully')
    
    // Add markers for neighborhoods and parks
    if (map.value) {
      // Add neighborhood markers
      neighborhoods.value.forEach(neighborhood => {
        if (neighborhood.lat && neighborhood.lng) {
          const marker = new window.mapboxgl.Marker({
            color: '#3B82F6' // Blue for neighborhoods
          })
            .setLngLat([neighborhood.lng, neighborhood.lat])
            .setPopup(new window.mapboxgl.Popup().setHTML(`
              <div class="p-3">
                <h3 class="font-semibold">${neighborhood.name}</h3>
                <p class="text-sm text-gray-600 mt-1">${neighborhood.entries_count || 0} places</p>
                <a href="/local/${props.state}/${props.city}/${neighborhood.slug}" 
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">
                  View Neighborhood →
                </a>
              </div>
            `))
            .addTo(map.value)
        }
      })
      
      // Add park markers
      parkRegions.value.forEach(park => {
        if (park.lat && park.lng) {
          const marker = new window.mapboxgl.Marker({
            color: '#22C55E' // Green for parks
          })
            .setLngLat([park.lng, park.lat])
            .setPopup(new window.mapboxgl.Popup().setHTML(`
              <div class="p-3">
                <h3 class="font-semibold">${park.name}</h3>
                <p class="text-sm text-gray-600 mt-1">${park.park_designation || 'Park'}</p>
                <p class="text-sm text-gray-600">${park.entries_count || 0} places</p>
                <a href="/regions/${park.id}/explore" 
                   class="text-green-600 hover:text-green-800 text-sm font-medium mt-2 inline-block">
                  Explore Park →
                </a>
              </div>
            `))
            .addTo(map.value)
        }
      })
      
      // Fit bounds to show all regions
      const allRegions = [...neighborhoods.value, ...parkRegions.value].filter(r => r.lat && r.lng)
      if (allRegions.length > 1) {
        const bounds = new window.mapboxgl.LngLatBounds()
        allRegions.forEach(region => {
          bounds.extend([region.lng, region.lat])
        })
        map.value.fitBounds(bounds, { padding: 50 })
      }
    }
    
  } catch (err) {
    console.error('Error initializing city map:', err)
    mapError.value = 'Failed to initialize map'
  } finally {
    mapLoading.value = false
  }
}

const initNeighborhoodMap = async () => {
  console.log('initNeighborhoodMap called, places:', places.value)
  
  // Reset states
  mapLoading.value = true
  mapError.value = null
  
  try {
    // Only initialize map if we have places with coordinates
    const placesWithCoords = places.value.filter(p => 
      p.location?.latitude && p.location?.longitude
    )
    
    console.log('Places with coordinates:', placesWithCoords)
    
    if (placesWithCoords.length === 0) {
      console.log('No places with coordinates to display on map')
      console.log('Places data:', places.value.map(p => ({
        id: p.id,
        title: p.title,
        location: p.location
      })))
      mapError.value = 'No places with coordinates to display'
      mapLoading.value = false
      return
    }
    
    // Wait for DOM to update
    await nextTick()
    
    // Use the ref directly or fallback to getElementById
    let mapContainerEl = mapContainer.value || document.getElementById('neighborhood-map')
    
    // Retry logic if container not found
    let retries = 0
    while (!mapContainerEl && retries < 5) {
      await new Promise(resolve => setTimeout(resolve, 100))
      mapContainerEl = mapContainer.value || document.getElementById('neighborhood-map')
      retries++
    }
    
    if (!mapContainerEl) {
      console.error('Map container not found after retries')
      mapError.value = 'Map container element not found'
      mapLoading.value = false
      return
    }
    
    console.log('Map container found:', mapContainerEl)
    
    // Initialize map centered on first place or region center
    const centerLat = cityData.value?.lat || placesWithCoords[0].location.latitude
    const centerLng = cityData.value?.lng || placesWithCoords[0].location.longitude
    
    console.log('Initializing neighborhood map at:', { centerLat, centerLng })
    
    // Available Mapbox styles:
    // 'mapbox://styles/mapbox/streets-v12' - Standard street map (current)
    // 'mapbox://styles/mapbox/outdoors-v12' - Topographic outdoor style
    // 'mapbox://styles/mapbox/light-v11' - Light subtle style
    // 'mapbox://styles/mapbox/dark-v11' - Dark mode style
    // 'mapbox://styles/mapbox/satellite-v9' - Satellite imagery
    // 'mapbox://styles/mapbox/satellite-streets-v12' - Satellite with street labels
    // 'mapbox://styles/mapbox/navigation-day-v1' - Navigation focused
    // 'mapbox://styles/mapbox/navigation-night-v1' - Dark navigation
    
    const success = await initializeMap('neighborhood-map', {
      center: [centerLng, centerLat],
      zoom: 13,
      style: currentMapStyle.value // Use the selected style
    })
    
    if (!success) {
      console.error('Failed to initialize map')
      mapError.value = 'Failed to initialize map. Please check your internet connection.'
      mapLoading.value = false
      return
    }
    
    // Transform places data for the map
    const mapPlaces = placesWithCoords.map(place => ({
      id: place.id,
      title: place.title,
      name: place.title,
      slug: place.slug,
      category: place.category,
      description: place.description || '',
      latitude: place.location.latitude,
      longitude: place.location.longitude,
      is_featured: place.is_featured || false,
      is_verified: place.is_verified || false,
      logo_url: place.logo_url || '',
      featured_image_url: place.featured_image_url || '',
      cover_image_url: place.cover_image_url || '',
      canonical_url: place.canonical_url || `/p/${place.id}`,
      location: place.location
    }))
    
    // Update places on the map (this will handle clustering)
    updatePlaces(mapPlaces)
    
    // Wait a moment for the map to set up its handlers
    await nextTick()
    
    // Remove the default click handler from the composable
    if (map.value) {
      map.value.off('click', 'unclustered-point')
      
      // Add our custom click handler for unclustered points
      map.value.on('click', 'unclustered-point', (e) => {
        // Stop propagation to prevent multiple handlers
        if (e.originalEvent) {
          e.originalEvent.stopPropagation()
        }
        
        // Only process if we don't have a popup open or if clicking a different marker
        const features = e.features[0]
        if (features && features.properties) {
          const placeData = {
            id: features.properties.id,
            title: features.properties.name || features.properties.title,
            description: features.properties.description,
            canonical_url: features.properties.canonical_url,
            location: {
              latitude: features.geometry.coordinates[1],
              longitude: features.geometry.coordinates[0]
            }
          }
          
          // Only show popup if it's not already showing for this place
          if (!window.currentPopup || window.currentPlaceId !== placeData.id) {
            window.currentPlaceId = placeData.id
            showPlacePopup(placeData)
          }
        }
      })
      
      // Clear popup references when clicking on the map (not on markers)
      map.value.on('click', (e) => {
        // Check if the click was on empty space (not on a marker)
        const features = map.value.queryRenderedFeatures(e.point, {
          layers: ['unclustered-point', 'clusters']
        })
        
        if (features.length === 0 && window.currentPopup) {
          window.currentPopup.remove()
          window.currentPopup = null
          window.currentPlaceId = null
        }
      })
    }
    
    // Fit map to show all markers
    if (placesWithCoords.length > 1) {
      // Use the composable's fitBounds method with proper format
      const mapboxgl = window.mapboxgl
      if (mapboxgl && map.value) {
        const bounds = placesWithCoords.reduce((bounds, place) => {
          return bounds.extend([place.location.longitude, place.location.latitude])
        }, new mapboxgl.LngLatBounds())
        
        map.value.fitBounds(bounds, {
          padding: 50,
          maxZoom: 15
        })
      }
    }
    
    // Map loaded successfully
    mapLoading.value = false
    console.log('Map initialized successfully')
    
  } catch (error) {
    console.error('Error initializing map:', error)
    mapError.value = `Map initialization error: ${error.message}`
    mapLoading.value = false
  }
}

const handleRegionClick = (region) => {
  console.log('Region clicked:', region)
  
  // Check if region has coordinates
  if (!region.lat || !region.lng) {
    console.log('Region has no coordinates')
    return
  }
  
  // Fly to the region location
  if (map.value) {
    map.value.flyTo({
      center: [region.lng, region.lat],
      zoom: 13,
      essential: true
    })
    
    // Find and open the popup for this region
    // This would need to be implemented based on how markers are stored
  }
}

const handlePlaceClick = (place) => {
  console.log('Place clicked:', place)
  
  // Check if place has coordinates
  if (!place.location?.latitude || !place.location?.longitude) {
    console.log('Place has no coordinates')
    return
  }
  
  // Fly to the place location
  if (map.value) {
    flyTo(place.location.longitude, place.location.latitude, 16)
    
    // Open popup for this place
    showPlacePopup(place)
  }
}

const showPlacePopup = (place) => {
  if (!map.value || !window.mapboxgl) return
  
  const mapboxgl = window.mapboxgl
  
  // Close ALL existing popups from any source
  if (window.currentPopup) {
    window.currentPopup.remove()
    window.currentPopup = null
  }
  
  // Also close any popups created by the composable
  const allPopups = document.getElementsByClassName('mapboxgl-popup')
  Array.from(allPopups).forEach(popup => popup.remove())
  
  // Get first 7 words of description (strip HTML first)
  const cleanDescription = stripHtml(place.description)
  const descriptionWords = cleanDescription ? 
    cleanDescription.split(' ').slice(0, 7).join(' ') + '...' : 
    ''
  
  // Create popup HTML
  const popupHTML = `
    <div class="p-3 max-w-xs">
      <h3 class="font-semibold text-gray-900 mb-1">${place.title}</h3>
      ${descriptionWords ? `<p class="text-sm text-gray-600 mb-2">${descriptionWords}</p>` : ''}
      <div class="flex items-center gap-3">
        <a href="${place.canonical_url || `/p/${place.id}`}" 
           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
          View Details →
        </a>
        <a href="https://www.google.com/maps/dir/Current+Location/${place.location.latitude},${place.location.longitude}" 
           target="_blank"
           rel="noopener noreferrer"
           class="text-green-600 hover:text-green-800 text-sm font-medium">
          Directions
        </a>
      </div>
    </div>
  `
  
  // Create and show popup
  const popup = new mapboxgl.Popup({
    closeButton: true,
    closeOnClick: false, // Don't close on map click to prevent re-triggering
    closeOnMove: false,
    maxWidth: '300px',
    anchor: 'bottom'
  })
    .setLngLat([place.location.longitude, place.location.latitude])
    .setHTML(popupHTML)
    .addTo(map.value)
  
  // Store reference to current popup
  window.currentPopup = popup
  
  // Clean up when popup is closed
  popup.on('close', () => {
    if (window.currentPopup === popup) {
      window.currentPopup = null
      window.currentPlaceId = null
    }
    // Remove any lingering popups with loading spinners
    setTimeout(() => {
      const loadingPopups = document.querySelectorAll('.mapboxgl-popup .popup-loading')
      loadingPopups.forEach(p => {
        const popupEl = p.closest('.mapboxgl-popup')
        if (popupEl) popupEl.remove()
      })
    }, 100)
  })
}

const changeMapStyle = () => {
  if (map.value && currentMapStyle.value) {
    map.value.setStyle(currentMapStyle.value)
    
    // Re-add places after style change (styles clear all sources/layers)
    map.value.once('styledata', () => {
      // Wait for style to load then re-add our data
      setTimeout(() => {
        if (places.value.length > 0) {
          const placesWithCoords = places.value.filter(p => 
            p.location?.latitude && p.location?.longitude
          )
          
          const mapPlaces = placesWithCoords.map(place => ({
            id: place.id,
            title: place.title,
            name: place.title,
            slug: place.slug,
            category: place.category,
            description: place.description || '',
            latitude: place.location.latitude,
            longitude: place.location.longitude,
            is_featured: place.is_featured || false,
            is_verified: place.is_verified || false,
            logo_url: place.logo_url || '',
            featured_image_url: place.featured_image_url || '',
            cover_image_url: place.cover_image_url || '',
            canonical_url: place.canonical_url || `/p/${place.id}`,
            location: place.location
          }))
          
          // Re-initialize clustering and add places
          updatePlaces(mapPlaces)
        }
      }, 500)
    })
  }
}

const handleRegionUpdated = (updatedRegion) => {
  cityData.value = updatedRegion
  isDrawerOpen.value = false
  // Optionally refresh other data that might have changed
  fetchCityData()
}

onMounted(() => {
  fetchCityData()
})

// Watch for route changes to refresh data
watch(() => [props.state, props.city, props.neighborhood], () => {
  // Reset all data
  cityData.value = null
  stateData.value = null
  places.value = []
  neighborhoods.value = []
  parkRegions.value = []
  featuredPlaces.value = []
  featuredLists.value = []
  categories.value = []
  
  // Clean up existing map if present
  if (map.value) {
    cleanup()
  }
  
  // Fetch new data
  fetchCityData()
}, { deep: true })

// Watch for places to be loaded and initialize neighborhood map
watch(places, async (newPlaces) => {
  if (newPlaces.length > 0 && props.isNeighborhood) {
    // Wait for multiple ticks to ensure DOM is fully rendered
    await nextTick()
    await nextTick()
    
    // Add a small delay to ensure the DOM is ready
    setTimeout(() => {
      initNeighborhoodMap()
    }, 100)
  }
}, { immediate: false })

// Watch for neighborhoods to be loaded and initialize city map
watch(neighborhoods, async (newNeighborhoods) => {
  if (!props.isNeighborhood && (newNeighborhoods.length > 0 || parkRegions.value.length > 0)) {
    // Wait for multiple ticks to ensure DOM is fully rendered
    await nextTick()
    await nextTick()
    
    // Add a small delay to ensure the DOM is ready
    setTimeout(() => {
      initCityMap()
    }, 100)
  }
}, { immediate: false })

// Watch for park regions to be loaded
watch(parkRegions, async (newParkRegions) => {
  if (!props.isNeighborhood && (neighborhoods.value.length > 0 || newParkRegions.length > 0)) {
    // Only initialize if not already initialized
    if (!map.value) {
      // Wait for multiple ticks to ensure DOM is fully rendered
      await nextTick()
      await nextTick()
      
      // Add a small delay to ensure the DOM is ready
      setTimeout(() => {
        initCityMap()
      }, 100)
    }
  }
}, { immediate: false })

// Clean up map on component unmount
onUnmounted(() => {
  if (map.value) {
    cleanup()
  }
})
</script>