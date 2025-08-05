<template>
  <TransitionRoot as="template" :show="isOpen">
    <Dialog as="div" class="relative z-50" @close="close">
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
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                  <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                    <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                      {{ isEditMode ? 'Edit Collection' : 'Create New Collection' }}
                    </DialogTitle>
                    
                    <form @submit.prevent="save" class="mt-4">
                      <!-- Name -->
                      <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                          Collection Name <span class="text-red-500">*</span>
                        </label>
                        <input
                          v-model="form.name"
                          type="text"
                          required
                          maxlength="255"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="e.g., Weekend Trips"
                        />
                      </div>

                      <!-- Description -->
                      <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                          Description
                        </label>
                        <textarea
                          v-model="form.description"
                          rows="3"
                          maxlength="500"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="Optional description for your collection"
                        ></textarea>
                        <p class="text-xs text-gray-500 mt-1">{{ form.description?.length || 0 }}/500</p>
                      </div>

                      <!-- Color -->
                      <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                          Color
                        </label>
                        <div class="grid grid-cols-5 gap-2">
                          <button
                            v-for="color in availableColors"
                            :key="color.value"
                            type="button"
                            @click="form.color = color.value"
                            :class="[
                              'h-10 rounded-md transition-all',
                              color.bgClass,
                              form.color === color.value ? 'ring-2 ring-offset-2 ring-gray-400' : 'hover:scale-110'
                            ]"
                            :title="color.label"
                          >
                            <span class="sr-only">{{ color.label }}</span>
                          </button>
                        </div>
                      </div>

                      <!-- Icon -->
                      <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                          Icon
                        </label>
                        <div class="grid grid-cols-5 gap-2">
                          <button
                            v-for="icon in availableIcons"
                            :key="icon.value"
                            type="button"
                            @click="form.icon = icon.value"
                            :class="[
                              'h-10 flex items-center justify-center rounded-md border transition-all',
                              form.icon === icon.value 
                                ? 'border-indigo-500 bg-indigo-50 text-indigo-600' 
                                : 'border-gray-300 hover:bg-gray-50'
                            ]"
                            :title="icon.label"
                          >
                            <component :is="icon.component" class="h-5 w-5" />
                          </button>
                        </div>
                      </div>

                      <!-- Preview -->
                      <div v-if="form.name" class="mb-4 p-3 rounded-lg border border-gray-200 bg-gray-50">
                        <p class="text-xs text-gray-500 mb-2">Preview</p>
                        <div class="flex items-center space-x-2">
                          <div :class="`h-8 w-8 rounded-lg ${getColorClass(form.color)} flex items-center justify-center`">
                            <component 
                              :is="getIconComponent(form.icon)" 
                              class="h-4 w-4 text-white"
                            />
                          </div>
                          <span class="font-medium">{{ form.name }}</span>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              
              <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                <button
                  type="button"
                  @click="save"
                  :disabled="!form.name || isLoading"
                  class="inline-flex w-full justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="!isLoading">{{ isEditMode ? 'Update' : 'Create' }}</span>
                  <span v-else class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                  </span>
                </button>
                <button
                  type="button"
                  @click="close"
                  :disabled="isLoading"
                  class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
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
import { ref, reactive, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { 
  FolderIcon,
  StarIcon,
  HeartIcon,
  BookmarkIcon,
  FlagIcon,
  TagIcon,
  MapIcon,
  CameraIcon,
  ShoppingBagIcon,
  BriefcaseIcon
} from '@heroicons/vue/24/solid'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  collection: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'saved'])

const { showSuccess, showError } = useNotification()
const isLoading = ref(false)

const isEditMode = computed(() => !!props.collection)

const defaultForm = {
  name: '',
  description: '',
  color: 'gray',
  icon: 'folder'
}

const form = reactive({ ...defaultForm })

// Reset form when modal opens
const resetForm = () => {
  if (props.collection) {
    form.name = props.collection.name
    form.description = props.collection.description || ''
    form.color = props.collection.color
    form.icon = props.collection.icon || 'folder'
  } else {
    Object.assign(form, defaultForm)
  }
}

// Available colors
const availableColors = [
  { value: 'gray', label: 'Gray', bgClass: 'bg-gray-500' },
  { value: 'red', label: 'Red', bgClass: 'bg-red-500' },
  { value: 'orange', label: 'Orange', bgClass: 'bg-orange-500' },
  { value: 'yellow', label: 'Yellow', bgClass: 'bg-yellow-500' },
  { value: 'green', label: 'Green', bgClass: 'bg-green-500' },
  { value: 'teal', label: 'Teal', bgClass: 'bg-teal-500' },
  { value: 'blue', label: 'Blue', bgClass: 'bg-blue-500' },
  { value: 'indigo', label: 'Indigo', bgClass: 'bg-indigo-500' },
  { value: 'purple', label: 'Purple', bgClass: 'bg-purple-500' },
  { value: 'pink', label: 'Pink', bgClass: 'bg-pink-500' }
]

// Available icons
const availableIcons = [
  { value: 'folder', label: 'Folder', component: FolderIcon },
  { value: 'star', label: 'Star', component: StarIcon },
  { value: 'heart', label: 'Heart', component: HeartIcon },
  { value: 'bookmark', label: 'Bookmark', component: BookmarkIcon },
  { value: 'flag', label: 'Flag', component: FlagIcon },
  { value: 'tag', label: 'Tag', component: TagIcon },
  { value: 'map', label: 'Map', component: MapIcon },
  { value: 'camera', label: 'Camera', component: CameraIcon },
  { value: 'shopping-bag', label: 'Shopping Bag', component: ShoppingBagIcon },
  { value: 'briefcase', label: 'Briefcase', component: BriefcaseIcon }
]

const getColorClass = (color) => {
  const colorMap = {
    gray: 'bg-gray-500',
    red: 'bg-red-500',
    orange: 'bg-orange-500',
    yellow: 'bg-yellow-500',
    green: 'bg-green-500',
    teal: 'bg-teal-500',
    blue: 'bg-blue-500',
    indigo: 'bg-indigo-500',
    purple: 'bg-purple-500',
    pink: 'bg-pink-500'
  }
  return colorMap[color] || 'bg-gray-500'
}

const getIconComponent = (icon) => {
  const iconMap = {
    folder: FolderIcon,
    star: StarIcon,
    heart: HeartIcon,
    bookmark: BookmarkIcon,
    flag: FlagIcon,
    tag: TagIcon,
    map: MapIcon,
    camera: CameraIcon,
    'shopping-bag': ShoppingBagIcon,
    briefcase: BriefcaseIcon
  }
  return iconMap[icon] || FolderIcon
}

const close = () => {
  if (!isLoading.value) {
    emit('close')
    resetForm()
  }
}

const save = async () => {
  if (!form.name || isLoading.value) return
  
  isLoading.value = true
  
  try {
    let response
    if (isEditMode.value) {
      response = await axios.put(`/api/saved-collections/${props.collection.id}`, form)
      showSuccess('Collection updated successfully')
    } else {
      response = await axios.post('/api/saved-collections', form)
      showSuccess('Collection created successfully')
    }
    
    emit('saved', response.data.collection)
    close()
  } catch (error) {
    showError(error.response?.data?.message || 'Failed to save collection')
  } finally {
    isLoading.value = false
  }
}

// Reset form when collection prop changes
defineExpose({
  resetForm
})
</script>