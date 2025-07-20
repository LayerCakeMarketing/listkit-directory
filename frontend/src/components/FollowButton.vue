<template>
    <button
        @click="toggleFollow"
        :disabled="loading"
        :class="[
            'inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-colors',
            isFollowing 
                ? 'bg-gray-200 text-gray-700 hover:bg-gray-300' 
                : 'bg-blue-600 text-white hover:bg-blue-700',
            loading ? 'opacity-50 cursor-not-allowed' : ''
        ]"
    >
        <span v-if="loading" class="mr-2">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </span>
        {{ buttonText }}
    </button>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'

const props = defineProps({
    followableType: {
        type: String,
        required: true,
        validator: (value) => ['user', 'place', 'channel'].includes(value)
    },
    followableId: {
        type: [Number, String],
        required: true
    },
    initialFollowing: {
        type: Boolean,
        default: false
    },
    buttonClass: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['follow', 'unfollow'])

const { showSuccess, showError } = useNotification()
const isFollowing = ref(props.initialFollowing)
const loading = ref(false)

const buttonText = computed(() => {
    if (loading.value) {
        return isFollowing.value ? 'Unfollowing...' : 'Following...'
    }
    return isFollowing.value ? 'Following' : 'Follow'
})

const toggleFollow = async () => {
    loading.value = true
    
    try {
        let response
        let endpoint
        
        switch (props.followableType) {
            case 'user':
                endpoint = `/api/users/${props.followableId}/follow`
                break
            case 'channel':
                endpoint = `/api/channels/${props.followableId}/follow`
                break
            case 'place':
                endpoint = `/api/places/${props.followableId}/follow`
                break
        }
        
        if (isFollowing.value) {
            response = await axios.delete(endpoint)
            isFollowing.value = false
            emit('unfollow', response.data)
        } else {
            response = await axios.post(endpoint)
            isFollowing.value = true
            emit('follow', response.data)
        }
    } catch (error) {
        console.error('Error toggling follow:', error)
        
        // Show error message
        if (error.response?.status === 401) {
            showError('Authentication Required', 'Please login to follow')
        } else {
            showError('Error', error.response?.data?.message || 'An error occurred')
        }
    } finally {
        loading.value = false
    }
}

// Check initial following status
onMounted(async () => {
    if (!props.initialFollowing) {
        try {
            const response = await axios.get('/api/following/check', {
                params: {
                    type: props.followableType,
                    id: props.followableId
                }
            })
            isFollowing.value = response.data.is_following
        } catch (error) {
            console.error('Error checking follow status:', error)
        }
    }
})
</script>