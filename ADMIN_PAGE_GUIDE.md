# Creating New Admin Pages Guide

This guide explains how to create new administration pages using the AdminDashboardLayout.vue component in your Laravel + Vue.js application.

## Step-by-Step Guide

### 1. Create the API Controller (if needed)

First, create an API controller to handle data operations for your admin page.

**Location:** `app/Http/Controllers/Api/Admin/`

```php
<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\YourModel;
use Illuminate\Http\Request;

class YourModelManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = YourModel::query();

        // Add search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        // Add filtering
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Add sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $items = $query->paginate(20);

        return response()->json($items);
    }

    public function stats()
    {
        return response()->json([
            'total_items' => YourModel::count(),
            'active_items' => YourModel::where('status', 'active')->count(),
            'inactive_items' => YourModel::where('status', 'inactive')->count(),
        ]);
    }

    public function show($id)
    {
        $item = YourModel::findOrFail($id);
        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = YourModel::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $item->update($validated);

        return response()->json([
            'message' => 'Item updated successfully',
            'item' => $item,
        ]);
    }

    public function destroy($id)
    {
        $item = YourModel::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Item deleted successfully',
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'required|array',
            'item_ids.*' => 'exists:your_table,id',
            'action' => 'required|string|in:delete,update_status',
            'status' => 'required_if:action,update_status|in:active,inactive',
        ]);

        switch ($validated['action']) {
            case 'delete':
                YourModel::whereIn('id', $validated['item_ids'])->delete();
                $message = 'Items deleted successfully';
                break;

            case 'update_status':
                YourModel::whereIn('id', $validated['item_ids'])
                    ->update(['status' => $validated['status']]);
                $message = 'Status updated successfully';
                break;

            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }

        return response()->json(['message' => $message]);
    }
}
```

### 2. Add Routes

**Location:** `routes/web.php`

Add the import at the top:
```php
use App\Http\Controllers\Api\Admin\YourModelManagementController;
```

Add the page route in the admin section:
```php
Route::middleware(['auth', 'verified', 'role:admin,manager'])->prefix('admin')->name('admin.')->group(function () {
    // ... existing routes ...
    
    Route::get('/your-page', function () {
        return Inertia::render('Admin/YourPage/Index');
    })->name('your-page');
});
```

Add the API routes in the admin-data section:
```php
Route::middleware(['auth', 'role:admin,manager'])->prefix('admin-data')->group(function () {
    // ... existing routes ...
    
    // Your model management
    Route::get('/your-items', [YourModelManagementController::class, 'index']);
    Route::get('/your-items/stats', [YourModelManagementController::class, 'stats']);
    Route::get('/your-items/{item}', [YourModelManagementController::class, 'show']);
    Route::put('/your-items/{item}', [YourModelManagementController::class, 'update']);
    Route::delete('/your-items/{item}', [YourModelManagementController::class, 'destroy']);
    Route::post('/your-items/bulk-update', [YourModelManagementController::class, 'bulkUpdate']);
});
```

### 3. Create the Vue Component

**Location:** `resources/js/Pages/Admin/YourPage/Index.vue`

