import { createRouter, createWebHistory } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // Welcome page (public)
  {
    path: '/',
    name: 'Welcome',
    component: () => import('@/views/Welcome.vue')
  },

  // Authentication routes
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('@/views/auth/Register.vue'),
    meta: { guest: true }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('@/views/auth/ForgotPassword.vue'),
    meta: { guest: true }
  },
  {
    path: '/reset-password/:token',
    name: 'ResetPassword',
    component: () => import('@/views/auth/ResetPassword.vue'),
    meta: { guest: true }
  },
  {
    path: '/verify-email/:id/:hash',
    name: 'VerifyEmail',
    component: () => import('@/views/auth/VerifyEmail.vue'),
    meta: { guest: true }
  },

  // Home page for authenticated users
  {
    path: '/home',
    name: 'Home',
    component: () => import('@/views/Home.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/notifications',
    name: 'Notifications',
    component: () => import('@/views/Notifications.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/messages',
    name: 'Messages',
    component: () => import('@/views/Messages.vue'),
    meta: { requiresAuth: true }
  },

  // Dashboard redirects to home
  {
    path: '/dashboard',
    redirect: '/home'
  },


  // Admin routes
  {
    path: '/admin',
    name: 'AdminDashboard',
    component: () => import('@/views/admin/Dashboard.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },

  // Local/Region routes (3-level hierarchy)
  {
    path: '/local/:state/:city/:neighborhood',
    name: 'Neighborhood',
    component: () => import('@/views/regions/City.vue'),
    props: route => ({
      state: route.params.state,
      city: route.params.city,
      neighborhood: route.params.neighborhood,
      isNeighborhood: true
    })
  },
  {
    path: '/local/:state/:city',
    name: 'City',
    component: () => import('@/views/regions/City.vue'),
    props: true
  },
  {
    path: '/local/:state',
    name: 'State',
    component: () => import('@/views/regions/State.vue'),
    props: true
  },

  // Places routes (new canonical URL structure)
  {
    path: '/places',
    name: 'Places',
    component: () => import('@/views/places/Index.vue')
  },
  {
    path: '/places/map',
    name: 'PlacesMap',
    component: () => import('@/views/places/MapDiscovery.vue'),
    meta: { title: 'Places Map' }
  },
  {
    path: '/my-places',
    name: 'MyPlaces',
    component: () => import('@/views/MyPlaces.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/places/create',
    name: 'PlacesCreate',
    component: () => import('@/views/places/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/places/:id/edit',
    name: 'PlacesEdit',
    component: () => import('@/views/places/Edit.vue'),
    meta: { requiresAuth: true },
    props: true
  },
  
  // Short URL redirect
  {
    path: '/p/:id',
    name: 'PlaceShort',
    component: () => import('@/views/places/Show.vue'),
    props: route => ({ id: route.params.id, isShortUrl: true })
  },
  
  // Preview route for unpublished places
  {
    path: '/places/preview/:id',
    name: 'PlacePreview',
    component: () => import('@/views/places/Show.vue'),
    props: route => ({ id: route.params.id, isPreview: true }),
    meta: { requiresAuth: true }
  },
  
  // Category browsing route (simplified - let API handle disambiguation)
  {
    path: '/places/:slug',
    name: 'PlacesSlug',
    component: () => import('@/views/places/DynamicBrowse.vue'),
    props: true
  },
  {
    path: '/places/:state/:city',
    name: 'PlacesByCity',
    component: () => import('@/views/places/BrowseCity.vue'),
    props: true
  },
  {
    path: '/places/:state/:city/:category',
    name: 'PlacesByCityCategory',
    component: () => import('@/views/places/BrowseCategory.vue'),
    props: true
  },
  
  // Canonical entry route
  {
    path: '/places/:state/:city/:category/:entry',
    name: 'PlaceCanonical',
    component: () => import('@/views/places/Show.vue'),
    props: route => ({
      state: route.params.state,
      city: route.params.city,
      category: route.params.category,
      entry: route.params.entry,
      isCanonical: true
    })
  },
  
  // Business claiming route
  {
    path: '/places/:slug/claim',
    name: 'ClaimBusiness',
    component: () => import('@/views/places/ClaimBusiness.vue'),
    props: true,
    meta: { requiresAuth: true }
  },
  
  // Claim success route
  {
    path: '/places/:slug/claim-success',
    name: 'ClaimSuccess',
    component: () => import('@/views/places/ClaimSuccess.vue'),
    props: true,
    meta: { requiresAuth: true }
  },
  
  // Legacy routes (for backward compatibility)
  {
    path: '/places/category/:slug',
    name: 'PlacesCategory',
    component: () => import('@/views/places/Category.vue'),
    props: true
  },
  {
    path: '/places/entry/:slug',
    name: 'PlacesEntry',
    component: () => import('@/views/places/Show.vue'),
    props: true
  },
  {
    path: '/places/:category/:slug',
    name: 'PlacesByCategorySlug',
    component: () => import('@/views/places/Show.vue'),
    props: true
  },

  // Regions index route (redirects to local)
  {
    path: '/regions',
    redirect: '/local'
  },
  {
    path: '/regions/:state',
    redirect: to => `/local/${to.params.state}`
  },
  {
    path: '/regions/:state/:city',
    redirect: to => `/local/${to.params.state}/${to.params.city}`
  },
  {
    path: '/regions/:state/:city/:neighborhood',
    redirect: to => `/local/${to.params.state}/${to.params.city}/${to.params.neighborhood}`
  },
  
  // Local index route
  {
    path: '/local',
    name: 'LocalIndex',
    component: () => import('@/views/regions/Index.vue')
  },

  // Region Two-Column Demo (for development/testing)
  {
    path: '/demo/regions',
    name: 'RegionTwoColumnDemo',
    component: () => import('@/views/regions/TwoColumnExample.vue')
  },

  // Region Explorer View (AllTrails-style layout)
  {
    path: '/regions/:regionId/explore',
    name: 'RegionExplorer',
    component: () => import('@/views/regions/RegionExplorerView.vue'),
    props: route => ({ regionId: parseInt(route.params.regionId) })
  },

  // Profile management (authenticated)
  {
    path: '/profile/edit',
    name: 'ProfileEdit',
    component: () => import('@/views/profile/Edit.vue'),
    meta: { requiresAuth: true }
  },

  // My Lists (authenticated user's lists)
  {
    path: '/mylists',
    name: 'MyLists',
    component: () => import('@/views/lists/Index.vue'),
    meta: { requiresAuth: true }
  },
  
  // List Chains
  {
    path: '/chains/create',
    name: 'ChainCreate',
    component: () => import('@/views/chains/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/chains/:id',
    name: 'ChainView',
    component: () => import('@/views/chains/Show.vue'),
    props: true
  },
  {
    path: '/chains/:id/edit',
    name: 'ChainEdit',
    component: () => import('@/views/chains/Edit.vue'),
    meta: { requiresAuth: true },
    props: true
  },
  
  // My Places (authenticated user's places)
  {
    path: '/myplaces',
    name: 'MyPlaces',
    component: () => import('@/views/places/MyPlaces.vue'),
    meta: { requiresAuth: true }
  },
  
  // Edit list - Using new inline editing interface
  {
    path: '/mylists/:id/edit',
    name: 'ListEdit',
    component: () => import('@/views/lists/EditInline.vue'),
    meta: { requiresAuth: true },
    props: true
  },
  // Legacy edit with drawer (kept for reference)
  {
    path: '/mylists/:id/edit-drawer',
    name: 'ListEditDrawer',
    component: () => import('@/views/lists/Edit.vue'),
    meta: { requiresAuth: true },
    props: true
  },
  
  // Saved Items
  {
    path: '/saved',
    name: 'SavedItems',
    component: () => import('@/views/SavedItems.vue'),
    meta: { requiresAuth: true }
  },
  
  // Channel routes
  {
    path: '/channels',
    name: 'Channels',
    component: () => import('@/views/channels/Index.vue')
  },
  {
    path: '/channels/create',
    name: 'ChannelCreate',
    component: () => import('@/views/channels/Create.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/channels/:id/edit',
    name: 'ChannelEdit',
    component: () => import('@/views/channels/Edit.vue'),
    meta: { requiresAuth: true },
    props: true
  },
  {
    path: '/mychannels',
    name: 'MyChannels',
    component: () => import('@/views/channels/MyChannels.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/channels/:channelId/lists/create',
    name: 'ChannelListCreate',
    component: () => import('@/views/channels/lists/Create.vue'),
    meta: { requiresAuth: true },
    props: true
  },
  {
    path: '/channels/:channelId/chains/create',
    name: 'ChannelChainCreate',
    component: () => import('@/views/channels/chains/Create.vue'),
    meta: { requiresAuth: true },
    props: true
  },
  
  // Public lists exploration
  {
    path: '/lists',
    name: 'PublicLists',
    component: () => import('@/views/lists/PublicLists.vue')
  },
  {
    path: '/lists/:categorySlug',
    name: 'PublicListsByCategory',
    component: () => import('@/views/lists/PublicListsByCategory.vue')
  },
  // Admin routes
  {
    path: '/admin/users',
    name: 'AdminUsers',
    component: () => import('@/views/admin/users/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/places',
    name: 'AdminPlaces', 
    component: () => import('@/views/admin/places/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/places/bulk-import-export',
    name: 'AdminPlacesBulkImportExport',
    component: () => import('@/views/admin/places/BulkImportExport.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/places/pending',
    name: 'AdminPlacesPending',
    component: () => import('@/views/admin/places/Pending.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/categories',
    name: 'AdminCategories',
    component: () => import('@/views/admin/categories/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/regions',
    name: 'AdminRegions',
    component: () => import('@/views/admin/regions/Enhanced.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/notifications/send',
    name: 'AdminSendNotification',
    component: () => import('@/views/admin/notifications/SendNotification.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/claimed-places',
    name: 'AdminClaimedPlaces',
    component: () => import('@/views/admin/claims/ClaimedPlaces.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/claim-subscriptions',
    name: 'AdminClaimSubscriptions',
    component: () => import('@/views/admin/claims/ClaimSubscriptions.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  // Legacy routes - redirect to consolidated view
  {
    path: '/admin/regions/basic',
    redirect: '/admin/regions'
  },
  {
    path: '/admin/regions/enhanced',
    redirect: '/admin/regions'
  },
  {
    path: '/admin/lists',
    name: 'AdminLists',
    component: () => import('@/views/admin/lists/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/lists/:id/edit',
    name: 'AdminListEdit',
    component: () => import('@/views/admin/lists/Edit.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/list-categories',
    name: 'AdminListCategories',
    component: () => import('@/views/admin/list-categories/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/reports',
    name: 'AdminReports',
    component: () => import('@/views/admin/reports/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/settings',
    name: 'AdminSettings',
    component: () => import('@/views/admin/settings/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/media',
    name: 'AdminMedia',
    component: () => import('@/views/admin/media/MediaManager.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/marketing-pages',
    name: 'AdminMarketingPages',
    component: () => import('@/views/admin/marketing-pages/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/marketing-pages/login',
    name: 'AdminLoginPageSettings',
    component: () => import('@/views/admin/marketing-pages/LoginPageSettings.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/marketing-pages/home',
    name: 'AdminHomePageSettings',
    component: () => import('@/views/admin/marketing-pages/HomePageSettings.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/marketing-pages/local',
    name: 'AdminLocalPageSettings',
    component: () => import('@/views/admin/marketing-pages/LocalPageEditor.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/marketing-pages/:page',
    name: 'AdminMarketingPageEditor',
    component: () => import('@/views/admin/marketing-pages/MarketingPageEditor.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/waitlist',
    name: 'AdminWaitlist',
    component: () => import('@/views/admin/waitlist/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/tags',
    name: 'AdminTags',
    component: () => import('@/views/admin/tags/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/claims',
    name: 'AdminClaims',
    component: () => import('@/views/admin/claims/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  {
    path: '/admin/media',
    name: 'AdminMedia',
    component: () => import('@/views/admin/Media.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  // User profile routes (with /up/ prefix)
  {
    path: '/up/@:username',
    name: 'UserProfile',
    component: () => import('@/views/profile/Show.vue'),
    props: route => ({ username: route.params.username })
  },
  {
    path: '/up/@:username/lists',
    name: 'UserListsPublic',
    component: () => import('@/views/lists/UserLists.vue'),
    props: true,
    meta: { requiresAuth: true }
  },
  {
    path: '/up/@:username/:slug',
    name: 'UserList',
    component: () => import('@/views/lists/Show.vue'),
    props: true
  },
  
  // Legacy channel routes with @ prefix (redirect to new URLs)
  {
    path: '/@:slug',
    redirect: to => ({ path: `/${to.params.slug}` })
  },
  {
    path: '/@:channelSlug/:listSlug',
    redirect: to => ({ path: `/${to.params.channelSlug}/${to.params.listSlug}` })
  },
  {
    path: '/@:channelSlug/chains/:chainSlug',
    redirect: to => ({ path: `/${to.params.channelSlug}/chains/${to.params.chainSlug}` })
  },
  
  // Generic category routes (should be after user routes)
  {
    path: '/:category/:subcategory/:slug',
    name: 'PlaceWithSubcategory',
    component: () => import('@/views/places/Show.vue'),
    props: true
  },
  {
    path: '/:category/:slug',
    name: 'Place',
    component: () => import('@/views/places/Show.vue'),
    props: true
  },

  // Channel routes (without @ prefix) - must come before catch-all
  {
    path: '/:channelSlug/chains/:chainSlug',
    name: 'ChannelChain',
    component: () => import('@/views/chains/Show.vue'),
    props: route => ({ 
      channelSlug: route.params.channelSlug,
      slug: route.params.chainSlug,
      isChannelChain: true
    }),
    beforeEnter: (to, from, next) => {
      // Check if this could be a channel chain route
      const forbiddenSlugs = ['admin', 'api', 'places', 'lists', 'regions', 'login', 'register', 
                             'logout', 'dashboard', 'profile', 'settings', 'home', 'local', 'mylists', 
                             'myplaces', 'saved', 'channels', 'edit', 'create', 'new', 'p', 'up'];
      if (forbiddenSlugs.includes(to.params.channelSlug.toLowerCase())) {
        next({ name: 'NotFound' });
      } else {
        next();
      }
    }
  },
  {
    path: '/:channelSlug/:listSlug',
    name: 'ChannelList',
    component: () => import('@/views/lists/Show.vue'),
    props: route => ({ 
      channelSlug: route.params.channelSlug,
      slug: route.params.listSlug,
      isChannelList: true
    }),
    beforeEnter: (to, from, next) => {
      // Check if this could be a channel list route
      const forbiddenSlugs = ['admin', 'api', 'places', 'lists', 'regions', 'login', 'register', 
                             'logout', 'dashboard', 'profile', 'settings', 'home', 'local', 'mylists', 
                             'myplaces', 'saved', 'channels', 'edit', 'create', 'new', 'p', 'up'];
      if (forbiddenSlugs.includes(to.params.channelSlug.toLowerCase())) {
        next({ name: 'NotFound' });
      } else {
        next();
      }
    }
  },
  
  // Channel profile or marketing page (single slug)
  {
    path: '/:slug',
    name: 'DynamicPage',
    component: () => import('@/views/DynamicPage.vue'),
    props: true,
    beforeEnter: (to, from, next) => {
      const forbiddenSlugs = ['admin', 'api', 'places', 'lists', 'regions', 'login', 'register', 
                             'logout', 'dashboard', 'profile', 'settings', 'home', 'local', 'mylists', 
                             'myplaces', 'saved', 'channels', 'edit', 'create', 'new', 'p', 'up',
                             'notifications', 'messages', 'search', 'explore', 'discover'];
      const slug = to.params.slug;
      
      // Reject if it's a forbidden slug
      if (forbiddenSlugs.includes(slug.toLowerCase())) {
        next({ name: 'NotFound' });
      } else {
        next();
      }
    }
  },

  // 404 catch-all
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/error/404.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    } else {
      return { top: 0, behavior: 'smooth' }
    }
  }
})

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Only fetch user on first load or if we think we should be authenticated
  if (!authStore.initialized) {
    const shouldFetchUser = () => {
      // Check localStorage for auth hint
      const wasAuthenticated = localStorage.getItem('isAuthenticated') === 'true'
      
      // Check for session cookies
      const hasSessionCookie = document.cookie.includes('laravel_session') || 
                              document.cookie.includes('XSRF-TOKEN')
      
      return wasAuthenticated || hasSessionCookie
    }
    
    if (shouldFetchUser()) {
      console.log('Initial auth check, fetching user...')
      await authStore.fetchUser()
    } else {
      // Mark as initialized even if we don't fetch
      authStore.initialized = true
    }
  }

  // Handle disambiguation for /:username/:slug routes
  // Removed the HEAD request check - let the component handle 404s
  // This was blocking navigation to valid lists

  // Check if route requires authentication
  if (to.meta.requiresAuth) {
    console.log('Route requires auth:', to.name, 'isAuthenticated:', authStore.isAuthenticated, 'user:', authStore.user)
    if (!authStore.isAuthenticated) {
      console.log('Not authenticated, redirecting to login')
      return next({ name: 'Login', query: { redirect: to.fullPath } })
    }

    // Check admin requirement
    if (to.meta.requiresAdmin && !authStore.isAdmin) {
      // Redirect non-admin users to home
      return next({ name: 'Home' })
    }
  }

  // Redirect authenticated users away from guest routes
  if (to.meta.guest && authStore.isAuthenticated) {
    return next({ name: 'Home' })
  }

  // Redirect authenticated users from welcome page to home
  if (to.name === 'Welcome' && authStore.isAuthenticated) {
    return next({ name: 'Home' })
  }

  next()
})

// Route disambiguation helper
export function disambiguateRoute(path) {
  // This function helps determine what type of content a dynamic path represents
  // You would typically make an API call to check what exists at this path
  
  const segments = path.split('/').filter(Boolean)
  
  // Check patterns in order of specificity
  if (segments[0] === 'location') {
    return 'region'
  }
  
  if (segments[0] === 'places') {
    return 'category'
  }
  
  // For single segments, need to check API to see if it's a user or region
  if (segments.length === 1) {
    // This would be an API call in practice
    return 'user' // or 'region' based on API response
  }
  
  if (segments.length === 2 && segments[1] === 'lists') {
    return 'user-lists'
  }
  
  if (segments.length === 3 && segments[1] === 'lists') {
    return 'user-list'
  }
  
  return 'unknown'
}

export default router