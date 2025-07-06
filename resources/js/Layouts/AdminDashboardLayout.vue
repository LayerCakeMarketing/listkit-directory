<template>
  <div>
    <TransitionRoot as="template" :show="sidebarOpen">
      <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
        <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-gray-900/80" />
        </TransitionChild>

        <div class="fixed inset-0 flex">
          <TransitionChild as="template" enter="transition ease-in-out duration-300 transform" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0" leave-to="-translate-x-full">
            <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
              <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-300" leave-from="opacity-100" leave-to="opacity-0">
                <div class="absolute top-0 left-full flex w-16 justify-center pt-5">
                  <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                    <span class="sr-only">Close sidebar</span>
                    <XMarkIcon class="size-6 text-white" aria-hidden="true" />
                  </button>
                </div>
              </TransitionChild>

              <!-- Sidebar component for mobile -->
              <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                  <Link :href="route('dashboard')">
                    <ApplicationLogo class="h-8 w-auto fill-current text-indigo-600" />
                  </Link>
                </div>
                <nav class="flex flex-1 flex-col">
                  <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                      <ul role="list" class="-mx-2 space-y-1">
                        <li v-for="item in navigation" :key="item.name">
                          <Link :href="route(item.href)" :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                            <component :is="item.icon" :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'size-6 shrink-0']" aria-hidden="true" />
                            {{ item.name }}
                          </Link>
                        </li>
                      </ul>
                    </li>
                    <li>
                      <div class="text-xs/6 font-semibold text-gray-400">Management</div>
                      <ul role="list" class="-mx-2 mt-2 space-y-1">
                        <li v-for="team in managementSections" :key="team.name">
                          <Link :href="route(team.href)" :class="[team.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                            <span :class="[team.current ? 'border-indigo-600 text-indigo-600' : 'border-gray-200 text-gray-400 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex size-6 shrink-0 items-center justify-center rounded-lg border bg-white text-[0.625rem] font-medium']">{{ team.initial }}</span>
                            <span class="truncate">{{ team.name }}</span>
                          </Link>
                        </li>
                      </ul>
                    </li>
                    <li class="mt-auto">
                      <Link :href="route('admin.settings')" class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-indigo-600">
                        <Cog6ToothIcon class="size-6 shrink-0 text-gray-400 group-hover:text-indigo-600" aria-hidden="true" />
                        Settings
                      </Link>
                    </li>
                  </ul>
                </nav>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Static sidebar for desktop -->
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
      <!-- Sidebar component for desktop -->
      <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4">
        <div class="flex h-16 shrink-0 items-center">
          <Link :href="route('dashboard')">
            <ApplicationLogo class="h-8 w-auto fill-current text-indigo-600" />
          </Link>
        </div>
        <nav class="flex flex-1 flex-col">
          <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
              <ul role="list" class="-mx-2 space-y-1">
                <li v-for="item in navigation" :key="item.name">
                  <Link :href="route(item.href)" :class="[item.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                    <component :is="item.icon" :class="[item.current ? 'text-indigo-600' : 'text-gray-400 group-hover:text-indigo-600', 'size-6 shrink-0']" aria-hidden="true" />
                    {{ item.name }}
                  </Link>
                </li>
              </ul>
            </li>
            <li>
              <div class="text-xs/6 font-semibold text-gray-400">Management</div>
              <ul role="list" class="-mx-2 mt-2 space-y-1">
                <li v-for="team in managementSections" :key="team.name">
                  <Link :href="route(team.href)" :class="[team.current ? 'bg-gray-50 text-indigo-600' : 'text-gray-700 hover:bg-gray-50 hover:text-indigo-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                    <span :class="[team.current ? 'border-indigo-600 text-indigo-600' : 'border-gray-200 text-gray-400 group-hover:border-indigo-600 group-hover:text-indigo-600', 'flex size-6 shrink-0 items-center justify-center rounded-lg border bg-white text-[0.625rem] font-medium']">{{ team.initial }}</span>
                    <span class="truncate">{{ team.name }}</span>
                  </Link>
                </li>
              </ul>
            </li>
            <li class="mt-auto">
              <Link :href="route('admin.settings')" class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-indigo-600">
                <Cog6ToothIcon class="size-6 shrink-0 text-gray-400 group-hover:text-indigo-600" aria-hidden="true" />
                Settings
              </Link>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <div class="lg:pl-72">
      <div class="sticky top-0 z-40 lg:mx-auto lg:px-8">
        <div class="flex h-16 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-xs sm:gap-x-6 sm:px-6 lg:px-0 lg:shadow-none">
          <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
            <span class="sr-only">Open sidebar</span>
            <Bars3Icon class="size-6" aria-hidden="true" />
          </button>

          <!-- Separator -->
          <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true" />

          <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
            <form class="grid flex-1 grid-cols-1" @submit.prevent="handleSearch">
              <input 
                v-model="searchQuery"
                type="search" 
                name="search" 
                aria-label="Search" 
                class="col-start-1 row-start-1 block size-full bg-white pl-8 text-base text-gray-900 outline-none placeholder:text-gray-400 sm:text-sm/6 border-0 focus:ring-0" 
                placeholder="Search users, places, lists..." 
              />
              <MagnifyingGlassIcon class="pointer-events-none col-start-1 row-start-1 size-5 self-center text-gray-400" aria-hidden="true" />
            </form>
            <div class="flex items-center gap-x-4 lg:gap-x-6">
              <!-- Quick Actions -->
              <div class="hidden sm:flex sm:space-x-2">
                <Link :href="route('places.create')" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                  <PlusIcon class="h-4 w-4 mr-1" />
                  Add Place
                </Link>
              </div>

              <!-- Notifications -->
              <button type="button" class="relative -m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
                <span class="sr-only">View notifications</span>
                <BellIcon class="size-6" aria-hidden="true" />
                <span v-if="notificationCount > 0" class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-400 flex items-center justify-center text-xs font-medium text-white">{{ notificationCount }}</span>
              </button>

              <!-- Separator -->
              <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200" aria-hidden="true" />

              <!-- Profile dropdown -->
              <UserProfileDropdown />
            </div>
          </div>
        </div>
      </div>

      <main class="py-10">
        <div class="mx-auto  px-4 sm:px-6 lg:px-8">
          <!-- Page header -->
          <div v-if="$slots.header" class="mb-8">
            <slot name="header" />
          </div>
          
          <!-- Page content -->
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
  Dialog,
  DialogPanel,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  Bars3Icon,
  BellIcon,
  ChartPieIcon,
  Cog6ToothIcon,
  DocumentDuplicateIcon,
  FolderIcon,
  HomeIcon,
  PlusIcon,
  UsersIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import UserProfileDropdown from '@/Components/UserProfileDropdown.vue'

