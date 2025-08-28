<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-16 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Edit Profile</h1>
          
          <!-- Form Actions (moved to top) -->
          <div v-if="!loading" class="flex items-center space-x-4">
            <router-link
              v-if="profile.username || profile.custom_url"
              :to="{ name: 'UserProfile', params: { username: profile.custom_url || profile.username } }"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </router-link>
            <button
              v-else
              @click="$router.push('/home')"
              class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              Cancel
            </button>
            <button
              @click="saveProfile"
              :disabled="saving || activeTab === 'media' || activeTab === 'password' || activeTab === 'channels'"
              class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
            <a
              v-if="profile.username || profile.custom_url"
              :href="`/up/@${profile.custom_url || profile.username}`"
              target="_blank"
              class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-md text-sm font-medium hover:bg-gray-200"
              title="View Profile"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
              </svg>
            </a>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        </div>

        <!-- Tabbed Interface -->
        <div v-else>
          <!-- Tab Navigation -->
          <div class="border-b border-gray-200 mb-8">
            <nav class="-mb-px flex space-x-8">
              <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                  'py-2 px-1 border-b-2 font-medium text-sm',
                  activeTab === tab.id
                    ? 'border-indigo-500 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
                type="button"
              >
                {{ tab.name }}
              </button>
            </nav>
          </div>

          <!-- Tab Content -->
          <div class="space-y-8">
            <!-- Basic Information Tab -->
            <div v-show="activeTab === 'basic'" class="space-y-8">
              <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium mb-4">Basic Information</h2>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-gray-700">First Name</label>
                <input
                  v-model="form.firstname"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
                <p v-if="errors.firstname" class="mt-1 text-sm text-red-600">{{ errors.firstname }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                <input
                  v-model="form.lastname"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
                <p v-if="errors.lastname" class="mt-1 text-sm text-red-600">{{ errors.lastname }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input
                  v-model="form.email"
                  type="email"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
                <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Username</label>
                <input
                  v-model="form.username"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                  placeholder="Letters, numbers, dashes and underscores only"
                />
                <p v-if="errors.username" class="mt-1 text-sm text-red-600">{{ errors.username }}</p>
              </div>

              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Custom URL</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                  <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                    {{ baseUrl }}/up/@
                  </span>
                  <input
                    v-model="form.custom_url"
                    type="text"
                    :disabled="profile.has_custom_url"
                    :class="[
                      'flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500',
                      profile.has_custom_url ? 'bg-gray-100 cursor-not-allowed' : ''
                    ]"
                    placeholder="your-custom-url"
                    @blur="checkCustomUrl"
                  />
                </div>
                <p v-if="profile.has_custom_url" class="mt-1 text-sm text-amber-600">
                  Custom URL has been set and cannot be changed
                </p>
                <p v-else class="mt-1 text-sm text-amber-600">
                  Choose wisely - this cannot be changed once set!
                </p>
                <p v-if="urlCheckMessage && !profile.has_custom_url" :class="urlAvailable ? 'text-green-600' : 'text-red-600'" class="mt-1 text-sm">
                  {{ urlCheckMessage }}
                </p>
                <p v-if="errors.custom_url" class="mt-1 text-sm text-red-600">{{ errors.custom_url }}</p>
              </div>

              <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Bio</label>
                <textarea
                  v-model="form.bio"
                  rows="4"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  maxlength="500"
                ></textarea>
                <p class="mt-1 text-sm text-gray-500">{{ form.bio?.length || 0 }}/500 characters</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Gender (optional)</label>
                <select 
                  v-model="form.gender"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                  <option value="">Select gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="prefer_not_to_say">Prefer not to say</option>
                </select>
                <p v-if="errors.gender" class="mt-1 text-sm text-red-600">{{ errors.gender }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Date of Birth (optional)</label>
                <input
                  v-model="form.birthdate"
                  type="date"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <p v-if="errors.birthdate" class="mt-1 text-sm text-red-600">{{ errors.birthdate }}</p>
              </div>
            </div>
          </div>
            </div>

            <!-- Images Tab -->
            <div v-show="activeTab === 'images'" class="space-y-8">
              <!-- Profile Images -->
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium mb-4">Profile Images</h2>
            
            <div class="space-y-6">
              <!-- Avatar -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                <div class="flex items-start space-x-4">
                  <img
                    :src="profile.avatar_url ? `${profile.avatar_url}?t=${profileImageTimestamp}` : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(form.firstname + ' ' + form.lastname)"
                    alt="Avatar"
                    class="w-20 h-20 rounded-full object-cover"
                  />
                  <div class="flex-1">
                    <CloudflareDragDropUploader
                      v-if="profile.id"
                      :max-files="1"
                      context="avatar"
                      :entity-type="'App\\Models\\User'"
                      :entity-id="profile.id"
                      :metadata="{
                        entity_id: profile.id,
                        entity_type: 'User',
                        user_name: profile.firstname + ' ' + profile.lastname,
                        context: 'avatar'
                      }"
                      @upload-success="handleAvatarUpload"
                      :compact="true"
                      button-text="Change Avatar"
                    />
                  </div>
                </div>
              </div>

              <!-- Cover Image -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cover Image</label>
                <div class="space-y-3">
                  <div class="relative h-48 bg-gray-200 rounded-lg overflow-hidden">
                    <img
                      v-if="profile.cover_image_url"
                      :src="`${profile.cover_image_url}?t=${profileImageTimestamp}`"
                      alt="Cover"
                      class="w-full h-full object-cover"
                    />
                    <div v-else class="flex items-center justify-center h-full">
                      <p class="text-gray-500">No cover image</p>
                    </div>
                  </div>
                  <CloudflareDragDropUploader
                    v-if="profile.id"
                    :max-files="1"
                    context="cover"
                    :entity-type="'App\\Models\\User'"
                    :entity-id="profile.id"
                    :metadata="{
                      entity_id: profile.id,
                      entity_type: 'User',
                      user_name: profile.firstname + ' ' + profile.lastname,
                      context: 'cover'
                    }"
                    @upload-success="handleCoverUpload"
                    :compact="true"
                    button-text="Change Cover Image"
                  />
                </div>
              </div>
            </div>
          </div>
            </div>

            <!-- Settings Tab -->
            <div v-show="activeTab === 'settings'" class="space-y-8">
              <!-- Page Settings -->
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium mb-4">Page Settings</h2>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-gray-700">Page Title</label>
                <input
                  v-model="form.page_title"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="My Awesome Page"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Profile Color</label>
                <input
                  v-model="form.profile_color"
                  type="color"
                  class="mt-1 block h-10 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Location</label>
                <LocationAutocomplete
                  v-model="form.location"
                  placeholder="Enter city, state or zip code"
                  help-text="Start typing to search for your location"
                  :return-full-data="true"
                  @place-selected="handleLocationSelected"
                  @error="handleLocationError"
                />
                <p v-if="errors.location" class="mt-1 text-sm text-red-600">{{ errors.location }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Website</label>
                <input
                  v-model="form.website"
                  type="url"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="https://example.com"
                />
              </div>
            </div>
          </div>

          <!-- Privacy Settings -->
          <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-lg font-medium mb-4">Privacy Settings</h2>
            
            <div class="space-y-4">
              <label class="flex items-center">
                <input
                  v-model="form.show_activity"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700">Show activity on profile</span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="form.show_followers"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700">Show followers count</span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="form.show_location"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700">Show location</span>
              </label>

              <label class="flex items-center">
                <input
                  v-model="form.show_website"
                  type="checkbox"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700">Show website</span>
              </label>
            </div>
          </div>
            </div>

            <!-- Password Tab -->
            <div v-show="activeTab === 'password'" class="space-y-8">
              <PasswordChangeForm />
            </div>

            <!-- Media Tab -->
            <div v-show="activeTab === 'media'" class="space-y-8">
              <!-- User Media -->
              <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                  <div>
                    <h2 class="text-lg font-medium">Your Media</h2>
                    <p class="text-sm text-gray-600">View and manage images associated with your profile and lists</p>
                  </div>
                  <button
                    @click="exportAllMedia"
                    :disabled="exportingMedia"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50"
                  >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                    </svg>
                    {{ exportingMedia ? 'Preparing...' : 'Export All Media' }}
                  </button>
                </div>
                
                <div class="space-y-6">
                  <!-- Profile Images Section -->
                  <div>
                    <h3 class="text-base font-medium text-gray-900 mb-4">Profile Images</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <!-- Avatar -->
                      <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Avatar</h4>
                        <div class="flex items-center space-x-4">
                          <img
                            :src="profile.avatar_url ? `${profile.avatar_url}?t=${profileImageTimestamp}` : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(form.name)"
                            alt="Current avatar"
                            class="w-20 h-20 rounded-full object-cover"
                          />
                          <div>
                            <p class="text-sm text-gray-600">Current profile picture</p>
                            <p v-if="profile.avatar_url && profile.avatar_updated_at" class="text-xs text-gray-500">
                              Added {{ formatDate(profile.avatar_updated_at) }}
                            </p>
                            <p v-else-if="profile.avatar_url" class="text-xs text-gray-500">
                              Avatar set
                            </p>
                            <p v-else class="text-xs text-gray-500">Default avatar</p>
                          </div>
                        </div>
                      </div>

                      <!-- Cover Image -->
                      <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Cover Image</h4>
                        <div class="space-y-3">
                          <div class="relative h-32 bg-gray-200 rounded overflow-hidden">
                            <img
                              v-if="profile.cover_image_url"
                              :src="`${profile.cover_image_url}?t=${profileImageTimestamp}`"
                              alt="Current cover"
                              class="w-full h-full object-cover"
                            />
                            <div v-else class="flex items-center justify-center h-full">
                              <p class="text-xs text-gray-500">No cover image</p>
                            </div>
                          </div>
                          <p v-if="profile.cover_image_url && profile.cover_updated_at" class="text-xs text-gray-500">
                            Added {{ formatDate(profile.cover_updated_at) }}
                          </p>
                          <p v-else-if="profile.cover_image_url" class="text-xs text-gray-500">
                            Cover image set
                          </p>
                          <p v-else class="text-xs text-gray-500">No cover image set</p>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Lists Media -->
                  <div>
                    <h3 class="text-base font-medium text-gray-900 mb-3">List Images</h3>
                    <p class="text-sm text-gray-600 mb-4">Images from all your lists</p>
                    
                    <div v-if="userLists.length > 0" class="space-y-6">
                      <div
                        v-for="list in userLists"
                        :key="list.id"
                        class="border rounded-lg p-4"
                      >
                        <div class="flex items-center space-x-3 mb-4">
                          <img
                            v-if="list.cover_image_url"
                            :src="list.cover_image_url"
                            :alt="list.name"
                            class="w-12 h-12 rounded-lg object-cover"
                          />
                          <div v-else class="w-12 h-12 rounded-lg bg-gray-200 flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                          </div>
                          <div>
                            <h4 class="font-medium text-gray-900">{{ list.name }}</h4>
                            <p class="text-sm text-gray-500">{{ list.items_count || 0 }} items</p>
                          </div>
                        </div>
                        
                        <!-- Loading state for list images -->
                        <div v-if="listImages[list.id] === undefined" class="text-center py-4">
                          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                        </div>
                        
                        <!-- Image thumbnails -->
                        <div v-else-if="listImages[list.id] && listImages[list.id].length > 0" class="grid grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                          <div
                            v-for="image in listImages[list.id]"
                            :key="image.id"
                            class="relative group cursor-pointer"
                            @click="openImageLightbox(image)"
                          >
                            <div class="aspect-square bg-gray-100 rounded overflow-hidden">
                              <img
                                :src="getImageThumbnail(image)"
                                :alt="image.filename"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
                                loading="lazy"
                                @error="handleImageError($event, image)"
                              />
                            </div>
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-opacity rounded flex items-center justify-center">
                              <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                              </svg>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Empty state -->
                        <div v-else class="text-center py-4 bg-gray-50 rounded">
                          <p class="text-sm text-gray-500">No images in this list</p>
                        </div>
                      </div>
                    </div>
                    <div v-else class="text-center py-8 bg-gray-50 rounded-lg">
                      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                      <p class="mt-2 text-sm text-gray-900">No lists created yet</p>
                      <router-link to="/lists/create" class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                        Create Your First List
                      </router-link>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Channels Tab -->
            <div v-show="activeTab === 'channels'" class="space-y-8">
              <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                  <div>
                    <h2 class="text-lg font-medium">Your Channels</h2>
                    <p class="text-sm text-gray-600">Create and manage channels to organize your lists</p>
                  </div>
                  <router-link
                    to="/channels/create"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
                  >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Channel
                  </router-link>
                </div>

                <!-- Channels List -->
                <div v-if="channelsLoading" class="flex justify-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>

                <div v-else-if="channels.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div
                    v-for="channel in channels"
                    :key="channel.id"
                    class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                  >
                    <div class="flex items-start space-x-3">
                      <img
                        :src="channel.avatar_url || defaultChannelAvatar(channel.name)"
                        :alt="channel.name"
                        class="h-12 w-12 rounded-full"
                      >
                      <div class="flex-1">
                        <h3 class="font-medium text-gray-900">{{ channel.name }}</h3>
                        <p class="text-sm text-gray-500">@{{ channel.slug }}</p>
                        <p v-if="channel.description" class="text-sm text-gray-600 mt-1">{{ channel.description }}</p>
                        <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                          <span>{{ channel.lists_count }} lists</span>
                          <span>{{ channel.followers_count }} followers</span>
                        </div>
                      </div>
                    </div>
                    <div class="mt-4 flex items-center space-x-2">
                      <router-link
                        :to="`/${channel.slug}`"
                        class="flex-1 text-center px-3 py-1 border rounded text-sm text-gray-700 hover:bg-gray-50"
                      >
                        View
                      </router-link>
                      <router-link
                        :to="`/channels/${channel.id}/edit`"
                        class="flex-1 text-center px-3 py-1 border rounded text-sm text-gray-700 hover:bg-gray-50"
                      >
                        Edit
                      </router-link>
                    </div>
                  </div>
                </div>

                <div v-else class="text-center py-8">
                  <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4" />
                  </svg>
                  <p class="mt-2 text-sm text-gray-900">No channels created yet</p>
                  <p class="text-sm text-gray-500">Channels help you organize your lists by theme or topic</p>
                  <router-link
                    to="/channels/create"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700"
                  >
                    Create Your First Channel
                  </router-link>
                </div>
              </div>

              <!-- Followed Channels -->
              <div class="bg-white shadow rounded-lg p-6">
                <h2 class="text-lg font-medium mb-4">Followed Channels</h2>
                
                <div v-if="followedChannelsLoading" class="flex justify-center py-8">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                </div>

                <div v-else-if="followedChannels.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <router-link
                    v-for="channel in followedChannels"
                    :key="channel.id"
                    :to="`/${channel.slug}`"
                    class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                  >
                    <div class="flex items-start space-x-3">
                      <img
                        :src="channel.avatar_url || defaultChannelAvatar(channel.name)"
                        :alt="channel.name"
                        class="h-10 w-10 rounded-full"
                      >
                      <div>
                        <h3 class="font-medium text-gray-900">{{ channel.name }}</h3>
                        <p class="text-sm text-gray-500">by {{ channel.user.name }}</p>
                        <div class="mt-1 text-sm text-gray-500">
                          {{ channel.lists_count }} lists • {{ channel.followers_count }} followers
                        </div>
                      </div>
                    </div>
                  </router-link>
                </div>

                <div v-else class="text-center py-6 text-sm text-gray-500">
                  You haven't followed any channels yet
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
    
    <!-- Image Lightbox -->
    <div
      v-if="lightboxImage"
      @click="closeLightbox"
      class="fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4"
    >
      <div class="relative max-w-6xl max-h-full">
        <img
          :src="getImageUrl(lightboxImage)"
          :alt="lightboxImage.filename"
          class="max-w-full max-h-[90vh] object-contain"
          @click.stop
        />
        <button
          @click="closeLightbox"
          class="absolute top-4 right-4 text-white hover:text-gray-300"
        >
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        <div class="absolute bottom-4 left-4 text-white">
          <p class="text-lg font-medium">{{ lightboxImage.filename }}</p>
          <p class="text-sm opacity-75">{{ new Date(lightboxImage.uploaded_at).toLocaleDateString() }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import MediaViewer from '@/components/MediaViewer.vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import PasswordChangeForm from '@/components/profile/PasswordChangeForm.vue'
import LocationAutocomplete from '@/components/LocationAutocomplete.vue'
import { useNotification } from '@/composables/useNotification'

const router = useRouter()
const { showSuccess, showError } = useNotification()
const loading = ref(true)
const saving = ref(false)
const profile = ref({
  username: '',
  custom_url: '',
  has_custom_url: false,
  name: '',
  firstname: '',
  lastname: '',
  email: '',
  gender: '',
  birthdate: ''
})
const errors = reactive({})
const urlCheckMessage = ref('')
const urlAvailable = ref(null)
const activeTab = ref('basic')
const userLists = ref([])
const listImages = ref({})
const lightboxImage = ref(null)
const exportingMedia = ref(false)
const profileImageTimestamp = ref(Date.now())
const channels = ref([])
const channelsLoading = ref(false)
const followedChannels = ref([])
const followedChannelsLoading = ref(false)

const baseUrl = computed(() => window.location.origin)
const maxBirthdate = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() - 18)
  return date.toISOString().split('T')[0]
})

