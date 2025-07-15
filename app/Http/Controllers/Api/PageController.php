<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    /**
     * Display the specified page by slug.
     */
    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (!$page) {
            return response()->json([
                'message' => 'Page not found'
            ], 404);
        }

        return response()->json([
            'data' => $page
        ]);
    }
}