<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get forbidden slugs from config
        $forbiddenSlugs = [
            'admin', 'api', 'auth', 'login', 'logout', 'register', 'forgot-password',
            'reset-password', 'email-verification', 'dashboard', 'profile', 'settings',
            'account', 'user', 'users', 'me', 'home', 'local', 'places', 'place',
            'lists', 'list', 'mylists', 'my-lists', 'myplaces', 'my-places', 'saved',
            'channels', 'channel', 'chains', 'chain', 'search', 'explore', 'discover',
            'trending', 'popular', 'featured', 'notifications', 'messages', 'chat',
            'edit', 'create', 'new', 'delete', 'remove', 'update', 'manage', 'claim',
            'verify', 'about', 'contact', 'help', 'support', 'privacy', 'terms', 'tos',
            'policy', 'legal', 'blog', 'news', 'press', 'p', 'up', 'app', 'assets',
            'public', 'storage', 'vendor', 'api-docs', 'docs', 'documentation',
            'subscribe', 'subscription', 'billing', 'payment', 'stripe', 'webhook',
            'webhooks', 'reports', 'analytics', 'stats', 'statistics', 'media',
            'marketing', 'waitlist', 'css', 'js', 'img', 'images', 'fonts', 'files',
            'upload', 'download', 'export', 'import', 'regions', 'region', 'state',
            'states', 'city', 'cities', 'neighborhood', 'neighborhoods', 'sitemap',
            'robots', 'rss', 'feed', 'amp', 'groups', 'events', 'jobs', 'marketplace',
            'shop', 'store'
        ];
        
        // Get all channels
        $channels = DB::table('channels')->get();
        
        foreach ($channels as $channel) {
            $originalSlug = $channel->slug;
            $newSlug = $originalSlug;
            
            // Remove @ prefix if present
            if (str_starts_with($newSlug, '@')) {
                $newSlug = ltrim($newSlug, '@');
            }
            
            // Check if slug is forbidden or conflicts with existing data
            $needsNewSlug = false;
            
            // Check if it's a forbidden slug
            if (in_array(strtolower($newSlug), array_map('strtolower', $forbiddenSlugs))) {
                $needsNewSlug = true;
            }
            
            // Check if it conflicts with another channel (after removing @)
            $conflictingChannel = DB::table('channels')
                ->where('id', '!=', $channel->id)
                ->where('slug', $newSlug)
                ->exists();
                
            if ($conflictingChannel) {
                $needsNewSlug = true;
            }
            
            // Check if it conflicts with a user
            $conflictingUser = DB::table('users')
                ->where(function ($query) use ($newSlug) {
                    $query->where('username', $newSlug)
                          ->orWhere('custom_url', $newSlug);
                })
                ->exists();
                
            if ($conflictingUser) {
                $needsNewSlug = true;
            }
            
            // Generate a new slug if needed
            if ($needsNewSlug) {
                $baseSlug = Str::slug($channel->name);
                $counter = 1;
                
                do {
                    $candidateSlug = $baseSlug . '-' . $counter;
                    $counter++;
                    
                    $isAvailable = !in_array(strtolower($candidateSlug), array_map('strtolower', $forbiddenSlugs)) &&
                                   !DB::table('channels')->where('id', '!=', $channel->id)->where('slug', $candidateSlug)->exists() &&
                                   !DB::table('users')->where('username', $candidateSlug)->exists() &&
                                   !DB::table('users')->where('custom_url', $candidateSlug)->exists();
                                   
                } while (!$isAvailable);
                
                $newSlug = $candidateSlug;
            }
            
            // Update the channel if the slug changed
            if ($newSlug !== $originalSlug) {
                DB::table('channels')
                    ->where('id', $channel->id)
                    ->update([
                        'slug' => $newSlug,
                        'updated_at' => now()
                    ]);
                    
                // Log the change
                \Log::info("Updated channel slug: {$channel->name} from '{$originalSlug}' to '{$newSlug}'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible as we don't store the original slugs
        // Channels will keep their updated slugs
        \Log::warning('Channel slug migration rollback: Slugs will remain in their updated state');
    }
};
