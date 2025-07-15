<template>
    <div>
        <!-- Page header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold leading-6 text-gray-900">Admin Dashboard</h1>
                    <p class="mt-2 text-sm text-gray-700">Welcome back! Here's what's happening with your platform.</p>
                </div>

                <div class="flex space-x-3">
                    <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                        Export Data
                    </button>
                    <router-link to="/places/create" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Place
                    </router-link>
                </div>
            </div>
        </div>

  <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
        <!-- Key Metrics -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <div v-for="metric in keyMetrics" :key="metric.name" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div :class="metric.iconClass" class="h-12 w-12 rounded-lg flex items-center justify-center">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="metric.iconPath" />
                            </svg>
                        </div>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="truncate text-sm font-medium text-gray-500">{{ metric.name }}</dt>
                            <dd class="flex items-baseline">
                                <div class="text-2xl font-semibold text-gray-900">{{ metric.value }}</div>
                                <div :class="[
                                    metric.changeType === 'increase' ? 'text-green-600' : 'text-red-600',
                                    'ml-2 flex items-baseline text-sm font-semibold'
                                ]">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            :d="metric.changeType === 'increase' ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6'" />
                                    </svg>
                                    {{ metric.change }}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Management Links -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Content Management</h3>
                    <div class="space-y-3">
                        <router-link to="/admin/users" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="bg-blue-100 rounded-lg p-2 mr-3">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">User Management</p>
                                    <p class="text-xs text-gray-500">{{ stats.users || 0 }} users</p>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </router-link>

                        <router-link to="/admin/entries" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="bg-green-100 rounded-lg p-2 mr-3">
                                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Entry Management</p>
                                    <p class="text-xs text-gray-500">{{ stats.entries || 0 }} places</p>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </router-link>

                        <router-link to="/admin/categories" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="bg-purple-100 rounded-lg p-2 mr-3">
                                    <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Categories</p>
                                    <p class="text-xs text-gray-500">{{ stats.categories || 0 }} categories</p>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </router-link>

                        <router-link to="/admin/regions" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="bg-orange-100 rounded-lg p-2 mr-3">
                                    <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Regions</p>
                                    <p class="text-xs text-gray-500">{{ stats.regions || 0 }} locations</p>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </router-link>

                        <router-link to="/admin/lists" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="bg-indigo-100 rounded-lg p-2 mr-3">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Lists</p>
                                    <p class="text-xs text-gray-500">{{ stats.lists || 0 }} lists</p>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </router-link>

                        <router-link to="/admin/list-categories" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center">
                                <div class="bg-pink-100 rounded-lg p-2 mr-3">
                                    <svg class="h-5 w-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">List Categories</p>
                                    <p class="text-xs text-gray-500">{{ stats.listCategories || 0 }} categories</p>
                                </div>
                            </div>
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </router-link>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity</h3>
                    <div class="mt-6 flow-root">
                        <ul role="list" class="-my-5 divide-y divide-gray-200">
                            <li v-for="activity in recentActivity" :key="activity.id" class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <img v-if="activity.user.avatar" class="h-8 w-8 rounded-full" :src="activity.user.avatar" :alt="activity.user.name" />
                                        <div v-else class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-gray-600 text-sm font-medium">{{ activity.user.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900">{{ activity.user.name }}</p>
                                        <p class="truncate text-sm text-gray-500">{{ activity.action }}</p>
                                    </div>
                                    <div class="flex-shrink-0 text-sm text-gray-500">
                                        {{ formatTime(activity.timestamp) }}
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">System Health</h3>
                    <div class="mt-6 space-y-4">
                        <div v-for="health in systemHealth" :key="health.name" class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div :class="[
                                    health.status === 'healthy' ? 'bg-green-400' : health.status === 'warning' ? 'bg-yellow-400' : 'bg-red-400',
                                    'h-2 w-2 rounded-full mr-3'
                                ]"></div>
                                <span class="text-sm font-medium text-gray-900">{{ health.name }}</span>
                            </div>
                            <span class="text-sm text-gray-500">{{ health.value }}</span>
                        </div>
                        <div class="pt-4 border-t border-gray-200">
                            <button class="w-full text-center text-sm font-medium text-blue-600 hover:text-blue-500">
                                View detailed logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Actions -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Pending Actions</h3>
                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                        {{ pendingActions.length }} items
                    </span>
                </div>
                <div class="mt-6">
                    <div class="space-y-4">
                        <div v-for="action in pendingActions" :key="action.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div :class="[
                                    action.priority === 'high' ? 'bg-red-100 text-red-800' : 
                                    action.priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                                    'bg-blue-100 text-blue-800',
                                    'inline-flex px-2 py-1 text-xs font-medium rounded-full'
                                ]">
                                    {{ action.priority }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ action.title }}</p>
                                    <p class="text-sm text-gray-500">{{ action.description }}</p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button class="text-sm font-medium text-blue-600 hover:text-blue-500">Review</button>
                                <button class="text-sm font-medium text-gray-600 hover:text-gray-500">Dismiss</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// Data from server
