<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;
use App\Models\Channel;

class ValidChannelSlug implements ValidationRule
{
    protected $excludeId;
    protected $isUpdate;

    /**
     * Create a new rule instance.
     *
     * @param int|null $excludeId Channel ID to exclude (for updates)
     * @param bool $isUpdate Whether this is an update operation
     */
    public function __construct($excludeId = null, $isUpdate = false)
    {
        $this->excludeId = $excludeId;
        $this->isUpdate = $isUpdate;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Convert to lowercase for comparison
        $slug = strtolower($value);
        
        // Remove @ prefix if present (for backward compatibility)
        $slug = ltrim($slug, '@');
        
        // Check if slug is in forbidden list
        $forbiddenSlugs = config('channels.forbidden_slugs', []);
        if (in_array($slug, array_map('strtolower', $forbiddenSlugs))) {
            $fail("The :attribute '{$value}' is reserved and cannot be used.");
            return;
        }
        
        // Check for valid slug format (alphanumeric and hyphens only)
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $fail('The :attribute must contain only lowercase letters, numbers, and hyphens.');
            return;
        }
        
        // Check minimum length
        if (strlen($slug) < 3) {
            $fail('The :attribute must be at least 3 characters long.');
            return;
        }
        
        // Check maximum length
        if (strlen($slug) > 50) {
            $fail('The :attribute must not exceed 50 characters.');
            return;
        }
        
        // Check if slug starts or ends with hyphen
        if (str_starts_with($slug, '-') || str_ends_with($slug, '-')) {
            $fail('The :attribute cannot start or end with a hyphen.');
            return;
        }
        
        // Check for consecutive hyphens
        if (str_contains($slug, '--')) {
            $fail('The :attribute cannot contain consecutive hyphens.');
            return;
        }
        
        // Check uniqueness against channels (excluding current if updating)
        $channelQuery = Channel::where('slug', $slug);
        if ($this->excludeId) {
            $channelQuery->where('id', '!=', $this->excludeId);
        }
        if ($channelQuery->exists()) {
            $fail('The :attribute has already been taken.');
            return;
        }
        
        // Check uniqueness against users (username and custom_url)
        if (User::where('username', $slug)->exists() || 
            User::where('custom_url', $slug)->exists()) {
            $fail('The :attribute conflicts with an existing user profile.');
            return;
        }
    }
}