<template>
    <div>
        <!-- Hero Section with Marketing Content -->
        <section class="relative mb-8">
            <!-- Background Image -->
            <div v-if="marketingContent.image_url" class="absolute inset-0">
                <img 
                    :src="marketingContent.image_url" 
                    alt="Places hero background" 
                    class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            </div>
            <div v-else class="absolute inset-0 bg-gradient-to-r from-green-600 to-blue-600"></div>
            
            <!-- Content -->
            <div class="relative z-10 py-24">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h1 class="text-4xl md:text-5xl font-bold mb-6 text-white">
                        {{ marketingContent.heading || 'Discover Amazing Places' }}
                    </h1>
                    <p class="text-xl mb-8 max-w-3xl mx-auto text-white/90">
                        {{ marketingContent.paragraph || 'Explore carefully curated local businesses, restaurants, and attractions. Find your next favorite spot or share your discoveries with the community.' }}
                    </p>
                </div>
            </div>
        </section>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-8">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error loading places</h3>
                    <p class="text-sm text-red-700 mt-1">{{ error }}</p>
                </div>
            </div>
        </div>

        <!-- Content -->
        <template v-else>
            <!-- Location Banner -->
            <div v-if="userLocation" class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm text-blue-800 font-medium">
                                Showing {{ currentCategory ? currentCategory.name + ' in' : 'places in' }} {{ userLocation.city }}{{ userLocation.state !== userLocation.city ? `, ${userLocation.state}` : '' }}
                            </p>
                            <div class="flex items-center space-x-2 mt-1">
                                <p class="text-xs text-blue-600">
                                    {{ locationDetectedVia === 'manual' ? 'Location selected manually' : 'Location detected automatically' }}
                                </p>
                                <span class="text-xs text-blue-400">•</span>
                                <router-link 
                                    v-if="userLocation.slug"
                                    :to="`/local/${userLocation.slug}`"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                >
                                    Explore {{ userLocation.city }}
                                </router-link>
                                <span v-if="userLocation.state !== userLocation.city" class="text-xs text-blue-600">
                                    and
                                    <router-link 
                                        :to="`/local/${getStateSlug(userLocation.state)}`"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                                    >
                                        {{ userLocation.state }}
                                    </router-link>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <LocationCommandPalette
                            v-model="selectedLocation"
                            :current-location="currentRegion"
                            @change="handleLocationChange"
                            class="w-64"
                        />
                    </div>
                </div>
            </div>
            
            <!-- Fallback Message -->
            <div v-if="fallbackMessage" class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-amber-800">{{ fallbackMessage }}</p>
                </div>
            </div>

            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h1 class="text-3xl font-bold text-gray-900">
                        {{ currentCategory ? currentCategory.name : 'Browse Places' }}
                    </h1>
                    <div class="flex items-center space-x-4">
                        <router-link
                            to="/places/map"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            Map View
                        </router-link>
                        <router-link
                            to="/places/create"
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
                        >
                            + Add Place
                        </router-link>
                    </div>
                </div>
                
                <!-- Location Context -->
                <div v-if="currentRegion" class="mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-600">
                            Showing places in <span class="font-semibold">{{ currentRegion.name }}</span>
                            <span v-if="parentRegion">, {{ parentRegion.name }}</span>
                        </p>
                        
                        <!-- Include Nearby Toggle -->
                        <label class="flex items-center cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="includeNearby"
                                @change="fetchEntries()"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                            />
                            <span class="ml-2 text-sm text-gray-700">
                                Include places within 20 miles
                            </span>
                        </label>
                    </div>
                    
                    <!-- Region Navigation -->
                    <div class="flex flex-wrap gap-2">
                        <!-- Parent Region Link -->
                        <router-link
                            v-if="parentRegion"
                            :to="`/regions/${parentRegion.slug}`"
                            class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition-colors text-sm"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            View all {{ parentRegion.name }}
                        </router-link>
                        
                        <!-- Current Region -->
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ currentRegion.name }}
                        </span>
                        
                        <!-- Child Regions (Neighborhoods) -->
                        <div v-if="childRegions.length > 0" class="flex items-center gap-2 flex-wrap">
                            <span class="text-gray-500 text-sm">|</span>
                            <span class="text-gray-600 text-sm">Neighborhoods:</span>
                            <router-link
                                v-for="child in (showAllNeighborhoods ? childRegions : childRegions.slice(0, 5))"
                                :key="child.id"
                                :to="`/regions/${child.slug}`"
                                class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 rounded-full hover:bg-blue-100 transition-colors text-sm"
                            >
                                {{ child.name }}
                            </router-link>
                            <button 
                                v-if="childRegions.length > 5"
                                @click="showAllNeighborhoods = !showAllNeighborhoods"
                                class="inline-flex items-center px-3 py-1 text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors"
                            >
                                <span>{{ showAllNeighborhoods ? 'Show less' : `Show ${childRegions.length - 5} more` }}</span>
                                <svg 
                                    class="ml-1 w-4 h-4 transition-transform duration-200"
                                    :class="{ 'rotate-180': showAllNeighborhoods }"
                                    fill="none" 
                                    stroke="currentColor" 
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <p v-else class="text-gray-600">
                    {{ currentCategory ? `Browse ${currentCategory.name.toLowerCase()} in your area` : 'Discover local businesses, restaurants, and attractions' }}
                </p>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search Places</label>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by name, description..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @input="debouncedSearch"
                        />
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select
                            v-model="selectedCategory"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @change="fetchEntries"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.slug">
                                {{ category.name }} ({{ category.places_count || category.directory_entries_count || 0 }})
                            </option>
                        </select>
                    </div>

                    <!-- Sort Options -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                        <select
                            v-model="sortOption"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            @change="fetchEntries"
                        >
                            <option value="latest">Latest Added</option>
                            <option value="name">Name (A-Z)</option>
                            <option value="featured">Featured First</option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Back to Categories Link -->
            <div v-if="currentCategory" class="mb-4">
                <router-link
                    to="/places"
                    class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to all categories
                </router-link>
            </div>

            <!-- Editor's Choice / Curated Lists Section -->
            <div v-if="curatedLists.length > 0 && !currentCategory" class="mb-12">
                <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Editor's Choice
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="list in curatedLists" :key="list.id" 
                         class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-lg p-6 border border-purple-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ list.name }}</h3>
                        <p v-if="list.description" class="text-gray-600 text-sm mb-4">{{ list.description }}</p>
                        
                        <!-- Mini preview of places in the list -->
                        <div v-if="list.places && list.places.length > 0" class="space-y-2 mb-4">
                            <div v-for="(place, index) in list.places.slice(0, 3)" :key="place.id" 
                                 class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500">{{ index + 1 }}.</span>
                                <router-link :to="getEntryUrl(place)" 
                                            class="text-sm text-gray-700 hover:text-purple-600 font-medium">
                                    {{ place.title }}
                                </router-link>
                            </div>
                            <div v-if="list.place_ids && list.place_ids.length > 3" 
                                 class="text-xs text-gray-500">
                                +{{ list.place_ids.length - 3 }} more places
                            </div>
                        </div>
                        
                        <router-link :to="`/lists/${list.slug}`" 
                                    class="text-sm text-purple-600 hover:text-purple-800 font-medium">
                            View full list →
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- Categories Grid -->
            <div v-if="!currentCategory" class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Browse by Category</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <!-- All Places Category -->
                    <router-link
                        :to="{path: '/places'}"
                        class="bg-white rounded-lg p-4 text-center border hover:shadow-md hover:border-green-300 transition-all cursor-pointer group"
                    >
                        <div class="text-2xl mb-2">🏢</div>
                        <h3 class="text-sm font-medium text-gray-900 group-hover:text-green-600 transition-colors">
                            All Places
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ totalPlacesCount }} {{ totalPlacesCount === 1 ? 'place' : 'places' }}
                        </p>
                    </router-link>
                    
                    <!-- Individual Categories -->
                    <router-link
                        v-for="category in categories.slice(0, 11)"
                        :key="category.id"
                        :to="{path: '/places', query: {category: category.slug}}"
                        class="bg-white rounded-lg p-4 text-center border hover:shadow-md hover:border-green-300 transition-all cursor-pointer group"
                    >
                        <div class="text-2xl mb-2">{{ category.icon || '📁' }}</div>
                        <h3 class="text-sm font-medium text-gray-900 group-hover:text-green-600 transition-colors">
                            {{ category.name }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ category.directory_entries_count || 0 }} {{ category.directory_entries_count === 1 ? 'place' : 'places' }}
                        </p>
                    </router-link>
                </div>
            </div>

            <!-- Featured Places Section -->
            <div v-if="featuredEntries.length > 0" class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Featured in {{ userLocation?.city || 'Your Area' }}
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <router-link
                        v-for="entry in featuredEntries.slice(0, 6)" 
                        :key="entry.id"
                        :to="getEntryUrl(entry)"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer border-2 border-yellow-200"
                    >
                        <!-- Featured Badge -->
                        <div class="bg-yellow-100 text-yellow-800 px-3 py-1 text-xs font-medium">
                            ⭐ Featured
                        </div>
                        
                        <!-- Entry Image -->
                        <div v-if="entry.cover_image_url || entry.logo_url" class="h-40 bg-gray-200 relative">
                            <img
                                :src="entry.cover_image_url || entry.logo_url"
                                :alt="entry.title"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div v-else class="h-40 bg-gradient-to-br from-yellow-50 to-yellow-100 flex items-center justify-center">
                            <span class="text-4xl text-yellow-400">⭐</span>
                        </div>

                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ entry.title }}</h3>
                            <div class="text-gray-600 text-sm mb-3 line-clamp-2">{{ stripHtml(entry.description) }}</div>
                            
                            <div v-if="entry.location" class="flex items-center text-gray-500 text-sm">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                {{ entry.location.city }}, {{ entry.location.state }}
                            </div>
                        </div>
                    </router-link>
                </div>
            </div>

            <!-- Places Grid -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Places
                        <span class="text-lg text-gray-500 font-normal">({{ pagination.total }} total)</span>
                    </h2>
                    <div class="flex items-center space-x-2 text-sm text-gray-600">
                        <span>View:</span>
                        <button
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'text-green-600' : 'text-gray-400'"
                            class="p-1 hover:text-green-600"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                        <button
                            @click="viewMode = 'list'"
                            :class="viewMode === 'list' ? 'text-green-600' : 'text-gray-400'"
                            class="p-1 hover:text-green-600"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid' && entries.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <router-link
                        v-for="entry in entries" 
                        :key="entry.id"
                        :to="getEntryUrl(entry)"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer group"
                    >
                        <!-- Image placeholder or actual image -->
                        <div v-if="entry.cover_image_url || entry.logo_url" class="h-40 bg-gray-200 overflow-hidden relative">
                            <img 
                                :src="entry.cover_image_url || entry.logo_url" 
                                :alt="entry.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <!-- Show logo overlay if we have both cover and logo -->
                            <div v-if="entry.logo_url && entry.cover_image_url" class="absolute bottom-2 left-2">
                                <img
                                    :src="entry.logo_url"
                                    :alt="entry.title + ' logo'"
                                    class="w-12 h-12 rounded-lg object-cover border-2 border-white"
                                />
                            </div>
                        </div>
                        <div v-else class="h-40 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                            <span class="text-4xl text-gray-400">{{ entry.category?.icon || '🏢' }}</span>
                        </div>

                        <div class="p-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ entry.category?.name || 'Uncategorized' }}
                                </span>
                                <span v-if="entry.is_featured" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    ⭐ Featured
                                </span>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-1 group-hover:text-green-600 transition-colors">{{ entry.title }}</h3>
                            <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ stripHtml(entry.description) }}</p>
                            
                            <div v-if="entry.location" class="flex items-center justify-between text-gray-500 text-sm">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span v-if="entry.location.neighborhood">{{ entry.location.neighborhood }}, </span>
                                    {{ entry.location.city }}, {{ entry.location.state }}
                                </div>
                                <span v-if="includeNearby && entry.distance_miles" class="text-xs font-medium">
                                    {{ entry.distance_miles }} mi
                                </span>
                            </div>
                        </div>
                    </router-link>
                </div>

                <!-- List View -->
                <div v-if="viewMode === 'list' && entries.length > 0" class="space-y-4">
                    <router-link
                        v-for="entry in entries" 
                        :key="entry.id"
                        :to="getEntryUrl(entry)"
                        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow cursor-pointer block group"
                    >
                        <div class="flex items-start space-x-4">
                            <div v-if="entry.featured_image" class="flex-shrink-0 w-24 h-24 bg-gray-200 rounded-lg overflow-hidden">
                                <img 
                                    :src="entry.featured_image" 
                                    :alt="entry.title"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                            <div v-else class="flex-shrink-0 w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                <span class="text-2xl text-gray-400">{{ entry.category?.icon || '🏢' }}</span>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ entry.category?.name || 'Uncategorized' }}
                                        </span>
                                        <span v-if="entry.is_featured" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            ⭐ Featured
                                        </span>
                                    </div>
                                </div>
                                
                                <h3 class="text-xl font-semibold text-gray-900 mb-1 group-hover:text-green-600 transition-colors">{{ entry.title }}</h3>
                                <p class="text-gray-600 mb-3 line-clamp-2">{{ stripHtml(entry.description) }}</p>
                                
                                <div class="flex items-center justify-between">
                                    <div v-if="entry.location" class="flex items-center text-gray-500 text-sm">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span v-if="entry.location.neighborhood">{{ entry.location.neighborhood }}, </span>
                                        {{ entry.location.city }}, {{ entry.location.state }}
                                        <span v-if="includeNearby && entry.distance_miles" class="ml-2 font-medium">
                                            ({{ entry.distance_miles }} mi)
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-3">
                                        <a v-if="entry.website_url" 
                                           :href="entry.website_url" 
                                           target="_blank" 
                                           class="text-green-600 hover:text-green-800 text-sm"
                                           @click.stop
                                        >
                                            Visit Website
                                        </a>
                                        <span v-if="entry.phone" class="text-gray-500 text-sm">{{ entry.phone }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </router-link>
                </div>

                <!-- Empty State -->
                <div v-if="entries.length === 0" class="text-center py-12">
                    <div class="text-gray-500 text-lg">No places found.</div>
                    <p class="text-gray-400 mt-2">Try adjusting your search or filters.</p>
                    <router-link
                        to="/places/create"
                        class="mt-4 inline-flex items-center text-green-600 hover:text-green-800 font-medium"
                    >
                        Add the first place →
                    </router-link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="px-3 py-2 text-sm rounded-md transition-colors"
                        :class="pagination.current_page === 1 ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'"
                    >
                        Previous
                    </button>
                    
                    <button
                        v-for="page in paginationRange"
                        :key="page"
                        @click="changePage(page)"
                        :class="[
                            'px-3 py-2 text-sm rounded-md transition-colors',
                            page === pagination.current_page 
                                ? 'bg-green-600 text-white' 
                                : 'text-gray-700 hover:bg-gray-100'
                        ]"
                    >
                        {{ page }}
                    </button>
                    
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="pagination.current_page === pagination.last_page"
                        class="px-3 py-2 text-sm rounded-md transition-colors"
                        :class="pagination.current_page === pagination.last_page ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100'"
                    >
                        Next
                    </button>
                </nav>
            </div>
            
            <!-- Location Picker Modal -->
            <div v-if="showLocationPicker" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Change Location</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                            <select
                                v-model="tempLocation.state"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                @change="tempLocation.city = ''"
                            >
                                <option value="">Select a state</option>
                                <option v-for="state in availableStates" :key="state.code" :value="state.name">
                                    {{ state.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                            <input
                                v-model="tempLocation.city"
                                type="text"
                                placeholder="Enter city name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            />
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button
                            @click="showLocationPicker = false"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="updateLocation"
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors"
                        >
                            Update Location
                        </button>
                    </div>
                </div>
            </div>
        </template>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'
import { usStates } from '@/data/usStates'
import LocationSwitcher from '@/components/LocationSwitcher.vue'
import LocationCommandPalette from '@/components/LocationCommandPalette.vue'
import { getPlaceUrl } from '@/utils/placeUrls'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

// Available states for location picker
const availableStates = usStates

// State
const loading = ref(true)
const error = ref(null)
const entries = ref([])
const categories = ref([])
const featuredEntries = ref([])
const curatedLists = ref([])
const locationData = ref({})
const fallbackMessage = ref(null)
const locationDetectedVia = ref('auto')
const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0
})
const marketingContent = ref({
    heading: 'Discover Amazing Places',
    paragraph: 'Explore carefully curated local businesses, restaurants, and attractions. Find your next favorite spot or share your discoveries with the community.',
    image_url: null
})
const includeNearby = ref(false)