const stats = ref({
    users: 0,
    new_users_this_week: 0,
    entries: 0,
    pending_entries: 0,
    lists: 0,
    public_lists: 0,
    categories: 0,
    regions: 0,
    listCategories: 0
})

const recentUsers = ref([])
const recentEntries = ref([])

// Key metrics computed from stats
const keyMetrics = computed(() => [
    {
        name: 'Total Users',
        value: stats.value.users?.toLocaleString() || '0',
        change: '+12%',
        changeType: 'increase',
        iconClass: 'bg-gradient-to-br from-blue-400 to-blue-600',
        iconPath: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
    },
    {
        name: 'Places Listed',
        value: stats.value.entries?.toLocaleString() || '0',
        change: '+8%',
        changeType: 'increase',
        iconClass: 'bg-gradient-to-br from-green-400 to-green-600',
        iconPath: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
    },
    {
        name: 'User Lists',
        value: stats.value.lists?.toLocaleString() || '0',
        change: '+23%',
        changeType: 'increase',
        iconClass: 'bg-gradient-to-br from-purple-400 to-purple-600',
        iconPath: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'
    },
    {
        name: 'Regions',
        value: stats.value.regions?.toLocaleString() || '0',
        change: '+5%',
        changeType: 'increase',
        iconClass: 'bg-gradient-to-br from-orange-400 to-orange-600',
        iconPath: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'
    }
])

// Recent activity
const recentActivity = computed(() => {
    const userActivities = recentUsers.value.slice(0, 3).map(user => ({
        id: `user-${user.id}`,
        user: { 
            name: user.name, 
            avatar: user.avatar
        },
        action: 'Joined the platform',
        timestamp: new Date(user.created_at)
    }))
    
    const entryActivities = recentEntries.value.slice(0, 2).map(entry => ({
        id: `entry-${entry.id}`,
        user: { 
            name: entry.created_by?.name || 'System', 
            avatar: entry.created_by?.avatar
        },
        action: `Created place: "${entry.title}"`,
        timestamp: new Date(entry.created_at)
    }))
    
    return [...userActivities, ...entryActivities].sort((a, b) => b.timestamp - a.timestamp)
})

const systemHealth = computed(() => [
    { name: 'Database', status: 'healthy', value: '99.9%' },
    { name: 'API Response', status: 'healthy', value: '< 200ms' },
    { name: 'Storage', status: 'warning', value: '78% used' },
    { name: 'CDN', status: 'healthy', value: 'Active' }
])

const pendingActions = computed(() => [
    {
        id: 1,
        title: 'Review pending entries',
        description: `${stats.value.pending_entries || 0} places waiting for approval`,
        priority: 'high'
    },
    {
        id: 2,
        title: 'Moderate reported content',
        description: '3 items flagged by users',
        priority: 'medium'
    },
    {
        id: 3,
        title: 'Update system settings',
        description: 'Review and update platform configuration',
        priority: 'low'
    }
])

const fetchDashboardData = async () => {
    try {
        // Fetch stats
        const statsRes = await axios.get('/api/admin/dashboard/stats')
        stats.value = statsRes.data

        // Fetch recent users
        const usersRes = await axios.get('/api/admin/users', {
            params: { limit: 5, sort_by: 'created_at', sort_order: 'desc' }
        })
        recentUsers.value = usersRes.data.data || []

        // Fetch recent entries
        const entriesRes = await axios.get('/api/admin/entries', {
            params: { limit: 5, sort_by: 'created_at', sort_order: 'desc' }
        })
        recentEntries.value = entriesRes.data.data || []
    } catch (error) {
        console.error('Error fetching dashboard data:', error)
    }
}

const formatTime = (timestamp) => {
    const now = new Date()
    const diff = now - (timestamp instanceof Date ? timestamp : new Date(timestamp))
    const minutes = Math.floor(diff / (1000 * 60))
    
    if (minutes < 1) return 'Just now'
    if (minutes < 60) return `${minutes}m ago`
    
    const hours = Math.floor(minutes / 60)
    if (hours < 24) return `${hours}h ago`
    
    const days = Math.floor(hours / 24)
    return `${days}d ago`
}

onMounted(() => {
    fetchDashboardData()
})
</script>