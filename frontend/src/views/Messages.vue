<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="py-4 md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold text-gray-900">Messages</h1>
          </div>
          <div class="mt-4 flex md:mt-0 md:ml-4 space-x-2">
            <button
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <CheckIcon class="-ml-0.5 mr-2 h-4 w-4" />
              Mark all as read
            </button>
            <button
              @click="refreshMessages"
              :disabled="loading"
              class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <ArrowPathIcon class="-ml-0.5 mr-2 h-4 w-4" :class="{ 'animate-spin': loading }" />
              Refresh
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="py-3 border-t flex flex-wrap gap-2">
          <button
            v-for="filter in filters"
            :key="filter.value"
            @click="activeFilter = filter.value"
            :class="[
              'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium transition-colors',
              activeFilter === filter.value
                ? 'bg-indigo-100 text-indigo-700'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            {{ filter.label }}
            <span v-if="filter.count" class="ml-1.5 inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-medium bg-white">
              {{ filter.count }}
            </span>
          </button>
        </div>
      </div>
    </div>

    <!-- Messages List -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Loading State -->
      <div v-if="loading && messages.length === 0" class="space-y-4">
        <div v-for="i in 3" :key="i" class="bg-white rounded-lg shadow-sm p-6 animate-pulse">
          <div class="flex space-x-3">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 bg-gray-200 rounded-full"></div>
            </div>
            <div class="flex-1 space-y-2">
              <div class="h-4 bg-gray-200 rounded w-1/4"></div>
              <div class="h-3 bg-gray-200 rounded w-3/4"></div>
              <div class="h-3 bg-gray-200 rounded w-1/2"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && filteredMessages.length === 0" class="bg-white rounded-lg shadow-sm">
        <div class="p-12 text-center">
          <InboxIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No messages</h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ activeFilter === 'unread' ? 'You have no unread messages' : 'You have no messages yet' }}
          </p>
        </div>
      </div>

      <!-- Messages -->
      <div v-else class="space-y-4">
        <TransitionGroup name="message" tag="div">
          <div
            v-for="message in filteredMessages"
            :key="message.id"
            :class="[
              'bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 cursor-pointer',
              message.isUnread ? 'ring-2 ring-indigo-500 ring-opacity-50' : ''
            ]"
            @click="handleMessageClick(message)"
          >
            <div class="p-6">
              <div class="flex items-start space-x-3">
                <!-- Icon -->
                <div class="flex-shrink-0">
                  <!-- Show avatar for follow notifications -->
                  <div v-if="message.type === 'follow' && message.metadata?.follower_data?.follower_avatar" class="h-10 w-10 rounded-full overflow-hidden">
                    <img 
                      :src="message.metadata.follower_data.follower_avatar" 
                      :alt="message.metadata.follower_data.follower_name"
                      class="h-full w-full object-cover"
                    />
                  </div>
                  <!-- Show icon for other notifications -->
                  <div v-else :class="[
                    'h-10 w-10 rounded-full flex items-center justify-center',
                    `bg-${message.color}-100`
                  ]">
                    <component
                      :is="getIcon(message.icon)"
                      :class="[`h-6 w-6 text-${message.color}-600`]"
                    />
                  </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <div>
                      <h3 class="text-sm font-semibold text-gray-900">
                        {{ message.title }}
                        <span v-if="message.isUnread" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                          New
                        </span>
                      </h3>
                      <p class="mt-1 text-sm text-gray-600">{{ message.message }}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex flex-col items-end">
                      <p class="text-xs text-gray-500">{{ formatDate(message.created_at) }}</p>
                      <div v-if="message.priority === 'high'" class="mt-1">
                        <ExclamationTriangleIcon class="h-4 w-4 text-amber-500" />
                      </div>
                    </div>
                  </div>

                  <!-- Action Button -->
                  <div v-if="message.action_url" class="mt-3">
                    <router-link
                      :to="message.action_url"
                      class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500"
                      @click.stop
                    >
                      View Details
                      <ArrowRightIcon class="ml-1 h-4 w-4" />
                    </router-link>
                  </div>

                  <!-- Metadata -->
                  <div v-if="message.sender" class="mt-3 flex items-center text-xs text-gray-500">
                    <span>From {{ message.sender.firstname }} {{ message.sender.lastname }}</span>
                  </div>
                </div>

                <!-- Actions Menu -->
                <div class="flex-shrink-0">
                  <Menu as="div" class="relative inline-block text-left">
                    <MenuButton
                      class="rounded-full p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                      @click.stop
                    >
                      <EllipsisVerticalIcon class="h-5 w-5" />
                    </MenuButton>
                    <transition
                      enter-active-class="transition ease-out duration-100"
                      enter-from-class="transform opacity-0 scale-95"
                      enter-to-class="transform opacity-100 scale-100"
                      leave-active-class="transition ease-in duration-75"
                      leave-from-class="transform opacity-100 scale-100"
                      leave-to-class="transform opacity-0 scale-95"
                    >
                      <MenuItems class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                        <div class="py-1">
                          <MenuItem v-if="message.isUnread" v-slot="{ active }">
                            <button
                              @click="markAsRead(message)"
                              :class="[
                                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                                'block w-full text-left px-4 py-2 text-sm'
                              ]"
                            >
                              Mark as read
                            </button>
                          </MenuItem>
                          <MenuItem v-else v-slot="{ active }">
                            <button
                              @click="markAsUnread(message)"
                              :class="[
                                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                                'block w-full text-left px-4 py-2 text-sm'
                              ]"
                            >
                              Mark as unread
                            </button>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="deleteMessage(message)"
                              :class="[
                                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                                'block w-full text-left px-4 py-2 text-sm text-red-600'
                              ]"
                            >
                              Delete
                            </button>
                          </MenuItem>
                        </div>
                      </MenuItems>
                    </transition>
                  </Menu>
                </div>
              </div>
            </div>
          </div>
        </TransitionGroup>
      </div>

      <!-- Load More -->
      <div v-if="hasMore && !loading" class="mt-8 flex justify-center">
        <button
          @click="loadMore"
          class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Load more messages
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
  CheckIcon,
  ArrowPathIcon,
  InboxIcon,
  ArrowRightIcon,
  EllipsisVerticalIcon,
  ExclamationTriangleIcon,
  BellIcon,
  BuildingStorefrontIcon,
  MegaphoneIcon,
  UserPlusIcon,
  ListBulletIcon,
  TvIcon,
  CogIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()

