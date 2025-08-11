<template>
    <div>
        <!-- Breadcrumb -->
        <nav class="bg-white border-b border-gray-200 fixed left-0 right-0 z-40">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 py-3 text-sm">
                    <router-link to="/" class="text-gray-500 hover:text-gray-700">Home</router-link>
                    <span class="text-gray-400">/</span>
                    <router-link
                        v-if="authStore.user?.role === 'admin' || authStore.user?.role === 'manager'"
                        to="/admin/places"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        Admin Places
                    </router-link>
                    <router-link
                        v-else
                        to="/places"
                        class="text-gray-500 hover:text-gray-700"
                    >
                        Directory
                    </router-link>
                    <span class="text-gray-400">/</span>
                    <a :href="getEntryViewUrl()" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium">{{ entry?.title }}</a>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">Edit</span>
                </div>
            </div>
        </nav>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center items-center py-12 mt-24">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="loadError" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mt-24">
            <div class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">{{ errorTitle }}</h3>
                        <p class="text-sm text-red-700 mt-1">{{ loadError }}</p>
                        <div class="mt-3 text-sm">
                            <button 
                                @click="router.push('/places')"
                                class="text-red-800 hover:text-red-900 font-medium"
                            >
                                ← Back to Directory
                            </button>
                            <span v-if="errorType === 'auth'" class="mx-2">|</span>
                            <router-link 
                                v-if="errorType === 'auth'"
                                to="/login"
                                class="text-red-800 hover:text-red-900 font-medium"
                            >
                                Login
                            </router-link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div v-else-if="entry" class="min-h-screen bg-gray-50 pt-[2.8rem]">
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 lg:px-8 py-4 fixed left-0 right-0 z-30">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Edit: {{ entry.title }}</h1>
                        <p class="text-sm text-gray-600 mt-1">
                            Status: 
                            <span :class="getStatusClass(entry.status)" class="font-medium">
                                {{ entry.status.replace('_', ' ').charAt(0).toUpperCase() + entry.status.slice(1).replace('_', ' ') }}
                            </span>
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <!-- Success Message -->
                        <div v-if="showSuccessMessage" class="bg-green-50 border border-green-200 rounded-md px-3 py-2">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-green-800 text-sm font-medium">Entry updated successfully!</span>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <button
                            @click="showEditPanel = !showEditPanel"
                            class="bg-gray-200 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-300 transition-colors inline-flex items-center"
                            :title="showEditPanel ? 'Hide editor' : 'Show editor'"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="showEditPanel" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                        </button>
                        <router-link
                            :to="authStore.user?.role === 'admin' || authStore.user?.role === 'manager' ? '/admin/places' : '/myplaces'"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            Cancel
                        </router-link>
                        <button
                            @click="submitForm"
                            :disabled="processing"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            {{ processing ? 'Updating...' : 'Update Entry' }}
                        </button>
                        <button
                            v-if="entry.status === 'draft' || entry.status === 'rejected'"
                            @click="submitForReview"
                            :disabled="processing"
                            class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors disabled:opacity-50"
                        >
                            Submit for Review
                        </button>
                        <a
                            v-if="entry.status === 'published'"
                            :href="getEntryViewUrl()"
                            target="_blank"
                            class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors inline-flex items-center"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                            View Live
                        </a>
                    </div>
                </div>
            </div>

            <!-- Split View Container -->
            <div class="flex h-screen pt-[5.3rem]">
                <!-- Left Side: Edit Form -->
                <div :class="[showEditPanel ? 'w-5/12' : 'w-0 overflow-hidden']" class="bg-white border-r border-gray-200 overflow-y-auto transition-all duration-300">
                    <div v-if="showEditPanel" class="p-6">

                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <!-- Basic Information Section -->
                    <AccordionSection title="Basic Information" :default-open="true">
                        <div class="space-y-4">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    id="title"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</div>
                            </div>

                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700">
                                    URL Slug
                                    <span class="text-gray-500 font-normal text-xs ml-1">(leave blank to auto-generate)</span>
                                </label>
                                <input
                                    v-model="form.slug"
                                    type="text"
                                    id="slug"
                                    placeholder="e.g., my-business-name"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors.slug" class="mt-1 text-sm text-red-600">{{ errors.slug }}</div>
                                <p class="mt-1 text-xs text-gray-500">This will be used in the URL for your place</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Type *</label>
                                <select
                                    v-model="form.type"
                                    id="type"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="">Select a type</option>
                                    <option value="business_b2b">Business B2B</option>
                                    <option value="business_b2c">Business B2C</option>
                                    <option value="religious_org">Church, Synagogue, Temple or Other</option>
                                    <option value="point_of_interest">Point of Interest</option>
                                    <option value="area_of_interest">Area of Interest</option>
                                    <option value="service">Service</option>
                                    <option value="online">Online</option>
                                </select>
                                <div v-if="errors.type" class="mt-1 text-sm text-red-600">{{ errors.type }}</div>
                            </div>
                        </div>

                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">Category *</label>
                            <select
                                v-model="form.category_id"
                                id="category_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">Select a category</option>
                                <optgroup v-for="group in groupedCategories" :key="group.parent" :label="group.parent">
                                    <option v-for="category in group.children" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </optgroup>
                            </select>
                            <div v-if="errors.category_id" class="mt-1 text-sm text-red-600">{{ errors.category_id }}</div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <RichTextEditor
                                v-model="form.description"
                                :placeholder="'Write a detailed description about ' + (form.title || 'this place') + '...'"
                            />
                            <div v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</div>
                        </div>
                        </div>
                    </AccordionSection>

                    <!-- Contact + Social Section -->
                    <AccordionSection title="Contact + Social">
                        <div class="space-y-4">
                            <!-- Existing contact fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        id="email"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <div v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</div>
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        id="phone"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <div v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</div>
                                </div>
                            </div>

                            <div>
                                <label for="website_url" class="block text-sm font-medium text-gray-700">Website</label>
                                <input
                                    v-model="form.website_url"
                                    type="url"
                                    id="website_url"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors.website_url" class="mt-1 text-sm text-red-600">{{ errors.website_url }}</div>
                            </div>

                            <!-- Links Section -->
                            <div class="col-span-full">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Additional Links
                                    <span class="text-gray-500 font-normal text-xs ml-1">(e.g., Menu, Booking, Reviews)</span>
                                </label>
                                <div class="space-y-2">
                                    <div v-for="(link, index) in form.links" :key="index" class="flex gap-2">
                                        <input
                                            v-model="link.text"
                                            type="text"
                                            placeholder="Link text (e.g., View Menu)"
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <input
                                            v-model="link.url"
                                            type="url"
                                            placeholder="https://example.com"
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <button
                                            @click="removeLink(index)"
                                            type="button"
                                            class="p-2 text-red-600 hover:text-red-800"
                                            title="Remove link"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        @click="addLink"
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Link
                                    </button>
                                </div>
                            </div>

                            <!-- Social Media Fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="facebook_url" class="block text-sm font-medium text-gray-700">Facebook URL</label>
                                    <input
                                        v-model="form.facebook_url"
                                        type="url"
                                        id="facebook_url"
                                        placeholder="https://facebook.com/yourpage"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>

                                <div>
                                    <label for="instagram_handle" class="block text-sm font-medium text-gray-700">Instagram Handle</label>
                                    <input
                                        v-model="form.instagram_handle"
                                        type="text"
                                        id="instagram_handle"
                                        placeholder="@yourusername"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>

                                <div>
                                    <label for="twitter_handle" class="block text-sm font-medium text-gray-700">Twitter Handle</label>
                                    <input
                                        v-model="form.twitter_handle"
                                        type="text"
                                        id="twitter_handle"
                                        placeholder="@yourusername"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>

                                <div>
                                    <label for="youtube_channel" class="block text-sm font-medium text-gray-700">YouTube Channel</label>
                                    <input
                                        v-model="form.youtube_channel"
                                        type="url"
                                        id="youtube_channel"
                                        placeholder="https://youtube.com/c/yourchannel"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </AccordionSection>

                    <!-- Media Section -->
                    <AccordionSection title="Media">
                        <div class="space-y-4">
                        
                        <!-- Logo Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Logo
                                <span v-if="logoImages.length > 0" class="text-green-600 font-normal"> - {{ logoImages.length }} image(s) uploaded ✓</span>
                            </label>
                            
                            <!-- Show existing logo -->
                            <div v-if="form.logo_url" class="mb-4">
                                <p class="text-sm text-gray-500 mb-2">Current Logo:</p>
                                <div class="relative inline-block">
                                    <img 
                                        :src="form.logo_url" 
                                        alt="Current logo" 
                                        class="h-32 w-auto rounded border border-gray-300"
                                    />
                                    <button
                                        @click="removeLogo"
                                        type="button"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <CloudflareDragDropUploader
                                :max-files="1"
                                :max-file-size="2097152"
                                context="logo"
                                entity-type="App\Models\Place"
                                :entity-id="entry.id"
                                @upload-success="handleLogoUpload"
                                @upload-error="handleUploadError"
                            />
                        </div>

                        <!-- Cover Image Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Cover Image
                                <span v-if="coverImages.length > 0" class="text-green-600 font-normal"> - {{ coverImages.length }} image(s) uploaded ✓</span>
                            </label>
                            
                            <!-- Show existing cover image -->
                            <div v-if="form.cover_image_url" class="mb-4">
                                <p class="text-sm text-gray-500 mb-2">Current Cover Image:</p>
                                <div class="relative inline-block">
                                    <img 
                                        :src="form.cover_image_url" 
                                        alt="Current cover image" 
                                        class="h-48 w-auto rounded border border-gray-300"
                                    />
                                    <button
                                        @click="removeCoverImage"
                                        type="button"
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <CloudflareDragDropUploader
                                :max-files="1"
                                :max-file-size="5242880"
                                context="cover"
                                entity-type="App\Models\Place"
                                :entity-id="entry.id"
                                @upload-success="handleCoverUpload"
                                @upload-error="handleUploadError"
                            />
                        </div>

                        <!-- Gallery Images -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Gallery Images
                                <span v-if="galleryImages.length > 0" class="text-green-600 font-normal"> - {{ galleryImages.length }} image(s) uploaded ✓</span>
                            </label>
                            <CloudflareDragDropUploader
                                :max-files="20"
                                :max-file-size="14680064"
                                context="gallery"
                                entity-type="App\Models\Place"
                                :entity-id="entry.id"
                                @upload-success="handleGalleryUpload"
                                @upload-error="handleUploadError"
                            />
                            
                            <!-- Upload Results for Gallery -->
                            <DraggableImageGallery 
                                v-model:images="galleryImages"
                                title="New Gallery Images"
                                @remove="handleNewGalleryRemove"
                                @reorder="updateNewGalleryOrder"
                            />
                            
                            <!-- Existing Gallery Images -->
                            <DraggableImageGallery 
                                v-if="existingGalleryImages.length > 0"
                                v-model:images="existingGalleryImages"
                                title="Current Gallery Images"
                                @remove="handleExistingGalleryRemove"
                                @reorder="updateExistingGalleryOrder"
                            />
                        </div>
                        </div>
                    </AccordionSection>

                    <!-- Location/Address Section -->
                    <AccordionSection 
                        title="Address / Location"
                    >
                        <div class="space-y-4">
                            <!-- AddressInput Component with automatic geocoding -->
                            <AddressInput
                                v-model="addressData"
                                :errors="addressErrors"
                                :auto-geocode="true"
                                :show-map-preview="true"
                                @validation-complete="handleAddressValidation"
                            />
                            
                            <!-- Region Associations -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <!-- Neighborhood Association -->
                                <div>
                                    <label for="neighborhood_region" class="block text-sm font-medium text-gray-700">
                                        Neighborhood
                                        <span class="text-gray-500 text-xs ml-1">(optional)</span>
                                    </label>
                                    <select
                                        v-model="form.neighborhood_region_id"
                                        id="neighborhood_region"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        @change="updateRegionAssociations"
                                    >
                                        <option value="">Select a neighborhood...</option>
                                        <option 
                                            v-for="neighborhood in availableNeighborhoods" 
                                            :key="neighborhood.id"
                                            :value="neighborhood.id"
                                        >
                                            {{ neighborhood.name }}
                                        </option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Associate this place with a specific neighborhood
                                    </p>
                                </div>

                                <!-- Park Association -->
                                <div>
                                    <label for="park_region" class="block text-sm font-medium text-gray-700">
                                        Park or Recreation Area
                                        <span class="text-gray-500 text-xs ml-1">(optional)</span>
                                    </label>
                                    <select
                                        v-model="form.park_region_id"
                                        id="park_region"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        @change="updateRegionAssociations"
                                    >
                                        <option value="">Select a park...</option>
                                        <optgroup label="National Parks">
                                            <option 
                                                v-for="park in nationalParks" 
                                                :key="park.id"
                                                :value="park.id"
                                            >
                                                {{ park.name }}
                                            </option>
                                        </optgroup>
                                        <optgroup label="State Parks">
                                            <option 
                                                v-for="park in stateParks" 
                                                :key="park.id"
                                                :value="park.id"
                                            >
                                                {{ park.name }}
                                            </option>
                                        </optgroup>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">
                                        If this place is within or related to a park
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Additional location fields -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="zip_code" class="block text-sm font-medium text-gray-700">
                                        ZIP Code 
                                        <span v-if="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)">*</span>
                                    </label>
                                    <input
                                        v-model="form.location.zip_code"
                                        type="text"
                                        id="zip_code"
                                        :required="['business_b2b', 'business_b2c', 'religious_org', 'point_of_interest', 'area_of_interest'].includes(form.type)"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <div v-if="errors['location.zip_code']" class="mt-1 text-sm text-red-600">{{ errors['location.zip_code'] }}</div>
                                </div>

                                <div>
                                    <label for="neighborhood" class="block text-sm font-medium text-gray-700">Neighborhood <span class="text-gray-500 text-xs">(optional)</span></label>
                                    <input
                                        v-model="form.location.neighborhood"
                                        type="text"
                                        id="neighborhood"
                                        placeholder="e.g., Downtown, Midtown, Financial District"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <div v-if="errors['location.neighborhood']" class="mt-1 text-sm text-red-600">{{ errors['location.neighborhood'] }}</div>
                                </div>
                            </div>
                        </div>
                    </AccordionSection>

                </form>
                    </div>
                </div>

                <!-- Right Side: Preview -->
                <div :class="[showEditPanel ? 'w-7/12' : 'w-full']" class="bg-gray-100 overflow-hidden transition-all duration-300">
                    <div class="h-full">
                        <div class="bg-white border-b border-gray-200 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-700">Preview</h3>
                                <div class="text-xs text-gray-500">Updates as you type</div>
                            </div>
                        </div>
                        <div class="h-[calc(100%-3rem)] overflow-y-auto">
                            <PlacePreview :place="previewData" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import AccordionSection from '@/components/AccordionSection.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import DraggableImageGallery from '@/components/DraggableImageGallery.vue'
