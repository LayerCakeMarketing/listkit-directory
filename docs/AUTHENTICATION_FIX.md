# Authentication and Profile Edit Fix Documentation

## Problem Summary

The application experienced two critical issues:
1. **Authentication not persisting** - Users were getting logged out between page navigations with 401 errors on authenticated routes
2. **Profile edit page errors** - The `/profile/edit` page was showing blank data and throwing Vue component errors

## Root Causes

### 1. Session Cookie Encryption Issue
- **Problem**: Laravel was encrypting the session cookie even when using database session driver
- **Impact**: Cookie couldn't be properly read between requests, causing authentication to fail
- **Solution**: Excluded the session cookie from encryption in the EncryptCookies middleware

### 2. User Model Lists Relationship
- **Problem**: The `lists()` method was returning a query builder instead of a proper Eloquent relationship
- **Impact**: `loadCount()` failed with "Call to undefined method Illuminate\Database\Query\Builder::getQuery()"
- **Solution**: Converted to a proper hasMany relationship for polymorphic lists

### 3. Vue Component Initialization
- **Problem**: Profile data wasn't initialized, causing router-link to fail with "Missing required param 'username'"
- **Impact**: Vue threw "instance.update is not a function" errors during component updates
- **Solution**: Properly initialized profile ref with default values and added v-if guards

## Detailed Solutions

### 1. Cookie Encryption Fix

**File**: `/app/Http/Middleware/EncryptCookies.php`
```php
protected $except = [
    // Don't encrypt the session cookie when using database driver
    'directory_app_session',
];
```

**Rationale**: When using database session driver, the cookie only contains a session ID reference, not sensitive data that needs encryption.

### 2. User Model Lists Relationship Fix

**File**: `/app/Models/User.php`

**Before** (Incorrect - returns query builder):
```php
public function lists()
{
    return UserList::where(function($query) {
        $query->where(function($q) {
            $q->where('owner_type', self::class)
              ->where('owner_id', $this->id);
        })
        ->orWhere(function($q) {
            $q->where('user_id', $this->id)
              ->whereNull('owner_type');
        });
    });
}
```

**After** (Correct - returns relationship):
```php
public function lists()
{
    // Use hasMany relationship for polymorphic owned lists
    return $this->hasMany(UserList::class, 'owner_id')
        ->where('owner_type', self::class);
}

// Get all lists (both polymorphic and legacy)
public function getAllLists()
{
    return UserList::where(function($query) {
        $query->where(function($q) {
            $q->where('owner_type', self::class)
              ->where('owner_id', $this->id);
        })
        ->orWhere(function($q) {
            $q->where('user_id', $this->id)
              ->whereNull('owner_type');
        });
    });
}
```

### 3. Vue Component Initialization Fix

**File**: `/frontend/src/views/profile/Edit.vue`

**Before**:
```javascript
const profile = ref({})
```

**After**:
```javascript
const profile = ref({
  username: '',
  custom_url: '',
  name: '',
  email: ''
})
```

**Router-link Protection**:
```vue
<router-link
  v-if="profile.username || profile.custom_url"
  :to="{ name: 'UserProfile', params: { username: profile.custom_url || profile.username } }"
  class="..."
>
  Cancel
</router-link>
<button
  v-else
  @click="$router.push('/home')"
  class="..."
>
  Cancel
</button>
```

### 4. ProfileController Error Handling

**File**: `/app/Http/Controllers/Api/ProfileController.php`

Added try-catch block for loadCount to handle potential errors gracefully:
```php
try {
    $user->loadCount(['followers', 'following', 'lists']);
    $followers_count = $user->followers_count ?? 0;
    $following_count = $user->following_count ?? 0;
    $lists_count = $user->lists_count ?? 0;
} catch (\Exception $e) {
    \Log::error('Error loading counts: ' . $e->getMessage());
    $followers_count = 0;
    $following_count = 0;
    $lists_count = 0;
}
```

## Testing Checklist

1. ✅ User can log in successfully
2. ✅ Authentication persists between page navigations
3. ✅ API routes return authenticated user data
4. ✅ Profile edit page loads without errors
5. ✅ Profile data displays correctly in all tabs
6. ✅ Profile changes save successfully
7. ✅ No Vue component errors in console

## Configuration Requirements

Ensure these environment variables are set correctly:
```env
SESSION_DRIVER=database
SESSION_ENCRYPT=false
SESSION_SAME_SITE=lax
SESSION_SECURE_COOKIE=false
SANCTUM_STATEFUL_DOMAINS=localhost:5174,localhost:5173,localhost:8000
```

## Lessons Learned

1. **Always use proper Eloquent relationships** - Methods that return query builders cannot be used with relationship methods like `loadCount()`
2. **Initialize Vue refs properly** - Empty objects can cause router-link parameter errors
3. **Understand session cookie encryption** - Database sessions don't need encrypted cookies
4. **Add defensive programming** - Use v-if guards and try-catch blocks to handle edge cases gracefully

## Related Files Modified

- `/app/Http/Middleware/EncryptCookies.php`
- `/app/Models/User.php`
- `/app/Http/Controllers/Api/ProfileController.php`
- `/frontend/src/views/profile/Edit.vue`
- `/bootstrap/app.php` (middleware configuration)

This fix ensures stable authentication and a working profile edit experience for all users.