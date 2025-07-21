<template>
    <div class="py-12">
            <div class="mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                        <button
                            @click="showCreateModal = true"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Add New User
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-6">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                            <div class="text-sm text-blue-600 font-medium">Total Users</div>
                            <div class="text-2xl font-bold text-blue-900">{{ stats.total_users || 0 }}</div>
                        </div>
                        <div v-for="(count, role) in stats.by_role" :key="role" :class="getRoleColorClass(role)" class="p-4 rounded-lg">
                            <div class="text-sm font-medium capitalize">{{ formatRoleName(role) }}s</div>
                            <div class="text-2xl font-bold">{{ count }}</div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                            <input
                                v-model="filters.search"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Name, email, or username"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select
                                v-model="filters.role"
                                @change="fetchUsers"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">All Roles</option>
                                <option value="admin">Admin</option>
                                <option value="manager">Manager</option>
                                <option value="editor">Editor</option>
                                <option value="business_owner">Business Owner</option>
                                <option value="user">User</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                @change="fetchUsers"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="created_at">Registration Date</option>
                                <option value="name">Name</option>
                                <option value="email">Email</option>
                                <option value="last_active_at">Last Active</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                            <select
                                v-model="filters.sort_order"
                                @change="fetchUsers"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="desc">Descending</option>
                                <option value="asc">Ascending</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedUsers.length > 0" class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700 font-medium">
                                    {{ selectedUsers.length }} user(s) selected
                                </span>
                                <div class="space-x-2">
                                    <button
                                        @click="bulkUpdateRole"
                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors"
                                    >
                                        Change Role
                                    </button>
                                    <button
                                        @click="bulkDelete"
                                        class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700 transition-colors"
                                    >
                                        Delete Selected
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Loading State -->
                        <div v-if="loading" class="flex justify-center py-8">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                        </div>

                        <!-- Table -->
                        <div v-else-if="users.data && users.data.length > 0" class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                @change="toggleSelectAll"
                                                :checked="selectedUsers.length === users.data.length"
                                                class="rounded border-gray-300"
                                            />
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            User
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Role
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Joined
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Last Active
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input
                                                type="checkbox"
                                                :value="user.id"
                                                v-model="selectedUsers"
                                                class="rounded border-gray-300"
                                            />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img
                                                        v-if="user.profile_photo_url"
                                                        class="h-10 w-10 rounded-full"
                                                        :src="user.profile_photo_url"
                                                        :alt="user.name"
                                                    />
                                                    <div v-else class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-gray-600 font-medium">{{ user.name.charAt(0).toUpperCase() }}</span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                                                    <div v-if="user.custom_url" class="text-xs text-blue-600">@{{ user.custom_url }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getRoleBadgeClass(user.role)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                {{ formatRoleName(user.role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ user.last_active_at ? formatDate(user.last_active_at) : 'Never' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-if="user.email_verified_at" class="text-green-600 text-sm">Verified</span>
                                            <span v-else class="text-yellow-600 text-sm">Unverified</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button
                                                @click="editUser(user)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteUser(user)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No users found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filters</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="users.last_page > 1" class="mt-6">
                            <Pagination :links="users.links" />
                        </div>
                    </div>
                </div>
            </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingUser ? 'Edit User' : 'Create New User' }}
                </h3>

                <form @submit.prevent="saveUser">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name</label>
                                <input
                                    v-model="form.firstname"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors.firstname" class="text-red-500 text-sm mt-1">{{ errors.firstname }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input
                                    v-model="form.lastname"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                <div v-if="errors.lastname" class="text-red-500 text-sm mt-1">{{ errors.lastname }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <div v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select
                                v-model="form.role"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="user">User</option>
                                <option value="business_owner">Business Owner</option>
                                <option value="editor">Editor</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                            <div v-if="errors.role" class="text-red-500 text-sm mt-1">{{ errors.role }}</div>
                        </div>

                        <div v-if="!editingUser">
                            <label class="block text-sm font-medium text-gray-700">Password</label>
                            <input
                                v-model="form.password"
                                type="password"
                                :required="!editingUser"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                            <div v-if="errors.password" class="text-red-500 text-sm mt-1">{{ errors.password }}</div>
                        </div>

                        <div v-if="!editingUser">
                            <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                :required="!editingUser"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ processing ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, watch } from 'vue'
import axios from 'axios'
import Modal from '@/components/Modal.vue'
import Pagination from '@/components/Pagination.vue'
import { debounce } from 'lodash'

// State
const loading = ref(false)
const users = ref({ data: [], links: [], last_page: 1 })
const stats = ref({ total_users: 0, by_role: {} })
const selectedUsers = ref([])
const showModal = ref(false)
const showCreateModal = ref(false)
const editingUser = ref(null)
const processing = ref(false)
const errors = ref({})

// Filters
const filters = reactive({
    search: '',
    role: '',
    sort_by: 'created_at',
    sort_order: 'desc'
})

