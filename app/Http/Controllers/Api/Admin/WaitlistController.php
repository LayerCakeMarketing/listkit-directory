<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationWaitlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationInvitation;

class WaitlistController extends Controller
{
    /**
     * Get paginated waitlist entries
     */
    public function index(Request $request)
    {
        $query = RegistrationWaitlist::query();
        
        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by email or name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        
        return $query->orderBy('created_at', 'desc')
                    ->paginate($request->per_page ?? 20);
    }

    /**
     * Get waitlist statistics
     */
    public function stats()
    {
        return response()->json([
            'total' => RegistrationWaitlist::count(),
            'pending' => RegistrationWaitlist::pending()->count(),
            'invited' => RegistrationWaitlist::invited()->count(),
            'registered' => RegistrationWaitlist::registered()->count(),
        ]);
    }

    /**
     * Send invitation to a single waitlist entry
     */
    public function invite($id)
    {
        $entry = RegistrationWaitlist::findOrFail($id);
        
        if ($entry->status !== 'pending') {
            return response()->json([
                'message' => 'This entry has already been processed.'
            ], 400);
        }
        
        // Mark as invited
        $entry->markAsInvited();
        
        // TODO: Send invitation email
        // Mail::to($entry->email)->send(new RegistrationInvitation($entry));
        
        return response()->json([
            'message' => 'Invitation sent successfully.',
            'entry' => $entry
        ]);
    }

    /**
     * Send invitations to multiple waitlist entries
     */
    public function inviteBulk(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:registration_waitlists,id'
        ]);
        
        $entries = RegistrationWaitlist::whereIn('id', $request->ids)
                                      ->where('status', 'pending')
                                      ->get();
        
        $invited = 0;
        foreach ($entries as $entry) {
            $entry->markAsInvited();
            // TODO: Send invitation email
            // Mail::to($entry->email)->send(new RegistrationInvitation($entry));
            $invited++;
        }
        
        return response()->json([
            'message' => "{$invited} invitations sent successfully.",
            'invited_count' => $invited
        ]);
    }

    /**
     * Delete a waitlist entry
     */
    public function destroy($id)
    {
        $entry = RegistrationWaitlist::findOrFail($id);
        $entry->delete();
        
        return response()->json([
            'message' => 'Entry deleted successfully.'
        ]);
    }
}