const tabs = [
  { id: 'basic', name: 'Basic Information' },
  { id: 'images', name: 'Images' },
  { id: 'settings', name: 'Settings' },
  { id: 'password', name: 'Password' },
  { id: 'media', name: 'Media' },
  { id: 'channels', name: 'Channels' }
]

const form = reactive({
  firstname: '',
  lastname: '',
  email: '',
  username: '',
  custom_url: '',
  gender: '',
  birthdate: '',
  bio: '',
  location: '',
  website: '',
  phone: '',
  page_title: '',
  display_title: '',
  profile_description: '',
  profile_color: '#3B82F6',
  show_activity: true,
  show_followers: true,
  show_following: true,
  show_join_date: true,
  show_location: true,
  show_website: true,
  page_logo_option: 'profile' // Required field with default value
})

// Handle location selection from autocomplete
function handleLocationSelected(locationData) {
  if (typeof locationData === 'string') {
    // Simple string format
    form.location = locationData
  } else if (locationData.formatted) {
    // Full data format with coordinates
    form.location = locationData.formatted
    
    // Optionally store coordinates for future use (e.g., map features)
    // You could add lat/lng fields to the user profile if needed
    if (locationData.coordinates) {
      // form.latitude = locationData.coordinates.lat
      // form.longitude = locationData.coordinates.lng
    }
  }
  
  // Clear any location errors
  if (errors.location) {
    errors.location = null
  }
}

