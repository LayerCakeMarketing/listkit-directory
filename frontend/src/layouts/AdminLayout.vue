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
                    <svg class="size-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </TransitionChild>

              <!-- Mobile Sidebar -->
              <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-indigo-600 px-6 pb-2">
                <div class="flex h-16 shrink-0 items-center">
                  <router-link to="/home" class="group">
                    <img class="h-8 w-auto" src="/images/listerino_logo.svg" alt="Listerino" />
                    <span class="text-xs text-indigo-200 group-hover:text-white">← Back to site</span>
                  </router-link>
                </div>
                <nav class="flex flex-1 flex-col">
                  <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                      <ul role="list" class="-mx-2 space-y-1">
                        <li v-for="item in mainNavigation" :key="item.name">
                          <router-link 
                            :to="item.to" 
                            :class="[
                              isActive(item.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-700 hover:text-white', 
                              'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold'
                            ]"
                          >
                            <svg 
                              :class="[isActive(item.to) ? 'text-white' : 'text-indigo-200 group-hover:text-white', 'size-6 shrink-0']" 
                              fill="none" 
                              viewBox="0 0 24 24" 
                              stroke="currentColor"
                            >
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                          </router-link>
                          <!-- Submenu for Places -->
                          <ul v-if="item.children && isActive(item.to)" role="list" class="ml-9 mt-1 space-y-1">
                            <li v-for="child in item.children" :key="child.name">
                              <router-link 
                                :to="child.to" 
                                :class="[
                                  isActive(child.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:text-white',
                                  'block rounded-md py-1.5 pr-2 pl-9 text-sm/6'
                                ]"
                              >
                                {{ child.name }}
                              </router-link>
                            </li>
                          </ul>
                        </li>
                      </ul>
                    </li>
                    
                    <!-- User Content Section -->
                    <li>
                      <div class="text-xs/6 font-semibold text-indigo-200">User Content</div>
                      <ul role="list" class="-mx-2 mt-2 space-y-1">
                        <li v-for="item in userContentNavigation" :key="item.name">
                          <router-link 
                            :to="item.to" 
                            :class="[
                              isActive(item.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-700 hover:text-white', 
                              'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold'
                            ]"
                          >
                            <span :class="[
                              isActive(item.to) ? 'border-indigo-400 bg-indigo-500' : 'border-indigo-400 bg-indigo-500',
                              'flex size-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium text-white'
                            ]">
                              {{ item.initial }}
                            </span>
                            <span class="truncate">{{ item.name }}</span>
                          </router-link>
                          <!-- Submenu for Lists -->
                          <ul v-if="item.children && isActive(item.to)" role="list" class="ml-9 mt-1 space-y-1">
                            <li v-for="child in item.children" :key="child.name">
                              <router-link 
                                :to="child.to" 
                                :class="[
                                  isActive(child.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:text-white',
                                  'block rounded-md py-1.5 pr-2 pl-9 text-sm/6'
                                ]"
                              >
                                {{ child.name }}
                              </router-link>
                            </li>
                          </ul>
                        </li>
                      </ul>
                    </li>

                    <!-- Administration Section -->
                    <li>
                      <div class="text-xs/6 font-semibold text-indigo-200">Administration</div>
                      <ul role="list" class="-mx-2 mt-2 space-y-1">
                        <li v-for="item in administrationNavigation" :key="item.name">
                          <router-link 
                            :to="item.to" 
                            :class="[
                              isActive(item.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-700 hover:text-white', 
                              'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold'
                            ]"
                          >
                            <span :class="[
                              isActive(item.to) ? 'border-indigo-400 bg-indigo-500' : 'border-indigo-400 bg-indigo-500',
                              'flex size-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium text-white'
                            ]">
                              {{ item.initial }}
                            </span>
                            <span class="truncate">{{ item.name }}</span>
                          </router-link>
                          <!-- Submenu for Marketing Pages -->
                          <ul v-if="item.children && isActive(item.to)" role="list" class="ml-9 mt-1 space-y-1">
                            <li v-for="child in item.children" :key="child.name">
                              <router-link 
                                :to="child.to" 
                                :class="[
                                  isActive(child.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:text-white',
                                  'block rounded-md py-1.5 pr-2 pl-9 text-sm/6'
                                ]"
                              >
                                {{ child.name }}
                              </router-link>
                            </li>
                          </ul>
                        </li>
                        <!-- Settings under Administration -->
                        <li>
                          <router-link 
                            to="/admin/settings" 
                            :class="[
                              isActive('/admin/settings') ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-700 hover:text-white',
                              'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold'
                            ]"
                          >
                            <svg 
                              :class="[isActive('/admin/settings') ? 'text-white' : 'text-indigo-200 group-hover:text-white', 'size-6 shrink-0']" 
                              fill="none" 
                              viewBox="0 0 24 24" 
                              stroke="currentColor"
                            >
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Settings</span>
                          </router-link>
                        </li>
                      </ul>
                    </li>

                    <!-- User Profile at bottom -->
                    <li class="-mx-6 mt-auto">
                      <router-link 
                        :to="`/${user?.custom_url || user?.username || 'profile'}`" 
                        class="flex items-center gap-x-4 px-6 py-3 text-sm/6 font-semibold text-white hover:bg-indigo-700"
                      >
                        <img 
                          class="size-8 rounded-full bg-indigo-700" 
                          :src="user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || '')}`" 
                          alt=""
                        />
                        <span class="sr-only">Your profile</span>
                        <span aria-hidden="true">{{ user?.name }}</span>
                      </router-link>
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
      <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-indigo-600 px-6">
        <div class="flex h-16 shrink-0 items-center justify-between">
          <router-link to="/home" class="group">
            <img class="h-8 w-auto" src="/images/listerino_logo.svg" alt="Listerino" />
            <span class="text-xs text-indigo-200 group-hover:text-white">← Back to site</span>
          </router-link>
        </div>
        <nav class="flex flex-1 flex-col">
          <ul role="list" class="flex flex-1 flex-col gap-y-7">
            <li>
              <ul role="list" class="-mx-2 space-y-1">
                <li v-for="item in mainNavigation" :key="item.name">
                  <router-link 
                    :to="item.to" 
                    :class="[
                      isActive(item.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-700 hover:text-white', 
                      'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold'
                    ]"
                  >
                    <svg 
                      :class="[isActive(item.to) ? 'text-white' : 'text-indigo-200 group-hover:text-white', 'size-6 shrink-0']" 
                      fill="none" 
                      viewBox="0 0 24 24" 
                      stroke="currentColor"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    {{ item.name }}
                  </router-link>
                  <!-- Submenu for Places -->
                  <ul v-if="item.children && (isActive(item.to) || item.children.some(child => isActive(child.to)))" role="list" class="ml-9 mt-1 space-y-1">
                    <li v-for="child in item.children" :key="child.name">
                      <router-link 
                        :to="child.to" 
                        :class="[
                          isActive(child.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:text-white',
                          'block rounded-md py-1.5 pr-2 pl-9 text-sm/6'
                        ]"
                      >
                        {{ child.name }}
                      </router-link>
                    </li>
                  </ul>
                </li>
              </ul>
            </li>
            
            <!-- User Content Section -->
            <li>
              <div class="text-xs/6 font-semibold text-indigo-200">User Content</div>
              <ul role="list" class="-mx-2 mt-2 space-y-1">
                <li v-for="item in userContentNavigation" :key="item.name">
                  <router-link 
                    :to="item.to" 
                    :class="[
                      isActive(item.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-700 hover:text-white', 
                      'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold'
                    ]"
                  >
                    <span :class="[
                      isActive(item.to) ? 'border-indigo-400 bg-indigo-500' : 'border-indigo-400 bg-indigo-500',
                      'flex size-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium text-white'
                    ]">
                      {{ item.initial }}
                    </span>
                    <span class="truncate">{{ item.name }}</span>
                  </router-link>
                  <!-- Submenu for Lists -->
                  <ul v-if="item.children && (isActive(item.to) || item.children.some(child => isActive(child.to)))" role="list" class="ml-9 mt-1 space-y-1">
                    <li v-for="child in item.children" :key="child.name">
                      <router-link 
                        :to="child.to" 
                        :class="[
                          isActive(child.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:text-white',
                          'block rounded-md py-1.5 pr-2 pl-9 text-sm/6'
                        ]"
                      >
                        {{ child.name }}
                      </router-link>
                    </li>
                  </ul>
                </li>
              </ul>
            </li>

            <!-- Administration Section -->
            <li>
              <div class="text-xs/6 font-semibold text-indigo-200">Administration</div>
              <ul role="list" class="-mx-2 mt-2 space-y-1">
                <li v-for="item in administrationNavigation" :key="item.name">
                  <router-link 
                    :to="item.to" 
                    :class="[
                      isActive(item.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-700 hover:text-white', 
                      'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold'
                    ]"
                  >
                    <span :class="[
                      isActive(item.to) ? 'border-indigo-400 bg-indigo-500' : 'border-indigo-400 bg-indigo-500',
                      'flex size-6 shrink-0 items-center justify-center rounded-lg border text-[0.625rem] font-medium text-white'
                    ]">
                      {{ item.initial }}
                    </span>
                    <span class="truncate">{{ item.name }}</span>
                  </router-link>
                  <!-- Submenu for Marketing Pages -->
                  <ul v-if="item.children && (isActive(item.to) || item.children.some(child => isActive(child.to)))" role="list" class="ml-9 mt-1 space-y-1">
                    <li v-for="child in item.children" :key="child.name">
                      <router-link 
                        :to="child.to" 
                        :class="[
                          isActive(child.to) ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:text-white',
                          'block rounded-md py-1.5 pr-2 pl-9 text-sm/6'
                        ]"
                      >
                        {{ child.name }}
                      </router-link>
                    </li>
                  </ul>
                </li>
                <!-- Settings under Administration -->
                <li>
                  <router-link 
                    to="/admin/settings" 
                    :class="[
                      isActive('/admin/settings') ? 'bg-indigo-700 text-white' : 'text-indigo-200 hover:bg-indigo-700 hover:text-white',
                      'group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold'
                    ]"
                  >
                    <svg 
                      :class="[isActive('/admin/settings') ? 'text-white' : 'text-indigo-200 group-hover:text-white', 'size-6 shrink-0']" 
                      fill="none" 
                      viewBox="0 0 24 24" 
                      stroke="currentColor"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>Settings</span>
                  </router-link>
                </li>
              </ul>
            </li>
            <li>
                  <button 
        @click="logout" 
        class="text-sm text-indigo-200 hover:text-white"
      >
        Sign Out
      </button>

              </li>

            <!-- User Profile at bottom -->
            <li class="-mx-6 mt-auto">
              <router-link 
                :to="`/${user?.custom_url || user?.username || 'profile'}`" 
                class="flex items-center gap-x-4 px-6 py-3 text-sm/6 font-semibold text-white hover:bg-indigo-700"
              >
                <img 
                  class="size-8 rounded-full bg-indigo-700" 
                  :src="user?.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || '')}`" 
                  alt=""
                />
                <span class="sr-only">Your profile</span>
                <span aria-hidden="true">{{ user?.name }}</span>
              </router-link>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Mobile top bar -->
    <div class="sticky top-0 z-40 flex items-center gap-x-6 bg-indigo-600 px-4 py-4 shadow-xs sm:px-6 lg:hidden">
      <button type="button" class="-m-2.5 p-2.5 text-indigo-200 lg:hidden" @click="sidebarOpen = true">
        <span class="sr-only">Open sidebar</span>
        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <div class="flex-1 text-sm/6 font-semibold text-white">{{ currentPageTitle }}</div>
  
    </div>

    <!-- Desktop top bar -->
    <div class="hidden lg:fixed lg:top-0 lg:right-0 lg:z-40 lg:flex lg:items-center lg:gap-x-6 lg:px-6 lg:py-4">
      <button 
        @click="logout" 
        class="text-sm font-medium text-gray-700 hover:text-gray-900 bg-white px-4 py-2 rounded-md shadow-sm hover:shadow-md transition-shadow"
      >
        Sign Out
      </button>
    </div>

    <!-- Main content -->
    <main class="py-10 lg:pl-72">
      <div class="px-4 sm:px-6 lg:px-8">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const sidebarOpen = ref(false)
