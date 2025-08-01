<template>
  <div class="bg-white rounded-lg shadow">
    <!-- Section Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200">
      <div class="flex items-center space-x-3">
        <button
          @click="$emit('toggle')"
          class="text-gray-500 hover:text-gray-700"
        >
          <ChevronDownIcon
            v-if="section.expanded"
            class="h-5 w-5"
          />
          <ChevronRightIcon
            v-else
            class="h-5 w-5"
          />
        </button>
        <input
          :value="section.name"
          @input="$emit('update-name', $event.target.value)"
          type="text"
          :placeholder="`Section ${index + 1}`"
          class="text-sm font-medium border-0 focus:ring-0 p-0"
        />
        <span class="text-sm text-gray-500">
          ({{ section.lists.length }} {{ section.lists.length === 1 ? 'list' : 'lists' }})
        </span>
      </div>
      <div class="flex items-center space-x-2">
        <button
          v-if="canMoveUp"
          @click="$emit('move', 'up')"
          class="p-1 text-gray-400 hover:text-gray-600"
          title="Move up"
        >
          <ArrowUpIcon class="h-4 w-4" />
        </button>
        <button
          v-if="canMoveDown"
          @click="$emit('move', 'down')"
          class="p-1 text-gray-400 hover:text-gray-600"
          title="Move down"
        >
          <ArrowDownIcon class="h-4 w-4" />
        </button>
        <button
          @click="$emit('remove')"
          class="p-1 text-red-400 hover:text-red-600"
          title="Remove section"
        >
          <XMarkIcon class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Section Content -->
    <div
      v-show="section.expanded"
      class="p-4"
    >
      <div
        @drop="handleDrop"
        @dragover.prevent
        @dragenter.prevent
        class="min-h-[100px]"
        :class="{
          'border-2 border-dashed border-gray-300 rounded-lg p-4': section.lists.length === 0
        }"
      >
        <p
          v-if="section.lists.length === 0"
          class="text-center text-sm text-gray-500"
        >
          Drag lists here
        </p>
        <!-- Lists in Section -->
        <draggable
          v-else
          :modelValue="section.lists"
          @update:modelValue="$emit('update-lists', $event)"
          :group="{ name: 'lists', pull: true, put: true }"
          item-key="id"
          handle=".drag-handle"
          class="space-y-2"
        >
          <template #item="{ element: list, index: listIndex }">
            <div class="flex items-center space-x-3 bg-gray-50 p-3 rounded-md">
              <div class="drag-handle cursor-move">
                <Bars3Icon class="h-5 w-5 text-gray-400" />
              </div>
              <div class="flex-1">
                <h4 class="text-sm font-medium text-gray-900">{{ list.name }}</h4>
                <input
                  :value="list.customDescription"
                  @input="updateListDescription(listIndex, $event.target.value)"
                  type="text"
                  class="mt-1 w-full text-xs border-gray-300 rounded-md"
                  placeholder="Optional description for this list in the chain..."
                />
              </div>
              <button
                @click="$emit('remove-list', listIndex)"
                class="text-red-600 hover:text-red-700"
              >
                <XMarkIcon class="h-4 w-4" />
              </button>
            </div>
          </template>
        </draggable>
      </div>
    </div>
  </div>
</template>

<script setup>
import draggable from 'vuedraggable'
import {
  ChevronDownIcon,
  ChevronRightIcon,
  Bars3Icon,
  XMarkIcon,
  ArrowUpIcon,
  ArrowDownIcon
} from '@heroicons/vue/24/outline'

/**
 * SectionManager Component
 * 
 * Manages a single section within the chain builder.
 * Handles section-specific operations like expand/collapse, move, and list management.
 * 
 * Props:
 * - section: The section object containing name, expanded state, and lists
 * - index: The index of this section in the sections array
 * - totalSections: Total number of sections (for move button visibility)
 * 
 * Emits:
 * - toggle: Toggle expanded/collapsed state
 * - update-name: Update section name
 * - update-lists: Update the lists array
 * - move: Move section up or down
 * - remove: Remove this section
 * - remove-list: Remove a specific list from this section
 * - drop: Handle drop event for adding lists
 */

const props = defineProps({
  section: {
    type: Object,
    required: true,
    validator(value) {
      return (
        typeof value.id !== 'undefined' &&
        typeof value.name === 'string' &&
        typeof value.expanded === 'boolean' &&
        Array.isArray(value.lists)
      )
    }
  },
  index: {
    type: Number,
    required: true
  },
  totalSections: {
    type: Number,
    required: true
  }
})

const emit = defineEmits([
  'toggle',
  'update-name',
  'update-lists',
  'move',
  'remove',
  'remove-list',
  'drop'
])

// Computed properties for move button visibility
const canMoveUp = props.index > 0
const canMoveDown = props.index < props.totalSections - 1

// Handle drop event
const handleDrop = (event) => {
  event.preventDefault()
  emit('drop', event)
}

// Update a specific list's custom description
const updateListDescription = (listIndex, value) => {
  const updatedLists = [...props.section.lists]
  updatedLists[listIndex] = {
    ...updatedLists[listIndex],
    customDescription: value
  }
  emit('update-lists', updatedLists)
}
</script>