// Handle location autocomplete errors
function handleLocationError(error) {
  console.error('Location autocomplete error:', error)
  showError('Unable to search locations. Please try again.')
}

// Fetch user profile data
async function fetchProfile() {
  try {
    const response = await axios.get('/api/profile')
    console.log('Profile data:', response.data.user)
    profile.value = response.data.user
    
    // Populate form with user data
    Object.keys(form).forEach(key => {
      if (profile.value[key] !== undefined) {
        // Special handling for birthdate - convert to YYYY-MM-DD format for date input
        if (key === 'birthdate' && profile.value[key]) {
          // If it's already a string in YYYY-MM-DD format, use it as is
          // Otherwise, extract the date part
          const dateValue = profile.value[key]
          form[key] = dateValue.split('T')[0] // This handles both "YYYY-MM-DD" and "YYYY-MM-DDTHH:mm:ss" formats
        } else {
          form[key] = profile.value[key]
        }
      }
    })
    
    // Ensure page_logo_option has a valid value
    if (!form.page_logo_option || !['profile', 'custom', 'none'].includes(form.page_logo_option)) {
      form.page_logo_option = 'profile'
    }
    
    // Update image timestamp to ensure fresh images
    profileImageTimestamp.value = Date.now()
  } catch (error) {
    console.error('Failed to fetch profile:', error)
  } finally {
    loading.value = false
  }
}

