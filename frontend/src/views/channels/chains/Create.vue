<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center">
            <router-link
              :to="`/channels/${channelId}/edit?tab=chains`"
              class="flex items-center text-gray-500 hover:text-gray-700"
            >
              <ChevronLeftIcon class="h-5 w-5 mr-1" />
              <span class="text-sm">Back to Channel</span>
            </router-link>
            <div class="ml-6">
              <h1 class="text-xl font-semibold text-gray-900">Create Chain for {{ channel?.name }}</h1>
              <p class="text-sm text-gray-500">Creating as @{{ channel?.slug }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <button
              @click="saveDraft"
              :disabled="!canSave || saving"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Save Draft
            </button>
            <button
              @click="createChain"
              :disabled="!canSubmit || saving"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ saving ? 'Creating...' : 'Create Chain' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex gap-6">
        <!-- Left Column: Chain Details & List Library -->
        <div class="w-5/12 space-y-6">
          <!-- Chain Details Form -->
          <ChainDetailsForm v-model="form" />

          <!-- List Library -->
          <ListLibrary
            :channel-lists="channelLists"
            :personal-lists="personalLists"
            :loading="loading"
            :chain-lists="allChainListIds"
            @drag-start="startDragList"
            @drag-end="endDragList"
            @add-to-chain="addListToChain"
          />
        </div>

        <!-- Right Column: Chain Builder -->
        <ChainBuilder
          :sections="chainSections"
          :dragged-list="draggedList"
          @add-section="addSection"
          @update-sections="chainSections = $event"
          @remove-section="removeSection"
          @drop-to-empty="handleDropToEmpty"
          @drop-to-section="handleDropToSection"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'
import { ChevronLeftIcon } from '@heroicons/vue/24/outline'

// Import our new components
import ChainDetailsForm from '@/components/chains/ChainDetailsForm.vue'
import ListLibrary from '@/components/chains/ListLibrary.vue'
import ChainBuilder from '@/components/chains/ChainBuilder.vue'

const props = defineProps({
  channelId: {
    type: String,
    required: true
  }
})

const route = useRoute()
const router = useRouter()
const { showSuccess, showError } = useNotification()

// Channel data
const channel = ref(null)

// Form data
const form = ref({
  name: '',
  description: '',
  visibility: 'private',
  status: 'draft',
  cover_image: null,
  cover_cloudflare_id: null
})

// List management
const loading = ref(false)
const saving = ref(false)
const channelLists = ref([])
const personalLists = ref([])
const chainSections = ref([])
const draggedList = ref(null)

// Computed properties
const totalListsCount = computed(() => {
  return chainSections.value.reduce((total, section) => total + section.lists.length, 0)
})

const canSave = computed(() => {
  return form.value.name && totalListsCount.value >= 2
})

const canSubmit = computed(() => {
  return form.value.name && totalListsCount.value >= 2
})

// Get all list IDs currently in the chain (for usage tracking in ListLibrary)
const allChainListIds = computed(() => {
  const ids = []
  chainSections.value.forEach(section => {
    section.lists.forEach(list => {
      ids.push(list.id)
    })
  })
  return ids
})

// Section management
const addSection = () => {
  chainSections.value.push({
    id: Date.now(),
    name: '',
    expanded: true,
    lists: []
  })
}

const removeSection = (index) => {
  chainSections.value.splice(index, 1)
}

// List management
const addListToChain = (list) => {
  // If no sections exist, create one
  if (chainSections.value.length === 0) {
    addSection()
  }

  // Add to the last section
  const targetSection = chainSections.value[chainSections.value.length - 1]
  targetSection.lists.push({
    ...list,
    customDescription: ''
  })
}

// Drag and drop handlers
const startDragList = (event, list) => {
  draggedList.value = list
  event.dataTransfer.effectAllowed = 'copy'
}

const endDragList = () => {
  draggedList.value = null
}

const handleDropToEmpty = (event) => {
  event.preventDefault()
  if (draggedList.value) {
    addSection()
    chainSections.value[0].lists.push({
      ...draggedList.value,
      customDescription: ''
    })
  }
}

const handleDropToSection = (event, sectionIndex) => {
  event.preventDefault()
  if (draggedList.value) {
    chainSections.value[sectionIndex].lists.push({
      ...draggedList.value,
      customDescription: ''
    })
  }
}

// Save/Create chain
const saveDraft = async () => {
  if (!canSave.value) return
  await saveChain('draft')
}

const createChain = async () => {
  if (!canSubmit.value) return
  await saveChain('published')
}

const saveChain = async (status) => {
  saving.value = true
  try {
    // Flatten all lists from sections with proper labels
    const allLists = []
    chainSections.value.forEach((section, sectionIndex) => {
      const sectionLabel = section.name || `Section ${sectionIndex + 1}`
      section.lists.forEach((list, listIndex) => {
        allLists.push({
          list_id: list.id,
          label: sectionLabel + (list.customDescription ? '' : ` - ${list.name}`),
          description: list.customDescription || null
        })
      })
    })

    // Prepare the chain data
    const chainData = {
      ...form.value,
      status,
      lists: allLists,
      owner_type: 'channel',
      owner_id: parseInt(props.channelId),
      metadata: {
        sections: chainSections.value.map(section => ({
          name: section.name,
          listCount: section.lists.length
        }))
      }
    }

    console.log('Sending channel chain data:', chainData)
    const response = await axios.post('/api/chains', chainData)
    
    showSuccess(status === 'draft' ? 'Chain saved as draft!' : 'Chain created successfully!')
    
    // Navigate to the channel chains tab
    router.push(`/channels/${props.channelId}/edit?tab=chains`)
  } catch (error) {
    console.error('Error saving chain:', error)
    console.error('Error response:', error.response?.data)
    
    // Show validation errors if present
    if (error.response?.data?.errors) {
      const errors = error.response.data.errors
      const firstError = Object.values(errors)[0]
      showError(Array.isArray(firstError) ? firstError[0] : firstError)
    } else {
      showError(error.response?.data?.message || 'Failed to save chain')
    }
  } finally {
    saving.value = false
  }
}

// Fetch channel and lists
const fetchChannelData = async () => {
  loading.value = true
  try {
    // Fetch channel details
    const channelResponse = await axios.get(`/api/channels/${props.channelId}`)
    channel.value = channelResponse.data

    // Fetch channel lists
    const channelListsResponse = await axios.get(`/api/channels/${props.channelId}/lists`, {
      params: {
        per_page: 100,
        sort_by: 'created_at',
        sort_order: 'desc'
      }
    })
    channelLists.value = channelListsResponse.data.data || []

    // Fetch personal lists
    const personalListsResponse = await axios.get('/api/lists', {
      params: {
        per_page: 100,
        sort: 'created_at',
        order: 'desc'
      }
    })
    personalLists.value = personalListsResponse.data.data || []
  } catch (error) {
    console.error('Error fetching data:', error)
    showError('Failed to load channel data')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchChannelData()
})
</script>