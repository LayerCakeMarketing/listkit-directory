<script setup>
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Button from '@/Components/UI/Button.vue'
import BentoCard from '@/Components/UI/BentoCard.vue'
</script>

<template>
    <Head title="Radiant Template Demo" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Radiant Template Components Demo
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Button Demo Section -->
                        <section class="mb-12">
                            <h3 class="text-2xl font-bold mb-6">Button Components</h3>
                            <div class="space-y-4">
                                <div class="flex gap-4 items-center">
                                    <Button variant="primary">
                                        Primary Button
                                    </Button>
                                    <Button variant="secondary">
                                        Secondary Button
                                    </Button>
                                    <Button variant="outline">
                                        Outline Button
                                    </Button>
                                </div>
                                
                                <div class="flex gap-4 items-center">
                                    <Button variant="primary" disabled>
                                        Disabled Primary
                                    </Button>
                                    <Button variant="primary" href="/dashboard">
                                        Link Button
                                    </Button>
                                </div>
                            </div>
                        </section>

                        <!-- BentoCard Demo Section -->
                        <section>
                            <h3 class="text-2xl font-bold mb-6">BentoCard Components</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <BentoCard
                                    eyebrow="Feature"
                                    title="Easy List Management"
                                    description="Create, organize, and share your lists with intuitive drag-and-drop functionality."
                                    :fade="['bottom']"
                                >
                                    <template #graphic>
                                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500 to-purple-600 opacity-20"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <svg class="w-20 h-20 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                            </svg>
                                        </div>
                                    </template>
                                </BentoCard>

                                <BentoCard
                                    eyebrow="Discovery"
                                    title="Find Amazing Places"
                                    description="Discover new locations through community-curated lists and detailed categories."
                                    :fade="['top']"
                                >
                                    <template #graphic>
                                        <div class="absolute inset-0 bg-gradient-to-br from-green-500 to-blue-600 opacity-20"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <svg class="w-20 h-20 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                        </div>
                                    </template>
                                </BentoCard>

                                <BentoCard
                                    eyebrow="Community"
                                    title="Share & Collaborate"
                                    description="Build a community around your interests by sharing lists and discovering others' favorites."
                                    dark
                                    :fade="['top', 'bottom']"
                                >
                                    <template #graphic>
                                        <div class="absolute inset-0 bg-gradient-to-br from-purple-500 to-pink-600 opacity-30"></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <svg class="w-20 h-20 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                            </svg>
                                        </div>
                                    </template>
                                </BentoCard>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>