// Save profile changes
async function saveProfile() {
  saving.value = true
  errors.value = {}
  
  // Only send profile-related fields, exclude password fields
  const profileData = {
    firstname: form.firstname || '',
    lastname: form.lastname || '',
    email: form.email || '',
    username: form.username || '',
    gender: form.gender || null,
    birthdate: form.birthdate || null,
    bio: form.bio || null,
    location: form.location || null,
    website: form.website || null,
    phone: form.phone || null,
    page_title: form.page_title || null,
    display_title: form.display_title || null,
    profile_description: form.profile_description || null,
    profile_color: form.profile_color || '#3B82F6',
    show_activity: Boolean(form.show_activity ?? true),
    show_followers: Boolean(form.show_followers ?? true),
    show_following: Boolean(form.show_following ?? true),
    show_join_date: Boolean(form.show_join_date ?? true),
    show_location: Boolean(form.show_location ?? true),
    show_website: Boolean(form.show_website ?? true),
    page_logo_option: form.page_logo_option || 'profile'
  }
  
  // Only include custom_url if it hasn't been set before
  if (!profile.value.has_custom_url) {
    profileData.custom_url = form.custom_url || null
  }
  
  console.log('Saving profile with data:', profileData)
  
  // Validate required fields before sending
  if (!profileData.firstname || !profileData.lastname || !profileData.email || !profileData.username) {
    showError('First name, last name, email, and username are required fields')
    saving.value = false
    return
  }
  
  // Basic email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(profileData.email)) {
    showError('Please enter a valid email address')
    saving.value = false
    return
  }
  
  try {
    const response = await axios.put('/api/profile', profileData)
    
    // If we get a user object in response, update our local data
    if (response.data.user) {
      profile.value = response.data.user
    }
    
    // Show success notification
    showSuccess('Profile updated successfully!')
    
    // Refresh profile data to ensure everything is in sync
    await fetchProfile()
    
    // If custom URL changed, redirect to new URL
    if (form.custom_url && form.custom_url !== profile.value.custom_url) {
      router.push({ name: 'UserProfile', params: { username: form.custom_url } })
    }
  } catch (error) {
    if (error.response?.status === 422) {
      console.error('Validation errors:', error.response.data.errors)
      console.error('Data sent:', profileData)
      console.error('Full error response:', error.response.data)
      Object.assign(errors.value, error.response.data.errors)
      // Show all validation errors for debugging
      const allErrors = []
      for (const [field, fieldErrors] of Object.entries(error.response.data.errors)) {
        allErrors.push(`${field}: ${fieldErrors.join(', ')}`)
      }
      console.error('All validation errors:', allErrors.join(' | '))
      // Show the first validation error
      const firstError = Object.values(error.response.data.errors)[0]
      if (firstError && firstError[0]) {
        showError(firstError[0])
      }
    } else {
      console.error('Profile update error:', error)
      console.error('Error response:', error.response?.data)
      showError('Failed to update profile. Please try again.')
    }
  } finally {
    saving.value = false
  }
}