import PlacePreview from '@/components/PlacePreview.vue'
import AddressInput from '@/components/AddressInput.vue'
import { usStates } from '@/data/usStates'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// State
const loading = ref(true)
const loadError = ref(null)
const errorTitle = ref('Error loading entry')
const errorType = ref('general')
const entry = ref(null)
const categories = ref([])
const availableNeighborhoods = ref([])
const nationalParks = ref([])
const stateParks = ref([])

const form = reactive({
    title: '',
    slug: '',
    description: '',
    type: '',
    category_id: '',
    email: '',
    phone: '',
    website_url: '',
    links: [],
    logo_url: '',
    cover_image_url: '',
    gallery_images: [],
    
    // Region associations
    neighborhood_region_id: null,
    park_region_id: null,
    
    // Social Media
    facebook_url: '',
    instagram_handle: '',
    twitter_handle: '',
    youtube_channel: '',
    messenger_contact: '',
    
    // Location
    location: {
        address_line1: '',
        address_line2: '',
        city: '',
        state: '',
        zip_code: '',
        country: 'US',
        latitude: '',
        longitude: '',
        cross_streets: '',
        neighborhood: '',
    }
})

const errors = ref({})
const processing = ref(false)
const geocoding = ref(false)
const showSuccessMessage = ref(false)

