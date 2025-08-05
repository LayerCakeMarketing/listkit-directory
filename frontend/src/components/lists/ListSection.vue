<template>
  <div class="bg-gray-50 rounded-lg p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center flex-1">
        <div class="flex-shrink-0 mr-3 cursor-grab drag-handle">
          <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
          </svg>
        </div>
        <input
          v-if="editing"
          v-model="editingHeading"
          @blur="saveHeading"
          @keyup.enter="saveHeading"
          @keyup.escape="cancelEdit"
          class="flex-1 text-lg font-semibold bg-white border-gray-300 rounded"
          autofocus
        />
        <h3 v-else class="text-lg font-semibold text-gray-900 flex-1" @click="startEdit">
          {{ section.heading }}
        </h3>
      </div>
      <div class="flex items-center space-x-2">
        <button
          @click="startEdit"
          v-if="!editing"
          class="text-blue-600 hover:text-blue-800 text-sm"
        >
          Edit
        </button>
        <button
          @click="$emit('remove')"
          class="text-red-600 hover:text-red-800 text-sm"
        >
          Remove
        </button>
      </div>
    </div>
    
    <draggable
      v-model="localItems"
      @end="handleReorder"
      item-key="id"
      class="space-y-2"
      group="items"
      handle=".item-handle"
    >
      <template #item="{ element }">
        <div class="bg-white p-3 rounded border hover:shadow-sm cursor-move">
          <div class="flex items-start space-x-3">
            <div class="flex-shrink-0 mt-1 item-handle cursor-grab">
              <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
              </svg>
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between">
                <h4 class="font-medium text-gray-900 text-sm">
                  {{ element.display_title || element.title }}
                </h4>
                <div class="flex items-center space-x-2">
                  <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                    {{ element.type }}
                  </span>
                  <button
                    type="button"
                    @click="$emit('edit-item', element)"
                    class="text-blue-600 hover:text-blue-800 text-sm"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    @click="$emit('remove-item', element)"
                    class="text-red-600 hover:text-red-800 text-sm"
                  >
                    Remove
                  </button>
                </div>
              </div>
              <p v-if="element.display_content || element.content" class="text-sm text-gray-600 mt-1">
                {{ truncate(element.display_content || element.content, 100) }}
              </p>
            </div>
          </div>
        </div>
      </template>
    </draggable>
    
    <div v-if="localItems.length === 0" class="text-center py-4 text-gray-500 text-sm">
      Drag items here or add new ones
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import draggable from 'vuedraggable'

const props = defineProps({
  section: Object,
  items: Array
})

const emit = defineEmits(['update-heading', 'reorder', 'remove', 'edit-item', 'remove-item'])

const editing = ref(false)
const editingHeading = ref('')

const localItems = computed({
  get: () => props.items,
  set: (value) => emit('reorder', value)
})

const startEdit = () => {
  editing.value = true
  editingHeading.value = props.section.heading
}

const saveHeading = () => {
  if (editingHeading.value.trim()) {
    emit('update-heading', editingHeading.value.trim())
  }
  editing.value = false
}

const cancelEdit = () => {
  editing.value = false
  editingHeading.value = ''
}

const handleReorder = () => {
  emit('reorder', localItems.value)
}

const truncate = (text, length) => {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}
</script>