// Check if custom URL is available
async function checkCustomUrl() {
  if (!form.custom_url || form.custom_url === profile.value.custom_url) {
    urlCheckMessage.value = ''
    return
  }
  
  try {
    const response = await axios.post('/api/profile/check-url', {
      custom_url: form.custom_url
    })
    
    urlAvailable.value = response.data.available
    urlCheckMessage.value = response.data.message
  } catch (error) {
    console.error('Failed to check URL:', error)
  }
}

// Handle avatar upload success
async function handleAvatarUpload(result) {
  console.log('Avatar upload result:', result)
  
  if (result && result.id) {
    try {
      // Use the correct URL from the result
      const imageUrl = result.url || result.variants?.[0] || `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${result.id}/public`
      
      const response = await updateProfileImage('avatar', result.id, imageUrl)
      
      // Small delay to ensure Cloudflare has processed the image
      await new Promise(resolve => setTimeout(resolve, 500))
      
      // Fetch fresh profile data
      await fetchProfile()
      
      // Show success notification
      showSuccess('Avatar updated successfully!')
    } catch (error) {
      console.error('Avatar update failed:', error)
      showError('Failed to update avatar. Please try again.')
    }
  }
}

// Handle cover image upload success
async function handleCoverUpload(result) {
  console.log('Cover upload result:', result)
  
  if (result && result.id) {
    try {
      // Use the correct URL from the result
      const imageUrl = result.url || result.variants?.[0] || `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${result.id}/public`
      
      const response = await updateProfileImage('cover', result.id, imageUrl)
      
      // Small delay to ensure Cloudflare has processed the image
      await new Promise(resolve => setTimeout(resolve, 500))
      
      // Fetch fresh profile data
      await fetchProfile()
      
      // Show success notification
      showSuccess('Cover image updated successfully!')
    } catch (error) {
      console.error('Cover update failed:', error)
      showError('Failed to update cover image. Please try again.')
    }
  }
}

