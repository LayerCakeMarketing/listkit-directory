<template>
  <div 
    :class="[
      'bg-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 overflow-hidden group cursor-pointer',
      { 'ring-2 ring-blue-500': isSelected },
      { 'scale-105 shadow-xl': isHovered },
      size === 'compact' ? 'h-32' : 'h-auto'
    ]"
    @click="$emit('click', place)"
    @mouseenter="$emit('hover', place)"
    @mouseleave="$emit('hover', null)"
    :tabindex="0"
    @keydown.enter="$emit('click', place)"
    @keydown.space.prevent="$emit('click', place)"
    role="button"
    :aria-label="`View details for ${place.name || place.title}`"
  >
    <!-- Image Section -->
    <div 
      v-if="showImage && imageUrl"
      :class="[
        'relative overflow-hidden',
        size === 'compact' ? 'h-20' : 'h-48'
      ]"
    >
      <img 
        :src="imageUrl" 
        :alt="place.name || place.title"
        :class="[
          'w-full object-cover transition-transform duration-300 group-hover:scale-105',
          size === 'compact' ? 'h-20' : 'h-48'
        ]"
        loading="lazy"
        @error="handleImageError"
      />
      
      <!-- Badges Overlay -->
      <div class="absolute top-2 left-2 flex flex-wrap gap-1">
        <span 
          v-if="place.is_featured" 
          class="bg-green-500 text-white text-xs font-medium px-2 py-1 rounded-full"
        >
          Featured
        </span>
        <span 
          v-if="place.is_verified" 
          class="bg-blue-500 text-white text-xs font-medium px-2 py-1 rounded-full"
        >
          Verified
        </span>
        <span 
          v-if="place.is_claimed" 
          class="bg-purple-500 text-white text-xs font-medium px-2 py-1 rounded-full"
        >
          Claimed
        </span>
      </div>

      <!-- Distance Badge (if provided) -->
      <div 
        v-if="distance"
        class="absolute top-2 right-2 bg-black bg-opacity-75 text-white text-xs font-medium px-2 py-1 rounded-full"
      >
        {{ formatDistance(distance) }}
      </div>
    </div>

    <!-- Placeholder for no image -->
    <div
      v-else-if="showImage"
      :class="[
        'bg-gray-200 flex items-center justify-center',
        size === 'compact' ? 'h-20' : 'h-48'
      ]"
    >
      <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
      </svg>
    </div>

    <!-- Content Section -->
    <div :class="['p-4', size === 'compact' ? 'py-2' : '']">
      <!-- Header -->
      <div class="flex items-start justify-between mb-2">
        <div class="flex-1 min-w-0">
          <h3 :class="[
            'font-semibold text-gray-900 group-hover:text-blue-600 transition-colors truncate',
            size === 'compact' ? 'text-sm' : 'text-lg'
          ]">
            {{ place.name || place.title }}
          </h3>
          
          <p v-if="place.category" :class="[
            'text-gray-600 truncate',
            size === 'compact' ? 'text-xs' : 'text-sm'
          ]">
            {{ place.category.name }}
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center space-x-1 ml-2" @click.stop>
          <!-- Save Button -->
          <SaveButton
            v-if="showSaveButton && authStore.isAuthenticated"
            item-type="place"
            :item-id="place.id"
            :initial-saved="place.is_saved || false"
            @saved="$emit('save', { place, saved: true })"
            @unsaved="$emit('save', { place, saved: false })"
          />

          <button
            v-if="showMoreButton"
            @click.stop="$emit('more', place)"
            class="p-1 rounded-full hover:bg-gray-100 transition-colors"
            aria-label="More options"
          >
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Rating -->
      <div 
        v-if="showRating && (place.average_rating > 0 || place.review_count > 0)" 
        class="flex items-center mb-2"
      >
        <div class="flex items-center">
          <div class="flex text-yellow-400">
            <svg 
              v-for="i in 5" 
              :key="i"
              :class="[
                'w-4 h-4',
                i <= Math.floor(place.average_rating || 0) ? 'fill-current' : 'text-gray-300'
              ]" 
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
          </div>
          <span :class="[
            'ml-1 text-gray-600',
            size === 'compact' ? 'text-xs' : 'text-sm'
          ]">
            {{ place.average_rating?.toFixed(1) || '0.0' }}
            <span v-if="place.review_count" class="text-gray-500">
              ({{ place.review_count }})
            </span>
          </span>
        </div>
      </div>

      <!-- Description (only for non-compact) -->
      <p 
        v-if="size !== 'compact' && place.description && showDescription"
        class="text-gray-700 text-sm mb-3 line-clamp-2"
      >
        {{ place.description }}
      </p>

      <!-- Location Info -->
      <div 
        v-if="showLocation && (place.location || place.city || place.state)"
        class="flex items-center text-gray-500 mb-2"
      >
        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span :class="[
          'truncate',
          size === 'compact' ? 'text-xs' : 'text-sm'
        ]">
          {{ formatLocation(place) }}
        </span>
      </div>

      <!-- Contact Info -->
      <div v-if="size !== 'compact' && showContact" class="flex items-center space-x-4 text-sm">
        <a
          v-if="place.phone"
          :href="`tel:${place.phone}`"
          class="flex items-center text-blue-600 hover:text-blue-800 transition-colors"
          @click.stop
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
          </svg>
          Call
        </a>

        <a
          v-if="place.website_url"
          :href="place.website_url"
          target="_blank"
          rel="noopener noreferrer"
          class="flex items-center text-blue-600 hover:text-blue-800 transition-colors"
          @click.stop
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          Website
        </a>
      </div>

      <!-- Business Hours (if available) -->
      <div 
        v-if="size !== 'compact' && showHours && currentHours"
        class="mt-2 text-sm"
      >
        <span 
          :class="[
            'font-medium',
            isOpenNow ? 'text-green-600' : 'text-red-600'
          ]"
        >
          {{ isOpenNow ? 'Open' : 'Closed' }}
        </span>
        <span v-if="currentHours" class="text-gray-600 ml-1">
          • {{ currentHours }}
        </span>
      </div>

      <!-- Tags (compact view) -->
      <div 
        v-if="size === 'compact' && place.tags && place.tags.length > 0"
        class="flex flex-wrap gap-1 mt-1"
      >
        <span
          v-for="tag in place.tags.slice(0, 2)"
          :key="tag"
          class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full"
        >
          {{ tag }}
        </span>
        <span
          v-if="place.tags.length > 2"
          class="text-gray-500 text-xs"
        >
          +{{ place.tags.length - 2 }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import SaveButton from '@/components/SaveButton.vue'
import { useAuthStore } from '@/stores/auth'

// Props
const props = defineProps({
  place: {
    type: Object,
    required: true
  },
  size: {
    type: String,
    default: 'normal', // 'compact' | 'normal'
    validator: (value) => ['compact', 'normal'].includes(value)
  },
  isSelected: {
    type: Boolean,
    default: false
  },
  isHovered: {
    type: Boolean,
    default: false
  },
  distance: {
    type: Number,
    default: null
  },
  showImage: {
    type: Boolean,
    default: true
  },
  showRating: {
    type: Boolean,
    default: true
  },
  showDescription: {
    type: Boolean,
    default: true
  },
  showLocation: {
    type: Boolean,
    default: true
  },
  showContact: {
    type: Boolean,
    default: true
  },
  showHours: {
    type: Boolean,
    default: true
  },
  showSaveButton: {
    type: Boolean,
    default: true
  },
  showMoreButton: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['click', 'hover', 'save', 'more'])

// Auth store
const authStore = useAuthStore()

// State
const imageError = ref(false)

// Computed
const imageUrl = computed(() => {
  if (imageError.value) return null
  return props.place.cover_image_url || 
         props.place.logo_url || 
         props.place.featured_image ||
         null
})

const currentHours = computed(() => {
  // This would need to be implemented based on hours_of_operation data structure
  if (!props.place.hours_of_operation) return null
  
  const today = new Date().getDay()
  const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
  const todayHours = props.place.hours_of_operation[days[today]]
  
  if (!todayHours || todayHours.closed) return 'Closed today'
  
  return `${todayHours.open} - ${todayHours.close}`
})

const isOpenNow = computed(() => {
  if (!props.place.hours_of_operation) return null
  
  const now = new Date()
  const today = now.getDay()
  const currentTime = now.getHours() * 100 + now.getMinutes()
  
  const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
  const todayHours = props.place.hours_of_operation[days[today]]
  
  if (!todayHours || todayHours.closed) return false
  
  // Simple time comparison (would need more robust parsing for real implementation)
  const openTime = parseInt(todayHours.open.replace(':', ''))
  const closeTime = parseInt(todayHours.close.replace(':', ''))
  
  return currentTime >= openTime && currentTime <= closeTime
})

// Methods
const handleImageError = () => {
  imageError.value = true
}

const formatDistance = (distance) => {
  if (distance < 1) {
    return `${(distance * 5280).toFixed(0)} ft`
  } else {
    return `${distance.toFixed(1)} mi`
  }
}

const formatLocation = (place) => {
  const parts = []
  
  if (place.location?.city || place.city) {
    parts.push(place.location?.city || place.city)
  }
  
  if (place.location?.state || place.state) {
    parts.push(place.location?.state || place.state)
  } else if (place.stateRegion?.name) {
    parts.push(place.stateRegion.name)
  }
  
  if (place.location?.neighborhood || place.neighborhoodRegion?.name) {
    parts.unshift(place.location?.neighborhood || place.neighborhoodRegion.name)
  }
  
  return parts.join(', ') || 'Location not specified'
}

</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Focus styles for accessibility */
.group:focus {
  @apply outline-none ring-2 ring-blue-500 ring-offset-2;
}

/* Hover animations */
.group:hover .group-hover\:scale-105 {
  transform: scale(1.05);
}

.group:hover .group-hover\:text-blue-600 {
  @apply text-blue-600;
}

/* Badge positioning */
.absolute.top-2.left-2 {
  z-index: 10;
}

.absolute.top-2.right-2 {
  z-index: 10;
}
</style>