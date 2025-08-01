<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Chain Content -->
      <div v-else-if="chain">
        <!-- Header -->
        <div class="bg-white shadow-sm rounded-lg mb-6">
          <div class="px-6 py-4">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ chain.name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ chain.description }}</p>
              </div>
              <div class="flex items-center space-x-3">
                <span
                  class="px-3 py-1 text-sm rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': chain.visibility === 'public',
                    'bg-gray-100 text-gray-800': chain.visibility === 'private',
                    'bg-yellow-100 text-yellow-800': chain.visibility === 'unlisted'
                  }"
                >
                  {{ chain.visibility }}
                </span>
                <button
                  v-if="canEdit"
                  @click="editChain"
                  class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                  <PencilIcon class="h-4 w-4 mr-1" />
                  Edit
                </button>
              </div>
            </div>
            <div class="mt-4 flex items-center text-sm text-gray-500 space-x-4">
              <span>{{ chain.lists_count }} lists</span>
              <span>•</span>
              <span>{{ chain.views_count }} views</span>
              <span>•</span>
              <span>Created {{ formatDate(chain.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Lists Timeline -->
        <div class="space-y-6">
          <div v-for="(item, index) in chain.lists" :key="item.id" class="relative">
            <!-- Connector Line -->
            <div
              v-if="index < chain.lists.length - 1"
              class="absolute left-8 top-20 bottom-0 w-0.5 bg-gray-300"
            ></div>

            <!-- List Card -->
            <div class="flex items-start space-x-4">
              <!-- Step Number -->
              <div class="flex-shrink-0 w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                {{ index + 1 }}
              </div>

              <!-- List Content -->
              <div class="flex-1 bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <router-link
                  :to="`/lists/${item.id}`"
                  class="block p-6"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h3 class="text-lg font-semibold text-gray-900">
                        {{ item.pivot.label || `Step ${index + 1}` }}
                      </h3>
                      <h4 class="mt-1 text-md font-medium text-gray-700">
                        {{ item.name }}
                      </h4>
                      <p v-if="item.pivot.description" class="mt-2 text-sm text-gray-600">
                        {{ item.pivot.description }}
                      </p>
                      <p v-else-if="item.description" class="mt-2 text-sm text-gray-600">
                        {{ item.description }}
                      </p>
                      <div class="mt-3 flex items-center text-sm text-gray-500 space-x-4">
                        <span class="flex items-center">
                          <FolderIcon class="h-4 w-4 mr-1" />
                          {{ item.items_count }} items
                        </span>
                        <span
                          class="px-2 py-0.5 text-xs rounded-full"
                          :class="{
                            'bg-green-100 text-green-800': item.visibility === 'public',
                            'bg-gray-100 text-gray-800': item.visibility === 'private'
                          }"
                        >
                          {{ item.visibility }}
                        </span>
                      </div>
                    </div>
                    <ChevronRightIcon class="h-5 w-5 text-gray-400 ml-4" />
                  </div>
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Owner Info -->
        <div class="mt-8 bg-white rounded-lg shadow-sm p-6">
          <div class="flex items-center">
            <img
              v-if="chain.owner?.avatar"
              :src="chain.owner.avatar"
              :alt="chain.owner.name"
              class="h-12 w-12 rounded-full"
            />
            <div v-else class="h-12 w-12 rounded-full bg-gray-200 flex items-center justify-center">
              <UserIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-gray-900">
                Created by {{ chain.owner?.name || 'Unknown' }}
              </p>
              <p class="text-sm text-gray-500">
                {{ chain.owner?.username ? `@${chain.owner.username}` : '' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else class="text-center py-12">
        <h3 class="text-lg font-medium text-gray-900">Chain not found</h3>
        <p class="mt-2 text-sm text-gray-500">
          The chain you're looking for doesn't exist or you don't have permission to view it.
        </p>
        <router-link
          to="/mylists"
          class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-100 hover:bg-indigo-200"
        >
          Back to My Lists
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import {
  PencilIcon,
  ChevronRightIcon,
  FolderIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  id: {
    type: String,
    required: true
  }
})

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const chain = ref(null)
const loading = ref(true)

const canEdit = computed(() => {
  if (!chain.value || !authStore.user) return false
  
  return chain.value.owner_type === 'App\\Models\\User' && 
         chain.value.owner_id === authStore.user.id
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
}

const fetchChain = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/api/chains/${props.id}`)
    chain.value = response.data
  } catch (error) {
    console.error('Error fetching chain:', error)
    chain.value = null
  } finally {
    loading.value = false
  }
}

const editChain = () => {
  router.push(`/chains/${props.id}/edit`)
}

onMounted(() => {
  fetchChain()
})
</script>