// Update profile image
async function updateProfileImage(type, cloudflareId, url) {
  try {
    const response = await axios.post('/api/profile/image', {
      type,
      cloudflare_id: cloudflareId,
      url
    })
    
    console.log('Image update response:', response.data)
    
    // Update local profile data with the full response
    if (response.data.user) {
      profile.value = response.data.user
      
      // Also update form data to keep in sync
      Object.keys(form).forEach(key => {
        if (response.data.user[key] !== undefined) {
          // Special handling for birthdate
          if (key === 'birthdate' && response.data.user[key]) {
            const dateValue = response.data.user[key]
            form[key] = dateValue.split('T')[0]
          } else {
            form[key] = response.data.user[key]
          }
        }
      })
      
      // Force reactive update
      profile.value = { ...profile.value }
    }
    
    return response.data
  } catch (error) {
    console.error('Failed to update image:', error)
    showError('Failed to update image. Please try again.')
    throw error
  }
}

// Fetch user's lists
async function fetchUserLists() {
  try {
    const response = await axios.get('/api/lists')
    userLists.value = response.data.data || []
    
    // Fetch images for each list
    userLists.value.forEach(list => {
      fetchListImages(list.id)
    })
  } catch (error) {
    console.error('Failed to fetch user lists:', error)
  }
}

