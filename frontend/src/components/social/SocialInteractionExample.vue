<template>
  <div class="max-w-3xl mx-auto p-6 space-y-8">
    <!-- Example for Post -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold mb-4">Post with Social Interactions</h3>
      
      <div class="mb-4">
        <p class="text-gray-900">This is an example post content...</p>
      </div>
      
      <!-- Interaction Bar for Post -->
      <InteractionBar
        type="post"
        :id="123"
        :liked="false"
        :likes-count="42"
        :comments-count="5"
        :reposted="false"
        :reposts-count="3"
        @toggle-comments="showPostComments = !showPostComments"
      />
      
      <!-- Comments Section -->
      <div v-if="showPostComments" class="border-t mt-4 pt-4">
        <CommentSection
          type="post"
          :id="123"
        />
      </div>
    </div>

    <!-- Example for List -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold mb-4">List with Social Interactions</h3>
      
      <div class="mb-4">
        <h4 class="font-medium">Best Coffee Shops in SF</h4>
        <p class="text-gray-600">A curated list of amazing coffee spots...</p>
      </div>
      
      <!-- Interaction Bar for List -->
      <InteractionBar
        type="list"
        :id="456"
        :liked="true"
        :likes-count="128"
        :comments-count="12"
        :reposted="false"
        :reposts-count="8"
        variant="default"
        @toggle-comments="showListComments = !showListComments"
      />
      
      <!-- Comments Section -->
      <div v-if="showListComments" class="border-t mt-4 pt-4">
        <CommentSection
          type="list"
          :id="456"
        />
      </div>
    </div>

    <!-- Example for Place -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold mb-4">Place with Social Interactions</h3>
      
      <div class="mb-4">
        <h4 class="font-medium">Blue Bottle Coffee</h4>
        <p class="text-gray-600">Premium coffee roaster and cafe...</p>
      </div>
      
      <!-- Interaction Bar for Place (no reposts) -->
      <InteractionBar
        type="place"
        :id="789"
        :liked="false"
        :likes-count="256"
        :comments-count="34"
        :show-reposts="false"
        variant="minimal"
        @toggle-comments="showPlaceComments = !showPlaceComments"
      />
      
      <!-- Comments Section -->
      <div v-if="showPlaceComments" class="border-t mt-4 pt-4">
        <CommentSection
          type="place"
          :id="789"
        />
      </div>
    </div>

    <!-- Standalone Components Examples -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold mb-4">Individual Components</h3>
      
      <div class="space-y-4">
        <!-- Like Button Variants -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Like Button Variants:</h4>
          <div class="flex items-center gap-4">
            <LikeButton
              type="post"
              :id="111"
              variant="default"
              show-label
            />
            <LikeButton
              type="post"
              :id="222"
              variant="minimal"
              show-count
            />
            <LikeButton
              type="post"
              :id="333"
              variant="filled"
              :initial-liked="true"
              :initial-count="99"
            />
          </div>
        </div>

        <!-- Repost Button Variants -->
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Repost Button Variants:</h4>
          <div class="flex items-center gap-4">
            <RepostButton
              type="post"
              :id="444"
              variant="default"
              show-label
            />
            <RepostButton
              type="list"
              :id="555"
              variant="minimal"
              :initial-reposted="true"
              :initial-count="12"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import InteractionBar from './InteractionBar.vue'
import CommentSection from './CommentSection.vue'
import LikeButton from './LikeButton.vue'
import RepostButton from './RepostButton.vue'

const showPostComments = ref(false)
const showListComments = ref(false)
const showPlaceComments = ref(false)
</script>