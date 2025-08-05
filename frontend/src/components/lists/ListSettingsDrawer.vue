<template>
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-40" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="transition-opacity ease-linear duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="transition-opacity ease-linear duration-300"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-600 bg-opacity-75" />
      </TransitionChild>

      <div class="fixed inset-0 z-40 flex">
        <TransitionChild
          as="template"
          enter="transition ease-in-out duration-300 transform"
          enter-from="-translate-x-full"
          enter-to="translate-x-0"
          leave="transition ease-in-out duration-300 transform"
          leave-from="translate-x-0"
          leave-to="-translate-x-full"
        >
          <DialogPanel class="relative flex w-full max-w-md flex-col overflow-y-auto bg-white pb-12 shadow-xl" style="margin-top: 64px;">
            <div class="flex items-center justify-between px-4 py-4 border-b">
              <h2 class="text-lg font-medium text-gray-900">List Settings</h2>
              <button
                type="button"
                class="relative -m-2 inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:text-gray-500"
                @click="$emit('close')"
              >
                <span class="absolute -inset-0.5" />
                <span class="sr-only">Close panel</span>
                <XMarkIcon class="h-6 w-6" aria-hidden="true" />
              </button>
            </div>

            <form @submit.prevent="$emit('save')" class="flex-1 px-4 py-6 space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input
                  v-model="form.name"
                  type="text"
                  class="mt-1 block w-full rounded-md border-gray-300"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <RichTextEditor
                  v-model="form.description"
                  placeholder="Enter list description..."
                  :max-height="200"
                />
              </div>

              <div>
                <CategorySelect
                  v-model="form.category_id"
                  label="Category *"
                  placeholder="Select a category..."
                  help-text="Choose the category that best describes your list"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Channel</label>
                <select
                  v-model="form.channel_id"
                  class="mt-1 block w-full rounded-md border-gray-300"
                >
                  <option :value="null">No channel (personal list)</option>
                  <option
                    v-for="channel in channels"
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
                  v-model="form.tags"
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
                      v-model="form.visibility"
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
                      v-model="form.visibility"
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
                      v-model="form.visibility"
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
                  @upload-success="$emit('photo-upload', $event)"
                  @upload-error="$emit('upload-error', $event)"
                />
                <!-- Upload Results with Drag & Drop Reordering -->
                <DraggableImageGallery 
                  :images="photos"
                  title="Uploaded Photos"
                  :show-primary="true"
                  @remove="$emit('gallery-remove', $event)"
                  @reorder="$emit('image-reorder')"
                />
                <!-- Existing Gallery Images -->
                <DraggableImageGallery 
                  v-if="existingPhotos.length > 0"
                  :images="existingPhotos"
                  title="Current Photos"
                  :show-primary="true"
                  @remove="$emit('existing-gallery-remove', $event)"
                  @reorder="$emit('existing-image-reorder')"
                />
                <p class="text-xs text-gray-500 mt-1">Add photos for your list. The first photo will be used as the featured image. Drag to reorder them.</p>
              </div>

              <!-- Publishing Options -->
              <div class="border-t pt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Publishing Options</label>
                <div class="space-y-3">
                  <label class="flex items-center">
                    <input
                      v-model="form.is_draft"
                      type="checkbox"
                      class="rounded border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Save as draft</span>
                  </label>
                  <div v-if="!form.is_draft">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule for later</label>
                    <input
                      v-model="form.scheduled_for"
                      type="datetime-local"
                      class="w-full rounded-md border-gray-300"
                      :min="new Date().toISOString().slice(0, 16)"
                    />
                    <p class="text-xs text-gray-500 mt-1">Leave empty to publish immediately</p>
                  </div>
                </div>
              </div>

              <!-- List Sharing (Private Lists Only) -->
              <ListSharing
                v-if="form.visibility === 'private'"
                :list-id="listId"
                :shares="shares"
                @share-added="$emit('share-added', $event)"
                @share-removed="$emit('share-removed', $event)"
              />
            </form>

            <div class="px-4 py-3 border-t">
              <button
                type="button"
                @click="$emit('save')"
                :disabled="saving"
                class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
              >
                {{ saving ? 'Saving...' : 'Save Settings' }}
              </button>
            </div>
          </DialogPanel>
        </TransitionChild>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import DraggableImageGallery from '@/components/DraggableImageGallery.vue'
import CategorySelect from '@/components/ui/CategorySelect.vue'
import TagInput from '@/components/ui/TagInput.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import ListSharing from './ListSharing.vue'

defineProps({
  open: Boolean,
  form: Object,
  channels: Array,
  photos: Array,
  existingPhotos: Array,
  listId: [String, Number],
  shares: Array,
  saving: Boolean
})

defineEmits([
  'close',
  'save',
  'photo-upload',
  'upload-error',
  'gallery-remove',
  'image-reorder',
  'existing-gallery-remove',
  'existing-image-reorder',
  'share-added',
  'share-removed'
])
</script>