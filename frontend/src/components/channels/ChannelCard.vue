<template>
  <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow overflow-hidden">
    <!-- Banner -->
    <div class="h-32 bg-gradient-to-r from-blue-600 to-purple-600 relative">
      <img 
        v-if="channel.banner_url" 
        :src="channel.banner_url" 
        :alt="`${channel.name} banner`"
        class="absolute inset-0 w-full h-full object-cover"
      >
    </div>
    
    <!-- Content -->
    <div class="p-4">
      <div class="flex items-start space-x-3">
        <!-- Avatar -->
        <img
          :src="channel.avatar_url || defaultAvatar(channel.name)"
          :alt="channel.name"
          class="h-12 w-12 rounded-full"
        >
        
        <!-- Info -->
        <div class="flex-1 min-w-0">
          <h3 class="text-lg font-semibold text-gray-900 truncate">
            <router-link :to="`/@${channel.slug}`" class="hover:text-blue-600">
              {{ channel.name }}
            </router-link>
          </h3>
          <p class="text-sm text-gray-500">@{{ channel.slug }}</p>
        </div>
      </div>
      
      <p v-if="channel.description" class="mt-3 text-sm text-gray-600 line-clamp-2">
        {{ channel.description }}
      </p>
      
      <!-- Stats -->
      <div class="mt-4 flex items-center justify-between text-sm text-gray-500">
        <div class="flex items-center space-x-4">
          <span>{{ channel.lists_count || 0 }} lists</span>
          <span>{{ channel.followers_count || 0 }} followers</span>
        </div>
        <span v-if="!channel.is_public" class="text-xs bg-gray-100 px-2 py-1 rounded">
          Private
        </span>
      </div>
      
      <!-- Actions -->
      <div class="mt-4 flex items-center space-x-2">
        <router-link
          :to="`/@${channel.slug}`"
          class="flex-1 text-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          View
        </router-link>
        <router-link
          v-if="showEdit"
          :to="`/channels/${channel.id}/edit`"
          class="flex-1 text-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          Edit
        </router-link>
        <button
          v-if="showEdit"
          @click="deleteChannel"
          class="px-3 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50"
        >
          Delete
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'
import axios from 'axios'

const props = defineProps({
  channel: {
    type: Object,
    required: true
  },
  showEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['deleted'])

const defaultAvatar = (name) => {
  const initials = name
    .split(' ')
    .map(word => word[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
  
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
    <rect width="100" height="100" fill="#6366f1"/>
    <text x="50" y="50" font-family="Arial, sans-serif" font-size="40" fill="white" text-anchor="middle" dominant-baseline="central">${initials}</text>
  </svg>`
  
  return `data:image/svg+xml;base64,${btoa(svg)}`
}

const deleteChannel = async () => {
  if (!confirm(`Are you sure you want to delete "${props.channel.name}"?`)) {
    return
  }
  
  try {
    await axios.delete(`/api/channels/${props.channel.slug}`)
    emit('deleted', props.channel.id)
  } catch (error) {
    console.error('Error deleting channel:', error)
    alert('Failed to delete channel')
  }
}
</script>