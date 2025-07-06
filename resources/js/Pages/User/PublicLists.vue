<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Logo from '@/Components/Logo.vue';

const props = defineProps({
    user: Object,
});

const lists = ref([]);
const loading = ref(false);

const fetchLists = async () => {
    loading.value = true;
    try {
        const response = await window.axios.get(`/data/users/${props.user.id}/public-lists`);
        lists.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching lists:', error);
        lists.value = [];
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchLists();
});

const visibilityBadge = (visibility) => {
    if (visibility === 'public') {
        return { text: 'Public', class: 'bg-green-100 text-green-800' };
    } else if (visibility === 'unlisted') {
        return { text: 'Unlisted', class: 'bg-yellow-100 text-yellow-800' };
    } else {
        return { text: 'Private', class: 'bg-red-100 text-red-800' };
    }
};
</script>

<template>
    <Head :title="`${user.name}'s Lists`" />
    
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center space-x-4">
                        <Logo href="/" imgClassName="h-8 w-auto" />
                        <span class="text-gray-500">/</span>
                        <h1 class="text-xl text-gray-600">{{ user.name }}'s Lists</h1>
                    </div>
                    
                    <nav class="flex items-center space-x-4">
                        <Link
                            href="/"
                            class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                        >
                            Home
                        </Link>
                        <Link
                            href="/login"
                            class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                        >
                            Sign In
                        </Link>
                        <Link
                            href="/register"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors"
                        >
                            Get Started
                        </Link>
                    </nav>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- User Info -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-16 w-16 bg-gray-300 rounded-full flex items-center justify-center">
                        <span class="text-2xl font-bold text-gray-600">{{ user.name?.charAt(0)?.toUpperCase() || '?' }}</span>
                    </div>
                    <div class="ml-6">
                        <h1 class="text-2xl font-bold text-gray-900">{{ user.name }}</h1>
                        <p class="text-gray-500">@{{ user.custom_url || user.username || `user-${user.id}` }}</p>
                    </div>
                </div>
            </div>

            <!-- Lists -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Public Lists</h2>
                
                <div v-if="loading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                </div>
                
                <div v-else-if="lists.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="list in lists" 
                        :key="list.id"
                        class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
                    >
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-xl font-semibold text-gray-900">{{ list.name }}</h3>
                                <span 
                                    :class="['inline-flex items-center px-2 py-1 rounded-full text-xs font-medium', visibilityBadge(list.visibility).class]"
                                >
                                    {{ visibilityBadge(list.visibility).text }}
                                </span>
                            </div>
                            
                            <p v-if="list.description" class="text-gray-600 text-sm mb-4 line-clamp-2">
                                {{ list.description }}
                            </p>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500">
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        {{ list.items_count || 0 }} items
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 116 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        {{ list.view_count || 0 }} views
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <Link 
                                    :href="`/${user.custom_url || user.username || user.id}/${list.slug}`"
                                    class="text-blue-600 hover:text-blue-800 font-medium"
                                >
                                    View List →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="text-center py-12 bg-white rounded-lg shadow-md">
                    <div class="text-gray-500 text-lg">No public lists yet.</div>
                    <p class="text-gray-400 mt-2">{{ user.name }} hasn't created any public lists.</p>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="mt-12 bg-blue-50 rounded-lg p-8 text-center">
                <h3 class="text-2xl font-bold text-gray-900 mb-4">Create Your Own Lists</h3>
                <p class="text-gray-600 mb-6">Join our community and start creating and sharing your own curated lists.</p>
                <div class="space-x-4">
                    <Link
                        href="/register"
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Sign Up Free
                    </Link>
                    <Link
                        href="/login"
                        class="text-blue-600 hover:text-blue-800 font-semibold"
                    >
                        Already have an account? Sign in
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>