// Region state
const currentRegion = ref(null)
const parentRegion = ref(null)
const childRegions = ref([])
const showAllNeighborhoods = ref(false)

// Location detection
const loadingLocation = ref(false)
const userLocation = ref(null)
const selectedLocation = ref(null)
const showLocationPicker = ref(false)
const tempLocation = reactive({ state: '', city: '' })

// Filters
const searchQuery = ref('')
const selectedCategory = ref('')
const sortOption = ref('latest')
const viewMode = ref('grid')

// Computed
const currentCategory = computed(() => {
    const categorySlug = router.currentRoute.value.query.category
    if (!categorySlug) return null
    return categories.value.find(cat => cat.slug === categorySlug)
})

const totalPlacesCount = computed(() => {
    // Sum up all category counts for location-specific total
    return categories.value.reduce((total, category) => {
        return total + (category.directory_entries_count || 0)
    }, 0)
})

const paginationRange = computed(() => {
    const range = []
    const { current_page, last_page } = pagination.value
    const delta = 2

    for (let i = Math.max(1, current_page - delta); i <= Math.min(last_page, current_page + delta); i++) {
        range.push(i)
    }

    return range
})

// Location detection
const detectUserLocation = async () => {
    loadingLocation.value = true
    
    try {
        // First try to use user's profile location
        if (authStore.user?.location) {
            // Parse user location string (e.g., "Irvine, CA")
            const parts = authStore.user.location.split(',').map(s => s.trim())
            if (parts.length >= 2) {
                const city = parts[0]
                const state = parts[1]
                
                // Try to find the region in the database
                const regionResponse = await axios.get('/api/regions/search', {
                    params: { q: city, type: 'city' }
                })
                
                if (regionResponse.data.data && regionResponse.data.data.length > 0) {
                    const cityRegion = regionResponse.data.data.find(r => 
                        r.name.toLowerCase() === city.toLowerCase() &&
                        r.parent?.name?.toLowerCase().includes(state.toLowerCase())
                    ) || regionResponse.data.data[0]
                    
                    if (cityRegion) {
                        currentRegion.value = cityRegion
                        userLocation.value = {
                            city: cityRegion.name,
                            state: cityRegion.parent?.name || state,
                            region_id: cityRegion.id,
                            lat: cityRegion.lat,
                            lng: cityRegion.lng
                        }
                        
                        // Fetch parent and child regions
                        await fetchRegionHierarchy(cityRegion)
                        
                        // Update filters
                        updateFiltersFromLocation()
                        loadingLocation.value = false
                        return
                    }
                }
            }
        }
        
        // Try to get location from localStorage
        const savedLocation = localStorage.getItem('userLocation')
        if (savedLocation) {
            userLocation.value = JSON.parse(savedLocation)
            updateFiltersFromLocation()
            loadingLocation.value = false
            return
        }
        
        // Use IP geolocation service as fallback
        const response = await fetch('https://ipapi.co/json/')
        const data = await response.json()
        
        if (data.city && data.region) {
            userLocation.value = {
                city: data.city,
                state: data.region,
                country: data.country_name || 'United States'
            }
            
            // Try to find matching region in database
            const regionResponse = await axios.get('/api/regions/search', {
                params: { q: data.city, type: 'city' }
            })
            
            if (regionResponse.data.data && regionResponse.data.data.length > 0) {
                const cityRegion = regionResponse.data.data[0]
                currentRegion.value = cityRegion
                userLocation.value.region_id = cityRegion.id
                await fetchRegionHierarchy(cityRegion)
            }
            
            // Save to localStorage
            localStorage.setItem('userLocation', JSON.stringify(userLocation.value))
            
            // Update filters to show places from user's location
            updateFiltersFromLocation()
        }
    } catch (err) {
        console.error('Error detecting location:', err)
        // Fallback to default (no location filtering)
    } finally {
        loadingLocation.value = false
    }
}