// State
const messages = ref([])
const loading = ref(false)
const page = ref(1)
const hasMore = ref(true)
const activeFilter = ref('all')
const unreadCount = ref(0)

// Icon mapping
const iconMap = {
  'bell': BellIcon,
  'building-storefront': BuildingStorefrontIcon,
  'megaphone': MegaphoneIcon,
  'user-plus': UserPlusIcon,
  'list-bullet': ListBulletIcon,
  'tv': TvIcon,
  'system': CogIcon
}

// Filters
const filters = computed(() => [
  { value: 'all', label: 'All Messages', count: messages.value.length },
  { value: 'unread', label: 'Unread', count: unreadCount.value },
  { value: 'system', label: 'System' },
  { value: 'claim', label: 'Claims' },
  { value: 'announcement', label: 'Announcements' },
])

// Computed filtered messages
const filteredMessages = computed(() => {
  if (activeFilter.value === 'all') {
    return messages.value
  }
  if (activeFilter.value === 'unread') {
    return messages.value.filter(m => m.isUnread)
  }
  return messages.value.filter(m => m.type === activeFilter.value)
})

// Methods
const fetchMessages = async (reset = false) => {
  if (loading.value) return
  
  loading.value = true
  try {
    if (reset) {
      page.value = 1
      messages.value = []
    }

    const response = await axios.get('/api/app-notifications', {
      params: {
        page: page.value,
        type: activeFilter.value !== 'all' && activeFilter.value !== 'unread' ? activeFilter.value : null
      }
    })

    const newMessages = response.data.data.map(msg => ({
      ...msg,
      isUnread: !msg.read_at,
      icon: msg.icon || 'bell',
      color: msg.color || 'gray'
    }))

    if (reset) {
      messages.value = newMessages
    } else {
      messages.value.push(...newMessages)
    }

    hasMore.value = response.data.current_page < response.data.last_page
    
    // Update unread count
    await updateUnreadCount()
  } catch (error) {
    console.error('Failed to fetch messages:', error)
  } finally {
    loading.value = false
  }
}

const updateUnreadCount = async () => {
  try {
    const response = await axios.get('/api/app-notifications/unread')
    unreadCount.value = response.data.count
  } catch (error) {
    console.error('Failed to fetch unread count:', error)
  }
}

const handleMessageClick = async (message) => {
  if (message.isUnread) {
    await markAsRead(message)
  }
  
  if (message.action_url) {
    router.push(message.action_url)
  }
}

const markAsRead = async (message) => {
  try {
    await axios.post(`/api/app-notifications/${message.id}/read`)
    message.isUnread = false
    message.read_at = new Date()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (error) {
    console.error('Failed to mark message as read:', error)
  }
}

const markAsUnread = async (message) => {
  try {
    // Note: You'll need to add this endpoint to your backend
    await axios.post(`/api/app-notifications/${message.id}/unread`)
    message.isUnread = true
    message.read_at = null
    unreadCount.value++
  } catch (error) {
    console.error('Failed to mark message as unread:', error)
  }
}

const markAllAsRead = async () => {
  try {
    await axios.post('/api/app-notifications/read-all')
    messages.value.forEach(msg => {
      if (msg.isUnread) {
        msg.isUnread = false
        msg.read_at = new Date()
      }
    })
    unreadCount.value = 0
  } catch (error) {
    console.error('Failed to mark all as read:', error)
  }
}

const deleteMessage = async (message) => {
  if (!confirm('Are you sure you want to delete this message?')) return
  
  try {
    await axios.delete(`/api/app-notifications/${message.id}`)
    const index = messages.value.findIndex(m => m.id === message.id)
    if (index > -1) {
      if (message.isUnread) {
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
      messages.value.splice(index, 1)
    }
  } catch (error) {
    console.error('Failed to delete message:', error)
  }
}

const loadMore = () => {
  page.value++
  fetchMessages()
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now - date)
  const diffMinutes = Math.floor(diffTime / (1000 * 60))
  const diffHours = Math.floor(diffTime / (1000 * 60 * 60))
  const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
  
  if (diffMinutes < 1) {
    return 'just now'
  } else if (diffMinutes < 60) {
    return `${diffMinutes} minute${diffMinutes === 1 ? '' : 's'} ago`
  } else if (diffHours < 24) {
    return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`
  } else if (diffDays < 7) {
    return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`
  } else {
    // For older dates, show formatted date
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric',
      year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
    })
  }
}

const refreshMessages = () => {
  fetchMessages(true)
}

const getIcon = (iconName) => {
  return iconMap[iconName] || BellIcon
}

// Lifecycle
onMounted(() => {
  fetchMessages()
})
</script>

<style scoped>
.message-enter-active,
.message-leave-active {
  transition: all 0.3s ease;
}
.message-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}
.message-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>