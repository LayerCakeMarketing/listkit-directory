<template>
  <div class="bg-white rounded-lg shadow-sm">
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          <component
            :is="tab.icon"
            :class="[
              activeTab === tab.id ? 'text-blue-500' : 'text-gray-400 group-hover:text-gray-500',
              '-ml-0.5 mr-2 h-5 w-5'
            ]"
          />
          {{ tab.name }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="p-6">
      <!-- Facts Tab -->
      <div v-if="activeTab === 'facts'" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ regionType }} Facts</h3>
        
        <div v-if="region.facts && Object.keys(region.facts).length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="(value, key) in region.facts" :key="key" class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-500 capitalize mb-1">{{ formatFactKey(key) }}</div>
            <div class="text-gray-900">{{ value }}</div>
          </div>
        </div>
        <div v-else class="text-gray-500 text-center py-8">
          No facts available for this {{ regionType.toLowerCase() }}.
        </div>
      </div>

      <!-- State Symbols Tab (only for states) -->
      <div v-if="activeTab === 'symbols' && region.type === 'state'" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">State Symbols</h3>
        
        <div v-if="region.state_symbols && hasStateSymbols" class="space-y-4">
          <!-- Basic Symbols -->
          <div v-if="region.state_symbols.bird || region.state_symbols.flower || region.state_symbols.tree" 
               class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-if="region.state_symbols.bird" class="bg-gray-50 rounded-lg p-4">
              <div class="text-sm text-gray-500 mb-1">State Bird</div>
              <div class="text-gray-900 font-medium">{{ formatSymbolValue(region.state_symbols.bird) }}</div>
            </div>
            <div v-if="region.state_symbols.flower" class="bg-gray-50 rounded-lg p-4">
              <div class="text-sm text-gray-500 mb-1">State Flower</div>
              <div class="text-gray-900 font-medium">{{ formatSymbolValue(region.state_symbols.flower) }}</div>
            </div>
            <div v-if="region.state_symbols.tree" class="bg-gray-50 rounded-lg p-4">
              <div class="text-sm text-gray-500 mb-1">State Tree</div>
              <div class="text-gray-900 font-medium">{{ formatSymbolValue(region.state_symbols.tree) }}</div>
            </div>
          </div>

          <!-- Other Symbols -->
          <div v-if="region.state_symbols.mammal || region.state_symbols.fish || region.state_symbols.song" 
               class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div v-if="region.state_symbols.mammal" class="bg-gray-50 rounded-lg p-4">
              <div class="text-sm text-gray-500 mb-1">State Mammal</div>
              <div class="text-gray-900 font-medium">{{ formatSymbolValue(region.state_symbols.mammal) }}</div>
            </div>
            <div v-if="region.state_symbols.fish" class="bg-gray-50 rounded-lg p-4">
              <div class="text-sm text-gray-500 mb-1">State Fish</div>
              <div class="text-gray-900 font-medium">{{ formatSymbolValue(region.state_symbols.fish) }}</div>
            </div>
            <div v-if="region.state_symbols.song" class="bg-gray-50 rounded-lg p-4">
              <div class="text-sm text-gray-500 mb-1">State Song</div>
              <div class="text-gray-900 font-medium">{{ formatSymbolValue(region.state_symbols.song) }}</div>
            </div>
          </div>
        </div>
        <div v-else class="text-gray-500 text-center py-8">
          No state symbols available.
        </div>
      </div>

      <!-- Geodata Tab -->
      <div v-if="activeTab === 'geodata'" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Geographic Data</h3>
        
        <div v-if="hasGeodata" class="space-y-4">
          <div v-if="region.area_sq_km" class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-500 mb-1">Area</div>
            <div class="text-gray-900">{{ formatArea(region.area_sq_km) }}</div>
          </div>
          
          <div v-if="region.boundary || region.geojson" class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-500 mb-1">Boundary Data</div>
            <div class="text-gray-900">Available ({{ region.geojson ? 'GeoJSON' : 'Polygon' }} format)</div>
          </div>
          
          <div v-if="region.center_point" class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-500 mb-1">Center Point</div>
            <div class="text-gray-900">{{ formatCenterPoint(region.center_point) }}</div>
          </div>
        </div>
        <div v-else class="text-gray-500 text-center py-8">
          No geographic data available for this {{ regionType.toLowerCase() }}.
        </div>
      </div>

      <!-- Featured Places Tab -->
      <div v-if="activeTab === 'places'" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Featured Places</h3>
        
        <div v-if="region.featured_entries && region.featured_entries.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="place in region.featured_entries" :key="place.id" 
               class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <h4 class="font-medium text-gray-900">{{ place.title }}</h4>
            <p v-if="place.category" class="text-sm text-gray-500">{{ place.category.name }}</p>
            <p v-if="place.pivot && place.pivot.tagline" class="text-sm text-gray-600 mt-1">{{ place.pivot.tagline }}</p>
          </div>
        </div>
        <div v-else class="text-gray-500 text-center py-8">
          No featured places for this {{ regionType.toLowerCase() }}.
        </div>
      </div>

      <!-- Featured Lists Tab -->
      <div v-if="activeTab === 'lists'" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Featured Lists</h3>
        
        <div v-if="region.featured_lists && region.featured_lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div v-for="list in region.featured_lists" :key="list.id" 
               class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <h4 class="font-medium text-gray-900">{{ list.name }}</h4>
            <p v-if="list.user" class="text-sm text-gray-500">by {{ list.user.name }}</p>
            <p v-if="list.description" class="text-sm text-gray-600 mt-1">{{ list.description }}</p>
          </div>
        </div>
        <div v-else class="text-gray-500 text-center py-8">
          No featured lists for this {{ regionType.toLowerCase() }}.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { 
  InformationCircleIcon,
  StarIcon,
  MapIcon,
  BuildingOfficeIcon,
  QueueListIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  region: {
    type: Object,
    required: true
  }
})