// Address data for AddressInput component
const addressData = ref({
    address: '',
    city: '',
    state: '',
    latitude: null,
    longitude: null
})

// Computed errors for AddressInput
const addressErrors = computed(() => ({
    address: errors.value['location.address_line1'],
    city: errors.value['location.city'],
    state: errors.value['location.state'],
    latitude: errors.value['location.latitude'],
    longitude: errors.value['location.longitude']
}))

// Image upload state
const logoImages = ref([])
const coverImages = ref([])
const galleryImages = ref([])
const existingGalleryImages = ref([])

// Computed
const groupedCategories = computed(() => {
    const groups = {}
    
    // First, add parent categories
    categories.value.forEach(category => {
        if (!category.parent_id) {
            groups[category.name] = {
                parent: category.name,
                children: []
            }
        }
    })
    
    // Then, add children to their respective parents
    categories.value.forEach(category => {
        if (category.parent_id && category.parent) {
            const parentName = category.parent.name
            if (!groups[parentName]) {
                groups[parentName] = {
                    parent: parentName,
                    children: []
                }
            }
            groups[parentName].children.push(category)
        }
    })
    return Object.values(groups)
})

const canGeocode = computed(() => {
    return form.location.address_line1 && form.location.city && form.location.state
})

// Methods
const getEntryViewUrl = () => {
    if (!entry.value) return '#'
    
    // Use canonical URL structure: /places/state/city/category/entry-slug-id
    if (entry.value.state_region && entry.value.city_region && entry.value.category) {
        const state = entry.value.state_region.slug
        const city = entry.value.city_region.slug
        const category = entry.value.category.slug
        const entrySlug = `${entry.value.slug}-${entry.value.id}`
        return `/places/${state}/${city}/${category}/${entrySlug}`
    }
    
    // Fallback for entries without complete regional data
    return `/places/entry/${entry.value.id}`
}