```vue
<template>
    <Head title="Your Page Management" />
    
    <AdminDashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Your Page Management</h2>
        </template>
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-900">Your Items</h2>
                        <button
                            @click="showCreateModal = true"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        >
                            Add New Item
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Total Items</div>
                            <div class="text-2xl font-bold">{{ stats.total_items }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Active</div>
                            <div class="text-2xl font-bold">{{ stats.active_items }}</div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded">
                            <div class="text-sm text-gray-500">Inactive</div>
                            <div class="text-2xl font-bold">{{ stats.inactive_items }}</div>
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
                                placeholder="Search items..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="filters.status"
                                @change="fetchItems"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                            <select
                                v-model="filters.sort_by"
                                @change="fetchItems"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="created_at">Created Date</option>
                                <option value="updated_at">Updated Date</option>
                                <option value="name">Name</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                            <select
                                v-model="filters.sort_order"
                                @change="fetchItems"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="desc">Descending</option>
                                <option value="asc">Ascending</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Bulk Actions -->
                        <div v-if="selectedItems.length > 0" class="mb-4 p-4 bg-blue-50 rounded">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-blue-700">
                                    {{ selectedItems.length }} item(s) selected
                                </span>
                                <div class="space-x-2">
                                    <button
                                        @click="bulkUpdateStatus('active')"
                                        class="text-sm bg-green-500 hover:bg-green-700 text-white px-3 py-1 rounded"
                                    >
                                        Mark Active
                                    </button>
                                    <button
                                        @click="bulkUpdateStatus('inactive')"
                                        class="text-sm bg-yellow-500 hover:bg-yellow-700 text-white px-3 py-1 rounded"
                                    >
                                        Mark Inactive
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
                                            Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input
                                                type="checkbox"
                                                :value="item.id"
                                                v-model="selectedItems"
                                                class="rounded border-gray-300"
                                            />
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ item.name }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span 
                                                :class="getStatusBadgeClass(item.status)"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ item.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ formatDate(item.created_at) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <button
                                                @click="editItem(item)"
                                                class="text-blue-600 hover:text-blue-900 mr-3"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                @click="deleteItem(item)"
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
                                    ...items.meta,
                                    links: items.links,
                                    from: items.meta?.from || 0,
                                    to: items.meta?.to || 0,
                                    total: items.meta?.total || 0
                                }" 
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingItem ? 'Edit Item' : 'Create Item' }}
                </h3>

                <form @submit.prevent="saveItem">
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
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select
                                v-model="editForm.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
                            {{ processing ? 'Saving...' : 'Save' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminDashboardLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
const axios = window.axios
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { debounce } from 'lodash'

// Data
const items = ref({ 
    data: [], 
    links: [],
    meta: { from: 0, to: 0, total: 0 }
})
const stats = ref({
    total_items: 0,
    active_items: 0,
    inactive_items: 0,
})
const filters = reactive({
    search: '',
    status: '',
    sort_by: 'created_at',
    sort_order: 'desc',
})

// UI State
const showEditModal = ref(false)
const showCreateModal = ref(false)
const processing = ref(false)
const selectedItems = ref([])
const editingItem = ref(null)

// Forms
const editForm = reactive({
    name: '',
    status: 'active',
})

// Computed
const isAllSelected = computed(() => {
    return items.value.data.length > 0 && 
           selectedItems.value.length === items.value.data.length
})

// Methods
const fetchItems = async () => {
    try {
        const response = await axios.get('/admin-data/your-items', { params: filters })
        
        if (response.data && response.data.data) {
            items.value = {
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
        console.error('Error fetching items:', error)
    }
}

const fetchStats = async () => {
    try {
        const response = await axios.get('/admin-data/your-items/stats')
        stats.value = response.data
    } catch (error) {
        console.error('Error fetching stats:', error)
    }
}

const debouncedSearch = debounce(() => {
    fetchItems()
}, 300)

const editItem = (item) => {
    editingItem.value = item
    Object.assign(editForm, {
        name: item.name,
        status: item.status,
    })
    showEditModal.value = true
}

const saveItem = async () => {
    processing.value = true
    try {
        if (editingItem.value) {
            await axios.put(`/admin-data/your-items/${editingItem.value.id}`, editForm)
        } else {
            await axios.post('/admin-data/your-items', editForm)
        }
        
        showEditModal.value = false
        fetchItems()
        fetchStats()
        alert('Item saved successfully')
    } catch (error) {
        alert('Error saving item: ' + error.response?.data?.message)
    } finally {
        processing.value = false
    }
}

const deleteItem = async (item) => {
   if (!confirm(`Are you sure you want to delete "${item.name}"?`)) return
   
   try {
       await axios.delete(`/admin-data/your-items/${item.id}`)
       fetchItems()
       fetchStats()
       alert('Item deleted successfully')
   } catch (error) {
       alert('Error deleting item: ' + error.response?.data?.message)
   }
}

const toggleSelectAll = () => {
   if (isAllSelected.value) {
       selectedItems.value = []
   } else {
       selectedItems.value = items.value.data.map(i => i.id)
   }
}

const bulkUpdateStatus = async (status) => {
   if (!confirm(`Update status to ${status} for ${selectedItems.value.length} items?`)) return
   
   processing.value = true
   try {
       await axios.post('/admin-data/your-items/bulk-update', {
           item_ids: selectedItems.value,
           status: status,
           action: 'update_status'
       })
       
       selectedItems.value = []
       fetchItems()
       alert('Status updated successfully')
   } catch (error) {
       alert('Error updating status: ' + error.response?.data?.message)
   } finally {
       processing.value = false
   }
}

const bulkDelete = async () => {
   if (!confirm(`Are you sure you want to delete ${selectedItems.value.length} items?`)) return
   
   processing.value = true
   try {
       await axios.post('/admin-data/your-items/bulk-update', {
           item_ids: selectedItems.value,
           action: 'delete'
       })
       
       selectedItems.value = []
       fetchItems()
       fetchStats()
       alert('Items deleted successfully')
   } catch (error) {
       alert('Error deleting items: ' + error.response?.data?.message)
   } finally {
       processing.value = false
   }
}

const getStatusBadgeClass = (status) => {
   const classes = {
       active: 'bg-green-100 text-green-800',
       inactive: 'bg-red-100 text-red-800',
   }
   return classes[status] || 'bg-gray-100 text-gray-800'
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
   fetchItems()
   fetchStats()
})
</script>
```