const activeTab = ref('facts')

const regionType = computed(() => {
  const typeMap = {
    'state': 'State',
    'city': 'City',
    'neighborhood': 'Neighborhood'
  }
  return typeMap[props.region.type] || 'Region'
})

const tabs = computed(() => {
  const baseTabs = [
    { id: 'facts', name: 'Facts', icon: InformationCircleIcon },
    { id: 'geodata', name: 'Geographic Data', icon: MapIcon },
    { id: 'places', name: 'Featured Places', icon: BuildingOfficeIcon },
    { id: 'lists', name: 'Featured Lists', icon: QueueListIcon }
  ]
  
  // Add state symbols tab only for states
  if (props.region.type === 'state') {
    baseTabs.splice(1, 0, { id: 'symbols', name: 'State Symbols', icon: StarIcon })
  }
  
  return baseTabs
})

const hasStateSymbols = computed(() => {
  if (!props.region.state_symbols) return false
  return Object.values(props.region.state_symbols).some(value => {
    if (typeof value === 'string') return value.length > 0
    if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0
    return false
  })
})

const hasGeodata = computed(() => {
  return props.region.area_sq_km || props.region.boundary || props.region.geojson || props.region.center_point
})

const formatFactKey = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatSymbolValue = (value) => {
  if (typeof value === 'string') return value
  if (Array.isArray(value) && value.length > 0) return value[0]
  if (typeof value === 'object' && value.name) return value.name
  return 'Not specified'
}

const formatArea = (areaSqKm) => {
  const sqMiles = areaSqKm * 0.386102
  return `${areaSqKm.toLocaleString()} km² (${sqMiles.toLocaleString()} mi²)`
}

const formatCenterPoint = (point) => {
  if (typeof point === 'string') {
    // Parse PostgreSQL point format: (lat,lng)
    const match = point.match(/\(([-\d.]+),([-\d.]+)\)/)
    if (match) {
      return `${match[1]}°, ${match[2]}°`
    }
  }
  return point
}
</script>