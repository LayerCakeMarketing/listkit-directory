// Navigation configuration for different user roles
export const navigationConfig = {
    // Main navigation items for all authenticated users
    main: [
        {
            name: 'Dashboard',
            href: 'dashboard',
            icon: 'home',
            roles: ['admin', 'manager', 'editor', 'business_owner', 'user']
        },
    ],
    
    // Admin section navigation
    admin: [
        {
            name: 'Admin Dashboard',
            href: 'admin.dashboard',
            icon: 'chart-bar',
            roles: ['admin', 'manager']
        },
        {
            name: 'Users',
            href: 'admin.users',
            icon: 'users',
            roles: ['admin', 'manager']
        },
        {
            name: 'Directory Entries',
            href: 'admin.entries',
            icon: 'folder',
            roles: ['admin', 'manager', 'editor']
        },
        {
            name: 'Categories',
            href: 'admin.categories',
            icon: 'tag',
            roles: ['admin', 'manager']
        },
        {
            name: 'Media',
            href: 'admin.media',
            icon: 'photograph',
            roles: ['admin', 'manager']
        },
        {
            name: 'Reports',
            href: 'admin.reports',
            icon: 'document-report',
            roles: ['admin']
        },
        {
            name: 'Settings',
            href: 'admin.settings',
            icon: 'cog',
            roles: ['admin']
        }
    ],
    
    // Business owner specific navigation
    business: [
        {
            name: 'My Businesses',
            href: 'business.index',
            icon: 'office-building',
            roles: ['business_owner']
        },
        {
            name: 'Claims',
            href: 'business.claims',
            icon: 'badge-check',
            roles: ['business_owner']
        }
    ],
    
    // User specific navigation
    user: [
        {
            name: 'My Lists',
            href: 'lists.my',  
            icon: 'collection',
            roles: ['user', 'admin', 'manager', 'editor', 'business_owner']
        },
        {
            name: 'Favorites',
            href: 'user.favorites',
            icon: 'heart',
            roles: ['user', 'admin', 'manager', 'editor', 'business_owner']
        }
    ]
}

// Helper function to get navigation items for a specific role
export function getNavigationForRole(userRole) {
    const items = []
    
    // Add main navigation items
    navigationConfig.main.forEach(item => {
        if (item.roles.includes(userRole)) {
            items.push({ ...item, section: 'main' })
        }
    })
    
    // Add admin navigation items
    navigationConfig.admin.forEach(item => {
        if (item.roles.includes(userRole)) {
            items.push({ ...item, section: 'admin' })
        }
    })
    
    // Add business navigation items
    navigationConfig.business.forEach(item => {
        if (item.roles.includes(userRole)) {
            items.push({ ...item, section: 'business' })
        }
    })
    
    // Add user navigation items
    navigationConfig.user.forEach(item => {
        if (item.roles.includes(userRole)) {
            items.push({ ...item, section: 'user' })
        }
    })
    
    return items
}

// Helper function to group navigation by sections
export function getGroupedNavigation(userRole) {
    const groups = {
        main: [],
        admin: [],
        business: [],
        user: []
    }
    
    const items = getNavigationForRole(userRole)
    items.forEach(item => {
        groups[item.section].push(item)
    })
    
    // Remove empty groups
    Object.keys(groups).forEach(key => {
        if (groups[key].length === 0) {
            delete groups[key]
        }
    })
    
    return groups
}