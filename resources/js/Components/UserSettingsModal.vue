<template>
  <Modal :show="show" @close="close" max-width="2xl">
    <div class="p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-medium text-gray-900">Account Settings</h2>
        <button
          @click="close"
          class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <div class="space-y-6">
        <!-- Profile Image Section -->
        <div>
          <h3 class="text-sm font-medium text-gray-900 mb-3">Profile Image</h3>
          <div class="flex items-center space-x-4">
            <img
              :src="currentProfileImage"
              :alt="`${form.name} profile`"
              class="h-16 w-16 rounded-full object-cover bg-gray-200"
              @error="handleImageError"
            />
            <div class="flex-1">
              <DirectCloudflareUpload
                v-model="avatarImage"
                label="Upload New Profile Image"
                upload-type="avatar"
                :entity-id="user.id"
                :max-size-m-b="5"
                :current-image-url="currentProfileImage"
                @upload-complete="handleAvatarUpload"
                class="text-sm"
              />
              <p class="mt-1 text-xs text-gray-500">This will be used as your profile image across the platform and default to Listerino logo if not set</p>
            </div>
          </div>
        </div>

        <!-- Personal Information -->
        <div class="border-t pt-6">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Personal Information</h3>
          <div class="grid grid-cols-1 gap-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': form.errors.name }"
              />
              <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': form.errors.email }"
              />
              <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700">Mobile Phone</label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': form.errors.phone }"
                placeholder="+1 (555) 123-4567"
              />
              <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
            </div>

            <div>
              <label for="custom_url" class="block text-sm font-medium text-gray-700">Custom URL</label>
              <div class="mt-1 flex rounded-md shadow-sm">
                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                  {{ siteUrl }}/
                </span>
                <input
                  id="custom_url"
                  v-model="form.custom_url"
                  type="text"
                  class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-300': form.errors.custom_url }"
                  placeholder="your-custom-url"
                />
              </div>
              <p v-if="form.errors.custom_url" class="mt-1 text-sm text-red-600">{{ form.errors.custom_url }}</p>
              <p class="mt-1 text-sm text-gray-500">Choose a custom URL for your public profile and lists</p>
            </div>
          </div>
        </div>

        <!-- Password Change (Collapsible) -->
        <div class="border-t pt-6">
          <button
            @click="showPasswordForm = !showPasswordForm"
            class="flex items-center justify-between w-full text-left"
          >
            <h3 class="text-sm font-medium text-gray-900">Change Password</h3>
            <ChevronDownIcon 
              :class="[
                showPasswordForm ? 'rotate-180' : '',
                'h-5 w-5 text-gray-400 transition-transform duration-200'
              ]"
            />
          </button>
          
          <div v-if="showPasswordForm" class="mt-4 grid grid-cols-1 gap-4">
            <div>
              <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
              <input
                id="current_password"
                v-model="passwordForm.current_password"
                type="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': passwordForm.errors.current_password }"
              />
              <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.current_password }}</p>
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
              <input
                id="password"
                v-model="passwordForm.password"
                type="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': passwordForm.errors.password }"
              />
              <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password }}</p>
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
              <input
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': passwordForm.errors.password_confirmation }"
              />
              <p v-if="passwordForm.errors.password_confirmation" class="mt-1 text-sm text-red-600">{{ passwordForm.errors.password_confirmation }}</p>
            </div>
            
            <div class="pt-4">
              <button
                @click="updatePassword"
                :disabled="passwordForm.processing || !canUpdatePassword"
                class="w-full inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
              >
                {{ passwordForm.processing ? 'Updating Password...' : 'Update Password' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-end pt-6 border-t mt-6">
        <div class="flex space-x-3">
          <button
            @click="close"
            class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          >
            Cancel
          </button>
          <button
            @click="updateProfile"
            :disabled="form.processing"
            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>

      <!-- Success Messages -->
      <div v-if="showSuccessMessage" class="mt-4 rounded-md bg-green-50 p-4">
        <div class="flex">
          <CheckCircleIcon class="h-5 w-5 text-green-400" />
          <div class="ml-3">
            <p class="text-sm font-medium text-green-800">
              {{ successMessage }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import DirectCloudflareUpload from '@/Components/ImageUpload/DirectCloudflareUpload.vue'
import { XMarkIcon, CheckCircleIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean
})

const emit = defineEmits(['close', 'updated'])

const page = usePage()
const user = computed(() => page.props.auth.user)

// Profile form
const form = useForm({
  name: user.value.name,
  email: user.value.email,
  phone: user.value.phone || '',
  custom_url: user.value.custom_url || '',
  avatar_cloudflare_id: user.value.avatar_cloudflare_id || null
})

// Password form
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: ''
})

