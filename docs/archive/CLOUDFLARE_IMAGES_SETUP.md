# Cloudflare Images Setup Guide for Listerino

This guide will help you set up Cloudflare Images for your Laravel + Vue application.

## Why Cloudflare Images?

✅ **Automatic optimization** - No server-side image processing needed  
✅ **Global CDN delivery** - Fast loading worldwide  
✅ **Built-in resizing** - On-demand resizing via URL parameters  
✅ **Cost-effective** - Pay per image stored, not bandwidth  
✅ **Reduced server load** - No CPU-intensive processing  

## Setup Steps

### 1. Cloudflare Account Setup

1. **Create/Access Cloudflare Account**
   - Go to [Cloudflare Dashboard](https://dash.cloudflare.com/)
   - Sign up or log in to your account

2. **Enable Cloudflare Images**
   - Navigate to "Images" in the sidebar
   - Click "Subscribe to Cloudflare Images"


3. **Get Your Credentials**
   - **Account ID**: Found in the right sidebar of any Cloudflare dashboard page
   - **Images API Token**: Go to "My Profile" → "API Tokens" → "Create Token"
     - Use "Custom token" template
     - **Permissions**: `Cloudflare Images:Edit`
     - **Account Resources**: Include your account
     - **Zone Resources**: Not needed for Images

### 2. Laravel Configuration

1. **Add Environment Variables**
   Add these to your `.env` file:
   ```env
   CLOUDFLARE_ACCOUNT_ID=your_account_id_here
   CLOUDFLARE_IMAGES_TOKEN=your_images_api_token_here
   CLOUDFLARE_IMAGES_DELIVERY_URL=https://imagedelivery.net
   ```

2. **Run Migrations**
   ```bash
   php artisan migrate
   ```

3. **Set Up Queue Worker** (for async processing)
   ```bash
   # For development
   php artisan queue:work

   # For production (use Supervisor)
   sudo supervisorctl start laravel-worker:*
   ```

### 3. Frontend Integration

1. **Add CSRF Token Meta Tag** (if not already present)
   In your main layout file:
   ```html
   <meta name="csrf-token" content="{{ csrf_token() }}">
   ```

2. **Use the Component**
   ```vue
   <template>
     <CloudflareImageUpload
       v-model="selectedImage"
       label="Profile Picture"
       upload-type="avatar"
       :entity-id="user.id"
       :max-size="10"
       :async-threshold="5"
       @upload-complete="handleUpload"
       @upload-error="handleError"
     />
   </template>

   <script setup>
   import CloudflareImageUpload from '@/Components/ImageUpload/CloudflareImageUpload.vue'

   const selectedImage = ref(null)

   const handleUpload = (image) => {
     console.log('Uploaded:', image)
     // image.urls contains all variants
   }

   const handleError = (error) => {
     console.error('Upload failed:', error)
   }
   </script>
   ```

## Component Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `upload-type` | String | Required | Type: `avatar`, `cover`, `list_image`, `entry_logo` |
| `entity-id` | Number/String | null | ID of related entity (user, list, entry) |
| `label` | String | null | Label for the upload area |
| `max-size` | Number | 10 | Maximum file size in MB |
| `async-threshold` | Number | 5 | Files larger than this (MB) go to async processing |
| `current-image-url` | String | null | URL of existing image |
| `is-required` | Boolean | false | Whether upload is required |
| `show-variants` | Boolean | false | Show available image variants |

## Image Variants

Cloudflare Images automatically generates these variants:

- **thumbnail**: 150x150px (cropped)
- **small**: 400px max (scaled down)
- **medium**: 800px max (scaled down)
- **large**: 1200px max (scaled down)
- **original**: Full size uploaded image

### Custom Variants via URL

You can request custom sizes on-demand:
```javascript
// Get 300x300 cropped version
const url = `https://imagedelivery.net/{account_id}/{image_id}/w=300,h=300,fit=cover`

// Get 600px wide, auto height
const url = `https://imagedelivery.net/{account_id}/{image_id}/w=600`

// Get WebP format
const url = `https://imagedelivery.net/{account_id}/{image_id}/w=800,f=webp`
```

## Performance Tips

### 1. Async Processing
Files larger than `async-threshold` are processed in the background:
- User gets immediate feedback
- No timeout issues for large files
- Better user experience

### 2. Client-Side Validation
The component validates:
- File type (JPEG, PNG, GIF, WebP)
- File size limits
- Image dimensions

### 3. Caching Strategy
- Cloudflare Images includes global CDN
- Images are cached at edge locations
- Add cache headers for your app's image references

### 4. Optimization
- Use appropriate variants for different contexts
- Implement lazy loading for image galleries
- Use WebP format when supported

## Database Schema

Two tables manage the upload process:

### `uploaded_images`
Stores completed uploads:
```sql
- id (primary key)
- user_id (foreign key)
- cloudflare_id (unique Cloudflare image ID)
- type (avatar, cover, list_image, entry_logo)
- entity_id (related entity ID)
- original_name, file_size, mime_type
- variants (JSON of Cloudflare variants)
- meta (JSON of additional metadata)
- timestamps
```

### `image_uploads`
Tracks async upload progress:
```sql
- id (primary key)
- user_id (foreign key)
- type, entity_id, temp_path
- status (pending, processing, completed, failed)
- cloudflare_id, image_record_id
- error_message, completed_at
- timestamps
```

## Error Handling

The system handles various error scenarios:

1. **Validation Errors**: File type, size, dimensions
2. **Upload Failures**: Network issues, API errors
3. **Processing Failures**: Async job failures
4. **Storage Failures**: Cloudflare API issues

All errors are logged and user-friendly messages are displayed.

## Cost Optimization

### Cloudflare Images Pricing
- $5/month for first 100k images
- $1 per additional 100k images
- No bandwidth charges
- Global delivery included

### Storage Management
- Delete unused images regularly
- Implement image lifecycle policies
- Monitor usage via Cloudflare dashboard

## Monitoring & Analytics

Track your image usage:

```php
// Get Cloudflare Images stats
$stats = app(CloudflareImageService::class)->getStats();

// Monitor upload success rates
Log::info('Image upload stats', $stats);
```

## Migration from Local Storage

If migrating from local storage:

1. **Batch Upload Existing Images**
   ```php
   // Create a command to migrate existing images
   php artisan make:command MigrateImagesToCloudflare
   ```

2. **Update Database References**
   - Update image URL fields to use Cloudflare URLs
   - Keep backup of original images initially

3. **Gradual Rollout**
   - Start with new uploads
   - Migrate existing images in batches
   - Monitor for issues

## Security Considerations

1. **API Token Security**
   - Store tokens in environment variables
   - Use read-only tokens where possible
   - Rotate tokens regularly

2. **Upload Validation**
   - Validate file types and sizes
   - Implement rate limiting
   - Check user permissions

3. **Image Access Control**
   - Use signed URLs for private images
   - Implement proper authorization
   - Monitor access patterns

## Troubleshooting

### Common Issues

1. **"Cloudflare Images credentials not configured"**
   - Check `.env` file has correct values
   - Verify account ID and API token

2. **Upload fails with 403 error**
   - Check API token permissions
   - Verify account ID is correct

3. **Async uploads stuck in processing**
   - Check queue worker is running
   - Review Laravel logs for job failures

4. **Images not displaying**
   - Verify Cloudflare delivery URL
   - Check image ID format
   - Ensure images are publicly accessible

### Debug Commands

```bash
# Check queue status
php artisan queue:work --verbose

# Clear failed jobs
php artisan queue:flush

# Monitor logs
tail -f storage/logs/laravel.log
```

## Production Deployment

1. **Environment Variables**
   - Set Cloudflare credentials in production `.env`
   - Configure queue driver (Redis recommended)

2. **Queue Workers**
   - Set up Supervisor for queue processing
   - Configure worker processes and memory limits

3. **Monitoring**
   - Set up log monitoring (Sentry, Bugsnag)
   - Monitor Cloudflare Images usage
   - Track upload success rates

4. **Backup Strategy**
   - Cloudflare Images includes redundancy
   - Consider additional backup for critical images
   - Document recovery procedures

## Support

For issues and questions:
- [Cloudflare Images Documentation](https://developers.cloudflare.com/images/)
- [Cloudflare Community](https://community.cloudflare.com/)
- Laravel documentation for queue and storage