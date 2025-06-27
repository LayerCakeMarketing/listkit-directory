<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                        <div class="text-gray-600">Total Users</div>
                    </div>
                    <div v-for="(count, role) in stats.by_role" :key="role" class="bg-white p-6 rounded-lg shadow">
                        <div class="text-2xl font-bold">{{ count }}</div>
                        <div class="text-gray-600 capitalize">{{ role }}s</div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <div class="flex gap-4">
                        <input
                            v-model="search"
                            @input="debouncedFetch"
                            type="text"
                            placeholder="Search users..."
                            class="flex-1 rounded-md border-gray-300"
                        />
                        <select v-model="roleFilter" @change="fetchUsers" class="rounded-md border-gray-300">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="editor">Editor</option>
                            <option value="business_owner">Business Owner</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="loading" class="text-center py-4">Loading...</div>
                        <div v-else-if="error" class="text-red-600 text-center py-4">{{ error }}</div>
                        <div v-else>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users" :key="user.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ user.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ user.email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                  :class="getRoleBadgeClass(user.role)">
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(user.created_at) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button @click="editUser(user)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                Edit
                                            </button>
                                            <button v-if="user.id !== $page.props.auth.user.id"
                                                    @click="deleteUser(user)"
                                                    class="text-red-600 hover:text-red-900">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" @click="showEditModal = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="updateUser">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                Edit User: {{ editingUser.name }}
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Name</label>
                                    <input v-model="editForm.name" type="text" class="mt-1 block w-full rounded-md border-gray-300" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Email</label>
                                    <input v-model="editForm.email" type="email" class="mt-1 block w-full rounded-md border-gray-300" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Role</label>
                                    <select v-model="editForm.role" class="mt-1 block w-full rounded-md border-gray-300">
                                        <option value="user">User</option>
                                        <option value="business_owner">Business Owner</option>
                                        <option value="editor">Editor</option>
                                        <option value="manager">Manager</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                Save
                            </button>
                            <button type="button" @click="showEditModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
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
import { debounce } from 'lodash'

// Data
const users = ref([])
const stats = ref({ total_users: 0, by_role: {} })
const loading = ref(false)
const error = ref(null)
const search = ref('')
const roleFilter = ref('')
const showEditModal = ref(false)
const editingUser = ref(null)
const editForm = ref({
    name: '',
    email: '',
    role: ''
})

// Methods
const fetchUsers = async () => {
    loading.value = true
    error.value = null
    
    try {
        const params = {}
        if (search.value) params.search = search.value
        if (roleFilter.value) params.role = roleFilter.value
        
        const response = await axios.get('/api/admin/users', { params })
        users.value = response.data.data || []
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to fetch users'
        console.error('Error fetching users:', err)
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/users/stats')
        stats.value = response.data
    } catch (err) {
        console.error('Error fetching stats:', err)
    }
}

const debouncedFetch = debounce(fetchUsers, 300)

const editUser = (user) => {
    editingUser.value = user
    editForm.value = {
        name: user.name,
        email: user.email,
        role: user.role
    }
    showEditModal.value = true
}

const updateUser = async () => {
    try {
        await axios.put(`/api/admin/users/${editingUser.value.id}`, editForm.value)
        showEditModal.value = false
        fetchUsers()
        alert('User updated successfully')
    } catch (err) {
        alert('Error updating user: ' + (err.response?.data?.message || 'Unknown error'))
    }
}

const deleteUser = async (user) => {
    if (!confirm(`Are you sure you want to delete ${user.name}?`)) return
    
    try {
        await axios.delete(`/api/admin/users/${user.id}`)
        fetchUsers()
        fetchStats()
        alert('User deleted successfully')
    } catch (err) {
        alert('Error deleting user: ' + (err.response?.data?.message || 'Unknown error'))
    }
}

const getRoleBadgeClass = (role) => {
    const classes = {
        admin: 'bg-red-100 text-red-800',
        manager: 'bg-orange-100 text-orange-800',
        editor: 'bg-yellow-100 text-yellow-800',
        business_owner: 'bg-green-100 text-green-800',
        user: 'bg-gray-100 text-gray-800',
    }
    return classes[role] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
    fetchUsers()
    fetchStats()
})
</script>