// UI state
const showPasswordForm = ref(false)

// Image upload state
const avatarImage = ref(null)
const currentProfileImage = ref(null)

// Success message state
const showSuccessMessage = ref(false)
const successMessage = ref('')

// Site URL for custom URL preview
const siteUrl = computed(() => {
  if (typeof window !== 'undefined') {
    return window.location.origin
  }
  return 'http://localhost:8000'
})

// Can update password check
const canUpdatePassword = computed(() => {
  return passwordForm.current_password && 
         passwordForm.password && 
         passwordForm.password_confirmation &&
         passwordForm.password === passwordForm.password_confirmation
})

// Initialize profile image
const initializeProfileImage = () => {
  if (user.value.avatar_cloudflare_id) {
    currentProfileImage.value = `https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A/${user.value.avatar_cloudflare_id}/public`
  } else if (user.value.avatar) {
    currentProfileImage.value = user.value.avatar
  } else {
    currentProfileImage.value = generateDefaultAvatar()
  }
}

// Generate default avatar (Listerino logo)
const generateDefaultAvatar = () => {
  const canvas = document.createElement('canvas')
  canvas.width = 64
  canvas.height = 64
  const ctx = canvas.getContext('2d')
  
  // Background gradient
  const gradient = ctx.createLinearGradient(0, 0, 64, 64)
  gradient.addColorStop(0, '#6366f1')
  gradient.addColorStop(1, '#4f46e5')
  ctx.fillStyle = gradient
  ctx.fillRect(0, 0, 64, 64)
  
  // Listerino "L" logo
  ctx.fillStyle = '#ffffff'
  ctx.font = 'bold 32px sans-serif'
  ctx.textAlign = 'center'
  ctx.textBaseline = 'middle'
  ctx.fillText('L', 32, 32)
  
  return canvas.toDataURL()
}

// Handle image error
const handleImageError = (event) => {
  event.target.src = generateDefaultAvatar()
}

// Handle avatar upload
const handleAvatarUpload = (image) => {
  avatarImage.value = image
  form.avatar_cloudflare_id = image.cloudflare_id
  currentProfileImage.value = image.urls.original
}

// Update profile
const updateProfile = () => {
  form.patch(route('profile.update'), {
    preserveScroll: true,
    onSuccess: (page) => {
      showSuccessMessage.value = true
      successMessage.value = 'Profile updated successfully!'
      setTimeout(() => {
        showSuccessMessage.value = false
      }, 3000)
      // Update the current user data in the page props to reflect changes
      page.props.auth.user = { ...page.props.auth.user, ...form.data() }
      emit('updated', form.data())
    }
  })
}

// Update password
const updatePassword = () => {
  passwordForm.put(route('password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset()
      showSuccessMessage.value = true
      successMessage.value = 'Password updated successfully!'
      setTimeout(() => {
        showSuccessMessage.value = false
      }, 3000)
    }
  })
}

// Close modal
const close = () => {
  emit('close')
  // Reset forms when closing
  form.reset()
  passwordForm.reset()
  showSuccessMessage.value = false
}

// Reset form data when user changes
watch(() => user.value, (newUser) => {
  if (newUser) {
    form.defaults({
      name: newUser.name,
      email: newUser.email,
      phone: newUser.phone || '',
      custom_url: newUser.custom_url || '',
      avatar_cloudflare_id: newUser.avatar_cloudflare_id || null
    })
    form.reset()
    initializeProfileImage()
  }
}, { immediate: true })

// Initialize
initializeProfileImage()
</script>