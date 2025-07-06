<template>
    <Head title="Media Management" />
    
    <AdminDashboardLayout>
        <template #header>
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Media Management</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage images and media files stored in Cloudflare Images</p>
                </div>
                <div class="mt-4 sm:mt-0 sm:flex sm:space-x-3">
                    <button
                        @click="showUploader = true"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        <CloudArrowUpIcon class="h-4 w-4 mr-2" />
                        Upload Images
                    </button>
                    <button
                        @click="runCleanup(true)"
                        :disabled="isLoading"
                        class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <MagnifyingGlassIcon class="h-4 w-4 mr-2" />
                        Preview Cleanup
                    </button>
                </div>
            </div>
        </template>

        <!-- Error Alert -->
        <div v-if="error" class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Error Loading Media</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <p>{{ error }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <PhotoIcon class="h-8 w-8 text-blue-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Images</dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ stats?.count ? stats.count.toLocaleString() : '—' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ServerIcon class="h-8 w-8 text-green-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Storage Used
                                <span v-if="!stats?.cloudflare_api_available" class="inline-flex items-center rounded-full bg-yellow-100 px-1.5 py-0.5 text-xs font-medium text-yellow-800 ml-1">
                                    DB
                                </span>
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ formatFileSize(stats?.current?.size || 0) }}
                            </dd>
                            <dd v-if="stats?.database?.total_tracked_size && stats?.cloudflare_api_available" class="text-xs text-gray-500">
                                Tracked: {{ formatFileSize(stats.database.total_tracked_size) }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ChartBarIcon class="h-8 w-8 text-purple-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Storage Limit
                                <span v-if="!stats?.cloudflare_api_available" class="inline-flex items-center rounded-full bg-yellow-100 px-1.5 py-0.5 text-xs font-medium text-yellow-800 ml-1">
                                    Est.
                                </span>
                            </dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ formatFileSize(stats?.allowed?.size || 0) }}
                            </dd>
                            <dd v-if="stats?.current?.size && stats?.allowed?.size" class="text-xs text-gray-500">
                                {{ Math.round((stats.current.size / stats.allowed.size) * 100) }}% used
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <ClockIcon class="h-8 w-8 text-orange-600" />
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Selected</dt>
                            <dd class="text-lg font-medium text-gray-900">
                                {{ selectedImages.length }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
            <!-- Search and Filter Controls -->
            <div class="space-y-4">
                <!-- Top Row: Search and Sort -->
                <div class="sm:flex sm:items-center sm:justify-between sm:space-x-4">
                    <div class="flex-1 max-w-lg">
                        <div class="relative">
                            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search images by filename or uploader..."
                                class="block w-full rounded-md border-0 py-1.5 pl-10 pr-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm"
                                @input="updateFilters"
                            />
                        </div>
                    </div>
                    <div class="mt-2 sm:mt-0 flex space-x-2">
                        <!-- Sort Control -->
                        <select
                            v-model="sortBy"
                            @change="updateFilters"
                            class="rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm"
                        >
                            <option value="uploaded">Sort by Upload Date</option>
                            <option value="filename">Sort by Filename</option>
                            <option value="size">Sort by File Size</option>
                            <option value="uploader">Sort by Uploader</option>
                        </select>
                        <!-- Sort Order -->
                        <button
                            @click="toggleSortOrder"
                            class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200"
                            :title="sortOrder === 'desc' ? 'Sort Ascending' : 'Sort Descending'"
                        >
                            <ChevronUpIcon v-if="sortOrder === 'asc'" class="h-4 w-4" />
                            <ChevronDownIcon v-else class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <!-- Filter Controls Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Uploader Filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Uploader</label>
                        <select
                            v-model="selectedUploader"
                            @change="updateFilters"
                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm"
                        >
                            <option value="">All Uploaders</option>
                            <option 
                                v-for="uploader in filterOptions?.uploaders || []" 
                                :key="uploader.id" 
                                :value="uploader.id"
                            >
                                {{ uploader.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Context Filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Context</label>
                        <select
                            v-model="selectedContext"
                            @change="updateFilters"
                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm"
                        >
                            <option value="">All Contexts</option>
                            <option 
                                v-for="context in filterOptions?.contexts || []" 
                                :key="context" 
                                :value="context"
                            >
                                {{ formatContext(context) }}
                            </option>
                        </select>
                    </div>

                    <!-- Entity Type Filter -->
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Entity Type</label>
                        <select
                            v-model="selectedEntityType"
                            @change="updateFilters"
                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm"
                        >
                            <option value="">All Types</option>
                            <option 
                                v-for="entityType in filterOptions?.entity_types || []" 
                                :key="entityType" 
                                :value="entityType"
                            >
                                {{ formatEntityType(entityType) }}
                            </option>
                        </select>
                    </div>

                    <!-- Places Filter (when entity type is DirectoryEntry) -->
                    <div v-if="selectedEntityType === 'App\\Models\\DirectoryEntry'">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Place</label>
                        <select
                            v-model="selectedEntityId"
                            @change="updateFilters"
                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm"
                        >
                            <option value="">All Places</option>
                            <option 
                                v-for="place in filterOptions?.places || []" 
                                :key="place.id" 
                                :value="place.id"
                            >
                                {{ place.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Clear Filters -->
                    <div class="flex items-end">
                        <button
                            @click="clearFilters"
                            class="w-full inline-flex justify-center items-center rounded-md bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200"
                        >
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 sm:flex sm:items-center sm:justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ images?.pagination?.count || 0 }} of {{ images?.pagination?.total_count || 0 }} images
                    <span v-if="!stats?.cloudflare_api_available" class="ml-2 inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                        Using database stats
                    </span>
                </div>
                <div class="mt-4 sm:mt-0 sm:flex sm:space-x-3">
                    <button
                        v-if="selectedImages.length > 0"
                        @click="bulkDeleteSelected"
                        :disabled="isLoading"
                        class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <TrashIcon class="h-4 w-4 mr-2" />
                        Delete Selected ({{ selectedImages.length }})
                    </button>
                    <button
                        @click="clearSelection"
                        v-if="selectedImages.length > 0"
                        class="inline-flex items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <!-- Images Grid -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div v-if="isLoading" class="p-8 text-center">
                <div class="inline-flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading images...
                </div>
            </div>

            <div v-else-if="filteredImages.length === 0" class="p-8 text-center">
                <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-semibold text-gray-900">No images found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ searchQuery ? 'Try adjusting your search query.' : 'Upload some images to get started.' }}
                </p>
            </div>

            <div v-else class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div
                        v-for="image in paginatedImages"
                        :key="image.id"
                        class="relative group cursor-pointer"
                        @click="toggleImageSelection(image.id)"
                    >
                        <!-- Checkbox -->
                        <div class="absolute top-2 left-2 z-10">
                            <input
                                type="checkbox"
                                :checked="selectedImages.includes(image.id)"
                                @click.stop="toggleImageSelection(image.id)"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600"
                            />
                        </div>

                        <!-- Image -->
                        <div class="aspect-square overflow-hidden rounded-lg bg-gray-100">
                            <img
                                :src="image.thumbnail"
                                :alt="image.filename"
                                class="h-full w-full object-cover transition-transform group-hover:scale-105"
                                @error="handleImageError"
                            />
                        </div>

                        <!-- Image Info -->
                        <div class="mt-2 space-y-1">
                            <p class="text-xs font-medium text-gray-900 truncate" :title="image.filename">
                                {{ image.filename }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ formatFileSize(image.size_bytes) }}
                            </p>
                            <div class="flex items-center space-x-1">
                                <span v-if="image.context" class="inline-flex items-center rounded-full bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">
                                    {{ formatContext(image.context) }}
                                </span>
                                <span v-if="!image.tracked_in_db" class="inline-flex items-center rounded-full bg-yellow-100 px-1.5 py-0.5 text-xs font-medium text-yellow-800">
                                    Untracked
                                </span>
                            </div>
                            <p v-if="image.user" class="text-xs text-gray-500 truncate" :title="`Uploaded by ${image.user.name}`">
                                <span class="font-medium">{{ image.user.name }}</span>
                            </p>
                            <p v-if="image.entity" class="text-xs text-blue-600 truncate" :title="`Associated with ${image.entity.name}`">
                                {{ image.entity.name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ formatDate(image.uploaded) }}
                            </p>
                        </div>

                        <!-- Actions Overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all rounded-lg">
                            <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    @click.stop="deleteImage(image.id)"
                                    class="p-1 bg-red-600 text-white rounded hover:bg-red-700"
                                    :title="`Delete ${image.filename}`"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="absolute bottom-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    @click.stop="viewImage(image)"
                                    class="p-1 bg-indigo-600 text-white rounded hover:bg-indigo-700"
                                    :title="`View ${image.filename}`"
                                >
                                    <EyeIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="mt-6 flex items-center justify-between border-t border-gray-200 pt-6">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <button
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage <= 1"
                            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Previous
                        </button>
                        <button
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage >= totalPages"
                            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next
                        </button>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to 
                                <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredImages.length) }}</span> of 
                                <span class="font-medium">{{ filteredImages.length }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <button
                                    @click="goToPage(currentPage - 1)"
                                    :disabled="currentPage <= 1"
                                    class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ChevronLeftIcon class="h-5 w-5" />
                                </button>
                                <button
                                    v-for="page in visiblePages"
                                    :key="page"
                                    @click="goToPage(page)"
                                    :class="[
                                        page === currentPage
                                            ? 'relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600'
                                            : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0'
                                    ]"
                                >
                                    {{ page }}
                                </button>
                                <button
                                    @click="goToPage(currentPage + 1)"
                                    :disabled="currentPage >= totalPages"
                                    class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <ChevronRightIcon class="h-5 w-5" />
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload Modal -->
        <Modal :show="showUploader" @close="showUploader = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload Images</h3>
                <CloudflareDragDropUploader
                    :max-files="20"
                    :max-file-size="14680064"
                    accepted-types="image/*"
                    @upload-success="handleUploadSuccess"
                    @upload-error="handleUploadError"
                />
                <div class="mt-6 flex justify-end">
                    <button
                        @click="showUploader = false"
                        class="rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Image Detail Modal -->
        <Modal :show="selectedImage !== null" @close="selectedImage = null">
            <div v-if="selectedImage" class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ selectedImage.filename }}</h3>
                    <button
                        @click="deleteImage(selectedImage.id)"
                        class="text-red-600 hover:text-red-800"
                    >
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
                
                <div class="space-y-4">
                    <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                        <img
                            :src="selectedImage.url"
                            :alt="selectedImage.filename"
                            class="h-full w-full object-contain"
                        />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">Size:</span>
                            {{ formatFileSize(selectedImage.size_bytes) }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Uploaded:</span>
                            {{ formatDate(selectedImage.uploaded) }}
                        </div>
                        <div v-if="selectedImage.dimensions.width">
                            <span class="font-medium text-gray-700">Dimensions:</span>
                            {{ selectedImage.dimensions.width }} × {{ selectedImage.dimensions.height }}
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">ID:</span>
                            <code class="text-xs bg-gray-100 px-1 rounded">{{ selectedImage.id }}</code>
                        </div>
                        <div v-if="selectedImage.user" class="col-span-2">
                            <span class="font-medium text-gray-700">Uploaded by:</span>
                            {{ selectedImage.user.name }} (ID: {{ selectedImage.user.id }})
                        </div>
                        <div v-if="selectedImage.context">
                            <span class="font-medium text-gray-700">Context:</span>
                            {{ formatContext(selectedImage.context) }}
                        </div>
                        <div v-if="selectedImage.entity" class="col-span-2">
                            <span class="font-medium text-gray-700">Associated with:</span>
                            {{ selectedImage.entity.name }} ({{ formatEntityType(selectedImage.entity_type) }})
                        </div>
                    </div>
                    
                    <div>
                        <span class="font-medium text-gray-700">URL:</span>
                        <input
                            :value="selectedImage.url"
                            readonly
                            class="mt-1 block w-full text-xs rounded-md border-gray-300 bg-gray-50"
                        />
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button
                        @click="selectedImage = null"
                        class="rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Cleanup Results Modal -->
        <Modal :show="cleanupResults !== null" @close="cleanupResults = null">
            <div v-if="cleanupResults" class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ cleanupResults.dry_run ? 'Cleanup Preview' : 'Cleanup Results' }}
                </h3>
                
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-700">Images found:</span>
                                {{ cleanupResults.count || cleanupResults.total_found || 0 }}
                            </div>
                            <div v-if="cleanupResults.total_size_bytes">
                                <span class="font-medium text-gray-700">Total size:</span>
                                {{ formatFileSize(cleanupResults.total_size_bytes) }}
                            </div>
                            <div v-if="cleanupResults.deleted_count">
                                <span class="font-medium text-gray-700">Deleted:</span>
                                {{ cleanupResults.deleted_count }}
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="cleanupResults.errors && cleanupResults.errors.length > 0" class="bg-red-50 rounded-lg p-4">
                        <h4 class="font-medium text-red-900 mb-2">Errors:</h4>
                        <ul class="text-sm text-red-800 space-y-1">
                            <li v-for="error in cleanupResults.errors" :key="error">{{ error }}</li>
                        </ul>
                    </div>
                    
                    <div class="text-sm text-gray-600">
                        {{ cleanupResults.message }}
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <button
                        v-if="cleanupResults.dry_run && cleanupResults.count > 0"
                        @click="runCleanup(false)"
                        class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                    >
                        Delete {{ cleanupResults.count }} Images
                    </button>
                    <button
                        @click="cleanupResults = null"
                        class="rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500"
                    >
                        Close
                    </button>
                </div>
            </div>
        </Modal>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import Modal from '@/Components/Modal.vue'
