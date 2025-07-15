<template>
  <div class="relative overflow-hidden" :class="containerClass">
    <!-- Blur placeholder -->
    <div 
      v-if="!isLoaded && placeholder"
      class="absolute inset-0 bg-gray-200"
    >
      <img 
        :src="placeholder"
        :alt="alt"
        class="w-full h-full object-cover filter blur-sm scale-110"
      />
    </div>
    
    <!-- Loading skeleton -->
    <div 
      v-else-if="!isLoaded && !placeholder"
      class="absolute inset-0 bg-gray-200 animate-pulse"
    />
    
    <!-- Main image -->
    <img
      ref="imageRef"
      :src="src"
      :alt="alt"
      :class="[imageClass, { 'opacity-0': !isLoaded }]"
      @load="onImageLoad"
      @error="onImageError"
      class="transition-opacity duration-300"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  placeholder: {
    type: String,
    default: null
  },
  alt: {
    type: String,
    default: ''
  },
  containerClass: {
    type: String,
    default: ''
  },
  imageClass: {
    type: String,
    default: 'w-full h-full object-cover'
  },
  lazy: {
    type: Boolean,
    default: true
  }
})

const imageRef = ref(null)
const isLoaded = ref(false)
const hasError = ref(false)
let observer = null

const onImageLoad = () => {
  isLoaded.value = true
  hasError.value = false
}

const onImageError = () => {
  hasError.value = true
  isLoaded.value = true
}

const loadImage = () => {
  if (!props.lazy) return
  
  // Create intersection observer for lazy loading
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          // Start loading the image
          const img = new Image()
          img.src = props.src
          observer.disconnect()
        }
      })
    },
    {
      rootMargin: '50px'
    }
  )
  
  if (imageRef.value) {
    observer.observe(imageRef.value)
  }
}

// Watch for src changes
watch(() => props.src, () => {
  isLoaded.value = false
  hasError.value = false
})

onMounted(() => {
  if (props.lazy) {
    loadImage()
  }
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})
</script>