// Form
const form = reactive({
    firstname: '',
    lastname: '',
    email: '',
    role: 'user',
    password: '',
    password_confirmation: ''
})

// Methods
const fetchUsers = async (page = 1) => {
    loading.value = true
    try {
        const response = await axios.get('/api/admin/users', {
            params: {
                page,
                search: filters.search,
                role: filters.role,
                sort_by: filters.sort_by,
                sort_order: filters.sort_order
            }
        })
        users.value = response.data
    } catch (error) {
        console.error('Error fetching users:', error)
    } finally {
        loading.value = false
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/admin/users/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchUsers()
}, 300)

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedUsers.value = users.value.data.map(user => user.id)
    } else {
        selectedUsers.value = []
    }
}

const editUser = (user) => {
    editingUser.value = user
    // Split name into firstname and lastname if separate fields not available
    if (user.firstname && user.lastname) {
        form.firstname = user.firstname
        form.lastname = user.lastname
    } else if (user.name) {
        const nameParts = user.name.split(' ')
        form.firstname = nameParts[0] || ''
        form.lastname = nameParts.slice(1).join(' ') || ''
    }
    form.email = user.email
    form.role = user.role
    form.password = ''
    form.password_confirmation = ''
    showModal.value = true
}

const deleteUser = async (user) => {
    if (!confirm(`Are you sure you want to delete ${user.name}?`)) return

    try {
        await axios.delete(`/api/admin/users/${user.id}`)
        fetchUsers()
        fetchStats()
    } catch (error) {
        alert('Error deleting user')
    }
}

const bulkDelete = async () => {
    if (!confirm(`Are you sure you want to delete ${selectedUsers.value.length} users?`)) return

    try {
        await axios.post('/api/admin/users/bulk-update', {
            user_ids: selectedUsers.value,
            action: 'delete'
        })
        selectedUsers.value = []
        fetchUsers()
        fetchStats()
    } catch (error) {
        alert('Error deleting users')
    }
}

const bulkUpdateRole = async () => {
    const newRole = prompt('Enter new role (admin, manager, editor, business_owner, user):')
    if (!newRole) return

    try {
        await axios.post('/api/admin/users/bulk-update', {
            user_ids: selectedUsers.value,
            action: 'update_role',
            role: newRole
        })
        selectedUsers.value = []
        fetchUsers()
        fetchStats()
    } catch (error) {
        alert('Error updating roles')
    }
}

const saveUser = async () => {
    processing.value = true
    errors.value = {}

    try {
        const userData = {
            firstname: form.firstname,
            lastname: form.lastname,
            email: form.email,
            role: form.role
        }

        // Only include password fields for new users
        if (!editingUser.value) {
            userData.password = form.password
            userData.password_confirmation = form.password_confirmation
        }

        if (editingUser.value) {
            await axios.put(`/api/admin/users/${editingUser.value.id}`, userData)
        } else {
            await axios.post('/api/admin/users', userData)
        }
        closeModal()
        fetchUsers()
        fetchStats()
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else {
            alert('Error saving user: ' + (error.response?.data?.message || error.message))
        }
    } finally {
        processing.value = false
    }
}

const closeModal = () => {
    showModal.value = false
    showCreateModal.value = false
    editingUser.value = null
    form.firstname = ''
    form.lastname = ''
    form.email = ''
    form.role = 'user'
    form.password = ''
    form.password_confirmation = ''
    errors.value = {}
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString()
}

const formatRoleName = (role) => {
    const roleNames = {
        admin: 'Admin',
        manager: 'Manager',
        editor: 'Editor',
        business_owner: 'Business Owner',
        user: 'User'
    }
    return roleNames[role] || role
}

const getRoleBadgeClass = (role) => {
    const classes = {
        admin: 'bg-red-100 text-red-800',
        manager: 'bg-purple-100 text-purple-800',
        editor: 'bg-yellow-100 text-yellow-800',
        business_owner: 'bg-green-100 text-green-800',
        user: 'bg-gray-100 text-gray-800'
    }
    return classes[role] || 'bg-gray-100 text-gray-800'
}

const getRoleColorClass = (role) => {
    const classes = {
        admin: 'bg-gradient-to-br from-red-50 to-red-100 text-red-600',
        manager: 'bg-gradient-to-br from-purple-50 to-purple-100 text-purple-600',
        editor: 'bg-gradient-to-br from-yellow-50 to-yellow-100 text-yellow-600',
        business_owner: 'bg-gradient-to-br from-green-50 to-green-100 text-green-600',
        user: 'bg-gradient-to-br from-gray-50 to-gray-100 text-gray-600'
    }
    return classes[role] || 'bg-gradient-to-br from-gray-50 to-gray-100 text-gray-600'
}

// Watch for create modal
watch(() => showCreateModal.value, (newVal) => {
    if (newVal) {
        editingUser.value = null
        showModal.value = true
    }
})

// Initialize
onMounted(() => {
    fetchUsers()
    fetchStats()
})
</script>