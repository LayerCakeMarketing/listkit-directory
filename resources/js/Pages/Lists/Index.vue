<template>
    <Head title="My Lists" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Lists</h2>
                <Link
                    :href="route('lists.create')"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Create New List
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search and Filters -->
                <div class="bg-white p-4 rounded-lg shadow mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input
                            v-model="filters.search"
                            @input="debouncedFetch"
                            type="text"
                            placeholder="Search lists..."
                            class="rounded-md border-gray-300"
                        />
                        <select v-model="filters.visibility" @change="fetchLists" class="rounded-md border-gray-300">
                            <option value="">All Lists</option>
                            <option value="public">Public Only</option>
                            <option value="private">Private Only</option>
                        </select>
                    </div>
                </div>

                <!-- Lists Grid -->
                <div v-if="loading" class="text-center py-12">
                    <div class="inline-flex items-center">
                        <svg class="animate-spin h-5 w-5 mr-3 text-gray-600" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading lists...
                    </div>
                </div>

                <div v-else-if="lists.data.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No lists</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new list.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('lists.create')"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                        >
                            Create New List
                        </Link>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="list in lists.data"
                        :key="list.id"
                        class="bg-white overflow-hidden shadow-sm rounded-lg hover:shadow-lg transition-shadow"
                    >
                        <div v-if="list.featured_image_url" class="h-48 bg-gray-200">
                            <img :src="list.featured_image_url" :alt="list.name" class="w-full h-full object-cover" />
                        </div>
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <Link :href="getListUrl(list)" class="hover:text-blue-600">
                                        {{ list.name }}
                                    </Link>
                                </h3>
                                <span v-if="!list.is_public" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    Private
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">{{ truncate(list.description, 100) }}</p>
                            <div class="flex justify-between items-center text-sm text-gray-500">
                                <span>{{ list.items_count }} items</span>
                                <div class="flex space-x-2">
                                    <Link
                                        :href="getEditUrl(list)"
                                        class="text-blue-600 hover:text-blue-800"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        @click="deleteList(list)"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-8" v-if="lists.meta && lists.meta.total > lists.meta.per_page">
                    <Pagination :links="{
                        ...lists.meta,
                        links: lists.links
                    }" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const page = usePage()
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

const axios = window.axios

// Data
const lists = ref({ data: [], meta: {}, links: [] })
const loading = ref(false)
const filters = reactive({
    search: '',
    visibility: ''
})

// Methods
const fetchLists = async () => {
    loading.value = true
    try {
        const response = await axios.get('/data/my-lists', { params: filters })
        lists.value = response.data
    } catch (error) {
        console.error('Error fetching lists:', error)
    } finally {
        loading.value = false
    }
}

const debouncedFetch = debounce(fetchLists, 300)

const deleteList = async (list) => {
    if (!confirm(`Are you sure you want to delete "${list.name}"?`)) return
    
    try {
        await axios.delete(`/data/lists/${list.id}`)
        fetchLists()
    } catch (error) {
        alert('Error deleting list: ' + error.response?.data?.message)
    }
}

const truncate = (text, length) => {
    if (!text) return ''
    return text.length > length ? text.substring(0, length) + '...' : text
}

const getUserSlug = () => {
    const user = page.props.auth?.user
    return user?.custom_url || user?.username || user?.id
}

const getListUrl = (list) => {
    const userSlug = getUserSlug()
    if (userSlug && list.slug) {
        return route('lists.show', [userSlug, list.slug])
    }
    return '#'
}

const getEditUrl = (list) => {
    const userSlug = getUserSlug()
    if (userSlug && list.slug) {
        return route('lists.edit', [userSlug, list.slug])
    }
    return '#'
}

// Lifecycle
onMounted(() => {
    fetchLists()
})
</script>