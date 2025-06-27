<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Admin Dashboard</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Admin Dashboard</h2>
                    
                    <!-- Quick Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <div class="text-blue-600 text-sm font-medium">Total Users</div>
                            <div class="text-3xl font-bold text-blue-800">{{ stats.users }}</div>
                            <div class="text-sm text-blue-600 mt-2">
                                +{{ stats.new_users_this_week }} this week
                            </div>
                        </div>
                        
                        <div class="bg-green-50 p-6 rounded-lg">
                            <div class="text-green-600 text-sm font-medium">Directory Entries</div>
                            <div class="text-3xl font-bold text-green-800">{{ stats.entries }}</div>
                            <div class="text-sm text-green-600 mt-2">
                                {{ stats.pending_entries }} pending review
                            </div>
                        </div>
                        
                        <div class="bg-purple-50 p-6 rounded-lg">
                            <div class="text-purple-600 text-sm font-medium">User Lists</div>
                            <div class="text-3xl font-bold text-purple-800">{{ stats.lists }}</div>
                            <div class="text-sm text-purple-600 mt-2">
                                {{ stats.public_lists }} public
                            </div>
                        </div>
                        
                        <div class="bg-orange-50 p-6 rounded-lg">
                            <div class="text-orange-600 text-sm font-medium">Comments</div>
                            <div class="text-3xl font-bold text-orange-800">{{ stats.comments }}</div>
                            <div class="text-sm text-orange-600 mt-2">
                                {{ stats.comments_today }} today
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Recent Users -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Recent Users</h3>
                            <div class="space-y-3">
                                <div v-for="user in recentUsers" :key="user.id" class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gray-300 rounded-full mr-3"></div>
                                        <div>
                                            <div class="text-sm font-medium">{{ user.name }}</div>
                                            <div class="text-xs text-gray-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ formatTime(user.created_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Entries -->
                        <div class="bg-gray-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold mb-4">Recent Entries</h3>
                            <div class="space-y-3">
                                <div v-for="entry in recentEntries" :key="entry.id" class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium">{{ entry.title }}</div>
                                        <div class="text-xs text-gray-500">by {{ entry.created_by.name }}</div>
                                    </div>
                                    <span :class="getStatusBadgeClass(entry.status)" class="text-xs px-2 py-1 rounded-full">
                                        {{ entry.status }}
                                    </span>
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
import { ref, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const stats = ref({
    users: 0,
    new_users_this_week: 0,
    entries: 0,
    pending_entries: 0,
    lists: 0,
    public_lists: 0,
    comments: 0,
    comments_today: 0,
})

const recentUsers = ref([])
const recentEntries = ref([])

const fetchDashboardData = async () => {
    try {
        // Updated to use admin-data endpoints
        const [statsRes, usersRes, entriesRes] = await Promise.all([
            axios.get('/admin-data/dashboard/stats'),
            axios.get('/admin-data/users?limit=5&sort_by=created_at&sort_order=desc'),
            axios.get('/admin-data/entries?limit=5&sort_by=created_at&sort_order=desc')
        ])

        stats.value = statsRes.data
        recentUsers.value = usersRes.data.data
        recentEntries.value = entriesRes.data.data
    } catch (error) {
        console.error('Error fetching dashboard data:', error)
    }
}

const formatTime = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diff = now - date
    const hours = Math.floor(diff / (1000 * 60 * 60))
    
    if (hours < 1) return 'Just now'
    if (hours < 24) return `${hours}h ago`
    if (hours < 48) return 'Yesterday'
    return date.toLocaleDateString()
}

const getStatusBadgeClass = (status) => {
    const classes = {
        published: 'bg-green-100 text-green-800',
        pending_review: 'bg-yellow-100 text-yellow-800',
        draft: 'bg-gray-100 text-gray-800',
        archived: 'bg-red-100 text-red-800'
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

onMounted(() => {
    fetchDashboardData()
})
</script>