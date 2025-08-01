<template>
  <div class="flex-1 bg-gray-50 overflow-hidden flex flex-col">
    <div class="p-6 border-b border-gray-200 bg-white">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-medium text-gray-900">Chain Sequence</h2>
        <div class="flex items-center space-x-4">
          <span class="text-sm text-gray-500">
            {{ totalListsCount }} {{ totalListsCount === 1 ? 'list' : 'lists' }} in chain
          </span>
          <button
            @click="$emit('add-section')"
            class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            <PlusIcon class="h-4 w-4 mr-1" />
            Add Section
          </button>
        </div>
      </div>
    </div>

    <!-- Chain Builder Area -->
    <div class="flex-1 overflow-y-auto p-6">
      <div
        v-if="sections.length === 0"
        @drop="handleEmptyDrop"
        @dragover.prevent
        @dragenter.prevent
        class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center"
      >
        <FolderPlusIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-4 text-sm font-medium text-gray-900">No sections yet</h3>
        <p class="mt-2 text-sm text-gray-500">
          Add a section or drag lists here to get started
        </p>
      </div>

      <!-- Sections -->
      <div v-else class="space-y-4">
        <SectionManager
          v-for="(section, sectionIndex) in sections"
          :key="section.id"
          :section="section"
          :index="sectionIndex"
          :total-sections="sections.length"
          @toggle="toggleSection(sectionIndex)"
          @update-name="updateSectionName(sectionIndex, $event)"
          @update-lists="updateSectionLists(sectionIndex, $event)"
          @move="moveSection(sectionIndex, $event)"
          @remove="$emit('remove-section', sectionIndex)"
          @remove-list="removeListFromSection(sectionIndex, $event)"
          @drop="handleSectionDrop($event, sectionIndex)"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SectionManager from './SectionManager.vue'
import { PlusIcon, FolderPlusIcon } from '@heroicons/vue/24/outline'

/**
 * ChainBuilder Component
 * 
 * The main chain building interface where sections are managed and lists are organized.
 * Provides drag-and-drop functionality and section management.
 * 
 * Props:
 * - sections: Array of section objects
 * - draggedList: The list currently being dragged (if any)
 * 
 * Emits:
 * - add-section: Add a new section
 * - update-sections: Update the entire sections array
 * - remove-section: Remove a section by index
 * - drop-to-empty: Handle drop when no sections exist
 * - drop-to-section: Handle drop to a specific section
 */

const props = defineProps({
  sections: {
    type: Array,
    default: () => []
  },
  draggedList: {
    type: Object,
    default: null
  }
})

const emit = defineEmits([
  'add-section',
  'update-sections',
  'remove-section',
  'drop-to-empty',
  'drop-to-section'
])

// Computed total lists count
const totalListsCount = computed(() => {
  return props.sections.reduce((total, section) => total + section.lists.length, 0)
})

// Toggle section expanded state
const toggleSection = (index) => {
  const updatedSections = [...props.sections]
  updatedSections[index] = {
    ...updatedSections[index],
    expanded: !updatedSections[index].expanded
  }
  emit('update-sections', updatedSections)
}

// Update section name
const updateSectionName = (index, name) => {
  const updatedSections = [...props.sections]
  updatedSections[index] = {
    ...updatedSections[index],
    name
  }
  emit('update-sections', updatedSections)
}

// Update section lists
const updateSectionLists = (index, lists) => {
  const updatedSections = [...props.sections]
  updatedSections[index] = {
    ...updatedSections[index],
    lists
  }
  emit('update-sections', updatedSections)
}

// Move section up or down
const moveSection = (index, direction) => {
  const updatedSections = [...props.sections]
  
  if (direction === 'up' && index > 0) {
    [updatedSections[index - 1], updatedSections[index]] = 
    [updatedSections[index], updatedSections[index - 1]]
  } else if (direction === 'down' && index < updatedSections.length - 1) {
    [updatedSections[index], updatedSections[index + 1]] = 
    [updatedSections[index + 1], updatedSections[index]]
  }
  
  emit('update-sections', updatedSections)
}

// Remove a list from a section
const removeListFromSection = (sectionIndex, listIndex) => {
  const updatedSections = [...props.sections]
  updatedSections[sectionIndex].lists.splice(listIndex, 1)
  emit('update-sections', updatedSections)
}

// Handle drop events
const handleEmptyDrop = (event) => {
  emit('drop-to-empty', event)
}

const handleSectionDrop = (event, sectionIndex) => {
  emit('drop-to-section', event, sectionIndex)
}
</script>