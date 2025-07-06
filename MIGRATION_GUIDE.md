# Migration Guide: listkit → listerino

This guide covers migrating from the existing listkit installation to listerino.com on the same DigitalOcean server (137.184.113.161).

## Current Setup
- **Server**: 137.184.113.161 (DigitalOcean)
- **Current Path**: `/var/www/listkit`
- **Database**: PostgreSQL database named `listkit`
- **Database User**: `listkit_user` (keeping the same)
- **New Domain**: listerino.com

## Step-by-Step Migration

### 1. Push Code to New Repository

Run locally:
```bash
# Run the migration script
./scripts/migrate-to-listerino.sh
```

This will:
- Add the new repository as a remote
- Push your current code to https://github.com/lcreative777/listerino.git

### 2. Run Server Migration

Copy and run the migration script on the server:
```bash
# Copy the migration script to server
scp /tmp/server-migration.sh root@137.184.113.161:/tmp/

# SSH to server
ssh root@137.184.113.161

# Run the migration
bash /tmp/server-migration.sh
```

### 3. What the Migration Does

1. **Backs up** current installation and database
2. **Creates** new directory `/var/www/listerino`
3. **Clones** code from new repository
4. **Copies** persistent data (storage, uploads)
5. **Duplicates** database as `listerino` (keeps all data)
6. **Updates** Nginx configuration for listerino.com
7. **Installs** dependencies and builds assets
8. **Gets** SSL certificate for listerino.com

### 4. Post-Migration Tasks

1. **Update Cloudflare credentials** in `/var/www/listerino/.env`:
   ```bash
   nano /var/www/listerino/.env
   ```

2. **Test the site**:
   - Visit https://listerino.com
   - Check login works
   - Verify images load
   - Test key functionality

3. **Update DNS** (if not done):
   - Point listerino.com A record to 137.184.113.161
   - Point www.listerino.com A record to 137.184.113.161

### 5. Cleanup (After Verification)

Once everything is working:
```bash
# Remove old installation
sudo rm -rf /var/www/listkit

# Remove old Nginx config
sudo rm /etc/nginx/sites-available/listkit

# Optional: Drop old database (only after thorough testing!)
sudo -u postgres dropdb listkit
```

### 6. Future Deployments

After migration, deploy updates with:
```bash
./deploy-listerino.sh
```

Or push to main branch to trigger automatic deployment via GitHub Actions.

## Important Notes

- **Database**: We're keeping the same database user (`listkit_user`) and password
- **Data**: All your data is preserved during migration
- **Backups**: Created automatically in `/root/` before migration
- **Rollback**: If issues occur, backups are available

## Troubleshooting

1. **SSL Certificate Issues**:
   ```bash
   sudo certbot --nginx -d listerino.com -d www.listerino.com
   ```

2. **Permission Issues**:
   ```bash
   sudo chown -R www-data:www-data /var/www/listerino
   sudo chmod -R 755 /var/www/listerino/storage
   ```

3. **Database Connection Issues**:
   - Check `.env` has correct database name: `listerino`
   - Verify user permissions: `sudo -u postgres psql -c "\l"`

4. **Check Logs**:
   ```bash
   tail -f /var/log/nginx/listerino.error.log
   tail -f /var/www/listerino/storage/logs/laravel.log
   ```