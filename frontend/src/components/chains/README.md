# Chain Components

This directory contains reusable components for the chain (lists of lists) functionality.

## Components

### ChainDetailsForm.vue
- **Purpose**: Handles chain metadata input (name, description, visibility)
- **Props**: 
  - `modelValue`: Chain form data object
- **Emits**: 
  - `update:modelValue`: Updates to form data
- **Usage**: Two-way binding with v-model for form fields

### ListLibrary.vue
- **Purpose**: Browse and select lists to add to chains
- **Props**:
  - `channelLists`: Array of channel-owned lists
  - `personalLists`: Array of user's personal lists
  - `chainSections`: Current chain sections (to show usage count)
  - `loading`: Loading state
- **Emits**:
  - `add-list`: When user clicks to add a list
  - `start-drag`: When drag operation starts
  - `end-drag`: When drag operation ends
- **Features**: Search, drag-and-drop, usage indicators

### ChainBuilder.vue
- **Purpose**: Main container for building chain structure
- **Props**:
  - `sections`: Array of chain sections
- **Emits**:
  - `add-section`: Add new section
  - `drop-to-empty`: Handle drop to empty state
  - `update:sections`: Update sections array
- **Features**: Empty state, section management

### SectionManager.vue
- **Purpose**: Manage individual chain sections
- **Props**:
  - `section`: Section object
  - `sectionIndex`: Index in sections array
  - `totalSections`: Total number of sections
- **Emits**:
  - `toggle`: Toggle section expanded state
  - `move`: Move section up/down
  - `remove`: Remove section
  - `drop-to-section`: Handle drop to section
  - `remove-list`: Remove list from section
  - `update:section`: Update section data
- **Features**: Expand/collapse, reordering, list management

## Usage Example

```vue
<script setup>
import ChainDetailsForm from '@/components/chains/ChainDetailsForm.vue'
import ListLibrary from '@/components/chains/ListLibrary.vue'
import ChainBuilder from '@/components/chains/ChainBuilder.vue'

const form = ref({ name: '', description: '', visibility: 'private' })
const sections = ref([])
const channelLists = ref([])
const personalLists = ref([])

const handleAddList = (list) => {
  // Add list to current section
}
</script>

<template>
  <div class="flex gap-6">
    <div class="w-5/12">
      <ChainDetailsForm v-model="form" />
      <ListLibrary 
        :channel-lists="channelLists"
        :personal-lists="personalLists"
        :chain-sections="sections"
        @add-list="handleAddList"
      />
    </div>
    <div class="flex-1">
      <ChainBuilder 
        v-model:sections="sections"
        @add-section="addSection"
      />
    </div>
  </div>
</template>
```

## Design Principles

1. **Single Responsibility**: Each component has one clear purpose
2. **Composition over Inheritance**: Use props/emits for communication
3. **Reusability**: Components can be used in create/edit views
4. **Type Safety**: Props have proper type definitions
5. **Accessibility**: Proper ARIA labels and keyboard support