const fetchRegionHierarchy = async (region) => {
    try {
        // Fetch parent region if exists
        if (region.parent_id) {
            const parentResponse = await axios.get(`/api/regions/${region.parent_id}`)
            parentRegion.value = parentResponse.data.data
        }
        
        // Fetch child regions (neighborhoods)
        const childResponse = await axios.get(`/api/regions/${region.id}/children`)
        childRegions.value = childResponse.data.data || []
        
        // Reset the show all neighborhoods when changing regions
        showAllNeighborhoods.value = false
    } catch (error) {
        console.error('Error fetching region hierarchy:', error)
    }
}

const updateFiltersFromLocation = () => {
    if (userLocation.value) {
        // Trigger a new search with the location filters
        fetchEntries()
    }
}

const updateLocation = () => {
    if (tempLocation.state && tempLocation.city) {
        userLocation.value = {
            state: tempLocation.state,
            city: tempLocation.city,
            country: 'United States'
        }
        
        // Save to localStorage
        localStorage.setItem('userLocation', JSON.stringify(userLocation.value))
        
        // Update filters
        updateFiltersFromLocation()
        
        // Close modal
        showLocationPicker.value = false
    }
}

const getCategoryIdBySlug = (slug) => {
    const category = categories.value.find(cat => cat.slug === slug)
    return category ? category.id : null
}

