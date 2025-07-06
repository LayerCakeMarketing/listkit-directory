<template>
  <Menu as="div" class="relative z-50 inline-block text-left">
    <div>
      <MenuButton class="inline-flex items-center gap-2 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-gray-300 ring-inset hover:bg-gray-50 transition-colors">
        <img
          :src="profileImageUrl"
          :alt="`${user.name} profile`"
          class="h-6 w-6 rounded-full object-cover bg-gray-200"
          @error="handleImageError"
        />
        <span class="hidden sm:block">{{ user.name }}</span>
        <ChevronDownIcon class="-mr-1 size-4 text-gray-400" aria-hidden="true" />
      </MenuButton>
    </div>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
    <div class="relative z-50">
      <MenuItems class="absolute right-0 z-50 mt-2 w-64 origin-top-right divide-y divide-gray-100 rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none">
        <!-- User Info Header -->
        <div class="px-4 py-3 bg-gray-50 rounded-t-md">
          <div class="flex items-center space-x-3">
            <img
              :src="profileImageUrl"
              :alt="`${user.name} profile`"
              class="h-10 w-10 rounded-full object-cover bg-gray-200"
              @error="handleImageError"
            />
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>
              <p class="text-xs text-gray-500 truncate">{{ user.email }}</p>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                {{ user.role }}
              </span>
            </div>
          </div>
        </div>

        <!-- Navigation Links -->
        <div class="py-1">
          <MenuItem v-slot="{ active }">
            <button
              @click="openNotifications"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center w-full px-4 py-2 text-sm text-left'
              ]"
            >
              <BellIcon class="mr-3 size-5 text-gray-400" />
              <span>Notifications</span>
              <span v-if="notificationCount > 0" class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                {{ notificationCount }}
              </span>
            </button>
          </MenuItem>

          <MenuItem v-slot="{ active }">
            <Link
              :href="route('user.lists')"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm'
              ]"
            >
              <DocumentTextIcon class="mr-3 size-5 text-gray-400" />
              My Lists
            </Link>
          </MenuItem>
          
          <MenuItem v-slot="{ active }">
            <Link
              :href="myPageUrl"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex justify-between items-center px-4 py-2 text-sm'
              ]"
            >
              <div class="flex items-center">
                <UserIcon class="mr-3 size-5 text-gray-400" />
                My Page
              </div>
              <Link
                :href="myPageEditUrl"
                class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded hover:bg-gray-200"
                @click.stop
                title="Edit My Page"
              >
                <CogIcon class="size-4" />
              </Link>
            </Link>
          </MenuItem>
          
          <MenuItem v-slot="{ active }">
            <button
              @click="openSettings"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center w-full px-4 py-2 text-sm text-left'
              ]"
            >
              <CogIcon class="mr-3 size-5 text-gray-400" />
              Settings
            </button>
          </MenuItem>
        </div>

        <!-- Administration Section (only for admin/manager users) -->
        <div v-if="canAccessAdmin" class="py-1 border-t border-gray-100">
          <MenuItem v-slot="{ active }">
            <Link
              :href="route('admin.dashboard')"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center px-4 py-2 text-sm'
              ]"
            >
              <BuildingOfficeIcon class="mr-3 size-5 text-gray-400" />
              Administration
            </Link>
          </MenuItem>
        </div>

        <!-- Logout Section -->
        <div class="py-1">
          <MenuItem v-slot="{ active }">
            <Link
              :href="route('logout')"
              method="post"
              as="button"
              :class="[
                active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                'group flex items-center w-full px-4 py-2 text-sm text-left'
              ]"
            >
              <ArrowRightOnRectangleIcon class="mr-3 size-5 text-gray-400" />
              Log Out
            </Link>
          </MenuItem>
        </div>
      </MenuItems>
      </div>
    </transition>

    <!-- Settings Modal -->
    <UserSettingsModal
      :show="showSettings"
      @close="closeSettings"
      @updated="handleUserUpdated"
    />
  </Menu>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  ArrowRightOnRectangleIcon,
  BellIcon,
  BuildingOfficeIcon,
  ChevronDownIcon,
  CogIcon,
  DocumentTextIcon,
  UserIcon,
} from '@heroicons/vue/20/solid'
import UserSettingsModal from '@/Components/UserSettingsModal.vue'

const page = usePage()
const user = computed(() => page.props.auth.user)

// Settings modal state
const showSettings = ref(false)

// Profile image with fallback
const profileImageUrl = ref(null)

// Computed URLs
const myPageUrl = computed(() => {
  if (user.value.custom_url) {
    return `/${user.value.custom_url}`
  }
  return `/user/${user.value.username || user.value.id}`
})

const myPageEditUrl = computed(() => {
  return route('profile.edit')
})

// Notification count (placeholder for future functionality)
const notificationCount = ref(0)

// Check if user can access admin features
const canAccessAdmin = computed(() => {
  const userRole = user.value?.role
  return ['admin', 'manager'].includes(userRole)
})

// Initialize profile image
const initializeProfileImage = () => {
  if (user.value.avatar_cloudflare_id) {
    // Use Cloudflare delivery URL
    profileImageUrl.value = `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${user.value.avatar_cloudflare_id}/public`
  } else if (user.value.avatar) {
    profileImageUrl.value = user.value.avatar
  } else {
    profileImageUrl.value = generateDefaultAvatar()
  }
}

// Generate default avatar (initials-based)
const generateDefaultAvatar = () => {
  const initials = user.value.name
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .substring(0, 2)
    .toUpperCase()
  
  // Create a data URL for a simple avatar with initials
  const canvas = document.createElement('canvas')
  canvas.width = 40
  canvas.height = 40
  const ctx = canvas.getContext('2d')
  
  // Background
  ctx.fillStyle = '#6366f1' // Indigo color
  ctx.fillRect(0, 0, 40, 40)
  
  // Text
  ctx.fillStyle = '#ffffff'
  ctx.font = '16px sans-serif'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText(initials, 20, 20)
  
  return canvas.toDataURL()
}

// Handle image error
const handleImageError = (event) => {
  event.target.src = generateDefaultAvatar()
}

// Modal handlers
const openSettings = () => {
  showSettings.value = true
}

const closeSettings = () => {
  showSettings.value = false
}

const openNotifications = () => {
  // Placeholder for future notifications functionality
  console.log('Notifications clicked - feature coming soon!')
}

const handleUserUpdated = (updatedUser) => {
  // Handle user data updates from settings modal
  page.props.auth.user = { ...page.props.auth.user, ...updatedUser }
  initializeProfileImage()
}

// Initialize on component mount
initializeProfileImage()
</script>