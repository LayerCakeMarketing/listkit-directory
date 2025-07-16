<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class RegistrationWaitlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'message',
        'status',
        'invitation_token',
        'invited_at',
        'registered_at',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'invited_at' => 'datetime',
        'registered_at' => 'datetime',
    ];

    /**
     * Generate a unique invitation token
     */
    public static function generateInvitationToken(): string
    {
        do {
            $token = Str::random(40);
        } while (self::where('invitation_token', $token)->exists());

        return $token;
    }

    /**
     * Scope for pending waitlist entries
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for invited entries
     */
    public function scopeInvited($query)
    {
        return $query->where('status', 'invited');
    }

    /**
     * Scope for registered entries
     */
    public function scopeRegistered($query)
    {
        return $query->where('status', 'registered');
    }

    /**
     * Mark as invited
     */
    public function markAsInvited(): void
    {
        $this->update([
            'status' => 'invited',
            'invitation_token' => self::generateInvitationToken(),
            'invited_at' => now(),
        ]);
    }

    /**
     * Mark as registered
     */
    public function markAsRegistered(): void
    {
        $this->update([
            'status' => 'registered',
            'registered_at' => now(),
        ]);
    }
}
