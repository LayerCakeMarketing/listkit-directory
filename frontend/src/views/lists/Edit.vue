<template>
    <div class="min-h-screen bg-gray-100">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-16">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Edit List: {{ list.name || 'Loading...' }}
                    </h2>
                    <div class="flex items-center space-x-4">
                        <button
                            v-if="hasSaved"
                            @click="previewList"
                            type="button"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Preview
                        </button>
                        <button
                            @click="updateList"
                            :disabled="savingList"
                            type="button"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ savingList ? 'Saving...' : 'Save' }}
                        </button>
                        <button
                            @click="settingsOpen = true"
                            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-md"
                            title="List Settings"
                        >
                            <Cog6ToothIcon class="h-5 w-5" />
                        </button>
                        <router-link
                            :to="backLink"
                            class="text-gray-600 hover:text-gray-900"
                        >
                            {{ backLinkText }}
                        </router-link>
                    </div>
                </div>

                <div v-if="loading" class="text-center">
                    <p class="text-gray-500">Loading list...</p>
                </div>
                <div v-else>
                    <!-- On Hold Warning -->
                    <div v-if="list.status === 'on_hold'" class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">This list is on hold</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p>This list has been put on hold by an administrator. It is not visible to the public.</p>
                                    <p v-if="list.status_reason" class="mt-1">Reason: {{ list.status_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Settings Drawer -->
                    <TransitionRoot as="template" :show="settingsOpen">
                        <Dialog class="relative z-50" @close="settingsOpen = false">
                            <div class="fixed inset-0 overflow-hidden">
                                <div class="absolute inset-0 overflow-hidden">
                                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full">
                                        <TransitionChild
                                            as="template"
                                            enter="transform transition ease-in-out duration-300"
                                            enter-from="translate-x-full"
                                            enter-to="translate-x-0"
                                            leave="transform transition ease-in-out duration-300"
                                            leave-from="translate-x-0"
                                            leave-to="translate-x-full"
                                        >
                                            <DialogPanel class="pointer-events-auto w-screen max-w-md">
                                                <div class="flex h-full flex-col overflow-y-auto bg-white shadow-2xl">
                                                    <div class="px-4 py-6 sm:px-6">
                                                        <div class="flex items-center justify-between">
                                                            <DialogTitle class="text-lg font-semibold text-gray-900">List Settings</DialogTitle>
                                                            <div class="ml-3 flex h-7 items-center">
                                                                <button
                                                                    type="button"
                                                                    class="rounded-md bg-white text-gray-400 hover:text-gray-500"
                                                                    @click="settingsOpen = false"
                                                                >
                                                                    <span class="sr-only">Close panel</span>
                                                                    <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="relative flex-1 px-4 sm:px-6">
                                                        <form @submit.prevent="updateList" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <input
                                        v-model="listForm.name"
                                        type="text"
                                        class="mt-1 block w-full rounded-md border-gray-300"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <RichTextEditor
                                        v-model="listForm.description"
                                        placeholder="Enter list description..."
                                        :max-height="200"
                                    />
                                </div>
                                <div>
                                    <CategorySelect
                                        v-model="listForm.category_id"
                                        label="Category *"
                                        placeholder="Select a category..."
                                        help-text="Choose the category that best describes your list"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
                                    <select
                                        v-model="listForm.channel_id"
                                        class="mt-1 block w-full rounded-md border-gray-300"
                                    >
                                        <option :value="null">No channel (personal list)</option>
                                        <option
                                            v-for="channel in userChannels"
                                            :key="channel.id"
                                            :value="channel.id"
                                        >
                                            {{ channel.name }} (@{{ channel.slug }})
                                        </option>
                                    </select>
                                    <p class="mt-1 text-sm text-gray-500">Assign this list to one of your channels</p>
                                </div>
                                <div>
                                    <TagInput
                                        v-model="listForm.tags"
                                        label="Tags"
                                        placeholder="Search or create tags..."
                                        help-text="Add tags to help people discover your list"
                                        :allow-create="true"
                                        :max-tags="10"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Visibility</label>
                                    <div class="space-y-2">
                                        <label class="flex items-start">
                                            <input
                                                v-model="listForm.visibility"
                                                value="public"
                                                type="radio"
                                                class="mt-0.5 rounded-full border-gray-300"
                                            />
                                            <div class="ml-3">
                                                <span class="block text-sm font-medium text-gray-700">Public</span>
                                                <span class="block text-xs text-gray-500">Visible to all logged-in users and appears in feeds</span>
                                            </div>
                                        </label>
                                        <label class="flex items-start">
                                            <input
                                                v-model="listForm.visibility"
                                                value="unlisted"
                                                type="radio"
                                                class="mt-0.5 rounded-full border-gray-300"
                                            />
                                            <div class="ml-3">
                                                <span class="block text-sm font-medium text-gray-700">Unlisted</span>
                                                <span class="block text-xs text-gray-500">Only accessible via direct URL</span>
                                            </div>
                                        </label>
                                        <label class="flex items-start">
                                            <input
                                                v-model="listForm.visibility"
                                                value="private"
                                                type="radio"
                                                class="mt-0.5 rounded-full border-gray-300"
                                            />
                                            <div class="ml-3">
                                                <span class="block text-sm font-medium text-gray-700">Private</span>
                                                <span class="block text-xs text-gray-500">Only visible to you and people you share with</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Photos
                                        <span v-if="photos.length > 0" class="text-green-600 font-normal"> - {{ photos.length }} photo(s) uploaded ✓</span>
                                    </label>
                                    <CloudflareDragDropUploader
                                        :max-files="10"
                                        :max-file-size="14680064"
                                        context="cover"
                                        entity-type="App\Models\UserList"
                                        :entity-id="listId"
                                        @upload-success="handlePhotoUpload"
                                        @upload-error="handleUploadError"
                                    />
                                    <!-- Upload Results with Drag & Drop Reordering -->
                                    <DraggableImageGallery 
                                        v-model:images="photos"
                                        title="Uploaded Photos"
                                        :show-primary="true"
                                        @remove="handleGalleryRemove"
                                        @reorder="updateImageOrder"
                                    />
                                    <!-- Existing Gallery Images -->
                                    <DraggableImageGallery 
                                        v-if="existingPhotos.length > 0"
                                        v-model:images="existingPhotos"
                                        title="Current Photos"
                                        :show-primary="true"
                                        @remove="handleExistingGalleryRemove"
                                        @reorder="updateExistingImageOrder"
                                    />
                                    <p class="text-xs text-gray-500 mt-1">Add photos for your list. The first photo will be used as the featured image. Drag to reorder them.</p>
                                </div>
                                                        </form>
                                                        
                                                        <!-- Publishing Options -->
                                                        <div class="border-t px-4 py-4 mt-4">
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">Publishing Options</label>
                                                            <div class="space-y-3">
                                                                <label class="flex items-center">
                                                                    <input
                                                                        v-model="listForm.is_draft"
                                                                        type="checkbox"
                                                                        class="rounded border-gray-300"
                                                                    />
                                                                    <span class="ml-2 text-sm text-gray-700">Save as draft</span>
                                                                </label>
                                                                <div v-if="!listForm.is_draft">
                                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule for later</label>
                                                                    <input
                                                                        v-model="listForm.scheduled_for"
                                                                        type="datetime-local"
                                                                        class="w-full rounded-md border-gray-300"
                                                                        :min="new Date().toISOString().slice(0, 16)"
                                                                    />
                                                                    <p class="text-xs text-gray-500 mt-1">Leave empty to publish immediately</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- List Sharing (Private Lists Only) -->
                                                        <div v-if="listForm.visibility === 'private'" class="border-t px-4 py-4 mt-4">
                                                            <h3 class="text-lg font-semibold mb-4">Share List</h3>
                                                            <!-- Add New Share -->
                                                            <div class="space-y-4 mb-6">
                                                                <div>
                                                                    <label class="block text-sm font-medium text-gray-700 mb-2">Share with user</label>
                                                                    <input
                                                                        v-model="shareSearch"
                                                                        @input="debouncedSearchUsers"
                                                                        type="text"
                                                                        placeholder="Search by name, username, or email..."
                                                                        class="w-full rounded-md border-gray-300"
                                                                    />
                                                                    <div v-if="userSearchResults.length > 0" class="mt-2 max-h-40 overflow-y-auto border rounded">
                                                                        <button
                                                                            v-for="user in userSearchResults"
                                                                            :key="user.id"
                                                                            @click="selectUserToShare(user)"
                                                                            class="w-full text-left p-2 hover:bg-gray-100 border-b last:border-b-0 flex items-center space-x-2"
                                                                        >
                                                                            <div>
                                                                                <div class="font-medium">{{ user.name }}</div>
                                                                                <div class="text-sm text-gray-500">@{{ user.username || user.email }}</div>
                                                                            </div>
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                                <div v-if="selectedUser" class="p-3 bg-gray-50 rounded border">
                                                                    <div class="flex items-center justify-between mb-3">
                                                                        <div>
                                                                            <div class="font-medium">{{ selectedUser.name }}</div>
                                                                            <div class="text-sm text-gray-500">@{{ selectedUser.username || selectedUser.email }}</div>
                                                                        </div>
                                                                        <button @click="clearSelectedUser" class="text-gray-400 hover:text-gray-600">
                                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                                            </svg>
                                                                        </button>
                                                                    </div>
                                                                    <div class="space-y-2">
                                                                        <div>
                                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Permission</label>
                                                                            <select v-model="shareForm.permission" class="w-full rounded-md border-gray-300">
                                                                                <option value="view">View only</option>
                                                                                <option value="edit">Can edit</option>
                                                                            </select>
                                                                        </div>
                                                                        <div>
                                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Expires (optional)</label>
                                                                            <input
                                                                                v-model="shareForm.expires_at"
                                                                                type="datetime-local"
                                                                                class="w-full rounded-md border-gray-300"
                                                                                :min="new Date().toISOString().slice(0, 16)"
                                                                            />
                                                                        </div>
                                                                        <button
                                                                            @click="shareList"
                                                                            :disabled="sharing"
                                                                            class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                                                                        >
                                                                            {{ sharing ? 'Sharing...' : 'Share List' }}
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Current Shares -->
                                                            <div v-if="shares.length > 0">
                                                                <h4 class="font-medium text-gray-900 mb-3">Current Shares</h4>
                                                                <div class="space-y-2">
                                                                    <div
                                                                        v-for="share in shares"
                                                                        :key="share.id"
                                                                        class="flex items-center justify-between p-3 bg-gray-50 rounded border"
                                                                    >
                                                                        <div>
                                                                            <div class="font-medium">{{ share.user.name }}</div>
                                                                            <div class="text-sm text-gray-500">
                                                                                {{ share.permission }} access
                                                                                <span v-if="share.expires_at"> • Expires {{ formatDate(share.expires_at) }}</span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="flex items-center space-x-2">
                                                                            <button
                                                                                @click="editShare(share)"
                                                                                class="text-blue-600 hover:text-blue-800 text-sm"
                                                                            >
                                                                                Edit
                                                                            </button>
                                                                            <button
                                                                                @click="removeShare(share)"
                                                                                class="text-red-600 hover:text-red-800 text-sm"
                                                                            >
                                                                                Remove
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div v-else class="text-sm text-gray-500 text-center py-4">
                                                                No one has access to this private list yet.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </DialogPanel>
                                        </TransitionChild>
                                    </div>
                                </div>
                            </div>
                        </Dialog>
                    </TransitionRoot>

                    <!-- List Items (full width when drawer is closed) -->
                    <div class="flex flex-col gap-6">
                        <!-- Add New Item -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold mb-4">Add Item</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Item Type</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            v-for="type in itemTypes"
                                            :key="type.value"
                                            type="button"
                                            @click="selectedItemType = type.value"
                                            :class="[
                                                'p-2 text-sm rounded border',
                                                selectedItemType === type.value
                                                    ? 'bg-blue-500 text-white border-blue-500'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                            ]"
                                        >
                                            {{ type.label }}
                                        </button>
                                    </div>
                                </div>
                                <!-- Directory Entry Search -->
                                <div v-if="selectedItemType === 'directory_entry'">
                                    <div class="flex gap-2">
                                        <input
                                            v-model="entrySearch"
                                            @input="debouncedSearchEntries"
                                            type="text"
                                            placeholder="Search directory entries..."
                                            class="flex-1 rounded-md border-gray-300"
                                        />
                                        <button
                                            @click="showSavedItems = true"
                                            type="button"
                                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-sm font-medium"
                                        >
                                            From Saved
                                        </button>
                                    </div>
                                    <div v-if="searchResults.length > 0" class="mt-2 max-h-60 overflow-y-auto border rounded">
                                        <button
                                            v-for="entry in searchResults"
                                            :key="entry.id"
                                            type="button"
                                            @click="addDirectoryEntry(entry)"
                                            class="w-full text-left p-2 hover:bg-gray-100 border-b last:border-b-0"
                                        >
                                            <div class="font-medium">{{ entry.title }}</div>
                                            <div class="text-sm text-gray-500">{{ entry.category?.name }}</div>
                                        </button>
                                    </div>
                                </div>
                                <!-- Text Item -->
                                <div v-if="selectedItemType === 'text'" class="space-y-2">
                                    <input
                                        v-model="newItem.title"
                                        type="text"
                                        placeholder="Title"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <textarea
                                        v-model="newItem.content"
                                        rows="3"
                                        placeholder="Content"
                                        class="w-full rounded-md border-gray-300"
                                    ></textarea>
                                    <button
                                        type="button"
                                        @click="addTextItem"
                                        class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Add Text
                                    </button>
                                </div>
                                <!-- Location Item -->
                                <div v-if="selectedItemType === 'location'" class="space-y-2">
                                    <input
                                        v-model="newItem.title"
                                        type="text"
                                        placeholder="Location name"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <input
                                        v-model="newItem.data.address"
                                        type="text"
                                        placeholder="Address"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <div class="grid grid-cols-2 gap-2">
                                        <input
                                            v-model.number="newItem.data.latitude"
                                            type="number"
                                            step="any"
                                            placeholder="Latitude"
                                            class="rounded-md border-gray-300"
                                        />
                                        <input
                                            v-model.number="newItem.data.longitude"
                                            type="number"
                                            step="any"
                                            placeholder="Longitude"
                                            class="rounded-md border-gray-300"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="addLocationItem"
                                        class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Add Location
                                    </button>
                                </div>
                                <!-- Event Item -->
                                <div v-if="selectedItemType === 'event'" class="space-y-2">
                                    <input
                                        v-model="newItem.title"
                                        type="text"
                                        placeholder="Event name"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <textarea
                                        v-model="newItem.content"
                                        rows="2"
                                        placeholder="Description"
                                        class="w-full rounded-md border-gray-300"
                                    ></textarea>
                                    <input
                                        v-model="newItem.data.start_date"
                                        type="datetime-local"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <input
                                        v-model="newItem.data.location"
                                        type="text"
                                        placeholder="Event location"
                                        class="w-full rounded-md border-gray-300"
                                    />
                                    <button
                                        type="button"
                                        @click="addEventItem"
                                        class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Add Event
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- List Items -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-6 border-b">
                                <h3 class="text-lg font-semibold">List Items</h3>
                                <p class="text-sm text-gray-500 mt-1">Drag to reorder items</p>
                            </div>
                            <div v-if="items.length === 0" class="p-6 text-center text-gray-500">
                                No items yet. Add some content to your list!
                            </div>
                            <draggable
                                v-else
                                v-model="items"
                                @end="handleReorder"
                                item-key="id"
                                class="divide-y"
                                handle=".drag-handle"
                            >
                                <template #item="{ element, index }">
                                    <div class="p-4 hover:bg-gray-50 cursor-move">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0 mt-1 drag-handle cursor-grab">
                                                <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                </svg>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between">
                                                    <h4 class="font-medium text-gray-900">
                                                        {{ element.display_title || element.title }}
                                                    </h4>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                                                            {{ element.type }}
                                                        </span>
                                                        <button
                                                            type="button"
                                                            @click="editItem(element)"
                                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            @click="removeItem(element)"
                                                            class="text-red-600 hover:text-red-800 text-sm"
                                                        >
                                                            Remove
                                                        </button>
                                                    </div>
                                                </div>
                                                <p v-if="element.display_content || element.content" class="text-sm text-gray-600 mt-1">
                                                    {{ truncate(element.display_content || element.content, 150) }}
                                                </p>
                                                <div v-if="element.type === 'location' && element.data" class="text-sm text-gray-500 mt-1">
                                                    📍 {{ element.data.address }}
                                                </div>
                                                <div v-if="element.type === 'event' && element.data" class="text-sm text-gray-500 mt-1">
                                                    📅 {{ formatDate(element.data.start_date) }}
                                                    <span v-if="element.data.location"> @ {{ element.data.location }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </draggable>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Item Modal -->
        <Modal :show="showEditModal" @close="closeEditModal" max-width="2xl">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Edit Item
                </h3>
                <form @submit.prevent="updateItem" class="space-y-4">
                    <div v-if="editingItem.type !== 'directory_entry'">
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input
                            v-model="editForm.title"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            required
                        />
                    </div>
                    <div v-if="['text', 'event'].includes(editingItem.type)">
                        <label class="block text-sm font-medium text-gray-700">Content</label>
                        <RichTextEditor
                            v-model="editForm.content"
                            placeholder="Enter item content..."
                            :max-height="300"
                        />
                    </div>
                    <div v-if="editingItem.type === 'directory_entry' || editForm.affiliate_url !== undefined">
                        <label class="block text-sm font-medium text-gray-700">Affiliate URL</label>
                        <input
                            v-model="editForm.affiliate_url"
                            type="url"
                            class="mt-1 block w-full rounded-md border-gray-300"
                            placeholder="https://example.com/affiliate"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <RichTextEditor
                            v-model="editForm.notes"
                            placeholder="Personal notes about this item..."
                            :max-height="150"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Item Image</label>
                        <DirectCloudflareUpload
                            v-model="itemImage"
                            label="Upload Image for this Item"
                            upload-type="list_image"
                            :entity-id="editingItem?.id || 1"
                            :max-size-m-b="14"
                            :current-image-url="editForm.item_image_url"
                            @upload-complete="handleItemImageUpload"
                        />
                        <p class="text-xs text-gray-500 mt-1">Add an image to make this list item more visually appealing</p>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="savingItem"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ savingItem ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
        
        <!-- Saved Items Modal -->
        <TransitionRoot :show="showSavedItems" as="template" @after-leave="savedItemsQuery = ''">
            <Dialog as="div" class="relative z-50" @close="showSavedItems = false">
                <TransitionChild
                    as="template"
                    enter="ease-out duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 z-10 overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        <TransitionChild
                            as="template"
                            enter="ease-out duration-300"
                            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to="opacity-100 translate-y-0 sm:scale-100"
                            leave="ease-in duration-200"
                            leave-from="opacity-100 translate-y-0 sm:scale-100"
                            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
                                <div>
                                    <h3 class="text-lg font-medium leading-6 text-gray-900">
                                        Select from Saved Places
                                    </h3>
                                    <div class="mt-4">
                                        <input
                                            v-model="savedItemsQuery"
                                            type="text"
                                            placeholder="Filter saved places..."
                                            class="w-full rounded-md border-gray-300 mb-4"
                                        />
                                        
                                        <div v-if="loadingSavedItems" class="text-center py-4">
                                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                                        </div>
                                        
                                        <div v-else-if="filteredSavedItems.length === 0" class="text-center py-8 text-gray-500">
                                            No saved places found.
                                        </div>
                                        
                                        <div v-else class="max-h-96 overflow-y-auto space-y-2">
                                            <div
                                                v-for="place in filteredSavedItems"
                                                :key="place.id"
                                                class="border rounded-lg p-4 hover:bg-gray-50 cursor-pointer"
                                                @click="addSavedPlace(place)"
                                            >
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <h4 class="font-medium text-gray-900">{{ place.title }}</h4>
                                                        <p class="text-sm text-gray-500">
                                                            {{ place.category }}
                                                            <span v-if="place.location"> · {{ place.location }}</span>
                                                        </p>
                                                        <p v-if="place.description" class="text-sm text-gray-600 mt-1 line-clamp-2">
                                                            {{ place.description }}
                                                        </p>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        class="ml-3 text-blue-600 hover:text-blue-800 text-sm font-medium"
                                                    >
                                                        Add
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-5 sm:mt-6">
                                    <button
                                        type="button"
                                        class="inline-flex w-full justify-center rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-400"
                                        @click="showSavedItems = false"
                                    >
                                        Close
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Modal from '@/components/Modal.vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import DraggableImageGallery from '@/components/DraggableImageGallery.vue'
import DirectCloudflareUpload from '@/components/image-upload/DirectCloudflareUpload.vue'
import CategorySelect from '@/components/ui/CategorySelect.vue'
import TagInput from '@/components/ui/TagInput.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import draggable from 'vuedraggable'
import { debounce } from 'lodash'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline'
// import { useToast } from 'vue-toastification'

// Vue Router
const route = useRoute()
const router = useRouter()
// const toast = useToast()

// Simple toast alternative
const showToast = (message, type = 'success') => {
  console.log(`${type}: ${message}`)
  if (type === 'error') {
    alert(`Error: ${message}`)
  }
}

// Get listId from route params
const listId = ref(route.params.id)

// Update page title
const updateTitle = (title) => {
    document.title = title ? `${title} - Edit List` : 'Edit List'
}

// Data
const list = ref({})
const items = ref([])
const loading = ref(false)
const savingList = ref(false)
const savingItem = ref(false)
const showEditModal = ref(false)
const editingItem = ref(null)
const selectedItemType = ref('directory_entry')
const entrySearch = ref('')
const searchResults = ref([])

// Sharing state
const shares = ref([])
const shareSearch = ref('')
const userSearchResults = ref([])
const selectedUser = ref(null)
const sharing = ref(false)

// Image upload state
const itemImage = ref(null)
const photos = ref([])
const existingPhotos = ref([])

// Drawer state
const settingsOpen = ref(false)
const hasSaved = ref(false)

// Saved items state
const showSavedItems = ref(false)
const savedItems = ref([])
const loadingSavedItems = ref(false)
const savedItemsQuery = ref('')
const userChannels = ref([])

const listForm = reactive({
    name: '',
    description: '',
    visibility: 'public',
    is_draft: false,
    scheduled_for: '',
    featured_image: '',
    featured_image_cloudflare_id: null,
    gallery_images: [],
    category_id: '',
    channel_id: null,
    tags: []
})

const newItem = reactive({
    title: '',
    content: '',
    data: {},
    affiliate_url: '',
    notes: ''
})

const editForm = reactive({
    title: '',
    content: '',
    data: {},
    affiliate_url: '',
    notes: '',
    item_image: null,
    item_image_url: null
})

const shareForm = reactive({
    user_id: null,
    permission: 'view',
    expires_at: ''
})

const itemTypes = [
    { value: 'directory_entry', label: 'Directory Entry' },
    { value: 'text', label: 'Text' },
    { value: 'location', label: 'Location' },
    { value: 'event', label: 'Event' }
]

// Computed
const backLink = computed(() => {
    // Check if it's a channel list
    if (list.value.owner_type === 'App\\Models\\Channel' && list.value.channel) {
        return `/@${list.value.channel.slug}`
    } else if (list.value.channel_id && list.value.channel) {
        // Legacy channel list
        return `/@${list.value.channel.slug}`
    }
    // Default to user lists
    return '/mylists'
})

const backLinkText = computed(() => {
    // Check if it's a channel list
    if (list.value.owner_type === 'App\\Models\\Channel' && list.value.channel) {
        return 'Back to Channel'
    } else if (list.value.channel_id && list.value.channel) {
        // Legacy channel list
        return 'Back to Channel'
    }
    // Default to user lists
    return 'Back to My Lists'
})

const filteredSavedItems = computed(() => {
    if (!savedItemsQuery.value) return savedItems.value
    const query = savedItemsQuery.value.toLowerCase()
    return savedItems.value.filter(item => 
        item.title.toLowerCase().includes(query) ||
        (item.description && item.description.toLowerCase().includes(query)) ||
        (item.category && item.category.toLowerCase().includes(query))
    )
})

// Methods
const fetchList = async () => {
    loading.value = true
    try {
        const response = await axios.get(`/api/lists/${listId.value}`)
        list.value = response.data
        items.value = response.data.items || []
        
        // Update page title
        updateTitle(response.data.name)
        
        // Update form
        Object.assign(listForm, {
            name: response.data.name,
            description: response.data.description || '',
            visibility: response.data.visibility || 'public',
            is_draft: response.data.is_draft || false,
            scheduled_for: response.data.scheduled_for ? new Date(response.data.scheduled_for).toISOString().slice(0, 16) : '',
            featured_image: response.data.featured_image || '',
            featured_image_cloudflare_id: response.data.featured_image_cloudflare_id || null,
            gallery_images: response.data.gallery_images || [],
            category_id: response.data.category_id || '',
            channel_id: response.data.channel_id || null,
            tags: response.data.tags || []
        })
        
        // Populate existing photos for display
        if (response.data.gallery_images && response.data.gallery_images.length > 0) {
            existingPhotos.value = response.data.gallery_images.map((image, index) => ({
                id: image.id || `existing-${index}`,
                url: image.url,
                filename: image.filename || `Photo ${index + 1}`
            }))
        }
        
        // Fetch shares if private list
        if (response.data.visibility === 'private') {
            fetchShares()
        }
    } catch (error) {
        console.error('Error fetching list:', error)
        if (error.response?.status === 404) {
            alert('List not found')
            router.push('/lists/my')
        } else if (error.response?.status === 401) {
            alert('Please log in to edit this list')
            router.push('/login')
        } else {
            alert('Error loading list')
        }
    } finally {
        loading.value = false
    }
}

const updateList = async () => {
    // Validate required fields
    if (!listForm.category_id) {
        alert('Please select a category for your list.')
        return
    }

    savingList.value = true
    try {
        await axios.put(`/api/lists/${listId.value}`, listForm)
        hasSaved.value = true
        alert('List settings saved successfully')
    } catch (error) {
        alert('Error saving list: ' + error.response?.data?.message)
    } finally {
        savingList.value = false
    }
}

const previewList = () => {
    // Open the list in a new tab
    let listUrl = ''
    
    // Check if it's a channel list or user list based on owner_type
    if (list.value.owner_type === 'App\\Models\\Channel' && list.value.channel) {
        // Channel list
        listUrl = `/@${list.value.channel.slug}/${list.value.slug}`
    } else if (list.value.channel_id && list.value.channel) {
        // Legacy channel list
        listUrl = `/@${list.value.channel.slug}/${list.value.slug}`
    } else {
        // User list
        const user = list.value.user
        const baseUrl = user?.custom_url ? `/up/@${user.custom_url}` : `/up/@${user?.username || user?.id}`
        listUrl = `${baseUrl}/${list.value.slug}`
    }
    
    window.open(listUrl, '_blank')
}

const searchEntries = async () => {
    if (!entrySearch.value) {
        searchResults.value = []
        return
    }
    
    try {
        const response = await axios.get('/api/directory-entries/search', {
            params: { q: entrySearch.value }
        })
        searchResults.value = response.data
    } catch (error) {
        console.error('Error searching entries:', error)
    }
}

const debouncedSearchEntries = debounce(searchEntries, 300)

const addDirectoryEntry = async (entry) => {
    try {
        const response = await axios.post(`/api/lists/${listId.value}/items`, {
            type: 'directory_entry',
            directory_entry_id: entry.id
        })
        items.value.push(response.data.item)
        entrySearch.value = ''
        searchResults.value = []
    } catch (error) {
        alert('Error adding entry: ' + error.response?.data?.message)
    }
}

const fetchSavedItems = async () => {
    loadingSavedItems.value = true
    try {
        const response = await axios.get('/api/saved-items/for-list-creation')
        savedItems.value = response.data.places || []
    } catch (error) {
        console.error('Error fetching saved items:', error)
        savedItems.value = []
    } finally {
        loadingSavedItems.value = false
    }
}

const addSavedPlace = async (place) => {
    try {
        const response = await axios.post(`/api/lists/${listId.value}/items`, {
            type: 'directory_entry',
            directory_entry_id: place.id
        })
        items.value.push(response.data.item)
        showSavedItems.value = false
        showToast('Place added to list')
    } catch (error) {
        showToast('Error adding place: ' + (error.response?.data?.message || 'Unknown error'), 'error')
    }
}

const addTextItem = async () => {
    if (!newItem.title) {
        alert('Please enter a title')
        return
    }
    
    try {
        const response = await axios.post(`/api/lists/${listId.value}/items`, {
            type: 'text',
            title: newItem.title,
            content: newItem.content
        })
        items.value.push(response.data.item)
        newItem.title = ''
        newItem.content = ''
    } catch (error) {
        alert('Error adding text: ' + error.response?.data?.message)
    }
}

const addLocationItem = async () => {
    if (!newItem.title || !newItem.data.latitude || !newItem.data.longitude) {
        alert('Please fill in all location fields')
        return
    }
    
    try {
        const response = await axios.post(`/api/lists/${listId.value}/items`, {
            type: 'location',
            title: newItem.title,
            data: {
                latitude: newItem.data.latitude,
                longitude: newItem.data.longitude,
                address: newItem.data.address,
                name: newItem.title
            }
        })
        items.value.push(response.data.item)
        newItem.title = ''
        newItem.data = {}
    } catch (error) {
        alert('Error adding location: ' + error.response?.data?.message)
    }
}

const addEventItem = async () => {
    if (!newItem.title || !newItem.data.start_date) {
        alert('Please enter event name and start date')
        return
    }
    
    try {
        const response = await axios.post(`/api/lists/${listId.value}/items`, {
            type: 'event',
            title: newItem.title,
            content: newItem.content,
            data: {
                start_date: newItem.data.start_date,
                location: newItem.data.location
            }
        })
        items.value.push(response.data.item)
        newItem.title = ''
        newItem.content = ''
        newItem.data = {}
    } catch (error) {
        alert('Error adding event: ' + error.response?.data?.message)
    }
}

const editItem = (item) => {
    editingItem.value = item
    Object.assign(editForm, {
        title: item.title || '',
        content: item.content || '',
        data: item.data || {},
        affiliate_url: item.affiliate_url || '',
        notes: item.notes || '',
        item_image: item.item_image_cloudflare_id || null,
        item_image_url: item.item_image_url || null
    })
    showEditModal.value = true
}

const updateItem = async () => {
    savingItem.value = true
    try {
        await axios.put(`/api/lists/${listId.value}/items/${editingItem.value.id}`, editForm)
        
        // Update local item
        const index = items.value.findIndex(i => i.id === editingItem.value.id)
        if (index !== -1) {
            Object.assign(items.value[index], editForm)
        }
        
        closeEditModal()
    } catch (error) {
        alert('Error updating item: ' + error.response?.data?.message)
    } finally {
        savingItem.value = false
    }
}

const removeItem = async (item) => {
    if (!confirm('Are you sure you want to remove this item?')) return
    
    try {
        await axios.delete(`/api/lists/${listId.value}/items/${item.id}`)
        items.value = items.value.filter(i => i.id !== item.id)
    } catch (error) {
        alert('Error removing item: ' + error.response?.data?.message)
    }
}

const handleReorder = async () => {
    const reorderData = items.value.map((item, index) => ({
        id: item.id,
        order_index: index
    }))
    
    console.log('Reordering items:', reorderData)
    
    try {
        const response = await axios.put(`/api/lists/${listId.value}/items/reorder`, { items: reorderData })
        console.log('Reorder successful:', response.data)
        
        // Update local order_index values to match what was sent
        items.value.forEach((item, index) => {
            item.order_index = index
        })
    } catch (error) {
        console.error('Error reordering items:', error)
        alert('Error saving order: ' + (error.response?.data?.message || error.message))
        // Revert on error
        fetchList()
    }
}

const closeEditModal = () => {
    showEditModal.value = false
    editingItem.value = null
    itemImage.value = null
    Object.keys(editForm).forEach(key => {
        editForm[key] = key === 'data' ? {} : ''
    })
}

// Handle item image upload
const handleItemImageUpload = (image) => {
    itemImage.value = image
    editForm.item_image = image.cloudflare_id
    editForm.item_image_url = image.urls.original
}

// Handle photo upload
const handlePhotoUpload = (uploadResult) => {
    photos.value.push(uploadResult)
    updateGalleryImages()
    console.log('Photo uploaded:', uploadResult)
}

const handleGalleryRemove = (index, removedImage) => {
    updateGalleryImages()
}

const updateImageOrder = () => {
    updateGalleryImages()
}

const handleExistingGalleryRemove = (index, removedImage) => {
    updateCombinedGalleryImages()
}

const updateExistingImageOrder = () => {
    updateCombinedGalleryImages()
}

const updateGalleryImages = () => {
    // Update gallery_images array with all photos (existing + new)
    const combinedImages = [...existingPhotos.value, ...photos.value]
    
    listForm.gallery_images = combinedImages.map(photo => ({
        id: photo.id,
        url: photo.url,
        filename: photo.filename
    }))
    
    // Set first image as featured_image for backward compatibility
    if (combinedImages.length > 0) {
        listForm.featured_image = combinedImages[0].url
        listForm.featured_image_cloudflare_id = combinedImages[0].id
    } else {
        listForm.featured_image = null
        listForm.featured_image_cloudflare_id = null
    }
    
    console.log('Gallery images updated:', listForm.gallery_images)
}

const updateCombinedGalleryImages = () => {
    updateGalleryImages()
}

const handleUploadError = (error) => {
    console.error('Upload error:', error)
    alert('Error uploading image: ' + (error.message || 'Unknown error'))
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleString()
}

// Sharing methods
const fetchShares = async () => {
    if (listForm.visibility !== 'private') return
    
    try {
        const response = await axios.get(`/api/lists/${listId.value}/shares`)
        shares.value = response.data
    } catch (error) {
        console.error('Error fetching shares:', error)
    }
}

const searchUsers = async () => {
    if (!shareSearch.value || shareSearch.value.length < 2) {
        userSearchResults.value = []
        return
    }
    
    try {
        const response = await axios.get('/api/users/search', {
            params: { q: shareSearch.value }
        })
        userSearchResults.value = response.data
    } catch (error) {
        console.error('Error searching users:', error)
    }
}

const debouncedSearchUsers = debounce(searchUsers, 300)

const selectUserToShare = (user) => {
    selectedUser.value = user
    shareForm.user_id = user.id
    shareSearch.value = ''
    userSearchResults.value = []
}

const clearSelectedUser = () => {
    selectedUser.value = null
    shareForm.user_id = null
    shareForm.permission = 'view'
    shareForm.expires_at = ''
}

const shareList = async () => {
    if (!selectedUser.value) return
    
    sharing.value = true
    try {
        const response = await axios.post(`/api/lists/${listId.value}/shares`, shareForm)
        shares.value.push(response.data.share)
        clearSelectedUser()
        alert('List shared successfully!')
    } catch (error) {
        alert('Error sharing list: ' + (error.response?.data?.error || error.message))
    } finally {
        sharing.value = false
    }
}

const editShare = (share) => {
    // For now, just allow removing and re-adding
    // Could implement inline editing later
    if (confirm(`Edit sharing permissions for ${share.user.name}?\n\nCurrent: ${share.permission} access`)) {
        removeShare(share)
    }
}

const removeShare = async (share) => {
    if (!confirm(`Remove ${share.user.name}'s access to this list?`)) return
    
    try {
        await axios.delete(`/api/lists/${listId.value}/shares/${share.id}`)
        shares.value = shares.value.filter(s => s.id !== share.id)
    } catch (error) {
        alert('Error removing share: ' + (error.response?.data?.message || error.message))
    }
}

// Watch for visibility changes
watch(() => listForm.visibility, (newValue, oldValue) => {
    if (newValue === 'private' && oldValue !== 'private') {
        fetchShares()
    } else if (newValue !== 'private') {
        shares.value = []
    }
})

// Watch for saved items modal
watch(showSavedItems, (newValue) => {
    if (newValue && savedItems.value.length === 0) {
        fetchSavedItems()
    }
})

const fetchChannels = async () => {
    try {
        const response = await axios.get('/api/my-channels')
        userChannels.value = response.data
    } catch (error) {
        console.error('Error fetching channels:', error)
    }
}

// Lifecycle
onMounted(() => {
    if (!listId.value) {
        alert('No list ID provided')
        router.push('/lists/my')
        return
    }
    fetchList()
    fetchChannels()
})
</script>