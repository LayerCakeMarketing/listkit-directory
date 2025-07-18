<?php

namespace App\Traits;

use App\Models\Tag;
use Illuminate\Support\Collection;

trait HasTags
{
    /**
     * Sync tags for the model
     *
     * @param array|Collection $tags Array of tag IDs or tag names
     * @return void
     */
    public function syncTags($tags)
    {
        if (!$tags) {
            $this->tags()->sync([]);
            return;
        }

        $tagIds = collect($tags)->map(function ($tag) {
            // If it's numeric, assume it's an ID
            if (is_numeric($tag)) {
                return $tag;
            }
            
            // If it's an array/object with an ID
            if (is_array($tag) && isset($tag['id'])) {
                return $tag['id'];
            }
            if (is_object($tag) && isset($tag->id)) {
                return $tag->id;
            }
            
            // Otherwise, it's a string name - find or create
            if (is_string($tag)) {
                $tagModel = Tag::findOrCreateByName($tag);
                return $tagModel->id;
            }
            
            return null;
        })->filter()->unique()->values()->toArray();

        $this->tags()->sync($tagIds);
        
        // Update tag counts
        $affectedTags = Tag::whereIn('id', $tagIds)->get();
        foreach ($affectedTags as $tag) {
            $tag->updateCounts();
        }
    }

    /**
     * Add tags without removing existing ones
     *
     * @param array|Collection $tags
     * @return void
     */
    public function attachTags($tags)
    {
        $currentTagIds = $this->tags()->pluck('id')->toArray();
        $newTags = array_merge($currentTagIds, (array) $tags);
        $this->syncTags($newTags);
    }

    /**
     * Remove specific tags
     *
     * @param array|Collection $tags
     * @return void
     */
    public function detachTags($tags)
    {
        $tagIds = collect($tags)->map(function ($tag) {
            if (is_numeric($tag)) {
                return $tag;
            }
            if (is_object($tag) && isset($tag->id)) {
                return $tag->id;
            }
            return null;
        })->filter()->toArray();

        $this->tags()->detach($tagIds);
        
        // Update tag counts
        $affectedTags = Tag::whereIn('id', $tagIds)->get();
        foreach ($affectedTags as $tag) {
            $tag->updateCounts();
        }
    }

    /**
     * Check if model has a specific tag
     *
     * @param string|int|Tag $tag
     * @return bool
     */
    public function hasTag($tag)
    {
        if ($tag instanceof Tag) {
            return $this->tags->contains('id', $tag->id);
        }
        
        if (is_numeric($tag)) {
            return $this->tags->contains('id', $tag);
        }
        
        return $this->tags->contains('name', $tag) || 
               $this->tags->contains('slug', \Illuminate\Support\Str::slug($tag));
    }

    /**
     * Get tag names as array
     *
     * @return array
     */
    public function tagNames()
    {
        return $this->tags->pluck('name')->toArray();
    }

    /**
     * Get tag IDs as array
     *
     * @return array
     */
    public function tagIds()
    {
        return $this->tags->pluck('id')->toArray();
    }
}