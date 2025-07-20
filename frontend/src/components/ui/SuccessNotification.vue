<template>
  <!-- Global notification live region, render this permanently at the end of the document -->
  <div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 z-50">
    <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
      <!-- Notification panel, dynamically insert this into the live region when it needs to be displayed -->
      <transition 
        enter-active-class="transform ease-out duration-300 transition" 
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2" 
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0" 
        leave-active-class="transition ease-in duration-100" 
        leave-from-class="opacity-100" 
        leave-to-class="opacity-0"
      >
        <div v-if="visible" class="pointer-events-auto w-full max-w-sm rounded-lg bg-white shadow-lg ring-1 ring-black/5">
          <div class="p-4">
            <div class="flex items-start">
              <div class="shrink-0">
                <CheckCircleIcon class="h-6 w-6 text-green-400" aria-hidden="true" />
              </div>
              <div class="ml-3 w-0 flex-1 pt-0.5">
                <p class="text-sm font-medium text-gray-900">{{ title }}</p>
                <p v-if="message" class="mt-1 text-sm text-gray-500">{{ message }}</p>
              </div>
              <div class="ml-4 flex shrink-0">
                <button 
                  type="button" 
                  @click="close" 
                  class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:outline-none"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { CheckCircleIcon } from '@heroicons/vue/24/outline'
import { XMarkIcon } from '@heroicons/vue/20/solid'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Saved'
  },
  message: {
    type: String,
    default: ''
  },
  duration: {
    type: Number,
    default: 3000 // 3 seconds
  }
})

const emit = defineEmits(['close'])

const visible = ref(false)
let timeout = null

const close = () => {
  visible.value = false
  if (timeout) {
    clearTimeout(timeout)
    timeout = null
  }
  // Wait for animation to complete before emitting
  setTimeout(() => {
    emit('close')
  }, 100)
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    visible.value = true
    // Auto close after duration
    if (props.duration > 0) {
      timeout = setTimeout(() => {
        close()
      }, props.duration)
    }
  } else {
    visible.value = false
    if (timeout) {
      clearTimeout(timeout)
      timeout = null
    }
  }
}, { immediate: true })
</script>