const fetchEntryData = async () => {
    loading.value = true
    loadError.value = null

    // Check if user is authenticated first
    if (!authStore.user) {
        console.log('User not authenticated, waiting for auth check...')
        // Wait a bit for auth to load
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        if (!authStore.user) {
            errorTitle.value = 'Authentication required'
            errorType.value = 'auth'
            loadError.value = 'You must be logged in to edit entries.'
            loading.value = false
            return
        }
    }

    try {
        // Fetch entry data using admin endpoint for full edit access
        const entryResponse = await axios.get(`/api/admin/places/${route.params.id}`)
        console.log('Entry API response:', entryResponse.data)
        
        // Admin endpoint returns the entry directly
        entry.value = entryResponse.data
        
        // Fetch categories
        const categoriesResponse = await axios.get('/api/categories')
        console.log('Categories API response:', categoriesResponse.data)
        categories.value = categoriesResponse.data.categories || categoriesResponse.data || []
        
        // Initialize form with entry data FIRST
        console.log('About to initialize form with entry:', entry.value)
        initializeForm()
        
        // THEN fetch neighborhoods and parks after form is initialized
        await Promise.all([
            fetchNeighborhoods(),
            fetchParks()
        ])
        
        console.log('Form after initialization:', form)
        document.title = `Edit ${entry.value.title}`
    } catch (err) {
        console.error('Error fetching entry:', err)
        
        if (err.response?.status === 404) {
            errorTitle.value = 'Entry not found'
            errorType.value = 'notfound'
            loadError.value = 'The entry you are trying to edit could not be found. It may have been deleted or you may not have permission to view it.'
        } else if (err.response?.status === 401) {
            errorTitle.value = 'Authentication required'
            errorType.value = 'auth'
            loadError.value = 'You must be logged in to edit entries. Please login and try again.'
        } else if (err.response?.status === 403) {
            errorTitle.value = 'Permission denied'
            errorType.value = 'permission'
            loadError.value = 'You do not have permission to edit this entry. Only admins, managers, and entry owners can edit entries.'
        } else {
            errorTitle.value = 'Error loading entry'
            errorType.value = 'general'
            loadError.value = err.response?.data?.message || 'An unexpected error occurred while loading the entry. Please try again later.'
        }
        
        // Log detailed error info for debugging
        console.log('Edit page error details:', {
            status: err.response?.status,
            message: err.response?.data?.message,
            user: authStore.user,
            entryId: route.params.id
        })
    } finally {
        loading.value = false
    }
}

