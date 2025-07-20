<template>
    <div class="py-12">
        <div class="mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900">Site Settings</h3>
                    <p class="mt-1 text-sm text-gray-600">Configure site-wide settings and preferences.</p>
                    
                    <!-- Loading State -->
                    <div v-if="loading" class="mt-6">
                        <div class="animate-pulse space-y-4">
                            <div class="h-10 bg-gray-200 rounded"></div>
                            <div class="h-10 bg-gray-200 rounded"></div>
                            <div class="h-10 bg-gray-200 rounded"></div>
                        </div>
                    </div>

                    <!-- Settings Tabs -->
                    <div v-else class="mt-6">
                        <div class="border-b border-gray-200">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button
                                    v-for="group in sortedGroups"
                                    :key="group.key"
                                    @click="activeTab = group.key"
                                    :class="[
                                        activeTab === group.key
                                            ? 'border-indigo-500 text-indigo-600'
                                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                        'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
                                    ]"
                                >
                                    {{ group.label }}
                                </button>
                            </nav>
                        </div>

                        <!-- Settings Form -->
                        <form @submit.prevent="saveSettings" class="mt-6 space-y-6">
                            <div v-for="(setting, key) in currentGroupSettings" :key="key">
                                <!-- Text Input -->
                                <div v-if="setting.type === 'string' && !setting.key.includes('color')" class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start">
                                    <label :for="setting.key" class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">
                                        {{ formatLabel(setting.key) }}
                                    </label>
                                    <div class="mt-1 sm:mt-0 sm:col-span-2">
                                        <input
                                            :id="setting.key"
                                            v-model="formData[setting.key]"
                                            type="text"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                        <p v-if="setting.description" class="mt-2 text-sm text-gray-500">{{ setting.description }}</p>
                                    </div>
                                </div>

                                <!-- Color Picker -->
                                <div v-else-if="setting.key.includes('color')" class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start">
                                    <label :for="setting.key" class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">
                                        {{ formatLabel(setting.key) }}
                                    </label>
                                    <div class="mt-1 sm:mt-0 sm:col-span-2">
                                        <div class="flex items-center space-x-3">
                                            <input
                                                :id="setting.key"
                                                v-model="formData[setting.key]"
                                                type="color"
                                                class="h-10 w-20 rounded border border-gray-300"
                                            />
                                            <input
                                                v-model="formData[setting.key]"
                                                type="text"
                                                class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="#000000"
                                            />
                                        </div>
                                        <p v-if="setting.description" class="mt-2 text-sm text-gray-500">{{ setting.description }}</p>
                                    </div>
                                </div>

                                <!-- Boolean Toggle -->
                                <div v-else-if="setting.type === 'boolean'" class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start">
                                    <label :for="setting.key" class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">
                                        {{ formatLabel(setting.key) }}
                                    </label>
                                    <div class="mt-1 sm:mt-0 sm:col-span-2">
                                        <button
                                            type="button"
                                            @click="formData[setting.key] = !formData[setting.key]"
                                            :class="[
                                                formData[setting.key] ? 'bg-indigo-600' : 'bg-gray-200',
                                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
                                            ]"
                                            role="switch"
                                            :aria-checked="formData[setting.key]"
                                        >
                                            <span
                                                :class="[
                                                    formData[setting.key] ? 'translate-x-5' : 'translate-x-0',
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                ]"
                                            />
                                        </button>
                                        <p v-if="setting.description" class="mt-2 text-sm text-gray-500">{{ setting.description }}</p>
                                    </div>
                                </div>

                                <!-- Number Input -->
                                <div v-else-if="setting.type === 'integer' || setting.type === 'float'" class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start">
                                    <label :for="setting.key" class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">
                                        {{ formatLabel(setting.key) }}
                                    </label>
                                    <div class="mt-1 sm:mt-0 sm:col-span-2">
                                        <input
                                            :id="setting.key"
                                            v-model.number="formData[setting.key]"
                                            type="number"
                                            :step="setting.type === 'float' ? '0.01' : '1'"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                        <p v-if="setting.description" class="mt-2 text-sm text-gray-500">{{ setting.description }}</p>
                                    </div>
                                </div>

                                <!-- Textarea for longer text -->
                                <div v-else-if="setting.type === 'string' && (setting.key.includes('description') || setting.key.includes('message'))" class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start">
                                    <label :for="setting.key" class="block text-sm font-medium text-gray-700 sm:mt-px sm:pt-2">
                                        {{ formatLabel(setting.key) }}
                                    </label>
                                    <div class="mt-1 sm:mt-0 sm:col-span-2">
                                        <textarea
                                            :id="setting.key"
                                            v-model="formData[setting.key]"
                                            rows="3"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                        <p v-if="setting.description" class="mt-2 text-sm text-gray-500">{{ setting.description }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Save Button -->
                            <div class="pt-5">
                                <div class="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        @click="resetCurrentGroup"
                                        class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        Reset to Defaults
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="saving"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
                                    >
                                        <span v-if="saving">Saving...</span>
                                        <span v-else>Save Changes</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useNotification } from '@/composables/useNotification'

// State
const loading = ref(true)
const saving = ref(false)
const activeTab = ref('general')
const settings = ref({})
const groups = ref({})
const formData = ref({})

const { showSuccess, showError } = useNotification()

// Computed
const sortedGroups = computed(() => {
    return Object.entries(groups.value)
        .sort((a, b) => a[1].order - b[1].order)
        .map(([key, group]) => ({ key, ...group }))
})

const currentGroupSettings = computed(() => {
    return settings.value[activeTab.value] || {}
})

// Methods
const formatLabel = (key) => {
    return key
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
}

const loadSettings = async () => {
    try {
        loading.value = true
        const response = await axios.get('/api/admin/settings')
        settings.value = response.data.settings
        groups.value = response.data.groups
        
        // Initialize form data
        Object.entries(settings.value).forEach(([group, groupSettings]) => {
            Object.entries(groupSettings).forEach(([key, setting]) => {
                formData.value[key] = setting.value
            })
        })
    } catch (error) {
        console.error('Failed to load settings:', error)
        alert('Failed to load settings. Please try again.')
    } finally {
        loading.value = false
    }
}

const saveSettings = async () => {
    try {
        saving.value = true
        
        // Prepare settings for current group
        const settingsToUpdate = []
        Object.entries(currentGroupSettings.value).forEach(([key, setting]) => {
            settingsToUpdate.push({
                key: setting.key,
                value: formData.value[setting.key],
                type: setting.type
            })
        })
        
        await axios.post('/api/admin/settings', { settings: settingsToUpdate })
        
        // Update local settings with new values
        settingsToUpdate.forEach(setting => {
            if (settings.value[activeTab.value][setting.key]) {
                settings.value[activeTab.value][setting.key].value = setting.value
            }
        })
        
        showSuccess('Saved', 'Settings saved successfully!')
    } catch (error) {
        console.error('Failed to save settings:', error)
        showError('Error', 'Failed to save settings. Please try again.')
    } finally {
        saving.value = false
    }
}

const resetCurrentGroup = async () => {
    if (!confirm('Are you sure you want to reset these settings to their default values?')) {
        return
    }
    
    try {
        saving.value = true
        await axios.post('/api/admin/settings/reset', { groups: [activeTab.value] })
        await loadSettings()
        alert('Settings reset to defaults successfully!')
    } catch (error) {
        console.error('Failed to reset settings:', error)
        alert('Failed to reset settings. Please try again.')
    } finally {
        saving.value = false
    }
}

// Lifecycle
onMounted(() => {
    loadSettings()
})
</script>