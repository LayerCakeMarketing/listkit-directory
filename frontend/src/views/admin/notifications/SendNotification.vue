<template>
  <div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Send Notification
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Send system notifications to users based on filters
          </p>
        </div>
      </div>

      <!-- Form -->
      <form @submit.prevent="sendNotification" class="space-y-8 bg-white shadow rounded-lg p-6">
        <!-- Message Content -->
        <div class="space-y-6">
          <h3 class="text-lg font-medium text-gray-900">Message Content</h3>
          
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700">
              Title <span class="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="title"
              v-model="form.title"
              required
              maxlength="255"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Important update about your listing"
            />
          </div>

          <div>
            <label for="message" class="block text-sm font-medium text-gray-700">
              Message <span class="text-red-500">*</span>
            </label>
            <textarea
              id="message"
              v-model="form.message"
              required
              rows="4"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="Write your message here..."
            ></textarea>
          </div>

          <div>
            <label for="action_url" class="block text-sm font-medium text-gray-700">
              Action URL (optional)
            </label>
            <input
              type="text"
              id="action_url"
              v-model="form.action_url"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              placeholder="/places/my-business"
            />
            <p class="mt-1 text-xs text-gray-500">Link users can click to take action</p>
          </div>

          <div>
            <label for="priority" class="block text-sm font-medium text-gray-700">Priority</label>
            <select
              id="priority"
              v-model="form.priority"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="low">Low</option>
              <option value="normal">Normal</option>
              <option value="high">High</option>
            </select>
          </div>

          <div class="flex items-center">
            <input
              id="send_email"
              type="checkbox"
              v-model="form.send_email"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="send_email" class="ml-2 block text-sm text-gray-900">
              Also send email notification
            </label>
          </div>
        </div>

        <!-- Target Audience -->
        <div class="space-y-6 pt-6 border-t border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">Target Audience</h3>
          
          <div>
            <label for="user_type" class="block text-sm font-medium text-gray-700">
              User Type <span class="text-red-500">*</span>
            </label>
            <select
              id="user_type"
              v-model="form.user_type"
              @change="updateRecipientPreview"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="">Select user type</option>
              <option v-for="type in filters.user_types" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>

          <!-- Additional Filters -->
          <div v-if="form.user_type && form.user_type !== 'all'" class="space-y-4">
            <h4 class="text-sm font-medium text-gray-700">Additional Filters (optional)</h4>
            
            <!-- Claim Status Filter -->
            <div v-if="['has_claim', 'business_owner'].includes(form.user_type)">
              <label for="claim_status" class="block text-sm font-medium text-gray-700">
                Claim Status
              </label>
              <select
                id="claim_status"
                v-model="form.filters.claim_status"
                @change="updateRecipientPreview"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="">Any status</option>
                <option v-for="status in filters.claim_statuses" :key="status.value" :value="status.value">
                  {{ status.label }}
                </option>
              </select>
            </div>

            <!-- Tags Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700">User Tags</label>
              <div class="mt-2 space-y-2 max-h-32 overflow-y-auto border border-gray-200 rounded-md p-2">
                <label v-for="tag in filters.tags" :key="tag.value" class="flex items-center">
                  <input
                    type="checkbox"
                    :value="tag.value"
                    v-model="form.filters.tags"
                    @change="updateRecipientPreview"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  />
                  <span class="ml-2 text-sm text-gray-700">{{ tag.label }}</span>
                </label>
              </div>
            </div>

            <!-- Minimum Followers -->
            <div>
              <label for="min_followers" class="block text-sm font-medium text-gray-700">
                Minimum Followers
              </label>
              <input
                type="number"
                id="min_followers"
                v-model.number="form.filters.min_followers"
                @change="updateRecipientPreview"
                min="0"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                placeholder="0"
              />
            </div>
          </div>

          <!-- Recipient Preview -->
          <div v-if="recipientPreview" class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-900 mb-2">Recipient Preview</h4>
            <p class="text-sm text-gray-700">
              This notification will be sent to approximately 
              <span class="font-semibold">{{ recipientPreview.total_recipients }}</span> users
            </p>
            <div v-if="recipientPreview.sample_users.length > 0" class="mt-2">
              <p class="text-xs text-gray-500 mb-1">Sample recipients:</p>
              <ul class="text-xs text-gray-600">
                <li v-for="user in recipientPreview.sample_users" :key="user.id">
                  {{ user.firstname }} {{ user.lastname }} ({{ user.email }})
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="pt-6 border-t border-gray-200 flex justify-between">
          <button
            type="button"
            @click="previewNotification"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <EyeIcon class="-ml-1 mr-2 h-4 w-4" />
            Preview
          </button>
          
          <div class="space-x-3">
            <router-link
              :to="{ name: 'AdminDashboard' }"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </router-link>
            <button
              type="submit"
              :disabled="sending || !isFormValid"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <PaperAirplaneIcon class="-ml-1 mr-2 h-4 w-4" />
              {{ sending ? 'Sending...' : 'Send Notification' }}
            </button>
          </div>
        </div>
      </form>

      <!-- Preview Modal -->
      <div v-if="showPreview" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg max-w-lg w-full mx-4 p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Preview</h3>
          
          <div class="bg-gray-50 rounded-lg p-4 space-y-3">
            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0">
                <div :class="[
                  'h-10 w-10 rounded-full flex items-center justify-center',
                  priorityColor
                ]">
                  <BellIcon class="h-6 w-6 text-white" />
                </div>
              </div>
              <div class="flex-1">
                <h4 class="text-sm font-semibold text-gray-900">{{ form.title }}</h4>
                <p class="mt-1 text-sm text-gray-600">{{ form.message }}</p>
                <div v-if="form.action_url" class="mt-2">
                  <a href="#" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    View Details →
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button
              @click="showPreview = false"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { EyeIcon, PaperAirplaneIcon, BellIcon } from '@heroicons/vue/24/outline'