import CloudflareDragDropUploader from '@/Components/CloudflareDragDropUploader.vue'
import {
    PhotoIcon,
    ServerIcon,
    ChartBarIcon,
    ClockIcon,
    CloudArrowUpIcon,
    MagnifyingGlassIcon,
    TrashIcon,
    EyeIcon,
    ExclamationTriangleIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronUpIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    images: Object,
    stats: Object,
    error: String,
    filters: Object,
    filterOptions: Object
})

// Reactive data
const isLoading = ref(false)
const showUploader = ref(false)
const selectedImages = ref([])
const selectedImage = ref(null)
const cleanupResults = ref(null)
const searchQuery = ref(props.filters?.search || '')
const currentPage = ref(1)
const itemsPerPage = ref(24)

// Filter state variables
const selectedUploader = ref(props.filters?.uploader_id || '')
const selectedContext = ref(props.filters?.context || '')
const selectedEntityType = ref(props.filters?.entity_type || '')
const selectedEntityId = ref(props.filters?.entity_id || '')
const sortBy = ref(props.filters?.sort || 'uploaded')
const sortOrder = ref(props.filters?.order || 'desc')

// Computed properties - now using server-side filtered data
const allImages = computed(() => {
    return props.images?.images || []
})

const filteredImages = computed(() => {
    return allImages.value // Server-side filtering is now handled in the backend
})

