<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'custom_url' => $this->custom_url,
            'email' => $this->when($request->user()?->id === $this->id, $this->email),
            'avatar' => $this->avatar,
            'avatar_cloudflare_id' => $this->avatar_cloudflare_id,
            'avatar_url' => $this->getAvatarUrlAttribute(),
            'bio' => $this->bio,
            'location' => $this->location,
            'website' => $this->website,
            'is_public' => $this->is_public,
            'role' => $this->when($request->user()?->role === 'admin', $this->role),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}