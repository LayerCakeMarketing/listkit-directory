# Component Migration Guide: Inertia to SPA

## Migration Pattern

### 1. Inertia Component (Before)
```vue
<!-- Pages/Profile/Edit.vue -->
<script setup>
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
  user: Object,
  categories: Array
})

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  bio: props.user.bio
})

function submit() {
  form.patch(route('profile.update'))
}
</script>
```

### 2. SPA Component (After)
```vue
<!-- Pages/Profile/EditSPA.vue -->
<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'

const user = ref({})
const form = reactive({
  name: '',
  email: '',
  bio: ''
})

async function fetchData() {
  const response = await axios.get('/api/profile')
  user.value = response.data.user
  
  // Populate form
  form.name = user.value.name
  form.email = user.value.email
  form.bio = user.value.bio
}

async function submit() {
  try {
    await axios.put('/api/profile', form)
    // Handle success
  } catch (error) {
    // Handle errors
  }
}

onMounted(() => {
  fetchData()
})
</script>
```

## Key Differences

### 1. **Data Loading**
- **Inertia**: Props passed from Laravel controller
- **SPA**: Data fetched via API calls in `onMounted()`

### 2. **Form Handling**
- **Inertia**: `useForm()` with `.post()`, `.patch()`, etc.
- **SPA**: Native forms with axios calls

### 3. **Routing**
- **Inertia**: `route('name')` helper
- **SPA**: Vue Router with `router.push()` or `<router-link>`

### 4. **Authentication**
- **Inertia**: `$page.props.auth.user`
- **SPA**: Pinia store or API call to `/api/user`

## Migration Steps for Each Component

### Profile Edit
1. ✅ API Endpoint: `/api/profile` (GET, PUT)
2. ✅ Component: `EditSPA.vue`
3. ✅ Features: Update profile, check custom URL, upload images

### Public Profile (My Page)
1. ✅ API Endpoint: `/api/page/{customUrl}`
2. ✅ Component: `ShowSPA.vue`
3. ✅ Features: Display profile, lists, follow/unfollow

### Lists Components
1. **My Lists**
   - API: `/api/lists` (GET, POST)
   - Component: `Lists/IndexSPA.vue`
   - Features: CRUD operations, reordering

2. **Public Lists**
   - API: `/api/users/{username}/lists`
   - Component: `Lists/PublicSPA.vue`
   - Features: Browse, search, filter

3. **List Detail**
   - API: `/api/lists/{id}`
   - Component: `Lists/ShowSPA.vue`
   - Features: View items, add/remove items

### Places/Directory
1. **Browse Places**
   - API: `/api/directory`
   - Component: `Places/IndexSPA.vue`
   - Features: Search, filter by category

2. **Place Detail**
   - API: `/api/directory/{id}`
   - Component: `Places/ShowSPA.vue`
   - Features: View details, claim business

3. **Create/Edit Place**
   - API: `/api/directory` (POST), `/api/directory/{id}` (PUT)
   - Component: `Places/CreateEditSPA.vue`
   - Features: Form with image upload

### Regions
1. **Region Landing**
   - API: `/api/regions/by-slug/{state}/{city?}/{neighborhood?}`
   - Component: Already created as `ShowSPA.vue`
   - Features: Show region info, featured entries, child regions

## Vue Router Configuration

Add all routes to `/resources/js/router/index.js`:

```javascript
// Profile routes
{
  path: '/profile/edit',
  name: 'ProfileEdit',
  component: () => import('@/Pages/Profile/EditSPA.vue'),
  meta: { requiresAuth: true }
},
{
  path: '/:username',
  name: 'UserProfile',
  component: () => import('@/Pages/Profile/ShowSPA.vue')
},

// Lists routes
{
  path: '/lists',
  name: 'Lists',
  component: () => import('@/Pages/Lists/IndexSPA.vue'),
  meta: { requiresAuth: true }
},
{
  path: '/:username/lists',
  name: 'UserLists',
  component: () => import('@/Pages/Lists/PublicSPA.vue')
},
{
  path: '/:username/lists/:slug',
  name: 'UserList',
  component: () => import('@/Pages/Lists/ShowSPA.vue')
},

// Places routes
{
  path: '/places',
  name: 'Places',
  component: () => import('@/Pages/Places/IndexSPA.vue')
},
{
  path: '/places/create',
  name: 'PlaceCreate',
  component: () => import('@/Pages/Places/CreateEditSPA.vue'),
  meta: { requiresAuth: true }
},
{
  path: '/places/:category/:subcategory/:slug',
  name: 'Place',
  component: () => import('@/Pages/Places/ShowSPA.vue')
}
```

## Benefits After Migration

1. **Route Count**: From 65+ Laravel routes to 1 catch-all
2. **Performance**: No server-side route resolution for navigation
3. **Scalability**: Millions of dynamic URLs without impacting Laravel
4. **User Experience**: Instant navigation, no full page reloads
5. **API-First**: Clean separation, easier to add mobile apps later