// Fetch images for a specific list
async function fetchListImages(listId) {
  try {
    const response = await axios.get(`/api/entities/list/${listId}/media`)
    // Use Vue.set equivalent to ensure reactivity
    if (listImages.value) {
      listImages.value = {
        ...listImages.value,
        [listId]: response.data.images || []
      }
    }
  } catch (error) {
    console.error(`Failed to fetch images for list ${listId}:`, error)
    if (listImages.value) {
      listImages.value = {
        ...listImages.value,
        [listId]: []
      }
    }
  }
}

// Get thumbnail URL for an image
function getImageThumbnail(image) {
  if (image.variants && image.variants.length > 0) {
    // Try to use thumbnail variant first
    const url = image.variants[0]
    // For newer images with thumbnail variant
    return url.replace('/public', '/thumbnail')
  }
  if (image.url) {
    return image.url
  }
  // Fallback - construct URL manually
  const baseUrl = 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A'
  // Try thumbnail variant first, Cloudflare will fallback to public if not available
  return `${baseUrl}/${image.id}/thumbnail`
}

// Get full-size image URL
function getImageUrl(image) {
  if (image.variants && image.variants.length > 0) {
    return image.variants[0]
  }
  if (image.url) {
    return image.url
  }
  const baseUrl = 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A'
  return `${baseUrl}/${image.id}/public`
}

