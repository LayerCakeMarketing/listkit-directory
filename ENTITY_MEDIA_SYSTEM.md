# Entity Media System Documentation

## Overview
This system allows tracking and viewing media (images) associated with different entity types (User, List, Place, Region) in the application. It leverages Cloudflare Images metadata for filtering and provides a unified interface for media management.

## Key Features

1. **Entity ID Storage**: Every uploaded image stores the entity ID in Cloudflare metadata
2. **Media Viewer Component**: Reusable Vue component to view/upload media for any entity
3. **API Endpoint**: Unified endpoint to fetch media for any entity type
4. **Caching**: 5-minute cache to reduce API calls
5. **Hybrid Approach**: Combines database tracking with Cloudflare metadata

## Implementation Details

### 1. Metadata Structure

When uploading images, the following metadata is stored in Cloudflare:

```json
{
  "entity_id": "123",              // The ID of the associated entity
  "entity_type": "Region",         // User, List, Place, or Region
  "region_name": "Florida",        // Entity-specific metadata
  "region_type": "state",
  "region_level": 1,
  "user_id": "456",               // Who uploaded it
  "context": "cover",             // avatar, cover, gallery, logo, etc.
  "uploaded_via": "drag_drop_uploader"
}
```

### 2. API Endpoints

#### Get Entity Media
```
GET /api/entities/{entityType}/{entityId}/media
```

**Parameters:**
- `entityType`: One of `user`, `list`, `place`, `region` (case-insensitive)
- `entityId`: The ID of the entity

**Response:**
```json
{
  "success": true,
  "entity_type": "region",
  "entity_id": "123",
  "images": [
    {
      "id": "cloudflare-image-id",
      "filename": "florida.jpg",
      "context": "cover",
      "uploaded_at": "2025-07-13T05:43:02.114Z",
      "url": "https://imagedelivery.net/...",
      "variants": ["..."],
      "user": { "id": 1, "name": "John Doe" },
      "metadata": { ... },
      "tracked_in_db": true
    }
  ],
  "total": 5
}
```

#### Clear Cache
```
POST /api/entities/{entityType}/{entityId}/media/clear-cache
```

### 3. MediaViewer Component Usage

#### Basic Usage
```vue
<MediaViewer
  entity-type="region"
  :entity-id="region.id"
  button-text="View Media"
/>
```

#### With Upload Capability
```vue
<MediaViewer
  entity-type="place"
  :entity-id="place.id"
  button-text="Gallery"
  :allow-upload="true"
  upload-context="gallery"
  :title="`Photos of ${place.name}`"
/>
```

#### Props
- `entityType` (required): 'user', 'list', 'place', or 'region'
- `entityId` (required): The entity's ID
- `buttonText`: Text for the trigger button (default: 'View Media')
- `buttonClass`: CSS classes for the button
- `title`: Modal title (default: '{EntityType} Media')
- `allowUpload`: Enable upload functionality (default: false)
- `uploadContext`: Context for uploads (default: 'gallery')
- `autoLoad`: Load media on mount (default: false)

#### Events
- `@upload-success`: Fired when an upload completes
- `@media-loaded`: Fired when media list is loaded

### 4. CloudflareDragDropUploader Integration

Always include entity metadata when using the uploader:

```vue
<CloudflareDragDropUploader
  :max-files="10"
  context="gallery"
  :entity-type="'App\\Models\\Place'"
  :entity-id="place.id"
  :metadata="{
    entity_id: place.id,
    entity_type: 'Place',
    place_name: place.name,
    place_category: place.category
  }"
  @upload-success="handleUploadSuccess"
/>
```

### 5. Backend Service

The `EntityMediaController` performs:
1. Fetches tracked images from database
2. Queries Cloudflare for images with matching metadata
3. Merges and deduplicates results
4. Returns combined data with tracking status

### 6. Database Schema

The `cloudflare_images` table tracks:
- `cloudflare_id`: Unique image ID from Cloudflare
- `entity_type`: Full model class name (e.g., 'App\Models\Region')
- `entity_id`: Foreign key to the entity
- `context`: Image context (avatar, cover, gallery, etc.)
- `metadata`: JSON field with all metadata
- `user_id`: Who uploaded the image

## Usage Examples

### 1. Add Media Button to User Profile
```vue
<template>
  <div class="user-profile">
    <h1>{{ user.name }}</h1>
    <MediaViewer
      entity-type="user"
      :entity-id="user.id"
      button-text="Photos"
      :allow-upload="user.id === currentUser.id"
    />
  </div>
</template>
```

### 2. List Gallery Management
```vue
<template>
  <div class="list-details">
    <MediaViewer
      entity-type="list"
      :entity-id="list.id"
      button-text="Gallery"
      button-class="btn btn-primary"
      :allow-upload="canEditList"
      upload-context="gallery"
      @upload-success="refreshList"
    />
  </div>
</template>
```

### 3. Place Photos with Custom Handler
```vue
<MediaViewer
  entity-type="place"
  :entity-id="place.id"
  :auto-load="true"
  @media-loaded="updatePhotoCount"
/>
```

## Best Practices

1. **Always Include Entity Metadata**: When uploading, always include entity_id and entity_type
2. **Use Appropriate Contexts**: Use consistent context values (avatar, cover, gallery, logo)
3. **Cache Wisely**: The 5-minute cache reduces API calls but may delay updates
4. **Handle Permissions**: Check user permissions before enabling uploads
5. **Optimize Queries**: For large collections, consider pagination

## Security Considerations

1. **Authorization**: The API checks if the entity exists but you should add permission checks
2. **Entity Validation**: Only valid entity types are accepted
3. **User Context**: Track who uploads what for audit trails
4. **Rate Limiting**: Consider adding rate limits for upload URLs

## Troubleshooting

### Images Not Appearing
1. Check if metadata includes entity_id
2. Verify entity_type matches exactly
3. Clear cache: `POST /api/entities/{type}/{id}/media/clear-cache`
4. Check browser console for API errors

### Upload Failures
1. Verify Cloudflare API credentials
2. Check file size limits (10MB max)
3. Ensure metadata doesn't exceed 1KB
4. Verify entity exists before upload

### Performance Issues
1. Limit Cloudflare queries to 500 images
2. Use database tracking for faster queries
3. Implement pagination for large galleries
4. Consider background sync jobs

## Future Enhancements

1. **Bulk Operations**: Select and delete multiple images
2. **Search/Filter**: Filter by upload date, user, context
3. **Reordering**: Drag-and-drop to reorder gallery images
4. **Tags**: Add searchable tags to images
5. **Direct Cloudflare Filtering**: When Cloudflare adds metadata search
6. **Webhooks**: Real-time updates when images are uploaded
7. **CDN Integration**: Automatic cache purging on updates