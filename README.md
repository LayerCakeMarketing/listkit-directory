# ListKit - Directory Application

A powerful Laravel-based directory application with custom user URLs, list management, and advanced search capabilities.

**Last Deployment Test**: July 16, 2025 - GitHub Actions CI/CD Working! 🚀

## Features

- 🔗 **Custom User URLs** - Clean URLs like `/yourusername/your-list`
- 📝 **List Management** - Create, edit, and organize lists with drag-and-drop reordering
- 🔍 **Advanced Search** - Full-text search with PostgreSQL
- 👥 **User Management** - Role-based permissions (admin, manager, user)
- 🌐 **Public/Private Lists** - Control list visibility
- 📱 **Responsive Design** - Works on all devices
- 🚀 **Production Ready** - Optimized for scalability

## Tech Stack

- **Backend**: Laravel 11, PHP 8.3+
- **Database**: PostgreSQL 15
- **Frontend**: Vue.js 3, Inertia.js, Tailwind CSS
- **Caching**: Redis
- **Search**: PostgreSQL Full-Text Search
- **Deployment**: DigitalOcean, Nginx

## Local Development

### Requirements
- PHP 8.3+
- Composer
- Node.js 20+
- PostgreSQL 15+
- Redis

### Setup

1. Clone the repository:
```bash
git clone https://github.com/yourusername/listkit-directory.git
cd listkit-directory
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Configure environment:
```bash
cp .env.example .env
# Update database credentials and other settings
php artisan key:generate
```

4. Set up database:
```bash
php artisan migrate
php artisan db:seed
```

5. Build assets:
```bash
npm run dev
# or for production:
npm run build
```

6. Start development server:
```bash
php artisan serve
```

## Deployment

### DigitalOcean Deployment

See `/deploy/setup-digitalocean.md` for complete deployment instructions.

Quick setup:
```bash
# On your DigitalOcean server (as root)
curl -O https://raw.githubusercontent.com/yourusername/listkit-directory/main/deploy/setup-ubuntu.sh
chmod +x setup-ubuntu.sh
./setup-ubuntu.sh
```

### Production Environment

The application is optimized for production with:
- PostgreSQL performance tuning
- Redis caching and sessions
- Queue workers via Supervisor
- Nginx with HTTP/2 and SSL
- Automated backups
- Log rotation

## Custom URL System

Users can set custom URLs in their profile:
- `/eric/my-awesome-list` instead of `/user/123/list/456`
- Fallback system: `custom_url` → `username` → `user_id`
- SEO-friendly and memorable URLs

## API Endpoints

### Lists
- `GET /data/my-lists` - User's lists
- `POST /data/lists` - Create list
- `PUT /data/lists/{id}` - Update list
- `DELETE /data/lists/{id}` - Delete list

### List Items
- `POST /data/lists/{id}/items` - Add item
- `PUT /data/lists/{id}/items/reorder` - Reorder items
- `DELETE /data/lists/{id}/items/{item}` - Remove item

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

This project is proprietary software.