// Open image in lightbox
function openImageLightbox(image) {
  lightboxImage.value = image
}

// Close lightbox
function closeLightbox() {
  lightboxImage.value = null
}

// Handle image loading error - fallback to public variant
function handleImageError(event, image) {
  const img = event.target
  // If already tried fallback, don't retry
  if (img.dataset.fallback === 'true') return
  
  img.dataset.fallback = 'true'
  // Try the public variant instead
  if (image.variants && image.variants.length > 0) {
    img.src = image.variants[0]
  } else {
    const baseUrl = 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A'
    img.src = `${baseUrl}/${image.id}/public`
  }
}

// Format date for display
function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Export all media
async function exportAllMedia() {
  exportingMedia.value = true
  
  try {
    // Collect all media URLs
    const mediaUrls = []
    
    // Add avatar if exists
    if (profile.value.avatar_url) {
      mediaUrls.push({
        url: profile.value.avatar_url,
        filename: `avatar_${profile.value.id}.jpg`,
        type: 'avatar'
      })
    }
    
    // Add cover if exists
    if (profile.value.cover_image_url) {
      mediaUrls.push({
        url: profile.value.cover_image_url,
        filename: `cover_${profile.value.id}.jpg`,
        type: 'cover'
      })
    }
    
    // Add all list images
    for (const [listId, images] of Object.entries(listImages.value)) {
      const list = userLists.value.find(l => l.id == listId)
      images.forEach((image, index) => {
        mediaUrls.push({
          url: getImageUrl(image),
          filename: image.filename || `list_${listId}_image_${index + 1}.jpg`,
          type: 'list',
          listName: list?.name || `List ${listId}`
        })
      })
    }
    
    if (mediaUrls.length === 0) {
      showError('No media to export')
      return
    }
    
    // Option 1: Create a text file with all URLs (simple, no server overhead)
    const content = mediaUrls.map(item => 
      `${item.type.toUpperCase()}: ${item.listName || ''}\nFilename: ${item.filename}\nURL: ${item.url}\n`
    ).join('\n---\n\n')
    
    const blob = new Blob([content], { type: 'text/plain' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `media_export_${new Date().toISOString().split('T')[0]}.txt`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
    
    showSuccess(`Exported ${mediaUrls.length} media URLs to text file`)
    
  } catch (error) {
    console.error('Failed to export media:', error)
    showError('Failed to export media. Please try again.')
  } finally {
    exportingMedia.value = false
  }
}

// Watch for profile changes to update timestamp
watch(() => profile.value.avatar_url, () => {
  profileImageTimestamp.value = Date.now()
})

watch(() => profile.value.cover_image_url, () => {
  profileImageTimestamp.value = Date.now()
})

const fetchChannels = async () => {
  channelsLoading.value = true
  try {
    const response = await axios.get('/api/my-channels')
    channels.value = response.data
  } catch (error) {
    console.error('Error fetching channels:', error)
  } finally {
    channelsLoading.value = false
  }
}

const fetchFollowedChannels = async () => {
  followedChannelsLoading.value = true
  try {
    const response = await axios.get('/api/followed-channels')
    followedChannels.value = response.data.data
  } catch (error) {
    console.error('Error fetching followed channels:', error)
  } finally {
    followedChannelsLoading.value = false
  }
}

const defaultChannelAvatar = (name) => {
  const initials = name
    .split(' ')
    .map(word => word[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
  
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
    <rect width="100" height="100" fill="#6366f1"/>
    <text x="50" y="50" font-family="Arial, sans-serif" font-size="40" fill="white" text-anchor="middle" dominant-baseline="central">${initials}</text>
  </svg>`
  
  return `data:image/svg+xml;base64,${btoa(svg)}`
}

onMounted(async () => {
  try {
    await fetchProfile()
    await fetchUserLists()
    await fetchChannels()
    await fetchFollowedChannels()
  } catch (error) {
    console.error('Error during component initialization:', error)
  }
})
</script>