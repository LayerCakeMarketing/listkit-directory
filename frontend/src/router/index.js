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

  // Home page for authenticated users
  {
    path: '/home',
    name: 'Home',
    component: () => import('@/views/Home.vue'),
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
    component: () => import('@/views/region/Show.vue'),
    props: route => ({
      state: route.params.state,
      city: route.params.city,
      neighborhood: route.params.neighborhood,
      level: 3
    })
  },
  {
    path: '/local/:state/:city',
    name: 'City',
    component: () => import('@/views/region/Show.vue'),
    props: route => ({
      state: route.params.state,
      city: route.params.city,
      level: 2
    })
  },
  {
    path: '/local/:state',
    name: 'State',
    component: () => import('@/views/region/Show.vue'),
    props: route => ({
      state: route.params.state,
      level: 1
    })
  },

  // Places routes (new canonical URL structure)
  {
    path: '/places',
    name: 'Places',
    component: () => import('@/views/places/Index.vue')
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

  // Regions routes
  {
    path: '/regions',
    name: 'Regions',
    component: () => import('@/views/regions/Index.vue')
  },
  {
    path: '/regions/:state',
    name: 'RegionState',
    component: () => import('@/views/regions/State.vue'),
    props: true
  },
  {
    path: '/regions/:state/:city',
    name: 'RegionCity', 
    component: () => import('@/views/regions/City.vue'),
    props: true
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
  
  // Edit list
  {
    path: '/mylists/:id/edit',
    name: 'ListEdit',
    component: () => import('@/views/lists/Edit.vue'),
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
    path: '/admin/waitlist',
    name: 'AdminWaitlist',
    component: () => import('@/views/admin/waitlist/Index.vue'),
    meta: { requiresAuth: true, requiresAdmin: true }
  },
  // User profile and list routes (with @ prefix)
  {
    path: '/@:username/lists',
    name: 'UserListsPublic',
    component: () => import('@/views/lists/UserLists.vue'),
    props: true,
    meta: { requiresAuth: true }
  },
  {
    path: '/@:username/:slug',
    name: 'UserList',
    component: () => import('@/views/lists/Show.vue'),
    props: true
    // Remove requiresAuth - authentication is handled by the API based on list visibility
  },
  {
    path: '/@:username',
    name: 'UserProfile',
    component: () => import('@/views/profile/Show.vue'),
    props: true
    // Remove requiresAuth - public profiles should be viewable
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

  // Test auth route
  {
    path: '/test-auth',
    name: 'TestAuth',
    component: () => import('@/views/TestAuth.vue')
  },
  
  // Debug auth route
  {
    path: '/debug-auth',
    name: 'DebugAuth',
    component: () => import('@/views/DebugAuth.vue')
  },
  
  // Marketing/Public pages at root level (must be after all specific routes)
  {
    path: '/:slug',
    name: 'PublicPage',
    component: () => import('@/views/pages/Show.vue'),
    props: true,
    // Only match if it doesn't start with @ and isn't a reserved route
    beforeEnter: (to, from, next) => {
      const reservedPaths = ['admin', 'api', 'places', 'lists', 'regions', 'login', 'register', 
                            'logout', 'dashboard', 'profile', 'settings', 'home', 'local', 'mylists', 'p'];
      const slug = to.params.slug;
      
      console.log('PublicPage route check:', slug, 'Reserved:', reservedPaths.includes(slug));
      
      if (slug.startsWith('@') || reservedPaths.includes(slug)) {
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