const initializeForm = () => {
    if (!entry.value) return
    
    form.title = entry.value.title || ''
    form.slug = entry.value.slug || ''
    form.description = entry.value.description || ''
    form.type = entry.value.type || ''
    form.category_id = entry.value.category_id || ''
    form.email = entry.value.email || ''
    form.phone = entry.value.phone || ''
    form.website_url = entry.value.website_url || ''
    form.links = entry.value.links || []
    form.logo_url = entry.value.logo_url || ''
    form.cover_image_url = entry.value.cover_image_url || ''
    form.gallery_images = entry.value.gallery_images || []
    
    // Region associations
    form.neighborhood_region_id = entry.value.neighborhood_region_id || null
    form.park_region_id = entry.value.park_region_id || null
    
    // Initialize existing logo image
    if (entry.value.logo_url) {
        logoImages.value = [{
            id: 'existing-logo',
            url: entry.value.logo_url,
            filename: 'Current Logo'
        }]
    }
    
    // Initialize existing cover image
    if (entry.value.cover_image_url) {
        coverImages.value = [{
            id: 'existing-cover',
            url: entry.value.cover_image_url,
            filename: 'Current Cover Image'
        }]
    }
    
    // Initialize existing gallery images for drag-and-drop component
    if (entry.value.gallery_images && entry.value.gallery_images.length > 0) {
        existingGalleryImages.value = entry.value.gallery_images.map((url, index) => ({
            id: `existing-${index}`,
            url: url,
            filename: `Gallery Image ${index + 1}`
        }))
    }
    
    // Social Media
    form.facebook_url = entry.value.facebook_url || ''
    form.instagram_handle = entry.value.instagram_handle || ''
    form.twitter_handle = entry.value.twitter_handle || ''
    form.youtube_channel = entry.value.youtube_channel || ''
    form.messenger_contact = entry.value.messenger_contact || ''
    
    // Set location data from either location relationship or direct fields
    if (entry.value.location) {
        form.location.address_line1 = entry.value.location.address_line1 || ''
        form.location.address_line2 = entry.value.location.address_line2 || ''
        form.location.city = entry.value.location.city || entry.value.city || ''
        form.location.state = entry.value.location.state || entry.value.state || ''
        form.location.zip_code = entry.value.location.zip_code || entry.value.zip_code || ''
        form.location.country = entry.value.location.country || 'US'
        form.location.latitude = entry.value.location.latitude || entry.value.latitude || ''
        form.location.longitude = entry.value.location.longitude || entry.value.longitude || ''
        form.location.cross_streets = entry.value.location.cross_streets || ''
        form.location.neighborhood = entry.value.location.neighborhood || ''
    } else {
        // Fallback to direct fields if location relationship doesn't exist
        form.location.address_line1 = entry.value.address_line1 || entry.value.address || ''
        form.location.city = entry.value.city || ''
        form.location.state = entry.value.state || ''
        form.location.zip_code = entry.value.zip_code || ''
        form.location.latitude = entry.value.latitude || ''
        form.location.longitude = entry.value.longitude || ''
    }
    
    // Always update addressData for AddressInput component
    addressData.value = {
        address: form.location.address_line1,
        city: form.location.city,
        state: form.location.state,
        latitude: form.location.latitude || null,
        longitude: form.location.longitude || null
    }
}

