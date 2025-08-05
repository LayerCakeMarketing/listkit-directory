<template>
    <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">Location Regions</h2>
                        <div class="flex gap-2">
                            <button
                                @click="openCreateModal"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Add New Region
                            </button>
                            <button
                                @click="showBulkImportModal = true"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
                            >
                                <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                Bulk Import
                            </button>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4 mt-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                            <div class="text-sm text-blue-600 font-medium">Total Regions</div>
                            <div class="text-2xl font-bold text-blue-900">{{ stats.total || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                            <div class="text-sm text-green-600 font-medium">States</div>
                            <div class="text-2xl font-bold text-green-900">{{ stats.states || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg">
                            <div class="text-sm text-purple-600 font-medium">Cities</div>
                            <div class="text-2xl font-bold text-purple-900">{{ stats.cities || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg">
                            <div class="text-sm text-orange-600 font-medium">Neighborhoods</div>
                            <div class="text-2xl font-bold text-orange-900">{{ stats.neighborhoods || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-cyan-50 to-cyan-100 p-4 rounded-lg">
                            <div class="text-sm text-cyan-600 font-medium">State Parks</div>
                            <div class="text-2xl font-bold text-cyan-900">{{ stats.state_parks || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-teal-50 to-teal-100 p-4 rounded-lg">
                            <div class="text-sm text-teal-600 font-medium">National Parks</div>
                            <div class="text-2xl font-bold text-teal-900">{{ stats.national_parks || 0 }}</div>
                        </div>
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 p-4 rounded-lg">
                            <div class="text-sm text-indigo-600 font-medium">With Places</div>
                            <div class="text-2xl font-bold text-indigo-900">{{ stats.with_places || 0 }}</div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input
                                v-model="filters.search"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Region name..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                            <select
                                v-model="filters.level"
                                @change="fetchRegions"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Levels</option>
                                <option value="1">State</option>
                                <option value="2">City</option>
                                <option value="3">Neighborhood</option>
                                <option value="4">State Park</option>
                                <option value="5">National Park</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Region</label>
                            <select
                                v-model="filters.parent_id"
                                @change="fetchRegions"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Regions</option>
                                <option v-for="parent in parentRegions" :key="parent.id" :value="parent.id">
                                    {{ parent.name }} ({{ parent.type }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                @change="fetchRegions"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="name">Name</option>
                                <option value="cached_place_count">Places Count</option>
                                <option value="created_at">Created Date</option>
                                <option value="display_priority">Priority</option>
                            </select>
                        </div>
                    </div>

                    <!-- Quick Filters -->
                    <div class="flex flex-wrap gap-2 mt-4">
                        <button
                            @click="setQuickFilter('featured')"
                            :class="quickFilter === 'featured' ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Featured Only
                        </button>
                        <button
                            @click="setQuickFilter('has_content')"
                            :class="quickFilter === 'has_content' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700'"
                            class="px-3 py-1 rounded-full text-sm font-medium transition-colors"
                        >
                            Has Content
                        </button>
                        <button
                            v-if="quickFilter"
                            @click="clearQuickFilter"
                            class="px-3 py-1 bg-gray-300 text-gray-700 rounded-full text-sm font-medium transition-colors"
                        >
                            Clear Filter
                        </button>
                    </div>
                </div>

                <!-- Regions Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Loading State -->
                        <div v-if="loading" class="flex justify-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="regions.data && regions.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Region
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Parent
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Places
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="region in regions.data" :key="region.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img
                                                        v-if="region.cover_image"
                                                        class="h-10 w-10 rounded-lg object-cover"
                                                        :src="region.cover_image"
                                                        :alt="region.name"
                                                    />
                                                    <div v-else class="h-10 w-10 rounded-lg bg-gray-300 flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ region.name }}</div>
                                                    <div class="text-sm text-gray-500">/{{ region.slug }}</div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span v-if="region.is_featured" class="text-purple-600">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                            </svg>
                                                        </span>
                                                        <span v-if="region.intro_text || region.cover_image" class="text-indigo-600">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col gap-1">
                                                <span :class="getTypeBadgeClass(region.type)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                    {{ formatType(region.type) }}
                                                </span>
                                                <span v-if="region.designation" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                    {{ formatDesignation(region.designation) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div v-if="region.parent" class="text-sm text-gray-900">
                                                {{ region.parent.name }}
                                            </div>
                                            <div v-else class="text-sm text-gray-400">
                                                —
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ region.cached_place_count || 0 }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                <span v-if="region.is_featured" class="text-xs text-purple-600 font-medium">Featured</span>
                                                <span v-if="region.display_priority > 0" class="text-xs text-gray-500">
                                                    Priority: {{ region.display_priority }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button
                                                @click="editRegion(region)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <router-link
                                                :to="`/local/${getRegionPath(region)}`"
                                                target="_blank"
                                                class="text-green-600 hover:text-green-900 mr-3"
                                            >
                                                View
                                            </router-link>
                                            <button
                                                @click="manageFeatured(region)"
                                                class="text-purple-600 hover:text-purple-900 mr-3"
                                            >
                                                Featured
                                            </button>
                                            <button
                                                @click="viewPlaces(region)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Places
                                            </button>
                                            <button
                                                @click="deleteRegion(region)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No regions found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filters</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="regions.last_page > 1" class="mt-6">
                            <Pagination :links="regions.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal" max-width="2xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingRegion ? 'Edit Region' : 'Create New Region' }}
                </h3>

                <form @submit.prevent="saveRegion">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug</label>
                            <input
                                v-model="form.slug"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Auto-generated from name"
                            />
                            <div v-if="errors.slug" class="text-red-500 text-sm mt-1">{{ errors.slug }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type/Level</label>
                            <select
                                v-model="form.level"
                                required
                                @change="onLevelChange"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="1">State (Level 1)</option>
                                <option value="2">City (Level 2)</option>
                                <option value="3">Neighborhood (Level 3)</option>
                                <option value="4">State Park (Level 4)</option>
                                <option value="5">National Park (Level 5)</option>
                            </select>
                            <div v-if="errors.level" class="text-red-500 text-sm mt-1">{{ errors.level }}</div>
                        </div>

                        <div v-if="form.level > 1">
                            <label class="block text-sm font-medium text-gray-700">Parent Region</label>
                            <select
                                v-model="form.parent_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select parent...</option>
                                <option v-for="parent in availableParents" :key="parent.id" :value="parent.id">
                                    {{ parent.name }} ({{ getParentTypeLabel(parent) }})
                                </option>
                            </select>
                            <div v-if="errors.parent_id" class="text-red-500 text-sm mt-1">{{ errors.parent_id }}</div>
                        </div>

                        <div v-if="form.level == 2 || form.level == 3">
                            <label class="block text-sm font-medium text-gray-700">Designation</label>
                            <select
                                v-model="form.designation"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">None</option>
                                <option value="resort_town">Resort Town</option>
                                <option value="beach_town">Beach Town</option>
                                <option value="ski_town">Ski Town</option>
                                <option value="historic_town">Historic Town</option>
                                <option value="college_town">College Town</option>
                                <option value="arts_district">Arts District</option>
                                <option value="financial_district">Financial District</option>
                                <option value="tech_hub">Tech Hub</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Optional special designation for this region</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Introduction Text</label>
                            <textarea
                                v-model="form.intro_text"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Brief description of the region..."
                            ></textarea>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image</label>
                            <CloudflareDragDropUploader
                                :max-files="1"
                                :auto-upload="true"
                                context="cover"
                                :entity-type="'App\\Models\\Region'"
                                :entity-id="editingRegion?.id"
                                @upload-complete="handleCoverImageUpload"
                                @upload-error="handleUploadError"
                            />
                            <div v-if="form.cover_image" class="mt-4">
                                <p class="text-sm text-gray-600 mb-2">Current cover image:</p>
                                <div class="relative inline-block">
                                    <img :src="form.cover_image" alt="Cover image" class="h-32 w-auto rounded-lg" />
                                    <button
                                        type="button"
                                        @click="removeCoverImage"
                                        class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Display Priority</label>
                            <input
                                v-model.number="form.display_priority"
                                type="number"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Options</label>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input
                                        v-model="form.is_featured"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Featured Region</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ processing ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Featured Entries Modal -->
        <Modal :show="showFeaturedModal" @close="closeFeaturedModal" max-width="4xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Manage Featured Entries for {{ selectedRegion?.name }}
                </h3>

                <!-- This would be a more complex component to manage featured entries -->
                <div class="text-center py-8 text-gray-500">
                    Featured entries management interface would go here
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="closeFeaturedModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Places Modal -->
        <Modal :show="showPlacesModal" @close="closePlacesModal" max-width="4xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Places in {{ selectedRegion?.name }}
                </h3>

                <!-- Loading State -->
                <div v-if="loadingPlaces" class="flex justify-center py-8">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>

                <!-- Places List -->
                <div v-else-if="regionPlaces.data && regionPlaces.data.length > 0" class="space-y-4">
                    <div class="text-sm text-gray-500 mb-4">
                        Found {{ regionPlaces.total || regionPlaces.data.length }} places in this region
                    </div>
                    
                    <div class="max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Place
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Category
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="place in regionPlaces.data" :key="place.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ place.title }}</div>
                                        <div class="text-sm text-gray-500">ID: {{ place.id }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ place.category?.name || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ place.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="removePlace(place)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Remove from Region
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="regionPlaces.last_page > 1" class="mt-4">
                        <Pagination :links="regionPlaces.links" @navigate="fetchRegionPlaces" />
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8">
                    <p class="text-gray-500">No places found in this region.</p>
                </div>

                <div class="mt-6 flex justify-end">
                    <button
                        @click="closePlacesModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Bulk Import Modal -->
        <Modal :show="showBulkImportModal" @close="closeBulkImportModal" max-width="2xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Bulk Import Regions</h3>
                
                <div class="space-y-6">
                    <!-- Instructions -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-2">CSV Format Requirements:</h4>
                        <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                            <li>Required columns: name, type, level</li>
                            <li>Optional columns: parent_name, parent_id, slug, intro_text, meta_title, meta_description, is_featured, display_priority, cover_image</li>
                            <li>Use level 1 for states, 2 for cities, 3 for neighborhoods</li>
                            <li>Parent regions must exist before importing child regions</li>
                        </ul>
                        <a 
                            href="/api/admin/regions/bulk-import/template" 
                            download
                            class="inline-flex items-center mt-3 text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download Template CSV
                        </a>
                    </div>

                    <!-- File Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV File</label>
                        <input
                            type="file"
                            ref="csvFileInput"
                            accept=".csv,text/csv"
                            @change="handleFileSelect"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        />
                    </div>

                    <!-- Import Results -->
                    <div v-if="importResults" class="space-y-4">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <h4 class="font-medium text-green-900">Import Results:</h4>
                            <p class="text-sm text-green-800 mt-1">
                                {{ importResults.success }} regions imported successfully
                                <span v-if="importResults.failed > 0">, {{ importResults.failed }} failed</span>
                            </p>
                        </div>

                        <div v-if="importResults.errors && importResults.errors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <h4 class="font-medium text-red-900">Errors:</h4>
                            <ul class="mt-2 space-y-1">
                                <li v-for="(error, index) in importResults.errors" :key="index" class="text-sm text-red-800">
                                    Line {{ error.line }}: {{ error.name }} - {{ error.message }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        @click="closeBulkImportModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                    >
                        Close
                    </button>
                    <button
                        @click="importCsv"
                        :disabled="!selectedFile || importing"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
                    >
                        {{ importing ? 'Importing...' : 'Import' }}
                    </button>
                </div>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed, watch } from 'vue'
import Modal from '@/components/Modal.vue'
import Pagination from '@/components/Pagination.vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import { debounce } from 'lodash'
import axios from 'axios'

// State
const loading = ref(false)
const regions = ref({ data: [], links: [], last_page: 1 })
const parentRegions = ref([])
const stats = ref({})
const showModal = ref(false)
const showFeaturedModal = ref(false)
const showBulkImportModal = ref(false)
const showPlacesModal = ref(false)
const editingRegion = ref(null)
const selectedRegion = ref(null)
const processing = ref(false)
const errors = ref({})
const quickFilter = ref('')
const selectedFile = ref(null)
const csvFileInput = ref(null)
const importing = ref(false)
const importResults = ref(null)
const loadingPlaces = ref(false)
const regionPlaces = ref({ data: [], links: [], last_page: 1, total: 0 })

// Filters
const filters = reactive({
    search: '',
    level: '',
    parent_id: '',
    sort_by: 'name',
    sort_order: 'asc'
})

// Form
const form = reactive({
    name: '',
    slug: '',
    level: 1,
    parent_id: null,
    intro_text: '',
    is_featured: false,
    display_priority: 0,
    cover_image: null,
    cloudflare_image_id: null,
    designation: null
})

// Computed
const availableParents = computed(() => {
    if (form.level == 2) {
        // Cities can have States as parents
        return parentRegions.value.filter(r => r.level == 1)
    } else if (form.level == 3) {
        // Neighborhoods can have Cities as parents
        return parentRegions.value.filter(r => r.level == 2)
    } else if (form.level == 4 || form.level == 5) {
        // State Parks and National Parks can have States as parents (same level as cities)
        return parentRegions.value.filter(r => r.level == 1)
    }
    return []
})

// Methods
const fetchRegions = async (page = 1) => {
    loading.value = true
    try {
        const params = {
            page,
            ...filters
        }

        if (quickFilter.value === 'featured') {
            params.is_featured = true
        } else if (quickFilter.value === 'has_content') {
            params.has_content = true
        }

        const response = await axios.get('/api/admin/regions', { params })
        regions.value = response.data
    } catch (error) {
        console.error('Error fetching regions:', error)
    } finally {
        loading.value = false
    }
}

const fetchParentRegions = async () => {
    try {
        const response = await axios.get('/api/admin/regions', {
            params: { limit: 1000, per_page: 1000 }
        })
        parentRegions.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching parent regions:', error)
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/regions/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchRegions()
}, 300)

const setQuickFilter = (filter) => {
    quickFilter.value = quickFilter.value === filter ? '' : filter
    fetchRegions()
}

const clearQuickFilter = () => {
    quickFilter.value = ''
    fetchRegions()
}

const onLevelChange = () => {
    form.parent_id = null
}

const editRegion = (region) => {
    editingRegion.value = region
    form.name = region.name
    form.slug = region.slug
    form.level = region.level
    form.parent_id = region.parent_id
    form.intro_text = region.intro_text || ''
    form.is_featured = region.is_featured
    form.display_priority = region.display_priority || 0
    form.cover_image = region.cover_image || null
    form.cloudflare_image_id = region.cloudflare_image_id || null
    form.designation = region.designation || null
    showModal.value = true
}

const deleteRegion = async (region) => {
    if (!confirm(`Are you sure you want to delete "${region.name}"? This action cannot be undone.`)) return

    try {
        await axios.delete(`/api/admin/regions/${region.id}`)
        fetchRegions()
        fetchStats()
    } catch (error) {
        const errorMessage = error.response?.data?.error || error.response?.data?.message || 'Unknown error'
        
        // If the error is about associated entries, offer to view them
        if (errorMessage.includes('associated entries')) {
            if (confirm(errorMessage + '\n\nWould you like to view the places in this region?')) {
                viewPlaces(region)
            }
        } else {
            alert('Error deleting region: ' + errorMessage)
        }
    }
}

const saveRegion = async () => {
    processing.value = true
    errors.value = {}

    try {
        const data = {
            ...form,
            type: form.level == 1 ? 'state' : 
                  form.level == 2 ? 'city' : 
                  form.level == 3 ? 'neighborhood' :
                  form.level == 4 ? 'state_park' :
                  form.level == 5 ? 'national_park' : 'other'
        }

        if (editingRegion.value) {
            await axios.put(`/api/admin/regions/${editingRegion.value.id}`, data)
        } else {
            await axios.post('/api/admin/regions', data)
        }
        closeModal()
        fetchRegions()
        fetchStats()
        fetchParentRegions()
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else {
            alert('Error saving region: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

const handleCoverImageUpload = (uploadedImages) => {
    if (uploadedImages.length > 0) {
        const image = uploadedImages[0]
        form.cover_image = image.url
        form.cloudflare_image_id = image.id
    }
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    alert('Failed to upload image: ' + error.message)
}

const removeCoverImage = async () => {
    if (confirm('Are you sure you want to remove the cover image?')) {
        form.cover_image = null
        form.cloudflare_image_id = null
        
        // If editing existing region, also remove from server
        if (editingRegion.value) {
            try {
                await axios.delete(`/api/admin/regions/${editingRegion.value.id}/cover-image`)
            } catch (error) {
                console.error('Failed to remove cover image:', error)
            }
        }
    }
}

const manageFeatured = (region) => {
    selectedRegion.value = region
    showFeaturedModal.value = true
}

const closeModal = () => {
    showModal.value = false
    editingRegion.value = null
    form.name = ''
    form.slug = ''
    form.level = 1
    form.parent_id = null
    form.intro_text = ''
    form.is_featured = false
    form.display_priority = 0
    form.cover_image = null
    form.cloudflare_image_id = null
    form.designation = null
    errors.value = {}
}

const closeFeaturedModal = () => {
    showFeaturedModal.value = false
    selectedRegion.value = null
}

const formatType = (type) => {
    const types = {
        state: 'State',
        city: 'City',
        neighborhood: 'Neighborhood',
        state_park: 'State Park',
        national_park: 'National Park'
    }
    return types[type] || type
}

const formatDesignation = (designation) => {
    const designations = {
        resort_town: 'Resort Town',
        beach_town: 'Beach Town',
        ski_town: 'Ski Town',
        historic_town: 'Historic Town',
        college_town: 'College Town',
        arts_district: 'Arts District',
        financial_district: 'Financial District',
        tech_hub: 'Tech Hub'
    }
    return designations[designation] || designation
}

const getTypeBadgeClass = (type) => {
    const classes = {
        state: 'bg-green-100 text-green-800',
        city: 'bg-purple-100 text-purple-800',
        neighborhood: 'bg-orange-100 text-orange-800',
        state_park: 'bg-blue-100 text-blue-800',
        national_park: 'bg-teal-100 text-teal-800'
    }
    return classes[type] || 'bg-gray-100 text-gray-800'
}

const getParentTypeLabel = (parent) => {
    if (!parent) return ''
    return formatType(parent.type || getTypeFromLevel(parent.level))
}

const getTypeFromLevel = (level) => {
    const levelTypeMap = {
        1: 'state',
        2: 'city',
        3: 'neighborhood',
        4: 'state_park',
        5: 'national_park'
    }
    return levelTypeMap[level] || 'other'
}

const getRegionPath = (region) => {
    if (region.level === 1) {
        return region.slug
    } else if (region.level === 2 && region.parent) {
        return `${region.parent.slug}/${region.slug}`
    } else if (region.level === 3) {
        // Would need to load full hierarchy
        return region.slug
    }
    return region.slug
}

const openCreateModal = () => {
    editingRegion.value = null
    // Reset form
    form.name = ''
    form.slug = ''
    form.level = 1
    form.parent_id = null
    form.intro_text = ''
    form.is_featured = false
    form.display_priority = 0
    form.cover_image = null
    form.cloudflare_image_id = null
    form.designation = null
    errors.value = {}
    showModal.value = true
}

const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file && file.type === 'text/csv' || file.name.endsWith('.csv')) {
        selectedFile.value = file
        importResults.value = null
    } else {
        alert('Please select a valid CSV file')
        selectedFile.value = null
    }
}

const importCsv = async () => {
    if (!selectedFile.value) return
    
    importing.value = true
    importResults.value = null
    
    try {
        const formData = new FormData()
        formData.append('file', selectedFile.value)
        
        const response = await axios.post('/api/admin/regions/bulk-import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        
        importResults.value = response.data.results
        
        // Refresh regions list if successful
        if (response.data.results.success > 0) {
            fetchRegions()
            fetchStats()
            fetchParentRegions()
        }
    } catch (error) {
        console.error('Import error:', error)
        if (error.response?.data?.message) {
            alert('Import failed: ' + error.response.data.message)
        } else {
            alert('Failed to import CSV. Please check the file format.')
        }
    } finally {
        importing.value = false
    }
}

const closeBulkImportModal = () => {
    showBulkImportModal.value = false
    selectedFile.value = null
    importResults.value = null
    if (csvFileInput.value) {
        csvFileInput.value.value = ''
    }
}

const viewPlaces = (region) => {
    selectedRegion.value = region
    showPlacesModal.value = true
    fetchRegionPlaces()
}

const closePlacesModal = () => {
    showPlacesModal.value = false
    selectedRegion.value = null
    regionPlaces.value = { data: [], links: [], last_page: 1, total: 0 }
}

const fetchRegionPlaces = async (page = 1) => {
    if (!selectedRegion.value) return
    
    loadingPlaces.value = true
    try {
        const response = await axios.get(`/api/admin/regions/${selectedRegion.value.id}/places`, {
            params: { page, per_page: 20 }
        })
        regionPlaces.value = response.data
    } catch (error) {
        console.error('Error fetching region places:', error)
        alert('Failed to load places for this region')
    } finally {
        loadingPlaces.value = false
    }
}

const removePlace = async (place) => {
    if (!confirm(`Are you sure you want to remove "${place.title}" from this region?`)) return
    
    try {
        await axios.delete(`/api/admin/regions/${selectedRegion.value.id}/places/${place.id}`)
        
        // Refresh the places list
        fetchRegionPlaces()
        
        // Also refresh the main regions list to update place counts
        fetchRegions()
        fetchStats()
    } catch (error) {
        alert('Error removing place from region: ' + (error.response?.data?.message || 'Unknown error'))
    }
}

// Initialize
onMounted(() => {
    fetchRegions()
    fetchParentRegions()
    fetchStats()
})
</script>