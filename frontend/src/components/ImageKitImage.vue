<template>
  <div ref="container" :class="containerClass" class="relative">
    <!-- Placeholder/Loading state -->
    <div
      v-if="!loaded && !error"
      class="absolute inset-0 bg-gray-200 animate-pulse rounded"
    />
    
    <!-- Error state -->
    <div
      v-if="error"
      class="absolute inset-0 bg-gray-200 flex items-center justify-center rounded"
    >
      <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </div>
    
    <!-- Actual image -->
    <img
      v-show="loaded"
      ref="image"
      :src="currentSrc"
      :alt="alt"
      :class="imageClass"
      @load="onLoad"
      @error="onError"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: ''
  },
  transformation: {
    type: String,
    default: 'medium' // thumbnail, small, medium, large, gallery_thumb
  },
  customTransformation: {
    type: Object,
    default: null
  },
  lazy: {
    type: Boolean,
    default: true
  },
  containerClass: {
    type: String,
    default: ''
  },
  imageClass: {
    type: String,
    default: ''
  },
  threshold: {
    type: Number,
    default: 0.1
  }
})

// Refs
const container = ref(null)
const image = ref(null)
const loaded = ref(false)
const error = ref(false)
const isIntersecting = ref(false)

// Common transformations
const transformations = {
  thumbnail: 'tr:w-150,h-150,c-at_max,q-80',
  small: 'tr:w-320,h-320,c-at_max,q-85',
  medium: 'tr:w-640,h-640,c-at_max,q-85',
  large: 'tr:w-1024,h-1024,c-at_max,q-90',
  gallery_thumb: 'tr:w-300,h-300,c-maintain_ratio,q-80'
}

// Computed
const transformedSrc = computed(() => {
  if (!props.src) return ''
  
  // If custom transformation is provided
  if (props.customTransformation) {
    const transforms = []
    if (props.customTransformation.width) transforms.push(`w-${props.customTransformation.width}`)
    if (props.customTransformation.height) transforms.push(`h-${props.customTransformation.height}`)
    if (props.customTransformation.crop) transforms.push(`c-${props.customTransformation.crop}`)
    if (props.customTransformation.quality) transforms.push(`q-${props.customTransformation.quality}`)
    if (props.customTransformation.format) transforms.push(`f-${props.customTransformation.format}`)
    
    if (transforms.length > 0) {
      const transformString = `tr:${transforms.join(',')}`
      return insertTransformation(props.src, transformString)
    }
  }
  
  // Use predefined transformation
  if (transformations[props.transformation]) {
    return insertTransformation(props.src, transformations[props.transformation])
  }
  
  return props.src
})

const currentSrc = computed(() => {
  if (!props.lazy || isIntersecting.value) {
    return transformedSrc.value
  }
  return ''
})

// Methods
const insertTransformation = (url, transformation) => {
  // Check if URL already has transformations
  if (url.includes('/tr:')) {
    return url
  }
  
  // Insert transformation after the base URL
  const parts = url.split('/')
  const filename = parts.pop()
  parts.push(transformation)
  parts.push(filename)
  
  return parts.join('/')
}

const onLoad = () => {
  loaded.value = true
  error.value = false
}

const onError = () => {
  loaded.value = false
  error.value = true
}

// Intersection Observer for lazy loading
let observer = null

const setupIntersectionObserver = () => {
  if (!props.lazy || !container.value) {
    isIntersecting.value = true
    return
  }
  
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          isIntersecting.value = true
          // Once loaded, we can disconnect the observer
          if (observer) {
            observer.disconnect()
          }
        }
      })
    },
    {
      threshold: props.threshold,
      rootMargin: '50px'
    }
  )
  
  observer.observe(container.value)
}

// Lifecycle
onMounted(() => {
  setupIntersectionObserver()
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})

// Watch for src changes
watch(() => props.src, () => {
  loaded.value = false
  error.value = false
  if (!props.lazy) {
    isIntersecting.value = true
  }
})
</script>