<?php

namespace Database\Seeders;

use App\Models\RegistrationWaitlist;
use Illuminate\Database\Seeder;

class TestWaitlistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $entries = [
            [
                'email' => 'john.doe@example.com',
                'name' => 'John Doe',
                'message' => 'I would love to join your platform!',
                'status' => 'pending',
                'metadata' => ['ip_address' => '192.168.1.1']
            ],
            [
                'email' => 'jane.smith@example.com',
                'name' => 'Jane Smith',
                'message' => null,
                'status' => 'pending',
                'metadata' => ['ip_address' => '192.168.1.2']
            ],
            [
                'email' => 'bob.wilson@example.com',
                'name' => 'Bob Wilson',
                'message' => 'Looking forward to creating lists!',
                'status' => 'invited',
                'invitation_token' => RegistrationWaitlist::generateInvitationToken(),
                'invited_at' => now()->subDays(2),
                'metadata' => ['ip_address' => '192.168.1.3']
            ],
            [
                'email' => 'alice.jones@example.com',
                'name' => 'Alice Jones',
                'message' => 'Excited about this new platform',
                'status' => 'pending',
                'metadata' => ['ip_address' => '192.168.1.4']
            ],
        ];

        foreach ($entries as $entry) {
            RegistrationWaitlist::create($entry);
        }
    }
}