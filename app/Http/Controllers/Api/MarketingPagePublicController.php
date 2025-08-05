<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketingPage;
use Illuminate\Http\Request;

class MarketingPagePublicController extends Controller
{
    /**
     * Get marketing page content by key (public access).
     */
    public function show($key)
    {
        $validKeys = ['places', 'lists', 'register'];
        
        if (!in_array($key, $validKeys)) {
            return response()->json(['message' => 'Invalid page key'], 404);
        }

        $page = MarketingPage::getByKey($key);
        
        return response()->json($page);
    }
}