const page = usePage()
const sidebarOpen = ref(false)
const searchQuery = ref('')
const notificationCount = ref(3) // Mock notification count

// Navigation items
const navigation = computed(() => [
  { 
    name: 'Dashboard', 
    href: 'admin.dashboard', 
    icon: HomeIcon, 
    current: page.url.startsWith('/admin') && page.url === '/admin' 
  },
  { 
    name: 'Analytics', 
    href: 'admin.analytics', 
    icon: ChartPieIcon, 
    current: page.url.includes('/admin/analytics') 
  },
  { 
    name: 'Reports', 
    href: 'admin.reports', 
    icon: DocumentDuplicateIcon, 
    current: page.url.includes('/admin/reports') 
  },
])

// Management sections
const managementSections = computed(() => [
  { 
    id: 1, 
    name: 'User Management', 
    href: 'admin.users', 
    initial: 'U', 
    current: page.url.includes('/admin/users') 
  },
  { 
    id: 2, 
    name: 'List Management', 
    href: 'admin.lists', 
    initial: 'L', 
    current: page.url.includes('/admin/lists') 
  },
  { 
    id: 3, 
    name: 'Places Management', 
    href: 'admin.entries', 
    initial: 'P', 
    current: page.url.includes('/admin/entries') 
  },
  { 
    id: 4, 
    name: 'Categories', 
    href: 'admin.categories', 
    initial: 'C', 
    current: page.url.includes('/admin/categories') 
  },
  { 
    id: 5, 
    name: 'List Categories', 
    href: 'admin.list-categories', 
    initial: 'LC', 
    current: page.url.includes('/admin/list-categories') 
  },
  { 
    id: 6, 
    name: 'Tags', 
    href: 'admin.tags', 
    initial: 'T', 
    current: page.url.includes('/admin/tags') 
  },
  { 
    id: 7, 
    name: 'Media', 
    href: 'admin.media', 
    initial: 'M', 
    current: page.url.includes('/admin/media') 
  },
])

// Handle search
const handleSearch = () => {
  if (searchQuery.value.trim()) {
    // Implement global admin search functionality
    console.log('Searching for:', searchQuery.value)
  }
}
</script>