const router = useRouter()

// State
const form = ref({
  title: '',
  message: '',
  action_url: '',
  priority: 'normal',
  user_type: '',
  filters: {
    claim_status: '',
    tags: [],
    min_followers: null
  },
  send_email: false
})

const filters = ref({
  user_types: [],
  claim_statuses: [],
  tags: []
})

const sending = ref(false)
const showPreview = ref(false)
const recipientPreview = ref(null)

// Computed
const isFormValid = computed(() => {
  return form.value.title && form.value.message && form.value.user_type
})

const priorityColor = computed(() => {
  return {
    'low': 'bg-gray-400',
    'normal': 'bg-blue-500',
    'high': 'bg-red-500'
  }[form.value.priority]
})

// Methods
const fetchFilters = async () => {
  try {
    const response = await axios.get('/api/admin/notifications/filters')
    filters.value = response.data
  } catch (error) {
    console.error('Failed to fetch filters:', error)
  }
}

const updateRecipientPreview = async () => {
  if (!form.value.user_type) {
    recipientPreview.value = null
    return
  }

  try {
    const response = await axios.post('/api/admin/notifications/preview-recipients', {
      user_type: form.value.user_type,
      filters: form.value.filters
    })
    recipientPreview.value = response.data
  } catch (error) {
    console.error('Failed to preview recipients:', error)
  }
}

const previewNotification = () => {
  if (!isFormValid.value) {
    alert('Please fill in all required fields')
    return
  }
  showPreview.value = true
}

const sendNotification = async () => {
  if (!isFormValid.value) return
  
  if (!confirm(`Are you sure you want to send this notification to ${recipientPreview.value?.total_recipients || 'selected'} users?`)) {
    return
  }

  sending.value = true
  try {
    const response = await axios.post('/api/admin/notifications/send', form.value)
    
    if (response.data.success) {
      alert(`Notification sent successfully to ${response.data.count} users!`)
      router.push({ name: 'AdminDashboard' })
    } else {
      alert('Failed to send notification: ' + response.data.message)
    }
  } catch (error) {
    console.error('Failed to send notification:', error)
    alert('Failed to send notification. Please try again.')
  } finally {
    sending.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchFilters()
})
</script>