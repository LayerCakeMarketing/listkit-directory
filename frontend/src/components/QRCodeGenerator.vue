<template>
  <div class="qr-code-generator">
    <!-- Trigger Button -->
    <button
      v-if="!showModal"
      @click="openModal"
      :class="buttonClass"
      type="button"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
      </svg>
      <span v-if="showButtonText" class="ml-2">{{ buttonText }}</span>
    </button>

    <!-- Modal -->
    <div
      v-if="showModal"
      @click.self="closeModal"
      class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-auto">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">{{ title }} QR Code</h3>
            <button
              @click="closeModal"
              class="text-gray-400 hover:text-gray-500"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- QR Code Canvas Container -->
          <div class="bg-gray-50 rounded-lg p-6 mb-4 flex justify-center">
            <div v-if="loading" class="py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
            </div>
            <canvas 
              v-show="!loading"
              ref="qrCanvas" 
              class="max-w-full"
            ></canvas>
          </div>

          <!-- Info -->
          <div class="bg-blue-50 rounded-lg p-4 mb-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">{{ title }} QR Code</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <p>This QR code links to:</p>
                  <p class="font-mono text-xs mt-1 break-all">{{ url }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Options -->
          <div class="space-y-3 mb-4">
            <!-- Size selector -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Size</label>
              <select
                v-model="selectedSize"
                @change="regenerateQRCode"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              >
                <option :value="128">Small (128x128)</option>
                <option :value="256">Medium (256x256)</option>
                <option :value="512">Large (512x512)</option>
                <option :value="1024">Extra Large (1024x1024)</option>
              </select>
            </div>

            <!-- Logo option (future enhancement) -->
            <label class="flex items-center">
              <input
                v-model="includeLogo"
                type="checkbox"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                disabled
              />
              <span class="ml-2 text-sm text-gray-500">Include logo (coming soon)</span>
            </label>
          </div>

          <!-- Actions -->
          <div class="flex space-x-3">
            <button
              @click="downloadQRCode"
              class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition-colors inline-flex items-center justify-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Download PNG
            </button>
            <button
              @click="copyToClipboard"
              class="flex-1 bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 transition-colors inline-flex items-center justify-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              {{ copyButtonText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import QRCode from 'qrcode'
import { useNotification } from '@/composables/useNotification'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['place', 'list', 'region'].includes(value)
  },
  data: {
    type: Object,
    required: true
  },
  buttonClass: {
    type: String,
    default: 'inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
  },
  buttonText: {
    type: String,
    default: 'QR Code'
  },
  showButtonText: {
    type: Boolean,
    default: true
  }
})

const { showSuccess, showError } = useNotification()

// State
const showModal = ref(false)
const loading = ref(false)
const qrCanvas = ref(null)
const selectedSize = ref(256)
const includeLogo = ref(false)
const copyButtonText = ref('Copy URL')

// Computed
const title = computed(() => {
  switch (props.type) {
    case 'place':
      return props.data.name || props.data.title || 'Place'
    case 'list':
      return props.data.name || 'List'
    case 'region':
      return props.data.name || 'Region'
    default:
      return 'Item'
  }
})

const url = computed(() => {
  const baseUrl = window.location.origin
  
  switch (props.type) {
    case 'place':
      // Use canonical URL if available
      if (props.data.canonical_url) {
        return `${baseUrl}${props.data.canonical_url}`
      }
      // Build URL from components
      if (props.data.state_region && props.data.city_region && props.data.category) {
        const state = props.data.state_region.slug
        const city = props.data.city_region.slug
        const category = props.data.category.slug
        const placeSlug = `${props.data.slug}-${props.data.id}`
        return `${baseUrl}/places/${state}/${city}/${category}/${placeSlug}`
      }
      // Fallback
      return `${baseUrl}/places/${props.data.id}`
      
    case 'list':
      // Check for owner-based URL
      if (props.data.owner_custom_url || props.data.owner_username) {
        const ownerSlug = props.data.owner_custom_url || props.data.owner_username
        return `${baseUrl}/${ownerSlug}/${props.data.slug}`
      }
      // Fallback to simple list URL
      return `${baseUrl}/lists/${props.data.slug || props.data.id}`
      
    case 'region':
      // Build hierarchical URL if possible
      if (props.data.full_path) {
        return `${baseUrl}/regions/${props.data.full_path}`
      }
      return `${baseUrl}/regions/${props.data.slug || props.data.id}`
      
    default:
      return baseUrl
  }
})

const filename = computed(() => {
  const name = title.value.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')
  return `${props.type}-${name}-qrcode.png`
})

// Methods
const openModal = async () => {
  showModal.value = true
  loading.value = true
  
  await nextTick()
  await generateQRCode()
}

const closeModal = () => {
  showModal.value = false
  copyButtonText.value = 'Copy URL'
}

const generateQRCode = async () => {
  if (!qrCanvas.value) return
  
  try {
    loading.value = true
    
    const options = {
      width: selectedSize.value,
      margin: 2,
      color: {
        dark: '#000000',
        light: '#FFFFFF'
      },
      errorCorrectionLevel: 'M'
    }
    
    await QRCode.toCanvas(qrCanvas.value, url.value, options)
    
    loading.value = false
  } catch (error) {
    console.error('Error generating QR code:', error)
    showError('Failed to generate QR code')
    loading.value = false
  }
}

const regenerateQRCode = async () => {
  await generateQRCode()
}

const downloadQRCode = () => {
  if (!qrCanvas.value) return
  
  try {
    // Convert canvas to blob
    qrCanvas.value.toBlob((blob) => {
      // Create download link
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename.value
      
      // Trigger download
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      // Clean up
      URL.revokeObjectURL(url)
      
      showSuccess('QR code downloaded successfully!')
    }, 'image/png')
  } catch (error) {
    console.error('Error downloading QR code:', error)
    showError('Failed to download QR code')
  }
}

const copyToClipboard = async () => {
  try {
    await navigator.clipboard.writeText(url.value)
    copyButtonText.value = 'Copied!'
    showSuccess('URL copied to clipboard!')
    
    // Reset button text after 2 seconds
    setTimeout(() => {
      copyButtonText.value = 'Copy URL'
    }, 2000)
  } catch (error) {
    console.error('Error copying to clipboard:', error)
    showError('Failed to copy URL')
  }
}
</script>

<style scoped>
.qr-code-generator {
  display: inline-block;
}

/* Ensure QR code is crisp on high DPI displays */
canvas {
  image-rendering: pixelated;
  image-rendering: -moz-crisp-edges;
  image-rendering: crisp-edges;
}
</style>