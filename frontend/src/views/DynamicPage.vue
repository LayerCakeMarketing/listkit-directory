<template>
  <div>
    <!-- Show channel profile if it's a channel -->
    <ChannelShow v-if="isChannel" :slug="slug" />
    <!-- Show marketing page if it's a marketing page -->
    <PublicPageShow v-else-if="isMarketingPage" :slug="slug" />
    <!-- Show 404 if neither -->
    <NotFound v-else />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import ChannelShow from '@/views/channels/Show.vue'
import PublicPageShow from '@/views/pages/Show.vue'
import NotFound from '@/views/error/404.vue'

const props = defineProps({
  slug: {
    type: String,
    required: true
  }
})

const route = useRoute()
const isChannel = ref(false)
const isMarketingPage = ref(false)
const loading = ref(true)

const checkPageType = async () => {
  loading.value = true
  isChannel.value = false
  isMarketingPage.value = false
  
  try {
    // First, try to load as a channel
    const channelResponse = await axios.get(`/api/channels/${props.slug}`)
    if (channelResponse.data) {
      isChannel.value = true
      loading.value = false
      return
    }
  } catch (error) {
    // Not a channel, continue checking
  }
  
  try {
    // Then, try to load as a marketing page
    const pageResponse = await axios.get(`/api/pages/${props.slug}`)
    if (pageResponse.data) {
      isMarketingPage.value = true
      loading.value = false
      return
    }
  } catch (error) {
    // Not a marketing page either
  }
  
  // Neither channel nor marketing page found
  loading.value = false
}

onMounted(() => {
  checkPageType()
})

// Watch for route changes
watch(() => props.slug, () => {
  checkPageType()
})
</script>