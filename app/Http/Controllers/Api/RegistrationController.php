<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationWaitlist;
use App\Models\Setting;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    /**
     * Check if registration is enabled
     */
    public function status()
    {
        try {
            $settingsService = app(SiteSettingsService::class);
            $isEnabled = $settingsService->get('allow_registration', true);
            
            return response()->json([
                'registration_enabled' => $isEnabled,
            ]);
        } catch (\Exception $e) {
            // If settings service fails, default to enabled
            return response()->json([
                'registration_enabled' => true,
            ]);
        }
    }

    /**
     * Add email to waitlist
     */
    public function joinWaitlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:registration_waitlists,email',
            'name' => 'nullable|string|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Capture metadata
        $metadata = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
        ];

        $waitlistEntry = RegistrationWaitlist::create([
            'email' => $request->email,
            'name' => $request->name,
            'message' => $request->message,
            'status' => 'pending',
            'metadata' => $metadata,
        ]);

        return response()->json([
            'message' => 'You have been added to the waitlist. We\'ll notify you when registration opens.',
            'success' => true,
        ]);
    }
}