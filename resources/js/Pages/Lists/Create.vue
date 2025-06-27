<template>
    <Head title="Create New List" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New List</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="createList">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">List Name *</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                    required
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300"
                                ></textarea>
                            </div>

                            <div>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.is_public"
                                        type="checkbox"
                                        class="rounded border-gray-300"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Make this list public</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <Link
                                :href="route('lists.my')"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="processing"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
                            >
                                {{ processing ? 'Creating...' : 'Create List' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const axios = window.axios
const page = usePage()

const form = reactive({
    name: '',
    description: '',
    is_public: true
})

const processing = ref(false)

const createList = async () => {
    processing.value = true
    try {
        const response = await axios.post('/data/lists', form)
        router.visit(route('lists.edit', [page.props.auth.user.username, response.data.list.slug]))
    } catch (error) {
        const errorMessage = error.response?.data?.message || error.response?.data?.error || error.message || 'An unknown error occurred'
        alert('Error creating list: ' + errorMessage)
        processing.value = false
    }
}
</script>