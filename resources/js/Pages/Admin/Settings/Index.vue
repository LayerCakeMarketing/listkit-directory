<template>
    <Head title="Settings" />
    
    <AdminDashboardLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                System Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash?.success" class="mb-4 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ $page.props.flash.success }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Settings Groups -->
                <div class="space-y-6">
                    <!-- Security Settings -->
                    <div v-if="settings?.security" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Security Settings</h3>
                            
                            <div class="space-y-6">
                                <!-- Registration Toggle -->
                                <div v-for="setting in settings.security" :key="setting.key">
                                    <div v-if="setting.type === 'boolean'" class="flex items-center justify-between">
                                        <div class="flex-grow">
                                            <label :for="setting.key" class="text-sm font-medium text-gray-700">
                                                {{ formatLabel(setting.key) }}
                                            </label>
                                            <p v-if="setting.description" class="text-sm text-gray-500">
                                                {{ setting.description }}
                                            </p>
                                        </div>
                                        <button
                                            :id="setting.key"
                                            type="button"
                                            @click="toggleSetting(setting)"
                                            :class="[
                                                setting.value ? 'bg-indigo-600' : 'bg-gray-200',
                                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2'
                                            ]"
                                            role="switch"
                                            :aria-checked="setting.value"
                                        >
                                            <span
                                                :class="[
                                                    setting.value ? 'translate-x-5' : 'translate-x-0',
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                ]"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- General Settings -->
                    <div v-if="settings?.general" class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">General Settings</h3>
                            
                            <form @submit.prevent="saveGeneralSettings">
                                <div class="space-y-6">
                                    <div v-for="setting in settings.general" :key="setting.key">
                                        <!-- String inputs -->
                                        <div v-if="setting.type === 'string'">
                                            <label :for="setting.key" class="block text-sm font-medium text-gray-700">
                                                {{ formatLabel(setting.key) }}
                                            </label>
                                            <input
                                                :id="setting.key"
                                                v-model="generalSettings[setting.key]"
                                                type="text"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            />
                                            <p v-if="setting.description" class="mt-1 text-sm text-gray-500">
                                                {{ setting.description }}
                                            </p>
                                        </div>
                                        
                                        <!-- Boolean toggles -->
                                        <div v-else-if="setting.type === 'boolean'" class="flex items-center justify-between">
                                            <div class="flex-grow">
                                                <label :for="setting.key" class="text-sm font-medium text-gray-700">
                                                    {{ formatLabel(setting.key) }}
                                                </label>
                                                <p v-if="setting.description" class="text-sm text-gray-500">
                                                    {{ setting.description }}
                                                </p>
                                            </div>
                                            <button
                                                :id="setting.key"
                                                type="button"
                                                @click="toggleGeneralSetting(setting.key)"
                                                :class="[
                                                    generalSettings[setting.key] ? 'bg-indigo-600' : 'bg-gray-200',
                                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2'
                                                ]"
                                                role="switch"
                                                :aria-checked="generalSettings[setting.key]"
                                            >
                                                <span
                                                    :class="[
                                                        generalSettings[setting.key] ? 'translate-x-5' : 'translate-x-0',
                                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                                    ]"
                                                />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <button
                                        type="submit"
                                        :disabled="savingGeneral"
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                    >
                                        <svg v-if="savingGeneral" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ savingGeneral ? 'Saving...' : 'Save General Settings' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminDashboardLayout>
</template>

<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

console.log('Settings page loaded', props.settings);

// Reactive state for general settings form
const generalSettings = reactive({});
const savingGeneral = ref(false);

// Initialize general settings
if (props.settings && props.settings.general) {
    props.settings.general.forEach(setting => {
        generalSettings[setting.key] = setting.value;
    });
}

// Format setting key to label
const formatLabel = (key) => {
    return key
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

// Toggle a boolean setting immediately (for security settings)
const toggleSetting = async (setting) => {
    try {
        const response = await axios.put(`/admin/settings/${setting.key}`, {
            value: !setting.value,
        });
        
        if (response.data.success) {
            // Update the local state
            setting.value = !setting.value;
            
            // Show success message
            router.reload({
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    // Setting updated
                },
            });
        }
    } catch (error) {
        console.error('Error updating setting:', error);
        alert('Failed to update setting. Please try again.');
    }
};

// Toggle general setting in form (doesn't save immediately)
const toggleGeneralSetting = (key) => {
    generalSettings[key] = !generalSettings[key];
};

// Save general settings
const saveGeneralSettings = async () => {
    savingGeneral.value = true;
    
    try {
        const settingsArray = Object.entries(generalSettings).map(([key, value]) => {
            const originalSetting = props.settings?.general?.find(s => s.key === key);
            return {
                key,
                value,
                type: originalSetting?.type || 'string',
            };
        });
        
        router.put('/admin/settings', {
            settings: settingsArray,
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                savingGeneral.value = false;
            },
        });
    } catch (error) {
        console.error('Error saving settings:', error);
        savingGeneral.value = false;
    }
};
</script>