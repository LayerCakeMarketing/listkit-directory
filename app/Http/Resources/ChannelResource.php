<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChannelResource extends JsonResource
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
            'avatar_image' => $this->avatar_image,
            'avatar_cloudflare_id' => $this->avatar_cloudflare_id,
            'avatar_url' => $this->avatar_url,
            'banner_image' => $this->banner_image,
            'banner_cloudflare_id' => $this->banner_cloudflare_id,
            'banner_url' => $this->banner_url,
            'is_public' => $this->is_public,
            'followers_count' => $this->followers_count,
            'lists_count' => $this->lists_count,
            'chains_count' => $this->chains_count,
            'is_following' => $this->is_following,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}