// Handle upload completions
const handleLogoUpload = (uploadResult) => {
    logoImages.value.push(uploadResult)
    form.logo_url = uploadResult.url
}

const handleCoverUpload = (uploadResult) => {
    coverImages.value.push(uploadResult)
    form.cover_image_url = uploadResult.url
}

const handleGalleryUpload = (uploadResult) => {
    galleryImages.value.push(uploadResult)
    form.gallery_images.push(uploadResult.url)
}

const handleNewGalleryRemove = (index, removedImage) => {
    const urlIndex = form.gallery_images.indexOf(removedImage.url)
    if (urlIndex > -1) {
        form.gallery_images.splice(urlIndex, 1)
    }
}

const updateNewGalleryOrder = (reorderedImages) => {
    const newUrls = reorderedImages.map(img => img.url)
    const existingUrls = existingGalleryImages.value.map(img => img.url)
    form.gallery_images = [...existingUrls, ...newUrls]
}

const handleExistingGalleryRemove = (index, removedImage) => {
    const urlIndex = form.gallery_images.indexOf(removedImage.url)
    if (urlIndex > -1) {
        form.gallery_images.splice(urlIndex, 1)
    }
}

const updateExistingGalleryOrder = (reorderedImages) => {
    const existingUrls = reorderedImages.map(img => img.url)
    const newUrls = galleryImages.value.map(img => img.url)
    form.gallery_images = [...existingUrls, ...newUrls]
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
}

