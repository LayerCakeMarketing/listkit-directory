# Deployment Checklist for listerino.com

## Pre-Deployment Setup

- [ ] SSH access to server (137.184.113.161) confirmed
- [ ] DNS A record for listerino.com points to 137.184.113.161
- [ ] DNS A record for www.listerino.com points to 137.184.113.161
- [ ] GitHub repository is up to date with latest code

## Server Setup (First Time Only)

- [ ] Run `./quick-deploy.sh` to set up SSH config
- [ ] Server setup script copied and executed
- [ ] PostgreSQL database and user created
- [ ] Nginx configuration created
- [ ] PHP-FPM pool configured
- [ ] SSL certificate obtained from Let's Encrypt

## Environment Configuration

- [ ] Copy `.env.production` to server as `/var/www/listerino/.env`
- [ ] Update database password in `.env`
- [ ] Add Cloudflare credentials:
  - [ ] CLOUDFLARE_ACCOUNT_ID
  - [ ] CLOUDFLARE_API_TOKEN
  - [ ] CLOUDFLARE_ACCOUNT_HASH
- [ ] Generate new APP_KEY: `php artisan key:generate`
- [ ] Update mail server settings if needed

## GitHub Configuration

- [ ] Run `./scripts/github-setup.sh` for instructions
- [ ] Deploy key added to GitHub repository
- [ ] GitHub Actions secrets configured:
  - [ ] SERVER_HOST
  - [ ] SERVER_USER
  - [ ] SERVER_SSH_KEY
- [ ] Repository URL updated on server

## Initial Deployment

- [ ] Clone repository on server (if not done by setup script)
- [ ] Run `./deploy-to-listerino.sh` for first deployment
- [ ] Verify site loads at https://listerino.com
- [ ] Check SSL certificate is working

## Data Migration (if needed)

- [ ] Export database from old server
- [ ] Import database to new server
- [ ] Update any absolute URLs in database from listk.it to listerino.com
- [ ] Copy uploaded files/images if stored locally

## Post-Deployment Verification

- [ ] Homepage loads correctly
- [ ] Login/registration works
- [ ] Images load properly (Cloudflare integration)
- [ ] Email sending works
- [ ] No errors in logs

## Monitoring Setup

- [ ] Application logs accessible: `/var/www/listerino/storage/logs/laravel.log`
- [ ] Nginx logs accessible: `/var/log/nginx/listerino.*.log`
- [ ] PHP logs accessible: `/var/log/php/listerino-error.log`
- [ ] Automated backups configured (optional)

## Final Steps

- [ ] Update any external services with new domain
- [ ] Update Cloudflare settings if needed
- [ ] Test automatic deployment by pushing to main branch
- [ ] Document any custom configurations

## Rollback Plan

If issues occur:
1. Check logs for errors
2. Restore database from backup if needed
3. Revert to previous commit: `git reset --hard HEAD~1`
4. Clear all caches: `php artisan optimize:clear`