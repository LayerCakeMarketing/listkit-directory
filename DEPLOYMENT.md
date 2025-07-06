# Deployment Guide for listerino.com

This guide covers deploying the Laravel application to a DigitalOcean server at 137.184.113.161.

## Prerequisites

- SSH access to the server (137.184.113.161)
- GitHub repository with your application code
- Domain name (listerino.com) pointing to the server
- Cloudflare account (for image hosting)

## Initial Server Setup

1. **SSH into the server**:
   ```bash
   ssh root@137.184.113.161
   ```

2. **Run the server setup script**:
   ```bash
   # Copy the script to the server
   scp scripts/server-setup.sh root@137.184.113.161:/tmp/
   
   # Run it on the server
   ssh root@137.184.113.161 'bash /tmp/server-setup.sh'
   ```

3. **Configure the database password**:
   - Update PostgreSQL password
   - Update the password in `/var/www/listerino/.env`

## GitHub Actions Setup

1. **Add repository secrets** in GitHub (Settings → Secrets → Actions):
   - `SERVER_HOST`: 137.184.113.161
   - `SERVER_USER`: root (or your deployment user)
   - `SERVER_SSH_KEY`: Your private SSH key
   - `SLACK_WEBHOOK`: (optional) for deployment notifications

2. **Add deploy key to repository**:
   - Go to Settings → Deploy keys
   - Add the public key from the server (`/root/.ssh/listerino_deploy.pub`)
   - Enable write access

## Manual Deployment

To deploy manually, run:

```bash
./deploy-to-listerino.sh
```

## Automatic Deployment

Deployments happen automatically when you push to the `main` branch, thanks to the GitHub Actions workflow.

## Environment Configuration

1. **Update production .env file** on the server:
   ```bash
   nano /var/www/listerino/.env
   ```

2. **Required environment variables**:
   - Database credentials
   - Cloudflare API credentials
   - Mail server settings
   - APP_KEY (generate with `php artisan key:generate`)

## SSL Certificate

The setup script automatically configures Let's Encrypt SSL. To renew:

```bash
certbot renew
```

## Monitoring

1. **Check application logs**:
   ```bash
   tail -f /var/www/listerino/storage/logs/laravel.log
   ```

2. **Check Nginx logs**:
   ```bash
   tail -f /var/log/nginx/listerino.error.log
   tail -f /var/log/nginx/listerino.access.log
   ```

3. **Check PHP logs**:
   ```bash
   tail -f /var/log/php/listerino-error.log
   ```

## Maintenance

1. **Put site in maintenance mode**:
   ```bash
   php artisan down
   ```

2. **Bring site back online**:
   ```bash
   php artisan up
   ```

3. **Clear caches**:
   ```bash
   php artisan optimize:clear
   ```

## Backup

1. **Database backup**:
   ```bash
   pg_dump -U listerino_user listerino > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Full application backup**:
   ```bash
   tar -czf listerino_backup_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/listerino
   ```

## Troubleshooting

1. **Permission issues**:
   ```bash
   sudo chown -R www-data:www-data /var/www/listerino
   sudo chmod -R 755 /var/www/listerino/storage
   ```

2. **502 Bad Gateway**:
   - Check PHP-FPM: `sudo systemctl status php8.3-fpm`
   - Restart: `sudo systemctl restart php8.3-fpm`

3. **Database connection issues**:
   - Check PostgreSQL: `sudo systemctl status postgresql`
   - Test connection: `psql -U listerino_user -d listerino -h localhost`

## Migration from listk.it

All references to listk.it have been updated to listerino.com in:
- Environment files
- Nginx configuration
- Session/CORS configuration

Make sure to update any external services (Cloudflare, email providers, etc.) with the new domain.