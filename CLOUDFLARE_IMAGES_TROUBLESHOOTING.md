# Cloudflare Images API Troubleshooting Guide

## Overview
This guide documents common issues and solutions when working with Cloudflare Images API in this Laravel/Vue application.

## API Version Differences (v1 vs v2)

### Key Issue: Direct Upload URL Generation
**Problem**: Getting 415 error "Must be uploaded as a form, not as raw image data. Please use multipart/form-data format" when calling `/direct_upload` endpoint.

**Root Cause**: Cloudflare Images API v2 has different requirements for the direct upload endpoint compared to v1.

**Solution**: Use v1 API for direct uploads:
```php
// ❌ WRONG - v2 doesn't work with direct_upload
$this->baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v2";

// ✅ CORRECT - v1 works properly
$this->baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1";
```

### API Endpoint Comparison

| Feature | v1 Endpoint | v2 Endpoint | Notes |
|---------|------------|-------------|-------|
| Direct Upload | `/images/v1/direct_upload` | `/images/v2/direct_upload` | v2 has different requirements, use v1 |
| List Images | `/images/v1` | `/images/v2` | Both work, v2 has better pagination |
| Stats | `/images/v1/stats` | N/A | Only available in v1 |
| Delete Image | `/images/v1/{id}` | `/images/v2/{id}` | Both work |

## Authentication Methods

### 1. Global API Key (Legacy)
- **When to use**: Local development, full account access needed
- **Token format**: 32-37 character hex string (e.g., `e31e4a0da90ec617f0c2b16e47e5a30b68ce3`)
- **Required headers**:
  ```php
  'X-Auth-Email' => 'your-email@example.com',
  'X-Auth-Key' => 'your-global-api-key'
  ```

### 2. API Token (Recommended)
- **When to use**: Production, limited scope access
- **Token format**: 40+ characters with underscores (e.g., `rG3t4_8Xjs-9Zxb6KQoFk_ejWvJqPlDfY2Gx8w4N`)
- **Required headers**:
  ```php
  'Authorization' => 'Bearer your-api-token'
  ```

### Auto-Detection Logic
The `CloudflareImageService` automatically detects which auth method to use:
```php
// API tokens are longer (40+ chars) and contain underscores
// Global API keys are 32-37 chars hex
$this->useBearer = strlen($this->apiToken) > 37 || str_contains($this->apiToken, '_');
```

## Common Error Messages and Solutions

### 1. 415 Error: "Must be uploaded as a form"
**Full Error**: 
```json
{
  "errors": [{
    "code": 5415,
    "message": "Must be uploaded as a form, not as raw image data. Please use multipart/form-data format"
  }]
}
```
**Solution**: Switch from v2 to v1 API endpoint for direct uploads.

### 2. 401 Error: Authentication Failed
**Possible Causes**:
- Wrong API key/token
- Missing email header for Global API Key
- Using Bearer auth with Global API Key or vice versa

**Debug Steps**:
1. Check token length and format
2. Verify email is set in .env for Global API Key
3. Check Laravel logs for auth method detection

### 3. 500 Error: Failed to generate upload URL
**Common Causes**:
- API endpoint version mismatch
- Network/firewall issues
- Cloudflare service disruption

**Debug Steps**:
1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Look for "Cloudflare direct upload response" entries
3. Verify API credentials are correct

## Environment Configuration

### Required .env Variables
```bash
# Cloudflare Account ID (found in dashboard)
CLOUDFLARE_ACCOUNT_ID=your_account_id

# For Global API Key (local dev)
CLOUDFLARE_IMAGES_TOKEN=your_global_api_key
CLOUDFLARE_EMAIL=your-email@example.com

# For API Token (production)
CLOUDFLARE_IMAGES_TOKEN=your_api_token
# Email not needed for API tokens

# Image delivery URL
CLOUDFLARE_IMAGES_DELIVERY_URL=https://imagedelivery.net/your_hash
```

## Debugging Upload Issues

### 1. Enable Detailed Logging
The service already logs key information:
```php
Log::info('Cloudflare auth method', [
    'token_length' => strlen($this->apiToken),
    'has_underscore' => str_contains($this->apiToken, '_'),
    'use_bearer' => $this->useBearer,
    'has_email' => !empty($this->email)
]);

Log::info('Cloudflare direct upload response', [
    'status' => $response->status(),
    'response' => $response->json()
]);
```

### 2. Check Browser Console
Look for:
- Network tab: Check the `/api/cloudflare/upload-url` request
- Console errors: Note the exact error message and status code

### 3. Test API Connection
Use Tinker to test the service directly:
```bash
php artisan tinker
>>> $service = app(App\Services\CloudflareImageService::class);
>>> $stats = $service->getStats();
>>> print_r($stats);
```

## File Upload Flow

1. **Frontend**: User selects file in `CloudflareDragDropUploader.vue`
2. **Backend**: POST to `/api/cloudflare/upload-url` to get signed URL
3. **CloudflareImageService**: Calls Cloudflare's `/direct_upload` endpoint
4. **Frontend**: Uploads file directly to Cloudflare using signed URL
5. **Backend**: POST to `/api/cloudflare/confirm-upload` to track in database

## Quick Fixes Checklist

- [ ] Verify API version (v1 for uploads, not v2)
- [ ] Check .env has correct credentials
- [ ] Confirm auth method matches token type
- [ ] Clear Laravel cache: `php artisan cache:clear`
- [ ] Check Laravel logs for detailed errors
- [ ] Verify Cloudflare service status
- [ ] Test with curl/Postman to isolate issue

## Testing Upload with cURL

### Test v1 Direct Upload (Global API Key):
```bash
curl -X POST "https://api.cloudflare.com/client/v4/accounts/YOUR_ACCOUNT_ID/images/v1/direct_upload" \
  -H "X-Auth-Email: your-email@example.com" \
  -H "X-Auth-Key: your-global-api-key" \
  -H "Content-Type: application/json" \
  -d '{"expiry":"2024-01-01T00:00:00Z","metadata":{"test":"true"}}'
```

### Test v1 Direct Upload (API Token):
```bash
curl -X POST "https://api.cloudflare.com/client/v4/accounts/YOUR_ACCOUNT_ID/images/v1/direct_upload" \
  -H "Authorization: Bearer your-api-token" \
  -H "Content-Type: application/json" \
  -d '{"expiry":"2024-01-01T00:00:00Z","metadata":{"test":"true"}}'
```

## Related Files

- **Backend Service**: `/app/Services/CloudflareImageService.php`
- **API Controller**: `/app/Http/Controllers/Api/CloudflareImageController.php`
- **Frontend Component**: `/frontend/src/components/CloudflareDragDropUploader.vue`
- **Config**: `/config/services.php` (Cloudflare settings)
- **Model**: `/app/Models/CloudflareImage.php`

## Additional Resources

- [Cloudflare Images API Docs](https://developers.cloudflare.com/images/cloudflare-images/api-request/)
- [Direct Upload Documentation](https://developers.cloudflare.com/images/cloudflare-images/upload-images/direct-creator-upload/)
- [API Authentication](https://developers.cloudflare.com/fundamentals/api/get-started/authentication/)