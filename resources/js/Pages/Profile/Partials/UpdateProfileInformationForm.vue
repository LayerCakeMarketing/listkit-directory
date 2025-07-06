<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DirectCloudflareUpload from '@/Components/ImageUpload/DirectCloudflareUpload.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
    custom_url: user.custom_url || '',
    avatar_image: null,
    cover_image: null,
    page_logo_image: null,
});

// Image state
const avatarImage = ref(null);
const coverImage = ref(null);
const pageLogoImage = ref(null);

// Handle image uploads
const handleAvatarUpload = (image) => {
    avatarImage.value = image;
    form.avatar_image = image;
};

const handleCoverUpload = (image) => {
    coverImage.value = image;
    form.cover_image = image;
};

const handlePageLogoUpload = (image) => {
    pageLogoImage.value = image;
    form.page_logo_image = image;
};

const siteUrl = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin;
    }
    return 'http://localhost:8000'; // fallback for SSR
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Update your account's profile information and email address.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <!-- Profile Images Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <InputLabel value="Profile Picture" />
                    <DirectCloudflareUpload
                        v-model="avatarImage"
                        label="Upload Profile Picture"
                        upload-type="avatar"
                        :entity-id="user.id"
                        :max-size-m-b="14"
                        :current-image-url="user.avatar_url"
                        @upload-complete="handleAvatarUpload"
                        class="mt-1"
                    />
                    <InputError class="mt-2" :message="form.errors.avatar_image" />
                </div>

                <div>
                    <InputLabel value="Page Logo" />
                    <DirectCloudflareUpload
                        v-model="pageLogoImage"
                        label="Upload Page Logo"
                        upload-type="page_logo"
                        :entity-id="user.id"
                        :max-size-m-b="14"
                        :current-image-url="user.page_logo_url"
                        @upload-complete="handlePageLogoUpload"
                        class="mt-1"
                    />
                    <InputError class="mt-2" :message="form.errors.page_logo_image" />
                </div>

                <div>
                    <InputLabel value="Cover Image" />
                    <DirectCloudflareUpload
                        v-model="coverImage"
                        label="Upload Cover Image"
                        upload-type="cover"
                        :entity-id="user.id"
                        :max-size-m-b="14"
                        :current-image-url="user.cover_url"
                        @upload-complete="handleCoverUpload"
                        class="mt-1"
                    />
                    <InputError class="mt-2" :message="form.errors.cover_image" />
                </div>
            </div>

            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="custom_url" value="Custom URL" />
                
                <div class="mt-1">
                    <div class="flex rounded-md shadow-sm">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                            {{ siteUrl }}/
                        </span>
                        <TextInput
                            id="custom_url"
                            type="text"
                            class="flex-1 rounded-none rounded-r-md border-gray-300"
                            v-model="form.custom_url"
                            placeholder="your-custom-url"
                            autocomplete="off"
                        />
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Choose a custom URL for your public profile and lists. Only letters, numbers, and hyphens allowed.
                    </p>
                </div>

                <InputError class="mt-2" :message="form.errors.custom_url" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-green-600"
                    >
                        Profile updated successfully!
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
