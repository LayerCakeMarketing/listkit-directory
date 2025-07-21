# Listerino - Directory Application

A modern directory and list management application built with Laravel 11, Vue.js 3, and PostgreSQL.

## 🚀 Features

- **Custom User URLs** - Clean URLs like `/@username/list-name`
- **List Management** - Create and organize lists with drag-and-drop
- **Rich Content Editor** - Full-featured text editing with Tiptap
- **Advanced Search** - PostgreSQL full-text search capabilities
- **Image Management** - Cloudflare Images integration
- **Role-Based Access** - Admin, manager, and user roles
- **Responsive Design** - Mobile-first with Tailwind CSS
- **API-First** - RESTful API with Laravel Sanctum

## 📚 Documentation

- **[Development Setup](./DEVELOPMENT_SETUP.md)** - Get started with local development
- **[Production Setup](./PRODUCTION_SETUP.md)** - Production infrastructure and deployment
- **[Deployment Overview](./DEPLOYMENT.md)** - Quick reference for both environments
- **[Coding Guidelines](./CLAUDE.md)** - Project conventions and architecture

## 🛠️ Tech Stack

- **Backend**: Laravel 11, PHP 8.3+
- **Database**: PostgreSQL 15+ with PostGIS
- **Frontend**: Vue.js 3, Vite, Tailwind CSS
- **Caching**: Redis
- **Infrastructure**: Docker (production), Nginx, SSL
- **Services**: Cloudflare Images, ImageKit.io

## 🚦 Quick Start

### Development
```bash
# Clone and setup
git clone [repository-url]
cd directory-app

# Install dependencies
composer install
npm install --prefix frontend

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
createdb illum_local
php artisan migrate

# Start development servers
composer dev
```

Visit http://localhost:8000

### Production Deployment
```bash
# Build frontend assets
npm run build --prefix frontend

# Deploy to production
./deploy-simple.sh
```

## 🏗️ Project Structure

```
├── app/                    # Laravel application
│   ├── Http/Controllers/   # API controllers
│   │   ├── Api/           # Public API endpoints
│   │   └── Api/Admin/     # Admin endpoints
│   └── Models/            # Eloquent models
├── frontend/              # Vue.js SPA
│   ├── src/              
│   │   ├── components/    # Reusable components
│   │   ├── views/        # Page components
│   │   └── router/       # Vue Router config
│   └── dist/             # Production build
├── database/             
│   ├── migrations/       # Database migrations
│   └── seeders/         # Sample data
├── routes/              
│   ├── api.php          # API routes
│   └── web.php          # SPA entry point
└── docs/                # Additional documentation
```

## 🔧 Key Features Implementation

### Custom URLs
- Users can set custom URLs in their profile
- Format: `/@username/list-slug`
- Automatic slug generation from list names

### List Management
- Drag-and-drop reordering
- Public/private visibility
- Rich text descriptions
- Multiple items per list

### Search System
- PostgreSQL full-text search
- Search across lists, items, and descriptions
- Faceted search with categories

## 🔒 Security

- Laravel Sanctum for API authentication
- Environment-based configuration
- HTTPS enforced in production
- CORS properly configured
- Input validation and sanitization

## 📦 API Documentation

### Authentication
- `POST /sanctum/csrf-cookie` - Get CSRF token
- `POST /api/login` - User login
- `POST /api/register` - User registration
- `POST /api/logout` - User logout

### Lists
- `GET /api/lists` - Get public lists
- `GET /api/user-lists` - Get user's lists
- `POST /api/lists` - Create new list
- `PUT /api/lists/{id}` - Update list
- `DELETE /api/lists/{id}` - Delete list

### Admin
- `GET /api/admin/users` - Manage users
- `GET /api/admin/categories` - Manage categories
- `GET /api/admin/settings` - System settings

## 🚀 Production Information

- **URL**: https://listerino.com
- **Server**: DigitalOcean (137.184.113.161)
- **Architecture**: Docker containers with Nginx reverse proxy
- **SSL**: Let's Encrypt with auto-renewal
- **Monitoring**: Laravel logs, Docker stats

## 📤 Deployment

### Quick Deploy (Code Changes Only)
```bash
# Commit your changes
git add .
git commit -m "feat: your feature description"

# Push to trigger automatic deployment
git push listerino main

# Monitor at: https://github.com/lcreative777/listerino/actions
```

### Deploy with Database Changes
```bash
# Create and test migration locally
php artisan make:migration your_migration_name
php artisan migrate

# Commit and push
git add .
git commit -m "feat: feature with migration"
git push listerino main
```

### Manual Deployment
```bash
./deploy-simple.sh
```

See [DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md) for detailed deployment procedures.

## 🤝 Contributing

1. Review [CLAUDE.md](./CLAUDE.md) for coding standards
2. Create feature branch from `main`
3. Write tests for new features
4. Submit pull request with clear description

## 📝 License

This project is proprietary software. All rights reserved.

---

For deployment issues, see [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md).  
For development questions, see [DEVELOPMENT_SETUP.md](./DEVELOPMENT_SETUP.md).