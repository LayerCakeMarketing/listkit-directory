<template>
  <TransitionRoot as="template" :show="true">
    <Dialog class="relative z-50" @close="$emit('close')">
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
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
              <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                    <DialogTitle as="h3" class="text-lg font-semibold leading-6 text-gray-900">
                      Edit Item
                    </DialogTitle>
                    <div class="mt-4 space-y-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input
                          v-model="localForm.title"
                          type="text"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                        <RichTextEditor
                          v-model="localForm.content"
                          placeholder="Enter item content..."
                          :max-height="200"
                        />
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Affiliate URL</label>
                        <input
                          v-model="localForm.affiliate_url"
                          type="url"
                          placeholder="https://..."
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (private)</label>
                        <textarea
                          v-model="localForm.notes"
                          rows="2"
                          placeholder="Private notes about this item..."
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        ></textarea>
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Item Image</label>
                        <div v-if="localForm.item_image_url" class="mt-2">
                          <img 
                            :src="localForm.item_image_url" 
                            alt="Item image" 
                            class="h-20 w-20 rounded-lg object-cover"
                          />
                          <button
                            @click="removeImage"
                            type="button"
                            class="mt-1 text-sm text-red-600 hover:text-red-800"
                          >
                            Remove image
                          </button>
                        </div>
                        <CloudflareDragDropUploader
                          v-else
                          :max-files="1"
                          :max-file-size="5242880"
                          context="item"
                          @upload-success="handleImageUpload"
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button
                  type="button"
                  @click="handleSave"
                  :disabled="saving || !localForm.title"
                  class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ saving ? 'Saving...' : 'Save' }}
                </button>
                <button
                  type="button"
                  @click="$emit('close')"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                >
                  Cancel
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import CloudflareDragDropUploader from '@/components/CloudflareDragDropUploader.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'

const props = defineProps({
  item: Object,
  form: Object,
  saving: Boolean
})

const emit = defineEmits(['close', 'save', 'image-upload'])

const localForm = reactive({
  title: '',
  content: '',
  affiliate_url: '',
  notes: '',
  item_image: null,
  item_image_url: null
})

// Copy form data on mount and when form prop changes
watch(() => props.form, (newForm) => {
  if (newForm) {
    Object.assign(localForm, newForm)
  }
}, { immediate: true })

const handleSave = () => {
  // Update the parent's form before saving
  Object.assign(props.form, localForm)
  emit('save')
}

const handleImageUpload = (image) => {
  // Handle the response from CloudflareDragDropUploader
  if (image.cloudflare_id) {
    localForm.item_image = image.cloudflare_id
    // Check for different possible URL formats
    if (image.urls && image.urls.original) {
      localForm.item_image_url = image.urls.original
    } else if (image.urls && image.urls.public) {
      localForm.item_image_url = image.urls.public
    } else if (typeof image.urls === 'string') {
      localForm.item_image_url = image.urls
    } else {
      // Fallback to constructing URL from cloudflare_id
      localForm.item_image_url = `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${image.cloudflare_id}/public`
    }
  }
  emit('image-upload', image)
}

const removeImage = () => {
  localForm.item_image = null
  localForm.item_image_url = null
}
</script>