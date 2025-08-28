<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CloudflareImageService
{
    private string $accountId;
    private string $apiToken;
    private string $email;
    private string $baseUrl;
    private bool $useBearer;

    public function __construct()
    {
        $this->accountId = config('services.cloudflare.account_id');
        $this->apiToken = config('services.cloudflare.images_token');
        $this->email = config('services.cloudflare.email', '');
        $this->baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1";
        
        // Detect auth method: API tokens are longer (40+ chars) and contain underscores
        // Global API keys are 32-37 chars hex
        $this->useBearer = strlen($this->apiToken) > 37 || str_contains($this->apiToken, '_');
        
        Log::info('Cloudflare auth method', [
            'token_length' => strlen($this->apiToken),
            'has_underscore' => str_contains($this->apiToken, '_'),
            'use_bearer' => $this->useBearer,
            'has_email' => !empty($this->email)
        ]);
        
        if (empty($this->accountId) || empty($this->apiToken)) {
            throw new Exception('Cloudflare Images credentials not configured');
        }
        
        if (!$this->useBearer && empty($this->email)) {
            throw new Exception('Cloudflare email is required when using Global API Key');
        }
    }

    /**
     * Get authentication headers based on the token type
     */
    private function getAuthHeaders(): array
    {
        if ($this->useBearer) {
            return [
                'Authorization' => 'Bearer ' . $this->apiToken,
            ];
        } else {
            return [
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiToken,
            ];
        }
    }

    /**
     * Generate a signed upload URL for direct client uploads
     */
    public function generateSignedUploadUrl(array $metadata = []): array
    {
        try {
            $response = Http::withHeaders(array_merge(
                $this->getAuthHeaders(),
                ['Content-Type' => 'application/json']
            ))->post("{$this->baseUrl}/direct_upload", [
                'expiry' => now()->addMinutes(30)->toISOString(), // 30 minute expiry
                'metadata' => $metadata,
                'requireSignedURLs' => false, // Make images publicly accessible
            ]);

            Log::info('Cloudflare direct upload response', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if (!$response->successful()) {
                Log::error('Failed to generate Cloudflare signed upload URL', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                throw new Exception('Failed to generate signed upload URL');
            }

            $data = $response->json();
            
            return [
                'uploadURL' => $data['result']['uploadURL'],
                'id' => $data['result']['id'],
            ];

        } catch (Exception $e) {
            Log::error('Signed upload URL generation error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Upload image to Cloudflare Images
     */
    public function uploadImage(UploadedFile $file, array $options = []): array
    {
        try {
            // Validate file
            $this->validateFile($file);

            // Prepare the request
            $response = Http::withHeaders(
                $this->getAuthHeaders()
            )->attach(
                'file', 
                file_get_contents($file->getRealPath()), 
                $file->getClientOriginalName()
            )->post($this->baseUrl, array_filter([
                'metadata' => json_encode($options['metadata'] ?? []),
                'requireSignedURLs' => $options['requireSignedURLs'] ?? false,
            ]));

            if (!$response->successful()) {
                Log::error('Cloudflare Images upload failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                throw new Exception('Failed to upload image to Cloudflare Images');
            }

            $data = $response->json();
            
            return [
                'id' => $data['result']['id'],
                'filename' => $data['result']['filename'],
                'uploaded' => $data['result']['uploaded'],
                'variants' => $data['result']['variants'],
                'meta' => $data['result']['meta'] ?? [],
            ];

        } catch (Exception $e) {
            Log::error('Image upload error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete image from Cloudflare Images
     */
    public function deleteImage(string $imageId): bool
    {
        try {
            Log::info('Attempting to delete Cloudflare image', ['image_id' => $imageId]);
            
            $response = Http::withHeaders(
                $this->getAuthHeaders()
            )->delete("{$this->baseUrl}/{$imageId}");

            if ($response->successful()) {
                Log::info('Successfully deleted Cloudflare image', ['image_id' => $imageId]);
                return true;
            } else {
                Log::error('Cloudflare API returned error when deleting image', [
                    'image_id' => $imageId,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                // If 404, image doesn't exist, consider it deleted
                if ($response->status() === 404) {
                    Log::info('Image not found in Cloudflare, considering it deleted', ['image_id' => $imageId]);
                    return true;
                }
                
                throw new Exception('Cloudflare API error: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Failed to delete image from Cloudflare Images', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to let controller handle it
        }
    }

    /**
     * Get optimized image URL
     */
    public function getImageUrl(string $imageId, array $options = []): string
    {
        // Use the specific delivery URL from config
        $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
        
        // Default variant or custom transformations
        $variant = $options['variant'] ?? 'public';
        
        // Custom transformations (width, height, fit, etc.)
        // Note: Flexible transformations may not be available on all Cloudflare plans
        if (isset($options['width']) || isset($options['height']) || isset($options['fit'])) {
            // For now, fall back to public variant since transformations return 403
            // TODO: Check if flexible transformations are enabled on this account
            $variant = 'public';
        }
        
        return "{$deliveryUrl}/{$imageId}/{$variant}";
    }

    /**
     * Get multiple variants of an image
     */
    public function getImageVariants(string $imageId): array
    {
        // Use 'public' variant for all sizes - this is the default variant that always works
        $baseUrl = $this->getImageUrl($imageId, ['variant' => 'public']);
        
        return [
            'thumbnail' => $baseUrl,
            'small' => $baseUrl, 
            'medium' => $baseUrl,
            'large' => $baseUrl,
            'original' => $baseUrl,
        ];
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        // Check file size (10MB max for Cloudflare Images)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 10MB allowed by Cloudflare Images.');
        }

        // Check file type - Note: SVG+XML is supported by Cloudflare
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, WebP, AVIF, and SVG are allowed.');
        }

        // Check image dimensions per Cloudflare limits
        $imageInfo = getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new Exception('Invalid image file.');
        }

        [$width, $height] = $imageInfo;
        
        // Cloudflare Images limits: max 12,000 pixels per dimension and 100 megapixels total
        if ($width > 12000 || $height > 12000) {
            throw new Exception('Image dimensions too large. Maximum 12,000 pixels per dimension.');
        }
        
        $totalPixels = $width * $height;
        if ($totalPixels > 100000000) { // 100 megapixels
            throw new Exception('Image resolution too large. Maximum 100 megapixels allowed.');
        }
    }

    /**
     * Get detailed image information from Cloudflare
     */
    public function getImageDetails(string $imageId): array
    {
        try {
            $response = Http::withHeaders(
                $this->getAuthHeaders()
            )->timeout(10)->get("{$this->baseUrl}/{$imageId}");

            // Log the response status for debugging
            Log::debug('Cloudflare getImageDetails response', [
                'image_id' => $imageId,
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);
            
            if ($response->status() === 404) {
                // Image might not be propagated yet
                throw new Exception('Image not found in Cloudflare (may still be processing)');
            }

            if (!$response->successful()) {
                $errorMessage = 'Failed to get image details from Cloudflare';
                $body = $response->json();
                if (isset($body['errors']) && !empty($body['errors'])) {
                    $errorMessage .= ': ' . json_encode($body['errors']);
                }
                throw new Exception($errorMessage);
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare image details: ' . $e->getMessage(), [
                'image_id' => $imageId,
                'error_type' => get_class($e)
            ]);
            throw $e;
        }
    }

    /**
     * Get image stats and usage
     */
    public function getStats(): array
    {
        try {
            // Use the correct v1 stats endpoint
            $statsUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1/stats";
            
            $response = Http::withHeaders(
                $this->getAuthHeaders()
            )->get($statsUrl);

            if ($response->successful()) {
                $result = $response->json()['result'] ?? [];
                
                // Parse the nested count structure from Cloudflare v1 stats
                $currentCount = 0;
                if (isset($result['count']['current'])) {
                    $currentCount = $result['count']['current'];
                } elseif (isset($result['count']) && is_numeric($result['count'])) {
                    $currentCount = $result['count'];
                }
                
                return [
                    'count' => $currentCount,
                    'current' => [
                        'count' => $currentCount,
                        'size' => $result['size'] ?? 0, // May not be available
                    ],
                    'allowed' => [
                        'count' => $result['count']['allowed'] ?? 100000,
                        'size' => 500000000, // 500MB estimate
                    ]
                ];
            }

            Log::warning('Cloudflare stats response not successful', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare Images stats: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * List images from Cloudflare
     */
    public function listImages($page = 1, $perPage = 20): array
    {
        try {
            // Get images with pagination
            $response = Http::withHeaders(
                $this->getAuthHeaders()
            )->get("{$this->baseUrl}", [
                'per_page' => $perPage,
                'page' => $page
            ]);

            if (!$response->successful()) {
                Log::error('Failed to list Cloudflare images', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return ['images' => []];
            }

            $data = $response->json();
            return [
                'images' => $data['result']['images'] ?? [],
                'total' => $data['result']['count'] ?? 0,
            ];
        } catch (Exception $e) {
            Log::error('Failed to list Cloudflare images: ' . $e->getMessage());
            return ['images' => []];
        }
    }

    /**
     * List all variants configured in Cloudflare Images
     */
    public function listVariants(): array
    {
        try {
            $response = Http::withHeaders(
                $this->getAuthHeaders()
            )->get("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1/variants");

            if (!$response->successful()) {
                Log::error('Failed to list Cloudflare variants', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return [];
            }

            return $response->json()['result']['variants'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to list Cloudflare variants: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new variant in Cloudflare Images
     */
    public function createVariant(string $id, array $options): bool
    {
        try {
            // Note: Cloudflare uses PATCH to create/update variants
            $response = Http::withHeaders(array_merge(
                $this->getAuthHeaders(),
                ['Content-Type' => 'application/json']
            ))->patch("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1/variants/{$id}", [
                'id' => $id,
                'options' => $options,
                'neverRequireSignedURLs' => true
            ]);

            if (!$response->successful()) {
                Log::error('Failed to create Cloudflare variant', [
                    'variant_id' => $id,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }

            Log::info('Created Cloudflare variant successfully', [
                'variant_id' => $id,
                'options' => $options
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to create Cloudflare variant: ' . $e->getMessage(), [
                'variant_id' => $id,
                'options' => $options
            ]);
            return false;
        }
    }

    /**
     * Check if a variant exists
     */
    public function variantExists(string $variantId): bool
    {
        $variants = $this->listVariants();
        foreach ($variants as $variant) {
            if ($variant['id'] === $variantId) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get optimized gallery image URL with 16:9 variant
     */
    public function getGalleryImageUrl(string $imageId): string
    {
        // Try to use the gallery-16x9 variant first
        $deliveryUrl = config('services.cloudflare.delivery_url', 'https://imagedelivery.net/nCX0WluV4kb4MYRWgWWi4A');
        
        // Check if gallery-16x9 variant exists
        if ($this->variantExists('gallery-16x9')) {
            return "{$deliveryUrl}/{$imageId}/gallery-16x9";
        }
        
        // Fallback to public variant
        return "{$deliveryUrl}/{$imageId}/public";
    }
}