const showProfileMenu = ref(false)

const user = computed(() => authStore.user)

// Main navigation items
const mainNavigation = [
  { 
    name: 'Dashboard', 
    to: '/admin', 
    icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' 
  },
  { 
    name: 'Places', 
    to: '/admin/places', 
    icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
    children: [
      { name: 'Categories', to: '/admin/categories' }
    ]
  },
  { 
    name: 'Regions', 
    to: '/admin/regions', 
    icon: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z' 
  },
  { 
    name: 'Tags', 
    to: '/admin/tags', 
    icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' 
  },
  { 
    name: 'Media', 
    to: '/admin/media', 
    icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z' 
  },
  { 
    name: 'Reports', 
    to: '/admin/reports', 
    icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' 
  },
  { 
    name: 'Flagged Content', 
    to: '/admin/flags', 
    icon: 'M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9' 
  }
]

// User Content navigation
const userContentNavigation = [
  { 
    id: 1, 
    name: 'Users', 
    to: '/admin/users', 
    initial: 'U' 
  },
  { 
    id: 2, 
    name: 'Lists', 
    to: '/admin/lists', 
    initial: 'L',
    children: [
      { name: 'Categories', to: '/admin/list-categories' }
    ]
  },
  { 
    id: 3, 
    name: 'User Media', 
    to: '/admin/user-media', 
    initial: 'M' 
  }
]

