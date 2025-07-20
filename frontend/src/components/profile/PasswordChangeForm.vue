<template>
  <div class="bg-white shadow rounded-lg p-6">
    <div class="mb-6">
      <h3 class="text-lg font-medium text-gray-900">Change Password</h3>
      <p class="mt-1 text-sm text-gray-600">Update your password to keep your account secure</p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Current Password -->
      <div>
        <label for="current_password" class="block text-sm font-medium text-gray-700">
          Current Password
        </label>
        <div class="mt-1 relative">
          <input
            v-model="form.current_password"
            :type="showCurrentPassword ? 'text' : 'password'"
            id="current_password"
            name="current_password"
            autocomplete="current-password"
            required
            :disabled="processing"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-50 disabled:text-gray-500"
            @input="clearError('current_password')"
          />
          <button
            type="button"
            @click="showCurrentPassword = !showCurrentPassword"
            class="absolute inset-y-0 right-0 pr-3 flex items-center"
          >
            <EyeIcon v-if="!showCurrentPassword" class="h-5 w-5 text-gray-400" />
            <EyeSlashIcon v-else class="h-5 w-5 text-gray-400" />
          </button>
        </div>
        <p v-if="errors.current_password" class="mt-2 text-sm text-red-600">
          {{ errors.current_password }}
        </p>
      </div>

      <!-- New Password Fields (shown after current password is entered) -->
      <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 transform scale-95"
        enter-to-class="opacity-100 transform scale-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 transform scale-100"
        leave-to-class="opacity-0 transform scale-95"
      >
        <div v-if="form.current_password.length >= 6" class="space-y-6">
          <!-- New Password -->
          <div>
            <label for="new_password" class="block text-sm font-medium text-gray-700">
              New Password
            </label>
            <div class="mt-1 relative">
              <input
                v-model="form.password"
                :type="showNewPassword ? 'text' : 'password'"
                id="new_password"
                name="new_password"
                autocomplete="new-password"
                required
                :disabled="processing"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-50 disabled:text-gray-500"
                @input="validatePassword"
              />
              <button
                type="button"
                @click="showNewPassword = !showNewPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <EyeIcon v-if="!showNewPassword" class="h-5 w-5 text-gray-400" />
                <EyeSlashIcon v-else class="h-5 w-5 text-gray-400" />
              </button>
            </div>
            
            <!-- Password Strength Meter -->
            <div v-if="form.password" class="mt-2">
              <div class="flex items-center space-x-2">
                <div class="flex-1 bg-gray-200 rounded-full h-2">
                  <div
                    class="h-2 rounded-full transition-all duration-300"
                    :class="passwordStrengthClass"
                    :style="{ width: `${passwordStrength}%` }"
                  ></div>
                </div>
                <span class="text-sm" :class="passwordStrengthTextClass">
                  {{ passwordStrengthText }}
                </span>
              </div>
              <ul v-if="passwordSuggestions.length > 0" class="mt-2 text-sm text-gray-600">
                <li v-for="suggestion in passwordSuggestions" :key="suggestion" class="flex items-start">
                  <span class="text-gray-400 mr-1">•</span>
                  {{ suggestion }}
                </li>
              </ul>
            </div>
            
            <p v-if="errors.password" class="mt-2 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
              Confirm New Password
            </label>
            <div class="mt-1 relative">
              <input
                v-model="form.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                required
                :disabled="processing"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm disabled:bg-gray-50 disabled:text-gray-500"
                @input="validatePasswordMatch"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center"
              >
                <EyeIcon v-if="!showConfirmPassword" class="h-5 w-5 text-gray-400" />
                <EyeSlashIcon v-else class="h-5 w-5 text-gray-400" />
              </button>
            </div>
            <p v-if="passwordMatchError" class="mt-2 text-sm text-red-600">
              Passwords do not match
            </p>
            <p v-else-if="form.password_confirmation && passwordsMatch" class="mt-2 text-sm text-green-600 flex items-center">
              <CheckCircleIcon class="h-4 w-4 mr-1" />
              Passwords match
            </p>
          </div>

          <!-- Password Tips -->
          <div class="rounded-md bg-blue-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <InformationCircleIcon class="h-5 w-5 text-blue-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Password Tips</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <ul class="list-disc pl-5 space-y-1">
                    <li>Use a passphrase like "correct horse battery staple"</li>
                    <li>Mix uppercase and lowercase letters</li>
                    <li>Include numbers and symbols for extra security</li>
                    <li>Avoid using personal information</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>

      <!-- Submit Button -->
      <div v-if="form.current_password.length >= 6">
        <button
          type="submit"
          :disabled="!canSubmit || processing"
          class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
        >
          <span v-if="!processing">Update Password</span>
          <span v-else class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Updating...
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import axios from 'axios'
import { EyeIcon, EyeSlashIcon, CheckCircleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import { useNotification } from '@/composables/useNotification'

const { showSuccess, showError } = useNotification()

// Form state
const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})

