<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'info' => $request->session()->get('info'),
            ],
            'site' => [
                'name' => site_setting('site_name', 'Listerino'),
                'tagline' => site_setting('site_tagline', 'Your Local Directory'),
                'logo_url' => site_setting('logo_url', '/images/listerino_logo.svg'),
                'primary_color' => site_setting('primary_color', '#3B82F6'),
                'allow_registration' => site_setting('allow_registration', true),
            ],
        ];
    }
}
