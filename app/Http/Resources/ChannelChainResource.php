<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelChainResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'featured_image' => $this->featured_image,
            'featured_cloudflare_id' => $this->featured_cloudflare_id,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'views_count' => $this->views_count,
            'lists_count' => $this->whenCounted('lists'),
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', function() {
                if ($this->owner_type === 'App\Models\Channel') {
                    return new ChannelResource($this->owner);
                }
                return new UserResource($this->owner);
            }),
            'lists' => UserListResource::collection($this->whenLoaded('lists')),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}