const errors = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})

// UI state
const processing = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

// Password strength calculation
const passwordStrength = computed(() => {
  if (!form.password) return 0
  
  let strength = 0
  const password = form.password
  
  // Length
  if (password.length >= 8) strength += 20
  if (password.length >= 12) strength += 20
  
  // Character variety
  if (/[a-z]/.test(password)) strength += 15
  if (/[A-Z]/.test(password)) strength += 15
  if (/[0-9]/.test(password)) strength += 15
  if (/[^a-zA-Z0-9]/.test(password)) strength += 15
  
  // No common patterns
  if (!/(.)\1{2,}/.test(password)) strength += 10 // No repeated characters
  if (!/12345|password|qwerty/i.test(password)) strength += 10 // No common patterns
  
  return Math.min(100, strength)
})

const passwordStrengthText = computed(() => {
  if (passwordStrength.value < 30) return 'Weak'
  if (passwordStrength.value < 60) return 'Fair'
  if (passwordStrength.value < 80) return 'Good'
  return 'Strong'
})

const passwordStrengthClass = computed(() => {
  if (passwordStrength.value < 30) return 'bg-red-500'
  if (passwordStrength.value < 60) return 'bg-yellow-500'
  if (passwordStrength.value < 80) return 'bg-blue-500'
  return 'bg-green-500'
})

const passwordStrengthTextClass = computed(() => {
  if (passwordStrength.value < 30) return 'text-red-600'
  if (passwordStrength.value < 60) return 'text-yellow-600'
  if (passwordStrength.value < 80) return 'text-blue-600'
  return 'text-green-600'
})

const passwordSuggestions = computed(() => {
  const suggestions = []
  if (!form.password) return suggestions
  
  if (form.password.length < 8) {
    suggestions.push('Use at least 8 characters')
  }
  if (!/[A-Z]/.test(form.password)) {
    suggestions.push('Add uppercase letters')
  }
  if (!/[a-z]/.test(form.password)) {
    suggestions.push('Add lowercase letters')
  }
  if (!/[0-9]/.test(form.password)) {
    suggestions.push('Add numbers')
  }
  if (!/[^a-zA-Z0-9]/.test(form.password)) {
    suggestions.push('Add special characters (!@#$%^&*)')
  }
  
  return suggestions
})

const passwordsMatch = computed(() => {
  return form.password && form.password_confirmation && form.password === form.password_confirmation
})

const passwordMatchError = computed(() => {
  return form.password_confirmation && form.password !== form.password_confirmation
})

const canSubmit = computed(() => {
  return form.current_password &&
         form.password &&
         form.password.length >= 8 &&
         passwordsMatch.value &&
         passwordStrength.value >= 30
})

// Methods
const clearError = (field) => {
  errors[field] = ''
}

const validatePassword = () => {
  clearError('password')
  if (form.password && form.password.length < 8) {
    errors.password = 'Password must be at least 8 characters'
  }
}

const validatePasswordMatch = () => {
  clearError('password_confirmation')
}

const handleSubmit = async () => {
  if (!canSubmit.value || processing.value) return
  
  processing.value = true
  
  // Clear all errors
  Object.keys(errors).forEach(key => errors[key] = '')
  
  try {
    await axios.put('/api/profile/password', {
      current_password: form.current_password,
      password: form.password,
      password_confirmation: form.password_confirmation
    })
    
    showSuccess('Password Updated', 'Your password has been successfully changed', 5000)
    
    // Clear form
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''
    
    // Reset visibility states
    showCurrentPassword.value = false
    showNewPassword.value = false
    showConfirmPassword.value = false
    
  } catch (error) {
    if (error.response?.status === 422) {
      const responseErrors = error.response.data.errors || {}
      Object.keys(responseErrors).forEach(key => {
        if (errors.hasOwnProperty(key)) {
          errors[key] = responseErrors[key][0]
        }
      })
    } else if (error.response?.status === 401) {
      errors.current_password = 'Current password is incorrect'
    } else {
      showError('Update Failed', error.response?.data?.message || 'Failed to update password')
    }
  } finally {
    processing.value = false
  }
}
</script>