// Methods
const getEntryUrl = (entry) => {
    return getPlaceUrl(entry)
}

const stripHtml = (html) => {
    if (!html) return ''
    const temp = document.createElement('div')
    temp.innerHTML = html
    return temp.textContent || temp.innerText || ''
}

const fetchEntries = async (page = 1) => {
    loading.value = true
    error.value = null

    try {
        const params = {
            page,
            q: searchQuery.value,  // Changed from 'search' to 'q'
            category: selectedCategory.value || undefined,
            sort: sortOption.value
        }
        
        // Handle category from URL query
        if (router.currentRoute.value.query.category) {
            params.category = router.currentRoute.value.query.category
        }
        
        // Always filter by region if we have a location
        if (userLocation.value?.region_id) {
            params.region_id = userLocation.value.region_id
        } else if (currentRegion.value?.id) {
            params.region_id = currentRegion.value.id
        }
        
        // Add nearby parameter if enabled
        if (includeNearby.value && params.region_id) {
            params.include_nearby = true
            params.nearby_radius = 20 // 20 mile radius
        }
        
        // Filter out undefined values
        Object.keys(params).forEach(key => {
            if (params[key] === undefined || params[key] === '') {
                delete params[key]
            }
        })

        const response = await axios.get('/api/places', { params })
        
        // Handle location-aware API response
        if (response.data.location) {
            // Update user location from API response
            const loc = response.data.location
            if (loc.current) {
                // Build proper location object based on hierarchy
                let locationObj = {
                    id: loc.current.id,
                    name: loc.current.name,
                    slug: loc.current.slug,
                    level: loc.current.level,
                    country: 'United States'
                }
                
                // Set city and state based on hierarchy level
                if (loc.current.level === 1) { // State
                    locationObj.state = loc.current.name
                    locationObj.city = loc.current.name
                } else if (loc.current.level === 2) { // City
                    locationObj.city = loc.current.name
                    locationObj.state = loc.hierarchy && loc.hierarchy.length > 0 ? loc.hierarchy[0].name : loc.current.name
                } else if (loc.current.level === 3) { // Neighborhood
                    locationObj.neighborhood = loc.current.name
                    locationObj.city = loc.hierarchy && loc.hierarchy.length > 1 ? loc.hierarchy[1].name : loc.current.name
                    locationObj.state = loc.hierarchy && loc.hierarchy.length > 0 ? loc.hierarchy[0].name : 'California'
                }
                
                userLocation.value = locationObj
                loadingLocation.value = false
            }
            locationDetectedVia.value = loc.detected_via || 'auto'
            fallbackMessage.value = loc.fallback_message || null
        }
        
        // Handle categories
        if (response.data.categories) {
            categories.value = response.data.categories
        }
        
        // Handle featured places
        if (response.data.featured_places) {
            featuredEntries.value = response.data.featured_places
        }
        
        // Handle curated lists
        if (response.data.curated_lists) {
            curatedLists.value = response.data.curated_lists
        }
        
        // Handle different response formats for entries
        if (response.data.entries?.data) {
            // Paginated response
            entries.value = response.data.entries.data
            pagination.value = {
                current_page: response.data.entries.current_page,
                last_page: response.data.entries.last_page,
                total: response.data.entries.total
            }
        } else if (response.data.places?.data) {
            // Alternative paginated response
            entries.value = response.data.places.data
            pagination.value = {
                current_page: response.data.places.current_page,
                last_page: response.data.places.last_page,
                total: response.data.places.total
            }
        } else {
            // Direct array response
            entries.value = response.data.entries || response.data.places || []
            pagination.value = {
                current_page: 1,
                last_page: 1,
                total: response.data.entries.length
            }
        }
        
        if (!categories.value.length) {
            categories.value = response.data.categories || []
        }
        
        if (!locationData.value.states) {
            locationData.value = response.data.locationData || {}
        }
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load places'
        console.error('Error fetching entries:', err)
    } finally {
        loading.value = false
    }
}

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchEntries(page)
    }
}

