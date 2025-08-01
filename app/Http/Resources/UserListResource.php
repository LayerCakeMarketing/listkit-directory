<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserListResource extends JsonResource
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
            'featured_image_url' => $this->featured_image_url,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'is_draft' => $this->is_draft,
            'views_count' => $this->views_count,
            'items_count' => $this->whenCounted('items'),
            'owner_type' => $this->owner_type,
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', function() {
                if ($this->owner_type === 'App\Models\Channel') {
                    return new ChannelResource($this->owner);
                }
                return new UserResource($this->owner);
            }),
            'user' => new UserResource($this->whenLoaded('user')),
            'channel' => new ChannelResource($this->whenLoaded('channel')),
            'category' => $this->whenLoaded('category'),
            'tags' => $this->whenLoaded('tags'),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}