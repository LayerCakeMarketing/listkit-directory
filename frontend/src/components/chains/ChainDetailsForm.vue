<template>
  <div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">Chain Details</h2>
    
    <div class="space-y-4">
      <div>
        <label for="chain-name" class="block text-sm font-medium text-gray-700">Name</label>
        <input
          id="chain-name"
          :value="modelValue.name"
          @input="updateField('name', $event.target.value)"
          type="text"
          required
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          placeholder="My Amazing Chain"
        />
      </div>

      <div>
        <label for="chain-description" class="block text-sm font-medium text-gray-700">Description</label>
        <textarea
          id="chain-description"
          :value="modelValue.description"
          @input="updateField('description', $event.target.value)"
          rows="3"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          placeholder="Describe what this chain is about..."
        />
      </div>

      <div>
        <label for="chain-visibility" class="block text-sm font-medium text-gray-700">Visibility</label>
        <select
          id="chain-visibility"
          :value="modelValue.visibility"
          @input="updateField('visibility', $event.target.value)"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="private">Private - Only you can see</option>
          <option value="unlisted">Unlisted - Anyone with link can view</option>
          <option value="public">Public - Anyone can find and view</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * ChainDetailsForm Component
 * 
 * Handles the basic details input for a chain (name, description, visibility).
 * Uses v-model pattern for two-way data binding.
 * 
 * Props:
 * - modelValue: The form data object containing name, description, and visibility
 * 
 * Emits:
 * - update:modelValue: Emitted when any field changes
 */

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
    validator(value) {
      return (
        typeof value.name === 'string' &&
        typeof value.description === 'string' &&
        ['private', 'unlisted', 'public'].includes(value.visibility)
      )
    }
  }
})

const emit = defineEmits(['update:modelValue'])

// Update a single field in the form data
const updateField = (field, value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    [field]: value
  })
}
</script>