// Removed unused location filter handlers since we're using LocationSwitcher component

// Debounced search
let searchTimeout = null
const debouncedSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        fetchEntries()
    }, 300)
}

// Watch for search query changes
watch(searchQuery, () => {
    debouncedSearch()
})

// Handle location change from LocationSwitcher
const handleLocationChange = async (newLocation) => {
    // Update the selected location
    selectedLocation.value = newLocation
    
    // Update current region
    currentRegion.value = newLocation
    
    // Update user location
    userLocation.value = {
        city: newLocation.name,
        state: newLocation.parent?.name || newLocation.state,
        region_id: newLocation.id,
        lat: newLocation.lat,
        lng: newLocation.lng
    }
    
    // Fetch region hierarchy for the new location
    await fetchRegionHierarchy(newLocation)
    
    // Refresh the page data with new location
    fetchEntries()
}

// Helper to get state slug from name
const getStateSlug = (stateName) => {
    // Simple slugification - in production, you might want to fetch this from the API
    return stateName.toLowerCase().replace(/\s+/g, '-')
}

// Watch for route changes
watch(() => router.currentRoute.value.query, () => {
    fetchEntries()
})

// Fetch marketing content
const fetchMarketingContent = async () => {
    try {
        const response = await axios.get('/api/marketing-pages/places')
        if (response.data) {
            marketingContent.value = {
                heading: response.data.heading || marketingContent.value.heading,
                paragraph: response.data.paragraph || marketingContent.value.paragraph,
                image_url: response.data.image_url || null
            }
        }
    } catch (err) {
        console.error('Error fetching marketing content:', err)
        // Use defaults if fetch fails
    }
}

// Initialize
onMounted(async () => {
    document.title = 'Browse Places'
    
    // Start location detection and marketing content fetch in parallel
    Promise.all([
        detectUserLocation(),
        fetchMarketingContent()
    ])
    
    // Fetch initial data (will be filtered by location once detected)
    fetchEntries()
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