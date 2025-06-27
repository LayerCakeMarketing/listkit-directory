<template>
    <Head title="My Lists" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Lists</h2>
                <button
                    @click="showMyLists = !showMyLists"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                >
                    {{ showMyLists ? 'Browse Public Lists' : 'View My Lists' }}
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Toggle between My Lists and Public Lists -->
                <div class="mb-6">
                    <div class="bg-white border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8">
                            <button
                                @click="showMyLists = true"
                                :class="[
                                    showMyLists
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                My Lists
                            </button>
                            <button
                                @click="showMyLists = false"
                                :class="[
                                    !showMyLists
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                ]"
                            >
                                Browse Public Lists
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <MyLists v-if="showMyLists" />
                    <PublicIndex v-else />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import MyLists from '@/Components/Lists/MyLists.vue'
import PublicIndex from '@/Components/Lists/PublicIndex.vue'

const showMyLists = ref(true)
</script>