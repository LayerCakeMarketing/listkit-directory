# Large File Upload Configuration

This application supports image uploads up to 14MB via Cloudflare Images. The following configurations have been implemented:

## PHP Configuration

### Option 1: Using Custom PHP Configuration (Recommended for Development)
Start the Laravel development server with custom PHP settings:

```bash
php -c php-upload.ini artisan serve
```

### Option 2: Modify System PHP Configuration
Edit your main php.ini file at `/usr/local/etc/php/8.4/php.ini` and update:

```ini
upload_max_filesize = 16M
post_max_size = 16M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
max_file_uploads = 20
```

Then restart your web server.

### Option 3: For Apache/Nginx with mod_php
The `.htaccess` file in the `public/` directory includes the necessary configurations.

## Application Configuration

- **Frontend**: Components support up to 14MB uploads
- **Backend**: Laravel validation allows up to 14MB (14336KB)
- **Cloudflare**: Service validates up to 14MB files
- **Async Processing**: Files over 3MB are processed asynchronously

## File Size Limits by Context

- **Profile Avatar**: 14MB max
- **Cover Images**: 14MB max
- **Directory Entry Logos**: 14MB max
- **List Images**: 14MB max

## Testing Upload Limits

Visit `/test-cloudflare-upload` to test different file sizes and verify the configuration is working correctly.

## Production Deployment

For production environments, ensure your web server (Apache/Nginx) and PHP-FPM are configured with appropriate limits:

```nginx
# Nginx
client_max_body_size 16M;
```

```apache
# Apache
LimitRequestBody 16777216
```

## Environment Variables

The following environment variables control upload behavior:

```env
MAX_FILE_UPLOAD_SIZE=14
ASYNC_UPLOAD_THRESHOLD=3
```