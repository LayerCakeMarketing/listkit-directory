<template>
  <div class="relative">
    <Popover class="relative">
      <PopoverButton
        class="relative rounded-full p-1 text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        @click="handleBellClick"
      >
        <span class="sr-only">View notifications</span>
        <BellIcon class="h-6 w-6" />
        
        <!-- Notification Badge -->
        <span
          v-if="unreadCount > 0"
          class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
      </PopoverButton>

      <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1"
      >
        <PopoverPanel class="absolute right-0 z-10 mt-2 w-80 max-w-sm transform px-4 sm:px-0">
          <div class="overflow-hidden rounded-lg shadow-lg ring-1 ring-black ring-opacity-5">
            <!-- Header -->
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-900">Notifications</h3>
                <router-link
                  to="/messages"
                  class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                  View all
                </router-link>
              </div>
            </div>

            <!-- Notifications List -->
            <div class="bg-white">
              <!-- Loading State -->
              <div v-if="loading" class="p-4">
                <div class="animate-pulse space-y-3">
                  <div v-for="i in 3" :key="i" class="flex space-x-3">
                    <div class="flex-shrink-0">
                      <div class="h-8 w-8 bg-gray-200 rounded-full"></div>
                    </div>
                    <div class="flex-1 space-y-2">
                      <div class="h-3 bg-gray-200 rounded w-3/4"></div>
                      <div class="h-2 bg-gray-200 rounded w-1/2"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty State -->
              <div v-else-if="notifications.length === 0" class="p-8 text-center">
                <BellSlashIcon class="mx-auto h-8 w-8 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500">No new notifications</p>
              </div>

              <!-- Notifications -->
              <div v-else class="max-h-96 overflow-y-auto">
                <div
                  v-for="notification in notifications"
                  :key="notification.id"
                  class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-0"
                  @click="handleNotificationClick(notification)"
                >
                  <div class="flex space-x-3">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                      <!-- Show avatar for follow notifications -->
                      <div v-if="notification.type === 'follow' && notification.metadata?.follower_data?.follower_avatar" class="h-8 w-8 rounded-full overflow-hidden">
                        <img 
                          :src="notification.metadata.follower_data.follower_avatar" 
                          :alt="notification.metadata.follower_data.follower_name"
                          class="h-full w-full object-cover"
                        />
                      </div>
                      <!-- Show icon for other notifications -->
                      <div v-else :class="[
                        'h-8 w-8 rounded-full flex items-center justify-center',
                        `bg-${notification.color}-100`
                      ]">
                        <component
                          :is="getIcon(notification.icon)"
                          :class="[`h-4 w-4 text-${notification.color}-600`]"
                        />
                      </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 truncate">
                        {{ notification.title }}
                      </p>
                      <p class="text-sm text-gray-500 truncate">
                        {{ notification.message }}
                      </p>
                      <p class="text-xs text-gray-400 mt-1">
                        {{ notification.created_at }}
                      </p>
                    </div>

                    <!-- Unread Indicator -->
                    <div v-if="notification.isUnread" class="flex-shrink-0">
                      <div class="h-2 w-2 bg-indigo-400 rounded-full"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div v-if="unreadCount > 0" class="bg-gray-50 px-4 py-2 border-t border-gray-200">
                <button
                  @click="markAllAsRead"
                  class="text-sm text-gray-600 hover:text-gray-900 font-medium"
                >
                  Mark all as read
                </button>
              </div>
            </div>
          </div>
        </PopoverPanel>
      </transition>
    </Popover>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { Popover, PopoverButton, PopoverPanel } from '@headlessui/vue'
import {
  BellIcon,
  BellSlashIcon,
  BuildingStorefrontIcon,
  MegaphoneIcon,
  UserPlusIcon,
  ListBulletIcon,
  TvIcon,
  CogIcon
} from '@heroicons/vue/24/outline'
import { useNotifications } from '@/composables/useNotifications'

const router = useRouter()
const { 
  notifications, 
  unreadCount, 
  loading, 
  fetchUnread, 
  markAsRead, 
  markAllAsRead: markAllRead 
} = useNotifications()

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

// Methods
const handleBellClick = () => {
  fetchUnread()
}

const handleNotificationClick = async (notification) => {
  // Mark as read
  if (notification.isUnread) {
    await markAsRead(notification.id)
  }

  // Navigate to action URL or messages page
  if (notification.action_url) {
    router.push(notification.action_url)
  } else {
    router.push('/messages')
  }
}

const markAllAsRead = async () => {
  await markAllRead()
}

const getIcon = (iconName) => {
  return iconMap[iconName] || BellIcon
}

// Auto-refresh notifications every 30 seconds
let refreshInterval = null

onMounted(() => {
  fetchUnread()
  refreshInterval = setInterval(() => {
    fetchUnread()
  }, 30000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<style scoped>
/* Custom styles for color classes that might not be in Tailwind's default purge */
.bg-blue-100 { background-color: rgb(219 234 254); }
.text-blue-600 { color: rgb(37 99 235); }
.bg-indigo-100 { background-color: rgb(224 231 255); }
.text-indigo-600 { color: rgb(79 70 229); }
.bg-purple-100 { background-color: rgb(243 232 255); }
.text-purple-600 { color: rgb(147 51 234); }
.bg-green-100 { background-color: rgb(220 252 231); }
.text-green-600 { color: rgb(22 163 74); }
.bg-yellow-100 { background-color: rgb(254 249 195); }
.text-yellow-600 { color: rgb(202 138 4); }
.bg-pink-100 { background-color: rgb(252 231 243); }
.text-pink-600 { color: rgb(219 39 119); }
.bg-gray-100 { background-color: rgb(243 244 246); }
.text-gray-600 { color: rgb(75 85 99); }
</style>