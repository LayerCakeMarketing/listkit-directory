<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\User;
use App\Models\Page;

class UniqueUrlSlug implements ValidationRule
{
    protected $type;
    protected $excludeId;
    protected $reservedSlugs = [
        'admin', 'api', 'places', 'lists', 'regions', 'login', 'register', 'logout',
        'dashboard', 'profile', 'settings', 'images', 'cloudflare', 'stats', 'debug',
        'resolve-path', 'home', 'help', 'about', 'contact', 'privacy', 'terms',
        'user', 'users', 'page', 'pages', 'm', 'p', 'mylists'
    ];

    /**
     * Create a new rule instance.
     *
     * @param string $type Either 'user' or 'page'
     * @param int|null $excludeId ID to exclude from uniqueness check
     */
    public function __construct($type, $excludeId = null)
    {
        $this->type = $type;
        $this->excludeId = $excludeId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if it's a reserved slug
        if (in_array(strtolower($value), $this->reservedSlugs)) {
            $fail("The :attribute cannot use a reserved system name.");
            return;
        }

        // For user custom URLs, check against page slugs
        if ($this->type === 'user') {
            $pageExists = Page::where('slug', $value)->exists();
            if ($pageExists) {
                $fail("The :attribute is already used by a page.");
                return;
            }
        }

        // For page slugs, check against user custom URLs
        if ($this->type === 'page') {
            $userQuery = User::where('custom_url', $value);
            if ($this->excludeId) {
                $userQuery->where('id', '!=', $this->excludeId);
            }
            
            if ($userQuery->exists()) {
                $fail("The :attribute is already used by a user profile.");
                return;
            }
        }
    }
}