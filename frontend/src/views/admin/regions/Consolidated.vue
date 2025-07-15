<template>
    <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <!-- Tabs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex">
                            <button
                                @click="activeTab = 'quick'"
                                :class="[
                                    activeTab === 'quick'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm'
                                ]"
                            >
                                Quick Edit
                            </button>
                            <button
                                @click="activeTab = 'detail'"
                                :class="[
                                    activeTab === 'detail'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm'
                                ]"
                            >
                                Detail Edit
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Tab Content -->
                <div v-if="activeTab === 'quick'">
                    <BasicRegionView />
                </div>
                <div v-else-if="activeTab === 'detail'">
                    <EnhancedRegionView />
                </div>
            </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import BasicRegionView from './components/BasicRegionView.vue'
import EnhancedRegionView from './components/EnhancedRegionView.vue'

const activeTab = ref('quick')
</script>