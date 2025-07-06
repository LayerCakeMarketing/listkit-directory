<template>
  <Head title="Admin Dashboard" />

  <AdminDashboardLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-semibold leading-6 text-gray-900">Admin Dashboard</h1>
          <p class="mt-2 text-sm text-gray-700">Welcome back! Here's what's happening with your platform.</p>
        </div>
        <div class="flex space-x-3">
          <button class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
            <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
            Export Data
          </button>
          <Link :href="route('places.create')" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
            <PlusIcon class="h-4 w-4 mr-2" />
            Add Place
          </Link>
        </div>
      </div>
    </template>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <div v-for="metric in keyMetrics" :key="metric.name" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <component :is="metric.icon" class="h-8 w-8 text-gray-400" />
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
                  <component :is="metric.changeType === 'increase' ? ArrowTrendingUpIcon : ArrowTrendingDownIcon" class="h-4 w-4 mr-1" />
                  {{ metric.change }}
                </div>
              </dd>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
      <!-- Traffic Overview -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Platform Activity</h3>
            <select class="rounded-md border-gray-300 text-sm">
              <option>Last 7 days</option>
              <option>Last 30 days</option>
              <option>Last 3 months</option>
            </select>
          </div>
          <div class="mt-6">
            <!-- Placeholder for chart -->
            <div class="bg-gray-50 rounded-lg h-64 flex items-center justify-center">
              <div class="text-center">
                <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500">Chart visualization would go here</p>
                <p class="text-xs text-gray-400">Integration with Chart.js or similar library</p>
              </div>
            </div>
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
                    <img class="h-8 w-8 rounded-full" :src="activity.user.avatar" :alt="activity.user.name" />
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
    </div>

    <!-- Content Management Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
      <!-- Top Places -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Top Places</h3>
            <Link :href="route('admin.entries')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">View all</Link>
          </div>
          <div class="mt-6">
            <ul role="list" class="space-y-4">
              <li v-for="place in topPlaces" :key="place.id" class="flex items-center space-x-3">
                <div class="flex-shrink-0">
                  <img v-if="place.image" class="h-10 w-10 rounded-lg object-cover" :src="place.image" :alt="place.name" />
                  <div v-else class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center">
                    <BuildingOfficeIcon class="h-6 w-6 text-gray-400" />
                  </div>
                </div>
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ place.name }}</p>
                  <p class="text-sm text-gray-500">{{ place.views }} views</p>
                </div>
                <div class="flex-shrink-0">
                  <span :class="[
                    place.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800',
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full'
                  ]">
                    {{ place.status }}
                  </span>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- User Growth -->
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg leading-6 font-medium text-gray-900">User Growth</h3>
            <Link :href="route('admin.users')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Manage users</Link>
          </div>
          <div class="mt-6">
            <div class="grid grid-cols-2 gap-4">
              <div class="text-center">
                <div class="text-2xl font-semibold text-gray-900">{{ userStats.newThisWeek }}</div>
                <div class="text-sm text-gray-500">New this week</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-semibold text-gray-900">{{ userStats.activeToday }}</div>
                <div class="text-sm text-gray-500">Active today</div>
              </div>
            </div>
            <div class="mt-4">
              <div class="bg-gray-200 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: userStats.growthPercent + '%' }"></div>
              </div>
              <p class="mt-2 text-sm text-gray-600">{{ userStats.growthPercent }}% growth this month</p>
            </div>
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
              <button class="w-full text-center text-sm font-medium text-indigo-600 hover:text-indigo-500">
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
                <button class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Review</button>
                <button class="text-sm font-medium text-gray-600 hover:text-gray-500">Dismiss</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import axios from 'axios'
import {
  ArrowDownTrayIcon,
  ArrowTrendingDownIcon,
  ArrowTrendingUpIcon,
  BuildingOfficeIcon,
  ChartBarIcon,
  PlusIcon,
  UsersIcon,
  DocumentTextIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline'

// Data from server or defaults
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

// Key metrics computed from stats
const keyMetrics = computed(() => [
  {
    name: 'Total Users',
    value: stats.value.users?.toLocaleString() || '1,247',
    change: '+12%',
    changeType: 'increase',
    icon: UsersIcon
  },
  {
    name: 'Places Listed',
    value: stats.value.entries?.toLocaleString() || '3,421',
    change: '+8%',
    changeType: 'increase',
    icon: BuildingOfficeIcon
  },
  {
    name: 'User Lists',
    value: stats.value.lists?.toLocaleString() || '856',
    change: '+23%',
    changeType: 'increase',
    icon: DocumentTextIcon
  },
  {
    name: 'Page Views',
    value: stats.value.comments?.toLocaleString() || '12,453',
    change: '+5%',
    changeType: 'increase',
    icon: EyeIcon
  }
])

// Enhanced recent activity with mock data structure
const recentActivity = computed(() => {
    // Convert recent users and entries to activity format
    const userActivities = recentUsers.value.slice(0, 2).map(user => ({
        id: `user-${user.id}`,
        user: { 
            name: user.name, 
            avatar: user.avatar || 'https://images.unsplash.com/photo-1494790108755-2616b612b786?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
        },
        action: 'Joined the platform',
        timestamp: new Date(user.created_at)
    }))
    
    const entryActivities = recentEntries.value.slice(0, 2).map(entry => ({
        id: `entry-${entry.id}`,
        user: { 
            name: entry.created_by?.name || 'Unknown User', 
            avatar: entry.created_by?.avatar || 'https://images.unsplash.com/photo-1519244703995-f4e0f30006d5?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80'
        },
        action: `Created place: "${entry.title}"`,
        timestamp: new Date(entry.created_at)
    }))
    
    return [...userActivities, ...entryActivities].sort((a, b) => b.timestamp - a.timestamp)
})

const topPlaces = computed(() => 
    recentEntries.value.slice(0, 4).map((entry, index) => ({
        id: entry.id,
        name: entry.title,
        views: Math.floor(Math.random() * 2000) + 500, // Mock views
        status: entry.status,
        image: entry.image
    }))
)

const userStats = computed(() => ({
    newThisWeek: stats.value.new_users_this_week || 47,
    activeToday: Math.floor(stats.value.users * 0.1) || 234, // Mock 10% active
    growthPercent: 15
}))

const systemHealth = computed(() => [
    { name: 'Database', status: 'healthy', value: '99.9%' },
    { name: 'API Response', status: 'healthy', value: '< 200ms' },
    { name: 'Storage', status: 'warning', value: '78% used' },
    { name: 'CDN', status: 'healthy', value: 'Active' }
])

const pendingActions = computed(() => [
    {
        id: 1,
        title: 'Review flagged content',
        description: `${stats.value.pending_entries || 3} places have been reported by users`,
        priority: 'high'
    },
    {
        id: 2,
        title: 'Approve business verifications',
        description: '8 businesses pending verification',
        priority: 'medium'
    },
    {
        id: 3,
        title: 'Update system configuration',
        description: 'Scheduled maintenance window available',
        priority: 'low'
    }
])

const fetchDashboardData = async () => {
    try {
        // Updated to use admin-data endpoints
        const [statsRes, usersRes, entriesRes] = await Promise.all([
            axios.get('/admin-data/dashboard/stats'),
            axios.get('/admin-data/users?limit=5&sort_by=created_at&sort_order=desc'),
            axios.get('/admin-data/entries?limit=5&sort_by=created_at&sort_order=desc')
        ])

        stats.value = statsRes.data
        recentUsers.value = usersRes.data.data || []
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