// Administration navigation
const administrationNavigation = [
  { 
    id: 1, 
    name: 'Tickets', 
    to: '/admin/tickets', 
    initial: 'T' 
  },
  { 
    id: 2, 
    name: 'Todos', 
    to: '/admin/todos', 
    initial: 'TD' 
  },
  { 
    id: 3, 
    name: 'Marketing Pages', 
    to: '/admin/marketing-pages', 
    initial: 'MP',
    children: [
      { name: 'All Pages', to: '/admin/marketing-pages' },
      { name: 'Login Page', to: '/admin/marketing-pages/login' },
      { name: 'Home Page', to: '/admin/marketing-pages/home' }
    ]
  },
  { 
    id: 4, 
    name: 'Waitlist', 
    to: '/admin/waitlist', 
    initial: 'W' 
  }
]

// Current page title for mobile
const currentPageTitle = computed(() => {
  const allNavItems = [
    ...mainNavigation,
    ...userContentNavigation,
    ...administrationNavigation
  ]
  
  const currentItem = allNavItems.find(item => isActive(item.to))
  return currentItem?.name || 'Admin'
})

const isActive = (path) => {
  return route.path === path || (path !== '/admin' && route.path.startsWith(path))
}

const logout = async () => {
  try {
    await axios.post('/api/logout')
    authStore.clearAuth()
    router.push('/')
  } catch (error) {
    console.error('Logout failed:', error)
  }
}
</script>