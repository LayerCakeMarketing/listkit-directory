<template>
    <Head :title="list.name || 'Loading...'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ list.name || 'Loading...' }}
                </h2>
                <div class="flex space-x-2">
                    <Link
                        v-if="canEdit"
                        :href="list.id && list.user ? route('lists.edit', [list.user.username, list.slug]) : '#'"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm"
                    >
                        Edit List
                    </Link>
                    <Link
                        :href="route('lists.my')"
                        class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm"
                    >
                        Back to Lists
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div v-if="!list.id" class="max-w-4xl mx-auto sm:px-6 lg:px-8 text-center">
                <p class="text-gray-500">Loading list...</p>
            </div>
            <div v-else class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="list.description" class="mb-6">
                            <p class="text-gray-700">{{ list.description }}</p>
                        </div>

                        <div class="space-y-4">
                            <div v-for="item in list.items" :key="item.id" class="border-b pb-4 last:border-b-0">
                                <h3 class="font-semibold text-lg">{{ item.display_title || item.title }}</h3>
                                <p v-if="item.display_content || item.content" class="text-gray-600 mt-1">
                                    {{ item.display_content || item.content }}
                                </p>
                                
                                <div v-if="item.type === 'location' && item.data" class="mt-2 text-sm text-gray-500">
                                    📍 {{ item.data.address }}
                                </div>
                                
                                <div v-if="item.type === 'event' && item.data" class="mt-2 text-sm text-gray-500">
                                    📅 {{ new Date(item.data.start_date).toLocaleString() }}
                                    <span v-if="item.data.location"> @ {{ item.data.location }}</span>
                                </div>

                                <div v-if="item.affiliate_url" class="mt-2">
                                    <a :href="item.affiliate_url" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">
                                        Visit Link →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const axios = window.axios
const page = usePage()

const props = defineProps({
    listId: String
})

const list = ref({})

const canEdit = computed(() => {
    const user = page.props.auth.user
    if (!user) return false
    return list.value.user_id === user.id || ['admin', 'manager'].includes(user.role)
})

const fetchList = async () => {
    try {
        const response = await axios.get(`/data/lists/${props.listId}`)
        list.value = response.data
    } catch (error) {
        console.error('Error fetching list:', error)
    }
}

onMounted(() => {
    fetchList()
})
</script>