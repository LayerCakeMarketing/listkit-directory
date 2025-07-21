# Deployment Overview

This repository includes comprehensive documentation for both development and production environments.

## Documentation Structure

### 📘 [DEVELOPMENT_SETUP.md](./DEVELOPMENT_SETUP.md)
Complete guide for setting up your local development environment, including:
- Prerequisites and initial setup
- Running development servers
- Testing and code quality
- Common development tasks
- Troubleshooting tips

### 📗 [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md)
Detailed documentation of the production infrastructure, including:
- Docker services configuration
- Nginx setup and SSL
- Deployment process
- Maintenance procedures
- Security best practices

## Quick Reference

### Development
```bash
# Start all development services
composer dev

# Or run individually
php artisan serve
npm run dev --prefix frontend
```

### Production Deployment
```bash
# Build frontend
npm run build --prefix frontend

# Deploy to production (from local)
./deploy-simple.sh
```

### Key Differences

| Aspect | Development | Production |
|--------|-------------|------------|
| Database | Local PostgreSQL | Docker PostgreSQL |
| Redis | Not required | Docker Redis |
| PHP | Native installation | Docker container |
| Frontend | Vite dev server | Built static files |
| SSL | Not required | Let's Encrypt |
| URL | http://localhost:8000 | https://listerino.com |

## Architecture

```
Production Infrastructure:
┌─────────────────────┐
│   Nginx (Host)      │ ← SSL Termination
├─────────────────────┤
│ Docker Containers:  │
│ - listerino_app     │ ← Laravel (port 8001)
│ - listerino_db      │ ← PostgreSQL
│ - listerino_redis   │ ← Cache/Sessions
└─────────────────────┘
```

## Important Notes

1. **Environment Files**: Never commit `.env` files. Use `.env.example` as template.

2. **Database Migrations**: Always test migrations locally before deploying.

3. **Frontend Builds**: Build frontend assets locally before deployment to avoid server resource usage.

4. **Docker on Production**: The production server uses Docker for all services except Nginx.

## Support

For detailed instructions, refer to the specific documentation files:
- Development issues → [DEVELOPMENT_SETUP.md](./DEVELOPMENT_SETUP.md)
- Production issues → [PRODUCTION_SETUP.md](./PRODUCTION_SETUP.md)
- Application architecture → [CLAUDE.md](./CLAUDE.md)