const totalPages = computed(() => {
    return props.images?.pagination?.total_pages || 1
})

const paginatedImages = computed(() => {
    return filteredImages.value // Server-side pagination is now handled in the backend
})

const visiblePages = computed(() => {
    const pages = []
    const total = totalPages.value
    const current = currentPage.value
    
    let start = Math.max(1, current - 2)
    let end = Math.min(total, current + 2)
    
    for (let i = start; i <= end; i++) {
        pages.push(i)
    }
    
    return pages
})

// Methods
const formatFileSize = (bytes) => {
    if (!bytes) return '0 B'
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const toggleImageSelection = (imageId) => {
    const index = selectedImages.value.indexOf(imageId)
    if (index === -1) {
        selectedImages.value.push(imageId)
    } else {
        selectedImages.value.splice(index, 1)
    }
}

const clearSelection = () => {
    selectedImages.value = []
}

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page
    }
}

const deleteImage = async (imageId) => {
    if (!confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
        return
    }
    
    isLoading.value = true
    
    try {
        const response = await fetch(`/admin-data/media/${imageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        
        if (response.ok) {
            // Refresh the page to update the images list
            router.reload({ only: ['images', 'stats'] })
            selectedImage.value = null
        } else {
            const error = await response.json()
            alert(`Failed to delete image: ${error.message}`)
        }
    } catch (error) {
        alert(`Error deleting image: ${error.message}`)
    } finally {
        isLoading.value = false
    }
}

const bulkDeleteSelected = async () => {
    if (selectedImages.value.length === 0) return
    
    if (!confirm(`Are you sure you want to delete ${selectedImages.value.length} images? This action cannot be undone.`)) {
        return
    }
    
    isLoading.value = true
    
    try {
        const response = await fetch('/admin-data/media/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                image_ids: selectedImages.value
            })
        })
        
        const result = await response.json()
        
        if (result.success) {
            alert(result.message)
            selectedImages.value = []
            // Refresh the page to update the images list
            router.reload({ only: ['images', 'stats'] })
        } else {
            alert(`Failed to delete images: ${result.message}`)
        }
    } catch (error) {
        alert(`Error deleting images: ${error.message}`)
    } finally {
        isLoading.value = false
    }
}

const runCleanup = async (dryRun = true) => {
    isLoading.value = true
    
    try {
        const response = await fetch('/admin-data/media/cleanup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                dry_run: dryRun
            })
        })
        
        const result = await response.json()
        
        if (result.success) {
            cleanupResults.value = result
            
            if (!dryRun) {
                // Refresh the page after actual cleanup
                router.reload({ only: ['images', 'stats'] })
            }
        } else {
            alert(`Cleanup failed: ${result.message}`)
        }
    } catch (error) {
        alert(`Error running cleanup: ${error.message}`)
    } finally {
        isLoading.value = false
    }
}

// Filter methods
const updateFilters = () => {
    const params = new URLSearchParams()
    
    if (searchQuery.value) params.set('search', searchQuery.value)
    if (selectedUploader.value) params.set('uploader_id', selectedUploader.value)
    if (selectedContext.value) params.set('context', selectedContext.value)
    if (selectedEntityType.value) params.set('entity_type', selectedEntityType.value)
    if (selectedEntityId.value) params.set('entity_id', selectedEntityId.value)
    if (sortBy.value !== 'uploaded') params.set('sort', sortBy.value)
    if (sortOrder.value !== 'desc') params.set('order', sortOrder.value)
    
    const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '')
    router.visit(url, { 
        preserveState: true, 
        preserveScroll: true,
        only: ['images', 'stats', 'filters', 'filterOptions']
    })
}

const clearFilters = () => {
    searchQuery.value = ''
    selectedUploader.value = ''
    selectedContext.value = ''
    selectedEntityType.value = ''
    selectedEntityId.value = ''
    sortBy.value = 'uploaded'
    sortOrder.value = 'desc'
    updateFilters()
}

const toggleSortOrder = () => {
    sortOrder.value = sortOrder.value === 'desc' ? 'asc' : 'desc'
    updateFilters()
}

const formatContext = (context) => {
    if (!context) return 'Unknown'
    return context.charAt(0).toUpperCase() + context.slice(1).replace('_', ' ')
}

const formatEntityType = (entityType) => {
    if (!entityType) return 'Unknown'
    const parts = entityType.split('\\')
    return parts[parts.length - 1]
}

const viewImage = (image) => {
    selectedImage.value = image
}

const handleUploadSuccess = (result) => {
    // Refresh the page to show new uploads
    router.reload({ only: ['images', 'stats'] })
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
}

const handleImageError = (event) => {
    // Handle broken images
    event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjQiIGhlaWdodD0iMjQiIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTkgMTJMMTEgMTRMMTUgMTBNMjEgMTJDMjEgMTYuOTcwNiAxNi45NzA2IDIxIDEyIDIxQzcuMDI5NCAyMSAzIDE2Ljk3MDYgMyAxMkMzIDcuMDI5NCA3LjAyOTQgMyAxMiAzQzE2Ljk3MDYgMyAyMSA3LjAyOTQgMjEgMTJaIiBzdHJva2U9IiM5Q0EzQUYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+Cjwvc3ZnPgo='
}

// Watch for search query changes
watch(searchQuery, () => {
    currentPage.value = 1
})

onMounted(() => {
    // Any initialization logic
})
</script>