### 4. Register in Navigation

**Location:** `resources/js/Layouts/AdminDashboardLayout.vue`

Add your new page to the `managementSections` computed property:

```javascript
const managementSections = computed(() => [
  // ... existing sections ...
  { 
    id: 5, 
    name: 'Your Page', 
    href: 'admin.your-page', 
    initial: 'Y', 
    current: page.url.includes('/admin/your-page') 
  },
])
```

## Key Components Explained

### AdminDashboardLayout Structure

The AdminDashboardLayout provides:
- Responsive sidebar navigation
- Header slot for page titles
- Consistent styling and layout
- Navigation state management

### Required Props/Slots

- `#header` slot: For page title and breadcrumbs
- Main content goes in the default slot

### CSS Grid Classes for Stats

Use these responsive grid classes for stats blocks:
- `grid-cols-1 md:grid-cols-3` - For 3 columns on desktop
- `grid-cols-1 md:grid-cols-4` - For 4 columns on desktop
- `grid-cols-1 md:grid-cols-5` - For 5 columns on desktop

### Authentication & Authorization

All admin routes require:
- `auth` middleware (user must be logged in)
- `role:admin,manager` middleware (user must have admin or manager role)

## Common Patterns

### Stats Display
```vue
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
    <div class="bg-gray-50 p-4 rounded">
        <div class="text-sm text-gray-500">Label</div>
        <div class="text-2xl font-bold">{{ stat_value }}</div>
    </div>
</div>
```

### Filter Section
```vue
<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
        <input v-model="filters.search" @input="debouncedSearch" />
    </div>
</div>
```

### Data Table
```vue
<table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <!-- Table headers -->
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        <!-- Table rows -->
    </tbody>
</table>
```

## File Structure

```
app/
├── Http/Controllers/Api/Admin/
│   └── YourModelManagementController.php
resources/js/
├── Layouts/
│   └── AdminDashboardLayout.vue
└── Pages/Admin/
    └── YourPage/
        └── Index.vue
routes/
└── web.php
```

## Tips

1. **Consistent Naming**: Use consistent naming patterns for routes, controllers, and components
2. **Error Handling**: Always include try-catch blocks for API calls
3. **User Feedback**: Provide clear success/error messages
4. **Responsive Design**: Test your layout on different screen sizes
5. **Accessibility**: Include proper labels and ARIA attributes
6. **Performance**: Use debounced search and pagination for large datasets

## Testing

1. Test the page at `http://localhost:8000/admin/your-page`
2. Verify authentication (redirect to login if not authenticated)
3. Test filtering, sorting, and search functionality
4. Test bulk operations
5. Test CRUD operations
6. Verify responsive layout on mobile devices