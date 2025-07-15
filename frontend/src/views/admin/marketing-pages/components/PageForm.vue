<template>
    <div class="p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ page ? 'Edit Page' : 'Create New Page' }}
        </h3>

        <form @submit.prevent="savePage">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title *</label>
                    <input
                        v-model="form.title"
                        type="text"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        @input="generateSlug"
                    />
                    <div v-if="errors.title" class="text-red-500 text-sm mt-1">{{ errors.title }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                    <input
                        v-model="form.slug"
                        type="text"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="auto-generated-from-title"
                    />
                    <div v-if="errors.slug" class="text-red-500 text-sm mt-1">{{ errors.slug }}</div>
                    <p class="text-xs text-gray-500 mt-1">Leave empty to auto-generate from title</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                    <RichTextEditor 
                        v-model="form.content" 
                        placeholder="Enter page content..."
                    />
                    <div v-if="errors.content" class="text-red-500 text-sm mt-1">{{ errors.content }}</div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select
                        v-model="form.status"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                    <div v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</div>
                </div>

                <div class="border-t pt-4">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">SEO Settings</h4>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                            <input
                                v-model="form.meta_title"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Leave empty to use page title"
                            />
                            <div v-if="errors.meta_title" class="text-red-500 text-sm mt-1">{{ errors.meta_title }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                            <textarea
                                v-model="form.meta_description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Brief description for search engines"
                            ></textarea>
                            <div v-if="errors.meta_description" class="text-red-500 text-sm mt-1">{{ errors.meta_description }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button
                    type="button"
                    @click="$emit('cancel')"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    :disabled="processing"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                    {{ processing ? 'Saving...' : 'Save Page' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import RichTextEditor from '@/components/RichTextEditor.vue'

const props = defineProps({
    page: {
        type: Object,
        default: null
    }
})

const emit = defineEmits(['saved', 'cancel'])

// State
const processing = ref(false)
const errors = ref({})

// Form
const form = reactive({
    title: '',
    slug: '',
    content: '',
    status: 'draft',
    meta_title: '',
    meta_description: ''
})

// Methods
const generateSlug = () => {
    if (!props.page && form.title) {
        form.slug = form.title
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
    }
}

const savePage = async () => {
    processing.value = true
    errors.value = {}

    try {
        console.log('Saving page:', form)
        const response = props.page 
            ? await axios.put(`/api/admin/marketing-pages/${props.page.id}`, form)
            : await axios.post('/api/admin/marketing-pages', form)
        
        console.log('Save response:', response.data)
        emit('saved')
    } catch (error) {
        console.error('Save error:', error.response || error)
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors
        } else if (error.response?.data?.message) {
            alert(`Error: ${error.response.data.message}`)
        } else {
            alert('Error saving page. Please check the console for details.')
        }
    } finally {
        processing.value = false
    }
}

// Initialize form with page data
onMounted(() => {
    if (props.page) {
        Object.assign(form, {
            title: props.page.title || '',
            slug: props.page.slug || '',
            content: props.page.content || '',
            status: props.page.status || 'draft',
            meta_title: props.page.meta_title || '',
            meta_description: props.page.meta_description || ''
        })
    }
})
</script>