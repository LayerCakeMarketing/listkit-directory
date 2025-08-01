<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CheckClaimsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'claims:check {--fix : Attempt to fix issues}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the claims table structure and fix issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking claims table structure...');

        // Check if claims table exists
        if (!Schema::hasTable('claims')) {
            $this->error('Claims table does not exist!');
            
            if ($this->option('fix')) {
                $this->info('Running migrations to create claims table...');
                $this->call('migrate');
                return 0;
            } else {
                $this->error('Run "php artisan migrate" to create the claims table');
                return 1;
            }
        }

        // Check columns
        $requiredColumns = [
            'id', 'place_id', 'user_id', 'status', 'verification_method',
            'business_email', 'business_phone', 'verification_notes',
            'rejection_reason', 'verified_at', 'approved_at', 'rejected_at',
            'expires_at', 'approved_by', 'rejected_by', 'metadata',
            'created_at', 'updated_at'
        ];

        $existingColumns = Schema::getColumnListing('claims');
        $missingColumns = array_diff($requiredColumns, $existingColumns);

        if (count($missingColumns) > 0) {
            $this->error('Missing columns: ' . implode(', ', $missingColumns));
            
            // Check for business_id instead of place_id
            if (in_array('place_id', $missingColumns) && in_array('business_id', $existingColumns)) {
                $this->warn('Found business_id column instead of place_id');
                
                if ($this->option('fix')) {
                    $this->info('Renaming business_id to place_id...');
                    DB::statement('ALTER TABLE claims RENAME COLUMN business_id TO place_id');
                    $this->info('Column renamed successfully!');
                } else {
                    $this->warn('Use --fix option to rename business_id to place_id');
                }
            } else {
                if ($this->option('fix')) {
                    $this->info('Running migrations to fix table structure...');
                    $this->call('migrate');
                } else {
                    $this->error('Run with --fix option to attempt repairs');
                }
            }
        } else {
            $this->info('✓ All required columns exist');
        }

        // Check foreign key constraints
        try {
            $constraints = DB::select("
                SELECT 
                    tc.constraint_name, 
                    tc.table_name, 
                    kcu.column_name, 
                    ccu.table_name AS foreign_table_name,
                    ccu.column_name AS foreign_column_name 
                FROM 
                    information_schema.table_constraints AS tc 
                    JOIN information_schema.key_column_usage AS kcu
                      ON tc.constraint_name = kcu.constraint_name
                      AND tc.table_schema = kcu.table_schema
                    JOIN information_schema.constraint_column_usage AS ccu
                      ON ccu.constraint_name = tc.constraint_name
                      AND ccu.table_schema = tc.table_schema
                WHERE tc.constraint_type = 'FOREIGN KEY' 
                    AND tc.table_name='claims'
                    AND kcu.column_name='place_id'
            ");

            if (count($constraints) > 0) {
                $constraint = $constraints[0];
                if ($constraint->foreign_table_name === 'directory_entries') {
                    $this->info('✓ Foreign key constraint for place_id is correctly set to directory_entries');
                } else {
                    $this->error('Foreign key constraint for place_id points to wrong table: ' . $constraint->foreign_table_name);
                }
            } else {
                $this->warn('No foreign key constraint found for place_id');
            }
        } catch (\Exception $e) {
            $this->warn('Could not check foreign key constraints: ' . $e->getMessage());
        }

        // Show table stats
        $claimCount = DB::table('claims')->count();
        $pendingCount = DB::table('claims')->where('status', 'pending')->count();
        
        $this->info("\nTable Statistics:");
        $this->info("Total claims: {$claimCount}");
        $this->info("Pending claims: {$pendingCount}");

        // Test mode status
        $this->info("\nTest Mode Status:");
        $this->info("CLAIMING_TEST_MODE: " . (config('services.claiming.test_mode') ? 'Enabled' : 'Disabled'));
        $this->info("CLAIMING_TEST_OTP: " . config('services.claiming.test_otp', 'Not set'));
        $this->info("CLAIMING_AUTO_APPROVE: " . (config('services.claiming.auto_approve') ? 'Enabled' : 'Disabled'));

        return 0;
    }
}