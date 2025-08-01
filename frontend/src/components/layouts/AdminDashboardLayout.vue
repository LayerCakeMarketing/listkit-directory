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
                    <svg class="size-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </TransitionChild>

              <!-- Sidebar component for mobile -->
              <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                  <router-link to="/admin">
                    <ApplicationLogo class="h-8 w-auto fill-current text-blue-600" />
                  </router-link>
                </div>
                <nav class="flex flex-1 flex-col">
                  <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                      <ul role="list" class="-mx-2 space-y-1">
                        <li v-for="item in navigation" :key="item.name">
                          <div v-if="item.children">
                            <button @click="toggleExpanded(item.name)" :class="[isCurrentRoute(item.href) || isChildActive(item) ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold w-full text-left']">
                              <svg :class="[isCurrentRoute(item.href) || isChildActive(item) ? 'text-blue-600' : 'text-gray-400 group-hover:text-blue-600', 'size-6 shrink-0']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.iconPath" />
                              </svg>
                              {{ item.name }}
                              <svg :class="['ml-auto size-5 transition-transform', expandedItems[item.name] ? 'rotate-90' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                              </svg>
                            </button>
                            <ul v-show="expandedItems[item.name]" class="mt-1 ml-8 space-y-1">
                              <li v-for="child in item.children" :key="child.href">
                                <router-link :to="child.href" :class="[isCurrentRoute(child.href) ? 'bg-gray-50 text-blue-600' : 'text-gray-500 hover:text-blue-600', 'block rounded-md py-2 pl-3 pr-2 text-sm font-medium']">
                                  {{ child.name }}
                                </router-link>
                              </li>
                            </ul>
                          </div>
                          <router-link v-else :to="item.href" :class="[isCurrentRoute(item.href) ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                            <svg :class="[isCurrentRoute(item.href) ? 'text-blue-600' : 'text-gray-400 group-hover:text-blue-600', 'size-6 shrink-0']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.iconPath" />
                            </svg>
                            {{ item.name }}
                          </router-link>
                        </li>
                      </ul>
                    </li>
                    <li>
                      <div class="text-xs/6 font-semibold text-gray-400">Management</div>
                      <ul role="list" class="-mx-2 mt-2 space-y-1">
                        <li v-for="team in managementSections" :key="team.name">
                          <router-link :to="team.href" :class="[isCurrentRoute(team.href) ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                            <span :class="[isCurrentRoute(team.href) ? 'border-blue-600 text-blue-600' : 'border-gray-200 text-gray-400 group-hover:border-blue-600 group-hover:text-blue-600', 'flex size-6 shrink-0 items-center justify-center rounded-lg border bg-white text-[0.625rem] font-medium']">{{ team.initial }}</span>
                            <span class="truncate">{{ team.name }}</span>
                          </router-link>
                        </li>
                      </ul>
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
      <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4">
        <div class="flex h-16 shrink-0 items-center">
          <router-link to="/admin">
            <ApplicationLogo class="h-8 w-auto fill-current text-blue-600" />
          </router-link>
        </div>
        <nav class="flex flex-1 flex-col">
          <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
              <ul role="list" class="-mx-2 space-y-1">
                <li v-for="item in navigation" :key="item.name">
                  <div v-if="item.children">
                    <button @click="toggleExpanded(item.name)" :class="[isCurrentRoute(item.href) || isChildActive(item) ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold w-full text-left']">
                      <svg :class="[isCurrentRoute(item.href) || isChildActive(item) ? 'text-blue-600' : 'text-gray-400 group-hover:text-blue-600', 'size-6 shrink-0']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.iconPath" />
                      </svg>
                      {{ item.name }}
                      <svg :class="['ml-auto size-5 transition-transform', expandedItems[item.name] ? 'rotate-90' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                      </svg>
                    </button>
                    <ul v-show="expandedItems[item.name]" class="mt-1 ml-8 space-y-1">
                      <li v-for="child in item.children" :key="child.href">
                        <router-link :to="child.href" :class="[isCurrentRoute(child.href) ? 'bg-gray-50 text-blue-600' : 'text-gray-500 hover:text-blue-600', 'block rounded-md py-2 pl-3 pr-2 text-sm font-medium']">
                          {{ child.name }}
                        </router-link>
                      </li>
                    </ul>
                  </div>
                  <router-link v-else :to="item.href" :class="[isCurrentRoute(item.href) ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                    <svg :class="[isCurrentRoute(item.href) ? 'text-blue-600' : 'text-gray-400 group-hover:text-blue-600', 'size-6 shrink-0']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.iconPath" />
                    </svg>
                    {{ item.name }}
                  </router-link>
                </li>
              </ul>
            </li>
            <li>
              <div class="text-xs/6 font-semibold text-gray-400">Management</div>
              <ul role="list" class="-mx-2 mt-2 space-y-1">
                <li v-for="team in managementSections" :key="team.name">
                  <router-link :to="team.href" :class="[isCurrentRoute(team.href) ? 'bg-gray-50 text-blue-600' : 'text-gray-700 hover:bg-gray-50 hover:text-blue-600', 'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold']">
                    <span :class="[isCurrentRoute(team.href) ? 'border-blue-600 text-blue-600' : 'border-gray-200 text-gray-400 group-hover:border-blue-600 group-hover:text-blue-600', 'flex size-6 shrink-0 items-center justify-center rounded-lg border bg-white text-[0.625rem] font-medium']">{{ team.initial }}</span>
                    <span class="truncate">{{ team.name }}</span>
                  </router-link>
                </li>
              </ul>
            </li>
            <li class="mt-auto">
              <router-link to="/home" class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                <svg class="size-6 shrink-0 text-gray-400 group-hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Back to Site
              </router-link>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <div class="lg:pl-72">
      <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
        <button type="button" class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
          <span class="sr-only">Open sidebar</span>
          <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>

        <!-- Separator -->
        <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true" />

        <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
          <div class="relative flex flex-1 items-center">
            <div v-if="$slots.header" class="flex-1">
              <slot name="header" />
            </div>
            <div v-else class="flex-1">
              <h2 class="text-lg font-semibold">Admin Dashboard</h2>
            </div>
          </div>
          
          <!-- Navigation Links - moved to the right for admin pages -->
          <div class="hidden lg:flex items-center gap-x-6">
            <router-link to="/lists" class="text-sm font-medium text-gray-700 hover:text-gray-900">
              Explore
            </router-link>
            <router-link to="/places" class="text-sm font-medium text-gray-700 hover:text-gray-900">
              Places
            </router-link>
            <router-link to="/regions/ca" class="text-sm font-medium text-gray-700 hover:text-gray-900">
              Browse California
            </router-link>
            <router-link to="/regions" class="text-sm font-medium text-gray-700 hover:text-gray-900">
              Regions
            </router-link>
          </div>
          
          <div class="flex items-center gap-x-4 lg:gap-x-6">
            <!-- User dropdown -->
            <Menu as="div" class="relative">
              <MenuButton class="-m-1.5 flex items-center p-1.5">
                <span class="sr-only">Open user menu</span>
                <img v-if="user?.avatar" class="size-8 rounded-full bg-gray-50" :src="user.avatar" alt="" />
                <div v-else class="size-8 rounded-full bg-gray-300 flex items-center justify-center">
                  <span class="text-gray-600 text-sm font-medium">{{ user?.name?.charAt(0)?.toUpperCase() || 'A' }}</span>
                </div>
                <span class="hidden lg:flex lg:items-center">
                  <span class="ml-4 text-sm/6 font-semibold text-gray-900" aria-hidden="true">{{ user?.name || 'Admin' }}</span>
                  <svg class="ml-2 size-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </span>
              </MenuButton>
              <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none">
                  <MenuItem v-slot="{ active }">
                    <router-link to="/profile/edit" :class="[active ? 'bg-gray-50' : '', 'block px-3 py-1 text-sm/6 text-gray-900']">Your profile</router-link>
                  </MenuItem>
                  <MenuItem v-slot="{ active }">
                    <button @click="logout" :class="[active ? 'bg-gray-50' : '', 'block w-full text-left px-3 py-1 text-sm/6 text-gray-900']">Sign out</button>
                  </MenuItem>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </div>
      </div>

      <main>
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Dialog, DialogPanel, Menu, MenuButton, MenuItem, MenuItems, TransitionChild, TransitionRoot } from '@headlessui/vue'
import ApplicationLogo from '@/components/ApplicationLogo.vue'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const sidebarOpen = ref(false)
const user = ref(null)
const expandedItems = ref({})

