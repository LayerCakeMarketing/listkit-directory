<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    /**
     * Display a page by slug.
     */
    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->published()
            ->firstOrFail();

        // Return JSON for SPA
        return response()->json([
            'page' => $page,
            'meta' => [
                'title' => $page->meta_title ?: $page->title,
                'description' => $page->meta_description,
            ]
        ]);
    }
}