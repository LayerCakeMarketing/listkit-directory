<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center"
        @click="close"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-90" />
        
        <!-- Close button -->
        <button
          class="absolute top-4 right-4 z-10 p-2 text-white hover:bg-white hover:bg-opacity-10 rounded-full transition-colors"
          @click="close"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        
        <!-- Navigation buttons -->
        <button
          v-if="images.length > 1 && currentIndex > 0"
          class="absolute left-4 z-10 p-2 text-white hover:bg-white hover:bg-opacity-10 rounded-full transition-colors"
          @click.stop="previous"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        
        <button
          v-if="images.length > 1 && currentIndex < images.length - 1"
          class="absolute right-4 z-10 p-2 text-white hover:bg-white hover:bg-opacity-10 rounded-full transition-colors"
          @click.stop="next"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        
        <!-- Image container -->
        <div class="relative z-10 max-w-7xl max-h-[90vh] mx-4" @click.stop>
          <img
            v-if="currentImage"
            :src="currentImage.url"
            :alt="currentImage.alt || `Image ${currentIndex + 1}`"
            class="max-w-full max-h-[90vh] object-contain"
            @load="onImageLoad"
          />
          
          <!-- Loading spinner -->
          <div
            v-if="loading"
            class="absolute inset-0 flex items-center justify-center"
          >
            <svg class="animate-spin h-10 w-10 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
          </div>
        </div>
        
        <!-- Image counter -->
        <div
          v-if="images.length > 1"
          class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white text-sm bg-black bg-opacity-50 px-3 py-1 rounded-full"
        >
          {{ currentIndex + 1 }} / {{ images.length }}
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  images: {
    type: Array,
    default: () => []
  },
  initialIndex: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['update:modelValue'])

// State
const currentIndex = ref(props.initialIndex)
const loading = ref(true)

// Computed
const currentImage = computed(() => {
  return props.images[currentIndex.value] || null
})

// Methods
const close = () => {
  emit('update:modelValue', false)
}

const previous = () => {
  if (currentIndex.value > 0) {
    currentIndex.value--
    loading.value = true
  }
}

const next = () => {
  if (currentIndex.value < props.images.length - 1) {
    currentIndex.value++
    loading.value = true
  }
}

const onImageLoad = () => {
  loading.value = false
}

// Keyboard navigation
const handleKeydown = (e) => {
  if (!props.modelValue) return
  
  switch (e.key) {
    case 'Escape':
      close()
      break
    case 'ArrowLeft':
      previous()
      break
    case 'ArrowRight':
      next()
      break
  }
}

// Touch/swipe support
let touchStartX = 0
let touchEndX = 0

const handleTouchStart = (e) => {
  touchStartX = e.changedTouches[0].screenX
}

const handleTouchEnd = (e) => {
  touchEndX = e.changedTouches[0].screenX
  handleSwipe()
}

const handleSwipe = () => {
  const swipeThreshold = 50
  const diff = touchStartX - touchEndX
  
  if (Math.abs(diff) > swipeThreshold) {
    if (diff > 0) {
      // Swiped left
      next()
    } else {
      // Swiped right
      previous()
    }
  }
}

// Watch for prop changes
watch(() => props.initialIndex, (newVal) => {
  currentIndex.value = newVal
})

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    // Prevent body scroll when lightbox is open
    document.body.style.overflow = 'hidden'
  } else {
    // Restore body scroll
    document.body.style.overflow = ''
  }
})

// Lifecycle
onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
  window.addEventListener('touchstart', handleTouchStart)
  window.addEventListener('touchend', handleTouchEnd)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  window.removeEventListener('touchstart', handleTouchStart)
  window.removeEventListener('touchend', handleTouchEnd)
  // Ensure body scroll is restored
  document.body.style.overflow = ''
})
</script>