const removeLogo = () => {
    form.logo_url = ''
    logoImages.value = []
}

const removeCoverImage = () => {
    form.cover_image_url = ''
    coverImages.value = []
}

// Fetch neighborhoods based on the city
const fetchNeighborhoods = async () => {
    try {
        // Get city from multiple possible sources
        const cityName = addressData.value.city || 
                        form.location.city || 
                        entry.value?.city ||
                        entry.value?.location?.city
                        
        console.log('Fetching neighborhoods for city:', cityName)
        
        if (!cityName) {
            console.log('No city found for fetching neighborhoods')
            return
        }
        
        // Fetch neighborhoods for this city
        const response = await axios.get('/api/regions/search', {
            params: {
                type: 'neighborhood',
                city: cityName,
                limit: 50
            }
        })
        
        console.log('Neighborhoods fetched:', response.data.data)
        availableNeighborhoods.value = response.data.data || []
    } catch (error) {
        console.error('Error fetching neighborhoods:', error)
        availableNeighborhoods.value = []
    }
}

// Fetch parks (state and national)
const fetchParks = async () => {
    try {
        const response = await axios.get('/api/regions/search', {
            params: {
                park_designation: true,
                limit: 100
            }
        })
        
        const parks = response.data.data || []
        nationalParks.value = parks.filter(p => p.park_designation === 'national_park')
        stateParks.value = parks.filter(p => p.park_designation === 'state_park')
    } catch (error) {
        console.error('Error fetching parks:', error)
        nationalParks.value = []
        stateParks.value = []
    }
}

