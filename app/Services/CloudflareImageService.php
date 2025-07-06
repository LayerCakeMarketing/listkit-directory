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

    public function __construct()
    {
        $this->accountId = config('services.cloudflare.account_id');
        $this->apiToken = config('services.cloudflare.images_token');
        $this->email = config('services.cloudflare.email');
        $this->baseUrl = "https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1";
        
        if (empty($this->accountId) || empty($this->apiToken) || empty($this->email)) {
            throw new Exception('Cloudflare Images credentials not configured');
        }
    }

    /**
     * Generate a signed upload URL for direct client uploads
     */
    public function generateSignedUploadUrl(array $metadata = []): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/direct_upload", [
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
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiToken,
            ])->attach(
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
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiToken,
            ])->delete("{$this->baseUrl}/{$imageId}");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to delete image from Cloudflare Images', [
                'image_id' => $imageId,
                'error' => $e->getMessage()
            ]);
            return false;
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
        // Check file size (14MB max)
        if ($file->getSize() > 14 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 14MB allowed.');
        }

        // Check file type
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception('Invalid file type. Only JPEG, PNG, GIF, WebP, and AVIF are allowed.');
        }

        // Check image dimensions (optional)
        $imageInfo = getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new Exception('Invalid image file.');
        }

        [$width, $height] = $imageInfo;
        if ($width > 4000 || $height > 4000) {
            throw new Exception('Image dimensions too large. Maximum 4000x4000 pixels.');
        }
    }

    /**
     * Get detailed image information from Cloudflare
     */
    public function getImageDetails(string $imageId): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiToken,
            ])->get("{$this->baseUrl}/{$imageId}");

            if (!$response->successful()) {
                throw new Exception('Failed to get image details from Cloudflare');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare image details: ' . $e->getMessage(), [
                'image_id' => $imageId
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
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiToken,
            ])->get("{$this->baseUrl}/stats");

            if ($response->successful()) {
                return $response->json()['result'];
            }

            return [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare Images stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * List all variants configured in Cloudflare Images
     */
    public function listVariants(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiToken,
            ])->get("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1/variants");

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
            $response = Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->patch("https://api.cloudflare.com/client/v4/accounts/{$this->accountId}/images/v1/variants/{$id}", [
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