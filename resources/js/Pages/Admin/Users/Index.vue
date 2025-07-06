<template>
    <Head title="User Management" />
 
        <AdminDashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Management</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                        <button
                            @click="showCreateModal = true"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Add New User
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Total Users</div>
                            <div class="text-2xl font-bold">{{ stats.total_users }}</div>
                        </div>
                        <div v-for="(count, role) in stats.by_role" :key="role" class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500 capitalize">{{ role }}s</div>
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
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <select
                                v-model="filters.role"
                                @change="fetchUsers"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                        <div v-if="selectedUsers.length > 0" class="mb-4 p-4 bg-blue-50 rounded">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700">
                                    {{ selectedUsers.length }} user(s) selected
                                </span>
                                <div class="space-x-2">
                                    <button
                                        @click="showBulkRoleModal = true"
                                        class="text-sm bg-blue-500 hover:bg-blue-700 text-white px-3 py-1 rounded"
                                    >
                                        Change Role
                                    </button>
                                    <button
                                        @click="bulkDelete"
                                        class="text-sm bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded"
                                    >
                                        Delete Selected
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left">
                                            <input
                                                type="checkbox"
                                                @change="toggleSelectAll"
                                                :checked="isAllSelected"
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
                                            Stats
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Joined
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Last Active
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="user.id"
                                                v-model="selectedUsers"
                                                class="rounded border-gray-300"
                                                :disabled="user.id === $page.props.auth.user.id"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img 
                                                        v-if="user.avatar" 
                                                        :src="user.avatar" 
                                                        :alt="user.name"
                                                        class="h-10 w-10 rounded-full"
                                                    />
                                                    <div v-else class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                        <span class="text-gray-600 font-medium">
                                                            {{ user.name.charAt(0).toUpperCase() }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        {{ user.name }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        {{ user.email }}
                                                    </div>
                                                    <div v-if="user.username" class="text-xs text-gray-400">
                                                        @{{ user.username }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span 
                                                :class="getRoleBadgeClass(user.role)"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <div>Lists: {{ user.lists_count }}</div>
                                            <div>Entries: {{ user.created_entries_count }}</div>
                                            <div v-if="user.owned_entries_count > 0">
                                                Owned: {{ user.owned_entries_count }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ formatDate(user.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ user.last_active_at ? formatDate(user.last_active_at) : 'Never' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <button
                                                @click="editUser(user)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                v-if="user.id !== $page.props.auth.user.id"
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

                        <!-- Pagination -->
                        <div class="mt-4">
                            <Pagination 
                                :links="{
                                    ...users.meta,
                                    links: users.links,
                                    from: users.meta?.from || 0,
                                    to: users.meta?.to || 0,
                                    total: users.meta?.total || 0
                                }" 
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Edit User: {{ editingUser?.name }}
                </h3>

                <form @submit.prevent="updateUser">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input
                                v-model="editForm.email"
                                type="email"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Username</label>
                            <input
                                v-model="editForm.username"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            <select
                                v-model="editForm.role"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="user">User</option>
                                <option value="business_owner">Business Owner</option>
                                <option value="editor">Editor</option>
                                <option value="manager">Manager</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Bio</label>
                            <textarea
                                v-model="editForm.bio"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            ></textarea>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="editForm.is_public"
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700">Public Profile</span>
                            </label>
                        </div>

                        <div class="border-t pt-4">
                            <label class="flex items-center">
                                <input
                                    v-model="changePassword"
                                    type="checkbox"
                                    class="rounded border-gray-300"
                                />
                                <span class="ml-2 text-sm text-gray-700">Change Password</span>
                            </label>
                        </div>

                        <div v-if="changePassword" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">New Password</label>
                                <input
                                    v-model="passwordForm.password"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    minlength="8"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                <input
                                    v-model="passwordForm.password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                    minlength="8"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                        >
                            {{ processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Create User Modal -->
<Modal :show="showCreateModal" @close="showCreateModal = false">
    <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
            Add New User
        </h3>

        <form @submit.prevent="createUser">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                        v-model="createForm.name"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input
                        v-model="createForm.email"
                        type="email"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Username (optional)</label>
                    <input
                        v-model="createForm.username"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select
                        v-model="createForm.role"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        required
                    >
                        <option value="">Select a role</option>
                        <option value="user">User</option>
                        <option value="business_owner">Business Owner</option>
                        <option value="editor">Editor</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input
                        v-model="createForm.password"
                        type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        minlength="8"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input
                        v-model="createForm.password_confirmation"
                        type="password"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        minlength="8"
                        required
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    type="button"
                    @click="showCreateModal = false"
                    class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="processing"
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                >
                    {{ processing ? 'Creating...' : 'Create User' }}
                </button>
            </div>
        </form>
    </div>
</Modal>



        <!-- Bulk Role Change Modal -->
        <Modal :show="showBulkRoleModal" @close="showBulkRoleModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Change Role for {{ selectedUsers.length }} Users
                </h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Role</label>
                    <select
                        v-model="bulkRole"
                        class="w-full rounded-md border-gray-300 shadow-sm"
                    >
                        <option value="">Select a role</option>
                        <option value="user">User</option>
                        <option value="business_owner">Business Owner</option>
                        <option value="editor">Editor</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <button
                        @click="showBulkRoleModal = false"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                    >
                        Cancel
                    </button>
                    <button
                        @click="bulkUpdateRoles"
                        :disabled="!bulkRole || processing"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                    >
                        Update Roles
                    </button>
                </div>
            </div>
        </Modal>
     </AdminDashboardLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
const axios = window.axios
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

// Get the current page props
const page = usePage()

// Data
const users = ref({ 
    data: [], 
    links: [],
    meta: {
        from: 0,
        to: 0,
        total: 0
    }
})
const stats = ref({
    total_users: 0,
    by_role: {},
})
const filters = reactive({
    search: '',
    role: '',
    sort_by: 'created_at',
    sort_order: 'desc',
})

// UI State
const showEditModal = ref(false)
const showBulkRoleModal = ref(false)
const showCreateModal = ref(false)
const processing = ref(false)
const selectedUsers = ref([])
const editingUser = ref(null)
const changePassword = ref(false)
const bulkRole = ref('')

// Forms
const editForm = reactive({
    name: '',
    email: '',
    username: '',
    role: '',
    bio: '',
    is_public: true,
})

const createForm = reactive({
    name: '',
    email: '',
    username: '',
    role: '',
    password: '',
    password_confirmation: '',
})

const passwordForm = reactive({
    password: '',
    password_confirmation: '',
})

// Computed
const isAllSelected = computed(() => {
    return users.value.data.length > 0 && 
           selectedUsers.value.length === users.value.data.filter(u => u.id !== page.props.auth.user.id).length
})

// Methods
const fetchUsers = async () => {
    try {
        const response = await axios.get('/admin-data/users', { params: filters })
        
        // Handle pagination data structure
        if (response.data && response.data.data) {
            users.value = {
                data: response.data.data,
                links: response.data.links || [],
                meta: {
                    from: response.data.from || 0,
                    to: response.data.to || 0,
                    total: response.data.total || 0,
                    current_page: response.data.current_page || 1,
                    last_page: response.data.last_page || 1,
                    per_page: response.data.per_page || 20,
                }
            }
        }
    } catch (error) {
        console.error('Error fetching users:', error)
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/admin-data/users/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchUsers()
}, 300)

const createUser = async () => {
    processing.value = true
    try {
        await axios.post('/admin-data/users', createForm)
        
        // Reset form
        Object.keys(createForm).forEach(key => {
            createForm[key] = ''
        })
        
        showCreateModal.value = false
        fetchUsers()
        fetchStats()
        alert('User created successfully')
    } catch (error) {
        if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat()
            alert('Error: ' + errors.join(', '))
        } else {
            alert('Error creating user: ' + (error.response?.data?.message || 'Unknown error'))
        }
    } finally {
        processing.value = false
    }
}

const editUser = (user) => {
    editingUser.value = user
    Object.assign(editForm, {
        name: user.name,
        email: user.email,
        username: user.username || '',
        role: user.role,
        bio: user.bio || '',
        is_public: user.is_public,
    })
    changePassword.value = false
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
    showEditModal.value = true
}

const updateUser = async () => {
    processing.value = true
    try {
        await axios.put(`/admin-data/users/${editingUser.value.id}`, editForm)
        
        if (changePassword.value && passwordForm.password) {
            await axios.put(`/admin-data/users/${editingUser.value.id}/password`, passwordForm)
        }
        
        showEditModal.value = false
        fetchUsers()
        alert('User updated successfully')
    } catch (error) {
        alert('Error updating user: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const deleteUser = async (user) => {
   if (!confirm(`Are you sure you want to delete ${user.name}?`)) return
   
   try {
       await axios.delete(`/admin-data/users/${user.id}`)
       fetchUsers()
       fetchStats()
       alert('User deleted successfully')
   } catch (error) {
       alert('Error deleting user: ' + error.response?.data?.message)
   }
}

const toggleSelectAll = () => {
   if (isAllSelected.value) {
       selectedUsers.value = []
   } else {
       selectedUsers.value = users.value.data
           .filter(u => u.id !== page.props.auth.user.id)
           .map(u => u.id)
   }
}

const bulkUpdateRoles = async () => {
   if (!bulkRole.value) return
   
   processing.value = true
   try {
       await axios.post('/admin-data/users/bulk-update', {
           user_ids: selectedUsers.value,
           role: bulkRole.value,
           action: 'update_role'
       })
       
       showBulkRoleModal.value = false
       selectedUsers.value = []
       bulkRole.value = ''
       fetchUsers()
       alert('Roles updated successfully')
   } catch (error) {
       alert('Error updating roles: ' + error.response?.data?.message)
   } finally {
       processing.value = false
   }
}

const bulkDelete = async () => {
   if (!confirm(`Are you sure you want to delete ${selectedUsers.value.length} users?`)) return
   
   processing.value = true
   try {
       await axios.post('/admin-data/users/bulk-update', {
           user_ids: selectedUsers.value,
           action: 'delete'
       })
       
       selectedUsers.value = []
       fetchUsers()
       fetchStats()
       alert('Users deleted successfully')
   } catch (error) {
       alert('Error deleting users: ' + error.response?.data?.message)
   } finally {
       processing.value = false
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

const formatDate = (dateString) => {
   if (!dateString) return 'N/A'
   return new Date(dateString).toLocaleDateString('en-US', {
       year: 'numeric',
       month: 'short',
       day: 'numeric'
   })
}

// Lifecycle
onMounted(() => {
   fetchUsers()
   fetchStats()
})
</script>