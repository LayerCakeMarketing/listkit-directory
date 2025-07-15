<?php

namespace App\Services;

use ImageKit\ImageKit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ImageKitService
{
    private ImageKit $imageKit;
    private string $publicKey;
    private string $urlEndpoint;

    public function __construct()
    {
        $this->publicKey = config('services.imagekit.public_key', '');
        $privateKey = config('services.imagekit.private_key', '');
        $this->urlEndpoint = config('services.imagekit.url_endpoint');
        
        // Initialize ImageKit SDK
        $this->imageKit = new ImageKit(
            $this->publicKey,
            $privateKey,
            $this->urlEndpoint
        );
    }

    /**
     * Generate authentication parameters for client-side uploads
     * 
     * @return array
     */
    public function getAuthenticationParams(): array
    {
        $authParams = $this->imageKit->getAuthenticationParameters();
        
        // Convert stdClass to array if needed
        if (is_object($authParams)) {
            $authParams = (array) $authParams;
        }
        
        return [
            'token' => $authParams['token'],
            'expire' => $authParams['expire'],
            'signature' => $authParams['signature'],
            'publicKey' => $this->publicKey,
            'urlEndpoint' => $this->urlEndpoint,
        ];
    }

    /**
     * Delete an image from ImageKit
     * 
     * @param string $fileId
     * @return bool
     */
    public function deleteImage(string $fileId): bool
    {
        try {
            $this->imageKit->deleteFile($fileId);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete image from ImageKit', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get image details from ImageKit
     * 
     * @param string $fileId
     * @return array|null
     */
    public function getImageDetails(string $fileId): ?array
    {
        try {
            $result = $this->imageKit->getDetails($fileId);
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to get image details from ImageKit', [
                'file_id' => $fileId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Build transformation URL for an image
     * 
     * @param string $url
     * @param array $transformations
     * @return string
     */
    public function buildTransformationUrl(string $url, array $transformations = []): string
    {
        if (empty($transformations)) {
            return $url;
        }

        // Use ImageKit SDK's URL builder
        $urlOptions = [
            'path' => $this->extractPathFromUrl($url),
            'urlEndpoint' => $this->urlEndpoint,
            'transformation' => [$transformations]
        ];

        return $this->imageKit->url($urlOptions);
    }

    /**
     * Extract path from full ImageKit URL
     * 
     * @param string $url
     * @return string
     */
    private function extractPathFromUrl(string $url): string
    {
        $urlEndpoint = rtrim($this->urlEndpoint, '/');
        if (strpos($url, $urlEndpoint) === 0) {
            return substr($url, strlen($urlEndpoint));
        }
        return $url;
    }

    /**
     * Get commonly used image transformations
     * 
     * @return array
     */
    public function getCommonTransformations(): array
    {
        return [
            'thumbnail' => [
                'width' => 150,
                'height' => 150,
                'crop' => 'at_max',
                'quality' => 80
            ],
            'small' => [
                'width' => 320,
                'height' => 320,
                'crop' => 'at_max',
                'quality' => 85
            ],
            'medium' => [
                'width' => 640,
                'height' => 640,
                'crop' => 'at_max',
                'quality' => 85
            ],
            'large' => [
                'width' => 1024,
                'height' => 1024,
                'crop' => 'at_max',
                'quality' => 90
            ],
            'gallery_thumb' => [
                'width' => 300,
                'height' => 300,
                'crop' => 'maintain_ratio',
                'quality' => 80
            ]
        ];
    }
}