// Update region associations when dropdowns change
const updateRegionAssociations = () => {
    // This will be called when the user selects a neighborhood or park
    // The form values are already bound via v-model
    console.log('Region associations updated:', {
        neighborhood: form.neighborhood_region_id,
        park: form.park_region_id
    })
}

// Handle address validation from AddressInput
const handleAddressValidation = (result) => {
    console.log('Address validation result:', result)
    if (result.valid && result.confidence >= 0.8) {
        // Clear any previous address errors
        delete errors.value['location.address_line1']
        delete errors.value['location.city']
        delete errors.value['location.state']
        delete errors.value['location.latitude']
        delete errors.value['location.longitude']
    }
    
    // Fetch neighborhoods when city changes
    if (result.city !== addressData.value.city) {
        fetchNeighborhoods()
    }
}

// Watch addressData and sync with form.location
watch(addressData, (newVal, oldVal) => {
    form.location.address_line1 = newVal.address
    form.location.city = newVal.city
    form.location.state = newVal.state
    form.location.latitude = newVal.latitude
    form.location.longitude = newVal.longitude
    
    // If city changed, fetch neighborhoods for the new city
    if (newVal.city && newVal.city !== oldVal?.city) {
        console.log('City changed to:', newVal.city)
        fetchNeighborhoods()
    }
}, { deep: true })

const submitForm = async () => {
    processing.value = true
    errors.value = {}
    
    try {
        const response = await axios.put(`/api/places/${entry.value.id}`, form)
        
        if (response.data) {
            showSuccessMessage.value = true
            
            // Hide success message after 5 seconds
            setTimeout(() => {
                showSuccessMessage.value = false
            }, 5000)
        }
    } catch (error) {
        console.error('Form submission error:', error)
        
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {}
        } else if (error.response?.status === 401) {
            router.push('/login')
        } else {
            alert('Error updating entry: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

// Watch for route changes
watch(() => route.params.id, (newId) => {
    if (newId) {
        fetchEntryData()
    }
})

// Link management methods
const addLink = () => {
    form.links.push({ text: '', url: '' })
}

const removeLink = (index) => {
    form.links.splice(index, 1)
}

// Preview related
const showEditPanel = ref(true)

// Computed property for preview data that combines entry data with form changes
const previewData = computed(() => {
    if (!entry.value) return {}
    
    return {
        ...entry.value,
        ...form,
        location: {
            ...entry.value.location,
            ...form.location
        },
        category: categories.value.find(c => c.id === form.category_id) || entry.value.category
    }
})

const getStatusClass = (status) => {
    const classes = {
        'draft': 'text-gray-600',
        'pending_review': 'text-yellow-600',
        'published': 'text-green-600',
        'rejected': 'text-red-600',
        'archived': 'text-gray-500'
    }
    return classes[status] || 'text-gray-600'
}

const submitForReview = async () => {
    if (!confirm('Are you ready to submit this place for review? Once submitted, you will not be able to edit it until it has been reviewed.')) {
        return
    }

    processing.value = true
    
    try {
        // First save any pending changes
        await submitForm()
        
        // Then update status to pending_review
        const response = await axios.patch(`/api/places/${route.params.id}/submit-for-review`)
        
        entry.value = response.data
        showSuccessMessage.value = true
        
        // Hide success message after 3 seconds
        setTimeout(() => {
            showSuccessMessage.value = false
        }, 3000)
        
        // Redirect to My Places after submission
        setTimeout(() => {
            router.push('/myplaces')
        }, 1000)
    } catch (error) {
        console.error('Error submitting for review:', error)
        alert('Failed to submit for review. Please try again.')
    } finally {
        processing.value = false
    }
}

// Initialize
onMounted(() => {
    fetchEntryData()
})
</script>