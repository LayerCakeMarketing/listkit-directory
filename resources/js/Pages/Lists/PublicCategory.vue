<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Logo from '@/Components/Logo.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    lists: Object,
    category: Object,
    filters: {
        type: Object,
        default: () => ({})
    }
});

const page = usePage();

// Determine which layout to use based on authentication
const layoutComponent = computed(() => {
    return page.props.auth?.user ? AuthenticatedLayout : PublicLayout;
});

const filters = ref({
    search: props.filters.search || '',
    sort: props.filters.sort || 'latest'
});

const getListUrl = (list) => {
    return `/${list.user.custom_url || list.user.username}/${list.slug}`;
};

const handleListClick = (list) => {
    // Check if user is authenticated via Inertia page props
    if (!page.props.auth || !page.props.auth.user) {
        // Redirect to login with message
        router.visit('/login', {
            data: {
                message: 'Sign up to view full lists and save your favorites.',
                redirect_to: getListUrl(list)
            }
        });
        return;
    }
    
    // User is authenticated, navigate to list
    router.visit(getListUrl(list));
};

const applyFilters = () => {
    const params = new URLSearchParams();
    
    if (filters.value.search) {
        params.append('search', filters.value.search);
    }
    if (filters.value.sort) {
        params.append('sort', filters.value.sort);
    }
    
    const url = `/lists/category/${props.category.slug}${params.toString() ? '?' + params.toString() : ''}`;
    router.visit(url, { preserveState: true });
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const truncateText = (text, length = 100) => {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
};
</script>

<template>
    <Head :title="`${category.name} Lists`" />
    
    <component :is="layoutComponent">
        <!-- Breadcrumb for authenticated users -->
        <nav v-if="$page.props.auth.user" class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center space-x-2 py-3 text-sm">
                    <Link href="/" class="text-gray-500 hover:text-gray-700">Home</Link>
                    <span class="text-gray-400">/</span>
                    <Link href="/lists" class="text-gray-500 hover:text-gray-700">Lists</Link>
                    <span class="text-gray-400">/</span>
                    <span class="text-gray-900">{{ category.name }}</span>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Category Header -->
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center space-x-4 mb-4">
                        <div 
                            class="w-12 h-12 rounded-lg flex items-center justify-center text-white text-xl font-bold"
                            :style="{ backgroundColor: category.color }"
                        >
                            {{ category.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ category.name }}</h1>
                            <p v-if="category.description" class="text-gray-600 mt-1">{{ category.description }}</p>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="flex items-center space-x-6 text-sm text-gray-500">
                        <span>{{ lists.total }} {{ lists.total === 1 ? 'list' : 'lists' }}</span>
                        <Link href="/lists" class="text-blue-600 hover:text-blue-800">← Back to all lists</Link>
                    </div>
                </div>
            </div>

            <!-- Search and Sort -->
            <div class="mb-8">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Search -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search in {{ category.name }}</label>
                            <input
                                v-model="filters.search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Search by title or description..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        
                        <!-- Sort -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <select
                                v-model="filters.sort"
                                @change="applyFilters"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="latest">Most Recent</option>
                                <option value="views">Most Viewed</option>
                                <option value="items">Most Items</option>
                                <option value="name">Alphabetical</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-between items-center">
                        <button
                            @click="applyFilters"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Apply Filters
                        </button>
                        
                        <div class="text-sm text-gray-500">
                            Showing {{ lists.data.length }} of {{ lists.total }} lists
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lists Grid -->
            <div class="mb-8">
                <div v-if="lists.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="list in lists.data" 
                        :key="list.id"
                        @click="handleListClick(list)"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                    >
                        <!-- Featured Image -->
                        <div v-if="list.featured_image" class="h-48 bg-gray-200">
                            <img 
                                :src="list.featured_image" 
                                :alt="list.name"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div v-else class="h-48 bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                            <div class="text-white text-2xl font-bold">
                                {{ list.name.charAt(0).toUpperCase() }}
                            </div>
                        </div>
                        
                        <div class="p-6">
                            <!-- Item Count -->
                            <div class="flex items-center justify-between mb-3">
                                <span 
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                    :style="{ 
                                        backgroundColor: category.color + '20', 
                                        color: category.color 
                                    }"
                                >
                                    {{ category.name }}
                                </span>
                                <span class="text-gray-500 text-sm">{{ list.items_count }} items</span>
                            </div>
                            
                            <!-- List Title -->
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ list.name }}</h3>
                            
                            <!-- Description -->
                            <div class="text-gray-600 text-sm mb-4 line-clamp-2">
                                {{ truncateText(list.description) }}
                            </div>
                            
                            <!-- Tags -->
                            <div v-if="list.tags && list.tags.length > 0" class="flex flex-wrap gap-1 mb-4">
                                <span
                                    v-for="tag in list.tags.slice(0, 3)"
                                    :key="tag.id"
                                    class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                                    :style="{ 
                                        backgroundColor: tag.color + '20', 
                                        color: tag.color 
                                    }"
                                >
                                    {{ tag.name }}
                                </span>
                                <span v-if="list.tags.length > 3" class="text-xs text-gray-500">
                                    +{{ list.tags.length - 3 }} more
                                </span>
                            </div>

                            <!-- Creator and Date -->
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <div class="flex items-center space-x-2">
                                    <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                        <span class="text-xs font-medium">
                                            {{ list.user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <span>by {{ list.user.name }}</span>
                                </div>
                                <span>{{ formatDate(list.updated_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-12">
                    <div class="text-gray-500 text-lg">No {{ category.name.toLowerCase() }} lists found.</div>
                    <p class="text-gray-400 mt-2">Be the first to create a {{ category.name.toLowerCase() }} list!</p>
                    <Link
                        href="/register"
                        class="inline-block mt-4 bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors"
                    >
                        Get Started
                    </Link>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="lists.links && lists.links.length > 3" class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    <template v-for="(link, index) in lists.links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                link.active 
                                    ? 'bg-blue-600 text-white' 
                                    : 'text-gray-700 hover:bg-gray-100'
                            ]"
                        />
                        <span
                            v-else
                            v-html="link.label"
                            :class="[
                                'px-3 py-2 text-sm rounded-md transition-colors',
                                link.active 
                                    ? 'bg-blue-600 text-white' 
                                    : 'text-gray-400 cursor-not-allowed'
                            ]"
                        />
                    </template>
                </nav>
            </div>
        </div>

        <!-- Sign up prompt for non-authenticated users -->
        <div v-if="!page.props.auth?.user" class="fixed bottom-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg">
            <div class="flex items-center space-x-3">
                <div>
                    <div class="font-medium">Sign up to view full lists!</div>
                    <div class="text-sm text-blue-100">Create your own lists and save favorites</div>
                </div>
                <Link
                    href="/register"
                    class="bg-white text-blue-600 px-4 py-2 rounded font-medium hover:bg-gray-100 transition-colors"
                >
                    Join Now
                </Link>
            </div>
        </div>
    </component>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>