const navigation = [
  { name: 'Dashboard', href: '/admin', iconPath: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: 'Users', href: '/admin/users', iconPath: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
  { name: 'Places', href: '/admin/places', iconPath: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
  { 
    name: 'Claims', 
    href: '/admin/claims',
    iconPath: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    children: [
      { name: 'Claimed Places', href: '/admin/claimed-places' },
      { name: 'Claim Subscriptions', href: '/admin/claim-subscriptions' }
    ]
  },
  { name: 'Pending Approval', href: '/admin/places/pending', iconPath: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
  { name: 'Categories', href: '/admin/categories', iconPath: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' },
]

const managementSections = [
  { id: 1, name: 'Regions', href: '/admin/regions', initial: 'R' },
  { id: 2, name: 'Lists', href: '/admin/lists', initial: 'L' },
  { id: 3, name: 'List Categories', href: '/admin/list-categories', initial: 'LC' },
  { id: 4, name: 'Tags', href: '/admin/tags', initial: 'T' },
  { id: 5, name: 'Media', href: '/admin/media', initial: 'M' },
  { id: 6, name: 'Reports', href: '/admin/reports', initial: 'RP' },
  { id: 7, name: 'Settings', href: '/admin/settings', initial: 'S' },
]

const isCurrentRoute = (href) => {
  return route.path === href || route.path.startsWith(href + '/')
}

const logout = async () => {
  try {
    await axios.post('/logout')
    router.push('/login')
  } catch (error) {
    console.error('Logout failed:', error)
  }
}

const fetchUser = async () => {
  try {
    const response = await axios.get('/api/user')
    user.value = response.data
  } catch (error) {
    console.error('Error fetching user:', error)
  }
}

const toggleExpanded = (itemName) => {
  expandedItems.value[itemName] = !expandedItems.value[itemName]
}

const isChildActive = (item) => {
  if (!item.children) return false
  return item.children.some(child => isCurrentRoute(child.href))
}

onMounted(() => {
  fetchUser()
  
  // Auto-expand items with active children
  navigation.forEach(item => {
    if (item.children && isChildActive(item)) {
      expandedItems.value[item.name] = true
    }
  })
})
</script>